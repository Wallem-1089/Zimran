<?php

declare(strict_types=1);

class AuditService
{
    /**
     * Database connection.
     *
     * @var PDO
     */
    private PDO $db;

    /**
     * Constructor.
     *
     * @param PDO $db
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Record an audit log.
     *
     * @param int|null $userId
     * @param int|null $visitId
     * @param string $module
     * @param string $action
     * @param string $description
     * @return bool
     */
    public function log(
        ?int $userId,
        ?int $visitId,
        string $module,
        string $action,
        string $description,
        ?int $departmentId = null,
        string $severity = 'INFO',
        ?string $eventType = null
    ): bool {

        return $this->writeLog(
            $userId,
            $visitId,
            null,
            $module,
            $action,
            $description,
            $departmentId,
            $severity,
            $eventType
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Patient-Aware Audit
    |--------------------------------------------------------------------------
    */

    public function logPatient(
        ?int $userId,
        int $patientId,
        ?int $visitId,
        string $module,
        string $action,
        string $description,
        ?int $departmentId = null,
        string $severity = 'INFO',
        ?string $eventType = null
    ): bool {
        if ($patientId <= 0) {
            return false;
        }

        return $this->writeLog(
            $userId,
            $visitId,
            $patientId,
            $module,
            $action,
            $description,
            $departmentId,
            $severity,
            $eventType
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Protected Health Information Access
    |--------------------------------------------------------------------------
    */

    public function logPatientAccess(
        int $userId,
        int $patientId,
        ?int $visitId,
        ?int $departmentId,
        string $accessType,
        string $resourceType = 'PatientChart',
        ?int $resourceId = null,
        ?string $accessReason = null
    ): bool {
        if ($userId <= 0
            || $patientId <= 0
            || trim($accessType) === ''
            || trim($resourceType) === ''
        ) {
            return false;
        }

        $ownsTransaction = !$this->db->inTransaction();

        try {
            if ($ownsTransaction) {
                $this->db->beginTransaction();
            }

            $stmt = $this->db->prepare('
                INSERT INTO record_access_logs (
                    patient_id,
                    visit_id,
                    user_id,
                    department_id,
                    access_type,
                    resource_type,
                    resource_id,
                    access_reason,
                    ip_address,
                    user_agent
                ) VALUES (
                    :patient_id,
                    :visit_id,
                    :user_id,
                    :department_id,
                    :access_type,
                    :resource_type,
                    :resource_id,
                    :access_reason,
                    :ip_address,
                    :user_agent
                )
            ');
            $stmt->execute([
                ':patient_id' => $patientId,
                ':visit_id' => $visitId,
                ':user_id' => $userId,
                ':department_id' => $departmentId,
                ':access_type' => trim($accessType),
                ':resource_type' => trim($resourceType),
                ':resource_id' => $resourceId,
                ':access_reason' => $accessReason,
                ':ip_address' => $this->getClientIp(),
                ':user_agent' => $this->getUserAgent()
            ]);

            if (!$this->logPatient(
                $userId,
                $patientId,
                $visitId,
                'Medical Records',
                'MEDICAL_RECORD_VIEWED',
                'Viewed patient chart #' . $patientId . '.',
                $departmentId,
                'INFO',
                'MEDICAL_RECORD_VIEWED'
            )) {
                throw new RuntimeException('Unable to record patient access audit.');
            }

            if ($ownsTransaction) {
                $this->db->commit();
            }

            return true;
        } catch (Throwable $exception) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }

            if (!$ownsTransaction) {
                throw $exception;
            }

            return false;
        }
    }

    private function writeLog(
        ?int $userId,
        ?int $visitId,
        ?int $patientId,
        string $module,
        string $action,
        string $description,
        ?int $departmentId,
        string $severity,
        ?string $eventType
    ): bool {

        $sql = "

            INSERT INTO audit_logs (

                user_id,
                visit_id,
                patient_id,
                module,
                action,
                description,
                ip_address,
                user_agent,
                department_id,
                severity,
                event_type

            )

            VALUES (

                :user_id,
                :visit_id,
                :patient_id,
                :module,
                :action,
                :description,
                :ip_address,
                :user_agent,
                :department_id,
                :severity,
                :event_type

            )

        ";

        $ownsTransaction = !$this->db->inTransaction();

        try {
            if ($ownsTransaction) {
                $this->db->beginTransaction();
            }

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':user_id'     => $userId,
                ':visit_id'    => $visitId,
                ':patient_id'  => $patientId,
                ':module'      => $module,
                ':action'      => $action,
                ':description' => $description,
                ':ip_address'  => $this->getClientIp(),
                ':user_agent' => $this->getUserAgent(),
                ':department_id' => $departmentId,
                ':severity' => strtoupper($severity),
                ':event_type' => $eventType ?? $action
            ]);

            if ($ownsTransaction) {
                $this->db->commit();
            }

            return $result;
        } catch (Throwable $exception) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }

            if (!$ownsTransaction) {
                throw $exception;
            }

            return false;
        }
    }

    /**
     * Determine client IP address.
     *
     * @return string
     */
    private function getClientIp(): string
    {
        $keys = [

            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR'

        ];

        foreach ($keys as $key) {

            if (!empty($_SERVER[$key])) {

                return trim(explode(',', $_SERVER[$key])[0]);

            }

        }

        return 'UNKNOWN';
    }

    private function getUserAgent(): ?string
    {
        $userAgent = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
        return $userAgent === '' ? null : substr($userAgent, 0, 255);
    }

    /**
     * Successful login.
     */
    public function loginSuccess(int $userId): void
    {
        $this->log(

            $userId,
            null,
            'Authentication',
            'LOGIN_SUCCESS',
            'User logged into the system.',
            null,
            'INFO',
            'LOGIN_SUCCESS'

        );
    }

    /**
     * Failed login.
     */
    public function loginFailed(string $login, ?int $userId = null): void
    {
        $this->log(

            $userId,
            null,
            'Authentication',
            'LOGIN_FAILED',
            'Failed login attempt.',
            null,
            'WARNING',
            'LOGIN_FAILED'

        );
    }

    /**
     * Logout.
     */
    public function logout(int $userId): void
    {
        $this->log(

            $userId,
            null,
            'Authentication',
            'LOGOUT',
            'User logged out.'

        );
    }

    /**
     * Patient registration.
     */
    public function patientRegistered(
        int $userId,
        string $hospitalNumber
    ): void {

        $this->log(

            $userId,
            null,
            'Patients',
            'CREATE',
            "Registered patient {$hospitalNumber}"

        );
    }

    /**
     * Encounter created.
     */
    public function encounterCreated(
        int $userId,
        int $visitId
    ): void {

        $this->log(

            $userId,
            $visitId,
            'Encounter',
            'CREATE',
            "Created encounter #{$visitId}"

        );
    }

    /**
     * Consultation completed.
     */
    public function consultationCompleted(
        int $userId,
        int $visitId
    ): void {

        $this->log(

            $userId,
            $visitId,
            'Consultation',
            'UPDATE',
            'Consultation completed.'

        );
    }

    /**
     * Laboratory request.
     */
    public function laboratoryRequested(
        int $userId,
        int $visitId
    ): void {

        $this->log(

            $userId,
            $visitId,
            'Laboratory',
            'REQUEST',
            'Laboratory investigation requested.'

        );
    }

    /**
     * Laboratory result uploaded.
     */
    public function laboratoryResultUploaded(
        int $userId,
        int $visitId
    ): void {

        $this->log(

            $userId,
            $visitId,
            'Laboratory',
            'RESULT',
            'Laboratory results uploaded.'

        );
    }

    /**
     * X-Ray completed.
     */
    public function radiologyCompleted(
        int $userId,
        int $visitId
    ): void {

        $this->log(

            $userId,
            $visitId,
            'Radiology',
            'COMPLETE',
            'Radiology report uploaded.'

        );
    }

    /**
     * Medication dispensed.
     */
    public function medicationDispensed(
        int $userId,
        int $visitId
    ): void {

        $this->log(

            $userId,
            $visitId,
            'Pharmacy',
            'DISPENSE',
            'Medication dispensed.'

        );
    }

    /**
     * Payment received.
     */
    public function paymentReceived(
        int $userId,
        int $visitId
    ): void {

        $this->log(

            $userId,
            $visitId,
            'Accounts',
            'PAYMENT',
            'Payment received.'

        );
    }

    /**
     * Generic update.
     */
    public function updated(
        int $userId,
        ?int $visitId,
        string $module,
        string $description
    ): void {

        $this->log(

            $userId,
            $visitId,
            $module,
            'UPDATE',
            $description

        );
    }

    /**
     * Generic delete.
     */
    public function deleted(
        int $userId,
        ?int $visitId,
        string $module,
        string $description
    ): void {

        $this->log(

            $userId,
            $visitId,
            $module,
            'DELETE',
            $description

        );
    }

    /**
     * Get recent audit logs.
     *
     * @param int $limit
     * @return array
     */
    public function recent(int $limit = 50): array
    {
        $stmt = $this->db->prepare("

            SELECT

                a.*,

                u.first_name,

                u.last_name,

                u.username

            FROM audit_logs a

            LEFT JOIN users u
                ON a.user_id = u.id

            ORDER BY a.created_at DESC

            LIMIT :limit

        ");

        $stmt->bindValue(

            ':limit',

            $limit,

            PDO::PARAM_INT

        );

        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get audit history for a specific encounter.
     *
     * @param int $visitId
     * @return array
     */
    public function getEncounterTimeline(int $visitId): array
    {
        $stmt = $this->db->prepare("

            SELECT

                a.*,

                u.first_name,

                u.last_name,

                u.username

            FROM audit_logs a

            LEFT JOIN users u
                ON a.user_id = u.id

            WHERE a.visit_id = :visit_id

            ORDER BY a.created_at ASC

        ");

        $stmt->execute([

            ':visit_id' => $visitId

        ]);

        return $stmt->fetchAll();
    }

    public function search(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        $where = [];
        $parameters = [];

        if (!empty($filters['module'])) {
            $where[] = 'a.module = :module';
            $parameters[':module'] = $filters['module'];
        }
        if (!empty($filters['action'])) {
            $where[] = 'a.action = :action';
            $parameters[':action'] = $filters['action'];
        }
        if (!empty($filters['event_type'])) {
            $where[] = 'a.event_type = :event_type';
            $parameters[':event_type'] = $filters['event_type'];
        }
        if (!empty($filters['user_id'])) {
            $where[] = 'a.user_id = :user_id';
            $parameters[':user_id'] = (int)$filters['user_id'];
        }
        if (!empty($filters['visit_id'])) {
            $where[] = 'a.visit_id = :visit_id';
            $parameters[':visit_id'] = (int)$filters['visit_id'];
        }
        if (!empty($filters['patient_id'])) {
            $where[] = 'a.patient_id = :patient_id';
            $parameters[':patient_id'] = (int)$filters['patient_id'];
        }
        if (!empty($filters['department_id'])) {
            $where[] = 'a.department_id = :department_id';
            $parameters[':department_id'] = (int)$filters['department_id'];
        }
        if (!empty($filters['severity'])) {
            $where[] = 'a.severity = :severity';
            $parameters[':severity'] = $filters['severity'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'a.created_at >= :date_from';
            $parameters[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'a.created_at <= :date_to';
            $parameters[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $condition = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM audit_logs a' . $condition);
        $countStmt->execute($parameters);
        $total = (int)$countStmt->fetchColumn();

        $sql = '
            SELECT a.*, u.first_name, u.last_name, u.username,
                   d.department_name
            FROM audit_logs a
            LEFT JOIN users u ON u.id = a.user_id
            LEFT JOIN departments d ON d.id = a.department_id
        ' . $condition . ' ORDER BY a.created_at DESC, a.id DESC LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($sql);
        foreach ($parameters as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'success' => true,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'errors' => []
        ];
    }

    public function getPatientHistory(
        int $patientId,
        int $page = 1,
        int $perPage = 25
    ): array {
        if ($patientId <= 0) {
            return [
                'success' => false,
                'data' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => max(1, $perPage),
                'errors' => ['Patient is required.']
            ];
        }

        return $this->search(
            ['patient_id' => $patientId],
            $page,
            $perPage
        );
    }

    public function securitySummary(): array
    {
        $queries = [
            'active_sessions' => "SELECT COUNT(*) FROM active_sessions WHERE status = 'Active'",
            'failed_logins_today' => "SELECT COUNT(*) FROM audit_logs WHERE action = 'LOGIN_FAILED' AND created_at >= CURDATE()",
            'locked_accounts' => "SELECT COUNT(*) FROM users WHERE locked_at IS NOT NULL",
            'password_resets_today' => "SELECT COUNT(*) FROM audit_logs WHERE action = 'PASSWORD_RESET' AND created_at >= CURDATE()",
            'security_events_today' => "SELECT COUNT(*) FROM audit_logs WHERE module = 'Security' AND created_at >= CURDATE()"
        ];
        $summary = [];
        foreach ($queries as $key => $sql) {
            $summary[$key] = (int)$this->db->query($sql)->fetchColumn();
        }
        return ['success' => true, 'data' => $summary, 'errors' => []];
    }

    public function recentByModules(array $modules, int $limit = 8): array
    {
        if ($modules === []) {
            return [];
        }

        $placeholders = [];
        $parameters = [];
        foreach (array_values($modules) as $index => $module) {
            $key = ':module_' . $index;
            $placeholders[] = $key;
            $parameters[$key] = $module;
        }

        $stmt = $this->db->prepare(
            'SELECT a.*, u.first_name, u.last_name, u.username,
                    d.department_name
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             LEFT JOIN departments d ON d.id = a.department_id
             WHERE a.module IN (' . implode(',', $placeholders) . ')
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT :limit'
        );
        foreach ($parameters as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', max(1, min(50, $limit)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function activityByDay(array $actions = [], int $days = 7): array
    {
        $where = 'created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)';
        $parameters = [':days' => max(1, min(31, $days))];

        if ($actions !== []) {
            $placeholders = [];
            foreach (array_values($actions) as $index => $action) {
                $key = ':action_' . $index;
                $placeholders[] = $key;
                $parameters[$key] = $action;
            }
            $where .= ' AND action IN (' . implode(',', $placeholders) . ')';
        }

        $stmt = $this->db->prepare(
            "SELECT DATE(created_at) AS activity_date, COUNT(*) AS total
             FROM audit_logs WHERE {$where}
             GROUP BY DATE(created_at) ORDER BY activity_date"
        );
        foreach ($parameters as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function userHistory(int $userId, string $module = 'Authentication'): array
    {
        $stmt = $this->db->prepare('
            SELECT a.*, d.department_name
            FROM audit_logs a
            LEFT JOIN departments d ON d.id = a.department_id
            WHERE a.user_id = :user_id AND a.module = :module
            ORDER BY a.created_at DESC, a.id DESC
        ');
        $stmt->execute([':user_id' => $userId, ':module' => $module]);
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'errors' => []];
    }
}
