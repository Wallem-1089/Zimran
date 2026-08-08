<?php

declare(strict_types=1);

final class DatabaseSafety
{
    public const TEST_DATABASE_PATTERN = '/^hms_test_[a-z0-9_]+$/';

    public static function requireCli(): void
    {
        if (PHP_SAPI !== 'cli') {
            throw new RuntimeException('Database maintenance tools are CLI-only.');
        }
    }

    public static function resolveTestDatabase(array $appConfig): array
    {
        $live = trim((string)($appConfig['database']['name'] ?? ''));
        $test = trim((string)getenv('HMS_TEST_DB_NAME'));
        $environment = strtolower(trim((string)getenv('HMS_APP_ENV')));

        if ($live === '') {
            throw new RuntimeException('The application database name is empty.');
        }
        if ($test === '') {
            throw new RuntimeException('HMS_TEST_DB_NAME is required.');
        }
        if ($test === $live) {
            throw new RuntimeException('The test database must differ from the live database.');
        }
        if (!preg_match(self::TEST_DATABASE_PATTERN, $test)) {
            throw new RuntimeException('The test database must match hms_test_[a-z0-9_]+.');
        }
        if ($environment !== 'testing') {
            throw new RuntimeException('HMS_APP_ENV must explicitly be testing.');
        }

        return ['live' => $live, 'test' => $test];
    }

    public static function assertSafeSchema(string $sql, string $liveDatabase): void
    {
        $patterns = [
            '/\bDROP\s+DATABASE\b/i' => 'DROP DATABASE',
            '/\bCREATE\s+DATABASE\b/i' => 'CREATE DATABASE',
            '/\bUSE\s+[`\"]?' . preg_quote($liveDatabase, '/') . '[`\"]?\b/i'
                => 'hardcoded live database selection'
        ];

        foreach ($patterns as $pattern => $description) {
            if (preg_match($pattern, $sql)) {
                throw new RuntimeException(
                    'Schema preflight rejected ' . $description . '.'
                );
            }
        }
    }

    public static function requireDestructiveApproval(array $arguments): void
    {
        if (!in_array('--confirm-destructive-test-db', $arguments, true)) {
            throw new RuntimeException(
                'Pass --confirm-destructive-test-db to recreate a test database.'
            );
        }

        $backup = trim((string)getenv('HMS_VERIFIED_BACKUP_PATH'));
        $acknowledged = in_array('--ack-no-test-backup', $arguments, true);

        if ($backup !== '') {
            self::verifyBackup($backup);
            return;
        }

        if (!$acknowledged) {
            throw new RuntimeException(
                'A verified backup path or --ack-no-test-backup is required.'
            );
        }
    }

    public static function verifyBackup(string $path): array
    {
        $resolved = realpath($path);
        if ($resolved === false || !is_file($resolved) || !is_readable($resolved)) {
            throw new RuntimeException('The configured backup is not readable.');
        }
        $size = filesize($resolved);
        if ($size === false || $size <= 0) {
            throw new RuntimeException('The configured backup is empty.');
        }
        $sample = (string)file_get_contents($resolved, false, null, 0, 4096);
        if (!str_contains($sample, 'MariaDB dump')
            && !str_contains($sample, 'MySQL dump')
        ) {
            throw new RuntimeException('The configured file is not a recognized SQL dump.');
        }

        return [
            'path' => $resolved,
            'size' => $size,
            'created_at' => date(DATE_ATOM, (int)filectime($resolved))
        ];
    }

    public static function logOperation(
        string $target,
        string $reason,
        string $authorization
    ): void {
        $directory = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create the database operation log directory.');
        }
        $line = sprintf(
            "%s\ttarget=%s\treason=%s\tauthorization=%s%s",
            date(DATE_ATOM),
            $target,
            str_replace(["\r", "\n", "\t"], ' ', $reason),
            $authorization,
            PHP_EOL
        );
        if (file_put_contents(
            $directory . '/database_operations.log',
            $line,
            FILE_APPEND | LOCK_EX
        ) === false) {
            throw new RuntimeException('Unable to write the database operation log.');
        }
    }

    public static function quoteIdentifier(string $identifier): string
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $identifier)) {
            throw new RuntimeException('Unsafe database identifier.');
        }
        return '`' . $identifier . '`';
    }
}
