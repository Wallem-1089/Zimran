<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/AuditService.php';
require_once __DIR__ . '/../../services/MedicalDocumentService.php';
require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/PermissionService.php';
require_once __DIR__ . '/../../services/RadiologyService.php';
require_once __DIR__ . '/../../services/VisitService.php';

requireCsrfToken();

$sourceType = trim((string)($_POST['source_type'] ?? ''));
$sourceId = filter_input(INPUT_POST, 'source_id', FILTER_VALIDATE_INT) ?: 0;
$returnUrl = trim((string)($_POST['return_url'] ?? '../patients/search.php'));
$consentConfirmed = isset($_POST['patient_consent_confirmed']);

if (!$consentConfirmed) {
    $_SESSION['validation_errors'] = ['Confirm patient consent before opening WhatsApp.'];
    header('Location: ' . safeReturnUrl($returnUrl));
    exit;
}

try {
    $permissionService = new PermissionService($pdo);
    $patientService = new PatientService($pdo);
    $visitService = new VisitService($pdo);
    $auditService = new AuditService($pdo);

    $payload = match ($sourceType) {
        'radiology_report' => radiologyHandoffPayload(
            $pdo,
            $sourceId,
            $currentUser,
            $patientService,
            $visitService,
            $permissionService
        ),
        'medical_document' => documentHandoffPayload(
            $sourceId,
            $currentUser,
            $patientService,
            $permissionService
        ),
        default => throw new RuntimeException('Unsupported WhatsApp handoff source.'),
    };

    $phone = normalizeWhatsappPhone((string)($payload['patient']['phone'] ?? ''));
    if ($phone === '') {
        $_SESSION['validation_errors'] = ['Patient phone number is missing or invalid.'];
        header('Location: ' . safeReturnUrl($returnUrl));
        exit;
    }

    $message = whatsappMessage($payload);
    $logged = $auditService->logPatient(
        (int)($currentUser['id'] ?? 0),
        (int)$payload['patient']['id'],
        $payload['visit_id'],
        'Patient Communications',
        'PATIENT_WHATSAPP_HANDOFF_INITIATED',
        $payload['audit_description'],
        (int)($currentUser['department_id'] ?? 0) ?: null,
        'INFO',
        'PATIENT_WHATSAPP_HANDOFF_INITIATED'
    );

    if (!$logged) {
        $_SESSION['validation_errors'] = ['Unable to audit WhatsApp handoff. Please try again.'];
        header('Location: ' . safeReturnUrl($returnUrl));
        exit;
    }

    header('Location: https://wa.me/' . rawurlencode($phone) . '?text=' . rawurlencode($message));
    exit;
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $_SESSION['validation_errors'] = ['Unable to open WhatsApp handoff.'];
    header('Location: ' . safeReturnUrl($returnUrl));
    exit;
}

function radiologyHandoffPayload(
    PDO $pdo,
    int $requestId,
    array $user,
    PatientService $patientService,
    VisitService $visitService,
    PermissionService $permissionService
): array {
    if ($requestId <= 0) {
        throw new RuntimeException('Radiology request is required.');
    }

    $radiologyService = new RadiologyService($pdo, null, null, $permissionService);
    $request = $radiologyService->getRequestById($requestId, $user);
    if (!$request) {
        throw new RuntimeException('Radiology request not found or access denied.');
    }

    $visit = $visitService->getVisitById((int)$request['visit_id']);
    if (!$visit || !$permissionService->canViewRadiology((int)$request['patient_id'], $user)) {
        throw new RuntimeException('Radiology WhatsApp handoff denied.');
    }

    $result = $radiologyService->getResult($requestId, $user);
    if (!$result || trim((string)($result['impression'] ?? '')) === '') {
        throw new RuntimeException('Radiology report is not available yet.');
    }

    $patient = $patientService->getPatientById((int)$request['patient_id']);
    if (!$patient) {
        throw new RuntimeException('Patient not found.');
    }

    return [
        'type' => 'radiology_report',
        'patient' => $patient,
        'visit_id' => (int)$request['visit_id'],
        'visit_number' => (string)($request['visit_number'] ?? ''),
        'title' => 'Radiology report',
        'item' => (string)($request['study_requested'] ?? 'Radiology study'),
        'audit_description' => 'Opened WhatsApp handoff for radiology request #' . $requestId . '. Patient consent confirmed by staff.',
    ];
}

function documentHandoffPayload(
    int $documentId,
    array $user,
    PatientService $patientService,
    PermissionService $permissionService
): array {
    if ($documentId <= 0) {
        throw new RuntimeException('Medical document is required.');
    }

    $documentService = new MedicalDocumentService($GLOBALS['pdo']);
    $result = $documentService->getDocumentByIdForUser($documentId, $user, true);
    if (!($result['success'] ?? false)) {
        throw new RuntimeException('Medical document not found or access denied.');
    }

    $document = $result['data']['document'];
    if (!$permissionService->canDownloadMedicalDocuments((int)$document['patient_id'], $user)) {
        throw new RuntimeException('Medical document WhatsApp handoff denied.');
    }

    $patient = $patientService->getPatientById((int)$document['patient_id']);
    if (!$patient) {
        throw new RuntimeException('Patient not found.');
    }

    return [
        'type' => 'medical_document',
        'patient' => $patient,
        'visit_id' => !empty($document['visit_id']) ? (int)$document['visit_id'] : null,
        'visit_number' => '',
        'title' => 'Medical document',
        'item' => (string)($document['title'] ?? 'Medical document'),
        'audit_description' => 'Opened WhatsApp handoff for medical document #' . $documentId . '. Patient consent confirmed by staff.',
    ];
}

function normalizeWhatsappPhone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if ($digits === '') {
        return '';
    }
    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    }
    if (str_starts_with($digits, '0') && strlen($digits) === 11) {
        $digits = '234' . substr($digits, 1);
    }
    return strlen($digits) >= 10 ? $digits : '';
}

function whatsappMessage(array $payload): string
{
    $patient = $payload['patient'];
    $name = trim((string)($patient['first_name'] ?? '') . ' ' . (string)($patient['last_name'] ?? ''));
    $hospitalNumber = (string)($patient['hospital_number'] ?? '');

    return 'Patient Name: ' . $name . PHP_EOL
        . 'Patient Number: ' . $hospitalNumber;
}

function safeReturnUrl(string $returnUrl): string
{
    $returnUrl = trim($returnUrl);
    if ($returnUrl === '' || preg_match('/^[a-z][a-z0-9+.-]*:/i', $returnUrl) === 1 || str_starts_with($returnUrl, '//')) {
        return '../patients/search.php';
    }
    return $returnUrl;
}
