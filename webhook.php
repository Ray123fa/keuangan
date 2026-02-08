<?php
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
        if ($data && is_array($data['timestamps'])) {
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

// Log incoming request (hanya jika APP_DEBUG=true)
$rawInput = file_get_contents('php://input');
if (APP_DEBUG) {
    error_log('Webhook received: ' . $rawInput);
}

// Parse JSON input
$data = json_decode($rawInput, true);

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
