<?php
declare(strict_types=1);

use App\Console\Migration;

return new class extends Migration
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS admins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                google_sub VARCHAR(64) NULL UNIQUE,
                email VARCHAR(191) NOT NULL UNIQUE,
                name VARCHAR(100) NOT NULL,
                avatar_url VARCHAR(255) DEFAULT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                last_login_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS expenses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                category_id INT NOT NULL,
                amount BIGINT UNSIGNED NOT NULL,
                description VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec('CREATE INDEX idx_expenses_created_at ON expenses(created_at)');
        $pdo->exec('CREATE INDEX idx_expenses_category_created_at ON expenses(category_id, created_at)');

        $pdo->exec(
            "INSERT INTO categories (name) VALUES
            ('makan'),
            ('transport'),
            ('belanja'),
            ('hiburan'),
            ('tagihan'),
            ('lainnya'),
            ('laundry'),
            ('minum')
            ON DUPLICATE KEY UPDATE name = name"
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS expenses');
        $pdo->exec('DROP TABLE IF EXISTS admins');
        $pdo->exec('DROP TABLE IF EXISTS categories');
    }
};
