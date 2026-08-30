<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';

$_SESSION['test'] = 'Hospital System';

echo $_SESSION['test'];
