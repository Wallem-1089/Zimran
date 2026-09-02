<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/EncounterEventService.php';
require_once __DIR__ . '/PermissionService.php';

class RadiologyService
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
                . DIRECTORY_SEPARATOR . 'radiology_charts';
    }

    public function createRequest(array $data, array $user): array
    {
        try {
            $this->pdo->beginTransaction();

            $visit = $this->lockVisit((int)($data['visit_id'] ?? 0));
            $source = $this->normalizeSource((string)($data['request_source'] ?? 'Clinical'));
            $priority = $this->normalizePriority((string)($data['priority'] ?? 'Routine'));
            $studyRequested = trim((string)($data['study_requested'] ?? $data['tests_requested'] ?? ''));
            $clinicalIndication = $this->nullableText($data['clinical_indication'] ?? $data['clinical_information'] ?? null);
            $errors = $this->validateRequest($visit, $user, $source, $priority, $studyRequested, $clinicalIndication);

            if (isset($data['patient_id']) && (int)$data['patient_id'] !== (int)$visit['patient_id']) {
                $errors[] = 'Patient and encounter do not match.';
            }

            $departmentId = $this->resolveRadiologyDepartmentId();
            if ($departmentId === null) {
                $errors[] = 'Radiology department is not available.';
            }

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO radiology_requests (
                    visit_id, patient_id, requested_by, department_id,
                    request_source, study_requested, clinical_indication,
                    priority, status, created_at, updated_at
                ) VALUES (
                    :visit_id, :patient_id, :requested_by, :department_id,
                    :request_source, :study_requested, :clinical_indication,
                    :priority, \'Requested\', NOW(), NOW()
                )
            ');
            $stmt->execute([
                ':visit_id' => (int)$visit['id'],
                ':patient_id' => (int)$visit['patient_id'],
                ':requested_by' => (int)$user['id'],
                ':department_id' => $departmentId,
                ':request_source' => $source,
                ':study_requested' => $studyRequested,
                ':clinical_indication' => $clinicalIndication,
                ':priority' => $priority,
            ]);
            $requestId = (int)$this->pdo->lastInsertId();

            if (!$this->audit(
                'RADIOLOGY_REQUEST_CREATED',
                $visit,
                $user,
                'Created radiology request #' . $requestId . '.'
            )) {
                throw new RuntimeException('Unable to audit radiology request creation.');
            }

            if (!$this->event(
                (int)$visit['id'],
                'RADIOLOGY_REQUESTED',
                'Radiology Request Created',
                'Radiology request created.',
                $visit,
                $user
            )) {
                throw new RuntimeException('Unable to record radiology request event.');
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'radiology_request_id' => $requestId,
                'visit_id' => (int)$visit['id'],
                'patient_id' => (int)$visit['patient_id'],
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to save radiology request.']);
        }
    }

    public function getRequestById(int $requestId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE lr.id = :id LIMIT 1');
        $stmt->execute([':id' => $requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        if ($user !== null && !$this->canViewRow($row, $user)) {
            return null;
        }

        return $this->decorateRow($row);
    }

    public function listByVisit(int $visitId, ?array $user = null): array
    {
        if ($visitId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE lr.visit_id = :visit_id ORDER BY lr.created_at DESC, lr.id DESC');
        $stmt->execute([':visit_id' => $visitId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->filterRows($rows, $user);
    }

    public function listByPatient(int $patientId, ?array $user = null): array
    {
        if ($patientId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE lr.patient_id = :patient_id ORDER BY lr.created_at DESC, lr.id DESC');
        $stmt->execute([':patient_id' => $patientId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->filterRows($rows, $user);
    }

    public function listWorklist(?array $user = null, array $filters = []): array
    {
        if ($user !== null && !$this->permissionService->canViewRadiologyWorklist($user)) {
            return [];
        }

        $status = $this->normalizeWorklistStatus((string)($filters['status'] ?? ''));
        $params = [];
        $where = '';

        if ($status === '') {
            $where = " WHERE lr.status IN ('Requested', 'In Progress')";
        } elseif ($status !== 'All') {
            $where = ' WHERE lr.status = :status';
            $params[':status'] = $status;
        }

        $stmt = $this->pdo->prepare($this->baseSelect() . $where . ' ORDER BY lr.created_at DESC, lr.id DESC');
        $stmt->execute($params);
        return $this->filterRows($stmt->fetchAll(PDO::FETCH_ASSOC), $user);
    }

    public function startRequest(int $requestId, array $user): array
    {
        return $this->transitionRequest($requestId, $user, 'In Progress', 'RADIOLOGY_REQUEST_STARTED', 'Started radiology request #' . $requestId . '.');
    }

    public function saveResult(array $data, array $user, ?array $file = null): array
    {
        return $this->saveOrUpdateResult($data, $user, $file, false, 'enter_radiology_report');
    }

    public function updateResult(array $data, array $user, ?array $file = null): array
    {
        return $this->saveOrUpdateResult($data, $user, $file, true, 'edit_radiology_report');
    }

    public function getResult(int $requestId, ?array $user = null): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT lr.*, lres.id AS result_id, lres.findings, lres.impression, lres.recommendation,
                   lres.chart_original_name, lres.chart_stored_path, lres.chart_mime_type, lres.chart_file_size,
                   lres.performed_by, lres.completed_by, lres.created_at AS result_created_at,
                   lres.updated_at AS result_updated_at, lres.completed_at AS result_completed_at,
                   CONCAT(performed.first_name, " ", performed.last_name) AS performed_by_name,
                   CONCAT(completed.first_name, " ", completed.last_name) AS completed_by_name
            FROM radiology_requests lr
            LEFT JOIN radiology_reports lres ON lres.radiology_request_id = lr.id
            LEFT JOIN users performed ON performed.id = lres.performed_by
            LEFT JOIN users completed ON completed.id = lres.completed_by
            WHERE lr.id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        if ($user !== null && !$this->canViewRow($row, $user)) {
            return null;
        }

        return $this->decorateRow($row);
    }

    public function prepareChartDownload(int $requestId, array $user): array
    {
        $request = $this->getResult($requestId, $user);
        if (!$request || empty($request['chart_stored_path'])) {
            return $this->failure(['Radiology chart/document not found.']);
        }

        $path = (string)$request['chart_stored_path'];
        if (!is_file($path)) {
            return $this->failure(['Radiology chart/document file is missing.']);
        }

        $this->audit(
            'RADIOLOGY_CHART_DOWNLOADED',
            $request,
            $user,
            'Downloaded radiology chart/document for request #' . $requestId . '.'
        );

        return [
            'success' => true,
            'path' => $path,
            'filename' => (string)($request['chart_original_name'] ?? ('radiology-document-' . $requestId)),
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
                return $this->failure(['Radiology request not found.']);
            }

            $visit = $this->lockVisit((int)$request['visit_id']);
            $errors = $this->validateProcessing($request, $visit, $user, 'complete_radiology_request');
            $result = $this->lockResultByRequest($requestId);

            if (!$result) {
                $errors[] = 'Enter a radiology result before completing the request.';
            }

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $this->transitionRequestRecord($requestId, 'Completed', $user['id']);
            $this->markResultCompleted($requestId, (int)$user['id']);

            if (!$this->audit(
                'RADIOLOGY_REQUEST_COMPLETED',
                $visit,
                $user,
                'Completed radiology request #' . $requestId . '.'
            )) {
                throw new RuntimeException('Unable to audit radiology completion.');
            }

            if (!$this->event(
                (int)$visit['id'],
                'RADIOLOGY_COMPLETED',
                'Radiology Request Completed',
                'Radiology request completed.',
                $visit,
                $user
            )) {
                throw new RuntimeException('Unable to record radiology completion event.');
            }

            $this->pdo->commit();

            return ['success' => true, 'radiology_request_id' => $requestId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to complete radiology request.']);
        }
    }

    public function cancelRequest(int $requestId, array $user): array
    {
        try {
            $this->pdo->beginTransaction();
            $request = $this->lockRequest($requestId);
            if (!$request) {
                $this->rollback();
                return $this->failure(['Radiology request not found.']);
            }

            $visit = $this->lockVisit((int)$request['visit_id']);
            $errors = $this->validateProcessing($request, $visit, $user, 'process_radiology_request');

            if ((string)($request['status'] ?? '') !== 'Requested' && (string)($request['status'] ?? '') !== 'In Progress') {
                $errors[] = 'Only active radiology requests can be cancelled.';
            }

            if ($this->lockResultByRequest($requestId) !== null) {
                $errors[] = 'Requests with results cannot be cancelled.';
            }

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $this->transitionRequestRecord($requestId, 'Cancelled', $user['id']);

            if (!$this->audit(
                'RADIOLOGY_REQUEST_CANCELLED',
                $visit,
                $user,
                'Cancelled radiology request #' . $requestId . '.'
            )) {
                throw new RuntimeException('Unable to audit radiology cancellation.');
            }

            $this->pdo->commit();
            return ['success' => true, 'radiology_request_id' => $requestId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to cancel radiology request.']);
        }
    }

    private function saveOrUpdateResult(array $data, array $user, ?array $file, bool $mustExist, string $permissionKey): array
    {
        try {
            $this->pdo->beginTransaction();

            $requestId = (int)($data['radiology_request_id'] ?? $data['request_id'] ?? 0);
            $request = $this->lockRequest($requestId);
            if (!$request) {
                $this->rollback();
                return $this->failure(['Radiology request not found.']);
            }

            $visit = $this->lockVisit((int)$request['visit_id']);
            $errors = $this->validateProcessing($request, $visit, $user, $permissionKey);
            $findings = $this->nullableText($data['findings'] ?? null);
            $impression = $this->nullableText($data['impression'] ?? null);
            $recommendation = $this->nullableText($data['recommendation'] ?? null);

            if ($findings !== null && $this->textLength($findings) > 10000) {
                $errors[] = 'Findings are too long.';
            }

            if ($impression === null || $impression === '') {
                $errors[] = 'Impression is required.';
            }

            if ($this->textLength((string)$impression) > 10000) {
                $errors[] = 'Impression is too long.';
            }

            if ($recommendation !== null && $this->textLength($recommendation) > 5000) {
                $errors[] = 'Recommendation is too long.';
            }

            $existing = $this->lockResultByRequest($requestId);
            if ($mustExist && !$existing) {
                $errors[] = 'Radiology report not found.';
            }

            $upload = $this->prepareUpload($file, $errors, false);

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            if ($existing) {
                $stmt = $this->pdo->prepare('
                    UPDATE radiology_reports
                    SET findings = :findings,
                        impression = :impression,
                        recommendation = :recommendation,
                        chart_original_name = COALESCE(:chart_original_name, chart_original_name),
                        chart_stored_path = COALESCE(:chart_stored_path, chart_stored_path),
                        chart_mime_type = COALESCE(:chart_mime_type, chart_mime_type),
                        chart_file_size = COALESCE(:chart_file_size, chart_file_size),
                        performed_by = :performed_by,
                        updated_at = NOW()
                    WHERE radiology_request_id = :radiology_request_id
                ');
                $stmt->execute([
                    ':findings' => $findings,
                    ':impression' => $impression,
                    ':recommendation' => $recommendation,
                    ':chart_original_name' => $upload['original_name'] ?? null,
                    ':chart_stored_path' => $upload['stored_path'] ?? null,
                    ':chart_mime_type' => $upload['mime_type'] ?? null,
                    ':chart_file_size' => $upload['file_size'] ?? null,
                    ':performed_by' => (int)$user['id'],
                    ':radiology_request_id' => $requestId,
                ]);

                if (!$this->audit(
                    'RADIOLOGY_REPORT_UPDATED',
                    $visit,
                    $user,
                    'Updated radiology report for request #' . $requestId . '.'
                )) {
                    throw new RuntimeException('Unable to audit radiology report update.');
                }
            } else {
                $stmt = $this->pdo->prepare('
                    INSERT INTO radiology_reports (
                        radiology_request_id, visit_id, patient_id,
                        findings, impression, recommendation,
                        chart_original_name, chart_stored_path, chart_mime_type, chart_file_size,
                        performed_by, created_at, updated_at
                    ) VALUES (
                        :radiology_request_id, :visit_id, :patient_id,
                        :findings, :impression, :recommendation,
                        :chart_original_name, :chart_stored_path, :chart_mime_type, :chart_file_size,
                        :performed_by, NOW(), NOW()
                    )
                ');
                $stmt->execute([
                    ':radiology_request_id' => $requestId,
                    ':visit_id' => (int)$visit['id'],
                    ':patient_id' => (int)$visit['patient_id'],
                    ':findings' => $findings,
                    ':impression' => $impression,
                    ':recommendation' => $recommendation,
                    ':chart_original_name' => $upload['original_name'] ?? null,
                    ':chart_stored_path' => $upload['stored_path'] ?? null,
                    ':chart_mime_type' => $upload['mime_type'] ?? null,
                    ':chart_file_size' => $upload['file_size'] ?? null,
                    ':performed_by' => (int)$user['id'],
                ]);

                if ((string)($request['status'] ?? '') === 'Requested') {
                    $this->transitionRequestRecord($requestId, 'In Progress', $user['id']);
                }

                if (!$this->audit(
                    'RADIOLOGY_REPORT_CREATED',
                    $visit,
                    $user,
                    'Created radiology report for request #' . $requestId . '.'
                )) {
                    throw new RuntimeException('Unable to audit radiology report creation.');
                }
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'radiology_request_id' => $requestId,
                'errors' => [],
            ];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to save radiology result.']);
        }
    }

    private function prepareUpload(?array $file, array &$errors, bool $required): array
    {
        if (!$file || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                $errors[] = 'Scanned X-Ray/Radiology document is required.';
            }
            return [];
        }

        if ((int)$file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'The X-Ray/Radiology document could not be uploaded.';
            return [];
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > 10 * 1024 * 1024) {
            $errors[] = 'X-Ray/Radiology document must be between 1 byte and 10 MB.';
            return [];
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmp);
        $allowed = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($allowed[$mime])) {
            $errors[] = 'X-Ray/Radiology document must be a PDF, JPG, or PNG file.';
            return [];
        }

        if (!is_dir($this->storageRoot) && !mkdir($this->storageRoot, 0775, true)) {
            $errors[] = 'X-Ray/Radiology document storage is not available.';
            return [];
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        $storedPath = $this->storageRoot . DIRECTORY_SEPARATOR . $storedName;
        $stored = move_uploaded_file($tmp, $storedPath);
        if (!$stored && PHP_SAPI === 'cli' && is_file($tmp)) {
            $stored = rename($tmp, $storedPath);
        }

        if (!$stored) {
            $errors[] = 'Unable to store the X-Ray/Radiology document securely.';
            return [];
        }

        return [
            'original_name' => basename((string)($file['name'] ?? 'radiology-document.' . $allowed[$mime])),
            'stored_path' => $storedPath,
            'mime_type' => $mime,
            'file_size' => $size,
        ];
    }

    private function validateRequest(
        array $visit,
        array $user,
        string $source,
        string $priority,
        string $studyRequested,
        ?string $clinicalIndication
    ): array {
        $errors = [];
        if (!$this->permissionService->canViewEncounter($visit, $user)) {
            $errors[] = 'You cannot access this encounter.';
        }

        if (in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)
            && !$this->permissionService->isAdministrator($user)) {
            $errors[] = 'Completed or cancelled encounters cannot receive radiology mutations.';
        }

        if ($source === 'Clinical') {
            if (!$this->permissionService->canCreateRadiologyRequest($visit, $user, 'Clinical')) {
                $errors[] = 'You cannot create a clinical radiology request.';
            }
        } elseif ($source === 'Direct') {
            if (!$this->permissionService->canCreateRadiologyRequest($visit, $user, 'Direct')) {
                $errors[] = 'You cannot create a direct radiology request.';
            }

            if (!$this->isRadiologyEncounter($visit)) {
                $errors[] = 'Direct radiology requests require an active Radiology encounter.';
            }
        } else {
            $errors[] = 'Invalid radiology request source.';
        }

        if ($studyRequested === '') {
            $errors[] = 'Study requested is required.';
        }

        if ($this->textLength($studyRequested) > 5000) {
            $errors[] = 'Study requested is too long.';
        }

        if ($clinicalIndication !== null && $this->textLength($clinicalIndication) > 5000) {
            $errors[] = 'Clinical indication is too long.';
        }

        if (!in_array($priority, ['Routine', 'Urgent'], true)) {
            $errors[] = 'Priority is invalid.';
        }

        return $errors;
    }

    private function validateProcessing(array $request, array $visit, array $user, string $permissionKey): array
    {
        $errors = [];
        if (!$this->permissionService->canViewRadiology((int)($visit['patient_id'] ?? 0), $user)) {
            $errors[] = 'You cannot access this encounter.';
        }

        if (in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)
            && !$this->permissionService->isAdministrator($user)) {
            $errors[] = 'Completed or cancelled encounters cannot receive radiology mutations.';
        }

        $allowed = match ($permissionKey) {
            'process_radiology_request' => $this->permissionService->canProcessRadiologyRequest($visit, $user),
            'enter_radiology_report' => $this->permissionService->canEnterRadiologyResult($visit, $user),
            'edit_radiology_report' => $this->permissionService->canEditRadiologyResult($visit, $user),
            'complete_radiology_request' => $this->permissionService->canCompleteRadiologyRequest($visit, $user),
            default => false,
        };

        if (!$allowed) {
            $errors[] = match ($permissionKey) {
                'process_radiology_request' => 'You cannot process this radiology request.',
                'enter_radiology_report' => 'You cannot enter this radiology result.',
                'edit_radiology_report' => 'You cannot edit this radiology result.',
                'complete_radiology_request' => 'You cannot complete this radiology request.',
                default => 'You cannot process this radiology request.',
            };
        }

        if ((string)($request['status'] ?? '') === 'Cancelled') {
            $errors[] = 'Cancelled radiology requests cannot be modified.';
        }

        if ((string)($request['status'] ?? '') === 'Completed') {
            $errors[] = 'Completed radiology requests are view-only.';
        }

        return $errors;
    }

    private function transitionRequest(int $requestId, array $user, string $newStatus, string $action, string $description): array
    {
        try {
            $this->pdo->beginTransaction();
            $request = $this->lockRequest($requestId);
            if (!$request) {
                $this->rollback();
                return $this->failure(['Radiology request not found.']);
            }

            $visit = $this->lockVisit((int)$request['visit_id']);
            $errors = $this->validateProcessing($request, $visit, $user, 'process_radiology_request');

            if ($newStatus === 'In Progress' && (string)($request['status'] ?? '') !== 'Requested') {
                $errors[] = 'Only requested radiology requests can be started.';
            }

            if ($newStatus === 'In Progress' && !$this->permissionService->canProcessRadiologyRequest($visit, $user)) {
                $errors[] = 'You cannot start this radiology request.';
            }

            if ($errors !== []) {
                $this->rollback();
                return $this->failure($errors);
            }

            $this->transitionRequestRecord($requestId, $newStatus, $user['id']);

            if (!$this->audit($action, $visit, $user, $description)) {
                throw new RuntimeException('Unable to audit radiology request transition.');
            }

            $this->pdo->commit();
            return ['success' => true, 'radiology_request_id' => $requestId, 'errors' => []];
        } catch (Throwable) {
            $this->rollback();
            return $this->failure(['Unable to update radiology request.']);
        }
    }

    private function transitionRequestRecord(int $requestId, string $status, int $actorId): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE radiology_requests
            SET status = :status,
                updated_at = NOW()
            WHERE id = :id
        ');
        $stmt->execute([
            ':status' => $status,
            ':id' => $requestId,
        ]);
    }

    private function markResultCompleted(int $requestId, int $completedBy): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE radiology_reports
            SET completed_by = COALESCE(completed_by, :completed_by),
                completed_at = COALESCE(completed_at, NOW()),
                updated_at = NOW()
            WHERE radiology_request_id = :radiology_request_id
        ');
        $stmt->execute([
            ':completed_by' => $completedBy,
            ':radiology_request_id' => $requestId,
        ]);
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
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE lr.id = :id LIMIT 1 FOR UPDATE');
        $stmt->execute([':id' => $requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function lockResultByRequest(int $requestId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM radiology_reports WHERE radiology_request_id = :id FOR UPDATE');
        $stmt->execute([':id' => $requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function canViewRow(array $row, array $user): bool
    {
        return $this->permissionService->canViewRadiology((int)($row['patient_id'] ?? 0), $user)
            || $this->permissionService->isAdministrator($user);
    }

    private function filterRows(array $rows, ?array $user): array
    {
        if ($user === null) {
            return array_map([$this, 'decorateRow'], $rows);
        }

        $rows = array_filter($rows, fn (array $row): bool => $this->canViewRow($row, $user));
        return array_map([$this, 'decorateRow'], array_values($rows));
    }

    private function decorateRow(array $row): array
    {
        $row['result_status'] = !empty($row['result_id'])
            ? ((string)($row['result_completed_at'] ?? '') !== '' ? 'Completed' : 'Recorded')
            : 'Pending';
        $row['summary'] = $this->summarize($row);
        return $row;
    }

    private function summarize(array $row): string
    {
        $study = trim((string)($row['study_requested'] ?? $row['tests_requested'] ?? ''));
        if ($study === '') {
            return 'Radiology request.';
        }

        return mb_strlen($study) > 180
            ? mb_substr($study, 0, 177) . '...'
            : $study;
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
        return in_array($status, ['Requested', 'In Progress', 'Completed', 'Cancelled', 'All'], true)
            ? $status
            : '';
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

    private function resolveRadiologyDepartmentId(): ?int
    {
        $stmt = $this->pdo->prepare("
            SELECT id
            FROM departments
            WHERE department_name IN ('Radiology', 'X-Ray')
            ORDER BY CASE WHEN department_name = 'Radiology' THEN 0 ELSE 1 END, id ASC
            LIMIT 1
        ");
        $stmt->execute();
        $id = $stmt->fetchColumn();
        return $id !== false ? (int)$id : null;
    }

    private function isRadiologyEncounter(array $visit): bool
    {
        $labDepartmentId = $this->resolveRadiologyDepartmentId();
        if ($labDepartmentId !== null && (int)($visit['current_department_id'] ?? 0) === $labDepartmentId) {
            return true;
        }

        return in_array((string)($visit['visit_status'] ?? ''), ['Radiology', 'X-Ray'], true);
    }

    private function audit(string $action, array $visit, array $user, string $description): bool
    {
        return $this->auditService->logPatient(
            (int)($user['id'] ?? 0),
            (int)$visit['patient_id'],
            (int)$visit['id'],
            'Radiology',
            $action,
            $description,
            (int)($visit['current_department_id'] ?? 0) ?: null,
            'INFO',
            $action
        );
    }

    private function event(
        int $visitId,
        string $type,
        string $title,
        string $description,
        array $visit,
        array $user
    ): bool {
        $event = $this->eventService->record(
            $visitId,
            $type,
            $title,
            $description,
            (int)($visit['current_department_id'] ?? 0) ?: null,
            (int)($user['id'] ?? 0) ?: null
        );
        return (bool)($event['success'] ?? false);
    }

    private function baseSelect(): string
    {
        return '
            SELECT lr.*,
                   p.hospital_number,
                   CONCAT(p.first_name, " ", p.last_name) AS patient_name,
                   v.visit_number,
                   v.visit_status,
                   d.department_name,
                   CONCAT(req.first_name, " ", req.last_name) AS requested_by_name,
                   CONCAT(resper.first_name, " ", resper.last_name) AS result_performed_by_name,
                   CONCAT(rescomp.first_name, " ", rescomp.last_name) AS result_completed_by_name,
                   lres.id AS result_id,
                   lres.findings,
                   lres.impression,
                   lres.recommendation,
                   lres.chart_original_name,
                   lres.chart_stored_path,
                   lres.chart_mime_type,
                   lres.chart_file_size,
                   lres.performed_by AS result_performed_by,
                   lres.completed_by AS result_completed_by,
                   lres.created_at AS result_created_at,
                   lres.updated_at AS result_updated_at,
                   lres.completed_at AS result_completed_at
            FROM radiology_requests lr
            INNER JOIN visits v ON v.id = lr.visit_id
            INNER JOIN patients p ON p.id = lr.patient_id
            LEFT JOIN departments d ON d.id = lr.department_id
            LEFT JOIN users req ON req.id = lr.requested_by
            LEFT JOIN radiology_reports lres ON lres.radiology_request_id = lr.id
            LEFT JOIN users resper ON resper.id = lres.performed_by
            LEFT JOIN users rescomp ON rescomp.id = lres.completed_by
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

