<?php

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
        string $description
    ): bool {

        $sql = "

            INSERT INTO audit_logs (

                user_id,
                visit_id,
                module,
                action,
                description,
                ip_address

            )

            VALUES (

                :user_id,
                :visit_id,
                :module,
                :action,
                :description,
                :ip_address

            )

        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([

            ':user_id'     => $userId,
            ':visit_id'    => $visitId,
            ':module'      => $module,
            ':action'      => $action,
            ':description' => $description,
            ':ip_address'  => $this->getClientIp()

        ]);
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

    /**
     * Successful login.
     */
    public function loginSuccess(int $userId): void
    {
        $this->log(

            $userId,
            null,
            'Authentication',
            'LOGIN',
            'User logged into the system.'

        );
    }

    /**
     * Failed login.
     */
    public function loginFailed(string $login): void
    {
        $this->log(

            null,
            null,
            'Authentication',
            'LOGIN FAILED',
            "Failed login attempt using '{$login}'."

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
}