<?php
/**
 * Cart API — AJAX Operations
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in first.', 'requireLogin' => true]);
    exit;
}

$db = getDB();
$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'POST':
        // Add to cart
        $productId = (int)($input['product_id'] ?? $_POST['product_id'] ?? 0);
        $quantity = (int)($input['quantity'] ?? $_POST['quantity'] ?? 1);

        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product.']);
            exit;
        }

        // Check product exists and has stock
        $stmt = $db->prepare("SELECT stock, name FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
            exit;
        }

        if ($product['stock'] < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock available.']);
            exit;
        }

        // Insert or update cart
        $stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
        $stmt->execute([$userId, $productId, $quantity]);

        $cartCount = getCartCount();
        echo json_encode([
            'success' => true,
            'message' => $product['name'] . ' added to cart!',
            'cartCount' => $cartCount
        ]);
        break;

    case 'PUT':
        // Update quantity
        $productId = (int)($input['product_id'] ?? 0);
        $quantity = (int)($input['quantity'] ?? 0);

        if ($quantity <= 0) {
            // Remove item if quantity is 0 or less
            $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
        } else {
            // Check stock
            $stockStmt = $db->prepare("SELECT stock FROM products WHERE id = ?");
            $stockStmt->execute([$productId]);
            $product = $stockStmt->fetch();

            if ($product && $quantity > $product['stock']) {
                $quantity = $product['stock'];
            }

            $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$quantity, $userId, $productId]);
        }

        $cartCount = getCartCount();
        $cartTotal = getCartTotal();
        echo json_encode([
            'success' => true,
            'message' => 'Cart updated.',
            'cartCount' => $cartCount,
            'cartTotal' => $cartTotal
        ]);
        break;

    case 'DELETE':
        // Remove from cart
        $productId = (int)($input['product_id'] ?? 0);

        $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);

        $cartCount = getCartCount();
        $cartTotal = getCartTotal();
        echo json_encode([
            'success' => true,
            'message' => 'Item removed from cart.',
            'cartCount' => $cartCount,
            'cartTotal' => $cartTotal
        ]);
        break;

    case 'GET':
        // Get cart info
        $cartCount = getCartCount();
        $cartTotal = getCartTotal();
        $cartItems = getCartItems();
        echo json_encode([
            'success' => true,
            'count' => $cartCount,
            'total' => $cartTotal,
            'items' => $cartItems
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
