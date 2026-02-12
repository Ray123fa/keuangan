<?php
declare(strict_types=1);

use App\Console\Seeder;

return new class extends Seeder
{
    public function run(\PDO $pdo): void
    {
        $pdo->exec(
            "INSERT INTO admins (id, google_sub, email, name, avatar_url, is_active, last_login_at, created_at, updated_at) VALUES
            (1, '', 'rayhanfrdh123@gmail.com', 'Muhammad Rayhan Faridh', 'https://lh3.googleusercontent.com/a/ACg8ocK1ld_QUFbGLe2yApvmCLxBsR-NFBZrI79rhaWw7uMp_7XUKZT6=s96-c', 1, '2026-02-12 00:00:00', '2026-02-12 00:00:00', '2026-02-12 00:00:00')
            ON DUPLICATE KEY UPDATE
                google_sub = VALUES(google_sub),
                email = VALUES(email),
                name = VALUES(name),
                avatar_url = VALUES(avatar_url),
                is_active = VALUES(is_active),
                last_login_at = VALUES(last_login_at),
                created_at = VALUES(created_at),
                updated_at = VALUES(updated_at)"
        );
    }
};
