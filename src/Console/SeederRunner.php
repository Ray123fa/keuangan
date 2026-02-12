<?php
declare(strict_types=1);

namespace App\Console;

use PDO;
use RuntimeException;
use Throwable;

final class SeederRunner
{
    /**
     * @var array<string, bool>
     */
    private array $running = [];

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $seedPath
    ) {
    }

    public function makeSeeder(string $name): void
    {
        $class = $this->normalizeSeederClass($name);
        $this->ensureSeedDirectory();

        $targetPath = $this->seedPath . DIRECTORY_SEPARATOR . $class . '.php';
        if (file_exists($targetPath)) {
            throw new RuntimeException('Seeder sudah ada: database/seeders/' . $class . '.php');
        }

        if (file_put_contents($targetPath, $this->seederTemplate()) === false) {
            throw new RuntimeException('Gagal membuat file seeder: ' . $targetPath);
        }

        echo 'Created seeder: database/seeders/' . $class . '.php' . PHP_EOL;
    }

    public function seed(?string $class = null): void
    {
        $targetClass = $class !== null && trim($class) !== ''
            ? $this->normalizeSeederClass($class)
            : 'DatabaseSeeder';

        $this->runSeeder($targetClass);
        echo 'Seeding finished.' . PHP_EOL;
    }

    public function runSeeder(string $class): void
    {
        $normalizedClass = $this->normalizeSeederClass($class);

        if (isset($this->running[$normalizedClass])) {
            throw new RuntimeException('Circular seeder dependency terdeteksi: ' . $normalizedClass);
        }

        $seeder = $this->loadSeeder($normalizedClass);

        try {
            $this->running[$normalizedClass] = true;
            echo 'Seeding: ' . $normalizedClass . PHP_EOL;
            $seeder->setRunner($this);
            $seeder->run($this->pdo);
            echo 'Seeded: ' . $normalizedClass . PHP_EOL;
        } catch (Throwable $throwable) {
            throw new RuntimeException('Seeder failed [' . $normalizedClass . ']: ' . $throwable->getMessage(), 0, $throwable);
        } finally {
            unset($this->running[$normalizedClass]);
        }
    }

    private function loadSeeder(string $class): Seeder
    {
        $this->ensureSeedDirectory();

        $fullPath = $this->seedPath . DIRECTORY_SEPARATOR . $class . '.php';
        if (!file_exists($fullPath)) {
            throw new RuntimeException('Seeder file tidak ditemukan: database/seeders/' . $class . '.php');
        }

        $instance = require $fullPath;
        if (!$instance instanceof Seeder) {
            throw new RuntimeException('Seeder file harus me-return instance dari App\\Console\\Seeder: ' . $class . '.php');
        }

        return $instance;
    }

    private function ensureSeedDirectory(): void
    {
        if (is_dir($this->seedPath)) {
            return;
        }

        if (!mkdir($this->seedPath, 0755, true) && !is_dir($this->seedPath)) {
            throw new RuntimeException('Gagal membuat folder seeder: ' . $this->seedPath);
        }
    }

    private function normalizeSeederClass(string $rawName): string
    {
        $name = trim($rawName);
        $name = preg_replace('/\.php$/i', '', $name) ?? '';
        if ($name === '') {
            throw new RuntimeException('Nama seeder tidak valid.');
        }

        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) === 1) {
            $class = $name;
        } else {
            $sanitized = preg_replace('/[^A-Za-z0-9_]+/', ' ', $name) ?? '';
            $parts = preg_split('/\s+/', trim($sanitized)) ?: [];
            $class = '';

            foreach ($parts as $part) {
                $class .= ucfirst(strtolower($part));
            }
        }

        if ($class === '') {
            throw new RuntimeException('Nama seeder tidak valid.');
        }

        if (preg_match('/Seeder$/i', $class) !== 1) {
            $class .= 'Seeder';
        }

        return $class;
    }

    private function seederTemplate(): string
    {
        return <<<'PHP'
<?php
declare(strict_types=1);

use App\Console\Seeder;

return new class extends Seeder
{
    public function run(\PDO $pdo): void
    {
        // TODO: Implement seeder.
    }
};
PHP;
    }
}
