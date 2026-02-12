<?php
declare(strict_types=1);

namespace App\Console;

abstract class Seeder
{
    private ?SeederRunner $runner = null;

    final public function setRunner(SeederRunner $runner): void
    {
        $this->runner = $runner;
    }

    protected function call(string $seeder): void
    {
        if ($this->runner === null) {
            throw new \RuntimeException('Seeder runner belum diinisialisasi.');
        }

        $this->runner->runSeeder($seeder);
    }

    abstract public function run(\PDO $pdo): void;
}
