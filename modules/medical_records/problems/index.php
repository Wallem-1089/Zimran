<?php

declare(strict_types=1);

$patientId = filter_input(INPUT_GET, 'patient', FILTER_VALIDATE_INT);
header('Location: ../chart.php?patient=' . (int)$patientId . '&tab=problems' . (!empty($_GET['visit']) ? '&visit=' . (int)$_GET['visit'] : '')); exit;
