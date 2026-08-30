<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/EncounterEventService.php';
require_once __DIR__ . '/PermissionService.php';

class ECGService
{
    private AuditService $auditService;
    private EncounterEventService $eventService;
    private PermissionService $permissionService;
    private string $storageRoot;

    public function __construct(
        private PDO $pdo,
        ?AuditService $auditService = null,
        ?EncounterEventService $eventService = null,
        ?PermissionService $permissionService = null,
        ?string $storageRoot = null
    ) {
        $this->auditService = $auditService ?? new AuditService($pdo);
        $this->eventService = $eventService ?? new EncounterEventService($pdo);
        $this->permissionService = $permissionService ?? new PermissionService($pdo);
        $config = require __DIR__ . '/../config/app.php';
        $this->storageRoot = $storageRoot
            ?? rtrim((string)($config['documents']['storage_root'] ?? dirname(__DIR__, 2) . '/hms_secure_documents'), "\\/")
                . DIRECTORY_SEPARATOR . 'ecg_charts';
    }

    public function createRequest(array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();
            $visit = $this->lockVisit((int)($data['visit_id'] ?? 0));
            $source = $this->normalizeSource((string)($data['request_source'] ?? 'Clinical'));
            $priority = $this->normalizePriority((string)($data['priority'] ?? 'Routine'));
            $study = trim((string)($data['study_requested'] ?? 'ECG'));
            $indication = $this->nullableText($data['clinical_indication'] ?? null);
            $errors = $this->validateRequest($visit, $user, $source, $priority, $study, $indication);

            if (isset($data['patient_id']) && (int)$data['patient_id'] !== (int)$visit['patient_id']) {
                $errors[] = 'Patient and encounter do not match.';
            }

            $departmentId = $this->resolveEcgDepartmentId();
            if ($departmentId === null) {
                $errors[] = 'ECG department is not available.';
            }

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO ecg_requests (
                    visit_id, patient_id, requested_by, department_id,
                    request_source, study_requested, clinical_indication,
                    priority, status, created_at, updated_at
                ) VALUES (
                    :visit_id, :patient_id, :requested_by, :department_id,
                    :request_source, :study_requested, :clinical_indication,
                    :priority, "Requested", NOW(), NOW()
                )
            ');
            $stmt->execute([
                ':visit_id' => (int)$visit['id'],
                ':patient_id' => (int)$visit['patient_id'],
                ':requested_by' => (int)$user['id'],
                ':department_id' => $departmentId,
                ':request_source' => $source,
                ':study_requested' => $study,
                ':clinical_indication' => $indication,
                ':priority' => $priority,
            ]);
            $requestId = (int)$this->pdo->lastInsertId();

            $this->audit('ECG_REQUEST_CREATED', $visit, $user, 'Created ECG request #' . $requestId . '.');
            $this->event((int)$visit['id'], 'ECG_REQUESTED', 'ECG Requested', 'ECG request created.', $visit, $user);
            $this->pdo->commit();

            return ['success' => true, 'ecg_request_id' => $requestId, 'visit_id' => (int)$visit['id'], 'patient_id' => (int)$visit['patient_id'], 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to save ECG request.']);
        }
    }

