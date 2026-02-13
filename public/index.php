<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../vendor/autoload.php';

$httpsHeader = $_SERVER['HTTPS'] ?? '';
$forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
$isHttps = $httpsHeader === 'on' || $httpsHeader === '1' || strtolower((string) $forwardedProto) === 'https';

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', $isHttps ? '1' : '0');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', (string) (SESSION_IDLE_TIMEOUT_MINUTES * 60));

session_name('__keu_sess');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

use App\Bootstrap\App;

App::run(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI'] ?? '/'
);
