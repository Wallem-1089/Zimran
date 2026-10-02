<?php

declare(strict_types=1);

require_once __DIR__ . '/PermissionService.php';

class DiagnosticAttachmentService
{
    private string $storageRoot;
    private ?bool $tableAvailable = null;

    public function __construct(
        private PDO $pdo,
        private ?PermissionService $permissionService = null
    ) {
        $this->permissionService = $permissionService ?? new PermissionService($pdo);
        $this->storageRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'diagnostic_attachments';
    }

    public function uploadMany(string $sourceModule, int $sourceRecordId, array $context, ?array $files, array $user): array
    {
        if (!$this->tableExists()) {
            return ['success' => false, 'uploaded' => 0, 'errors' => ['Diagnostic attachment table is not available. Apply Migration 080.']];
        }

        $sourceModule = $this->normalizeModule($sourceModule);
        if ($sourceModule === '') {
            return ['success' => false, 'uploaded' => 0, 'errors' => ['Invalid diagnostic attachment module.']];
        }

        $normalizedFiles = $this->normalizeFiles($files);
        if ($normalizedFiles === []) {
            return ['success' => true, 'uploaded' => 0, 'errors' => []];
        }

        $errors = [];
        $prepared = [];
        foreach ($normalizedFiles as $file) {
            $upload = $this->prepareUpload($sourceModule, $file, $errors);
            if ($upload !== null) {
                $prepared[] = $upload;
            }
        }

        if ($errors !== []) {
            foreach ($prepared as $upload) {
                if (!empty($upload['stored_path']) && is_file((string)$upload['stored_path'])) {
                    @unlink((string)$upload['stored_path']);
                }
            }
            return ['success' => false, 'uploaded' => 0, 'errors' => $errors];
        }

        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO diagnostic_attachments (
                    source_module, source_record_id, visit_id, patient_id,
                    original_filename, stored_path, mime_type, file_size,
                    uploaded_by, uploaded_at, is_active
                ) VALUES (
                    :source_module, :source_record_id, :visit_id, :patient_id,
                    :original_filename, :stored_path, :mime_type, :file_size,
                    :uploaded_by, NOW(), 1
                )
            ');
            foreach ($prepared as $upload) {
                $stmt->execute([
                    ':source_module' => $sourceModule,
                    ':source_record_id' => $sourceRecordId,
                    ':visit_id' => (int)($context['visit_id'] ?? 0),
                    ':patient_id' => (int)($context['patient_id'] ?? 0),
                    ':original_filename' => $upload['original_filename'],
                    ':stored_path' => $upload['stored_path'],
                    ':mime_type' => $upload['mime_type'],
                    ':file_size' => $upload['file_size'],
                    ':uploaded_by' => (int)($user['id'] ?? 0),
                ]);
            }

            return ['success' => true, 'uploaded' => count($prepared), 'errors' => []];
        } catch (Throwable) {
            foreach ($prepared as $upload) {
                if (!empty($upload['stored_path']) && is_file((string)$upload['stored_path'])) {
                    @unlink((string)$upload['stored_path']);
                }
            }
            return ['success' => false, 'uploaded' => 0, 'errors' => ['Unable to save diagnostic attachments.']];
        }
    }

    public function listForSource(string $sourceModule, int $sourceRecordId, ?array $user = null): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $sourceModule = $this->normalizeModule($sourceModule);
        if ($sourceModule === '' || $sourceRecordId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare($this->baseSelect() . '
            WHERE da.source_module = :source_module
              AND da.source_record_id = :source_record_id
              AND da.is_active = 1
            ORDER BY da.uploaded_at DESC, da.id DESC
        ');
        $stmt->execute([
            ':source_module' => $sourceModule,
            ':source_record_id' => $sourceRecordId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getForDownload(int $attachmentId, array $user): array
    {
        if (!$this->tableExists()) {
            return $this->failure(['Diagnostic attachment table is not available.']);
        }

        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE da.id = :id AND da.is_active = 1 LIMIT 1');
        $stmt->execute([':id' => $attachmentId]);
        $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$attachment) {
            return $this->failure(['Diagnostic attachment not found.']);
        }

        if (!$this->canViewAttachment($attachment, $user)) {
            return $this->failure(['You are not allowed to download this diagnostic attachment.']);
        }

        $path = (string)$attachment['stored_path'];
        if (!is_file($path)) {
            return $this->failure(['Diagnostic attachment file is missing.']);
        }

        return [
            'success' => true,
            'attachment' => $attachment,
            'path' => $path,
            'filename' => (string)$attachment['original_filename'],
            'mime_type' => (string)$attachment['mime_type'],
            'errors' => [],
        ];
    }

    public function selectedForMessage(array $attachmentIds, string $sourceModule, int $sourceRecordId, array $user): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $attachmentIds), static fn(int $id): bool => $id > 0)));
        if ($ids === [] || !$this->tableExists()) {
            return [];
        }

        $sourceModule = $this->normalizeModule($sourceModule);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare($this->baseSelect() . "
            WHERE da.id IN ({$placeholders})
              AND da.source_module = ?
              AND da.source_record_id = ?
              AND da.is_active = 1
            ORDER BY da.uploaded_at DESC, da.id DESC
        ");
        $stmt->execute([...$ids, $sourceModule, $sourceRecordId]);

        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            if ($this->canViewAttachment($row, $user)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function tableExists(): bool
    {
        if ($this->tableAvailable !== null) {
            return $this->tableAvailable;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*)
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                   AND table_name = :table'
            );
            $stmt->execute([':table' => 'diagnostic_attachments']);
            $this->tableAvailable = (int)$stmt->fetchColumn() > 0;
        } catch (Throwable) {
            $this->tableAvailable = false;
        }

        return $this->tableAvailable;
    }

    private function prepareUpload(string $sourceModule, array $file, array &$errors): ?array
    {
        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($error !== UPLOAD_ERR_OK) {
            $errors[] = $sourceModule . ' attachment upload failed.';
            return null;
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > 10 * 1024 * 1024) {
            $errors[] = $sourceModule . ' attachments must be between 1 byte and 10 MB.';
            return null;
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_file($tmp)) {
            $errors[] = $sourceModule . ' attachment temporary file is missing.';
            return null;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmp);
        $allowed = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($allowed[$mime])) {
            $errors[] = $sourceModule . ' attachments must be PDF, JPG, or PNG files.';
            return null;
        }

        if (!is_dir($this->storageRoot) && !mkdir($this->storageRoot, 0775, true)) {
            $errors[] = 'Diagnostic attachment storage is not available.';
            return null;
        }

        $storedPath = $this->storageRoot . DIRECTORY_SEPARATOR . bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        $stored = move_uploaded_file($tmp, $storedPath);
        if (!$stored && PHP_SAPI === 'cli' && is_file($tmp)) {
            $stored = rename($tmp, $storedPath);
        }
        if (!$stored) {
            $errors[] = 'Unable to store ' . $sourceModule . ' attachment securely.';
            return null;
        }

        return [
            'original_filename' => basename((string)($file['name'] ?? strtolower($sourceModule) . '-attachment.' . $allowed[$mime])),
            'stored_path' => $storedPath,
            'mime_type' => $mime,
            'file_size' => $size,
        ];
    }

    private function normalizeFiles(?array $files): array
    {
        if (!$files || !isset($files['name'])) {
            return [];
        }

        if (!is_array($files['name'])) {
            return [$files];
        }

        $normalized = [];
        $walk = function (mixed $names, array $path = []) use (&$walk, &$normalized, $files): void {
            if (is_array($names)) {
                foreach ($names as $index => $value) {
                    $walk($value, [...$path, $index]);
                }
                return;
            }

            $normalized[] = [
                'name' => $names,
                'type' => $this->nestedUploadValue($files['type'] ?? null, $path, ''),
                'tmp_name' => $this->nestedUploadValue($files['tmp_name'] ?? null, $path, ''),
                'error' => $this->nestedUploadValue($files['error'] ?? null, $path, UPLOAD_ERR_NO_FILE),
                'size' => $this->nestedUploadValue($files['size'] ?? null, $path, 0),
            ];
        };
        $walk($files['name']);

        return $normalized;
    }

    private function nestedUploadValue(mixed $value, array $path, mixed $default): mixed
    {
        foreach ($path as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    private function canViewAttachment(array $attachment, array $user): bool
    {
        $patientId = (int)($attachment['patient_id'] ?? 0);
        return match ((string)($attachment['source_module'] ?? '')) {
            'Laboratory' => $this->permissionService->canViewLaboratory($patientId, $user),
            'Radiology' => $this->permissionService->canViewRadiology($patientId, $user),
            'ECG' => $this->permissionService->canViewEcg($patientId, $user),
            default => false,
        };
    }

    private function baseSelect(): string
    {
        return '
            SELECT da.*,
                   CONCAT(uploaded_by.first_name, " ", uploaded_by.last_name) AS uploaded_by_name
            FROM diagnostic_attachments da
            LEFT JOIN users uploaded_by ON uploaded_by.id = da.uploaded_by
        ';
    }

    private function normalizeModule(string $sourceModule): string
    {
        $sourceModule = trim($sourceModule);
        return in_array($sourceModule, ['Laboratory', 'Radiology', 'ECG'], true) ? $sourceModule : '';
    }

    private function failure(array $errors): array
    {
        return ['success' => false, 'errors' => $errors];
    }
}
