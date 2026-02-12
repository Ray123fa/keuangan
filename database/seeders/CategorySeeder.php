<?php
declare(strict_types=1);

use App\Console\Seeder;

return new class extends Seeder
{
    public function run(\PDO $pdo): void
    {
        $pdo->exec(
            "INSERT INTO categories (id, name, created_at) VALUES
            (1, 'makan', '2026-02-12 00:00:00'),
            (2, 'transport', '2026-02-12 00:00:00'),
            (3, 'belanja', '2026-02-12 00:00:00'),
            (4, 'hiburan', '2026-02-12 00:00:00'),
            (5, 'tagihan', '2026-02-12 00:00:00'),
            (6, 'lainnya', '2026-02-12 00:00:00'),
            (7, 'laundry', '2026-02-12 00:00:00'),
            (8, 'minum', '2026-02-12 00:00:00')
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                created_at = VALUES(created_at)"
        );
    }
};
