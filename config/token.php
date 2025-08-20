<?php
function generate_token() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    if (empty($_SESSION['token'])) {
        $_SESSION['token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['token'];
}

function validate_token($token) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    return isset($_SESSION['token']) && hash_equals($_SESSION['token'], $token);
}