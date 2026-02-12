<?php
declare(strict_types=1);

namespace App\Services;

use Google\Client;
use RuntimeException;

final class GoogleOAuthService
{
    private const STATE_KEY = 'google_oauth_state';

    public function buildAuthUrl(): string
    {
        $client = $this->buildClient();
        $state = bin2hex(random_bytes(24));
        $_SESSION[self::STATE_KEY] = $state;
        $client->setState($state);

        return $client->createAuthUrl();
    }

    public function authenticate(string $code, ?string $state): array
    {
        $storedState = $_SESSION[self::STATE_KEY] ?? null;
        unset($_SESSION[self::STATE_KEY]);

        if (!is_string($storedState) || $storedState === '' || !is_string($state) || !hash_equals($storedState, $state)) {
            throw new RuntimeException('State OAuth tidak valid. Silakan coba login ulang.');
        }

        $client = $this->buildClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new RuntimeException('Google OAuth gagal: ' . (string) $token['error']);
        }

        $idToken = isset($token['id_token']) ? (string) $token['id_token'] : '';
        if ($idToken === '') {
            throw new RuntimeException('ID token tidak ditemukan dari Google.');
        }

        $payload = $client->verifyIdToken($idToken);
        if (!is_array($payload)) {
            throw new RuntimeException('Verifikasi ID token gagal.');
        }

        $email = (string) ($payload['email'] ?? '');
        $sub = (string) ($payload['sub'] ?? '');

        if ($email === '' || $sub === '') {
            throw new RuntimeException('Profil Google tidak lengkap.');
        }

        if (!(bool) ($payload['email_verified'] ?? false)) {
            throw new RuntimeException('Email Google belum terverifikasi.');
        }

        return [
            'sub' => $sub,
            'email' => $email,
            'name' => (string) ($payload['name'] ?? $email),
            'avatar' => isset($payload['picture']) ? (string) $payload['picture'] : null,
        ];
    }

    private function buildClient(): Client
    {
        $clientId = envValue('GOOGLE_CLIENT_ID', '');
        $clientSecret = envValue('GOOGLE_CLIENT_SECRET', '');
        $redirectUri = envValue('GOOGLE_REDIRECT_URI', '');

        if ($clientId === '' || $clientSecret === '' || $redirectUri === '') {
            throw new RuntimeException('Google OAuth belum dikonfigurasi di .env.');
        }

        $client = new Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->addScope('openid');
        $client->addScope('email');
        $client->addScope('profile');
        $client->setAccessType('online');
        $client->setPrompt('select_account');

        return $client;
    }
}
