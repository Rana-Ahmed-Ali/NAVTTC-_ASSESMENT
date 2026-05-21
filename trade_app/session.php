<?php
/**
 * Secure Session Manager
 * Configures cookie security settings to mitigate XSS, CSRF, and Session Hijacking.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Determine if HTTPS is active
    $isSecure = false;
    if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on') {
        $isSecure = true;
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        $isSecure = true;
    }

    // Configure secure session cookie options
    session_start([
        'cookie_lifetime' => 0,              // Cookie expires when the browser closes
        'cookie_path'     => '/',
        'cookie_secure'   => $isSecure,        // True if HTTPS (strongly recommended in production)
        'cookie_httponly' => true,             // Mitigates XSS by preventing JS access to session ID
        'cookie_samesite' => 'Lax',            // Mitigates CSRF attacks while keeping usability
        'use_strict_mode' => true,             // Prevents session fixation attacks
    ]);
}

/**
 * Regenerate session ID periodically or on privilege change
 */
function secure_regenerate_session() {
    if (isset($_SESSION['user'])) {
        session_regenerate_id(true);
    }
}
