<?php
declare(strict_types=1);

use App\Console\Seeder;

return new class extends Seeder
{
    public function run(\PDO $pdo): void
    {
        $this->call('AdminSeeder');
        $this->call('CategorySeeder');
        $this->call('ExpenseSeeder');
    }
};
