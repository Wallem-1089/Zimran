<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/PermissionService.php';

class UserNotificationService
{
    public function __construct(
        private PDO $pdo,
        private ?AuditService $auditService = null,
        private ?PermissionService $permissionService = null
    ) {
        $this->auditService ??= new AuditService($pdo);
        $this->permissionService ??= new PermissionService($pdo);
    }

    public function send(array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();

            $visit = $this->lockVisit((int)($data['visit_id'] ?? 0));
            $toUserId = (int)($data['to_user_id'] ?? 0);
            $message = trim((string)($data['message'] ?? ''));
            $errors = $this->validateSend($visit, $toUserId, $message, $user);

            if ($errors !== []) {
                $this->pdo->rollBack();
                return $this->failure($errors);
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO user_notifications (
                    visit_id, patient_id, to_user_id, sent_by, message, status
                 ) VALUES (
                    :visit_id, :patient_id, :to_user_id, :sent_by, :message, 'Unread'
                 )"
            );
            $stmt->execute([
                ':visit_id' => (int)$visit['id'],
                ':patient_id' => (int)$visit['patient_id'],
                ':to_user_id' => $toUserId,
                ':sent_by' => (int)$user['id'],
                ':message' => $message,
            ]);

            $notificationId = (int)$this->pdo->lastInsertId();

            if (!$this->auditService->logPatient(
                (int)$user['id'],
                (int)$visit['patient_id'],
                (int)$visit['id'],
                'User Notifications',
                'USER_NOTIFICATION_SENT',
                'Sent user notification #' . $notificationId . '.',
                null,
                'INFO',
                'USER_NOTIFICATION_SENT'
            )) {
                throw new RuntimeException('Unable to audit user notification.');
            }

            $this->pdo->commit();
            return ['success' => true, 'notification_id' => $notificationId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to send user notification.']);
        }
    }

    public function listForUser(int $userId, string $status = ''): array
    {
        if ($userId <= 0) {
            return [];
        }

        $params = [':user_id' => $userId];
        $where = ' WHERE n.to_user_id = :user_id';
        if (in_array($status, ['Unread', 'Read', 'Resolved'], true)) {
            $where .= ' AND n.status = :status';
            $params[':status'] = $status;
        }

        $stmt = $this->pdo->prepare(
            $this->baseSelect() . $where . ' ORDER BY n.created_at DESC, n.id DESC'
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listForVisit(int $visitId): array
    {
        if ($visitId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare(
            $this->baseSelect() . ' WHERE n.visit_id = :visit_id ORDER BY n.created_at DESC, n.id DESC'
        );
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

    public function getUnreadCount(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*)
                 FROM user_notifications
                 WHERE to_user_id = :user_id
                   AND status = 'Unread'"
            );
            $stmt->execute([':user_id' => $userId]);
            return (int)$stmt->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }

    public function listActiveUsers(): array
    {
        $stmt = $this->pdo->query(
            "SELECT u.id, u.employee_id, u.first_name, u.last_name,
                    r.role_name, d.department_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             INNER JOIN departments d ON d.id = u.department_id
             WHERE u.status = 'Active'
               AND u.locked_at IS NULL
               AND LOWER(u.username) <> 'walter'
               AND r.role_name <> 'Super Administrator'
               AND d.department_name <> 'Super Administrator'
             ORDER BY u.first_name, u.last_name, u.id"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function validateSend(array $visit, int $toUserId, string $message, array $user): array
    {
        $errors = [];

        if (!$this->permissionService->canViewEncounter($visit, $user)) {
            $errors[] = 'You cannot access this encounter.';
        }
        if (in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)) {
            $errors[] = 'Completed or cancelled encounters cannot receive new user notifications.';
        }
        if ($toUserId <= 0 || !$this->userExists($toUserId)) {
            $errors[] = 'Destination user is invalid.';
        }
        if ($toUserId === (int)($user['id'] ?? 0)) {
            $errors[] = 'Choose another user account.';
        }
        if ($message === '') {
            $errors[] = 'Message is required.';
        }
        if (mb_strlen($message) > 2000) {
            $errors[] = 'Message is too long.';
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
            if ((int)$notification['to_user_id'] !== (int)($user['id'] ?? 0)) {
                $this->pdo->rollBack();
                return $this->failure(['You cannot manage this notification.']);
            }

            if ($targetStatus === 'Read') {
                $stmt = $this->pdo->prepare(
                    "UPDATE user_notifications
                     SET status = CASE WHEN status = 'Unread' THEN 'Read' ELSE status END,
                         read_by = COALESCE(read_by, :actor),
                         read_at = COALESCE(read_at, NOW())
                     WHERE id = :id"
                );
                $action = 'USER_NOTIFICATION_READ';
                $message = 'Marked user notification #' . $notificationId . ' as read.';
            } else {
                $stmt = $this->pdo->prepare(
                    "UPDATE user_notifications
                     SET status = 'Resolved',
                         resolved_by = :resolved_by,
                         resolved_at = NOW(),
                         read_by = COALESCE(read_by, :read_by),
                         read_at = COALESCE(read_at, NOW())
                     WHERE id = :id"
                );
                $action = 'USER_NOTIFICATION_RESOLVED';
                $message = 'Resolved user notification #' . $notificationId . '.';
            }

            $executeParams = $targetStatus === 'Read'
                ? [':actor' => (int)$user['id'], ':id' => $notificationId]
                : [
                    ':resolved_by' => (int)$user['id'],
                    ':read_by' => (int)$user['id'],
                    ':id' => $notificationId,
                ];

            $stmt->execute($executeParams);

            if (!$this->auditService->logPatient(
                (int)$user['id'],
                (int)$notification['patient_id'],
                (int)$notification['visit_id'],
                'User Notifications',
                $action,
                $message,
                null,
                'INFO',
                $action
            )) {
                throw new RuntimeException('Unable to audit user notification transition.');
            }

            $this->pdo->commit();
            return ['success' => true, 'notification_id' => $notificationId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to update user notification.']);
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
        $stmt = $this->pdo->prepare('SELECT * FROM user_notifications WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $notificationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function userExists(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             INNER JOIN departments d ON d.id = u.department_id
             WHERE u.id = :id
               AND u.status = 'Active'
               AND u.locked_at IS NULL
               AND LOWER(u.username) <> 'walter'
               AND r.role_name <> 'Super Administrator'
               AND d.department_name <> 'Super Administrator'"
        );
        $stmt->execute([':id' => $userId]);
        return (int)$stmt->fetchColumn() > 0;
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
                   CONCAT(sender.first_name, " ", sender.last_name) AS sender_name,
                   CONCAT(target.first_name, " ", target.last_name) AS target_name,
                   target.employee_id AS target_employee_id,
                   target_role.role_name AS target_role_name,
                   target_department.department_name AS target_department_name,
                   CONCAT(reader.first_name, " ", reader.last_name) AS read_by_name,
                   CONCAT(resolver.first_name, " ", resolver.last_name) AS resolved_by_name
            FROM user_notifications n
            INNER JOIN patients p ON p.id = n.patient_id
            INNER JOIN visits v ON v.id = n.visit_id
            INNER JOIN users sender ON sender.id = n.sent_by
            INNER JOIN users target ON target.id = n.to_user_id
            INNER JOIN roles target_role ON target_role.id = target.role_id
            INNER JOIN departments target_department ON target_department.id = target.department_id
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
