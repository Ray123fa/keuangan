<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AdminRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function upsertGoogleAdmin(string $googleSub, string $email, string $name, ?string $avatarUrl): array
    {
        $existing = $this->findByGoogleSubOrEmail($googleSub, $email);

        if ($existing === null) {
            $stmt = $this->db->prepare(
                'INSERT INTO admins (google_sub, email, name, avatar_url, is_active, last_login_at) VALUES (?, ?, ?, ?, 1, NOW())'
            );
            $stmt->execute([$googleSub, $email, $name, $avatarUrl]);

            return $this->findById((int) $this->db->lastInsertId()) ?? [];
        }

        $stmt = $this->db->prepare(
            'UPDATE admins SET google_sub = ?, email = ?, name = ?, avatar_url = ?, last_login_at = NOW(), updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$googleSub, $email, $name, $avatarUrl, (int) $existing['id']]);

        return $this->findById((int) $existing['id']) ?? [];
    }

    public function findByGoogleSubOrEmail(string $googleSub, string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM admins WHERE google_sub = ? OR email = ? LIMIT 1');
        $stmt->execute([$googleSub, $email]);
        $admin = $stmt->fetch();

        return is_array($admin) ? $admin : null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM admins WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $admin = $stmt->fetch();

        return is_array($admin) ? $admin : null;
    }

    public function findActiveById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM admins WHERE id = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$id]);
        $admin = $stmt->fetch();

        return is_array($admin) ? $admin : null;
    }
}
