<?php

declare(strict_types=1);

$requestId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
header('Location: record.php' . ($requestId > 0 ? '?id=' . $requestId : ''));
exit;
