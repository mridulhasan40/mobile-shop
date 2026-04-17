<?php
/**
 * Helper Functions
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Sanitize output to prevent XSS
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Format price with currency
 */
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

/**
 * Set flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Display flash message HTML
 */
function displayFlash() {
    $flash = getFlash();
    if ($flash) {
        $type = sanitize($flash['type']);
        $message = sanitize($flash['message']);
        echo "<div class='alert alert-{$type}' id='flash-message'>
                <span>{$message}</span>
                <button class='alert-close' onclick='this.parentElement.remove()'>&times;</button>
              </div>";
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Upload product image
 */
function uploadImage($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error occurred'];
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, WebP, GIF'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large. Maximum 5MB allowed'];
    }
    
    // Create upload directory if not exists
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('product_') . '.' . $ext;
    $destination = UPLOAD_DIR . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

/**
 * Get cart count for current user
 */
function getCartCount() {
    if (!isLoggedIn()) return 0;
    
    $db = getDB();
    $stmt = $db->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}

/**
 * Get cart items for current user
 */
function getCartItems() {
    if (!isLoggedIn()) return [];
    
    $db = getDB();
    $stmt = $db->prepare("
        SELECT c.*, p.name, p.price, p.image, p.stock, p.brand 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchAll();
}

/**
 * Get cart total
 */
function getCartTotal() {
    $items = getCartItems();
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

/**
 * Get all categories
 */
function getCategories() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM categories ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Get all unique brands
 */
function getBrands() {
    $db = getDB();
    $stmt = $db->query("SELECT DISTINCT brand FROM products ORDER BY brand");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Truncate text to a specific length
 */
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

/**
 * Get product image URL — uses real Unsplash photos as placeholders
 */
function getProductImage($image) {
    if ($image && $image !== 'default.png' && file_exists(UPLOAD_DIR . $image)) {
        return UPLOAD_URL . $image;
    }
    $placeholders = [
        'samsung-s24-ultra.jpg'  => 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=600&q=80',
        'iphone-15-pro-max.jpg'  => 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=600&q=80',
        'pixel-8-pro.jpg'        => 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=600&q=80',
        'oneplus-12.jpg'         => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=600&q=80',
        'xiaomi-14-pro.jpg'      => 'https://images.unsplash.com/photo-1574944985070-8f3ebc6b79d2?w=600&q=80',
        'samsung-a55.jpg'        => 'https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?w=600&q=80',
        'ipad-pro-m4.jpg'        => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=600&q=80',
        'galaxy-tab-s9.jpg'      => 'https://images.unsplash.com/photo-1623126908029-58cb08a2b272?w=600&q=80',
        'airpods-pro-2.jpg'      => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=600&q=80',
        'galaxy-watch-6.jpg'     => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&q=80',
        'anker-65w.jpg'          => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&q=80',
        'apple-20w.jpg'          => 'https://images.unsplash.com/photo-1585771724684-38269d6639fd?w=600&q=80',
        'spigen-case.jpg'        => 'https://images.unsplash.com/photo-1601784551446-20c9e07cdbdb?w=600&q=80',
        'otterbox-defender.jpg'  => 'https://images.unsplash.com/photo-1606041008023-472dfb5e530f?w=600&q=80',
    ];
    if ($image && isset($placeholders[$image])) {
        return $placeholders[$image];
    }
    return 'https://images.unsplash.com/photo-1510557880182-3d4d3cba35a5?w=600&q=80';
}
