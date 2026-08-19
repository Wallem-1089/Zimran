<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
admissionRequireReady($admissionTablesReady);
requireCsrfToken();

$result = $admissionService->addBed($_POST, $currentUser);
admissionFlash($result, 'Bed created.');
header('Location: wards.php');
exit;
