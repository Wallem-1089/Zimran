<?php

declare(strict_types=1);

require_once __DIR__ . '/DatabaseSafety.php';

final class MigrationManager
{
    public function __construct(private PDO $pdo, private string $databaseName)
    {
    }

    public function ensureLedger(): void
    {
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS schema_migrations (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                migration_name VARCHAR(255) NOT NULL,
                checksum CHAR(64) NOT NULL,
                batch INT NOT NULL,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                execution_time_ms INT NOT NULL DEFAULT 0,
                CONSTRAINT uq_schema_migrations_name UNIQUE (migration_name),
                INDEX idx_schema_migrations_batch (batch, applied_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function recordRepresented(string $migrationPath, int $batch = 1): void
    {
        $this->record($migrationPath, $batch, 0);
    }

    public function apply(string $migrationPath, int $batch): void
    {
        $name = basename($migrationPath);
        $checksum = hash_file('sha256', $migrationPath);
        $existing = $this->find($name);
        if ($existing) {
            if (!hash_equals((string)$existing['checksum'], (string)$checksum)) {
                throw new RuntimeException('Checksum changed for applied migration ' . $name . '.');
            }
            return;
        }

        $sql = (string)file_get_contents($migrationPath);
        DatabaseSafety::assertSafeSchema($sql, $this->databaseName);
        $started = microtime(true);
        $this->pdo->exec($sql);
        $elapsed = (int)round((microtime(true) - $started) * 1000);
        $this->record($migrationPath, $batch, $elapsed);
    }

    public function status(array $migrationPaths): array
    {
        $rows = $this->pdo->query(
            'SELECT migration_name, checksum, batch, applied_at, execution_time_ms
             FROM schema_migrations ORDER BY migration_name'
        )->fetchAll(PDO::FETCH_ASSOC);
        $applied = array_column($rows, null, 'migration_name');
        $status = [];
        foreach ($migrationPaths as $path) {
            $name = basename($path);
            $checksum = hash_file('sha256', $path);
            $row = $applied[$name] ?? null;
            $status[] = [
                'migration' => $name,
                'status' => !$row ? 'Pending'
                    : (hash_equals((string)$row['checksum'], (string)$checksum)
                        ? 'Applied' : 'Checksum mismatch'),
                'batch' => $row['batch'] ?? null,
                'applied_at' => $row['applied_at'] ?? null
            ];
        }
        return $status;
    }

    private function record(string $migrationPath, int $batch, int $elapsed): void
    {
        $name = basename($migrationPath);
        $checksum = hash_file('sha256', $migrationPath);
        if ($checksum === false) {
            throw new RuntimeException('Unable to checksum ' . $name . '.');
        }
        $existing = $this->find($name);
        if ($existing) {
            if (!hash_equals((string)$existing['checksum'], $checksum)) {
                throw new RuntimeException('Checksum changed for ' . $name . '.');
            }
            return;
        }
        $stmt = $this->pdo->prepare('
            INSERT INTO schema_migrations (
                migration_name, checksum, batch, execution_time_ms
            ) VALUES (:name, :checksum, :batch, :execution_time_ms)
        ');
        $stmt->execute([
            ':name' => $name,
            ':checksum' => $checksum,
            ':batch' => $batch,
            ':execution_time_ms' => $elapsed
        ]);
    }

    private function find(string $name): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM schema_migrations WHERE migration_name = :name'
        );
        $stmt->execute([':name' => $name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
