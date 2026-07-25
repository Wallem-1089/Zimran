<?php

declare(strict_types=1);

require_once '../config/session.php';

$_SESSION['test'] = 'Hospital System';

echo $_SESSION['test'];