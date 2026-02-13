<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        $current = Session::get(self::SESSION_KEY);
        if (is_string($current) && $current !== '') {
            return $current;
        }

        $token = bin2hex(random_bytes(32));
        Session::put(self::SESSION_KEY, $token);

        return $token;
    }

    public static function validate(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $sessionToken = Session::get(self::SESSION_KEY);
        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        $valid = hash_equals($sessionToken, $token);

        if ($valid) {
            self::regenerate();
        }

        return $valid;
    }

    public static function regenerate(): void
    {
        Session::put(self::SESSION_KEY, bin2hex(random_bytes(32)));
    }
}
