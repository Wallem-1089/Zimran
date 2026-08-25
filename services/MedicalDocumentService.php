<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/DocumentStorageInterface.php';
require_once __DIR__ . '/EncounterEventService.php';
require_once __DIR__ . '/PermissionService.php';
require_once __DIR__ . '/SecureLocalDocumentStorage.php';
require_once __DIR__ . '/SettingsService.php';

class MedicalDocumentService
{
    private const DOCUMENT_TYPES = [
        'referral_letter', 'identity_document', 'insurance_document',
        'consent_form', 'external_laboratory_result',
        'blood_card', 'blood_group_result', 'crossmatch_form',
        'transfusion_record',
        'operation_consent_form', 'theatre_operation_note',
        'continuation_sheet', 'observation_chart', 'nursing_checklist',
        'dressing_record', 'drug_chart', 'dm_sheet', 'emergency_record',
        'department_report_book',
        'external_radiology_report', 'discharge_document',
        'clinical_photograph', 'medical_certificate', 'correspondence', 'other'
    ];
    private const CONFIDENTIALITY_LEVELS = [
        'Standard', 'Restricted', 'Confidential', 'Highly Confidential'
    ];
    private const MIME_EXTENSIONS = [
        'application/pdf' => ['pdf'],
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'text/plain' => ['txt']
    ];
    private const DANGEROUS_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
        'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'bat', 'cmd', 'com', 'exe',
        'dll', 'scr', 'msi', 'js', 'mjs', 'html', 'htm', 'svg', 'xml', 'jar'
    ];

    private AuditService $auditService;
    private EncounterEventService $eventService;
    private SettingsService $settingsService;
    private PermissionService $permissionService;
    private DocumentStorageInterface $storage;

    public function __construct(
        private PDO $pdo,
        ?DocumentStorageInterface $storage = null,
        ?AuditService $auditService = null,
        ?EncounterEventService $eventService = null,
        ?SettingsService $settingsService = null,
        ?PermissionService $permissionService = null
    ) {
        $this->auditService = $auditService ?? new AuditService($pdo);
        $this->eventService = $eventService ?? new EncounterEventService($pdo);
        $this->settingsService = $settingsService ?? new SettingsService($pdo);
        $this->permissionService = $permissionService
            ?? new PermissionService($pdo, $this->settingsService);
        if ($storage !== null) {
            $this->storage = $storage;
        } else {
            $config = require __DIR__ . '/../config/app.php';
            $this->storage = new SecureLocalDocumentStorage(
                (string)$config['documents']['storage_root']
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Document Commands
    |--------------------------------------------------------------------------
    */

    public function uploadDocument(array $data, array $file, array $user): array
    {
        $prepared = $this->prepareMetadata($data);
        $actorId = (int)($user['id'] ?? 0);
        if (!$this->permissionService->canUploadMedicalDocuments(
            (int)$prepared['patient_id'],
            (string)$prepared['document_type'],
            $user
        )) {
            $this->auditDenied($actorId, (int)$prepared['patient_id'], 'DOCUMENT_ACCESS_DENIED');
            return $this->forbidden('You do not have permission to upload this document type.');
        }

        $metadataErrors = $this->validateMetadata($prepared, $actorId);
        $upload = $this->inspectUpload($file);
        $errors = array_merge($metadataErrors, $upload['errors']);
        if ($errors !== []) {
            $this->auditRejected($actorId, (int)$prepared['patient_id'], $prepared['visit_id']);
            $this->storage->deleteTemporaryFile((string)($file['tmp_name'] ?? ''));
            return $this->failure($errors);
        }

        $quarantined = $this->settingsService->getBoolean(
            'documents.malware_scanning_required',
            false
        );
        $storageKey = $this->generateStorageKey((string)$upload['data']['extension']);
        $stored = $this->storage->store(
            (string)$upload['data']['temporary_path'],
            $storageKey,
            $quarantined
        );
        if (!($stored['success'] ?? false)) {
            $this->auditRejected($actorId, (int)$prepared['patient_id'], $prepared['visit_id']);
            return $this->failure($stored['errors'] ?? ['Unable to store the uploaded document.']);
        }

        try {
            $this->pdo->beginTransaction();
            $this->lockPatient((int)$prepared['patient_id']);
            $visit = $this->lockVisitForPatient(
                $prepared['visit_id'],
                (int)$prepared['patient_id'],
                true
            );
            $this->assertEncounterAccess($visit, $user);
            if (!$this->permissionService->canUploadMedicalDocuments(
                (int)$prepared['patient_id'],
                (string)$prepared['document_type'],
                $user
            )) {
                throw new RuntimeException('Document upload authorization changed.');
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO medical_documents (
                    patient_id,visit_id,document_type,title,description,
                    department_id,confidentiality_level,document_status,
                    current_version,uploaded_by,version
                ) VALUES (
                    :patient_id,:visit_id,:document_type,:title,:description,
                    :department_id,:confidentiality_level,\'Active\',1,
                    :uploaded_by,1
                )
            ');
            $stmt->execute([
                ':patient_id' => $prepared['patient_id'],
                ':visit_id' => $prepared['visit_id'],
                ':document_type' => $prepared['document_type'],
                ':title' => $prepared['title'],
                ':description' => $prepared['description'],
                ':department_id' => $this->departmentId($user),
                ':confidentiality_level' => $prepared['confidentiality_level'],
                ':uploaded_by' => $actorId
            ]);
            $documentId = (int)$this->pdo->lastInsertId();
            $versionId = $this->insertFileVersion(
                $documentId,
                1,
                $storageKey,
                $upload['data'],
                $actorId,
                $quarantined,
                null,
                null
            );
            $this->audit(
                $actorId,
                (int)$prepared['patient_id'],
                $prepared['visit_id'],
                'MEDICAL_DOCUMENT_UPLOADED',
                'Uploaded Medical Document #' . $documentId . ' ('
                    . $prepared['document_type'] . ').'
            );
            $this->encounterEvent(
                $visit,
                'MEDICAL_DOCUMENT_UPLOADED',
                'Medical Document Uploaded',
                'A document was attached to this encounter.',
                $actorId
            );
            $this->pdo->commit();

            return $this->success([
                'document_id' => $documentId,
                'document_version_id' => $versionId,
                'patient_id' => (int)$prepared['patient_id'],
                'visit_id' => $prepared['visit_id'],
                'upload_status' => $quarantined ? 'Quarantined' : 'Available'
            ]);
        } catch (Throwable $exception) {
            $this->rollback();
            $this->storage->remove($storageKey, $quarantined);
            return $this->failure([$this->safeWriteError(
                $exception,
                'Unable to save the Medical Document.'
            )]);
        }
    }

    public function replaceDocument(
        int $documentId,
        array $data,
        array $file,
        array $user,
        int $expectedVersion
    ): array {
        $document = $this->getDocumentInternal($documentId);
        if (!$document) {
            return $this->failure(['Document not found.']);
        }
        if (!$this->permissionService->canReplaceMedicalDocuments(
            (int)$document['patient_id'],
            $user
        )) {
            $this->auditDenied((int)($user['id'] ?? 0), (int)$document['patient_id'], 'DOCUMENT_ACCESS_DENIED');
            return $this->forbidden('You do not have permission to replace this document.');
        }
        $reason = trim((string)($data['replacement_reason'] ?? ''));
        if ($reason === '') {
            return $this->failure(['A replacement reason is required.']);
        }
        $upload = $this->inspectUpload($file);
        if ($upload['errors'] !== []) {
            $this->auditRejected((int)$user['id'], (int)$document['patient_id'], $this->nullableInt($document['visit_id']));
            $this->storage->deleteTemporaryFile((string)($file['tmp_name'] ?? ''));
            return $this->failure($upload['errors']);
        }

        $quarantined = $this->settingsService->getBoolean('documents.malware_scanning_required', false);
        $storageKey = $this->generateStorageKey((string)$upload['data']['extension']);
        $stored = $this->storage->store((string)$upload['data']['temporary_path'], $storageKey, $quarantined);
        if (!($stored['success'] ?? false)) {
            return $this->failure($stored['errors'] ?? ['Unable to store the replacement document.']);
        }

        try {
            $this->pdo->beginTransaction();
            $current = $this->lockDocument($documentId);
            if (!$current) {
                throw new RuntimeException('Document not found.');
            }
            if ((int)$current['version'] !== $expectedVersion) {
                $this->rollback();
                $this->storage->remove($storageKey, $quarantined);
                return $this->conflict(
                    'The document changed. Reload and try again.',
                    (int)$current['version']
                );
            }
            if ((string)$current['document_status'] !== 'Active') {
                throw new RuntimeException('Only active documents can be replaced.');
            }
            $visit = $this->lockVisitForPatient(
                $this->nullableInt($current['visit_id']),
                (int)$current['patient_id'],
                true
            );
            $this->assertEncounterAccess($visit, $user);
            if (!$this->permissionService->canReplaceMedicalDocuments(
                (int)$current['patient_id'],
                $user
            )) {
                throw new RuntimeException('Document replacement authorization changed.');
            }
            $previous = $this->getVersionInternal(
                $documentId,
                (int)$current['current_version']
            );
            if (!$previous) {
                throw new RuntimeException('Current document version is unavailable.');
            }
            $nextVersion = (int)$current['current_version'] + 1;
            $versionId = $this->insertFileVersion(
                $documentId,
                $nextVersion,
                $storageKey,
                $upload['data'],
                (int)$user['id'],
                $quarantined,
                $reason,
                (int)$previous['id']
            );
            $stmt = $this->pdo->prepare('
                UPDATE medical_documents
                SET current_version=:current_version,version=version+1
                WHERE id=:id AND version=:expected_version
            ');
            $stmt->execute([
                ':current_version' => $nextVersion,
                ':id' => $documentId,
                ':expected_version' => $expectedVersion
            ]);
            $this->assertAffected($stmt, 'Concurrent document replacement detected.');
            $this->audit(
                (int)$user['id'],
                (int)$current['patient_id'],
                $this->nullableInt($current['visit_id']),
                'MEDICAL_DOCUMENT_REPLACED',
                'Replaced Medical Document #' . $documentId . ' with version '
                    . $nextVersion . '.'
            );
            $this->encounterEvent(
                $visit,
                'MEDICAL_DOCUMENT_REPLACED',
                'Medical Document Replaced',
                'A new document version was attached to this encounter.',
                (int)$user['id']
            );
            $this->pdo->commit();

            return $this->success([
                'document_id' => $documentId,
                'document_version_id' => $versionId,
                'version_number' => $nextVersion,
                'document_version' => $expectedVersion + 1,
                'upload_status' => $quarantined ? 'Quarantined' : 'Available'
            ]);
        } catch (Throwable $exception) {
            $this->rollback();
            $this->storage->remove($storageKey, $quarantined);
            return $this->failure([$this->safeWriteError(
                $exception,
                'Unable to replace the Medical Document.'
            )]);
        }
    }

    public function archiveDocument(
        int $documentId,
        string $reason,
        array $user,
        int $expectedVersion
    ): array {
        return $this->transitionDocument(
            $documentId,
            'archive',
            $reason,
            $user,
            $expectedVersion
        );
    }

    public function restoreDocument(
        int $documentId,
        string $reason,
        array $user,
        int $expectedVersion
    ): array {
        return $this->transitionDocument(
            $documentId,
            'restore',
            $reason,
            $user,
            $expectedVersion
        );
    }

    public function markDocumentEnteredInError(
        int $documentId,
        string $reason,
        array $user,
        int $expectedVersion
    ): array {
        return $this->transitionDocument(
            $documentId,
            'entered_error',
            $reason,
            $user,
            $expectedVersion
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Authorized Queries and Downloads
    |--------------------------------------------------------------------------
    */

    public function getDocumentById(int $documentId): ?array
    {
        $document = $this->getDocumentInternal($documentId);
        return $document ? $this->minimumDocument($document) : null;
    }

    public function getDocumentByIdForUser(
        int $documentId,
        array $user,
        bool $auditAccess = true
    ): array {
        $document = $this->getDocumentInternal($documentId);
        if (!$document) {
            return $this->failure(['Document not found.']);
        }
        $patientId = (int)$document['patient_id'];
        if (!$this->permissionService->canViewMedicalDocuments($patientId, $user)) {
            $this->auditDenied((int)($user['id'] ?? 0), $patientId, 'DOCUMENT_ACCESS_DENIED');
            return $this->forbidden('You do not have permission to view this document.');
        }
        if (!$this->hasDocumentEncounterAccess($document, $user)) {
            $this->auditDenied((int)($user['id'] ?? 0), $patientId, 'DOCUMENT_ACCESS_DENIED');
            return $this->forbidden('You do not have permission to view this encounter document.');
        }
        $canViewConfidential = $this->permissionService->canViewConfidentialDocuments(
            $patientId,
            $user
        );
        if ($this->isConfidential($document) && !$canViewConfidential) {
            return $this->success([
                'document' => $this->protectDocument($document, false)
            ]);
        }
        if ($auditAccess && $this->isConfidential($document)) {
            $audit = $this->recordProtectedAccess(
                $document,
                $user,
                'CONFIDENTIAL_DOCUMENT_VIEWED',
                'VIEW'
            );
            if (!($audit['success'] ?? false)) {
                return $audit;
            }
        }
        return $this->success(['document' => $document]);
    }

    public function listPatientDocuments(
        int $patientId,
        array $user,
        bool $includeInactive = false
    ): array {
        if (!$this->permissionService->canViewMedicalDocuments($patientId, $user)) {
            return [];
        }
        $sql = $this->documentSelect() . ' WHERE d.patient_id=:patient_id';
        if (!$includeInactive) {
            $sql .= " AND d.document_status='Active'";
        }
        $sql .= ' ORDER BY d.created_at DESC,d.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':patient_id' => $patientId]);
        return array_map(
            fn (array $row): array => $this->minimumDocument($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function listEncounterDocuments(int $visitId, array $user): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM visits WHERE id=:id');
        $stmt->execute([':id' => $visitId]);
        $visit = $stmt->fetch(PDO::FETCH_ASSOC);
        $patientId = (int)($visit['patient_id'] ?? 0);
        if (!$visit
            || !$this->permissionService->canViewEncounter($visit, $user)
            || $patientId <= 0
            || !$this->permissionService->canViewMedicalDocuments($patientId, $user)
        ) {
            return [];
        }
        $stmt = $this->pdo->prepare(
            $this->documentSelect()
            . " WHERE d.visit_id=:visit_id AND d.document_status<>'Entered-in-error'"
            . ' ORDER BY d.created_at DESC,d.id DESC'
        );
        $stmt->execute([':visit_id' => $visitId]);
        return array_map(
            fn (array $row): array => $this->minimumDocument($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function getDocumentVersions(int $documentId, array $user): array
    {
        $document = $this->getDocumentInternal($documentId);
        if (!$document) {
            return $this->failure(['Document not found.']);
        }
        $patientId = (int)$document['patient_id'];
        if (!$this->permissionService->canViewDocumentHistory($patientId, $user)) {
            $this->auditDenied((int)($user['id'] ?? 0), $patientId, 'DOCUMENT_ACCESS_DENIED');
            return $this->forbidden('You do not have permission to view document versions.');
        }
        if (!$this->hasDocumentEncounterAccess($document, $user)) {
            $this->auditDenied((int)($user['id'] ?? 0), $patientId, 'DOCUMENT_ACCESS_DENIED');
            return $this->forbidden('You do not have permission to view this encounter document.');
        }
        $canViewConfidential = $this->permissionService->canViewConfidentialDocuments(
            $patientId,
            $user
        );
        $stmt = $this->pdo->prepare('
            SELECT v.id,v.document_id,v.version_number,v.original_filename,
                v.mime_type,v.file_extension,v.file_size,v.sha256_checksum,
                v.upload_status,v.malware_scan_status,v.uploaded_by,
                v.uploaded_at,v.replacement_reason,v.supersedes_version_id,
                CONCAT(u.first_name,\' \',u.last_name) uploader_name
            FROM medical_document_versions v
            INNER JOIN users u ON u.id=v.uploaded_by
            WHERE v.document_id=:document_id
            ORDER BY v.version_number DESC
        ');
        $stmt->execute([':document_id' => $documentId]);
        $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($this->isConfidential($document) && !$canViewConfidential) {
            foreach ($versions as &$version) {
                $version['original_filename'] = 'Confidential document';
                $version['sha256_checksum'] = null;
                $version['replacement_reason'] = null;
                $version['confidential_hidden'] = true;
            }
            unset($version);
        }
        if ($this->isConfidential($document) && $canViewConfidential) {
            $audit = $this->recordProtectedAccess(
                $document,
                $user,
                'CONFIDENTIAL_DOCUMENT_VIEWED',
                'HISTORY'
            );
            if (!($audit['success'] ?? false)) {
                return $audit;
            }
        }
        return $this->success([
            'document' => $this->protectDocument($document, $canViewConfidential),
            'versions' => $versions
        ]);
    }

    public function getDocumentHistory(int $documentId, array $user): array
    {
        return $this->getDocumentVersions($documentId, $user);
    }

    public function prepareDownload(
        int $documentId,
        array $user,
        ?int $versionId = null
    ): array {
        $stream = null;
        try {
            $this->pdo->beginTransaction();
            $document = $this->lockDocument($documentId);
            if (!$document) {
                throw new RuntimeException('Document not found.');
            }
            $patientId = (int)$document['patient_id'];
            if (!$this->permissionService->canDownloadMedicalDocuments($patientId, $user)) {
                $this->rollback();
                $this->auditDenied((int)($user['id'] ?? 0), $patientId, 'DOCUMENT_ACCESS_DENIED');
                return $this->forbidden('You do not have permission to download this document.');
            }
            $this->assertDocumentEncounterAccess($document, $user);
            if ($this->isConfidential($document)
                && !$this->permissionService->canViewConfidentialDocuments($patientId, $user)
            ) {
                $this->rollback();
                $this->auditDenied((int)($user['id'] ?? 0), $patientId, 'DOCUMENT_ACCESS_DENIED');
                return $this->forbidden('Confidential document access is not permitted.');
            }
            if ((string)$document['document_status'] === 'Entered-in-error') {
                throw new RuntimeException('Entered-in-error documents cannot be downloaded.');
            }
            $version = $versionId === null
                ? $this->getVersionInternal($documentId, (int)$document['current_version'])
                : $this->getVersionByIdInternal($versionId);
            if (!$version || (int)$version['document_id'] !== $documentId) {
                throw new RuntimeException('Document version not found.');
            }
            if ((int)$version['version_number'] !== (int)$document['current_version']
                && !$this->permissionService->canViewDocumentHistory($patientId, $user)
            ) {
                $this->rollback();
                return $this->forbidden('Historical document download is not permitted.');
            }
            if ((string)$version['upload_status'] !== 'Available'
                || in_array((string)$version['malware_scan_status'], [
                    'Suspicious', 'Infected', 'Scan Failed'
                ], true)
                || ((string)$version['malware_scan_status'] === 'Not Scanned'
                    && $this->settingsService->getBoolean(
                        'documents.malware_scanning_required',
                        false
                    ))
            ) {
                throw new RuntimeException('This document version is not available for download.');
            }
            $metadata = $this->storage->getMetadata((string)$version['storage_key'], false);
            if (!($metadata['success'] ?? false)) {
                throw new RuntimeException('Stored document is unavailable.');
            }
            if ((int)$metadata['data']['size'] !== (int)$version['file_size']
                || !hash_equals(
                    (string)$version['sha256_checksum'],
                    (string)$metadata['data']['sha256']
                )
            ) {
                throw new RuntimeException('Stored document integrity verification failed.');
            }
            $stream = $this->storage->openStream((string)$version['storage_key'], false);
            if (!is_resource($stream)) {
                throw new RuntimeException('Stored document is unavailable.');
            }
            $logged = $this->recordDownloadInternal($document, $version, $user);
            if (!$logged) {
                throw new RuntimeException('Unable to record protected document access.');
            }
            $this->pdo->commit();

            return $this->success([
                'document_id' => $documentId,
                'document_version_id' => (int)$version['id'],
                'stream' => $stream,
                'filename' => (string)$version['original_filename'],
                'mime_type' => (string)$version['mime_type'],
                'file_size' => (int)$version['file_size'],
                'cache_control' => $this->getDownloadCachePolicy()
            ]);
        } catch (Throwable $exception) {
            $this->rollback();
            if (is_resource($stream)) {
                fclose($stream);
            }
            $failure = $this->failure([$this->safeWriteError(
                $exception,
                'The document could not be prepared for download.'
            )]);
            if ($exception->getMessage() === 'Unable to record protected document access.') {
                $failure['audit_failed'] = true;
            }
            return $failure;
        }
    }

    public function recordDownload(
        int $documentId,
        int $versionId,
        array $user
    ): array {
        try {
            $this->pdo->beginTransaction();
            $document = $this->lockDocument($documentId);
            $version = $this->getVersionByIdInternal($versionId);
            if (!$document || !$version || (int)$version['document_id'] !== $documentId) {
                throw new RuntimeException('Document version not found.');
            }
            if (!$this->permissionService->canDownloadMedicalDocuments(
                (int)$document['patient_id'],
                $user
            )) {
                $this->rollback();
                return $this->forbidden('Document download is not permitted.');
            }
            $this->assertDocumentEncounterAccess($document, $user);
            if ($this->isConfidential($document)
                && !$this->permissionService->canViewConfidentialDocuments(
                    (int)$document['patient_id'],
                    $user
                )
            ) {
                $this->rollback();
                return $this->forbidden('Confidential document access is not permitted.');
            }
            if ((string)$document['document_status'] === 'Entered-in-error'
                || (string)$version['upload_status'] !== 'Available'
                || in_array((string)$version['malware_scan_status'], [
                    'Suspicious', 'Infected', 'Scan Failed'
                ], true)
                || ((string)$version['malware_scan_status'] === 'Not Scanned'
                    && $this->settingsService->getBoolean(
                        'documents.malware_scanning_required',
                        false
                    ))
            ) {
                throw new RuntimeException('Document version is not downloadable.');
            }
            if ((int)$version['version_number'] !== (int)$document['current_version']
                && !$this->permissionService->canViewDocumentHistory(
                    (int)$document['patient_id'],
                    $user
                )
            ) {
                $this->rollback();
                return $this->forbidden('Historical document download is not permitted.');
            }
            if (!$this->recordDownloadInternal($document, $version, $user)) {
                throw new RuntimeException('Unable to record protected document access.');
            }
            $this->pdo->commit();
            return $this->success(['document_id' => $documentId, 'version_id' => $versionId]);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Unable to record the document download.']);
        }
    }

    public function getDocumentSummary(int $patientId, array $user): array
    {
        $documents = $this->listPatientDocuments($patientId, $user, false);
        return $this->success([
            'documents' => $documents,
            'total' => count($documents),
            'encounter_linked' => count(array_filter(
                $documents,
                static fn (array $row): bool => !empty($row['visit_id'])
            ))
        ]);
    }

    public function getAllowedDocumentTypes(): array
    {
        return $this->settingSubset('documents.allowed_types', self::DOCUMENT_TYPES);
    }

    public function getAllowedConfidentialityLevels(): array
    {
        return $this->settingSubset(
            'documents.confidentiality_levels',
            self::CONFIDENTIALITY_LEVELS
        );
    }

    public function getMaximumUploadBytes(): int
    {
        return max(1024, min(
            41943040,
            $this->settingsService->getInteger(
                'documents.maximum_upload_bytes',
                10485760
            )
        ));
    }

    public function canAcceptEncounterUpload(array $visit): bool
    {
        return !in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)
            || $this->settingsService->getBoolean('documents.closed_encounter_uploads', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Internal Lifecycle and Persistence
    |--------------------------------------------------------------------------
    */

    private function transitionDocument(
        int $documentId,
        string $operation,
        string $reason,
        array $user,
        int $expectedVersion
    ): array {
        if (trim($reason) === '') {
            return $this->failure(['A lifecycle reason is required.']);
        }
        $document = $this->getDocumentInternal($documentId);
        if (!$document) {
            return $this->failure(['Document not found.']);
        }
        if (!$this->permissionService->canArchiveMedicalDocuments(
            (int)$document['patient_id'],
            $user
        )) {
            $this->auditDenied((int)($user['id'] ?? 0), (int)$document['patient_id'], 'DOCUMENT_ACCESS_DENIED');
            return $this->forbidden('You do not have permission to change document status.');
        }

        try {
            $this->pdo->beginTransaction();
            $current = $this->lockDocument($documentId);
            if (!$current) {
                throw new RuntimeException('Document not found.');
            }
            if ((int)$current['version'] !== $expectedVersion) {
                $this->rollback();
                return $this->conflict(
                    'The document changed. Reload and try again.',
                    (int)$current['version']
                );
            }
            $status = (string)$current['document_status'];
            if ($status === 'Entered-in-error') {
                throw new RuntimeException('Entered-in-error documents are terminal.');
            }
            $allowed = ($operation === 'archive' && $status === 'Active')
                || ($operation === 'restore' && $status === 'Archived')
                || ($operation === 'entered_error');
            if (!$allowed) {
                throw new RuntimeException('The requested document transition is not available.');
            }
            $visit = $this->lockVisitForPatient(
                $this->nullableInt($current['visit_id']),
                (int)$current['patient_id'],
                false
            );
            $this->assertEncounterAccess($visit, $user);
            $newVersion = $expectedVersion + 1;
            [$set, $auditAction, $eventTitle] = match ($operation) {
                'archive' => [
                    "document_status='Archived',archived_by=:actor_id,archived_at=NOW(),archive_reason=:reason",
                    'MEDICAL_DOCUMENT_ARCHIVED',
                    'Medical Document Archived'
                ],
                'restore' => [
                    "document_status='Active',archived_by=NULL,archived_at=NULL,archive_reason=NULL",
                    'MEDICAL_DOCUMENT_RESTORED',
                    null
                ],
                default => [
                    "document_status='Entered-in-error',archived_by=:actor_id,archived_at=NOW(),archive_reason=:reason",
                    'MEDICAL_DOCUMENT_ENTERED_IN_ERROR',
                    null
                ]
            };
            $sql = 'UPDATE medical_documents SET ' . $set
                . ',version=:version WHERE id=:id AND version=:expected_version';
            $params = [
                ':version' => $newVersion,
                ':id' => $documentId,
                ':expected_version' => $expectedVersion
            ];
            if ($operation !== 'restore') {
                $params[':actor_id'] = (int)$user['id'];
                $params[':reason'] = trim($reason);
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $this->assertAffected($stmt, 'Concurrent document transition detected.');
            $this->audit(
                (int)$user['id'],
                (int)$current['patient_id'],
                $this->nullableInt($current['visit_id']),
                $auditAction,
                str_replace('_', ' ', $auditAction) . ' for document #'
                    . $documentId . '.'
            );
            if ($eventTitle !== null) {
                $this->encounterEvent(
                    $visit,
                    $auditAction,
                    $eventTitle,
                    'An encounter-linked document status changed.',
                    (int)$user['id']
                );
            }
            $this->pdo->commit();
            return $this->success([
                'document_id' => $documentId,
                'document_status' => match ($operation) {
                    'archive' => 'Archived',
                    'restore' => 'Active',
                    default => 'Entered-in-error'
                },
                'version' => $newVersion
            ]);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure([$this->safeWriteError(
                $exception,
                'Unable to change document status.'
            )]);
        }
    }

    private function insertFileVersion(
        int $documentId,
        int $versionNumber,
        string $storageKey,
        array $upload,
        int $actorId,
        bool $quarantined,
        ?string $replacementReason,
        ?int $supersedesVersionId
    ): int {
        $stmt = $this->pdo->prepare('
            INSERT INTO medical_document_versions (
                document_id,version_number,storage_provider,storage_key,
                original_filename,stored_filename,mime_type,file_extension,
                file_size,sha256_checksum,upload_status,malware_scan_status,
                uploaded_by,replacement_reason,supersedes_version_id
            ) VALUES (
                :document_id,:version_number,\'local\',:storage_key,
                :original_filename,:stored_filename,:mime_type,:file_extension,
                :file_size,:sha256_checksum,:upload_status,\'Not Scanned\',
                :uploaded_by,:replacement_reason,:supersedes_version_id
            )
        ');
        $stmt->execute([
            ':document_id' => $documentId,
            ':version_number' => $versionNumber,
            ':storage_key' => $storageKey,
            ':original_filename' => $upload['original_filename'],
            ':stored_filename' => basename($storageKey),
            ':mime_type' => $upload['mime_type'],
            ':file_extension' => $upload['extension'],
            ':file_size' => $upload['file_size'],
            ':sha256_checksum' => $upload['sha256'],
            ':upload_status' => $quarantined ? 'Quarantined' : 'Available',
            ':uploaded_by' => $actorId,
            ':replacement_reason' => $replacementReason,
            ':supersedes_version_id' => $supersedesVersionId
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /*
    |--------------------------------------------------------------------------
    | Upload Validation
    |--------------------------------------------------------------------------
    */

    private function inspectUpload(array $file): array
    {
        $errors = [];
        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            $errors[] = $this->uploadErrorMessage($error);
            return ['data' => null, 'errors' => $errors];
        }
        $temporaryPath = (string)($file['tmp_name'] ?? '');
        if ($temporaryPath === '' || !is_file($temporaryPath) || !is_readable($temporaryPath)) {
            return ['data' => null, 'errors' => ['The uploaded temporary file is unavailable.']];
        }
        $original = (string)($file['name'] ?? '');
        if ($original === ''
            || strlen($original) > 255
            || str_contains($original, "\0")
            || str_contains($original, '/')
            || str_contains($original, '\\')
        ) {
            $errors[] = 'The original filename is invalid.';
        }
        $safeName = $this->sanitizeDisplayFilename($original);
        $segments = array_map('strtolower', explode('.', $safeName));
        $extension = count($segments) > 1 ? (string)end($segments) : '';
        foreach (array_slice($segments, 1, -1) as $segment) {
            if (in_array($segment, self::DANGEROUS_EXTENSIONS, true)) {
                $errors[] = 'Double-extension or executable filenames are not permitted.';
                break;
            }
        }
        $allowedExtensions = $this->settingSubset(
            'documents.allowed_extensions',
            array_values(array_unique(array_merge(...array_values(self::MIME_EXTENSIONS))))
        );
        if (!in_array($extension, $allowedExtensions, true)
            || in_array($extension, self::DANGEROUS_EXTENSIONS, true)
        ) {
            $errors[] = 'The file extension is not permitted.';
        }
        $size = (int)filesize($temporaryPath);
        if ($size <= 0) {
            $errors[] = 'Empty files are not permitted.';
        } elseif ($size > $this->getMaximumUploadBytes()) {
            $errors[] = 'The uploaded file exceeds the configured size limit.';
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($temporaryPath);
        $allowedMimes = $this->settingSubset(
            'documents.allowed_mime_types',
            array_keys(self::MIME_EXTENSIONS)
        );
        if (!isset(self::MIME_EXTENSIONS[$mime])
            || !in_array($mime, $allowedMimes, true)
            || !in_array($extension, self::MIME_EXTENSIONS[$mime] ?? [], true)
        ) {
            $errors[] = 'The detected file type does not match an approved extension.';
        }
        if ($mime === 'application/pdf') {
            $handle = @fopen($temporaryPath, 'rb');
            $header = is_resource($handle) ? (string)fread($handle, 5) : '';
            if (is_resource($handle)) {
                fclose($handle);
            }
            if ($header !== '%PDF-') {
                $errors[] = 'The PDF signature is invalid.';
            }
        }
        if (in_array($mime, ['image/jpeg', 'image/png'], true)) {
            $image = @getimagesize($temporaryPath);
            $expectedType = $mime === 'image/jpeg' ? IMAGETYPE_JPEG : IMAGETYPE_PNG;
            if ($image === false || (int)($image[2] ?? 0) !== $expectedType) {
                $errors[] = 'The image structure is invalid.';
            }
        }
        if ($mime === 'text/plain') {
            $sample = (string)file_get_contents($temporaryPath, false, null, 0, 8192);
            if (str_contains($sample, "\0")) {
                $errors[] = 'Binary content is not permitted as plain text.';
            }
        }
        if ($errors !== []) {
            return ['data' => null, 'errors' => array_values(array_unique($errors))];
        }
        return [
            'data' => [
                'temporary_path' => $temporaryPath,
                'original_filename' => $safeName,
                'mime_type' => $mime,
                'extension' => $extension,
                'file_size' => $size,
                'sha256' => (string)hash_file('sha256', $temporaryPath)
            ],
            'errors' => []
        ];
    }

    private function prepareMetadata(array $data): array
    {
        return [
            'patient_id' => (int)($data['patient_id'] ?? 0),
            'visit_id' => $this->nullableInt($data['visit_id'] ?? null),
            'document_type' => trim((string)($data['document_type'] ?? '')),
            'title' => trim((string)($data['title'] ?? '')),
            'description' => $this->nullableText($data['description'] ?? null),
            'confidentiality_level' => trim((string)($data['confidentiality_level']
                ?? $this->settingsService->getString(
                    'documents.default_confidentiality',
                    'Standard'
                )))
        ];
    }

    private function validateMetadata(array $data, int $actorId): array
    {
        $errors = [];
        if ($data['patient_id'] <= 0 || $actorId <= 0) {
            $errors[] = 'Patient and authenticated user are required.';
        }
        if (!in_array($data['document_type'], $this->getAllowedDocumentTypes(), true)) {
            $errors[] = 'Select an approved document type.';
        }
        if ($data['title'] === '' || mb_strlen($data['title']) > 200) {
            $errors[] = 'Title is required and must not exceed 200 characters.';
        }
        if ($data['description'] !== null && mb_strlen($data['description']) > 10000) {
            $errors[] = 'Description must not exceed 10,000 characters.';
        }
        if (!in_array(
            $data['confidentiality_level'],
            $this->getAllowedConfidentialityLevels(),
            true
        )) {
            $errors[] = 'Select an approved confidentiality level.';
        }
        return $errors;
    }

    /*
    |--------------------------------------------------------------------------
    | Database and Audit Helpers
    |--------------------------------------------------------------------------
    */

    private function documentSelect(): string
    {
        return '
            SELECT d.*,v.id current_version_id,v.original_filename,v.mime_type,
                v.file_extension,v.file_size,v.sha256_checksum,v.upload_status,
                v.malware_scan_status,v.uploaded_at,
                CONCAT(u.first_name,\' \',u.last_name) uploader_name,
                dep.department_name
            FROM medical_documents d
            INNER JOIN medical_document_versions v
                ON v.document_id=d.id AND v.version_number=d.current_version
            INNER JOIN users u ON u.id=d.uploaded_by
            LEFT JOIN departments dep ON dep.id=d.department_id
        ';
    }

    private function getDocumentInternal(int $documentId): ?array
    {
        $stmt = $this->pdo->prepare(
            $this->documentSelect() . ' WHERE d.id=:id LIMIT 1'
        );
        $stmt->execute([':id' => $documentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function lockDocument(int $documentId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM medical_documents WHERE id=:id FOR UPDATE');
        $stmt->execute([':id' => $documentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function getVersionInternal(int $documentId, int $versionNumber): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM medical_document_versions WHERE document_id=:document_id AND version_number=:version_number');
        $stmt->execute([':document_id' => $documentId, ':version_number' => $versionNumber]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function getVersionByIdInternal(int $versionId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM medical_document_versions WHERE id=:id');
        $stmt->execute([':id' => $versionId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function lockPatient(int $patientId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM patients WHERE id=:id FOR UPDATE');
        $stmt->execute([':id' => $patientId]);
        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('Patient not found.');
        }
    }

    private function lockVisitForPatient(
        ?int $visitId,
        int $patientId,
        bool $mutation
    ): ?array {
        if ($visitId === null) {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT * FROM visits WHERE id=:id FOR UPDATE');
        $stmt->execute([':id' => $visitId]);
        $visit = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$visit || (int)$visit['patient_id'] !== $patientId) {
            throw new RuntimeException('Encounter does not belong to this patient.');
        }
        if ($mutation
            && in_array((string)$visit['visit_status'], ['Completed', 'Cancelled'], true)
            && !$this->settingsService->getBoolean(
                'documents.closed_encounter_uploads',
                false
            )
        ) {
            throw new RuntimeException('Closed encounters do not accept new document attachments.');
        }
        return $visit;
    }

    private function assertEncounterAccess(?array $visit, array $user): void
    {
        if ($visit !== null
            && !$this->permissionService->canViewEncounter($visit, $user)
            && !$this->permissionService->isAdministrator($user)
            && (string)($user['role_name'] ?? '') !== 'Records Officer'
            && (string)($user['department_name'] ?? '') !== 'Records'
        ) {
            throw new RuntimeException('Encounter access is not permitted.');
        }
    }

    private function assertDocumentEncounterAccess(array $document, array $user): void
    {
        if (!$this->hasDocumentEncounterAccess($document, $user)) {
            throw new RuntimeException('Encounter access is not permitted.');
        }
    }

    private function hasDocumentEncounterAccess(array $document, array $user): bool
    {
        $visitId = $this->nullableInt($document['visit_id'] ?? null);
        if ($visitId === null) {
            return true;
        }
        $stmt = $this->pdo->prepare('SELECT * FROM visits WHERE id=:id');
        $stmt->execute([':id' => $visitId]);
        $visit = $stmt->fetch(PDO::FETCH_ASSOC);
        return $visit
            && (int)$visit['patient_id'] === (int)$document['patient_id']
            && (
                $this->permissionService->canViewEncounter($visit, $user)
                || $this->permissionService->isAdministrator($user)
                || (string)($user['role_name'] ?? '') === 'Records Officer'
                || (string)($user['department_name'] ?? '') === 'Records'
            );
    }

    private function audit(
        int $actorId,
        int $patientId,
        ?int $visitId,
        string $action,
        string $description
    ): void {
        if (!$this->auditService->logPatient(
            $actorId,
            $patientId,
            $visitId,
            'Medical Records',
            $action,
            $description,
            null,
            'INFO',
            $action
        )) {
            throw new RuntimeException('Unable to write document audit log.');
        }
    }

    private function recordDownloadInternal(array $document, array $version, array $user): bool
    {
        $actorId = (int)($user['id'] ?? 0);
        $patientId = (int)$document['patient_id'];
        $visitId = $this->nullableInt($document['visit_id']);
        if (!$this->auditService->logPatient(
            $actorId,
            $patientId,
            $visitId,
            'Medical Records',
            'MEDICAL_DOCUMENT_DOWNLOADED',
            'Downloaded Medical Document #' . (int)$document['id']
                . ' version ' . (int)$version['version_number'] . '.',
            $this->departmentId($user),
            'INFO',
            'MEDICAL_DOCUMENT_DOWNLOADED'
        )) {
            return false;
        }
        if ($this->isConfidential($document)
            && !$this->auditService->logPatient(
                $actorId,
                $patientId,
                $visitId,
                'Medical Records',
                'CONFIDENTIAL_DOCUMENT_VIEWED',
                'Accessed confidential Medical Document #' . (int)$document['id'] . '.',
                $this->departmentId($user),
                'WARNING',
                'CONFIDENTIAL_DOCUMENT_VIEWED'
            )
        ) {
            return false;
        }
        return $this->auditService->logPatientAccess(
            $actorId,
            $patientId,
            $visitId,
            $this->departmentId($user),
            'DOWNLOAD',
            'MedicalDocument',
            (int)$document['id'],
            'Authorized document download.'
        );
    }

    private function recordProtectedAccess(
        array $document,
        array $user,
        string $action,
        string $accessType
    ): array {
        try {
            $this->pdo->beginTransaction();
            $this->lockPatient((int)$document['patient_id']);
            $this->audit(
                (int)$user['id'],
                (int)$document['patient_id'],
                $this->nullableInt($document['visit_id']),
                $action,
                'Viewed protected Medical Document #' . (int)$document['id'] . '.'
            );
            if (!$this->auditService->logPatientAccess(
                (int)$user['id'],
                (int)$document['patient_id'],
                $this->nullableInt($document['visit_id']),
                $this->departmentId($user),
                $accessType,
                'MedicalDocument',
                (int)$document['id'],
                'Authorized protected document access.'
            )) {
                throw new RuntimeException('Unable to record protected document access.');
            }
            $this->pdo->commit();
            return $this->success(['document_id' => (int)$document['id']]);
        } catch (Throwable $exception) {
            $this->rollback();
            return $this->failure(['Protected document access could not be recorded.'])
                + ['audit_failed' => true];
        }
    }

    private function encounterEvent(
        ?array $visit,
        string $type,
        string $title,
        string $description,
        int $actorId
    ): void {
        if ($visit === null) {
            return;
        }
        $result = $this->eventService->record(
            (int)$visit['id'],
            $type,
            $title,
            $description,
            $this->nullableInt($visit['current_department_id'] ?? null),
            $actorId
        );
        if (!($result['success'] ?? false)) {
            throw new RuntimeException('Unable to write encounter event.');
        }
    }

    private function auditRejected(int $actorId, int $patientId, ?int $visitId): void
    {
        if ($actorId <= 0 || $patientId <= 0) {
            return;
        }
        $this->auditService->logPatient(
            $actorId,
            $patientId,
            $visitId,
            'Medical Records',
            'DOCUMENT_UPLOAD_REJECTED',
            'A Medical Document upload was rejected by server validation.',
            null,
            'WARNING',
            'DOCUMENT_UPLOAD_REJECTED'
        );
    }

    private function auditDenied(int $actorId, int $patientId, string $action): void
    {
        if ($patientId <= 0) {
            return;
        }
        $this->auditService->logPatient(
            $actorId > 0 ? $actorId : null,
            $patientId,
            null,
            'Security',
            $action,
            'Medical Document access was denied.',
            null,
            'WARNING',
            $action
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Safe Presentation and Utility Helpers
    |--------------------------------------------------------------------------
    */

    private function protectDocument(array $document, bool $canViewConfidential): array
    {
        $hidden = $this->isConfidential($document) && !$canViewConfidential;
        unset($document['storage_key'], $document['stored_filename']);
        $document['confidential_hidden'] = $hidden;
        if ($hidden) {
            $document['title'] = 'Confidential Medical Document';
            $document['description'] = null;
            $document['original_filename'] = 'Confidential document';
            $document['sha256_checksum'] = null;
        }
        return $document;
    }

    private function minimumDocument(array $document): array
    {
        $document = $this->protectDocument($document, false);
        unset(
            $document['description'],
            $document['original_filename'],
            $document['sha256_checksum']
        );
        return $document;
    }

    private function isConfidential(array $document): bool
    {
        return (string)($document['confidentiality_level'] ?? 'Standard') !== 'Standard';
    }

    private function sanitizeDisplayFilename(string $filename): string
    {
        $filename = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $filename) ?? '');
        $filename = preg_replace('/\s+/u', ' ', $filename) ?? '';
        return $filename === '' ? 'document' : mb_substr($filename, 0, 255);
    }

    private function generateStorageKey(string $extension): string
    {
        $random = bin2hex(random_bytes(32));
        return substr($random, 0, 2) . '/' . $random . '.' . $extension;
    }

    private function getDownloadCachePolicy(): string
    {
        $policy = $this->settingsService->getString(
            'documents.download_cache_policy',
            'no-store'
        );
        return in_array($policy, ['no-store', 'private, no-cache'], true)
            ? $policy
            : 'no-store';
    }

    private function settingSubset(string $key, array $supported): array
    {
        $configured = $this->settingsService->getArray($key, $supported);
        $allowed = array_values(array_unique(array_intersect($configured, $supported)));
        return $allowed !== [] ? $allowed : $supported;
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the server size limit.',
            UPLOAD_ERR_PARTIAL => 'The file upload was incomplete.',
            UPLOAD_ERR_NO_FILE => 'Select a file to upload.',
            default => 'The file upload could not be completed.'
        };
    }

    private function departmentId(array $user): ?int
    {
        return $this->nullableInt(
            $user['active_department_id'] ?? $user['department_id'] ?? null
        );
    }

    private function nullableInt(mixed $value): ?int
    {
        $value = (int)$value;
        return $value > 0 ? $value : null;
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    private function assertAffected(PDOStatement $statement, string $message): void
    {
        if ($statement->rowCount() !== 1) {
            throw new RuntimeException($message);
        }
    }

    private function safeWriteError(Throwable $exception, string $fallback): string
    {
        return match ($exception->getMessage()) {
            'Patient not found.' => 'Patient not found.',
            'Encounter does not belong to this patient.' => 'The selected encounter does not belong to this patient.',
            'Closed encounters do not accept new document attachments.' => 'Closed encounters do not accept new document attachments.',
            'Only active documents can be replaced.' => 'Only active documents can be replaced.',
            'Entered-in-error documents are terminal.' => 'Entered-in-error documents are terminal.',
            'The requested document transition is not available.' => 'The requested document transition is not available.',
            'This document version is not available for download.' => 'This document version is not available for download.',
            'Stored document is unavailable.' => 'The stored document is unavailable.',
            'Stored document integrity verification failed.' => 'Document integrity verification failed.',
            default => $fallback
        };
    }

    private function success(array $data): array
    {
        return ['success' => true, 'data' => $data, 'errors' => []] + $data;
    }

    private function failure(array $errors): array
    {
        return ['success' => false, 'data' => null, 'errors' => $errors];
    }

    private function forbidden(string $message): array
    {
        return [
            'success' => false,
            'data' => null,
            'forbidden' => true,
            'errors' => [$message]
        ];
    }

    private function conflict(string $message, int $version): array
    {
        return [
            'success' => false,
            'data' => null,
            'conflict' => true,
            'current_version' => $version,
            'errors' => [$message]
        ];
    }

    private function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
