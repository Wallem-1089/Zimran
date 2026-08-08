<?php

declare(strict_types=1);

putenv('HMS_DOCUMENT_STORAGE_ROOT=' . __DIR__ . '/../storage/tests/medical_documents');

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../services/MedicalDocumentService.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';

function assertDocumentTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function documentSuccess(array $result, string $label): array
{
    assertDocumentTest(($result['success'] ?? false) === true, $label . ': ' . implode(' ', $result['errors'] ?? []));
    return $result;
}

function testUpload(string $path, string $name): array
{
    return ['name' => $name, 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'size' => filesize($path), 'type' => 'application/octet-stream'];
}

function writeFixture(string $directory, string $name, string $contents): string
{
    $path = $directory . DIRECTORY_SEPARATOR . $name;
    if (file_put_contents($path, $contents) === false) {
        throw new RuntimeException('Unable to create isolated upload fixture.');
    }
    return $path;
}

function removeTree(string $directory): void
{
    if (!is_dir($directory)) { return; }
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($items as $item) { $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname()); }
    rmdir($directory);
}

$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
assertDocumentTest($databaseName === $resolved['test'] && $databaseName !== $resolved['live'], 'Medical Document tests are not isolated from live data.');
fwrite(STDOUT, 'Resolved live database: ' . $resolved['live'] . PHP_EOL . 'Resolved test database: ' . $resolved['test'] . PHP_EOL);

$storageRoot = __DIR__ . '/../storage/tests/medical_documents';
$fixtureRoot = __DIR__ . '/../storage/tests/medical_document_uploads';
removeTree($storageRoot); removeTree($fixtureRoot); mkdir($fixtureRoot, 0700, true);

$users = [];
$rows = $pdo->query("SELECT u.*,r.role_name,d.department_name FROM users u INNER JOIN roles r ON r.id=u.role_id INNER JOIN departments d ON d.id=u.department_id WHERE u.username IN ('admin','dev_doctor','dev_reception','dev_records','dev_nurse','dev_accounts')")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) { $users[$row['username']] = $row; }
foreach (['admin','dev_doctor','dev_reception','dev_records','dev_nurse','dev_accounts'] as $username) { assertDocumentTest(isset($users[$username]), 'Missing fixture user ' . $username); }
$admin = $users['admin']; $doctor = $users['dev_doctor']; $reception = $users['dev_reception']; $accounts = $users['dev_accounts'];
$patientIds = array_map('intval', $pdo->query("SELECT id FROM patients WHERE hospital_number IN ('DEV-PATIENT-0001','DEV-PATIENT-0002') ORDER BY hospital_number")->fetchAll(PDO::FETCH_COLUMN));
assertDocumentTest(count($patientIds) === 2, 'Patient fixtures are missing.');
[$patientId, $otherPatientId] = $patientIds;

$suffix = date('YmdHis') . random_int(1000, 9999);
$pdo->prepare("INSERT INTO visits (visit_number,patient_id,visit_date,visit_type,current_department_id,attending_doctor_id,current_department_received_status,visit_status,created_by) VALUES (:number,:patient,NOW(),'Outpatient',:department,:doctor,'Received','Doctor',:creator)")->execute([':number'=>'TEST-DOC-'.$suffix,':patient'=>$patientId,':department'=>$doctor['department_id'],':doctor'=>$doctor['id'],':creator'=>$admin['id']]);
$visitId = (int)$pdo->lastInsertId();
$documentIds = [];
$storage = new SecureLocalDocumentStorage($storageRoot);
$service = new MedicalDocumentService($pdo, $storage);
$settings = new SettingsService($pdo);

