<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../../../database/tools/DatabaseSafety.php';

requireSecurityAdministrator();

$projectRoot = dirname(__DIR__, 3);
$backupDirectory = $projectRoot . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'backups';
$appConfig = require __DIR__ . '/../../../config/app.php';
$databaseConfig = $appConfig['database'] ?? [];
$databaseName = (string)($databaseConfig['name'] ?? '');
$messages = [];
$errors = [];

function databaseBackupFormatBytes(int $bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }

    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }

    return $bytes . ' bytes';
}

function databaseBackupResolveDumpCommand(): string
{
    $configuredPath = trim((string)getenv('HMS_MYSQLDUMP_PATH'));
    if ($configuredPath !== '') {
        return $configuredPath;
    }

    if (PHP_OS_FAMILY === 'Windows') {
        $xamppDump = 'C:' . DIRECTORY_SEPARATOR . 'xampp'
            . DIRECTORY_SEPARATOR . 'mysql'
            . DIRECTORY_SEPARATOR . 'bin'
            . DIRECTORY_SEPARATOR . 'mysqldump.exe';

        if (is_file($xamppDump)) {
            return $xamppDump;
        }
    }

    return 'mysqldump';
}

function databaseBackupListRecent(string $backupDirectory): array
{
    if (!is_dir($backupDirectory)) {
        return [];
    }

    $files = glob($backupDirectory . DIRECTORY_SEPARATOR . '*.sql') ?: [];
    usort(
        $files,
        static fn (string $a, string $b): int => (int)filemtime($b) <=> (int)filemtime($a)
    );

    return array_map(
        static function (string $path): array {
            return [
                'filename' => basename($path),
                'size' => databaseBackupFormatBytes((int)filesize($path)),
                'created_at' => date('Y-m-d H:i:s', (int)filemtime($path))
            ];
        },
        array_slice($files, 0, 15)
    );
}

function databaseBackupCreate(
    string $backupDirectory,
    string $databaseName,
    array $databaseConfig,
    string $projectRoot
): array {
    if ($databaseName === '') {
        return ['success' => false, 'errors' => ['Database name is not configured.']];
    }

    if (!function_exists('proc_open')) {
        return ['success' => false, 'errors' => ['PHP proc_open is disabled, so the browser backup tool cannot run mysqldump.']];
    }

    if (!is_dir($backupDirectory)
        && !mkdir($backupDirectory, 0770, true)
        && !is_dir($backupDirectory)
    ) {
        return ['success' => false, 'errors' => ['Unable to create the backup directory.']];
    }

    if (!is_writable($backupDirectory)) {
        return ['success' => false, 'errors' => ['The backup directory is not writable by PHP/Apache.']];
    }

    $safeDatabaseName = preg_replace('/[^a-zA-Z0-9_]+/', '_', $databaseName) ?: 'database';
    $filename = sprintf(
        'admin_backup_%s_%s.sql',
        $safeDatabaseName,
        date('Ymd_His')
    );
    $backupPath = $backupDirectory . DIRECTORY_SEPARATOR . $filename;
    $dumpCommand = databaseBackupResolveDumpCommand();

    $command = [
        $dumpCommand,
        '--host=' . (string)($databaseConfig['host'] ?? 'localhost'),
        '--user=' . (string)($databaseConfig['user'] ?? ''),
        '--single-transaction',
        '--routines',
        '--triggers',
        '--default-character-set=utf8mb4',
        '--result-file=' . $backupPath,
        $databaseName
    ];

    $password = (string)($databaseConfig['pass'] ?? '');
    if ($password !== '') {
        array_splice($command, 3, 0, ['--password=' . $password]);
    }

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];

    $process = @proc_open($command, $descriptors, $pipes, $projectRoot);
    if (!is_resource($process)) {
        return ['success' => false, 'errors' => ['Unable to start mysqldump. Configure HMS_MYSQLDUMP_PATH if needed.']];
    }

    fclose($pipes[0]);
    $standardOutput = stream_get_contents($pipes[1]) ?: '';
    $standardError = stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);
    if ($exitCode !== 0) {
        if (is_file($backupPath) && filesize($backupPath) === 0) {
            @unlink($backupPath);
        }

        $message = trim($standardError) !== ''
            ? trim($standardError)
            : trim($standardOutput);

        return [
            'success' => false,
            'errors' => [
                'mysqldump failed. Confirm MySQL is running and HMS_MYSQLDUMP_PATH points to mysqldump.',
                $message !== '' ? $message : 'Exit code: ' . (string)$exitCode
            ]
        ];
    }

    try {
        $verified = DatabaseSafety::verifyBackup($backupPath);
    } catch (Throwable $exception) {
        return ['success' => false, 'errors' => [$exception->getMessage()]];
    }

    return [
        'success' => true,
        'filename' => basename($verified['path']),
        'size' => databaseBackupFormatBytes((int)$verified['size']),
        'created_at' => $verified['created_at']
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    if (($_POST['action'] ?? '') !== 'create_backup') {
        $_SESSION['administration_errors'] = ['Invalid database safety action.'];
        header('Location: database_backup.php');
        exit;
    }

    $result = databaseBackupCreate($backupDirectory, $databaseName, $databaseConfig, $projectRoot);

    if (($result['success'] ?? false) === true) {
        $filename = (string)($result['filename'] ?? 'backup.sql');
        $_SESSION['success_message'] = sprintf(
            'Database backup created: %s (%s).',
            $filename,
            (string)($result['size'] ?? 'size unknown')
        );

        try {
            DatabaseSafety::logOperation(
                $databaseName,
                'Manual browser database backup created: ' . $filename,
                'administrator_user_id=' . (string)($currentUser['id'] ?? '')
            );
        } catch (Throwable $exception) {
            $_SESSION['administration_errors'] = [
                'Backup was created, but writing the database operation log failed: '
                . $exception->getMessage()
            ];
        }

        $securityAuditService->log(
            (int)($currentUser['id'] ?? 0),
            null,
            'Administration',
            'DATABASE_BACKUP_CREATED',
            'Database backup created: ' . $filename,
            $_SESSION['active_department_id'] ?? null,
            'INFO',
            'DATABASE_BACKUP_CREATED'
        );
    } else {
        $_SESSION['administration_errors'] = $result['errors'] ?? ['Unable to create database backup.'];
    }

    header('Location: database_backup.php');
    exit;
}

