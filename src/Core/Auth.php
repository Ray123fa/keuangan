<?php
declare(strict_types=1);

namespace App\Core;

use App\Infrastructure\Database\Connection;
use App\Repositories\AdminRepository;

final class Auth
{
    private const ADMIN_KEY = 'admin_user';
    private const ISSUED_AT_KEY = 'auth_issued_at';
    private const LAST_ACTIVITY_AT_KEY = 'auth_last_activity_at';
    private const EXPIRES_AT_KEY = 'auth_expires_at';

    public static function login(array $admin): void
    {
        $now = time();
        session_regenerate_id(true);
        Session::put(self::ADMIN_KEY, $admin);
        Session::put(self::ISSUED_AT_KEY, $now);
        Session::put(self::LAST_ACTIVITY_AT_KEY, $now);
        Session::put(self::EXPIRES_AT_KEY, $now + (SESSION_ABSOLUTE_TIMEOUT_MINUTES * 60));
    }

    public static function logout(): void
    {
        Session::forget(self::ADMIN_KEY);
        Session::forget(self::ISSUED_AT_KEY);
        Session::forget(self::LAST_ACTIVITY_AT_KEY);
        Session::forget(self::EXPIRES_AT_KEY);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function user(): ?array
    {
        $user = Session::get(self::ADMIN_KEY);
        if (!is_array($user)) {
            return null;
        }

        $now = time();
        if (self::isSessionExpired($now)) {
            self::logout();
            Session::flash('error', 'Sesi login kamu sudah berakhir. Silakan login lagi.');
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
        Session::put(self::LAST_ACTIVITY_AT_KEY, $now);

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

    private static function isSessionExpired(int $now): bool
    {
        $issuedAt = self::timestampFromSession(self::ISSUED_AT_KEY);
        $lastActivityAt = self::timestampFromSession(self::LAST_ACTIVITY_AT_KEY);
        $expiresAt = self::timestampFromSession(self::EXPIRES_AT_KEY);

        if ($issuedAt === null || $lastActivityAt === null || $expiresAt === null) {
            return true;
        }

        $idleLimitSeconds = SESSION_IDLE_TIMEOUT_MINUTES * 60;
        if (($now - $lastActivityAt) > $idleLimitSeconds) {
            return true;
        }

        return $now > $expiresAt;
    }

    private static function timestampFromSession(string $key): ?int
    {
        $value = Session::get($key);

        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            $timestamp = (int) $value;
            return $timestamp > 0 ? $timestamp : null;
        }

        return null;
    }
}
