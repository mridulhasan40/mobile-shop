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
 * Get the effective (sale or regular) price for a product
 */
function getEffectivePrice($product) {
    return ($product['discount_price'] !== null && $product['discount_price'] > 0 && $product['discount_price'] < $product['price'])
        ? (float)$product['discount_price']
        : (float)$product['price'];
}

/**
 * Check if a product has an active discount
 */
function hasDiscount($product) {
    return $product['discount_price'] !== null
        && $product['discount_price'] > 0
        && $product['discount_price'] < $product['price'];
}

/**
 * Get discount percentage for a product
 */
function getDiscountPercent($product) {
    if (!hasDiscount($product)) return 0;
    return round((($product['price'] - $product['discount_price']) / $product['price']) * 100);
}

/**
 * Render price HTML with discount styling for product cards
 */
function renderCardPrice($product) {
    if (hasDiscount($product)) {
        $pct = getDiscountPercent($product);
        return '<span class="price-group">' .
               '<span class="price-original">' . formatPrice($product['price']) . '</span>' .
               '<span class="product-card-price">' . formatPrice($product['discount_price']) . '</span>' .
               '</span>';
    }
    return '<span class="product-card-price">' . formatPrice($product['price']) . '</span>';
}

/**
 * Set flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
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
        $type    = sanitize($flash['type']);
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

    // Validate MIME type via finfo (server-side, not trusting $_FILES['type'])
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, WebP, GIF'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large. Maximum 5MB allowed'];
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('product_') . '.' . strtolower($ext);
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
        SELECT c.*, p.name, p.price, p.discount_price, p.image, p.stock, p.brand
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
        $total += getEffectivePrice($item) * $item['quantity'];
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
 * Get product image URL
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

// ─────────────────────────────────────────────────────────────────────────────
// REVIEWS & RATINGS
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Get all reviews for a product
 */
function getProductReviews($productId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT r.*, u.name as user_name
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.product_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

/**
 * Get average rating and count for a product
 */
function getProductRating($productId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT AVG(rating) as avg, COUNT(*) as total FROM reviews WHERE product_id = ?");
    $stmt->execute([$productId]);
    return $stmt->fetch();
}

/**
 * Check if logged-in user already reviewed a product
 */
function userHasReviewed($productId) {
    if (!isLoggedIn()) return false;
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ?");
    $stmt->execute([$productId, $_SESSION['user_id']]);
    return (bool)$stmt->fetch();
}

/**
 * Check if user has ordered a product (required to review)
 */
function userHasOrderedProduct($productId) {
    if (!isLoggedIn()) return false;
    $db = getDB();
    $stmt = $db->prepare("
        SELECT oi.id FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'delivered'
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id'], $productId]);
    return (bool)$stmt->fetch();
}

/**
 * Render star rating HTML
 */
function renderStars($rating, $max = 5) {
    $html = '<span class="star-rating">';
    for ($i = 1; $i <= $max; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="fas fa-star" style="color:#f59e0b;"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $html .= '<i class="fas fa-star-half-alt" style="color:#f59e0b;"></i>';
        } else {
            $html .= '<i class="far fa-star" style="color:#d1d5db;"></i>';
        }
    }
    $html .= '</span>';
    return $html;
}

// ─────────────────────────────────────────────────────────────────────────────
// WISHLIST
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Get wishlist count for current user
 */
function getWishlistCount() {
    if (!isLoggedIn()) return 0;
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return (int)$stmt->fetchColumn();
}

/**
 * Check if a product is in the user's wishlist
 */
function isInWishlist($productId) {
    if (!isLoggedIn()) return false;
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $productId]);
    return (bool)$stmt->fetch();
}

/**
 * Get all wishlist items for the current user
 */
