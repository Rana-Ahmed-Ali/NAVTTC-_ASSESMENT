<?php
/**
 * Secure Login Form Processor
 * Implements anti-CSRF tokens, brute-force throttling, secure session initiation,
 * and database-backed rate-limiting.
 */

require_once 'session.php';
require_once 'api/db.php'; // Required for tracking failed attempts in the database

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? '';

// 1. Get Client IP Address safely
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// 2. Brute Force Protection: Check failed attempts in the last 15 minutes
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM login_attempts 
        WHERE ip_address = :ip 
          AND attempted_at > (NOW() - INTERVAL 15 MINUTE)
    ");
    $stmt->execute(['ip' => $ipAddress]);
    $failedCount = (int)$stmt->fetchColumn();

    if ($failedCount >= 5) {
        $_SESSION['login_error'] = 'Too many failed attempts. Access temporarily locked for 15 minutes.';
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    // Fail-safe: log database error but do not block normal functionality if table is broken
    error_log("Database error in login rate limiter: " . $e->getMessage());
}

// 3. CSRF Verification
if (empty($csrfToken) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    $_SESSION['login_error'] = 'Security validation failed. Please refresh and try again.';
    header('Location: login.php');
    exit;
}

// 4. Authenticate Credentials
$user = null;
try {
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error during authentication: " . $e->getMessage());
}

if ($user && password_verify($password, $user['password_hash'])) {
    // Login Success!
    
    // Clear failed attempts for this IP on successful login
    try {
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = :ip");
        $stmt->execute(['ip' => $ipAddress]);
    } catch (PDOException $e) {
        error_log("Failed to clear login attempts: " . $e->getMessage());
    }

    // Assign session credentials
    $_SESSION['user'] = $username;
    
    // Regenerate Session ID to defend against Session Fixation attacks
    secure_regenerate_session();
    
    // Clear CSRF token after successful login to prevent reuse
    unset($_SESSION['csrf_token']);

    header('Location: index.php');
    exit;
}

// Login Failed!
try {
    $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address) VALUES (:ip)");
    $stmt->execute(['ip' => $ipAddress]);
} catch (PDOException $e) {
    error_log("Failed to log failed login attempt: " . $e->getMessage());
}

$_SESSION['login_error'] = 'Invalid username or password.';
header('Location: login.php');
exit;
