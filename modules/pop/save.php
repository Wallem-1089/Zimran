<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

requireCsrfToken();

$visitId = filter_input(INPUT_POST, 'visit_id', FILTER_VALIDATE_INT) ?: 0;
$requestSource = popRequestSourceLabel((string)($_POST['request_source'] ?? 'Clinical'));

if (!$visitId) {
    http_response_code(400);
    exit('Invalid encounter.');
}

$visit = popRequireVisit($visitService, $visitId);
popRequireCreateAccess($permissionService, $visit, $currentUser, $requestSource);

if (!$popTablesReady) {
    http_response_code(503);
    exit('POP tables are not available yet. Apply Migration 059 to enable this section.');
}

$result = $popService->createRequest($_POST, $currentUser);

if (!($result['success'] ?? false)) {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to save POP request.'];
    $_SESSION['old_pop_request'] = [
        'request_source' => $requestSource,
        'priority' => (string)($_POST['priority'] ?? 'Routine'),
        'procedure_requested' => (string)($_POST['procedure_requested'] ?? 'POP / Casting'),
        'clinical_indication' => (string)($_POST['clinical_indication'] ?? ''),
    ];
    header('Location: request.php?visit=' . $visitId . '&source=' . urlencode($requestSource));
    exit;
}

$_SESSION['success_message'] = 'POP request saved.';
header('Location: view.php?id=' . (int)$result['pop_request_id']);
exit;