try {
    $ledger = $pdo->prepare("SELECT checksum FROM schema_migrations WHERE migration_name='020_phase2_medical_documents_up.sql'");
    $ledger->execute();
    assertDocumentTest(hash_equals((string)$ledger->fetchColumn(), (string)hash_file('sha256', __DIR__ . '/../database/migrations/020_phase2_medical_documents_up.sql')), 'Migration 020 checksum is not ledger-aligned.');

    $invalidSetting = $settings->update('documents.allowed_mime_types', ['application/pdf','application/x-php'], (int)$admin['id']);
    assertDocumentTest(!($invalidSetting['success'] ?? true), 'Unsafe MIME setting was accepted.');

    $pdf = writeFixture($fixtureRoot, 'valid.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");
    $uploaded = documentSuccess($service->uploadDocument(['patient_id'=>$patientId,'visit_id'=>$visitId,'document_type'=>'referral_letter','title'=>'Referral '.$suffix,'description'=>'Encounter referral.','confidentiality_level'=>'Standard'], testUpload($pdf, 'referral.pdf'), $admin), 'Encounter upload');
    $documentId = (int)$uploaded['document_id']; $documentIds[] = $documentId;
    assertDocumentTest($uploaded['upload_status'] === 'Available', 'Narrow unscanned development upload was not available.');
    assertDocumentTest((int)$pdo->query("SELECT COUNT(*) FROM encounter_events WHERE visit_id=$visitId AND event_type='MEDICAL_DOCUMENT_UPLOADED'")->fetchColumn() === 1, 'Encounter upload event missing.');

    $patientPdf = writeFixture($fixtureRoot, 'patient.pdf', "%PDF-1.4\n% patient\n%%EOF\n");
    $patientUpload = documentSuccess($service->uploadDocument(['patient_id'=>$patientId,'document_type'=>'insurance_document','title'=>'Insurance '.$suffix,'confidentiality_level'=>'Confidential'], testUpload($patientPdf, 'insurance.pdf'), $admin), 'Patient-level upload');
    $confidentialId = (int)$patientUpload['document_id']; $documentIds[] = $confidentialId;
    $masked = $service->getDocumentByIdForUser($confidentialId, $reception, false);
    assertDocumentTest(($masked['success'] ?? false) && !empty($masked['data']['document']['confidential_hidden']), 'Confidential metadata was not masked.');
    $full = documentSuccess($service->getDocumentByIdForUser($confidentialId, $admin, true), 'Confidential admin access');
    assertDocumentTest(empty($full['data']['document']['confidential_hidden']), 'Authorized confidential detail was masked.');

    $replacement = writeFixture($fixtureRoot, 'replacement.pdf', "%PDF-1.4\n% replacement\n%%EOF\n");
    $replaced = documentSuccess($service->replaceDocument($documentId, ['replacement_reason'=>'Corrected source file.'], testUpload($replacement, 'referral-v2.pdf'), $admin, 1), 'Replacement');
    assertDocumentTest((int)$replaced['version_number'] === 2 && (int)$pdo->query("SELECT COUNT(*) FROM medical_document_versions WHERE document_id=$documentId")->fetchColumn() === 2, 'Replacement did not append an immutable version.');
    $stale = $service->replaceDocument($documentId, ['replacement_reason'=>'Stale replacement.'], testUpload(writeFixture($fixtureRoot, 'stale.pdf', "%PDF-1.4\n%%EOF\n"), 'stale.pdf'), $admin, 1);
    assertDocumentTest(!($stale['success'] ?? true) && !empty($stale['conflict']), 'Stale replacement was not rejected.');

    $versions = documentSuccess($service->getDocumentVersions($documentId, $admin), 'Version history');
    assertDocumentTest(count($versions['data']['versions']) === 2, 'Version history is incomplete.');
    $download = documentSuccess($service->prepareDownload($documentId, $admin), 'Authorized download');
    assertDocumentTest(is_resource($download['data']['stream']) && $download['data']['mime_type'] === 'application/pdf', 'Download stream metadata is invalid.');
    fclose($download['data']['stream']);
    assertDocumentTest((int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE patient_id=$patientId AND action='MEDICAL_DOCUMENT_DOWNLOADED'")->fetchColumn() >= 1, 'Download audit is missing.');
    assertDocumentTest((int)$pdo->query("SELECT COUNT(*) FROM record_access_logs WHERE patient_id=$patientId AND resource_type='MedicalDocument'")->fetchColumn() >= 1, 'Document PHI access log is missing.');

    documentSuccess($service->archiveDocument($documentId, 'Retention workflow.', $admin, 2), 'Archive');
    documentSuccess($service->restoreDocument($documentId, 'Record required again.', $admin, 3), 'Restore');
    $enteredPdf = writeFixture($fixtureRoot, 'error.pdf', "%PDF-1.4\n% error\n%%EOF\n");
    $enteredUpload = documentSuccess($service->uploadDocument(['patient_id'=>$patientId,'document_type'=>'other','title'=>'Error '.$suffix,'confidentiality_level'=>'Standard'], testUpload($enteredPdf, 'error.pdf'), $admin), 'Error lifecycle upload');
    $enteredId = (int)$enteredUpload['document_id']; $documentIds[] = $enteredId;
    documentSuccess($service->markDocumentEnteredInError($enteredId, 'Attached to chart in error.', $admin, 1), 'Entered in error');
    assertDocumentTest(!($service->restoreDocument($enteredId, 'Invalid restore.', $admin, 2)['success'] ?? true), 'Entered-in-error document was restored.');

    $png = writeFixture($fixtureRoot, 'valid.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
    $pngResult = documentSuccess($service->uploadDocument(['patient_id'=>$patientId,'document_type'=>'clinical_photograph','title'=>'Image '.$suffix,'confidentiality_level'=>'Standard'], testUpload($png, 'image.png'), $admin), 'PNG upload');
    $documentIds[] = (int)$pngResult['document_id'];
    $jpeg = writeFixture($fixtureRoot, 'valid.jpg', base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAf/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oADAMBAAIAAwAAABAf/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPxB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPxB//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxB//9k='));
    $jpegResult = documentSuccess($service->uploadDocument(['patient_id'=>$patientId,'document_type'=>'clinical_photograph','title'=>'JPEG '.$suffix,'confidentiality_level'=>'Standard'], testUpload($jpeg, 'image.jpg'), $admin), 'JPEG upload');
    $documentIds[] = (int)$jpegResult['document_id'];

    foreach ([
        ['bad.php', '<?php echo 1; ?>'],
        ['bad.php.pdf', "%PDF-1.4\n%%EOF\n"],
        ['../bad.pdf', "%PDF-1.4\n%%EOF\n"],
        ['spoof.pdf', 'not a pdf']
    ] as [$name,$contents]) {
        $path = writeFixture($fixtureRoot, 'reject-' . bin2hex(random_bytes(3)), $contents);
        $rejected = $service->uploadDocument(['patient_id'=>$patientId,'document_type'=>'other','title'=>'Rejected','confidentiality_level'=>'Standard'], testUpload($path, $name), $admin);
        assertDocumentTest(!($rejected['success'] ?? true), 'Unsafe upload was accepted: ' . $name);
    }
    $missing = $service->uploadDocument(['patient_id'=>$patientId,'document_type'=>'other','title'=>'Missing','confidentiality_level'=>'Standard'], ['name'=>'missing.pdf','tmp_name'=>$fixtureRoot.'/missing','error'=>UPLOAD_ERR_OK], $admin);
    assertDocumentTest(!($missing['success'] ?? true), 'Missing temporary upload was accepted.');
    documentSuccess($settings->update('documents.maximum_upload_bytes', 1024, (int)$admin['id']), 'Reduce upload limit');
    $oversized = writeFixture($fixtureRoot, 'large.txt', str_repeat('A', 2048));
    assertDocumentTest(!($service->uploadDocument(['patient_id'=>$patientId,'document_type'=>'other','title'=>'Oversized','confidentiality_level'=>'Standard'], testUpload($oversized, 'large.txt'), $admin)['success'] ?? true), 'Oversized upload was accepted.');
    documentSuccess($settings->update('documents.maximum_upload_bytes', 10485760, (int)$admin['id']), 'Restore upload limit');

    $cross = writeFixture($fixtureRoot, 'cross.pdf', "%PDF-1.4\n%%EOF\n");
    $crossResult = $service->uploadDocument(['patient_id'=>$otherPatientId,'visit_id'=>$visitId,'document_type'=>'other','title'=>'Cross patient','confidentiality_level'=>'Standard'], testUpload($cross, 'cross.pdf'), $admin);
    assertDocumentTest(!($crossResult['success'] ?? true), 'Cross-patient encounter document was accepted.');

    $unauthorized = writeFixture($fixtureRoot, 'unauthorized.pdf', "%PDF-1.4\n%%EOF\n");
    $denied = $service->uploadDocument(['patient_id'=>$patientId,'document_type'=>'clinical_photograph','title'=>'Unauthorized','confidentiality_level'=>'Standard'], testUpload($unauthorized, 'unauthorized.pdf'), $accounts);
    assertDocumentTest(!($denied['success'] ?? true) && !empty($denied['forbidden']), 'Role-scoped document type restriction failed.');

    $pdo->exec("UPDATE visits SET visit_status='Completed' WHERE id=$visitId");
    $closedFile = writeFixture($fixtureRoot, 'closed.pdf', "%PDF-1.4\n%%EOF\n");
    assertDocumentTest(!($service->uploadDocument(['patient_id'=>$patientId,'visit_id'=>$visitId,'document_type'=>'other','title'=>'Closed','confidentiality_level'=>'Standard'], testUpload($closedFile, 'closed.pdf'), $admin)['success'] ?? true), 'Closed encounter accepted an attachment.');
    $pdo->exec("UPDATE visits SET visit_status='Doctor' WHERE id=$visitId");

    documentSuccess($settings->update('documents.malware_scanning_required', true, (int)$admin['id']), 'Enable quarantine policy');
    $quarantine = writeFixture($fixtureRoot, 'quarantine.pdf', "%PDF-1.4\n% quarantine\n%%EOF\n");
    $quarantined = documentSuccess($service->uploadDocument(['patient_id'=>$patientId,'document_type'=>'other','title'=>'Quarantine '.$suffix,'confidentiality_level'=>'Standard'], testUpload($quarantine, 'quarantine.pdf'), $admin), 'Quarantined upload');
    $quarantineId = (int)$quarantined['document_id']; $documentIds[] = $quarantineId;
    assertDocumentTest($quarantined['upload_status'] === 'Quarantined' && !($service->prepareDownload($quarantineId, $admin)['success'] ?? true), 'Quarantined file was downloadable.');
    documentSuccess($settings->update('documents.malware_scanning_required', false, (int)$admin['id']), 'Restore quarantine policy');

    $currentStorageKey = (string)$pdo->query("SELECT storage_key FROM medical_document_versions WHERE document_id=$documentId AND version_number=2")->fetchColumn();
    $currentPath = $storageRoot . '/available/' . str_replace('/', DIRECTORY_SEPARATOR, $currentStorageKey);
    $originalBytes = (string)file_get_contents($currentPath);
    file_put_contents($currentPath, $originalBytes . 'tampered');
    assertDocumentTest(!($service->prepareDownload($documentId, $admin)['success'] ?? true), 'Checksum mismatch was not rejected.');
    file_put_contents($currentPath, $originalBytes);

    $accessFailAudit = new class($pdo) extends AuditService {
        public function logPatientAccess(int $userId, int $patientId, ?int $visitId, ?int $departmentId, string $accessType, string $resourceType = 'PatientChart', ?int $resourceId = null, ?string $accessReason = null): bool { return false; }
    };
    $accessFailService = new MedicalDocumentService($pdo, $storage, $accessFailAudit);
    assertDocumentTest(!($accessFailService->prepareDownload($documentId, $admin)['success'] ?? true), 'Required download access-log failure did not fail closed.');

    $failingStorage = new class implements DocumentStorageInterface {
        public function store(string $temporaryPath,string $storageKey,bool $quarantined): array { return ['success'=>false,'errors'=>['Injected storage failure.']]; }
        public function openStream(string $storageKey,bool $quarantined=false): mixed { return false; }
        public function exists(string $storageKey,bool $quarantined=false): bool { return false; }
        public function deleteTemporaryFile(string $temporaryPath): bool { return true; }
        public function quarantine(string $storageKey): bool { return false; }
        public function moveFromQuarantine(string $storageKey): bool { return false; }
        public function remove(string $storageKey,bool $quarantined=false): bool { return true; }
        public function getMetadata(string $storageKey,bool $quarantined=false): array { return ['success'=>false,'errors'=>['Injected storage failure.']]; }
    };
    $storageFailureFile = writeFixture($fixtureRoot, 'storage-failure.pdf', "%PDF-1.4\n%%EOF\n");
    $storageFailure = (new MedicalDocumentService($pdo, $failingStorage))->uploadDocument(['patient_id'=>$patientId,'document_type'=>'other','title'=>'Storage failure','confidentiality_level'=>'Standard'], testUpload($storageFailureFile, 'failure.pdf'), $admin);
    assertDocumentTest(!($storageFailure['success'] ?? true), 'Storage failure did not reject the upload.');

    $failingAudit = new class($pdo) extends AuditService {
        public function logPatient(?int $userId, int $patientId, ?int $visitId, string $module, string $action, string $description, ?int $departmentId = null, string $severity = 'INFO', ?string $eventType = null): bool { return false; }
    };
    $rollbackService = new MedicalDocumentService($pdo, $storage, $failingAudit);
    $storedFileCountBefore = iterator_count(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($storageRoot, FilesystemIterator::SKIP_DOTS)));
    $rollback = writeFixture($fixtureRoot, 'rollback.pdf', "%PDF-1.4\n% rollback\n%%EOF\n");
    $rollbackTitle = 'Rollback ' . $suffix;
    $rolledBack = $rollbackService->uploadDocument(['patient_id'=>$patientId,'document_type'=>'other','title'=>$rollbackTitle,'confidentiality_level'=>'Standard'], testUpload($rollback, 'rollback.pdf'), $admin);
    assertDocumentTest(!($rolledBack['success'] ?? true), 'Audit failure did not fail the upload.');
    $check = $pdo->prepare('SELECT COUNT(*) FROM medical_documents WHERE title=:title'); $check->execute([':title'=>$rollbackTitle]);
    assertDocumentTest((int)$check->fetchColumn() === 0, 'Audit failure did not roll back document metadata.');
    $storedFileCountAfter = iterator_count(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($storageRoot, FilesystemIterator::SKIP_DOTS)));
    assertDocumentTest($storedFileCountAfter === $storedFileCountBefore, 'Audit rollback left an orphaned stored file.');

    $failingEvent = new class($pdo) extends EncounterEventService {
        public function record(int $visitId,string $eventType,string $eventTitle,?string $eventDescription,?int $departmentId,?int $performedBy): array { return ['success'=>false,'errors'=>['Injected event failure.']]; }
    };
    $eventFailureFile = writeFixture($fixtureRoot, 'event-failure.pdf', "%PDF-1.4\n%%EOF\n");
    $eventFailureTitle = 'Event rollback ' . $suffix;
    $eventFailure = (new MedicalDocumentService($pdo, $storage, null, $failingEvent))->uploadDocument(['patient_id'=>$patientId,'visit_id'=>$visitId,'document_type'=>'other','title'=>$eventFailureTitle,'confidentiality_level'=>'Standard'], testUpload($eventFailureFile, 'event-failure.pdf'), $admin);
    assertDocumentTest(!($eventFailure['success'] ?? true), 'Encounter-event failure did not reject the upload.');
    $check->execute([':title'=>$eventFailureTitle]); assertDocumentTest((int)$check->fetchColumn() === 0, 'Encounter-event failure did not roll back metadata.');

    assertDocumentTest(is_file($storageRoot . '/.htaccess') && is_file($storageRoot . '/web.config'), 'Direct-web-access denial files are missing.');
    assertDocumentTest((int)$pdo->query('SELECT COUNT(*) FROM clinical_notes')->fetchColumn() === 0, 'Medical Document workflow created Clinical Notes unexpectedly.');
} finally {
    $settings->update('documents.malware_scanning_required', false, (int)$admin['id']);
    $settings->update('documents.maximum_upload_bytes', 10485760, (int)$admin['id']);
    if ($documentIds !== []) {
        $ids = implode(',', array_map('intval', $documentIds));
        $pdo->exec("DELETE FROM record_access_logs WHERE resource_type='MedicalDocument' AND resource_id IN ($ids)");
        $pdo->exec("UPDATE medical_document_versions SET supersedes_version_id=NULL WHERE document_id IN ($ids)");
        $pdo->exec("DELETE FROM medical_document_versions WHERE document_id IN ($ids)");
        $pdo->exec("DELETE FROM medical_documents WHERE id IN ($ids)");
    }
    $pdo->exec("DELETE FROM encounter_events WHERE visit_id=$visitId");
    $pdo->exec("DELETE FROM audit_logs WHERE visit_id=$visitId OR (patient_id=$patientId AND action IN ('MEDICAL_DOCUMENT_UPLOADED','MEDICAL_DOCUMENT_REPLACED','MEDICAL_DOCUMENT_DOWNLOADED','MEDICAL_DOCUMENT_ARCHIVED','MEDICAL_DOCUMENT_RESTORED','MEDICAL_DOCUMENT_ENTERED_IN_ERROR','CONFIDENTIAL_DOCUMENT_VIEWED','DOCUMENT_ACCESS_DENIED','DOCUMENT_UPLOAD_REJECTED','MEDICAL_RECORD_VIEWED'))");
    $pdo->exec("DELETE FROM visits WHERE id=$visitId");
    removeTree($storageRoot); removeTree($fixtureRoot);
}
assertDocumentTest(!is_dir($storageRoot) && !is_dir($fixtureRoot), 'Isolated test storage cleanup failed.');
echo "Phase 2.5 Medical Document tests passed.\n";
