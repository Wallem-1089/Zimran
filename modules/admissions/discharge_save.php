<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
admissionRequireReady($admissionTablesReady);
requireCsrfToken();

$id = (int)($_POST['id'] ?? 0);
$result = $admissionService->discharge($id, $_POST, $currentUser);
admissionFlash($result, 'Admission discharged.');
header('Location: view.php?id=' . $id);
exit;
