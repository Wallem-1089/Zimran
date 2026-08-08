<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
if ($visitId <= 0) {
    header('Location: ../patients/search.php');
    exit;
}

$visit = consultationRequireVisit($visitService, $visitId);
consultationRequireAccess($permissionService, $visit, $currentUser);

$consultation = $consultationService->getByVisit($visitId);
if ($consultation) {
    header('Location: view.php?id=' . (int)$consultation['id']);
    exit;
}

header('Location: create.php?visit=' . $visitId);
exit;