$successMessage = $_SESSION['success_message'] ?? null;
$errors = $_SESSION['administration_errors'] ?? [];
unset($_SESSION['success_message'], $_SESSION['administration_errors']);

$recentBackups = databaseBackupListRecent($backupDirectory);
$pageTitle = 'Database Safety';

require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';
?>

<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>

    <section class="card">
        <div class="card-header">
            <div>
                <h2>Database Safety</h2>
                <p>Create a verified SQL backup before migrations, risky data fixes, or deployment work.</p>
            </div>
            <a class="btn-secondary" href="dashboard.php">Security Dashboard</a>
        </div>

        <?php if ($successMessage): ?>
            <div class="alert-success"><?= e((string)$successMessage) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert-danger">
                <ul>
                    <?php foreach ((array)$errors as $error): ?>
                        <li><?= e((string)$error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="stats">
            <div class="card">
                <h3>Backup Database</h3>
                <p>
                    This creates a timestamped <code>.sql</code> dump in
                    <code>database/backups/</code> and verifies that the file is readable.
                </p>
                <form method="POST" action="database_backup.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="create_backup">
                    <button class="btn-primary" type="submit">Create Backup</button>
                </form>
            </div>

            <div class="card">
                <h3>Restore Drill Guidance</h3>
                <p>
                    Browser restore is intentionally not available. To test a backup, restore it into a
                    separate database such as <code>hms_restore_test</code>, verify important tables, then
                    discard the test database when finished.
                </p>
                <p>
                    Never restore over the live database from the browser.
                </p>
            </div>
        </div>
    </section>

    <section class="card">
        <h3>Recent Backups</h3>
        <?php if ($recentBackups === []): ?>
            <p>No SQL backups found in <code>database/backups/</code>.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Size</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentBackups as $backup): ?>
                        <tr>
                            <td><code><?= e($backup['filename']) ?></code></td>
                            <td><?= e($backup['size']) ?></td>
                            <td><?= e($backup['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
