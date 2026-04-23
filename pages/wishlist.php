<?php
/**
 * Wishlist Page
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pageTitle = 'My Wishlist';
$items     = getWishlistItems();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-content">
    <div class="container">
        <div class="page-header">
            <div class="breadcrumb">
                <a href="<?php echo SITE_URL; ?>">Home</a>
                <span class="separator">/</span>
                <span>My Wishlist</span>
            </div>
            <h1 class="page-title">
                <i class="fas fa-heart" style="color:var(--accent-pink, #ec4899);"></i>
                My Wishlist
                <span style="font-size:1rem;font-weight:500;color:var(--text-muted);margin-left:8px;">(<?php echo count($items); ?> items)</span>
            </h1>
        </div>

        <?php if (empty($items)): ?>
        <div class="empty-state">
            <i class="fas fa-heart-broken"></i>
            <h3>Your wishlist is empty</h3>
            <p>Save products you love and come back to them anytime.</p>
            <a href="<?php echo SITE_URL; ?>/pages/products.php" class="btn btn-primary btn-lg">
                <i class="fas fa-shopping-bag"></i> Browse Products
            </a>
        </div>
        <?php else: ?>
        <div class="grid-products">
            <?php foreach ($items as $item): ?>
            <div class="product-card" id="wishlist-item-<?php echo $item['product_id']; ?>">
                <!-- Wishlist Remove -->
                <button class="wishlist-remove-btn"
                    onclick="toggleWishlist(<?php echo $item['product_id']; ?>, this)"
                    title="Remove from wishlist"
                    style="position:absolute;top:10px;right:10px;background:rgba(239,68,68,.1);border:none;border-radius:50%;width:36px;height:36px;cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:2;transition:all .2s;">
                    <i class="fas fa-heart" style="color:#ef4444;font-size:.9rem;"></i>
                </button>

                <div class="product-card-image" style="position:relative;">
                    <a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $item['product_id']; ?>">
                        <img src="<?php echo getProductImage($item['image']); ?>"
                             alt="<?php echo sanitize($item['name']); ?>">
                    </a>
                    <?php if ($item['stock'] == 0): ?>
                    <span class="badge badge-out-of-stock" style="position:absolute;top:10px;left:10px;">Out of Stock</span>
                    <?php endif; ?>
                </div>

                <div class="product-card-body">
                    <div class="product-card-brand"><?php echo sanitize($item['brand']); ?></div>
                    <h3 class="product-card-name">
                        <a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $item['product_id']; ?>">
                            <?php echo sanitize($item['name']); ?>
                        </a>
                    </h3>
                    <div class="product-card-footer">
                        <span class="product-card-price"><?php echo formatPrice($item['price']); ?></span>
                        <?php if ($item['stock'] > 0): ?>
                        <button class="btn-add-cart"
                            onclick="addToCart(<?php echo $item['product_id']; ?>)"
                            title="Add to Cart">
                            <i class="fas fa-shopping-bag"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
async function toggleWishlist(productId, btn) {
    try {
        const res = await fetch(`<?php echo SITE_URL; ?>/api/wishlist.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId })
        });
        const data = await res.json();
        if (data.success && data.action === 'removed') {
            const card = document.getElementById('wishlist-item-' + productId);
            card.style.opacity = '0';
            card.style.transform = 'scale(0.9)';
            card.style.transition = 'all .3s';
            setTimeout(() => card.remove(), 300);
        }
    } catch (e) {
        console.error(e);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
