<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';

class DashboardService
{
    private PDO $pdo;

    private AuditService $auditService;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->auditService = new AuditService($pdo);
    }

    public function getAdministratorDashboard(): array
    {
        try {
            return [
                'success' => true,
                'data' => [
                    'users' => $this->userSummary(),
                    'departments' => $this->departmentSummary(),
                    'encounters' => $this->encounterSummary(),
                    'queue' => $this->queueSummary(),
                    'security' => $this->securitySummary(),
                    'audit' => $this->auditSummary(),
                    'charts' => $this->chartSummary()
                ],
                'errors' => []
            ];
        } catch (Throwable $exception) {
            error_log($exception->getMessage());

            return [
                'success' => false,
                'data' => [],
                'errors' => ['Dashboard data could not be loaded.']
            ];
        }
    }

    public function recordDashboardView(?int $userId): bool
    {
        return $this->auditService->log(
            $userId,
            null,
            'Administration',
            'ADMIN_DASHBOARD_VIEWED',
            'Administrator dashboard viewed.'
        );
    }

    private function userSummary(): array
    {
        $summary = [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'locked' => 0,
            'administrators' => 0,
            'by_role' => []
        ];

        $stmt = $this->pdo->query(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'Active') AS active_count,
                SUM(status = 'Inactive') AS inactive_count,
                SUM(locked_at IS NOT NULL) AS locked_count
             FROM users"
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $summary['total'] = (int)($row['total'] ?? 0);
        $summary['active'] = (int)($row['active_count'] ?? 0);
        $summary['inactive'] = (int)($row['inactive_count'] ?? 0);
        $summary['locked'] = (int)($row['locked_count'] ?? 0);

        $roleStmt = $this->pdo->query(
            "SELECT r.role_name, COUNT(u.id) AS total
             FROM roles r
             LEFT JOIN users u ON u.role_id = r.id
             GROUP BY r.id, r.role_name
             ORDER BY r.role_name"
        );

        foreach ($roleStmt->fetchAll(PDO::FETCH_ASSOC) as $role) {
            $name = (string)$role['role_name'];
            $summary['by_role'][$name] = (int)$role['total'];

            if ($name === 'System Administrator') {
                $summary['administrators'] = (int)$role['total'];
            }
        }

        return $summary;
    }

    private function departmentSummary(): array
    {
        $stmt = $this->pdo->query(
            "SELECT
                d.id,
                d.department_name,
                d.department_code,
                d.queue_enabled,
                d.is_active,
                COUNT(DISTINCT CASE WHEN u.status = 'Active' THEN u.id END) AS active_users,
                COUNT(DISTINCT CASE WHEN u.status = 'Inactive' THEN u.id END) AS inactive_users,
                COUNT(DISTINCT CASE WHEN v.visit_status NOT IN ('Completed', 'Cancelled') THEN v.id END) AS active_encounters
             FROM departments d
             LEFT JOIN users u ON u.department_id = d.id
             LEFT JOIN visits v ON v.current_department_id = d.id
             GROUP BY d.id, d.department_name, d.department_code, d.queue_enabled, d.is_active
             ORDER BY d.display_order, d.department_name"
        );

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total' => count($items),
            'active' => count(array_filter($items, static fn (array $item): bool => !empty($item['is_active']))),
            'queue_enabled' => count(array_filter($items, static fn (array $item): bool => !empty($item['queue_enabled']))),
            'items' => $items
        ];
    }

    private function encounterSummary(): array
    {
        $stmt = $this->pdo->query(
            "SELECT visit_status, COUNT(*) AS total
             FROM visits
             GROUP BY visit_status"
        );
        $byStatus = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $byStatus[(string)$row['visit_status']] = (int)$row['total'];
        }

        $completed = $byStatus['Completed'] ?? 0;
        $cancelled = $byStatus['Cancelled'] ?? 0;
        $total = array_sum($byStatus);

        return [
            'total' => $total,
            'active' => max(0, $total - $completed - $cancelled),
            'waiting' => $byStatus['Waiting'] ?? 0,
            'received' => (int)$this->pdo->query(
                "SELECT COUNT(*) FROM visits
                 WHERE current_department_received_status = 'Received'
                 AND visit_status NOT IN ('Completed', 'Cancelled')"
            )->fetchColumn(),
            'in_consultation' => $byStatus['Doctor'] ?? 0,
            'laboratory' => $byStatus['Laboratory'] ?? 0,
            'pharmacy' => $byStatus['Pharmacy'] ?? 0,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'by_status' => $byStatus
        ];
    }

    private function queueSummary(): array
    {
        $activeStatuses = "('Waiting', 'Called', 'In Progress')";
        $statusStmt = $this->pdo->query(
            "SELECT queue_status, COUNT(*) AS total
             FROM visit_queue
             WHERE queue_status IN {$activeStatuses}
             GROUP BY queue_status"
        );
        $statuses = [];
        foreach ($statusStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $statuses[(string)$row['queue_status']] = (int)$row['total'];
        }

        $departmentStmt = $this->pdo->query(
            "SELECT d.department_name, q.department_id, COUNT(*) AS total
             FROM visit_queue q
             INNER JOIN departments d ON d.id = q.department_id
             WHERE q.queue_status IN {$activeStatuses}
             GROUP BY q.department_id, d.department_name
             ORDER BY total DESC, d.department_name"
        );

        $departmentRows = $departmentStmt->fetchAll(PDO::FETCH_ASSOC);
        $average = (float)$this->pdo->query(
            "SELECT COALESCE(AVG(queue_length), 0)
             FROM (
                 SELECT department_id, COUNT(*) AS queue_length
                 FROM visit_queue
                 WHERE queue_status IN {$activeStatuses}
                 GROUP BY department_id
             ) queue_lengths"
        )->fetchColumn();

        return [
            'waiting' => $statuses['Waiting'] ?? 0,
            'called' => $statuses['Called'] ?? 0,
            'in_service' => $statuses['In Progress'] ?? 0,
            'average_length' => round($average, 1),
            'by_department' => $departmentRows
        ];
    }

    private function securitySummary(): array
    {
        $summary = $this->auditService->securitySummary();
        $data = $summary['data'] ?? [];
        $data['successful_logins_today'] = (int)$this->pdo->query(
            "SELECT COUNT(*) FROM audit_logs
             WHERE action = 'LOGIN_SUCCESS' AND created_at >= CURDATE()"
        )->fetchColumn();

        return $data;
    }

    private function auditSummary(): array
    {
        return [
            'recent' => $this->auditService->recent(8),
            'security' => $this->auditService->search(['module' => 'Security'], 1, 8)['data'] ?? [],
            'administration' => $this->auditService->search(['module' => 'Administration'], 1, 8)['data'] ?? [],
            'encounters' => $this->auditService->recentByModules(
                ['Encounter', 'Visits', 'Queue'],
                8
            )
        ];
    }

    private function chartSummary(): array
    {
        return [
            'login_activity' => $this->auditService->activityByDay(
                ['LOGIN_SUCCESS', 'LOGIN_FAILED'],
                7
            ),
            'audit_activity' => $this->auditService->activityByDay([], 7)
        ];
    }
}
