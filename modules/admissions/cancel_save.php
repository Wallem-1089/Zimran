<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
admissionRequireReady($admissionTablesReady);
requireCsrfToken();

$id = (int)($_POST['id'] ?? 0);
$result = $admissionService->cancel($id, $_POST, $currentUser);
admissionFlash($result, 'Admission cancelled.');
header('Location: view.php?id=' . $id);
exit;
