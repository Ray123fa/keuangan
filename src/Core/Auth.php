<?php
declare(strict_types=1);

namespace App\Core;

use App\Infrastructure\Database\Connection;
use App\Repositories\AdminRepository;

final class Auth
{
    private const ADMIN_KEY = 'admin_user';

    public static function login(array $admin): void
    {
        session_regenerate_id(true);
        Session::put(self::ADMIN_KEY, $admin);
    }

    public static function logout(): void
    {
        Session::forget(self::ADMIN_KEY);
    }

    public static function user(): ?array
    {
        $user = Session::get(self::ADMIN_KEY);
        if (!is_array($user)) {
            return null;
        }

        $id = isset($user['id']) ? (int) $user['id'] : 0;
        if ($id <= 0) {
            self::logout();
            return null;
        }

        $admins = new AdminRepository(Connection::get());
        $activeAdmin = $admins->findActiveById($id);
        if ($activeAdmin === null) {
            self::logout();
            Session::flash('error', 'Sesi login kamu sudah tidak valid. Silakan login lagi.');
            return null;
        }

        $normalizedUser = [
            'id' => (int) $activeAdmin['id'],
            'email' => (string) $activeAdmin['email'],
            'name' => (string) $activeAdmin['name'],
            'avatar' => isset($activeAdmin['avatar_url']) ? (string) $activeAdmin['avatar_url'] : '',
        ];

        Session::put(self::ADMIN_KEY, $normalizedUser);

        return $normalizedUser;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function requireAdmin(): void
    {
        if (self::check()) {
            return;
        }

        header('Location: /admin/login');
        exit;
    }
}
