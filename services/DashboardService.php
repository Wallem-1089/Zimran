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
                    'charts' => $this->chartSummary(),
                    'notifications' => $this->notificationSummary(),
                    'clinical' => $this->clinicalActivitySummary(),
                    'financial' => $this->financialSummary(),
                    'inventory' => $this->inventorySummary()
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

    public function recordReportView(?int $userId, string $action = 'REPORT_VIEWED'): bool
    {
        return $this->auditService->log(
            $userId,
            null,
            'Reports',
            $action,
            'Read-only report viewed.'
        );
    }

    public function getPatientEncounterActivity(array $filters = []): array
    {
        $range = $this->dateRange($filters);
        $params = [':from' => $range['from'], ':to' => $range['to']];
        $where = 'v.visit_date BETWEEN :from AND :to';

        if (!empty($filters['department_id'])) {
            $where .= ' AND v.current_department_id = :department_id';
            $params[':department_id'] = (int)$filters['department_id'];
        }

        if (!empty($filters['status'])) {
            $where .= ' AND v.visit_status = :status';
            $params[':status'] = (string)$filters['status'];
        }

        $summary = $this->fetchOne(
            "SELECT COUNT(*) AS encounter_count,
                    SUM(v.visit_status = 'Completed') AS completed_count,
                    SUM(v.visit_status NOT IN ('Completed','Cancelled')) AS active_count
             FROM visits v
             WHERE {$where}",
            $params
        );

        $byDepartment = $this->fetchAll(
            "SELECT COALESCE(d.department_name, 'Unassigned') AS department_name,
                    COUNT(*) AS encounter_count,
                    SUM(v.visit_status = 'Completed') AS completed_count
             FROM visits v
             LEFT JOIN departments d ON d.id = v.current_department_id
             WHERE {$where}
             GROUP BY d.id, d.department_name
             ORDER BY encounter_count DESC, department_name",
            $params
        );

        return [
            'filters' => $range,
            'summary' => $summary,
            'by_department' => $byDepartment,
        ];
    }

    public function getClinicalActivityReport(array $filters = []): array
    {
        $range = $this->dateRange($filters);
        $departmentId = (int)($filters['department_id'] ?? 0);

        return [
            'filters' => $range,
            'items' => [
                ['label' => 'Consultations', 'total' => $this->countTable('consultations', 'created_at', $range, $departmentId)],
                ['label' => 'Nursing Assessments', 'total' => $this->countTable('nursing_assessments', 'created_at', $range, $departmentId)],
                ['label' => 'Vital Signs', 'total' => $this->countTable('vital_signs', 'created_at', $range, $departmentId)],
                ['label' => 'Laboratory Requests', 'total' => $this->countTable('laboratory_requests', 'created_at', $range, $departmentId)],
                ['label' => 'Radiology Requests', 'total' => $this->countTable('radiology_requests', 'created_at', $range, $departmentId)],
                ['label' => 'Physiotherapy Records', 'total' => $this->countTable('physiotherapy_records', 'created_at', $range, $departmentId)],
                ['label' => 'Physiotherapy Sessions', 'total' => $this->countTable('physiotherapy_sessions', 'created_at', $range, 0)],
                ['label' => 'Theatre Records', 'total' => $this->countTable('theatre_records', 'created_at', $range, $departmentId)],
                ['label' => 'Prescriptions', 'total' => $this->countTable('prescriptions', 'created_at', $range, $departmentId)],
            ],
        ];
    }

    public function getFinancialReport(array $filters = []): array
    {
        $range = $this->dateRange($filters);

        return [
            'filters' => $range,
            'charges' => $this->sumTable('patient_charges', 'created_at', 'amount', $range, "status = 'Active'"),
            'invoices' => $this->sumTable('invoices', 'created_at', 'total_amount', $range, "status <> 'Cancelled'"),
            'payments' => $this->sumTable('payments', 'created_at', 'amount', $range),
            'open_invoices' => $this->scalar("SELECT COUNT(*) FROM invoices WHERE status IN ('Unpaid','Partially Paid')"),
            'outstanding_balance' => $this->scalar("SELECT COALESCE(SUM(balance_due),0) FROM invoices WHERE status IN ('Unpaid','Partially Paid')"),
        ];
    }

    public function getInventoryReport(array $filters = []): array
    {
        $range = $this->dateRange($filters);
        $departmentId = (int)($filters['department_id'] ?? 0);
        $itemId = (int)($filters['item_id'] ?? 0);
        $params = [':from' => $range['from'], ':to' => $range['to']];
        $where = 'st.created_at BETWEEN :from AND :to';

        if ($departmentId > 0) {
            $where .= ' AND (st.from_department_id = :department_id OR st.to_department_id = :department_id)';
            $params[':department_id'] = $departmentId;
        }

        if ($itemId > 0) {
            $where .= ' AND st.inventory_item_id = :item_id';
            $params[':item_id'] = $itemId;
        }

        $transactions = $this->fetchAll(
            "SELECT st.transaction_type, COUNT(*) AS total_transactions, COALESCE(SUM(st.quantity),0) AS total_quantity
             FROM stock_transactions st
             WHERE {$where}
             GROUP BY st.transaction_type
             ORDER BY st.transaction_type",
            $params
        );

        $balanceParams = [];
        $balanceWhere = '1 = 1';
        if ($departmentId > 0) {
            $balanceWhere .= ' AND dsb.department_id = :balance_department_id';
            $balanceParams[':balance_department_id'] = $departmentId;
        }
        if ($itemId > 0) {
            $balanceWhere .= ' AND dsb.inventory_item_id = :balance_item_id';
            $balanceParams[':balance_item_id'] = $itemId;
        }

        return [
            'filters' => $range,
            'transactions' => $transactions,
            'balances' => $this->fetchAll(
                "SELECT ii.item_code, ii.item_name, d.department_name, dsb.quantity
                 FROM department_stock_balances dsb
                 INNER JOIN inventory_items ii ON ii.id = dsb.inventory_item_id
                 INNER JOIN departments d ON d.id = dsb.department_id
                 WHERE {$balanceWhere}
                 ORDER BY d.department_name, ii.item_name
                 LIMIT 100",
                $balanceParams
            ),
            'zero_or_low_stock' => $this->scalar('SELECT COUNT(*) FROM department_stock_balances WHERE quantity <= 0'),
        ];
    }

    public function listReportDepartments(): array
    {
        return $this->fetchAll(
            'SELECT id, department_name
             FROM departments
             ORDER BY department_name'
        );
    }

    public function listReportInventoryItems(): array
    {
        if (!$this->tableExists('inventory_items')) {
            return [];
        }

        return $this->fetchAll(
            'SELECT id, item_code, item_name
             FROM inventory_items
             ORDER BY item_name'
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
            'completed_today' => (int)$this->pdo->query(
                "SELECT COUNT(*) FROM visits
                 WHERE visit_status = 'Completed'
                 AND updated_at >= CURDATE()"
            )->fetchColumn(),
            'completed' => $completed,
            'cancelled' => $cancelled,
            'by_status' => $byStatus
        ];
    }

    private function notificationSummary(): array
    {
        if (!$this->tableExists('department_notifications')) {
            return ['unread' => 0];
        }

        return [
            'unread' => $this->scalar("SELECT COUNT(*) FROM department_notifications WHERE status = 'Unread'"),
        ];
    }

    private function clinicalActivitySummary(): array
    {
        $today = ['from' => date('Y-m-d 00:00:00'), 'to' => date('Y-m-d 23:59:59')];

        return [
            'consultations_today' => $this->countTable('consultations', 'created_at', $today),
            'nursing_today' => $this->countTable('nursing_assessments', 'created_at', $today),
            'laboratory_today' => $this->countTable('laboratory_requests', 'created_at', $today),
            'radiology_today' => $this->countTable('radiology_requests', 'created_at', $today),
            'physiotherapy_records_today' => $this->countTable('physiotherapy_records', 'created_at', $today),
            'physiotherapy_sessions_today' => $this->countTable('physiotherapy_sessions', 'created_at', $today),
            'theatre_today' => $this->countTable('theatre_records', 'created_at', $today),
            'prescriptions_today' => $this->countTable('prescriptions', 'created_at', $today),
        ];
    }

    private function financialSummary(): array
    {
        $today = ['from' => date('Y-m-d 00:00:00'), 'to' => date('Y-m-d 23:59:59')];

        return [
            'charges_today' => $this->sumTable('patient_charges', 'created_at', 'amount', $today, "status = 'Active'"),
            'payments_today' => $this->sumTable('payments', 'created_at', 'amount', $today),
            'outstanding_balance' => $this->scalar("SELECT COALESCE(SUM(balance_due),0) FROM invoices WHERE status IN ('Unpaid','Partially Paid')"),
            'open_invoices' => $this->scalar("SELECT COUNT(*) FROM invoices WHERE status IN ('Unpaid','Partially Paid')"),
        ];
    }

    private function inventorySummary(): array
    {
        $today = ['from' => date('Y-m-d 00:00:00'), 'to' => date('Y-m-d 23:59:59')];

        return [
            'active_items' => $this->scalar('SELECT COUNT(*) FROM inventory_items WHERE is_active = 1'),
            'low_or_zero_stock' => $this->scalar('SELECT COUNT(*) FROM department_stock_balances WHERE quantity <= 0'),
            'receipts_today' => $this->countStockTransactions('Receipt', $today),
            'issues_today' => $this->countStockTransactions('Issue', $today),
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

    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = :table'
            );
            $stmt->execute([':table' => $table]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function scalar(string $sql, array $params = []): int|float
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $value = $stmt->fetchColumn();
            return is_numeric($value) ? (float)$value : 0;
        } catch (Throwable) {
            return 0;
        }
    }

    private function fetchOne(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable) {
            return [];
        }
    }

    private function fetchAll(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable) {
            return [];
        }
    }

    private function dateRange(array $filters): array
    {
        $from = trim((string)($filters['date_from'] ?? ''));
        $to = trim((string)($filters['date_to'] ?? ''));

        if ($from === '') {
            $from = date('Y-m-d');
        }
        if ($to === '') {
            $to = date('Y-m-d');
        }

        return [
            'from_date' => $from,
            'to_date' => $to,
            'from' => $from . ' 00:00:00',
            'to' => $to . ' 23:59:59',
        ];
    }

    private function countTable(string $table, string $dateColumn, array $range, int $departmentId = 0): int
    {
        if (!$this->tableExists($table)) {
            return 0;
        }

        $params = [':from' => $range['from'], ':to' => $range['to']];
        $where = "{$dateColumn} BETWEEN :from AND :to";
        if ($departmentId > 0) {
            $where .= ' AND department_id = :department_id';
            $params[':department_id'] = $departmentId;
        }

        return (int)$this->scalar("SELECT COUNT(*) FROM {$table} WHERE {$where}", $params);
    }

    private function sumTable(
        string $table,
        string $dateColumn,
        string $sumColumn,
        array $range,
        string $extraWhere = '1 = 1'
    ): float {
        if (!$this->tableExists($table)) {
            return 0.0;
        }

        return (float)$this->scalar(
            "SELECT COALESCE(SUM({$sumColumn}),0) FROM {$table}
             WHERE {$dateColumn} BETWEEN :from AND :to AND {$extraWhere}",
            [':from' => $range['from'], ':to' => $range['to']]
        );
    }

    private function countStockTransactions(string $type, array $range): int
    {
        if (!$this->tableExists('stock_transactions')) {
            return 0;
        }

        return (int)$this->scalar(
            'SELECT COUNT(*) FROM stock_transactions
             WHERE transaction_type = :type AND created_at BETWEEN :from AND :to',
            [':type' => $type, ':from' => $range['from'], ':to' => $range['to']]
        );
    }
}
