<?php
/**
 * Wishlist API — Toggle add/remove
 */
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to use wishlist.']);
    exit;
}

$input     = json_decode(file_get_contents('php://input'), true);
$productId = (int)($input['product_id'] ?? 0);

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product.']);
    exit;
}

$db     = getDB();
$userId = $_SESSION['user_id'];

// Check if already in wishlist
$stmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
$stmt->execute([$userId, $productId]);
$existing = $stmt->fetch();

if ($existing) {
    // Remove
    $db->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?")->execute([$userId, $productId]);
    $count = getWishlistCount();
    echo json_encode(['success' => true, 'action' => 'removed', 'count' => $count]);
} else {
    // Add — verify product exists
    $check = $db->prepare("SELECT id FROM products WHERE id = ?");
    $check->execute([$productId]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Product not found.']);
        exit;
    }
    $db->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)")->execute([$userId, $productId]);
    $count = getWishlistCount();
    echo json_encode(['success' => true, 'action' => 'added', 'count' => $count]);
}
