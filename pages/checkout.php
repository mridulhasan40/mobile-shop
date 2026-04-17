<?php
/**
 * Checkout Page
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db = getDB();
$pageTitle = 'Checkout';

$cartItems = getCartItems();
$cartTotal = getCartTotal();
$shippingCost = $cartTotal >= 99 ? 0 : 9.99;
$orderTotal = $cartTotal + $shippingCost;

if (empty($cartItems)) {
    setFlash('warning', 'Your cart is empty.');
    redirect(SITE_URL . '/pages/cart.php');
}

$user = getCurrentUser();
$errors = [];

// Handle checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    }

    $shippingName = trim($_POST['shipping_name'] ?? '');
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $shippingPhone = trim($_POST['shipping_phone'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'cod';

    if (empty($shippingName)) $errors[] = 'Shipping name is required.';
    if (empty($shippingAddress)) $errors[] = 'Shipping address is required.';
    if (empty($shippingPhone)) $errors[] = 'Phone number is required.';

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Create order
            $orderStmt = $db->prepare("INSERT INTO orders (user_id, total_price, shipping_name, shipping_address, shipping_phone, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            $orderStmt->execute([
                $_SESSION['user_id'],
                $orderTotal,
                $shippingName,
                $shippingAddress,
                $shippingPhone,
                $paymentMethod
            ]);
            $orderId = $db->lastInsertId();

            // Add order items
            $itemStmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cartItems as $item) {
                $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);

                // Reduce stock
                $stockStmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
                $stockStmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
            }

            // Clear cart
            $clearStmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
            $clearStmt->execute([$_SESSION['user_id']]);

            $db->commit();

            setFlash('success', "Order #$orderId placed successfully! We'll process it shortly.");
            redirect(SITE_URL . '/pages/orders.php');
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'An error occurred. Please try again.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-content">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="breadcrumb">
                <a href="<?php echo SITE_URL; ?>">Home</a>
                <span class="separator">/</span>
                <a href="<?php echo SITE_URL; ?>/pages/cart.php">Cart</a>
                <span class="separator">/</span>
                <span>Checkout</span>
            </div>
            <h1 class="page-title">Checkout</h1>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <span><?php echo implode('<br>', array_map('sanitize', $errors)); ?></span>
            <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="checkout-layout">
                <!-- Shipping Form -->
                <div>
                    <div class="checkout-form-section" style="margin-bottom: var(--space-6);">
                        <h2><i class="fas fa-truck" style="color: var(--accent-cyan); margin-right: 8px;"></i>Shipping Details</h2>
                        <div class="form-group">
                            <label class="form-label" for="shipping_name">Full Name *</label>
                            <input type="text" id="shipping_name" name="shipping_name" class="form-control" value="<?php echo sanitize($user['name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="shipping_phone">Phone Number *</label>
                            <input type="tel" id="shipping_phone" name="shipping_phone" class="form-control" value="<?php echo sanitize($user['phone'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="shipping_address">Delivery Address *</label>
                            <textarea id="shipping_address" name="shipping_address" class="form-control" rows="3" required><?php echo sanitize($user['address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="checkout-form-section">
                        <h2><i class="fas fa-credit-card" style="color: var(--accent-purple); margin-right: 8px;"></i>Payment Method</h2>
                        <div class="payment-options">
                            <label class="payment-option selected">
                                <input type="radio" name="payment_method" value="cod" checked>
                                <i class="fas fa-money-bill-wave" style="color: var(--accent-green); font-size: 1.2rem;"></i>
                                <span>Cash on Delivery</span>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="online" disabled>
                                <i class="fas fa-credit-card" style="color: var(--accent-purple); font-size: 1.2rem;"></i>
                                <span>Online Payment <small style="color: var(--text-muted);">(Coming Soon)</small></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="cart-summary">
                    <h3>Order Summary</h3>

                    <?php foreach ($cartItems as $item): ?>
                    <div class="flex gap-3 items-center" style="padding: var(--space-3) 0; border-bottom: 1px solid var(--border-color);">
                        <img src="<?php echo getProductImage($item['image']); ?>" alt="" style="width: 48px; height: 48px; border-radius: var(--radius-sm); object-fit: cover;">
                        <div style="flex: 1;">
                            <div style="font-size: var(--font-size-sm); font-weight: 600;"><?php echo sanitize(truncateText($item['name'], 30)); ?></div>
                            <div style="font-size: var(--font-size-xs); color: var(--text-muted);">Qty: <?php echo $item['quantity']; ?></div>
                        </div>
                        <div style="font-size: var(--font-size-sm); font-weight: 600;"><?php echo formatPrice($item['price'] * $item['quantity']); ?></div>
                    </div>
                    <?php endforeach; ?>

                    <div class="cart-summary-row" style="margin-top: var(--space-4);">
                        <span>Subtotal</span>
                        <span><?php echo formatPrice($cartTotal); ?></span>
                    </div>
                    <div class="cart-summary-row">
                        <span>Shipping</span>
                        <span style="color: var(--accent-green);"><?php echo $shippingCost > 0 ? formatPrice($shippingCost) : 'Free'; ?></span>
                    </div>
                    <div class="cart-summary-row total">
                        <span>Total</span>
                        <span class="price"><?php echo formatPrice($orderTotal); ?></span>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-full" style="margin-top: var(--space-6);">
                        <i class="fas fa-check"></i> Place Order
                    </button>
                    <a href="<?php echo SITE_URL; ?>/pages/cart.php" class="btn btn-secondary w-full" style="margin-top: var(--space-3);">
                        <i class="fas fa-arrow-left"></i> Back to Cart
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.payment-option').forEach(option => {
    option.addEventListener('click', () => {
        document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
        option.classList.add('selected');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
