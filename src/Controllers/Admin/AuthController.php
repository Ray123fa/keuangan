<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Infrastructure\Database\Connection;
use App\Repositories\AdminRepository;
use App\Services\GoogleOAuthService;
use RuntimeException;

final class AuthController
{
    private GoogleOAuthService $oauth;
    private AdminRepository $admins;

    public function __construct()
    {
        $this->oauth = new GoogleOAuthService();
        $this->admins = new AdminRepository(Connection::get());
    }

    public function showLogin(): void
    {
        if (Auth::check()) {
            header('Location: /admin');
            exit;
        }

        View::render('auth/login', [
            'error' => Session::consumeFlash('error'),
            'success' => Session::consumeFlash('success'),
        ], 'guest');
    }

    public function redirectToGoogle(): void
    {
        try {
            $url = $this->oauth->buildAuthUrl();
            header('Location: ' . $url);
            exit;
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            header('Location: /admin/login');
            exit;
        }
    }

    public function handleGoogleCallback(): void
    {
        $code = isset($_GET['code']) ? (string) $_GET['code'] : '';
        $state = isset($_GET['state']) ? (string) $_GET['state'] : null;

        if ($code === '') {
            Session::flash('error', 'Kode OAuth tidak ditemukan dari Google.');
            header('Location: /admin/login');
            exit;
        }

        try {
            $profile = $this->oauth->authenticate($code, $state);
            if (!$this->isAllowedEmail($profile['email'])) {
                throw new RuntimeException('Email kamu tidak masuk allowlist admin.');
            }

            $admin = $this->admins->upsertGoogleAdmin(
                $profile['sub'],
                $profile['email'],
                $profile['name'],
                $profile['avatar']
            );

            if (!isset($admin['is_active']) || (int) $admin['is_active'] !== 1) {
                throw new RuntimeException('Akun admin tidak aktif.');
            }

            Auth::login([
                'id' => (int) $admin['id'],
                'email' => (string) $admin['email'],
                'name' => (string) $admin['name'],
                'avatar' => isset($admin['avatar_url']) ? (string) $admin['avatar_url'] : '',
            ]);

            Session::flash('success', 'Login berhasil. Selamat datang, ' . (string) $admin['name'] . '.');
            header('Location: /admin');
            exit;
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            header('Location: /admin/login');
            exit;
        }
    }

    public function logout(): void
    {
        $token = isset($_POST['_token']) ? (string) $_POST['_token'] : null;
        if (!Csrf::validate($token)) {
            http_response_code(419);
            echo 'Token CSRF tidak valid.';
            return;
        }

        Auth::logout();
        Session::flash('success', 'Kamu sudah logout.');
        header('Location: /admin/login');
        exit;
    }

    private function isAllowedEmail(string $email): bool
    {
        $csv = envValue('ADMIN_ALLOWED_EMAILS', '');
        if ($csv === null || trim($csv) === '') {
            return false;
        }

        $allowed = array_map(
            static fn(string $item): string => strtolower(trim($item)),
            explode(',', $csv)
        );

        return in_array(strtolower($email), $allowed, true);
    }
}
