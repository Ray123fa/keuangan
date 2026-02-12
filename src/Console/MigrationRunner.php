<?php
declare(strict_types=1);

namespace App\Console;

use PDO;
use RuntimeException;
use Throwable;

final class MigrationRunner
{
    private const MIGRATIONS_TABLE = 'migrations';

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $migrationPath
    ) {
    }

    public function makeMigration(string $name): void
    {
        $normalized = $this->normalizeMigrationName($name);
        if ($normalized === '') {
            throw new RuntimeException('Nama migration tidak valid. Gunakan huruf, angka, underscore, atau dash.');
        }

        $this->ensureMigrationDirectory();

        $timestamp = date('Y_m_d_His');
        $baseFileName = $timestamp . '_' . $normalized;
        $fileName = $baseFileName . '.php';
        $counter = 1;

        while (file_exists($this->migrationPath . DIRECTORY_SEPARATOR . $fileName)) {
            $fileName = $baseFileName . '_' . $counter . '.php';
            $counter++;
        }

        $targetPath = $this->migrationPath . DIRECTORY_SEPARATOR . $fileName;
        $template = $this->migrationTemplate();

        if (file_put_contents($targetPath, $template) === false) {
            throw new RuntimeException('Gagal membuat file migration: ' . $targetPath);
        }

        echo 'Created migration: database/migrations/' . $fileName . PHP_EOL;
    }

    public function migrate(): void
    {
        $this->ensureMigrationDirectory();
        $this->ensureMigrationsTable();

        $allFiles = $this->getMigrationFiles();
        if ($allFiles === []) {
            echo "No migration files found.\n";
            return;
        }

        $applied = $this->getAppliedMigrations();
        $pending = array_values(array_filter(
            $allFiles,
            static fn (string $fileName): bool => !isset($applied[$fileName])
        ));

        if ($pending === []) {
            echo "Nothing to migrate.\n";
            return;
        }

        $batch = $this->nextBatchNumber();

        foreach ($pending as $fileName) {
            $this->runSingleMigration($fileName, $batch);
        }

        echo 'Migrated ' . count($pending) . " file(s).\n";
    }

    public function rollback(): void
    {
        $this->ensureMigrationsTable();

        $batch = $this->latestBatchNumber();
        if ($batch === null) {
            echo "Nothing to rollback.\n";
            return;
        }

        $statement = $this->pdo->prepare(
            'SELECT migration FROM ' . self::MIGRATIONS_TABLE . ' WHERE batch = :batch ORDER BY id DESC'
        );
        $statement->execute(['batch' => $batch]);

        $migrations = $statement->fetchAll(PDO::FETCH_COLUMN);
        if (!is_array($migrations) || $migrations === []) {
            echo "Nothing to rollback.\n";
            return;
        }

        foreach ($migrations as $migrationName) {
            if (!is_string($migrationName)) {
                continue;
            }

            $this->rollbackSingleMigration($migrationName);
        }

        echo 'Rolled back batch #' . $batch . ' (' . count($migrations) . " file(s)).\n";
    }

    public function fresh(): void
    {
        $this->dropAllTables();
        echo "Dropped all tables.\n";
        $this->migrate();
    }

    public function status(): void
    {
        $this->ensureMigrationDirectory();
        $this->ensureMigrationsTable();

        $files = $this->getMigrationFiles();
        $rows = $this->pdo
            ->query('SELECT migration, batch, applied_at FROM ' . self::MIGRATIONS_TABLE . ' ORDER BY id ASC')
            ->fetchAll(PDO::FETCH_ASSOC);

        $applied = [];
        foreach ($rows as $row) {
            if (!isset($row['migration']) || !is_string($row['migration'])) {
                continue;
            }

            $applied[$row['migration']] = [
                'batch' => $row['batch'] ?? null,
                'applied_at' => $row['applied_at'] ?? null,
            ];
        }

        if ($files === []) {
            echo "No migration files found.\n";
        } else {
            echo "Migration status:\n";
            foreach ($files as $fileName) {
                $isApplied = isset($applied[$fileName]);
                $state = $isApplied ? 'Y' : 'N';
                $batch = $isApplied ? (string) ($applied[$fileName]['batch'] ?? '-') : '-';
                $appliedAt = $isApplied ? (string) ($applied[$fileName]['applied_at'] ?? '-') : '-';

                echo sprintf('[%s] %s | batch: %s | applied_at: %s', $state, $fileName, $batch, $appliedAt) . PHP_EOL;
            }
        }

        $missingFiles = [];
        foreach (array_keys($applied) as $migrationName) {
            if (!in_array($migrationName, $files, true)) {
                $missingFiles[] = $migrationName;
            }
        }

        if ($missingFiles !== []) {
            echo "\nWarning: migration record exists but file is missing:\n";
            foreach ($missingFiles as $migrationName) {
                echo '- ' . $migrationName . PHP_EOL;
            }
        }
    }

    private function runSingleMigration(string $fileName, int $batch): void
    {
        $migration = $this->loadMigration($fileName);

        try {
            $migration->up($this->pdo);

            $statement = $this->pdo->prepare(
                'INSERT INTO ' . self::MIGRATIONS_TABLE . ' (migration, batch) VALUES (:migration, :batch)'
            );
            $statement->execute([
                'migration' => $fileName,
                'batch' => $batch,
            ]);
        } catch (Throwable $throwable) {
            throw new RuntimeException('Migration failed [' . $fileName . ']: ' . $throwable->getMessage(), 0, $throwable);
        }

        echo 'Migrated: ' . $fileName . PHP_EOL;
    }

    private function rollbackSingleMigration(string $fileName): void
    {
        $migration = $this->loadMigration($fileName);

        try {
            $migration->down($this->pdo);

            $statement = $this->pdo->prepare(
                'DELETE FROM ' . self::MIGRATIONS_TABLE . ' WHERE migration = :migration LIMIT 1'
            );
            $statement->execute(['migration' => $fileName]);
        } catch (Throwable $throwable) {
            throw new RuntimeException('Rollback failed [' . $fileName . ']: ' . $throwable->getMessage(), 0, $throwable);
        }

        echo 'Rolled back: ' . $fileName . PHP_EOL;
    }

    private function ensureMigrationDirectory(): void
    {
        if (is_dir($this->migrationPath)) {
            return;
        }

        if (!mkdir($this->migrationPath, 0755, true) && !is_dir($this->migrationPath)) {
            throw new RuntimeException('Gagal membuat folder migration: ' . $this->migrationPath);
        }
    }

    private function ensureMigrationsTable(): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS ' . self::MIGRATIONS_TABLE . ' (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            batch INT NOT NULL,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        $this->pdo->exec($sql);
    }

    private function dropAllTables(): void
    {
        $databaseName = $this->pdo->query('SELECT DATABASE()')->fetchColumn();
        if (!is_string($databaseName) || $databaseName === '') {
            throw new RuntimeException('Tidak bisa mendeteksi nama database aktif.');
        }

        $statement = $this->pdo->prepare(
            'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_TYPE = :type'
        );
        $statement->execute([
            'schema' => $databaseName,
            'type' => 'BASE TABLE',
        ]);

        $tables = $statement->fetchAll(PDO::FETCH_COLUMN);
        if (!is_array($tables) || $tables === []) {
            return;
        }

        $quotedTables = [];
        foreach ($tables as $table) {
            if (!is_string($table) || $table === '') {
                continue;
            }

            $quotedTables[] = '`' . str_replace('`', '``', $table) . '`';
        }

        if ($quotedTables === []) {
            return;
        }

        try {
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            $this->pdo->exec('DROP TABLE IF EXISTS ' . implode(', ', $quotedTables));
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        } catch (Throwable $throwable) {
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            throw new RuntimeException('Gagal drop semua tabel saat migrate:fresh: ' . $throwable->getMessage(), 0, $throwable);
        }
    }

    /**
     * @return array<string>
     */
    private function getMigrationFiles(): array
    {
        $pattern = $this->migrationPath . DIRECTORY_SEPARATOR . '*.php';
        $matches = glob($pattern);
        if ($matches === false || $matches === []) {
            return [];
        }

        $fileNames = array_map('basename', $matches);
        sort($fileNames);

        return array_values($fileNames);
    }

    /**
     * @return array<string, bool>
     */
    private function getAppliedMigrations(): array
    {
        $rows = $this->pdo->query('SELECT migration FROM ' . self::MIGRATIONS_TABLE)->fetchAll(PDO::FETCH_COLUMN);
        if (!is_array($rows)) {
            return [];
        }

        $applied = [];
        foreach ($rows as $value) {
            if (is_string($value)) {
                $applied[$value] = true;
            }
        }

        return $applied;
    }

    private function nextBatchNumber(): int
    {
        $max = $this->pdo->query('SELECT COALESCE(MAX(batch), 0) FROM ' . self::MIGRATIONS_TABLE)->fetchColumn();
        return ((int) $max) + 1;
    }

    private function latestBatchNumber(): ?int
    {
        $max = $this->pdo->query('SELECT MAX(batch) FROM ' . self::MIGRATIONS_TABLE)->fetchColumn();
        $value = (int) $max;

        return $value > 0 ? $value : null;
    }

    private function loadMigration(string $fileName): Migration
    {
        $fullPath = $this->migrationPath . DIRECTORY_SEPARATOR . $fileName;
        if (!file_exists($fullPath)) {
            throw new RuntimeException('Migration file tidak ditemukan: ' . $fileName);
        }

        $instance = require $fullPath;
        if (!$instance instanceof Migration) {
            throw new RuntimeException('Migration file harus me-return instance dari App\\Console\\Migration: ' . $fileName);
        }

        return $instance;
    }

    private function normalizeMigrationName(string $rawName): string
    {
        $name = strtolower(trim($rawName));
        $name = preg_replace('/[^a-z0-9_-]+/', '_', $name) ?? '';
        $name = trim($name, '_-');

        return $name;
    }

    private function migrationTemplate(): string
    {
        return <<<'PHP'
<?php
declare(strict_types=1);

use App\Console\Migration;

return new class extends Migration
{
    public function up(\PDO $pdo): void
    {
        // TODO: Implement up migration.
    }

    public function down(\PDO $pdo): void
    {
        // TODO: Implement down migration.
    }
};
PHP;
    }
}
