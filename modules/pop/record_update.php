<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

$requestId = filter_input(INPUT_POST, 'pop_request_id', FILTER_VALIDATE_INT) ?: 0;
if (!$requestId) {
    http_response_code(400);
    exit('Invalid Plaster request.');
}
$request = $popService->getRequestById($requestId, $currentUser);
if (!$request) {
    http_response_code(404);
    exit('Plaster request not found.');
}
$visit = popRequireVisit($visitService, (int)$request['visit_id']);
if (!$permissionService->canEditPopRecord($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot edit this Plaster record.');
}

$result = $popService->updateRecord($_POST, $currentUser);
if (($result['success'] ?? false) === true) {
    $configuredResult = $configurableFormService->saveResponse(
        'pop_record',
        (int)$request['patient_id'],
        (int)$request['visit_id'],
        'Plaster Record',
        $requestId,
        $_POST,
        $currentUser
    );
    if (($configuredResult['success'] ?? false) !== true) {
        $_SESSION['old_configured_fields'] = $_POST['configured_fields'] ?? [];
        $_SESSION['validation_errors'] = $configuredResult['errors'] ?? ['Unable to save configured form fields.'];
        header('Location: record.php?id=' . $requestId);
        exit;
    }
}
popFlash($result, 'Plaster record updated.');
header('Location: record.php?id=' . $requestId);
exit;
