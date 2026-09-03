<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/EncounterEventService.php';
require_once __DIR__ . '/EncounterStateService.php';
require_once __DIR__ . '/QueueService.php';
require_once __DIR__ . '/PermissionService.php';

class VisitService
{
    private PDO $pdo;

    private AuditService $auditService;

    private EncounterEventService $eventService;

    private EncounterStateService $stateService;

    private QueueService $queueService;

    private PermissionService $permissionService;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        $this->auditService = new AuditService($pdo);

        $this->eventService = new EncounterEventService($pdo);

        $this->stateService = new EncounterStateService();

        $this->queueService = new QueueService($pdo);

        $this->permissionService = new PermissionService($pdo);
    }

    /*
    |--------------------------------------------------------------------------
    | Queue Compatibility Wrappers
    |--------------------------------------------------------------------------
    */

    public function enqueueEncounter(
        int $visitId,
        int $departmentId,
        ?int $queuedBy = null,
        ?int $assignedUserId = null,
        ?string $remarks = null
    ): array {
        return $this->queueService->enqueueEncounter(
            $visitId,
            $departmentId,
            $queuedBy,
            $assignedUserId,
            $remarks
        );
    }

    public function dequeueEncounter(
        int $queueId,
        ?int $performedBy = null,
        ?string $remarks = null
    ): array {
        return $this->queueService->dequeueEncounter(
            $queueId,
            $performedBy,
            $remarks
        );
    }

    public function callNextPatient(
        int $departmentId,
        ?int $calledBy = null
    ): array {
        return $this->queueService->callNextPatient(
            $departmentId,
            $calledBy
        );
    }

    public function startService(
        int $queueId,
        ?int $startedBy = null
    ): array {
        return $this->queueService->startService(
            $queueId,
            $startedBy
        );
    }

    public function completeQueueEntry(
        int $queueId,
        ?int $completedBy = null,
        ?string $remarks = null
    ): array {
        return $this->queueService->completeQueueEntry(
            $queueId,
            $completedBy,
            $remarks
        );
    }

    public function cancelQueueEntry(
        int $queueId,
        ?int $cancelledBy = null,
        ?string $remarks = null
    ): array {
        return $this->queueService->cancelQueueEntry(
            $queueId,
            $cancelledBy,
            $remarks
        );
    }

    public function getQueueEntryForVisit(int $visitId): ?array
    {
        return $this->queueService->getQueueEntryForVisit($visitId);
    }

    public function getDepartmentQueue(
        int $departmentId,
        ?array $statuses = null
    ): array {
        return $this->queueService->getDepartmentQueue(
            $departmentId,
            $statuses
        );
    }

    public function listDepartmentWorklist(int $departmentId): array
    {
        if ($departmentId <= 0) {
            return [];
        }

        $queueRows = $this->queueService->getDepartmentQueue(
            $departmentId,
            ['Waiting', 'Called', 'In Progress']
        );

        $rowsByVisit = [];

        foreach ($queueRows as $row) {
            $row['worklist_status'] =
                ($row['current_department_received_status'] ?? '') === 'Pending'
                    ? 'Awaiting Receive'
                    : (string)($row['queue_status'] ?? 'Waiting');
            $row['can_receive'] =
                ($row['current_department_received_status'] ?? '') === 'Pending';
            $rowsByVisit[(int)$row['visit_id']] = $row;
        }

        $stmt = $this->pdo->prepare("
            SELECT
                NULL AS id,
                v.id AS visit_id,
                v.current_department_id AS department_id,
                NULL AS assigned_user_id,
                NULL AS position,
                'Waiting' AS queue_status,
                NULL AS remarks,
                v.created_at AS queued_at,
                v.visit_number,
                v.visit_status,
                v.current_department_received_status,
                v.patient_id,
                p.hospital_number,
                p.first_name,
                p.last_name,
                d.department_name,
                NULL AS assigned_user_name,
                'Awaiting Receive' AS worklist_status,
                1 AS can_receive
            FROM visits v
            INNER JOIN patients p ON p.id = v.patient_id
            INNER JOIN departments d ON d.id = v.current_department_id
            WHERE v.current_department_id = :department_id
              AND v.current_department_received_status = 'Pending'
              AND v.visit_status NOT IN ('Completed', 'Cancelled')
            ORDER BY v.updated_at DESC, v.id DESC
        ");

        $stmt->execute([':department_id' => $departmentId]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rowsByVisit[(int)$row['visit_id']] ??= $row;
        }

        $this->appendDepartmentRequestWorklistRows($rowsByVisit, $departmentId);

        $rows = array_values($rowsByVisit);

        usort($rows, static function (array $a, array $b): int {
            $aPending = (string)($a['worklist_status'] ?? '') === 'Awaiting Receive';
            $bPending = (string)($b['worklist_status'] ?? '') === 'Awaiting Receive';

            if ($aPending !== $bPending) {
                return $aPending ? -1 : 1;
            }

            return strcmp(
                (string)($a['queued_at'] ?? ''),
                (string)($b['queued_at'] ?? '')
            );
        });

        return $rows;
    }

    /*
    |--------------------------------------------------------------------------
    | Create Encounter
    |--------------------------------------------------------------------------
    */

    public function createVisit(
    array $visit,
    int $createdBy
): array {

    if (!$this->permissionService->hasPermission('create_encounter')) {
        $this->permissionService->logDenied(
            $createdBy,
            null,
            'CREATE_ENCOUNTER_DENIED',
            'User attempted to create an encounter without permission.'
        );

        return [
            'success'      => false,
            'visit_id'     => null,
            'visit_number' => null,
            'errors'       => [
                'You do not have permission to create encounters.'
            ]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Input
    |--------------------------------------------------------------------------
    */

    $errors = $this->validate($visit);

    if (!empty($errors)) {

        return [

            'success'      => false,

            'visit_id'     => null,

            'visit_number' => null,

            'errors'       => $errors

        ];

    }

    try {

        $this->pdo->beginTransaction();

        /*
        |--------------------------------------------------------------------------
        | Generate Visit Number
        |--------------------------------------------------------------------------
        */

        $visitNumber = $this->generateVisitNumber();

        /*
        |--------------------------------------------------------------------------
        | Determine Initial Status
        |--------------------------------------------------------------------------
        |
        | The encounter begins in the selected department.
        | Enterprise HIS systems treat the creating department
        | as already having received the patient.
        |
        */

        $stmt = $this->pdo->prepare("

            SELECT department_name

            FROM departments

            WHERE id = :id

            LIMIT 1

        ");

        $stmt->execute([

            ':id' => $visit['current_department_id']

        ]);

        $department = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$department) {

            throw new RuntimeException(
                'Invalid initial department.'
            );

        }

        $initialStatus = $department['department_name'];

        $stateValidation = $this->stateService
            ->validateStatusTransition('Waiting', $initialStatus);

        if (!$stateValidation['success']) {

            throw new RuntimeException(
                'The selected department is not configured for encounter workflow.'
            );

        }

        /*
        |--------------------------------------------------------------------------
        | Create Encounter
        |--------------------------------------------------------------------------
        */

        $sql = "

            INSERT INTO visits (

                visit_number,

                patient_id,

                visit_date,

                visit_type,

                current_department_id,

                visit_status,

                current_department_received_status,

                current_department_received_at,

                current_department_received_by,

                created_by

            )

            VALUES (

                :visit_number,

                :patient_id,

                :visit_date,

                :visit_type,

                :current_department_id,

                :visit_status,

                'Received',

                NOW(),

                :received_by,

                :created_by

            )

        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([

            ':visit_number'          => $visitNumber,

            ':patient_id'            => $visit['patient_id'],

            ':visit_date'            => $visit['visit_date'],

            ':visit_type'            => $visit['visit_type'],

            ':current_department_id' => $visit['current_department_id'],

            ':visit_status'          => $initialStatus,

            ':received_by'           => $createdBy,

            ':created_by'            => $createdBy

        ]);

        $visitId = (int)$this->pdo->lastInsertId();

        /*
        |--------------------------------------------------------------------------
        | Record Initial Encounter Event
        |--------------------------------------------------------------------------
        |
        | This will become the first item in the encounter timeline.
        |
        */

        $this->recordWorkflowHistory(
            $visitId,
            'ENCOUNTER_CREATED',
            'Encounter Created',
            sprintf(
                'Encounter created and patient received in %s.',
                $department['department_name']
            ),
            (int)$visit['current_department_id'],
            $createdBy,
            'Encounter',
            'CREATE'
        );

        $queueResult = $this->queueService->enqueueEncounter(
            $visitId,
            (int)$visit['current_department_id'],
            $createdBy,
            null,
            null,
            false
        );

        if (!$queueResult['success']) {

            throw new RuntimeException(
                $queueResult['errors'][0]
                ?? 'Unable to queue encounter.'
            );

        }

        $this->pdo->commit();

        return [

            'success'      => true,

            'visit_id'     => $visitId,

            'visit_number' => $visitNumber,

            'visit_status' => $initialStatus,

            'errors'       => []

        ];

    } catch (Throwable $e) {

        if ($this->pdo->inTransaction()) {

            $this->pdo->rollBack();

        }

        return [

            'success'      => false,

            'visit_id'     => null,

            'visit_number' => null,

            'errors'       => [
                $e->getMessage() ?: 'Unable to create encounter.'
            ]

        ];

    }

}

    /*
    |--------------------------------------------------------------------------
    | Validate Encounter
    |--------------------------------------------------------------------------
    */

    private function validate(array $visit): array
    {
        $errors = [];

        if (empty($visit['patient_id'])) {

            $errors[] = 'Patient is required.';

        }

        if (empty($visit['visit_date'])) {

            $errors[] = 'Visit date is required.';

        }

        if (empty($visit['visit_type'])) {

            $errors[] = 'Visit type is required.';

        }

        $allowedTypes = [

            'Outpatient',
            'Inpatient',
            'Emergency',
            'Referral'

        ];

        if (
            !empty($visit['visit_type']) &&
            !in_array(
                $visit['visit_type'],
                $allowedTypes,
                true
            )
        ) {

            $errors[] = 'Invalid visit type selected.';

        }

        if (empty($visit['current_department_id'])) {

            $errors[] = 'Receiving department is required.';

        }

        return $errors;
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Visit Number
    |--------------------------------------------------------------------------
    */

    private function generateVisitNumber(): string
    {
        $prefix = 'VIS';
        $year = date('Y');
        $pattern = $prefix . '-' . $year . '-%';

        $stmt = $this->pdo->prepare("
            SELECT MAX(CAST(SUBSTRING_INDEX(visit_number, '-', -1) AS UNSIGNED))
            FROM visits
            WHERE visit_number LIKE :pattern
        ");
        $stmt->execute([':pattern' => $pattern]);

        $next = ((int)$stmt->fetchColumn()) + 1;

        do {
            $visitNumber = sprintf(
                '%s-%s-%06d',
                $prefix,
                $year,
                $next
            );

            $exists = $this->pdo->prepare('
                SELECT COUNT(*)
                FROM visits
                WHERE visit_number = :visit_number
            ');
            $exists->execute([':visit_number' => $visitNumber]);
            $next++;
        } while ((int)$exists->fetchColumn() > 0);

        return $visitNumber;
    }
        /*
    |--------------------------------------------------------------------------
    | Get Encounter By ID
    |--------------------------------------------------------------------------
    */

    public function getVisitById(
    int $id
): ?array {

    $stmt = $this->pdo->prepare("

        SELECT

            v.*,

            /*
            |--------------------------------------------------------------------------
            | Patient
            |--------------------------------------------------------------------------
            */

            p.hospital_number,
            p.first_name,
            p.last_name,
            p.gender,
            p.phone,
            p.date_of_birth,

            /*
            |--------------------------------------------------------------------------
            | Current Department
            |--------------------------------------------------------------------------
            */

            d.department_name,

            /*
            |--------------------------------------------------------------------------
            | Attending Doctor
            |--------------------------------------------------------------------------
            */

            CONCAT(

                doctor.first_name,
                ' ',
                doctor.last_name

            ) AS doctor_name,

            /*
            |--------------------------------------------------------------------------
            | Registered By
            |--------------------------------------------------------------------------
            */

            CONCAT(

                creator.first_name,
                ' ',
                creator.last_name

            ) AS registered_by_name,

            /*
            |--------------------------------------------------------------------------
            | Current Department Receiver
            |--------------------------------------------------------------------------
            */

            CONCAT(

                receiver.first_name,
                ' ',
                receiver.last_name

            ) AS current_department_received_by_name

        FROM visits v

        INNER JOIN patients p
            ON p.id = v.patient_id

        LEFT JOIN departments d
            ON d.id = v.current_department_id

        /*
        |--------------------------------------------------------------------------
        | Doctor
        |--------------------------------------------------------------------------
        */

        LEFT JOIN users doctor
            ON doctor.id = v.attending_doctor_id

        /*
        |--------------------------------------------------------------------------
        | Encounter Creator
        |--------------------------------------------------------------------------
        */

        LEFT JOIN users creator
            ON creator.id = v.created_by

        /*
        |--------------------------------------------------------------------------
        | Department Receiver
        |--------------------------------------------------------------------------
        */

        LEFT JOIN users receiver
            ON receiver.id =
                v.current_department_received_by

        WHERE v.id = :id

        LIMIT 1

    ");

    $stmt->execute([

        ':id' => $id

    ]);

    $visit = $stmt->fetch(PDO::FETCH_ASSOC);

    return $visit ?: null;

}
    
    /*
|--------------------------------------------------------------------------
| Get Visit Number
|--------------------------------------------------------------------------
*/

public function getVisitNumber(
    int $visitId
): ?string {

    $stmt = $this->pdo->prepare("

        SELECT

            visit_number

        FROM visits

        WHERE id = :id

        LIMIT 1

    ");

    $stmt->execute([

        ':id' => $visitId

    ]);

    $visitNumber = $stmt->fetchColumn();

    return $visitNumber !== false
        ? (string)$visitNumber
        : null;

}

public function canAccessDepartmentWorkspace(
    int $visitId
): bool {

    /*
    |--------------------------------------------------------------------------
    | Load Encounter
    |--------------------------------------------------------------------------
    */

    $visit = $this->getVisitById($visitId);

    if (!$visit) {

        return false;

    }

    /*
    |--------------------------------------------------------------------------
    | Department Must Receive Patient
    |--------------------------------------------------------------------------
    */

    return (

        $visit['current_department_received_status']

        ?? 'Pending'

    ) === 'Received';

}
    

    /*
    |--------------------------------------------------------------------------
    | Get Patient Encounters
    |--------------------------------------------------------------------------
    */

    public function getPatientVisits(int $patientId): array
    {
        $stmt = $this->pdo->prepare("

            SELECT

                v.*,

                d.department_name,

                CONCAT(
                    u.first_name,
                    ' ',
                    u.last_name
                ) AS doctor_name

            FROM visits v

            LEFT JOIN departments d
                ON d.id = v.current_department_id

            LEFT JOIN users u
                ON u.id = v.attending_doctor_id

            WHERE v.patient_id = :patient_id

            ORDER BY
                v.visit_date DESC,
                v.id DESC

        ");

        $stmt->execute([

            ':patient_id' => $patientId

        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Active Encounter
    |--------------------------------------------------------------------------
    */

    public function getActiveVisit(
        int $patientId
    ): ?array {

        $stmt = $this->pdo->prepare("

            SELECT

                v.*,

                d.department_name,

                CONCAT(
                    u.first_name,
                    ' ',
                    u.last_name
                ) AS doctor_name

            FROM visits v

            LEFT JOIN departments d
                ON d.id = v.current_department_id

            LEFT JOIN users u
                ON u.id = v.attending_doctor_id

            WHERE
                v.patient_id = :patient_id

            AND
                v.visit_status NOT IN
                (
                    'Completed',
                    'Cancelled'
                )

            ORDER BY
                v.visit_date DESC,
                v.id DESC

            LIMIT 1

        ");

        $stmt->execute([

            ':patient_id' => $patientId

        ]);

        $visit = $stmt->fetch(PDO::FETCH_ASSOC);

        return $visit ?: null;
    }

    /*
|--------------------------------------------------------------------------
| Check for Open Encounter
|--------------------------------------------------------------------------
*/

    public function patientHasOpenVisit(
        int $patientId
    ): bool {

        return $this->getActiveVisit($patientId) !== null;

    }

    /*
    |--------------------------------------------------------------------------
    | Count Patient Encounters
    |--------------------------------------------------------------------------
    */

    public function countPatientVisits(
        int $patientId
    ): int {

        $stmt = $this->pdo->prepare("

            SELECT COUNT(*)

            FROM visits

            WHERE patient_id = :patient_id

        ");

        $stmt->execute([

            ':patient_id' => $patientId

        ]);

        return (int)$stmt->fetchColumn();
    }

    /*
    |--------------------------------------------------------------------------
    | Recent Encounters
    |--------------------------------------------------------------------------
    */

    public function getRecentVisits(
        int $limit = 20
    ): array {

        if (!$this->permissionService->hasPermission(
            'create_encounter'
        )) {

            $this->permissionService->logDenied(
                null,
                null,
                'CREATE_ENCOUNTER_DENIED',
                'User attempted to create an encounter without permission.'
            );

            return [
                'success' => false,
                'visit_id' => null,
                'visit_number' => null,
                'errors' => [
                    'You do not have permission to create encounters.'
                ]
            ];

        }

        $stmt = $this->pdo->prepare("

            SELECT

                v.*,

                p.hospital_number,

                p.first_name,

                p.last_name,

                d.department_name,

                CONCAT(
                    u.first_name,
                    ' ',
                    u.last_name
                ) AS doctor_name

            FROM visits v

            INNER JOIN patients p
                ON p.id = v.patient_id

            LEFT JOIN departments d
                ON d.id = v.current_department_id

            LEFT JOIN users u
                ON u.id = v.attending_doctor_id

            ORDER BY
                v.visit_date DESC,
                v.id DESC

            LIMIT :limit

        ");

        $stmt->bindValue(
            ':limit',
            $limit,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateVisit(
    int $visitId,
    array $visit,
    int $updatedBy
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Validate
        |--------------------------------------------------------------------------
        */

        $errors = [];

        if (empty($visit['visit_type'])) {

            $errors[] = 'Visit type is required.';

        }

        if (empty($visit['current_department_id'])) {

            $errors[] = 'Department is required.';

        }

        if (!empty($errors)) {

            return [

                'success' => false,

                'errors' => $errors

            ];

        }

        /*
        |--------------------------------------------------------------------------
        | Ensure Encounter Exists
        |--------------------------------------------------------------------------
        */

        $currentVisit = $this->getVisitById($visitId);

        if (!$currentVisit
            || !$this->permissionService->canEditEncounter(
                $currentVisit
            )
        ) {

            $this->permissionService->logDenied(
                null,
                $visitId,
                'EDIT_ENCOUNTER_DENIED',
                'User attempted to edit an encounter without permission.'
            );

            return [
                'success' => false,
                'errors' => [
                    'You do not have permission to edit this encounter.'
                ]
            ];

        }

        $stateValidation =
            $this->stateService->validateEditableEncounter(
                $currentVisit
            );

        if (!$stateValidation['success']) {

            return [

                'success' => false,

                'errors' => $stateValidation['errors']

            ];

        }

        if ((int)$visit['current_department_id']
            !== (int)$currentVisit['current_department_id']
        ) {

            return [

                'success' => false,

                'errors' => [

                    'Use the transfer workflow to change the encounter department.'

                ]

            ];

        }

        if ((int)($visit['attending_doctor_id'] ?? 0)
            !== (int)($currentVisit['attending_doctor_id'] ?? 0)
        ) {

            return [

                'success' => false,

                'errors' => [

                    'Use the doctor assignment workflow to change the attending doctor.'

                ]

            ];

        }

        /*
        |--------------------------------------------------------------------------
        | Update Encounter
        |--------------------------------------------------------------------------
        */

        try {

            $this->pdo->beginTransaction();

            $sql = "

                UPDATE visits

                SET

                    visit_type = :visit_type,

                    updated_at = CURRENT_TIMESTAMP

                WHERE id = :id

            ";

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([

                ':visit_type' => $visit['visit_type'],

                ':id' => $visitId

            ]);

            $this->recordWorkflowHistory(
                $visitId,
                'ENCOUNTER_UPDATED',
                'Encounter Updated',
                'Encounter details were updated.',
                (int)$visit['current_department_id'],
                $updatedBy,
                'Encounter',
                'UPDATE'
            );

            $this->pdo->commit();

            return [

                'success' => true,

                'errors' => []

            ];

        } catch (Throwable $e) {

            if ($this->pdo->inTransaction()) {

                $this->pdo->rollBack();

            }

            return [

                'success' => false,

                'errors' => [

                    'Unable to update encounter.'

                ]

            ];

        }

    }
        /*
    |--------------------------------------------------------------------------
    | Update Encounter Status
    |--------------------------------------------------------------------------
    */

    public function updateStatus(
        int $visitId,
        string $status
    ): array {

        $visit = $this->getVisitById($visitId);

        if (!$visit
            || !$this->permissionService->canChangeEncounterStatus(
                $visit
            )
        ) {

            $this->permissionService->logDenied(
                null,
                $visitId,
                'CHANGE_STATUS_DENIED',
                'User attempted to change encounter status without permission.'
            );

            return [
                'success' => false,
                'errors' => [
                    'You do not have permission to change this encounter status.'
                ]
            ];

        }

        $stateValidation =
            $this->stateService->validateStatusTransition(
                $visit['visit_status'] ?? null,
                $status
            );

        if (!$stateValidation['success']) {

            return [

                'success' => false,

                'errors' => $stateValidation['errors']

            ];

        }

        try {
            $this->pdo->beginTransaction();

            $userId = isset($_SESSION['user']['id'])
                ? (int)$_SESSION['user']['id']
                : null;

            if (in_array($status, ['Completed', 'Cancelled'], true)) {

                $queueClose = $this->queueService
                    ->closeActiveForLifecycle(
                        $visitId,
                        $status,
                        $userId
                    );

                if (!$queueClose['success']) {

                    throw new RuntimeException(
                        $queueClose['errors'][0]
                        ?? 'Unable to close the queue entry.'
                    );

                }

            }

            $stmt = $this->pdo->prepare("

            UPDATE visits

            SET

                visit_status = :status

            WHERE

                id = :id

        ");

            $success = $stmt->execute([

            ':status' => $status,

            ':id' => $visitId

        ]);

            if (!$success || $stmt->rowCount() === 0) {
                throw new RuntimeException(
                    'Unable to update encounter status.'
                );
            }

            $this->recordWorkflowHistory(
                $visitId,
                'STATUS_CHANGED',
                'Encounter Status Changed',
                'Encounter status changed to ' . $status . '.',
                null,
                $userId,
                'Encounter',
                'STATUS_CHANGED'
            );

            $this->pdo->commit();

            return [

                'success' => true,

                'errors' => []

            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return [
                'success' => false,
                'errors' => ['Unable to update encounter status.']
            ];
        }

    }

    /*
    |--------------------------------------------------------------------------
    | Change Current Department
    |--------------------------------------------------------------------------
    */

    public function transferDepartment(
        int $visitId,
        int $departmentId
    ): bool {

        $actorId = (int)($_SESSION['user']['id'] ?? 0);

        if ($actorId <= 0) {

            $config = require __DIR__ . '/../config/app.php';

            if (($config['app']['environment'] ?? 'production') === 'development') {

                $actorId = 1;

            }

        }

        if ($actorId <= 0) {

            return false;

        }

        return $this->transferVisit(
            $visitId,
            $departmentId,
            $actorId
        )['success'];

    }

    public function getAvailableDoctors(
    ?int $departmentId = null
): array {

    $departmentClause = $departmentId !== null && $departmentId > 0
        ? 'AND u.department_id = :department'
        : '';

    $stmt = $this->pdo->prepare("

        SELECT

            u.id,

            u.employee_id,

            CONCAT(

                u.first_name,
                ' ',
                u.last_name

            ) AS full_name,

            u.email,

            u.phone

        FROM users u

        INNER JOIN roles r
            ON r.id = u.role_id

        WHERE

            LOWER(r.role_name) = 'doctor'

        $departmentClause

        AND

            u.status = 'Active'

        ORDER BY

            u.first_name,
            u.last_name

    ");

    $parameters = [];
    if ($departmentId !== null && $departmentId > 0) {
        $parameters[':department'] = $departmentId;
    }

    $stmt->execute($parameters);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);

}

    /*
    |--------------------------------------------------------------------------
    | Assign Doctor
    |--------------------------------------------------------------------------
    */

    public function assignDoctor(
    int $visitId,
    int $doctorId,
    int $assignedBy
): array {

    /*
    |--------------------------------------------------------------------------
    | Load Visit
    |--------------------------------------------------------------------------
    */

    $visit = $this->getVisitById($visitId);

    if (!$visit) {

        return [

            'success' => false,

            'errors' => [

                'Encounter not found.'

            ]

        ];

    }

    if (!$this->permissionService->canAssignDoctor($visit)) {

        $this->permissionService->logDenied(
            $assignedBy,
            $visitId,
            'ASSIGN_DOCTOR_DENIED',
            'User attempted to assign a doctor without permission.'
        );

        return [
            'success' => false,
            'errors' => [
                'You do not have permission to assign a doctor to this encounter.'
            ]
        ];

    }

    $stateValidation =
        $this->stateService->validateDoctorAssignment($visit);

    if (!$stateValidation['success']) {

        return [

            'success' => false,

            'errors' => $stateValidation['errors']

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Load Doctor
    |--------------------------------------------------------------------------
    */

    $stmt = $this->pdo->prepare("

        SELECT

            u.id,

            u.department_id,

            u.status,

            CONCAT(

                u.first_name,
                ' ',
                u.last_name

            ) AS doctor_name,

            r.role_name

        FROM users u

        INNER JOIN roles r
            ON r.id = u.role_id

        WHERE u.id = :id

        LIMIT 1

    ");

    $stmt->execute([

        ':id' => $doctorId

    ]);

    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {

        return [

            'success' => false,

            'errors' => [

                'Selected doctor does not exist.'

            ]

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Validate Role
    |--------------------------------------------------------------------------
    */

    if (

        strtolower($doctor['role_name']) !== 'doctor'

    ) {

        return [

            'success' => false,

            'errors' => [

                'Selected user is not a doctor.'

            ]

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Validate Status
    |--------------------------------------------------------------------------
    */

    if (

        strtolower($doctor['status']) !== 'active'

    ) {

        return [

            'success' => false,

            'errors' => [

                'Selected doctor is inactive.'

            ]

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Already Assigned?
    |--------------------------------------------------------------------------
    */

    if (

        (int)$visit['attending_doctor_id']

        ===

        $doctorId

    ) {

        return [

            'success' => false,

            'errors' => [

                'This doctor has already been assigned.'

            ]

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Save
    |--------------------------------------------------------------------------
    */

    try {

        $this->pdo->beginTransaction();

        $stmt = $this->pdo->prepare("

            UPDATE visits

            SET

                attending_doctor_id = :doctor,

                queue_number = NULL,

                updated_at = NOW()

            WHERE id = :visit

        ");

        $stmt->execute([

            ':doctor' => $doctorId,

            ':visit'  => $visitId

        ]);

        $this->recordWorkflowHistory(
            $visitId,
            'DOCTOR_ASSIGNED',
            'Doctor Assigned',
            'Doctor assigned: ' . $doctor['doctor_name'] . '.',
            (int)$visit['current_department_id'],
            $assignedBy,
            'Encounter',
            'ASSIGN_DOCTOR'
        );

        $this->pdo->commit();

        return [

            'success' => true,

            'doctor_id' => $doctorId,

            'doctor_name' => $doctor['doctor_name'],

            'assigned_by' => $assignedBy,

            'assigned_at' => date('Y-m-d H:i:s'),

            'queue_reset' => true,

            'errors' => []

        ];

    } catch (Throwable $e) {

        if ($this->pdo->inTransaction()) {

            $this->pdo->rollBack();

        }

        return [

            'success' => false,

            'errors' => [

                'Unable to assign doctor.'

            ]

        ];

    }

}

    /*
    |--------------------------------------------------------------------------
    | Close Encounter
    |--------------------------------------------------------------------------
    */

    public function closeVisit(
        int $visitId
    ): array {

        return $this->updateStatus(

            $visitId,

            'Completed'

        );

    }

    public function completeVisitWithDischarge(
        int $visitId,
        array $data,
        array $user
    ): array {
        $visit = $this->getVisitById($visitId);

        if (!$visit
            || !$this->permissionService->canChangeEncounterStatus($visit, $user)
        ) {
            $this->permissionService->logDenied(
                (int)($user['id'] ?? 0) ?: null,
                $visitId,
                'COMPLETE_ENCOUNTER_DENIED',
                'User attempted to complete an encounter without permission.'
            );

            return [
                'success' => false,
                'errors' => ['You do not have permission to complete this encounter.']
            ];
        }

        $dischargeDiagnosis = trim((string)($data['discharge_diagnosis'] ?? ''));
        $dischargeNotes = trim((string)($data['discharge_notes'] ?? ''));
        $followUpInstructions = trim((string)($data['follow_up_instructions'] ?? ''));
        $errors = [];

        if ($dischargeDiagnosis === '') {
            $errors[] = 'Discharge diagnosis is required.';
        }

        if (strlen($dischargeDiagnosis) > 5000) {
            $errors[] = 'Discharge diagnosis is too long.';
        }

        if (strlen($dischargeNotes) > 10000) {
            $errors[] = 'Discharge notes are too long.';
        }

        if (strlen($followUpInstructions) > 5000) {
            $errors[] = 'Follow-up instructions are too long.';
        }

        $stateValidation = $this->stateService->validateStatusTransition(
            $visit['visit_status'] ?? null,
            'Completed'
        );

        if (!$stateValidation['success']) {
            $errors = array_merge($errors, $stateValidation['errors']);
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        try {
            $this->pdo->beginTransaction();

            $userId = (int)($user['id'] ?? 0);

            $queueClose = $this->queueService->closeActiveForLifecycle(
                $visitId,
                'Completed',
                $userId > 0 ? $userId : null
            );

            if (!$queueClose['success']) {
                throw new RuntimeException(
                    $queueClose['errors'][0]
                    ?? 'Unable to close the queue entry.'
                );
            }

            $stmt = $this->pdo->prepare("
                UPDATE visits
                SET visit_status = 'Completed',
                    completed_at = NOW(),
                    completed_by = :completed_by,
                    discharge_diagnosis = :discharge_diagnosis,
                    discharge_notes = :discharge_notes,
                    follow_up_instructions = :follow_up_instructions
                WHERE id = :id
                  AND visit_status NOT IN ('Completed', 'Cancelled')
            ");

            $stmt->execute([
                ':completed_by' => $userId > 0 ? $userId : null,
                ':discharge_diagnosis' => $dischargeDiagnosis,
                ':discharge_notes' => $dischargeNotes === '' ? null : $dischargeNotes,
                ':follow_up_instructions' => $followUpInstructions === '' ? null : $followUpInstructions,
                ':id' => $visitId
            ]);

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('Unable to complete encounter.');
            }

            $this->recordWorkflowHistory(
                $visitId,
                'STATUS_CHANGED',
                'Encounter Completed',
                'Encounter completed with discharge details.',
                null,
                $userId > 0 ? $userId : null,
                'Encounter',
                'ENCOUNTER_COMPLETED'
            );

            $this->pdo->commit();

            return [
                'success' => true,
                'errors' => []
            ];
        } catch (Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return [
                'success' => false,
                'errors' => ['Unable to complete encounter.']
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Encounter
    |--------------------------------------------------------------------------
    */

    public function cancelVisit(
        int $visitId
    ): array {

        return $this->updateStatus(

            $visitId,

            'Cancelled'

        );

    }

    public function reopenVisit(
        int $visitId,
        string $reason,
        array $user
    ): array {
        $visit = $this->getVisitById($visitId);

        if (!$visit
            || !$this->permissionService->canReopenEncounter($visit, $user)
        ) {
            $this->permissionService->logDenied(
                (int)($user['id'] ?? 0) ?: null,
                $visitId,
                'REOPEN_ENCOUNTER_DENIED',
                'User attempted to reopen an encounter without permission.'
            );

            return [
                'success' => false,
                'errors' => ['You do not have permission to reopen this encounter.']
            ];
        }

        $reason = trim($reason);

        if ($reason === '') {
            return [
                'success' => false,
                'errors' => ['Reopen reason is required.']
            ];
        }

        if (strlen($reason) > 2000) {
            return [
                'success' => false,
                'errors' => ['Reopen reason is too long.']
            ];
        }

        $departmentId = (int)($visit['current_department_id'] ?? 0);
        $restoredStatus = trim((string)($visit['department_name'] ?? ''));

        if ($departmentId <= 0 || $restoredStatus === '') {
            return [
                'success' => false,
                'errors' => ['Encounter does not have a valid department to reopen into.']
            ];
        }

        try {
            $this->pdo->beginTransaction();

            $userId = (int)($user['id'] ?? 0);

            $stmt = $this->pdo->prepare("
                UPDATE visits
                SET visit_status = :status,
                    completed_at = NULL,
                    completed_by = NULL,
                    current_department_received_status = 'Received',
                    current_department_received_at = NOW(),
                    current_department_received_by = :received_by
                WHERE id = :id
                  AND visit_status = 'Completed'
            ");

            $stmt->execute([
                ':status' => $restoredStatus,
                ':received_by' => $userId > 0 ? $userId : null,
                ':id' => $visitId
            ]);

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('Unable to reopen encounter.');
            }

            $queueResult = $this->queueService->enqueueEncounter(
                $visitId,
                $departmentId,
                $userId > 0 ? $userId : null,
                null,
                'Encounter reopened: ' . $reason,
                false
            );

            if (!$queueResult['success']) {
                throw new RuntimeException(
                    $queueResult['errors'][0]
                    ?? 'Unable to queue reopened encounter.'
                );
            }

            $this->recordWorkflowHistory(
                $visitId,
                'ENCOUNTER_REOPENED',
                'Encounter Reopened',
                'Encounter reopened. Reason: ' . $reason,
                $departmentId,
                $userId > 0 ? $userId : null,
                'Encounter',
                'ENCOUNTER_REOPENED'
            );

            $this->pdo->commit();

            return [
                'success' => true,
                'errors' => []
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return [
                'success' => false,
                'errors' => [$e->getMessage() ?: 'Unable to reopen encounter.']
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Departments
    |--------------------------------------------------------------------------
    */

    public function getDepartments(): array
    {
        $stmt = $this->pdo->query("

            SELECT

                id,
                department_name

            FROM departments

            ORDER BY department_name

        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Doctors
    |--------------------------------------------------------------------------
    */

    public function getDoctors(): array
    {
        $stmt = $this->pdo->query("

            SELECT

                u.id,

                CONCAT(
                    u.first_name,
                    ' ',
                    u.last_name
                ) AS doctor_name

            FROM users u

            INNER JOIN roles r

                ON r.id = u.role_id

            WHERE

                r.role_name = 'Doctor'

            ORDER BY

                doctor_name

        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Check Active Encounter
    |--------------------------------------------------------------------------
    */

    public function hasActiveVisit(
        int $patientId
    ): bool {

        return $this->getActiveVisit(

            $patientId

        ) !== null;

    }

    /*
    |--------------------------------------------------------------------------
    | Total Encounters
    |--------------------------------------------------------------------------
    */

    public function countVisits(): int
    {
        return (int)$this->pdo
            ->query("SELECT COUNT(*) FROM visits")
            ->fetchColumn();
    }

    /*
    |--------------------------------------------------------------------------
    | Today's Encounters
    |--------------------------------------------------------------------------
    */

    public function countTodayVisits(): int
    {
        $stmt = $this->pdo->query("

            SELECT COUNT(*)

            FROM visits

            WHERE DATE(visit_date)=CURDATE()

        ");

        return (int)$stmt->fetchColumn();
    }

    /*
    |--------------------------------------------------------------------------
    | Waiting Encounters
    |--------------------------------------------------------------------------
    */

    public function countWaitingVisits(): int
    {
        $stmt = $this->pdo->query("

            SELECT COUNT(*)

            FROM visits

            WHERE visit_status='Waiting'

        ");

        return (int)$stmt->fetchColumn();
    }

    /*
    |--------------------------------------------------------------------------
    | Transfer Encounter
    |--------------------------------------------------------------------------
    */

    public function transferVisit(
    int $visitId,
    int $departmentId,
    int $transferredBy,
    string $transferType = 'Forward',
    ?string $remarks = null
): array {

    /*
    |--------------------------------------------------------------------------
    | Allowed Transfer Types
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Load Visit
    |--------------------------------------------------------------------------
    */

    $visit = $this->getVisitById($visitId);

    if (!$visit) {

        return [

            'success' => false,

            'department_name' => null,

            'visit_status' => null,

            'errors' => [

                'Encounter not found.'

            ]

        ];

    }

    if (!$this->permissionService->canTransferEncounter($visit)) {

        $this->permissionService->logDenied(
            $transferredBy,
            $visitId,
            'TRANSFER_ENCOUNTER_DENIED',
            'User attempted to transfer an encounter outside their department.'
        );

        return [
            'success' => false,
            'department_name' => null,
            'visit_status' => null,
            'errors' => [
                'You do not have permission to transfer this encounter.'
            ]
        ];

    }

    $stateValidation = $this->stateService->validateTransfer(
        $visit,
        $departmentId,
        $transferType
    );

    if (!$stateValidation['success']) {

        return [

            'success' => false,

            'department_name' => null,

            'visit_status' => null,

            'errors' => $stateValidation['errors']

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Load Destination Department
    |--------------------------------------------------------------------------
    */

    $stmt = $this->pdo->prepare("

        SELECT

            id,
            department_name

        FROM departments

        WHERE id = :id

        LIMIT 1

    ");

    $stmt->execute([

        ':id' => $departmentId

    ]);

    $department = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$department) {

        return [

            'success' => false,

            'department_name' => null,

            'visit_status' => null,

            'errors' => [

                'Invalid destination department.'

            ]

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Already In Department?
    |--------------------------------------------------------------------------
    */

    if (

        (int)$visit['current_department_id'] === $departmentId

    ) {

        return [

            'success' => false,

            'department_name' => $department['department_name'],

            'visit_status' => $visit['visit_status'],

            'errors' => [

                'Patient is already in this department.'

            ]

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Determine New Status
    |--------------------------------------------------------------------------
    */

    $newStatus = $department['department_name'];

    $targetStateValidation = $this->stateService
        ->validateStatusTransition(
            $visit['visit_status'] ?? null,
            $newStatus
        );

    if (!$targetStateValidation['success']) {

        return [

            'success' => false,

            'department_name' => $department['department_name'],

            'visit_status' => $visit['visit_status'],

            'errors' => [

                'The destination department is not configured for encounter workflow.'

            ]

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Doctor Assignment
    |--------------------------------------------------------------------------
    */

    $doctorId =

        $newStatus === 'Doctor'

            ? $visit['attending_doctor_id']

            : null;

    /*
    |--------------------------------------------------------------------------
    | Normalize Remarks
    |--------------------------------------------------------------------------
    */

    $remarks = trim((string)$remarks);

    if ($remarks === '') {

        $remarks = null;

    }

    /*
    |--------------------------------------------------------------------------
    | Save Transfer
    |--------------------------------------------------------------------------
    */

    try {

        $this->pdo->beginTransaction();

        $queueClose = $this->queueService->closeActiveForTransfer(
            $visitId,
            $transferredBy
        );

        if (!$queueClose['success']) {

            throw new RuntimeException(
                $queueClose['errors'][0]
                ?? 'Unable to close the current queue entry.'
            );

        }

        /*
        |--------------------------------------------------------------------------
        | Transfer History
        |--------------------------------------------------------------------------
        */

        $stmt = $this->pdo->prepare("

            INSERT INTO visit_transfers (

                visit_id,
                from_department_id,
                to_department_id,
                from_status,
                to_status,
                previous_status,
                new_status,
                transfer_type,
                remarks,
                transferred_by,
                transferred_at

            )

            VALUES (

                :visit_id,
                :from_department,
                :to_department,
                :from_status,
                :to_status,
                :previous_status,
                :new_status,
                :transfer_type,
                :remarks,
                :transferred_by,
                NOW()

            )

        ");

        $stmt->execute([

            ':visit_id'        => $visitId,

            ':from_department' => $visit['current_department_id'],

            ':to_department'   => $departmentId,

            ':from_status'     => $visit['visit_status'],

            ':to_status'       => $newStatus,

            ':previous_status' => $visit['visit_status'],

            ':new_status'      => $newStatus,

            ':transfer_type'   => $transferType,

            ':remarks'         => $remarks,

            ':transferred_by'  => $transferredBy

        ]);

        /*
        |--------------------------------------------------------------------------
        | Update Current Visit
        |--------------------------------------------------------------------------
        */

        $stmt = $this->pdo->prepare("

            UPDATE visits

            SET

                current_department_id = :department,

                visit_status = :status,

                attending_doctor_id = :doctor,

                current_department_received_status = 'Pending',

                current_department_received_by = NULL,

                current_department_received_at = NULL,

                updated_at = NOW()

            WHERE id = :id

        ");

        $stmt->execute([

            ':department' => $departmentId,

            ':status'     => $newStatus,

            ':doctor'     => $doctorId,

            ':id'         => $visitId

        ]);

        $this->recordWorkflowHistory(
            $visitId,
            'TRANSFERRED',
            'Encounter Transferred',
            sprintf(
                'Encounter transferred from %s to %s.',
                $visit['current_department_id'],
                $department['department_name']
            ),
            $departmentId,
            $transferredBy,
            'Visits',
            'TRANSFER'
        );

        $queueResult = $this->queueService->enqueueEncounter(
            $visitId,
            $departmentId,
            $transferredBy,
            null,
            $remarks,
            false
        );

        if (!$queueResult['success']) {

            throw new RuntimeException(
                $queueResult['errors'][0]
                ?? 'Unable to queue transferred encounter.'
            );

        }

        $this->pdo->commit();

        return [

            'success' => true,

            'department_name' => $department['department_name'],

            'visit_status' => $newStatus,

            'errors' => []

        ];

    } catch (Throwable $e) {

        if ($this->pdo->inTransaction()) {

            $this->pdo->rollBack();

        }

        return [

            'success' => false,

            'department_name' => null,

            'visit_status' => null,

            'errors' => [

                'Unable to transfer encounter.'

            ]

        ];

    }

}

public function hasPendingTransfer(
    int $visitId
): bool {

    /*
    |--------------------------------------------------------------------------
    | Check for Pending Transfer
    |--------------------------------------------------------------------------
    |
    | Returns TRUE when the encounter has been transferred
    | into another department but has not yet been received.
    |
    */

    $stmt = $this->pdo->prepare("

        SELECT

            COUNT(*) AS total

        FROM visit_transfers

        WHERE

            visit_id = :visit_id

            AND received_at IS NULL

    ");

    $stmt->execute([

        ':visit_id' => $visitId

    ]);

    $count = (int)$stmt->fetchColumn();

    return $count > 0;

}

/*
|--------------------------------------------------------------------------
| Get Transfer History
|--------------------------------------------------------------------------
|
| Returns all transfers for an encounter.
|
*/

/*
|--------------------------------------------------------------------------
| Get Transfer History
|--------------------------------------------------------------------------
|
| Returns the complete transfer history for an encounter.
|
*/

public function getTransferHistory(
    int $visitId
): array {

    $stmt = $this->pdo->prepare("

        SELECT

            vt.id,

            vt.visit_id,

            vt.from_department_id,

            vt.to_department_id,

            vt.transfer_type,

            vt.remarks,

            vt.transferred_at,

            vt.received_at,

            vt.transferred_by,

            vt.received_by,

            fd.department_name
                AS from_department_name,

            td.department_name
                AS to_department_name,

            CONCAT(

                tu.first_name,

                ' ',

                tu.last_name

            ) AS transferred_by_name,

            CONCAT(

                ru.first_name,

                ' ',

                ru.last_name

            ) AS received_by_name

        FROM visit_transfers vt

        LEFT JOIN departments fd

            ON fd.id = vt.from_department_id

        LEFT JOIN departments td

            ON td.id = vt.to_department_id

        LEFT JOIN users tu

            ON tu.id = vt.transferred_by

        LEFT JOIN users ru

            ON ru.id = vt.received_by

        WHERE vt.visit_id = :visit_id

        ORDER BY

            vt.transferred_at ASC,

            vt.id ASC

    ");

    $stmt->execute([

        ':visit_id' => $visitId

    ]);

    return $stmt->fetchAll(

        PDO::FETCH_ASSOC

    );

}

/*
|--------------------------------------------------------------------------
| Visit Timeline
|--------------------------------------------------------------------------
|
| Returns the complete chronological timeline for a visit.
|
*/

/*
|--------------------------------------------------------------------------
| Visit Timeline
|--------------------------------------------------------------------------
|
| Returns the complete chronological timeline for an encounter.
|
*/

public function getVisitTimeline(
    int $visitId
): array {

    /*
    |--------------------------------------------------------------------------
    | Load Encounter
    |--------------------------------------------------------------------------
    */

    $visit = $this->getVisitById(

        $visitId

    );

    if (!$visit) {

        return [];

    }

    $eventTimeline = $this->eventService->getTimelineEvents(

        $visitId

    );

    if (!empty($eventTimeline)) {

        return $eventTimeline;

    }

    /*
    |--------------------------------------------------------------------------
    | Load Transfer History
    |--------------------------------------------------------------------------
    */

    $transfers = $this->getTransferHistory(

        $visitId

    );

    /*
    |--------------------------------------------------------------------------
    | Build Timeline
    |--------------------------------------------------------------------------
    */

    $timeline = [];

    /*
    |--------------------------------------------------------------------------
    | Encounter Creation
    |--------------------------------------------------------------------------
    */

    $this->appendCreationEvent(

        $timeline,

        $visit

    );

    /*
    |--------------------------------------------------------------------------
    | Department Transfers
    |--------------------------------------------------------------------------
    */

    $this->appendTransferEvents(

        $timeline,

        $transfers

    );

    /*
    |--------------------------------------------------------------------------
    | Future Clinical Modules
    |--------------------------------------------------------------------------
    */

    if (method_exists($this, 'appendNursingEvents')) {

        $this->appendNursingEvents(

            $timeline,

            $visitId

        );

    }

    if (method_exists($this, 'appendConsultationEvents')) {

        $this->appendConsultationEvents(

            $timeline,

            $visitId

        );

    }

    if (method_exists($this, 'appendLaboratoryEvents')) {

        $this->appendLaboratoryEvents(

            $timeline,

            $visitId

        );

    }

    if (method_exists($this, 'appendRadiologyEvents')) {

        $this->appendRadiologyEvents(

            $timeline,

            $visitId

        );

    }

    if (method_exists($this, 'appendPharmacyEvents')) {

        $this->appendPharmacyEvents(

            $timeline,

            $visitId

        );

    }

    if (method_exists($this, 'appendBillingEvents')) {

        $this->appendBillingEvents(

            $timeline,

            $visitId

        );

    }

    if (method_exists($this, 'appendPhysiotherapyEvents')) {

        $this->appendPhysiotherapyEvents(

            $timeline,

            $visitId

        );

    }

    if (method_exists($this, 'appendTheatreEvents')) {

        $this->appendTheatreEvents(

            $timeline,

            $visitId

        );

    }

    if (method_exists($this, 'appendDocumentEvents')) {

        $this->appendDocumentEvents(

            $timeline,

            $visitId

        );

    }

    if (method_exists($this, 'appendNoteEvents')) {

        $this->appendNoteEvents(

            $timeline,

            $visitId

        );

    }

    /*
    |--------------------------------------------------------------------------
    | Sort Timeline
    |--------------------------------------------------------------------------
    */

    usort(

        $timeline,

        function (

            array $a,

            array $b

        ): int {

            return strtotime(

                $a['created_at']

            ) <=> strtotime(

                $b['created_at']

            );

        }

    );

    return $timeline;

}

/*
|--------------------------------------------------------------------------
| Append Creation Event
|--------------------------------------------------------------------------
*/

private function appendCreationEvent(
    array &$timeline,
    array $visit
): void {

    $timeline[] = [

        'type' => 'creation',

        'title' => 'Encounter Created',

        'description' => sprintf(
            '%s registered the patient at %s.',
            $visit['registered_by_name'] ?? 'Unknown User',
            $visit['department_name'] ?? 'Unknown Department'
        ),

        'department' => $visit['department_name'] ?? null,

        'performed_by' => $visit['registered_by_name'] ?? null,

        'transfer_type' => null,

        'remarks' => null,

        'created_at' => $visit['created_at']

    ];

}

/*
|--------------------------------------------------------------------------
| Append Transfer Events
|--------------------------------------------------------------------------
*/

private function appendTransferEvents(
    array &$timeline,
    array $transfers
): void {

    foreach ($transfers as $transfer) {

        $description = sprintf(
            'Transferred from %s to %s.',
            $transfer['from_department_name'] ?? 'Unknown Department',
            $transfer['to_department_name'] ?? 'Unknown Department'
        );

        if (!empty($transfer['remarks'])) {

            $description .= ' Remarks: ' . $transfer['remarks'];

        }

        $timeline[] = [

            'type' => 'transfer',

            'title' => $transfer['transfer_type'] . ' Transfer',

            'description' => $description,

            'department' => $transfer['to_department_name'] ?? null,

            'performed_by' => $transfer['transferred_by_name'] ?? null,

            'transfer_type' => $transfer['transfer_type'],

            'remarks' => $transfer['remarks'],

            'created_at' => $transfer['transferred_at']

        ];

    }

}

private function appendDepartmentRequestWorklistRows(
    array &$rowsByVisit,
    int $departmentId
): void {
    $specs = [
        [
            'table' => 'laboratory_requests',
            'title' => 'Laboratory Request',
            'label' => 'tests_requested',
            'statuses' => ['Requested', 'In Progress'],
        ],
        [
            'table' => 'radiology_requests',
            'title' => 'Radiology Request',
            'label' => 'study_requested',
            'statuses' => ['Requested', 'In Progress'],
        ],
        [
            'table' => 'ecg_requests',
            'title' => 'ECG Request',
            'label' => 'study_requested',
            'statuses' => ['Requested', 'In Progress'],
        ],
        [
            'table' => 'pop_requests',
            'title' => 'POP Request',
            'label' => 'procedure_requested',
            'statuses' => ['Requested', 'In Progress'],
        ],
        [
            'table' => 'physiotherapy_records',
            'title' => 'Physiotherapy Record',
            'label' => 'presenting_problem',
            'statuses' => ['Active'],
        ],
        [
            'table' => 'prescriptions',
            'title' => 'Prescription',
            'label' => 'medication_name',
            'statuses' => ['Prescribed'],
        ],
        [
            'table' => 'theatre_records',
            'title' => 'Theatre Record',
            'label' => 'procedure_name',
            'statuses' => ['Draft'],
        ],
    ];

    foreach ($specs as $spec) {
        $this->appendDepartmentRequestWorklistRowsForSpec(
            $rowsByVisit,
            $departmentId,
            $spec
        );
    }
}

private function appendDepartmentRequestWorklistRowsForSpec(
    array &$rowsByVisit,
    int $departmentId,
    array $spec
): void {
    $table = (string)($spec['table'] ?? '');
    $labelColumn = (string)($spec['label'] ?? '');
    $title = (string)($spec['title'] ?? 'Department Request');
    $statuses = (array)($spec['statuses'] ?? []);

    if (
        $table === ''
        || $labelColumn === ''
        || $statuses === []
        || !$this->tableExists($table)
    ) {
        return;
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)
        || !preg_match('/^[a-zA-Z0-9_]+$/', $labelColumn)
    ) {
        return;
    }

    $statusPlaceholders = [];
    $params = [':department_id' => $departmentId];
    foreach (array_values($statuses) as $index => $status) {
        $placeholder = ':status_' . $index;
        $statusPlaceholders[] = $placeholder;
        $params[$placeholder] = (string)$status;
    }

    try {
        $stmt = $this->pdo->prepare("
            SELECT
                NULL AS id,
                v.id AS visit_id,
                r.department_id AS department_id,
                NULL AS assigned_user_id,
                NULL AS position,
                r.status AS queue_status,
                CONCAT(:title_remarks, ': ', LEFT(COALESCE(r.$labelColumn, ''), 180)) AS remarks,
                COALESCE(r.updated_at, r.created_at) AS queued_at,
                v.visit_number,
                v.visit_status,
                v.current_department_received_status,
                v.patient_id,
                p.hospital_number,
                p.first_name,
                p.last_name,
                d.department_name,
                NULL AS assigned_user_name,
                :title_status AS worklist_status,
                0 AS can_receive
            FROM $table r
            INNER JOIN visits v ON v.id = r.visit_id
            INNER JOIN patients p ON p.id = r.patient_id
            LEFT JOIN departments d ON d.id = r.department_id
            WHERE r.department_id = :department_id
              AND r.status IN (" . implode(',', $statusPlaceholders) . ")
              AND v.visit_status NOT IN ('Completed', 'Cancelled')
            ORDER BY COALESCE(r.updated_at, r.created_at) DESC, r.id DESC
            LIMIT 100
        ");
        $params[':title_remarks'] = $title;
        $params[':title_status'] = $title;
        $stmt->execute($params);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rowsByVisit[(int)$row['visit_id']] ??= $row;
        }
    } catch (Throwable) {
        return;
    }
}

private function appendLaboratoryEvents(
    array &$timeline,
    int $visitId
): void {
    if (!$this->tableExists('laboratory_requests')) {
        return;
    }

    try {
        $stmt = $this->pdo->prepare('
            SELECT lr.id,
                   lr.tests_requested,
                   lr.request_source,
                   lr.priority,
                   lr.status,
                   lr.created_at,
                   lr.updated_at,
                   lr.completed_at,
                   CONCAT(u.first_name, " ", u.last_name) AS requested_by_name
            FROM laboratory_requests lr
            LEFT JOIN users u ON u.id = lr.requested_by
            WHERE lr.visit_id = :visit_id
            ORDER BY lr.created_at ASC, lr.id ASC
        ');
        $stmt->execute([':visit_id' => $visitId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable) {
        return;
    }

    foreach ($rows as $row) {
        $timeline[] = [
            'type' => 'laboratory',
            'title' => 'Laboratory Request Created',
            'description' => sprintf(
                '%s requested laboratory tests: %s (%s, %s).',
                $row['requested_by_name'] ?? 'Unknown User',
                $row['tests_requested'] ?? 'Unknown tests',
                $row['request_source'] ?? 'Clinical',
                $row['priority'] ?? 'Routine'
            ),
            'department' => 'Laboratory',
            'performed_by' => $row['requested_by_name'] ?? null,
            'transfer_type' => null,
            'remarks' => null,
            'created_at' => $row['created_at']
        ];

        if ((string)($row['status'] ?? '') === 'Completed' && !empty($row['completed_at'])) {
            $timeline[] = [
                'type' => 'laboratory',
                'title' => 'Laboratory Request Completed',
                'description' => 'Laboratory request completed.',
                'department' => 'Laboratory',
                'performed_by' => $row['requested_by_name'] ?? null,
                'transfer_type' => null,
                'remarks' => null,
                'created_at' => $row['completed_at']
            ];
        }
    }

}

private function appendRadiologyEvents(
    array &$timeline,
    int $visitId
): void {
    if (!$this->tableExists('radiology_requests')) {
        return;
    }

    try {
        $stmt = $this->pdo->prepare('
            SELECT lr.id,
                   lr.study_requested,
                   lr.request_source,
                   lr.priority,
                   lr.status,
                   lr.created_at,
                   lr.updated_at,
                   lr.completed_at,
                   CONCAT(u.first_name, " ", u.last_name) AS requested_by_name
            FROM radiology_requests lr
            LEFT JOIN users u ON u.id = lr.requested_by
            WHERE lr.visit_id = :visit_id
            ORDER BY lr.created_at ASC, lr.id ASC
        ');
        $stmt->execute([':visit_id' => $visitId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable) {
        return;
    }

    foreach ($rows as $row) {
        $timeline[] = [
            'type' => 'radiology',
            'title' => 'Radiology Request Created',
            'description' => sprintf(
                '%s requested radiology study: %s (%s, %s).',
                $row['requested_by_name'] ?? 'Unknown User',
                $row['study_requested'] ?? 'Unknown study',
                $row['request_source'] ?? 'Clinical',
                $row['priority'] ?? 'Routine'
            ),
            'department' => 'Radiology',
            'performed_by' => $row['requested_by_name'] ?? null,
            'transfer_type' => null,
            'remarks' => null,
            'created_at' => $row['created_at']
        ];

        if ((string)($row['status'] ?? '') === 'Completed' && !empty($row['completed_at'])) {
            $timeline[] = [
                'type' => 'radiology',
                'title' => 'Radiology Request Completed',
                'description' => 'Radiology request completed.',
                'department' => 'Radiology',
                'performed_by' => $row['requested_by_name'] ?? null,
                'transfer_type' => null,
                'remarks' => null,
                'created_at' => $row['completed_at']
            ];
        }
    }

}

private function appendPhysiotherapyEvents(
    array &$timeline,
    int $visitId
): void {
    if (!$this->tableExists('physiotherapy_records')) {
        return;
    }

    try {
        $stmt = $this->pdo->prepare('
            SELECT pr.id,
                   pr.presenting_problem,
                   pr.referral_reason,
                   pr.record_source,
                   pr.status,
                   pr.created_at,
                   pr.completed_at,
                   CONCAT(u.first_name, " ", u.last_name) AS created_by_name
            FROM physiotherapy_records pr
            LEFT JOIN users u ON u.id = pr.created_by
            WHERE pr.visit_id = :visit_id
            ORDER BY pr.created_at ASC, pr.id ASC
        ');
        $stmt->execute([':visit_id' => $visitId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable) {
        return;
    }

    foreach ($rows as $row) {
        $timeline[] = [
            'type' => 'physiotherapy',
            'title' => 'Physiotherapy Started',
            'description' => sprintf(
                '%s started physiotherapy: %s (%s).',
                $row['created_by_name'] ?? 'Unknown User',
                $row['presenting_problem'] ?? 'Unknown problem',
                $row['record_source'] ?? 'Clinical'
            ),
            'department' => 'Physiotherapy',
            'performed_by' => $row['created_by_name'] ?? null,
            'transfer_type' => null,
            'remarks' => null,
            'created_at' => $row['created_at']
        ];

        if ((string)($row['status'] ?? '') === 'Completed' && !empty($row['completed_at'])) {
            $timeline[] = [
                'type' => 'physiotherapy',
                'title' => 'Physiotherapy Completed',
                'description' => 'Physiotherapy record completed.',
                'department' => 'Physiotherapy',
                'performed_by' => $row['created_by_name'] ?? null,
                'transfer_type' => null,
                'remarks' => null,
                'created_at' => $row['completed_at']
            ];
        }
    }

}

private function appendTheatreEvents(
    array &$timeline,
    int $visitId
): void {
    if (!$this->tableExists('theatre_records')) {
        return;
    }

    try {
        $stmt = $this->pdo->prepare('
            SELECT tr.id,
                   tr.procedure_name,
                   tr.procedure_details,
                   tr.status,
                   tr.created_at,
                   tr.completed_at,
                   CONCAT(u.first_name, " ", u.last_name) AS created_by_name
            FROM theatre_records tr
            LEFT JOIN users u ON u.id = tr.created_by
            WHERE tr.visit_id = :visit_id
            ORDER BY tr.created_at ASC, tr.id ASC
        ');
        $stmt->execute([':visit_id' => $visitId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable) {
        return;
    }

    foreach ($rows as $row) {
        $timeline[] = [
            'type' => 'theatre',
            'title' => 'Theatre Started',
            'description' => sprintf(
                '%s started theatre for %s.',
                $row['created_by_name'] ?? 'Unknown User',
                $row['procedure_name'] ?? 'Unknown procedure'
            ),
            'department' => 'Theatre',
            'performed_by' => $row['created_by_name'] ?? null,
            'transfer_type' => null,
            'remarks' => null,
            'created_at' => $row['created_at']
        ];

        if ((string)($row['status'] ?? '') === 'Completed' && !empty($row['completed_at'])) {
            $timeline[] = [
                'type' => 'theatre',
                'title' => 'Theatre Completed',
                'description' => 'Theatre record completed.',
                'department' => 'Theatre',
                'performed_by' => $row['created_by_name'] ?? null,
                'transfer_type' => null,
                'remarks' => null,
                'created_at' => $row['completed_at']
            ];
        }
    }

}

private function tableExists(string $table): bool
{
    try {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = :table
        ');
        $stmt->execute([':table' => $table]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable) {
        return false;
    }
}

public function receiveVisit(
    int $visitId,
    int $receivedBy,
    ?string $remarks = null
): array {

    /*
    |--------------------------------------------------------------------------
    | Normalize Remarks
    |--------------------------------------------------------------------------
    */

    $remarks = trim((string)$remarks);

    if ($remarks === '') {

        $remarks = null;

    }

    /*
    |--------------------------------------------------------------------------
    | Load Encounter
    |--------------------------------------------------------------------------
    */

    $visit = $this->getVisitById($visitId);

    if (!$visit) {

        return [

            'success' => false,

            'errors' => [

                'Encounter not found.'

            ]

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Find Pending Transfer
    |--------------------------------------------------------------------------
    */

    $stmt = $this->pdo->prepare("

        SELECT

            vt.*,

            fd.department_name AS from_department_name,

            td.department_name AS to_department_name

        FROM visit_transfers vt

        LEFT JOIN departments fd

            ON fd.id = vt.from_department_id

        LEFT JOIN departments td

            ON td.id = vt.to_department_id

        WHERE

            vt.visit_id = :visit_id

            AND vt.received_at IS NULL

        ORDER BY

            vt.transferred_at DESC,

            vt.id DESC

        LIMIT 1

    ");

    $stmt->execute([

        ':visit_id' => $visitId

    ]);

    $transfer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$this->permissionService->canReceiveEncounter(
        $visit,
        $transfer ?: null
    )) {

        $this->permissionService->logDenied(
            $receivedBy,
            $visitId,
            'RECEIVE_ENCOUNTER_DENIED',
            'User attempted to receive an encounter outside their department.'
        );

        return [
            'success' => false,
            'errors' => [
                'You do not have permission to receive this encounter.'
            ]
        ];

    }

    $stateValidation = $this->stateService->validateReceive(
        $visit,
        $transfer ?: null
    );

    if (!$stateValidation['success']) {

        return [

            'success' => false,

            'errors' => $stateValidation['errors']

        ];

    }

    try {

        $this->pdo->beginTransaction();

        /*
        |--------------------------------------------------------------------------
        | Mark Transfer Received
        |--------------------------------------------------------------------------
        */

        $stmt = $this->pdo->prepare("

            UPDATE visit_transfers

            SET

                received_by = :received_by,

                received_at = NOW()

            WHERE id = :id

        ");

        $stmt->execute([

            ':received_by' => $receivedBy,

            ':id'          => $transfer['id']

        ]);

        /*
        |--------------------------------------------------------------------------
        | Update Encounter
        |--------------------------------------------------------------------------
        */

        $stmt = $this->pdo->prepare("

            UPDATE visits

            SET

                current_department_received_by = :received_by,

                current_department_received_at = NOW(),

                current_department_received_status = 'Received',

                updated_at = NOW()

            WHERE id = :visit_id

        ");

        $stmt->execute([

            ':received_by' => $receivedBy,

            ':visit_id'    => $visitId

        ]);

        /*
        |--------------------------------------------------------------------------
        | Build Event Description
        |--------------------------------------------------------------------------
        */

        $description =

            'Patient received in '

            . $transfer['to_department_name']

            . ' department.';

        if ($remarks !== null) {

            $description .=

                ' Remarks: '

                . $remarks;

        }

        /*
        |--------------------------------------------------------------------------
        | Record Encounter Event
        |--------------------------------------------------------------------------
        */

        $this->recordWorkflowHistory(
            $visitId,
            'PATIENT_RECEIVED',
            'Patient Received',
            $description,
            (int)$transfer['to_department_id'],
            $receivedBy,
            'Visits',
            'RECEIVE'
        );

        $this->pdo->commit();

        return [

            'success' => true,

            'visit_id' => $visitId,

            'transfer_id' => (int)$transfer['id'],

            'department_id' => (int)$transfer['to_department_id'],

            'department_name' => $transfer['to_department_name'],

            'received_by' => $receivedBy,

            'received_at' => date('Y-m-d H:i:s'),

            'remarks' => $remarks,

            'errors' => []

        ];

    } catch (Throwable $e) {

        if ($this->pdo->inTransaction()) {

            $this->pdo->rollBack();

        }

        return [

            'success' => false,

            'errors' => [

                'Unable to receive encounter.'

            ]

        ];

    }

}

    /*
    |--------------------------------------------------------------------------
    | Record Workflow History
    |--------------------------------------------------------------------------
    */

    private function recordWorkflowHistory(
        int $visitId,
        string $eventType,
        string $eventTitle,
        string $eventDescription,
        ?int $departmentId,
        ?int $performedBy,
        string $auditModule,
        string $auditAction
    ): void {
        $event = $this->eventService->record(
            $visitId,
            $eventType,
            $eventTitle,
            $eventDescription,
            $departmentId,
            $performedBy
        );

        if (!$event['success']) {
            throw new RuntimeException(
                'Unable to record encounter event.'
            );
        }

        if (!$this->auditService->log(
            $performedBy,
            $visitId,
            $auditModule,
            $auditAction,
            $eventDescription
        )) {
            throw new RuntimeException(
                'Unable to record audit log.'
            );
        }
    }
}
