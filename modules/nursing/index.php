<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$patientId = filter_input(INPUT_GET, 'patient', FILTER_VALIDATE_INT) ?: 0;

if ($visitId > 0) {
    header('Location: assessment.php?visit=' . $visitId);
    exit;
}

if ($patientId > 0) {
    header('Location: history.php?patient=' . $patientId);
    exit;
}

header('Location: history.php');
exit;