    public function getRequestById(int $requestId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE er.id = :id LIMIT 1');
        $stmt->execute([':id' => $requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || ($user !== null && !$this->canViewRow($row, $user))) {
            return null;
        }
        return $this->decorateRow($row);
    }

    public function listByVisit(int $visitId, ?array $user = null): array
    {
        if ($visitId <= 0) {
            return [];
        }
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE er.visit_id = :visit_id ORDER BY er.created_at DESC, er.id DESC');
        $stmt->execute([':visit_id' => $visitId]);
        return $this->filterRows($stmt->fetchAll(PDO::FETCH_ASSOC), $user);
    }

    public function listByPatient(int $patientId, ?array $user = null): array
    {
        if ($patientId <= 0) {
            return [];
        }
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE er.patient_id = :patient_id ORDER BY er.created_at DESC, er.id DESC');
        $stmt->execute([':patient_id' => $patientId]);
        return $this->filterRows($stmt->fetchAll(PDO::FETCH_ASSOC), $user);
    }

    public function listWorklist(?array $user = null, array $filters = []): array
    {
        if ($user !== null && !$this->permissionService->canViewEcgWorklist($user)) {
            return [];
        }
        $status = $this->normalizeWorklistStatus((string)($filters['status'] ?? ''));
        $params = [];
        $where = '';
        if ($status === '') {
            $where = " WHERE er.status IN ('Requested','In Progress')";
        } elseif ($status !== 'All') {
            $where = ' WHERE er.status = :status';
            $params[':status'] = $status;
        }
        $stmt = $this->pdo->prepare($this->baseSelect() . $where . ' ORDER BY er.created_at DESC, er.id DESC');
        $stmt->execute($params);
        return $this->filterRows($stmt->fetchAll(PDO::FETCH_ASSOC), $user);
    }

    public function startRequest(int $requestId, array $user): array
    {
        return $this->transitionRequest($requestId, $user, 'In Progress', 'ECG_REQUEST_STARTED', 'Started ECG request #' . $requestId . '.');
    }

    public function saveReport(array $data, array $user, ?array $file = null): array
    {
        return $this->saveOrUpdateReport($data, $user, $file, false, 'upload_ecg_chart');
    }

    public function updateReport(array $data, array $user, ?array $file = null): array
    {
        return $this->saveOrUpdateReport($data, $user, $file, true, 'edit_ecg_report');
    }

    public function getReport(int $requestId, ?array $user = null): ?array
    {
        return $this->getRequestById($requestId, $user);
    }

    public function prepareChartDownload(int $requestId, array $user): array
    {
        $request = $this->getRequestById($requestId, $user);
        if (!$request || empty($request['chart_stored_path'])) {
            return $this->failure(['ECG chart not found.']);
        }
        $path = (string)$request['chart_stored_path'];
        if (!is_file($path)) {
            return $this->failure(['ECG chart file is missing.']);
        }
        $this->audit('ECG_CHART_DOWNLOADED', $request, $user, 'Downloaded ECG chart for request #' . $requestId . '.');
        return [
            'success' => true,
            'path' => $path,
            'filename' => (string)($request['chart_original_name'] ?? ('ecg-chart-' . $requestId)),
            'mime_type' => (string)($request['chart_mime_type'] ?? 'application/octet-stream'),
            'errors' => [],
        ];
    }

    public function completeRequest(int $requestId, array $user): array
    {
        try {
            $this->pdo->beginTransaction();
            $request = $this->lockRequest($requestId);
            if (!$request) {
                $this->rollback();
                return $this->failure(['ECG request not found.']);
            }
            $visit = $this->lockVisit((int)$request['visit_id']);
            $errors = $this->validateProcessing($request, $visit, $user, 'complete_ecg_request');
            $report = $this->lockReportByRequest($requestId);
            if (!$report || trim((string)($report['chart_stored_path'] ?? '')) === '') {
                $errors[] = 'Upload the scanned ECG chart before completing the request.';
            }
            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }
            $this->transitionRequestRecord($requestId, 'Completed');
            $stmt = $this->pdo->prepare('UPDATE ecg_reports SET completed_by = COALESCE(completed_by, :user_id), completed_at = COALESCE(completed_at, NOW()), updated_at = NOW() WHERE ecg_request_id = :id');
            $stmt->execute([':user_id' => (int)$user['id'], ':id' => $requestId]);
            $this->audit('ECG_REQUEST_COMPLETED', $visit, $user, 'Completed ECG request #' . $requestId . '.');
            $this->event((int)$visit['id'], 'ECG_COMPLETED', 'ECG Completed', 'ECG request completed.', $visit, $user);
            $this->pdo->commit();
            return ['success' => true, 'ecg_request_id' => $requestId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to complete ECG request.']);
        }
    }

    public function cancelRequest(int $requestId, array $user): array
    {
        return $this->transitionRequest($requestId, $user, 'Cancelled', 'ECG_REQUEST_CANCELLED', 'Cancelled ECG request #' . $requestId . '.');
    }

    private function saveOrUpdateReport(array $data, array $user, ?array $file, bool $mustExist, string $permissionKey): array
    {
        try {
            $this->pdo->beginTransaction();
            $requestId = (int)($data['ecg_request_id'] ?? $data['request_id'] ?? 0);
            $request = $this->lockRequest($requestId);
            if (!$request) {
                $this->rollback();
                return $this->failure(['ECG request not found.']);
            }
            $visit = $this->lockVisit((int)$request['visit_id']);
            $errors = $this->validateProcessing($request, $visit, $user, $permissionKey);
            $notes = $this->nullableText($data['notes'] ?? null);
            $remarks = $this->nullableText($data['remarks'] ?? null);
            $existing = $this->lockReportByRequest($requestId);
            if ($mustExist && !$existing) {
                $errors[] = 'ECG report not found.';
            }
            $upload = $this->prepareUpload($file, $errors, !$existing);
            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            if ($existing) {
                $stmt = $this->pdo->prepare('
                    UPDATE ecg_reports
                    SET notes = :notes,
                        remarks = :remarks,
                        chart_original_name = COALESCE(:chart_original_name, chart_original_name),
                        chart_stored_path = COALESCE(:chart_stored_path, chart_stored_path),
                        chart_mime_type = COALESCE(:chart_mime_type, chart_mime_type),
                        chart_file_size = COALESCE(:chart_file_size, chart_file_size),
                        updated_by = :updated_by,
                        updated_at = NOW()
                    WHERE ecg_request_id = :request_id
                ');
                $stmt->execute([
                    ':notes' => $notes,
                    ':remarks' => $remarks,
                    ':chart_original_name' => $upload['original_name'] ?? null,
                    ':chart_stored_path' => $upload['stored_path'] ?? null,
                    ':chart_mime_type' => $upload['mime_type'] ?? null,
                    ':chart_file_size' => $upload['file_size'] ?? null,
                    ':updated_by' => (int)$user['id'],
                    ':request_id' => $requestId,
                ]);
                $this->audit('ECG_REPORT_UPDATED', $visit, $user, 'Updated ECG report for request #' . $requestId . '.');
            } else {
                $stmt = $this->pdo->prepare('
                    INSERT INTO ecg_reports (
                        ecg_request_id, visit_id, patient_id, chart_original_name,
                        chart_stored_path, chart_mime_type, chart_file_size,
                        notes, remarks, performed_by, created_at, updated_at
                    ) VALUES (
                        :request_id, :visit_id, :patient_id, :chart_original_name,
                        :chart_stored_path, :chart_mime_type, :chart_file_size,
                        :notes, :remarks, :performed_by, NOW(), NOW()
                    )
                ');
                $stmt->execute([
                    ':request_id' => $requestId,
                    ':visit_id' => (int)$visit['id'],
                    ':patient_id' => (int)$visit['patient_id'],
                    ':chart_original_name' => $upload['original_name'] ?? null,
                    ':chart_stored_path' => $upload['stored_path'] ?? null,
                    ':chart_mime_type' => $upload['mime_type'] ?? null,
                    ':chart_file_size' => $upload['file_size'] ?? null,
                    ':notes' => $notes,
                    ':remarks' => $remarks,
                    ':performed_by' => (int)$user['id'],
                ]);
                if ((string)$request['status'] === 'Requested') {
                    $this->transitionRequestRecord($requestId, 'In Progress');
                }
                $this->audit('ECG_REPORT_CREATED', $visit, $user, 'Created ECG report for request #' . $requestId . '.');
            }
            $this->pdo->commit();
            return ['success' => true, 'ecg_request_id' => $requestId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to save ECG report.']);
        }
    }

    private function prepareUpload(?array $file, array &$errors, bool $required): array
    {
        if (!$file || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                $errors[] = 'Scanned ECG chart is required.';
            }
            return [];
        }
        if ((int)$file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'The ECG chart could not be uploaded.';
            return [];
        }
        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > 10 * 1024 * 1024) {
            $errors[] = 'ECG chart must be between 1 byte and 10 MB.';
            return [];
        }
        $tmp = (string)($file['tmp_name'] ?? '');
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmp);
        $allowed = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($allowed[$mime])) {
            $errors[] = 'ECG chart must be a PDF, JPG, or PNG file.';
            return [];
        }
        if (!is_dir($this->storageRoot) && !mkdir($this->storageRoot, 0775, true)) {
            $errors[] = 'ECG chart storage is not available.';
            return [];
        }
        $storedName = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        $storedPath = $this->storageRoot . DIRECTORY_SEPARATOR . $storedName;
        $stored = move_uploaded_file($tmp, $storedPath);
        if (!$stored && PHP_SAPI === 'cli' && is_file($tmp)) {
            $stored = rename($tmp, $storedPath);
        }
        if (!$stored) {
            $errors[] = 'Unable to store the ECG chart securely.';
            return [];
        }
        return [
            'original_name' => basename((string)($file['name'] ?? 'ecg-chart.' . $allowed[$mime])),
            'stored_path' => $storedPath,
            'mime_type' => $mime,
            'file_size' => $size,
        ];
    }

