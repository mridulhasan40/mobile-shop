<?php
/**
 * Checkout Page
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';
requireLogin();

$db = getDB();
$pageTitle = 'Checkout';

$cartItems    = getCartItems();
$cartTotal    = getCartTotal();
$shippingCost = $cartTotal >= 99 ? 0 : 9.99;

if (empty($cartItems)) {
    setFlash('warning', 'Your cart is empty.');
    redirect(SITE_URL . '/pages/cart.php');
}

$user           = getCurrentUser();
$errors         = [];
$discount       = 0;
$appliedCoupon  = null;

// Reload coupon from session if already applied
if (!empty($_SESSION['applied_coupon'])) {
    $couponResult = validateCoupon($_SESSION['applied_coupon'], $cartTotal);
    if ($couponResult['valid']) {
        $discount      = $couponResult['discount'];
        $appliedCoupon = $couponResult['coupon'];
    } else {
        unset($_SESSION['applied_coupon']);
    }
}

$orderTotal = max(0, $cartTotal + $shippingCost - $discount);

// ── Apply Coupon (AJAX) ────────────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'apply_coupon') {
    header('Content-Type: application/json');
    $code   = trim($_POST['coupon_code'] ?? '');
    $result = validateCoupon($code, $cartTotal);
    if ($result['valid']) {
        $_SESSION['applied_coupon'] = strtoupper($code);
        echo json_encode([
            'success'  => true,
            'message'  => $result['message'],
            'discount' => $result['discount'],
            'total'    => max(0, $cartTotal + $shippingCost - $result['discount']),
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
    exit;
}

// ── Remove Coupon ──────────────────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'remove_coupon') {
    unset($_SESSION['applied_coupon']);
    redirect(SITE_URL . '/pages/checkout.php');
}

// ── Place Order ────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    }

    $shippingName    = trim($_POST['shipping_name']    ?? '');
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $shippingPhone   = trim($_POST['shipping_phone']   ?? '');
    $paymentMethod   = $_POST['payment_method']        ?? 'cod';

    if (empty($shippingName))    $errors[] = 'Shipping name is required.';
    if (empty($shippingAddress)) $errors[] = 'Shipping address is required.';
    if (empty($shippingPhone))   $errors[] = 'Phone number is required.';
    if (!preg_match('/^[\d\s+\-()]{7,20}$/', $shippingPhone)) $errors[] = 'Please enter a valid phone number.';

    // Re-validate coupon at submit time
    if (!empty($_SESSION['applied_coupon'])) {
        $couponResult = validateCoupon($_SESSION['applied_coupon'], $cartTotal);
        if ($couponResult['valid']) {
            $discount      = $couponResult['discount'];
            $appliedCoupon = $couponResult['coupon'];
        }
    }
    $orderTotal = max(0, $cartTotal + $shippingCost - $discount);

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $orderStmt = $db->prepare("
                INSERT INTO orders
                (user_id, total_price, shipping_name, shipping_address, shipping_phone,
                 payment_method, coupon_id, discount_amount, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $orderStmt->execute([
                $_SESSION['user_id'], $orderTotal, $shippingName,
                $shippingAddress, $shippingPhone, $paymentMethod,
                $appliedCoupon ? $appliedCoupon['id'] : null,
                $discount,
            ]);
            $orderId = $db->lastInsertId();

            // Order items + stock reduction
            $itemStmt  = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stockStmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
            foreach ($cartItems as $item) {
                $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
                $stockStmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
            }

            // Initial status history
            addOrderStatusHistory($orderId, 'pending', 'Order placed successfully.');

            // Mark coupon used
            if ($appliedCoupon) {
                useCoupon($appliedCoupon['id']);
                unset($_SESSION['applied_coupon']);
            }

            // Clear cart
            $db->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$_SESSION['user_id']]);
            $db->commit();

            // Send confirmation email (non-blocking)
            sendOrderConfirmationEmail($user['email'], $user['name'], $orderId, $orderTotal);

            setFlash('success', "Order #$orderId placed successfully! A confirmation email has been sent.");
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

        <form method="POST" action="" id="checkout-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="checkout-layout">
                <!-- Shipping + Payment -->
                <div>
                    <div class="checkout-form-section" style="margin-bottom: var(--space-6);">
                        <h2><i class="fas fa-truck" style="color:var(--accent-cyan);margin-right:8px;"></i>Shipping Details</h2>
                        <div class="form-group">
                            <label class="form-label" for="shipping_name">Full Name *</label>
                            <input type="text" id="shipping_name" name="shipping_name" class="form-control"
                                value="<?php echo sanitize($user['name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="shipping_phone">Phone Number *</label>
                            <input type="tel" id="shipping_phone" name="shipping_phone" class="form-control"
                                value="<?php echo sanitize($user['phone'] ?? ''); ?>"
                                placeholder="e.g. 01712345678" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="shipping_address">Delivery Address *</label>
                            <textarea id="shipping_address" name="shipping_address" class="form-control"
                                rows="3" required><?php echo sanitize($user['address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Coupon Code -->
                    <div class="checkout-form-section" style="margin-bottom: var(--space-6);">
                        <h2><i class="fas fa-ticket-alt" style="color:var(--accent-green);margin-right:8px;"></i>Coupon Code</h2>
                        <?php if ($appliedCoupon): ?>
                        <div class="alert alert-success" style="margin-bottom:var(--space-3);">
                            <span><i class="fas fa-check-circle"></i>
                                Coupon <strong><?php echo sanitize($appliedCoupon['code']); ?></strong> applied!
                                You save <?php echo formatPrice($discount); ?>
                            </span>
                        </div>
                        <form method="POST" action="" style="display:inline;">
                            <input type="hidden" name="action" value="remove_coupon">
                            <button type="submit" class="btn btn-secondary btn-sm">
                                <i class="fas fa-times"></i> Remove Coupon
                            </button>
                        </form>
                        <?php else: ?>
                        <div class="flex gap-3">
                            <input type="text" id="coupon-input" class="form-control"
                                placeholder="Enter coupon code (e.g. WELCOME10)"
                                style="flex:1; text-transform:uppercase;">
                            <button type="button" class="btn btn-secondary" onclick="applyCoupon()">
                                <i class="fas fa-tag"></i> Apply
                            </button>
                        </div>
                        <div id="coupon-msg" style="display:none;margin-top:var(--space-3);"></div>
                        <?php endif; ?>
                    </div>

                    <!-- Payment Method -->
                    <div class="checkout-form-section">
                        <h2><i class="fas fa-credit-card" style="color:var(--accent-purple);margin-right:8px;"></i>Payment Method</h2>
                        <div class="payment-options">
                            <label class="payment-option selected">
                                <input type="radio" name="payment_method" value="cod" checked>
                                <i class="fas fa-money-bill-wave" style="color:var(--accent-green);font-size:1.2rem;"></i>
                                <span>Cash on Delivery</span>
                            </label>
                            <label class="payment-option" style="opacity:.6;cursor:not-allowed;">
                                <input type="radio" name="payment_method" value="online" disabled>
                                <i class="fas fa-credit-card" style="color:var(--accent-purple);font-size:1.2rem;"></i>
                                <span>Online Payment <small style="color:var(--text-muted);">(Coming Soon)</small></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="cart-summary">
                    <h3>Order Summary</h3>

                    <?php foreach ($cartItems as $item): ?>
                    <div class="flex gap-3 items-center" style="padding:var(--space-3) 0;border-bottom:1px solid var(--border-color);">
                        <img src="<?php echo getProductImage($item['image']); ?>" alt=""
                             style="width:48px;height:48px;border-radius:var(--radius-sm);object-fit:cover;">
                        <div style="flex:1;">
                            <div style="font-size:var(--font-size-sm);font-weight:600;"><?php echo sanitize(truncateText($item['name'], 30)); ?></div>
                            <div style="font-size:var(--font-size-xs);color:var(--text-muted);">Qty: <?php echo $item['quantity']; ?></div>
                        </div>
                        <div style="font-size:var(--font-size-sm);font-weight:600;"><?php echo formatPrice($item['price'] * $item['quantity']); ?></div>
                    </div>
                    <?php endforeach; ?>

                    <div class="cart-summary-row" style="margin-top:var(--space-4);">
                        <span>Subtotal</span>
                        <span><?php echo formatPrice($cartTotal); ?></span>
                    </div>
                    <div class="cart-summary-row">
                        <span>Shipping</span>
                        <span style="color:var(--accent-green);"><?php echo $shippingCost > 0 ? formatPrice($shippingCost) : 'Free'; ?></span>
                    </div>
                    <?php if ($discount > 0): ?>
                    <div class="cart-summary-row" id="discount-row">
                        <span style="color:var(--accent-green);"><i class="fas fa-tag"></i> Discount</span>
                        <span style="color:var(--accent-green);">-<?php echo formatPrice($discount); ?></span>
                    </div>
                    <?php else: ?>
                    <div class="cart-summary-row" id="discount-row" style="display:none;">
                        <span style="color:var(--accent-green);"><i class="fas fa-tag"></i> Discount</span>
                        <span id="discount-amount" style="color:var(--accent-green);">-$0.00</span>
                    </div>
                    <?php endif; ?>
                    <div class="cart-summary-row total">
                        <span>Total</span>
                        <span class="price" id="order-total"><?php echo formatPrice($orderTotal); ?></span>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-full" style="margin-top:var(--space-6);">
                        <i class="fas fa-check"></i> Place Order
                    </button>
                    <a href="<?php echo SITE_URL; ?>/pages/cart.php" class="btn btn-secondary w-full" style="margin-top:var(--space-3);">
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

async function applyCoupon() {
    const code = document.getElementById('coupon-input').value.trim();
    const msg  = document.getElementById('coupon-msg');
    if (!code) { msg.style.display='block'; msg.className='alert alert-warning'; msg.textContent='Please enter a coupon code.'; return; }

    try {
        const fd = new FormData();
        fd.append('action', 'apply_coupon');
        fd.append('coupon_code', code);
        const res  = await fetch('', { method: 'POST', body: fd });
        const data = await res.json();
        msg.style.display = 'block';
        if (data.success) {
            msg.className = 'alert alert-success';
            msg.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
            // Update totals
            document.getElementById('discount-row').style.display = '';
            document.getElementById('discount-amount').textContent = '-$' + data.discount.toFixed(2);
            document.getElementById('order-total').textContent = '$' + data.total.toFixed(2);
        } else {
            msg.className = 'alert alert-error';
            msg.textContent = data.message;
        }
    } catch(e) { console.error(e); }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
