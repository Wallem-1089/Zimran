<?php
declare(strict_types=1);
$patientId = filter_input(INPUT_GET, 'patient', FILTER_VALIDATE_INT);
$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT);
if (!$patientId) { header('Location: ../index.php'); exit; }
header('Location: ../chart.php?patient=' . (int)$patientId . '&tab=notes' . ($visitId ? '&visit=' . (int)$visitId : ''));
exit;
