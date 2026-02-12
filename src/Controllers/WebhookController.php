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
            error_log('WARNING: WHITELIST_NUMBERS is empty. Falling back to default hardcoded number.');
            $whitelistNumbers = ['6282255623881'];
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
        $lockFile = sys_get_temp_dir() . '/fonnte_rate_' . md5($sender) . '.lock';
        $now = time();
        $oneMinuteAgo = $now - 60;

        $requests = [];
        if (file_exists($lockFile)) {
            $content = file_get_contents($lockFile);
            $data = json_decode($content === false ? '' : $content, true);

            if (is_array($data) && isset($data['timestamps']) && is_array($data['timestamps'])) {
                $requests = array_filter(
                    $data['timestamps'],
                    static fn(mixed $timestamp): bool => is_int($timestamp) && $timestamp > $oneMinuteAgo
                );
            }
        }

        if (count($requests) >= $maxPerMinute) {
            return false;
        }

        $requests[] = $now;
        file_put_contents($lockFile, json_encode(['timestamps' => $requests]), LOCK_EX);

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
}
