<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/EncounterEventService.php';
require_once __DIR__ . '/PermissionService.php';

class DepartmentNotificationService
{
    public function __construct(
        private PDO $pdo,
        private ?AuditService $auditService = null,
        private ?EncounterEventService $eventService = null,
        private ?PermissionService $permissionService = null
    ) {
        $this->auditService ??= new AuditService($pdo);
        $this->eventService ??= new EncounterEventService($pdo);
        $this->permissionService ??= new PermissionService($pdo);
    }

    public function send(array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();
            $visit = $this->lockVisit((int)($data['visit_id'] ?? 0));
            $toDepartmentId = (int)($data['to_department_id'] ?? 0);
            $reason = trim((string)($data['reason'] ?? ''));
            $errors = $this->validateSend($visit, $toDepartmentId, $reason, $user);

            if ($errors !== []) {
                $this->pdo->rollBack();
                return $this->failure($errors);
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO department_notifications (
                    visit_id, patient_id, from_department_id, to_department_id,
                    sent_by, reason, status
                ) VALUES (
                    :visit_id, :patient_id, :from_department_id, :to_department_id,
                    :sent_by, :reason, 'Unread'
                )
            ");
            $stmt->execute([
                ':visit_id' => (int)$visit['id'],
                ':patient_id' => (int)$visit['patient_id'],
                ':from_department_id' => (int)($visit['current_department_id'] ?? 0) ?: null,
                ':to_department_id' => $toDepartmentId,
                ':sent_by' => (int)$user['id'],
                ':reason' => $reason
            ]);
            $notificationId = (int)$this->pdo->lastInsertId();

            if (!$this->audit('DEPARTMENT_NOTIFICATION_SENT', $visit, $user, 'Sent department notification #' . $notificationId . '.')) {
                throw new RuntimeException('Unable to audit notification.');
            }
            $event = $this->eventService->record(
                (int)$visit['id'],
                'DEPARTMENT_NOTIFICATION_SENT',
                'Department Notification Sent',
                'Attention requested from another department.',
                (int)($visit['current_department_id'] ?? 0) ?: null,
                (int)$user['id']
            );
            if (!($event['success'] ?? false)) {
                throw new RuntimeException('Unable to record notification event.');
            }

            $this->pdo->commit();
            return ['success' => true, 'notification_id' => $notificationId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to send department notification.']);
        }
    }

    public function getById(int $notificationId): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE n.id = :id LIMIT 1');
        $stmt->execute([':id' => $notificationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listForDepartment(int $departmentId, string $status = ''): array
    {
        $params = [':department_id' => $departmentId];
        $where = ' WHERE n.to_department_id = :department_id';
        if (in_array($status, ['Unread', 'Read', 'Resolved'], true)) {
            $where .= ' AND n.status = :status';
            $params[':status'] = $status;
        }
        $stmt = $this->pdo->prepare($this->baseSelect() . $where . ' ORDER BY n.created_at DESC, n.id DESC');
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listForVisit(int $visitId): array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE n.visit_id = :visit_id ORDER BY n.created_at DESC, n.id DESC');
        $stmt->execute([':visit_id' => $visitId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markRead(int $notificationId, array $user): array
    {
        return $this->transition($notificationId, $user, 'Read');
    }

    public function resolve(int $notificationId, array $user): array
    {
        return $this->transition($notificationId, $user, 'Resolved');
    }

    public function getUnreadCount(int $departmentId): int
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM department_notifications
                 WHERE to_department_id = :department_id AND status = 'Unread'"
            );
            $stmt->execute([':department_id' => $departmentId]);
            return (int)$stmt->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }

    private function validateSend(array $visit, int $toDepartmentId, string $reason, array $user): array
    {
        $errors = [];
        if (!$this->permissionService->canViewEncounter($visit, $user)) {
            $errors[] = 'You cannot access this encounter.';
        }
        if (in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)) {
            $errors[] = 'Completed or cancelled encounters cannot receive new notifications.';
        }
        if ($reason === '') {
            $errors[] = 'Reason is required.';
        }
        if ($toDepartmentId <= 0 || !$this->departmentExists($toDepartmentId)) {
            $errors[] = 'Destination department is invalid.';
        }
        return $errors;
    }

    private function transition(int $notificationId, array $user, string $targetStatus): array
    {
        try {
            $this->pdo->beginTransaction();
            $notification = $this->lockNotification($notificationId);
            if (!$notification) {
                $this->pdo->rollBack();
                return $this->failure(['Notification not found.']);
            }
            if (!$this->permissionService->canAccessDepartment((int)$notification['to_department_id'], $user)) {
                $this->pdo->rollBack();
                return $this->failure(['You cannot manage this department notification.']);
            }

            if ($targetStatus === 'Read') {
                $stmt = $this->pdo->prepare("
                    UPDATE department_notifications
                    SET status = CASE WHEN status = 'Unread' THEN 'Read' ELSE status END,
                        read_by = COALESCE(read_by, :read_actor),
                        read_at = COALESCE(read_at, NOW())
                    WHERE id = :id
                ");
                $action = 'DEPARTMENT_NOTIFICATION_READ';
                $description = 'Marked department notification #' . $notificationId . ' as read.';
                $params = [
                    ':read_actor' => (int)$user['id'],
                    ':id' => $notificationId
                ];
            } else {
                $stmt = $this->pdo->prepare("
                    UPDATE department_notifications
                    SET status = 'Resolved',
                        resolved_by = :resolved_actor,
                        resolved_at = NOW(),
                        read_by = COALESCE(read_by, :read_actor),
                        read_at = COALESCE(read_at, NOW())
                    WHERE id = :id
                ");
                $action = 'DEPARTMENT_NOTIFICATION_RESOLVED';
                $description = 'Resolved department notification #' . $notificationId . '.';
                $params = [
                    ':read_actor' => (int)$user['id'],
                    ':resolved_actor' => (int)$user['id'],
                    ':id' => $notificationId
                ];
            }
            $stmt->execute($params);

            if (!$this->audit($action, $notification, $user, $description)) {
                throw new RuntimeException('Unable to audit notification transition.');
            }

            $this->pdo->commit();
            return ['success' => true, 'notification_id' => $notificationId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to update department notification.']);
        }
    }

    private function lockVisit(int $visitId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM visits WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $visitId]);
        $visit = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$visit) {
            throw new RuntimeException('Encounter not found.');
        }
        return $visit;
    }

    private function lockNotification(int $notificationId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM department_notifications WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $notificationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function departmentExists(int $departmentId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM departments
             WHERE id = :id
               AND is_active = 1
               AND department_name <> 'Super Administrator'"
        );
        $stmt->execute([':id' => $departmentId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function audit(string $action, array $row, array $user, string $description): bool
    {
        return $this->auditService->logPatient(
            (int)($user['id'] ?? 0),
            (int)$row['patient_id'],
            (int)($row['visit_id'] ?? $row['id'] ?? 0),
            'Department Notifications',
            $action,
            $description,
            (int)($row['from_department_id'] ?? 0) ?: null,
            'INFO',
            $action
        );
    }

    private function baseSelect(): string
    {
        return '
            SELECT n.*,
                   p.hospital_number,
                   p.first_name,
                   p.last_name,
                   v.visit_number,
                   v.visit_status,
                   from_d.department_name AS from_department_name,
                   to_d.department_name AS to_department_name,
                   CONCAT(sender.first_name, " ", sender.last_name) AS sender_name,
                   CONCAT(reader.first_name, " ", reader.last_name) AS read_by_name,
                   CONCAT(resolver.first_name, " ", resolver.last_name) AS resolved_by_name
            FROM department_notifications n
            INNER JOIN patients p ON p.id = n.patient_id
            INNER JOIN visits v ON v.id = n.visit_id
            LEFT JOIN departments from_d ON from_d.id = n.from_department_id
            INNER JOIN departments to_d ON to_d.id = n.to_department_id
            INNER JOIN users sender ON sender.id = n.sent_by
            LEFT JOIN users reader ON reader.id = n.read_by
            LEFT JOIN users resolver ON resolver.id = n.resolved_by
        ';
    }

    private function failure(array $errors): array
    {
        return ['success' => false, 'errors' => $errors];
    }

    private function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
