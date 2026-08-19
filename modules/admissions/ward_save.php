<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
admissionRequireReady($admissionTablesReady);
requireCsrfToken();

$result = $admissionService->createWard($_POST, $currentUser);
admissionFlash($result, 'Ward created.');
header('Location: wards.php');
exit;
