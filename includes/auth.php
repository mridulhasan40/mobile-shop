<?php
/**
 * Authentication Middleware
 * Include this file at the top of pages that require authentication
 */

session_start();
require_once __DIR__ . '/functions.php';

/**
 * Require user to be logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('error', 'Please log in to access this page.');
        redirect(SITE_URL . '/pages/login.php');
    }
}

/**
 * Require user to be admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setFlash('error', 'Access denied. Admin privileges required.');
        redirect(SITE_URL . '/index.php');
    }
}

/**
 * Check login rate limit (max 5 attempts per 15 min per email+IP)
 */
function checkLoginRateLimit($email) {
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $window = date('Y-m-d H:i:s', strtotime('-15 minutes'));

    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM login_attempts
         WHERE (email = ? OR ip_address = ?) AND attempted_at > ?"
    );
    $stmt->execute([$email, $ip, $window]);
    return (int)$stmt->fetchColumn() < 5;
}

/**
 * Record a failed login attempt
 */
function recordLoginAttempt($email) {
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $db->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)");
    $stmt->execute([$email, $ip]);
}

/**
 * Clear login attempts after successful login
 */
function clearLoginAttempts($email) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM login_attempts WHERE email = ?");
    $stmt->execute([$email]);
}

/**
 * Get remaining attempts message
 */
function getRemainingAttempts($email) {
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $window = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM login_attempts
         WHERE (email = ? OR ip_address = ?) AND attempted_at > ?"
    );
    $stmt->execute([$email, $ip, $window]);
    $used = (int)$stmt->fetchColumn();
    return max(0, 5 - $used);
}

/**
 * Login user
 */
function loginUser($email, $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Check if account is active
        if (isset($user['is_active']) && $user['is_active'] == 0) {
            return 'banned';
        }

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];

        // Update last login timestamp
        $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

        // Clear any login attempts on success
        clearLoginAttempts($email);

        return 'success';
    }

    return 'fail';
}

/**
 * Register new user
 */
function registerUser($name, $email, $password) {
    $db = getDB();

    // Check if email exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email already registered'];
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $hashedPassword]);

    return ['success' => true, 'message' => 'Registration successful'];
}

/**
 * Logout user
 */
function logoutUser() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
