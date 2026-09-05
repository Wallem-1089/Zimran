<?php

declare(strict_types=1);

require_once __DIR__ . '/DatabaseSafety.php';

DatabaseSafety::requireCli();

$label = preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string)($argv[1] ?? 'manual'));
$label = trim((string)$label, '_') ?: 'manual';

$config = require dirname(__DIR__, 2) . '/config/app.php';
$database = $config['database'];
$projectRoot = dirname(__DIR__, 2);
$backupDirectory = $projectRoot . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'backups';

if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0770, true) && !is_dir($backupDirectory)) {
    throw new RuntimeException('Unable to create backup directory.');
}

$filename = sprintf('%s_%s.sql', $label, date('Ymd_His'));
$backupPath = $backupDirectory . DIRECTORY_SEPARATOR . $filename;
$mysqldump = (string)getenv('HMS_MYSQLDUMP_PATH');
if ($mysqldump === '') {
    $xamppPath = 'C:' . DIRECTORY_SEPARATOR . 'xampp' . DIRECTORY_SEPARATOR . 'mysql' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'mysqldump.exe';
    $mysqldump = is_file($xamppPath) ? $xamppPath : 'mysqldump';
}

$command = [
    $mysqldump,
    '--host=' . (string)$database['host'],
    '--user=' . (string)$database['user'],
    '--result-file=' . $backupPath,
    (string)$database['name'],
];

if ((string)($database['pass'] ?? '') !== '') {
    array_splice($command, 3, 0, '--password=' . (string)$database['pass']);
}

$descriptorSpec = [
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

$process = proc_open($command, $descriptorSpec, $pipes, $projectRoot);
if (!is_resource($process)) {
    throw new RuntimeException('Unable to start mysqldump.');
}

$stderr = stream_get_contents($pipes[2]);
$exitCode = proc_close($process);
if ($exitCode !== 0) {
    throw new RuntimeException('mysqldump failed. ' . trim((string)$stderr));
}

$backup = DatabaseSafety::verifyBackup($backupPath);
echo $backup['path'] . PHP_EOL;