function getWishlistItems() {
    if (!isLoggedIn()) return [];
    $db = getDB();
    $stmt = $db->prepare("
        SELECT w.*, p.name, p.price, p.discount_price, p.image, p.stock, p.brand
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchAll();
}

// ─────────────────────────────────────────────────────────────────────────────
// COUPONS
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Validate and apply a coupon code
 * Returns: ['valid'=>bool, 'discount'=>float, 'message'=>string, 'coupon'=>array|null]
 */
function validateCoupon($code, $orderTotal) {
    if (empty($code)) return ['valid' => false, 'message' => 'No coupon code entered.'];

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
    $stmt->execute([strtoupper(trim($code))]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        return ['valid' => false, 'message' => 'Invalid or expired coupon code.'];
    }

    // Check expiry
    if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
        return ['valid' => false, 'message' => 'This coupon has expired.'];
    }

    // Check max uses
    if ($coupon['max_uses'] !== null && $coupon['used_count'] >= $coupon['max_uses']) {
        return ['valid' => false, 'message' => 'This coupon has reached its usage limit.'];
    }

    // Check minimum order
    if ($orderTotal < $coupon['min_order']) {
        return ['valid' => false, 'message' => 'Minimum order of ' . formatPrice($coupon['min_order']) . ' required for this coupon.'];
    }

    // Calculate discount
    if ($coupon['type'] === 'percentage') {
        $discount = round($orderTotal * ($coupon['value'] / 100), 2);
    } else {
        $discount = min($coupon['value'], $orderTotal);
    }

    return ['valid' => true, 'discount' => $discount, 'coupon' => $coupon, 'message' => 'Coupon applied! You save ' . formatPrice($discount)];
}

/**
 * Increment coupon used_count
 */
function useCoupon($couponId) {
    $db = getDB();
    $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?")->execute([$couponId]);
}

// ─────────────────────────────────────────────────────────────────────────────
// ORDER STATUS HISTORY
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Add an entry to the order status history
 */
function addOrderStatusHistory($orderId, $status, $note = '') {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO order_status_history (order_id, status, note) VALUES (?, ?, ?)");
    $stmt->execute([$orderId, $status, $note]);
}

/**
 * Get full status history for an order
 */
function getOrderStatusHistory($orderId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at ASC");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

// ─────────────────────────────────────────────────────────────────────────────
// RECENTLY VIEWED PRODUCTS
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Add product to recently viewed (session-based, max 6)
 */
function addRecentlyViewed($productId) {
    if (!isset($_SESSION['recently_viewed'])) {
        $_SESSION['recently_viewed'] = [];
    }
    // Remove if already exists
    $_SESSION['recently_viewed'] = array_filter(
        $_SESSION['recently_viewed'],
        fn($id) => $id !== $productId
    );
    // Prepend to front
    array_unshift($_SESSION['recently_viewed'], $productId);
    // Keep max 6
    $_SESSION['recently_viewed'] = array_slice($_SESSION['recently_viewed'], 0, 6);
}

/**
 * Get recently viewed products (excluding current product)
 */
function getRecentlyViewed($excludeId = null) {
    if (empty($_SESSION['recently_viewed'])) return [];
    $ids = $_SESSION['recently_viewed'];
    if ($excludeId) {
        $ids = array_filter($ids, fn($id) => $id !== $excludeId);
    }
    if (empty($ids)) return [];

    $db = getDB();
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND stock > 0 LIMIT 4");
    $stmt->execute(array_values($ids));
    return $stmt->fetchAll();
}

// ─────────────────────────────────────────────────────────────────────────────
// PASSWORD RESET
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Create a password reset token for an email
 */
function createPasswordReset($email) {
    $db = getDB();
    // Check user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if (!$stmt->fetch()) return false;

    // Delete any existing token
    $db->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)")
       ->execute([$email, $token, $expires]);

    return $token;
}

/**
 * Validate a password reset token
 */
function validatePasswordResetToken($token) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    return $stmt->fetch();
}

/**
 * Reset a user's password using a valid token
 */
function resetPassword($token, $newPassword) {
    $reset = validatePasswordResetToken($token);
    if (!$reset) return false;

    $db = getDB();
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $db->prepare("UPDATE users SET password = ? WHERE email = ?")->execute([$hash, $reset['email']]);
    $db->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);
    return true;
}
