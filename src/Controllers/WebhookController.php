<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Application\Handlers\MessageHandler;
use Throwable;

final class WebhookController
{
    public function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->verifyWebhookSecret()) {
            http_response_code(403);
            echo json_encode(['status' => false, 'message' => 'Forbidden']);
            return;
        }

        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (APP_DEBUG && is_array($data)) {
            error_log('Webhook received: ' . $this->sanitizeWebhookLog($data));
        }

        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['status' => false, 'message' => 'Invalid JSON']);
            return;
        }

        $sender = (string) ($data['sender'] ?? '');
        $message = (string) ($data['message'] ?? '');

        if ($sender === '' || $message === '') {
            echo json_encode(['status' => true, 'message' => 'No action needed']);
            return;
        }

        if (!$this->checkRateLimit($sender, 10)) {
            http_response_code(429);
            echo json_encode(['status' => false, 'message' => 'Too many requests']);
            return;
        }

        $whitelistNumbers = $this->parseWhitelistNumbers((string) WHITELIST_NUMBERS);
        if (empty($whitelistNumbers)) {
            error_log('CRITICAL: WHITELIST_NUMBERS is empty. Rejecting all webhook requests.');
            echo json_encode(['status' => false, 'message' => 'Service unavailable']);
            return;
        }

        if (!in_array($sender, $whitelistNumbers, true)) {
            echo json_encode(['status' => true, 'message' => 'Access denied']);
            return;
        }

        if (str_contains($sender, '@g.us')) {
            echo json_encode(['status' => true, 'message' => 'Group message skipped']);
            return;
        }

        try {
            $handler = new MessageHandler();
            $handler->handle($sender, $message);

            echo json_encode(['status' => true, 'message' => 'Processed']);
        } catch (Throwable $exception) {
            error_log('Webhook error: ' . $exception->getMessage());
            http_response_code(500);
            echo json_encode(['status' => false, 'message' => 'Internal error']);
        }
    }

    private function checkRateLimit(string $sender, int $maxPerMinute): bool
    {
        $rateLimitDir = __DIR__ . '/../../storage/rate_limits';
        if (!is_dir($rateLimitDir)) {
            mkdir($rateLimitDir, 0755, true);
        }

        $lockFile = $rateLimitDir . '/fonnte_rate_' . hash('sha256', $sender) . '.json';
        $now = time();
        $oneMinuteAgo = $now - 60;

        $handle = fopen($lockFile, 'c+');
        if ($handle === false) {
            error_log('Rate limit: failed to open lock file');
            return true;
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            return true;
        }

        $content = stream_get_contents($handle);
        $requests = [];

        if ($content !== false && $content !== '') {
            $data = json_decode($content, true);
            if (is_array($data) && isset($data['timestamps']) && is_array($data['timestamps'])) {
                $requests = array_values(array_filter(
                    $data['timestamps'],
                    static fn(mixed $timestamp): bool => is_int($timestamp) && $timestamp > $oneMinuteAgo
                ));
            }
        }

        if (count($requests) >= $maxPerMinute) {
            flock($handle, LOCK_UN);
            fclose($handle);
            return false;
        }

        $requests[] = $now;

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode(['timestamps' => $requests]));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return true;
    }

    /** @return string[] */
    private function parseWhitelistNumbers(string $csv): array
    {
        $numbers = array_map('trim', explode(',', $csv));
        $numbers = array_values(array_filter($numbers, static fn(string $number): bool => $number !== ''));

        return array_values(array_unique($numbers));
    }

    private function sanitizeWebhookLog(array $data): string
    {
        $safe = $data;

        if (isset($safe['message']) && is_string($safe['message'])) {
            $safe['message_preview'] = substr($safe['message'], 0, 120);
            unset($safe['message']);
        }

        if (isset($safe['sender']) && is_string($safe['sender'])) {
            $safe['sender'] = substr($safe['sender'], 0, 4) . '***';
        }

        $encoded = json_encode($safe, JSON_UNESCAPED_UNICODE);

        return $encoded === false ? '{}' : $encoded;
    }

    private function verifyWebhookSecret(): bool
    {
        $configuredSecret = envValue('WEBHOOK_SECRET', '');
        if ($configuredSecret === null || $configuredSecret === '') {
            // Jika secret belum dikonfigurasi, log warning dan izinkan (backward compatible)
            // Setelah secret dikonfigurasi, request tanpa secret akan ditolak
            error_log('WARNING: WEBHOOK_SECRET not configured. Webhook requests are unprotected.');
            return true;
        }

        $providedSecret = isset($_GET['secret']) ? (string) $_GET['secret'] : '';
        if ($providedSecret === '') {
            error_log('Webhook rejected: missing secret parameter');
            return false;
        }

        if (!hash_equals($configuredSecret, $providedSecret)) {
            error_log('Webhook rejected: invalid secret');
            return false;
        }

        return true;
    }
}
