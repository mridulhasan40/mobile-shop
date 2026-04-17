<?php
/**
 * Shopping Cart Page
 */
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Shopping Cart';

if (!isLoggedIn()) {
    setFlash('warning', 'Please log in to view your cart.');
    redirect(SITE_URL . '/pages/login.php');
}

$cartItems = getCartItems();
$cartTotal = getCartTotal();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-content">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="breadcrumb">
                <a href="<?php echo SITE_URL; ?>">Home</a>
                <span class="separator">/</span>
                <span>Shopping Cart</span>
            </div>
            <h1 class="page-title">Shopping Cart</h1>
        </div>

        <?php if (empty($cartItems)): ?>
        <div class="empty-state">
            <i class="fas fa-shopping-bag"></i>
            <h3>Your cart is empty</h3>
            <p>Looks like you haven't added any products to your cart yet.</p>
            <a href="<?php echo SITE_URL; ?>/pages/products.php" class="btn btn-primary btn-lg">
                <i class="fas fa-shopping-bag"></i> Start Shopping
            </a>
        </div>
        <?php else: ?>
        <div class="cart-layout">
            <!-- Cart Items -->
            <div class="cart-items">
                <?php foreach ($cartItems as $item): ?>
                <div class="cart-item" id="cart-item-<?php echo $item['product_id']; ?>">
                    <div class="cart-item-image">
                        <a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $item['product_id']; ?>">
                            <img src="<?php echo getProductImage($item['image']); ?>" alt="<?php echo sanitize($item['name']); ?>">
                        </a>
                    </div>
                    <div class="cart-item-info">
                        <h4><a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $item['product_id']; ?>" style="color: inherit; text-decoration: none;"><?php echo sanitize($item['name']); ?></a></h4>
                        <div class="brand"><?php echo sanitize($item['brand']); ?></div>
                        <div class="price"><?php echo formatPrice($item['price']); ?></div>
                    </div>
                    <div class="cart-item-actions">
                        <button class="cart-item-remove" onclick="removeFromCart(<?php echo $item['product_id']; ?>)" title="Remove">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                        <div class="quantity-input">
                            <button type="button" onclick="updateCartQty(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] - 1; ?>)"><i class="fas fa-minus"></i></button>
                            <input type="number" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" readonly>
                            <button type="button" onclick="updateCartQty(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] + 1; ?>)"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Cart Summary -->
            <div class="cart-summary">
                <h3>Order Summary</h3>
                <div class="cart-summary-row">
                    <span>Subtotal (<?php echo count($cartItems); ?> items)</span>
                    <span><?php echo formatPrice($cartTotal); ?></span>
                </div>
                <div class="cart-summary-row">
                    <span>Shipping</span>
                    <span style="color: var(--accent-green);"><?php echo $cartTotal >= 99 ? 'Free' : formatPrice(9.99); ?></span>
                </div>
                <div class="cart-summary-row total">
                    <span>Total</span>
                    <span class="price"><?php echo formatPrice($cartTotal >= 99 ? $cartTotal : $cartTotal + 9.99); ?></span>
                </div>
                <a href="<?php echo SITE_URL; ?>/pages/checkout.php" class="btn btn-primary btn-lg w-full" style="margin-top: var(--space-6);">
                    <i class="fas fa-lock"></i> Proceed to Checkout
                </a>
                <a href="<?php echo SITE_URL; ?>/pages/products.php" class="btn btn-secondary w-full" style="margin-top: var(--space-3);">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
