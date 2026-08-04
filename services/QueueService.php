<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/EncounterEventService.php';
require_once __DIR__ . '/EncounterStateService.php';
require_once __DIR__ . '/PermissionService.php';

class QueueService
{
    private PDO $pdo;

    private AuditService $auditService;

    private EncounterEventService $eventService;

    private EncounterStateService $stateService;

    private PermissionService $permissionService;

    private const ACTIVE_QUEUE_STATUSES = [
        'Waiting',
        'Called',
        'In Progress'
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        $this->auditService = new AuditService($pdo);

        $this->eventService = new EncounterEventService($pdo);

        $this->stateService = new EncounterStateService();

        $this->permissionService = new PermissionService($pdo);
    }

    /*
    |--------------------------------------------------------------------------
    | Enqueue Encounter
    |--------------------------------------------------------------------------
    */

    public function enqueueEncounter(
        int $visitId,
        int $departmentId,
        ?int $queuedBy = null,
        ?int $assignedUserId = null,
        ?string $remarks = null
    ): array {
        if ($visitId <= 0 || $departmentId <= 0) {
            return $this->failure('Encounter and department are required.');
        }

        if (!$this->permissionService->canAccessDepartment($departmentId)) {
            $this->permissionService->logDenied(
                $queuedBy,
                $visitId,
                'QUEUE_ENQUEUE_DENIED',
                'User attempted to queue an encounter in an unauthorized department.'
            );

            return $this->failure(
                'You do not have permission to use this department queue.'
            );
        }

        try {
            $ownsTransaction = !$this->pdo->inTransaction();

            if ($ownsTransaction) {
                $this->pdo->beginTransaction();
            }

            $visit = $this->lockVisit($visitId);

            if (!$visit) {
                throw new RuntimeException('Encounter not found.');
            }

            $editable = $this->stateService->validateEditableEncounter(
                $visit
            );

            if (!$editable['success']) {
                throw new RuntimeException($editable['errors'][0]);
            }

            if ((int)$visit['current_department_id'] !== $departmentId) {
                throw new RuntimeException(
                    'Queue department must match the current encounter department.'
                );
            }

            if ($this->hasActiveQueueEntry($visitId)) {
                throw new RuntimeException(
                    'This encounter already has an active queue entry.'
                );
            }

            $position = $this->nextPosition($departmentId);

            $stmt = $this->pdo->prepare("\n
                INSERT INTO visit_queue (\n
                    visit_id,\n
                    department_id,\n
                    assigned_user_id,\n
                    position,\n
                    queue_status,\n
                    remarks\n
                ) VALUES (\n
                    :visit_id,\n
                    :department_id,\n
                    :assigned_user_id,\n
                    :position,\n
                    'Waiting',\n
                    :remarks\n
                )\n
            ");

            $stmt->execute([
                ':visit_id' => $visitId,
                ':department_id' => $departmentId,
                ':assigned_user_id' => $assignedUserId,
                ':position' => $position,
                ':remarks' => $this->normalizeRemarks($remarks)
            ]);

            $queueId = (int)$this->pdo->lastInsertId();

            $this->updateLegacyQueueNumber($visitId, $position);

            $this->recordQueueHistory(
                $visitId,
                'QUEUED',
                'Encounter Queued',
                'Encounter added to the department queue.',
                $departmentId,
                $queuedBy,
                'ENQUEUE'
            );

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            return [
                'success' => true,
                'queue_id' => $queueId,
                'visit_id' => $visitId,
                'department_id' => $departmentId,
                'queue_status' => 'Waiting',
                'position' => $position,
                'errors' => []
            ];
        } catch (Throwable $e) {
            if ($ownsTransaction ?? false) {
                $this->rollback();
            }

            return $this->failure($e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Close Queue Entry Before Department Transfer
    |--------------------------------------------------------------------------
    */

    public function closeActiveForTransfer(
        int $visitId,
        ?int $performedBy = null,
        string $reason = 'transferred',
        string $auditAction = 'TRANSFER_QUEUE_CLOSE'
    ): array {
        if ($visitId <= 0) {
            return $this->failure('Encounter is required.');
        }

        try {
            $ownsTransaction = !$this->pdo->inTransaction();

            if ($ownsTransaction) {
                $this->pdo->beginTransaction();
            }

            $stmt = $this->pdo->prepare("\n
                SELECT *\n
                FROM visit_queue\n
                WHERE visit_id = :visit_id\n
                  AND queue_status IN ('Waiting', 'Called', 'In Progress')\n
                ORDER BY id DESC\n
                LIMIT 1\n
                FOR UPDATE\n
            ");

            $stmt->execute([
                ':visit_id' => $visitId
            ]);

            $queue = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$queue) {
                if ($ownsTransaction) {
                    $this->pdo->commit();
                }

                return [
                    'success' => true,
                    'queue_id' => null,
                    'errors' => []
                ];
            }

            $stmt = $this->pdo->prepare("\n
                UPDATE visit_queue\n
                SET queue_status = 'Cancelled',\n
                    cancelled_at = NOW(),\n
                    remarks = :remarks\n
                WHERE id = :id\n
            ");

            $stmt->execute([
                ':remarks' => 'Queue closed because the encounter was '
                    . $reason . '.',
                ':id' => $queue['id']
            ]);

            $this->clearLegacyQueueNumber($visitId);

            $this->recordQueueHistory(
                $visitId,
                'QUEUE_CANCELLED',
                'Queue Entry Closed',
                'Queue entry closed because the encounter was '
                    . $reason . '.',
                (int)$queue['department_id'],
                $performedBy,
                $auditAction
            );

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            return [
                'success' => true,
                'queue_id' => (int)$queue['id'],
                'errors' => []
            ];
        } catch (Throwable $e) {
            if ($ownsTransaction ?? false) {
                $this->rollback();
            }

            return $this->failure($e->getMessage());
        }
    }

    public function closeActiveForLifecycle(
        int $visitId,
        string $status,
        ?int $performedBy = null
    ): array {
        return $this->closeActiveForTransfer(
            $visitId,
            $performedBy,
            'closed with status ' . $status,
            'STATUS_QUEUE_CLOSE'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Dequeue Encounter
    |--------------------------------------------------------------------------
    */

    public function dequeueEncounter(
        int $queueId,
        ?int $performedBy = null,
        ?string $remarks = null
    ): array {
        return $this->cancelQueueEntry(
            $queueId,
            $performedBy,
            $remarks
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Call Next Patient
    |--------------------------------------------------------------------------
    */

    public function callNextPatient(
        int $departmentId,
        ?int $calledBy = null
    ): array {
        if ($departmentId <= 0) {
            return $this->failure('Department is required.');
        }

        if (!$this->permissionService->canAccessDepartment($departmentId)) {
            $this->permissionService->logDenied(
                $calledBy,
                null,
                'QUEUE_CALL_DENIED',
                'User attempted to call a patient from an unauthorized queue.'
            );

            return $this->failure(
                'You do not have permission to use this department queue.'
            );
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("\n
                SELECT *\n
                FROM visit_queue\n
                WHERE department_id = :department_id\n
                  AND queue_status = 'Waiting'\n
                ORDER BY position IS NULL, position, queued_at, id\n
                LIMIT 1\n
                FOR UPDATE\n
            ");

            $stmt->execute([
                ':department_id' => $departmentId
            ]);

            $queue = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$queue) {
                throw new RuntimeException(
                    'There are no waiting encounters in this department queue.'
                );
            }

            if (!$this->permissionService->canAccessDepartment(
                (int)$queue['department_id']
            )) {
                throw new RuntimeException(
                    'You do not have permission to cancel this queue entry.'
                );
            }

            $visit = $this->lockVisit((int)$queue['visit_id']);

            if (!$visit) {
                throw new RuntimeException('Encounter not found.');
            }

            $editable = $this->stateService->validateEditableEncounter(
                $visit
            );

            if (!$editable['success']) {
                throw new RuntimeException($editable['errors'][0]);
            }

            $stmt = $this->pdo->prepare("\n
                UPDATE visit_queue\n
                SET queue_status = 'Called',\n
                    called_at = NOW(),\n
                    assigned_user_id = COALESCE(\n
                        :assigned_user_id,\n
                        assigned_user_id\n
                    )\n
                WHERE id = :id\n
            ");

            $stmt->execute([
                ':assigned_user_id' => $calledBy,
                ':id' => $queue['id']
            ]);

            $this->recordQueueHistory(
                (int)$queue['visit_id'],
                'CALLED',
                'Patient Called',
                'Encounter called from the department queue.',
                $departmentId,
                $calledBy,
                'CALL'
            );

            $this->pdo->commit();

            return [
                'success' => true,
                'queue_id' => (int)$queue['id'],
                'visit_id' => (int)$queue['visit_id'],
                'queue_status' => 'Called',
                'errors' => []
            ];
        } catch (Throwable $e) {
            $this->rollback();

            return $this->failure($e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Start Service
    |--------------------------------------------------------------------------
    */

    public function startService(
        int $queueId,
        ?int $startedBy = null
    ): array {
        return $this->mutateServiceState(
            $queueId,
            'Called',
            'In Progress',
            'SERVICE_STARTED',
            'Service Started',
            'Encounter service started.',
            'START_SERVICE',
            $startedBy,
            'started_at'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Complete Queue Entry
    |--------------------------------------------------------------------------
    */

    public function completeQueueEntry(
        int $queueId,
        ?int $completedBy = null,
        ?string $remarks = null
    ): array {
        return $this->mutateServiceState(
            $queueId,
            'In Progress',
            'Completed',
            'SERVICE_COMPLETED',
            'Service Completed',
            $this->withRemarks(
                'Encounter service completed.',
                $remarks
            ),
            'COMPLETE_SERVICE',
            $completedBy,
            'completed_at'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Queue Entry
    |--------------------------------------------------------------------------
    */

    public function cancelQueueEntry(
        int $queueId,
        ?int $cancelledBy = null,
        ?string $remarks = null
    ): array {
        if ($queueId <= 0) {
            return $this->failure('Queue entry is required.');
        }

        try {
            $this->pdo->beginTransaction();

            $queue = $this->lockQueue($queueId);

            if (!$queue) {
                throw new RuntimeException('Queue entry not found.');
            }

            if (!$this->permissionService->canAccessDepartment(
                (int)$queue['department_id']
            )) {
                throw new RuntimeException(
                    'You do not have permission to use this queue.'
                );
            }

            if (in_array(
                $queue['queue_status'],
                ['Completed', 'Cancelled'],
                true
            )) {
                throw new RuntimeException(
                    'Completed or cancelled queue entries cannot be modified.'
                );
            }

            $visit = $this->lockVisit((int)$queue['visit_id']);

            if (!$visit) {
                throw new RuntimeException('Encounter not found.');
            }

            $editable = $this->stateService->validateEditableEncounter(
                $visit
            );

            if (!$editable['success']) {
                throw new RuntimeException($editable['errors'][0]);
            }

            $stmt = $this->pdo->prepare("\n
                UPDATE visit_queue\n
                SET queue_status = 'Cancelled',\n
                    cancelled_at = NOW(),\n
                    remarks = :remarks\n
                WHERE id = :id\n
            ");

            $stmt->execute([
                ':remarks' => $this->normalizeRemarks($remarks),
                ':id' => $queueId
            ]);

            $this->clearLegacyQueueNumber((int)$queue['visit_id']);

            $this->recordQueueHistory(
                (int)$queue['visit_id'],
                'QUEUE_CANCELLED',
                'Queue Entry Cancelled',
                $this->withRemarks(
                    'Encounter removed from the department queue.',
                    $remarks
                ),
                (int)$queue['department_id'],
                $cancelledBy,
                'CANCEL_QUEUE'
            );

            $this->pdo->commit();

            return [
                'success' => true,
                'queue_id' => $queueId,
                'visit_id' => (int)$queue['visit_id'],
                'queue_status' => 'Cancelled',
                'errors' => []
            ];
        } catch (Throwable $e) {
            $this->rollback();

            return $this->failure($e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Queue Reads
    |--------------------------------------------------------------------------
    */

    public function getQueueEntry(int $queueId): ?array
    {
        $stmt = $this->pdo->prepare($this->queueSelect() . "\n
            WHERE q.id = :queue_id\n
            LIMIT 1\n
        ");

        $stmt->execute([
            ':queue_id' => $queueId
        ]);

        $queue = $stmt->fetch(PDO::FETCH_ASSOC);

        return $queue ?: null;
    }

    public function getQueueEntryForVisit(int $visitId): ?array
    {
        $stmt = $this->pdo->prepare($this->queueSelect() . "\n
            WHERE q.visit_id = :visit_id\n
              AND q.queue_status IN ('Waiting', 'Called', 'In Progress')\n
            ORDER BY q.id DESC\n
            LIMIT 1\n
        ");

        $stmt->execute([
            ':visit_id' => $visitId
        ]);

        $queue = $stmt->fetch(PDO::FETCH_ASSOC);

        return $queue ?: null;
    }

    public function getDepartmentQueue(
        int $departmentId,
        ?array $statuses = null
    ): array {
        if ($departmentId <= 0) {
            return [];
        }

        $statuses = $statuses ?: self::ACTIVE_QUEUE_STATUSES;

        $placeholders = [];
        $parameters = [
            ':department_id' => $departmentId
        ];

        foreach (array_values($statuses) as $index => $status) {
            $key = ':status_' . $index;
            $placeholders[] = $key;
            $parameters[$key] = $status;
        }

        $stmt = $this->pdo->prepare($this->queueSelect() . "\n
            WHERE q.department_id = :department_id\n
              AND q.queue_status IN (" . implode(',', $placeholders) . ")\n
            ORDER BY q.position IS NULL, q.position, q.queued_at, q.id\n
        ");

        $stmt->execute($parameters);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Internal Queue Mutation Helpers
    |--------------------------------------------------------------------------
    */

    private function mutateServiceState(
        int $queueId,
        string $requiredStatus,
        string $newStatus,
        string $eventType,
        string $eventTitle,
        string $description,
        string $auditAction,
        ?int $performedBy,
        string $timestampColumn
    ): array {
        if ($queueId <= 0) {
            return $this->failure('Queue entry is required.');
        }

        try {
            $this->pdo->beginTransaction();

            $queue = $this->lockQueue($queueId);

            if (!$queue) {
                throw new RuntimeException('Queue entry not found.');
            }

            if ($queue['queue_status'] !== $requiredStatus) {
                throw new RuntimeException(
                    'Queue entry must be ' . $requiredStatus . '.'
                );
            }

            $visit = $this->lockVisit((int)$queue['visit_id']);

            if (!$visit) {
                throw new RuntimeException('Encounter not found.');
            }

            $editable = $this->stateService->validateEditableEncounter(
                $visit
            );

            if (!$editable['success']) {
                throw new RuntimeException($editable['errors'][0]);
            }

            if ((int)$visit['current_department_id']
                !== (int)$queue['department_id']
            ) {
                throw new RuntimeException(
                    'Queue entry does not belong to the current encounter department.'
                );
            }

            if (($visit['current_department_received_status'] ?? 'Pending')
                !== 'Received'
            ) {
                throw new RuntimeException(
                    'Pending transfers cannot start service.'
                );
            }

            $stmt = $this->pdo->prepare("\n
                UPDATE visit_queue\n
                SET queue_status = :queue_status,\n
                    " . $timestampColumn . " = NOW(),\n
                    assigned_user_id = COALESCE(\n
                        :assigned_user_id,\n
                        assigned_user_id\n
                    )\n
                WHERE id = :id\n
            ");

            $stmt->execute([
                ':queue_status' => $newStatus,
                ':assigned_user_id' => $performedBy,
                ':id' => $queueId
            ]);

            if ($newStatus === 'Completed') {
                $this->clearLegacyQueueNumber((int)$queue['visit_id']);
            }

            $this->recordQueueHistory(
                (int)$queue['visit_id'],
                $eventType,
                $eventTitle,
                $description,
                (int)$queue['department_id'],
                $performedBy,
                $auditAction
            );

            $this->pdo->commit();

            return [
                'success' => true,
                'queue_id' => $queueId,
                'visit_id' => (int)$queue['visit_id'],
                'queue_status' => $newStatus,
                'errors' => []
            ];
        } catch (Throwable $e) {
            $this->rollback();

            return $this->failure($e->getMessage());
        }
    }

    private function lockVisit(int $visitId): ?array
    {
        $stmt = $this->pdo->prepare("\n
            SELECT *\n
            FROM visits\n
            WHERE id = :visit_id\n
            LIMIT 1\n
            FOR UPDATE\n
        ");

        $stmt->execute([
            ':visit_id' => $visitId
        ]);

        $visit = $stmt->fetch(PDO::FETCH_ASSOC);

        return $visit ?: null;
    }

    private function lockQueue(int $queueId): ?array
    {
        $stmt = $this->pdo->prepare("\n
            SELECT *\n
            FROM visit_queue\n
            WHERE id = :queue_id\n
            LIMIT 1\n
            FOR UPDATE\n
        ");

        $stmt->execute([
            ':queue_id' => $queueId
        ]);

        $queue = $stmt->fetch(PDO::FETCH_ASSOC);

        return $queue ?: null;
    }

    private function hasActiveQueueEntry(int $visitId): bool
    {
        $placeholders = implode(
            ',',
            array_fill(0, count(self::ACTIVE_QUEUE_STATUSES), '?')
        );

        $stmt = $this->pdo->prepare("\n
            SELECT id\n
            FROM visit_queue\n
            WHERE visit_id = ?\n
              AND queue_status IN (" . $placeholders . ")\n
            LIMIT 1\n
        ");

        $stmt->execute(array_merge(
            [$visitId],
            self::ACTIVE_QUEUE_STATUSES
        ));

        return (bool)$stmt->fetchColumn();
    }

    private function nextPosition(int $departmentId): int
    {
        $placeholders = implode(
            ',',
            array_fill(0, count(self::ACTIVE_QUEUE_STATUSES), '?')
        );

        $stmt = $this->pdo->prepare("\n
            SELECT position\n
            FROM visit_queue\n
            WHERE department_id = ?\n
              AND queue_status IN (" . $placeholders . ")\n
              AND position IS NOT NULL\n
            ORDER BY position DESC, id DESC\n
            LIMIT 1\n
            FOR UPDATE\n
        ");

        $stmt->execute(array_merge(
            [$departmentId],
            self::ACTIVE_QUEUE_STATUSES
        ));

        $position = $stmt->fetchColumn();

        return $position === false ? 1 : ((int)$position + 1);
    }

    private function updateLegacyQueueNumber(
        int $visitId,
        int $position
    ): void {
        $stmt = $this->pdo->prepare("\n
            UPDATE visits\n
            SET queue_number = :position,\n
                updated_at = NOW()\n
            WHERE id = :visit_id\n
        ");

        $stmt->execute([
            ':position' => $position,
            ':visit_id' => $visitId
        ]);
    }

    private function clearLegacyQueueNumber(int $visitId): void
    {
        $stmt = $this->pdo->prepare("\n
            UPDATE visits\n
            SET queue_number = NULL,\n
                updated_at = NOW()\n
            WHERE id = :visit_id\n
        ");

        $stmt->execute([
            ':visit_id' => $visitId
        ]);
    }

    private function recordQueueHistory(
        int $visitId,
        string $eventType,
        string $eventTitle,
        string $description,
        ?int $departmentId,
        ?int $performedBy,
        string $auditAction
    ): void {
        $event = $this->eventService->record(
            $visitId,
            $eventType,
            $eventTitle,
            $description,
            $departmentId,
            $performedBy
        );

        if (!$event['success']) {
            throw new RuntimeException(
                $event['errors'][0] ?? 'Unable to record encounter event.'
            );
        }

        if (!$this->auditService->log(
            $performedBy,
            $visitId,
            'Queue',
            $auditAction,
            $description
        )) {
            throw new RuntimeException('Unable to record audit log.');
        }
    }

    private function queueSelect(): string
    {
        return "\n
            SELECT\n
                q.*,\n
                v.visit_number,\n
                v.visit_status,\n
                v.current_department_received_status,\n
                v.patient_id,\n
                p.hospital_number,\n
                p.first_name,\n
                p.last_name,\n
                d.department_name,\n
                CONCAT(u.first_name, ' ', u.last_name) AS assigned_user_name\n
            FROM visit_queue q\n
            INNER JOIN visits v\n
                ON v.id = q.visit_id\n
            INNER JOIN patients p\n
                ON p.id = v.patient_id\n
            INNER JOIN departments d\n
                ON d.id = q.department_id\n
            LEFT JOIN users u\n
                ON u.id = q.assigned_user_id\n
        ";
    }

    private function normalizeRemarks(?string $remarks): ?string
    {
        $remarks = trim((string)$remarks);

        return $remarks === '' ? null : $remarks;
    }

    private function withRemarks(
        string $description,
        ?string $remarks
    ): string {
        $remarks = $this->normalizeRemarks($remarks);

        return $remarks === null
            ? $description
            : $description . ' Remarks: ' . $remarks;
    }

    private function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function failure(string $message): array
    {
        return [
            'success' => false,
            'errors' => [$message]
        ];
    }
}