    private function validateRequest(array $visit, array $user, string $source, string $priority, string $study, ?string $indication): array
    {
        $errors = [];
        if (!$this->permissionService->canViewEncounter($visit, $user)) {
            $errors[] = 'You cannot access this encounter.';
        }
        if (in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true) && !$this->permissionService->isAdministrator($user)) {
            $errors[] = 'Completed or cancelled encounters cannot receive ECG mutations.';
        }
        if ($source === 'Clinical' && !$this->permissionService->canCreateEcgRequest($visit, $user, 'Clinical')) {
            $errors[] = 'You cannot create a clinical ECG request.';
        }
        if ($source === 'Direct') {
            if (!$this->permissionService->canCreateEcgRequest($visit, $user, 'Direct')) {
                $errors[] = 'You cannot create a direct ECG request.';
            }
            if (!$this->isEcgEncounter($visit)) {
                $errors[] = 'Direct ECG requests require an active ECG encounter.';
            }
        }
        if ($study === '') {
            $errors[] = 'Study requested is required.';
        }
        if (!in_array($priority, ['Routine', 'Urgent'], true)) {
            $errors[] = 'Priority is invalid.';
        }
        if ($this->textLength($study) > 255 || ($indication !== null && $this->textLength($indication) > 5000)) {
            $errors[] = 'ECG request text is too long.';
        }
        return $errors;
    }

    private function validateProcessing(array $request, array $visit, array $user, string $permissionKey): array
    {
        $errors = [];
        $allowed = match ($permissionKey) {
            'process_ecg_request' => $this->permissionService->canProcessEcgRequest($visit, $user),
            'upload_ecg_chart' => $this->permissionService->canUploadEcgChart($visit, $user),
            'edit_ecg_report' => $this->permissionService->canEditEcgReport($visit, $user),
            'complete_ecg_request' => $this->permissionService->canCompleteEcgRequest($visit, $user),
            default => false,
        };
        if (!$allowed) {
            $errors[] = 'You cannot perform this ECG action.';
        }
        if ((string)$request['status'] === 'Cancelled') {
            $errors[] = 'Cancelled ECG requests cannot be modified.';
        }
        if ((string)$request['status'] === 'Completed') {
            $errors[] = 'Completed ECG requests are view-only.';
        }
        if (in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true) && !$this->permissionService->isAdministrator($user)) {
            $errors[] = 'Completed or cancelled encounters cannot receive ECG mutations.';
        }
        return $errors;
    }

    private function transitionRequest(int $requestId, array $user, string $status, string $action, string $description): array
    {
        try {
            $this->pdo->beginTransaction();
            $request = $this->lockRequest($requestId);
            if (!$request) {
                $this->rollback();
                return $this->failure(['ECG request not found.']);
            }
            $visit = $this->lockVisit((int)$request['visit_id']);
            $errors = $this->validateProcessing($request, $visit, $user, 'process_ecg_request');
            if ($status === 'In Progress' && (string)$request['status'] !== 'Requested') {
                $errors[] = 'Only requested ECG requests can be started.';
            }
            if ($status === 'Cancelled' && !in_array((string)$request['status'], ['Requested', 'In Progress'], true)) {
                $errors[] = 'Only active ECG requests can be cancelled.';
            }
            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }
            $this->transitionRequestRecord($requestId, $status);
            $this->audit($action, $visit, $user, $description);
            $this->pdo->commit();
            return ['success' => true, 'ecg_request_id' => $requestId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to update ECG request.']);
        }
    }

    private function transitionRequestRecord(int $requestId, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE ecg_requests SET status = :status, updated_at = NOW(), completed_at = CASE WHEN :status_completed = "Completed" THEN NOW() ELSE completed_at END WHERE id = :id');
        $stmt->execute([':status' => $status, ':status_completed' => $status, ':id' => $requestId]);
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

    private function lockRequest(int $requestId): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE er.id = :id LIMIT 1 FOR UPDATE');
        $stmt->execute([':id' => $requestId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function lockReportByRequest(int $requestId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ecg_reports WHERE ecg_request_id = :id FOR UPDATE');
        $stmt->execute([':id' => $requestId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function canViewRow(array $row, array $user): bool
    {
        return $this->permissionService->isAdministrator($user)
            || $this->permissionService->canViewEcg((int)($row['patient_id'] ?? 0), $user);
    }

    private function filterRows(array $rows, ?array $user): array
    {
        $rows = $user === null ? $rows : array_filter($rows, fn (array $row): bool => $this->canViewRow($row, $user));
        return array_map([$this, 'decorateRow'], array_values($rows));
    }

    private function decorateRow(array $row): array
    {
        $row['result_status'] = !empty($row['report_id'])
            ? ((string)($row['report_completed_at'] ?? '') !== '' ? 'Completed' : 'Recorded')
            : 'Pending';
        return $row;
    }

    private function baseSelect(): string
    {
        return '
            SELECT er.*,
                   p.hospital_number,
                   CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                   v.visit_number,
                   v.visit_status,
                   d.department_name,
                   CONCAT(req.first_name, " ", req.last_name) AS requested_by_name,
                   rep.id AS report_id,
                   rep.chart_original_name,
                   rep.chart_stored_path,
                   rep.chart_mime_type,
                   rep.chart_file_size,
                   rep.notes,
                   rep.remarks,
                   rep.performed_by AS report_performed_by,
                   rep.updated_by AS report_updated_by,
                   rep.completed_by AS report_completed_by,
                   rep.created_at AS report_created_at,
                   rep.updated_at AS report_updated_at,
                   rep.completed_at AS report_completed_at,
                   CONCAT(performed.first_name, " ", performed.last_name) AS performed_by_name,
                   CONCAT(completed.first_name, " ", completed.last_name) AS completed_by_name
            FROM ecg_requests er
            INNER JOIN visits v ON v.id = er.visit_id
            INNER JOIN patients p ON p.id = er.patient_id
            LEFT JOIN departments d ON d.id = er.department_id
            LEFT JOIN users req ON req.id = er.requested_by
            LEFT JOIN ecg_reports rep ON rep.ecg_request_id = er.id
            LEFT JOIN users performed ON performed.id = rep.performed_by
            LEFT JOIN users completed ON completed.id = rep.completed_by
        ';
    }

    private function resolveEcgDepartmentId(): ?int
    {
        $id = $this->pdo->query("SELECT id FROM departments WHERE department_name = 'ECG' LIMIT 1")->fetchColumn();
        return $id !== false ? (int)$id : null;
    }

    private function isEcgEncounter(array $visit): bool
    {
        return (string)($visit['visit_status'] ?? '') === 'ECG'
            || (string)($visit['department_name'] ?? '') === 'ECG'
            || (int)($visit['current_department_id'] ?? 0) === (int)($this->resolveEcgDepartmentId() ?? 0);
    }

    private function normalizeSource(string $source): string
    {
        return in_array($source, ['Direct', 'Clinical'], true) ? $source : 'Clinical';
    }

    private function normalizePriority(string $priority): string
    {
        return in_array($priority, ['Routine', 'Urgent'], true) ? $priority : 'Routine';
    }

    private function normalizeWorklistStatus(string $status): string
    {
        return in_array($status, ['Requested', 'In Progress', 'Completed', 'Cancelled', 'All'], true) ? $status : '';
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string)$value);
        return $text === '' ? null : $text;
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? (int)mb_strlen($value) : strlen($value);
    }

    private function audit(string $action, array $visit, array $user, string $description): bool
    {
        return $this->auditService->logPatient(
            (int)($user['id'] ?? 0),
            (int)($visit['patient_id'] ?? 0),
            (int)($visit['id'] ?? 0),
            'ECG',
            $action,
            $description,
            (int)($visit['current_department_id'] ?? 0) ?: null,
            'INFO',
            $action
        );
    }

    private function event(int $visitId, string $type, string $title, string $description, array $visit, array $user): bool
    {
        $event = $this->eventService->record($visitId, $type, $title, $description, (int)($visit['current_department_id'] ?? 0) ?: null, (int)($user['id'] ?? 0) ?: null);
        return (bool)($event['success'] ?? false);
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
