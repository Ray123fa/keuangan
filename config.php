<?php
declare(strict_types=1);

/**
 * Konfigurasi Chatbot Keuangan WhatsApp
 * 
 * Load configuration from .env file, dengan fallback ke default values
 */

// Include guard - prevent multiple inclusions
if (defined('CONFIG_LOADED')) {
    return;
}
define('CONFIG_LOADED', true);

// ============================================
// LOAD ENVIRONMENT VARIABLES FROM .ENV
// ============================================

function loadEnv(string $filePath): void {
    if (!file_exists($filePath)) {
        throw new Exception("File .env tidak ditemukan di: $filePath");
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse line: KEY=value
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes jika ada
            if (preg_match('/^["\'](.+)["\']$/', $value, $matches)) {
                $value = $matches[1];
            }

            // Set sebagai environment variable
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Load .env file
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    loadEnv($envPath);
}

// ============================================
// GET ENV VARIABLE DENGAN DEFAULT FALLBACK
// ============================================

if (!function_exists('envValue')) {
    function envValue(string $key, ?string $default = null): ?string {
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }
}

if (!function_exists('envBool')) {
    function envBool(string $key, bool $default = false): bool {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }
}

// ============================================
// DATABASE CONFIGURATION
// ============================================

define('DB_HOST', envValue('DB_HOST', 'localhost'));
define('DB_PORT', envValue('DB_PORT', '3306'));
define('DB_NAME', envValue('DB_NAME', 'keuangan_db'));
define('DB_USER', envValue('DB_USER', 'root'));
define('DB_PASS', envValue('DB_PASS', 'root'));
define('DB_CHARSET', 'utf8mb4');

// ============================================
// FONNTE API CONFIGURATION
// ============================================

define('FONNTE_TOKEN', envValue('FONNTE_TOKEN', ''));

// Validate Fonnte token
if (empty(FONNTE_TOKEN)) {
    error_log('WARNING: FONNTE_TOKEN not configured. Set FONNTE_TOKEN in .env');
}

// ============================================
// APPLICATION CONFIGURATION
// ============================================

define('APP_ENV', envValue('APP_ENV', 'development'));
define('APP_DEBUG', envBool('APP_DEBUG', true));
define('WHITELIST_NUMBERS', envValue('WHITELIST_NUMBERS', '6282255623881'));

// ============================================
// TIMEZONE
// ============================================

$timezone = envValue('TIMEZONE', 'Asia/Jakarta');
date_default_timezone_set($timezone);

// ============================================
// ERROR HANDLING & LOGGING
// ============================================

error_reporting(E_ALL);

if (APP_DEBUG) {
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
}

ini_set('log_errors', 1);
$logDirectory = __DIR__ . '/storage/logs';
if (!is_dir($logDirectory)) {
    mkdir($logDirectory, 0755, true);
}

ini_set('error_log', $logDirectory . '/app.log');

// ============================================
// CONSTANTS
// ============================================

// Session expiry time (minutes)
define('SESSION_EXPIRY_MINUTES', 5);

// File upload timeout (seconds)
define('FILE_UPLOAD_TIMEOUT', 60);
