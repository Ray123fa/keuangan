<?php
declare(strict_types=1);

use App\Console\Seeder;

return new class extends Seeder
{
    public function run(\PDO $pdo): void
    {
        $pdo->exec(
            "INSERT INTO expenses (id, category_id, amount, description, created_at) VALUES
            (1, 1, 17000, 'nasi uduk', '2026-02-08 00:00:00'),
            (2, 1, 35500, 'geprek+nasgor', '2026-02-08 00:00:00'),
            (3, 1, 56955, 'makdura', '2026-02-08 00:00:00'),
            (4, 1, 9000, 'nasi uduk', '2026-02-09 00:00:00'),
            (5, 1, 13000, 'naspad', '2026-02-09 00:00:00'),
            (6, 1, 35500, 'ketoprak', '2026-02-09 00:00:00'),
            (7, 1, 14000, 'nasi uduk', '2026-02-10 00:00:00'),
            (8, 8, 7000, 'milky mango', '2026-02-10 00:00:00'),
            (9, 1, 21000, 'warteg', '2026-02-10 00:00:00'),
            (10, 1, 20000, 'ayam madura', '2026-02-10 00:00:00'),
            (11, 1, 5000, 'bakwan', '2026-02-10 00:00:00'),
            (12, 1, 23000, 'Warteg', '2026-02-11 00:00:00'),
            (13, 7, 31200, 'Laundry', '2026-02-11 00:00:00'),
            (14, 8, 13000, 'Jus Alpukat', '2026-02-11 00:00:00'),
            (15, 1, 55955, 'Makdura', '2026-02-11 00:00:00')
            ON DUPLICATE KEY UPDATE
                category_id = VALUES(category_id),
                amount = VALUES(amount),
                description = VALUES(description),
                created_at = VALUES(created_at)"
        );
    }
};
