<?php

declare(strict_types=1);

require_once __DIR__ . '/PermissionService.php';

class PatientCommunicationService
{
    private PDO $pdo;
    private PermissionService $permissionService;
    private ?bool $tableAvailable = null;

    public function __construct(PDO $pdo, ?PermissionService $permissionService = null)
    {
        $this->pdo = $pdo;
        $this->permissionService = $permissionService ?? new PermissionService($pdo);
    }

    public function recordWhatsAppHandoff(array $data, array $user): array
    {
        if (!$this->tableExists()) {
            return [
                'success' => true,
                'skipped' => true,
                'patient_communication_id' => null,
                'errors' => [],
            ];
        }

        $patientId = (int)($data['patient_id'] ?? 0);
        $sentBy = (int)($user['id'] ?? 0);
        $recipientPhone = trim((string)($data['recipient_phone'] ?? ''));
        $message = trim((string)($data['message'] ?? ''));
        $sourceModule = trim((string)($data['source_module'] ?? ''));
        $sourceType = trim((string)($data['source_type'] ?? ''));

        $errors = [];
        if ($patientId <= 0) {
            $errors[] = 'Patient is required.';
        }
        if ($sentBy <= 0) {
            $errors[] = 'Sender is required.';
        }
        if ($recipientPhone === '') {
            $errors[] = 'Recipient phone is required.';
        }
        if ($message === '') {
            $errors[] = 'Message is required.';
        }
        if ($sourceModule === '' || $sourceType === '') {
            $errors[] = 'Communication source is required.';
        }
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO patient_communications (
                    patient_id, visit_id, source_module, source_type, source_record_id,
                    document_id, channel, recipient_phone, message, consent_confirmed,
                    sent_by, status, provider_reference, error_message, created_at, sent_at
                ) VALUES (
                    :patient_id, :visit_id, :source_module, :source_type, :source_record_id,
                    :document_id, \'WhatsApp\', :recipient_phone, :message, :consent_confirmed,
                    :sent_by, :status, :provider_reference, :error_message, NOW(), NOW()
                )
            ');
            $stmt->execute([
                ':patient_id' => $patientId,
                ':visit_id' => !empty($data['visit_id']) ? (int)$data['visit_id'] : null,
                ':source_module' => $sourceModule,
                ':source_type' => $sourceType,
                ':source_record_id' => !empty($data['source_record_id']) ? (int)$data['source_record_id'] : null,
                ':document_id' => !empty($data['document_id']) ? (int)$data['document_id'] : null,
                ':recipient_phone' => $recipientPhone,
                ':message' => $message,
                ':consent_confirmed' => !empty($data['consent_confirmed']) ? 1 : 0,
                ':sent_by' => $sentBy,
                ':status' => (string)($data['status'] ?? 'Initiated') === 'Failed' ? 'Failed' : 'Initiated',
                ':provider_reference' => trim((string)($data['provider_reference'] ?? '')) ?: null,
                ':error_message' => trim((string)($data['error_message'] ?? '')) ?: null,
            ]);

            return [
                'success' => true,
                'patient_communication_id' => (int)$this->pdo->lastInsertId(),
                'errors' => [],
            ];
        } catch (Throwable) {
            return ['success' => false, 'errors' => ['Unable to record patient communication.']];
        }
    }

    public function listByPatient(int $patientId, ?array $user = null): array
    {
        if ($patientId <= 0 || !$this->tableExists()) {
            return [];
        }
        if ($user !== null && !$this->permissionService->canViewPatientCommunications($user)) {
            return [];
        }

        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE pc.patient_id = :patient_id ORDER BY pc.created_at DESC, pc.id DESC');
        $stmt->execute([':patient_id' => $patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listByVisit(int $visitId, ?array $user = null): array
    {
        if ($visitId <= 0 || !$this->tableExists()) {
            return [];
        }
        if ($user !== null && !$this->permissionService->canViewPatientCommunications($user)) {
            return [];
        }

        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE pc.visit_id = :visit_id ORDER BY pc.created_at DESC, pc.id DESC');
        $stmt->execute([':visit_id' => $visitId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function baseSelect(): string
    {
        return '
            SELECT
                pc.*,
                CONCAT(sent_by.first_name, " ", sent_by.last_name) AS sent_by_name,
                p.hospital_number,
                CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                v.visit_number
            FROM patient_communications pc
            INNER JOIN patients p ON p.id = pc.patient_id
            LEFT JOIN visits v ON v.id = pc.visit_id
            LEFT JOIN users sent_by ON sent_by.id = pc.sent_by
        ';
    }

    private function tableExists(): bool
    {
        if ($this->tableAvailable !== null) {
            return $this->tableAvailable;
        }

        try {
            $stmt = $this->pdo->prepare('
                SELECT COUNT(*)
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name = "patient_communications"
            ');
            $stmt->execute();
            $this->tableAvailable = (int)$stmt->fetchColumn() === 1;
        } catch (Throwable) {
            $this->tableAvailable = false;
        }

        return $this->tableAvailable;
    }
}
