<?php
declare(strict_types=1);

// Error reporting
//ini_set('display_errors', '1');
//ini_set('error_reporting', (string)E_ALL);

// Secure session initialization
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'name'            => 'SECURE_SESSID',
        'cookie_lifetime' => 86400,
        'cookie_path'     => '/',
        'cookie_domain'   => $_SERVER['HTTP_HOST'] ?? '',
        'cookie_secure'   => !empty($_SERVER['HTTPS']),
        'cookie_httponly' => true,
        'use_strict_mode' => true,
        'use_only_cookies'=> 1
    ]);
}

// Include token.php with absolute path
require __DIR__ . '/token.php';

/**
 * Validasi session user
 */
function validate_session(): void {
    // Basic session validation
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
        end_session();
        header("Location: login.php?error=session_invalid");
        exit;
    }

    // IP validation
    $current_ip = get_client_ip();
    $stored_ip  = $_SESSION['ip_address'] ?? '0.0.0.0';

    if (!ip_match($stored_ip, $current_ip)) {
        end_session();
        header("Location: login.php?error=ip_mismatch");
        exit;
    }

    // User agent validation
    $session_ua = $_SESSION['user_agent'] ?? '';
    $current_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    if ($session_ua !== $current_ua) {
        end_session();
        header("Location: login.php?error=agent_mismatch");
        exit;
    }
}

/**
 * Hapus semua session
 */
function end_session(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Ambil IP client
 */
function get_client_ip(): string {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    return '0.0.0.0';

    // Ambil 3 oktet pertama biar lebih fleksibel
    return implode('.', array_slice($parts, 0, 3));
}

/**
 * Cek apakah dua IP mirip (2 oktet pertama)
 */
function ip_match(string $ip1, string $ip2): bool {
    if (!$ip1 || !$ip2) {
        return false;
    }
    return implode('.', array_slice(explode('.', $ip1), 0, 2)) ===
           implode('.', array_slice(explode('.', $ip2), 0, 2));
}

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
//header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com");

