<?php
declare(strict_types=1);
/**
 * Webhook Endpoint untuk Fonnte
 * 
 * URL ini harus didaftarkan di dashboard Fonnte sebagai Webhook URL
 * Pastikan "Auto Read" diaktifkan di Fonnte agar webhook bisa berjalan
 */

// Set header
header('Content-Type: application/json; charset=utf-8');

// Load dependencies
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/handlers/MessageHandler.php';

// ============================================
// BASIC RATE LIMITING (per sender, per minute)
// ============================================

function checkRateLimit(string $sender, int $maxPerMinute = 10): bool
{
    $lockFile = sys_get_temp_dir() . '/fonnte_rate_' . md5($sender) . '.lock';
    
    // Get current timestamp
    $now = time();
    $oneMinuteAgo = $now - 60;
    
    // Read existing requests
    $requests = [];
    if (file_exists($lockFile)) {
        $data = json_decode(file_get_contents($lockFile), true);
        if (is_array($data) && isset($data['timestamps']) && is_array($data['timestamps'])) {
            $requests = array_filter($data['timestamps'], function($t) use ($oneMinuteAgo) {
                return $t > $oneMinuteAgo;
            });
        }
    }
    
    // Check if limit exceeded
    if (count($requests) >= $maxPerMinute) {
        return false;
    }
    
    // Add current request
    $requests[] = $now;
    file_put_contents($lockFile, json_encode(['timestamps' => $requests]), LOCK_EX);
    
    return true;
}

function parseWhitelistNumbers(string $csv): array
{
    $numbers = array_map('trim', explode(',', $csv));
    $numbers = array_values(array_filter($numbers, static function (string $number): bool {
        return $number !== '';
    }));

    return array_unique($numbers);
}

function sanitizeWebhookLog(array $data): string
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

// Log incoming request (hanya jika APP_DEBUG=true)
$rawInput = file_get_contents('php://input');

// Parse JSON input
$data = json_decode($rawInput, true);

if (APP_DEBUG && is_array($data)) {
    error_log('Webhook received: ' . sanitizeWebhookLog($data));
}

// Validasi data
if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Invalid JSON']);
    exit;
}

// Ambil data dari webhook Fonnte
$device = $data['device'] ?? '';      // Nomor device kamu
$sender = $data['sender'] ?? '';      // Nomor pengirim pesan
$message = $data['message'] ?? '';    // Isi pesan
$name = $data['name'] ?? '';          // Nama pengirim
$member = $data['member'] ?? '';      // Jika dari group, ini adalah member yang kirim
$location = $data['location'] ?? '';  // Lokasi jika ada

// Skip jika tidak ada sender atau message
if (empty($sender) || empty($message)) {
    echo json_encode(['status' => true, 'message' => 'No action needed']);
    exit;
}

// Check rate limit
if (!checkRateLimit($sender, 10)) {
    http_response_code(429);
    echo json_encode(['status' => false, 'message' => 'Too many requests']);
    exit;
}

// Whitelist nomor dari environment variable WHITELIST_NUMBERS
$whitelistNumbers = parseWhitelistNumbers(WHITELIST_NUMBERS);
if (empty($whitelistNumbers)) {
    error_log('WARNING: WHITELIST_NUMBERS is empty. Falling back to default hardcoded number.');
    $whitelistNumbers = ['6282255623881'];
}

if (!in_array($sender, $whitelistNumbers)) {
    echo json_encode(['status' => true, 'message' => 'Access denied']);
    exit;
}

// Skip pesan dari group (optional - bisa dihapus jika mau support group)
if (strpos($sender, '@g.us') !== false) {
    echo json_encode(['status' => true, 'message' => 'Group message skipped']);
    exit;
}

// Handle message
try {
    $handler = new MessageHandler();
    $handler->handle($sender, $message);
    
    echo json_encode(['status' => true, 'message' => 'Processed']);
} catch (Exception $e) {
    error_log('Webhook error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Internal error']);
}
