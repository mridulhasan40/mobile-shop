<?php
/**
 * Database & App Configuration
 * PDO connection with prepared statements for security.
 *
 * For production: move sensitive values to a .env file
 * and load them with vlucas/phpdotenv or a similar library.
 */

// ─── Database ──────────────────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'mobile_shop');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ─── Site ──────────────────────────────────────────────────────────────────────
define('SITE_NAME', 'MobileShop');
define('SITE_URL',  'http://localhost/mobile-shop');
define('UPLOAD_DIR', __DIR__ . '/../uploads/products/');
define('UPLOAD_URL', SITE_URL . '/uploads/products/');

// ─── Email ─────────────────────────────────────────────────────────────────────
// Change MAIL_FROM before deploying to production
define('MAIL_FROM', 'noreply@mobileshop.com');

/**
 * Get PDO database connection (singleton)
 * @return PDO
 */
function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production: log error, show user-friendly message
            error_log('Database connection failed: ' . $e->getMessage());
            http_response_code(500);
            if (file_exists(__DIR__ . '/../500.php')) {
                require __DIR__ . '/../500.php';
            } else {
                echo '<h1>Service temporarily unavailable. Please try again later.</h1>';
            }
            exit;
        }
    }

    return $pdo;
}
