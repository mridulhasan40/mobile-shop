<?php
/**
 * Product Detail Page
 */
require_once __DIR__ . '/../includes/auth.php';

$db = getDB();

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    setFlash('error', 'Product not found.');
    redirect(SITE_URL . '/pages/products.php');
}

$stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Product not found.');
    redirect(SITE_URL . '/pages/products.php');
}

// Related products (same category, different product)
$relatedStmt = $db->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? ORDER BY RAND() LIMIT 4");
$relatedStmt->execute([$product['category_id'], $product['id']]);
$relatedProducts = $relatedStmt->fetchAll();

$pageTitle = $product['name'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-content">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>">Home</a>
            <span class="separator">/</span>
            <a href="<?php echo SITE_URL; ?>/pages/products.php">Products</a>
            <?php if ($product['category_name']): ?>
            <span class="separator">/</span>
            <a href="<?php echo SITE_URL; ?>/pages/products.php?category=<?php echo $product['category_id']; ?>"><?php echo sanitize($product['category_name']); ?></a>
            <?php endif; ?>
            <span class="separator">/</span>
            <span><?php echo sanitize($product['name']); ?></span>
        </div>

        <!-- Product Detail -->
        <div class="product-detail">
            <div class="product-detail-image">
                <img src="<?php echo getProductImage($product['image']); ?>" alt="<?php echo sanitize($product['name']); ?>">
            </div>

            <div class="product-detail-info">
                <div class="product-detail-brand"><?php echo sanitize($product['brand']); ?></div>
                <h1 class="product-detail-name"><?php echo sanitize($product['name']); ?></h1>
                <div class="product-detail-price"><?php echo formatPrice($product['price']); ?></div>

                <p class="product-detail-desc"><?php echo nl2br(sanitize($product['description'])); ?></p>

                <!-- Meta Info -->
                <div class="product-detail-meta">
                    <div class="product-detail-meta-item">
                        <span class="label">Brand</span>
                        <span class="value"><?php echo sanitize($product['brand']); ?></span>
                    </div>
                    <div class="product-detail-meta-item">
                        <span class="label">Category</span>
                        <span class="value"><?php echo sanitize($product['category_name'] ?? 'Uncategorized'); ?></span>
                    </div>
                    <div class="product-detail-meta-item">
                        <span class="label">Availability</span>
                        <span class="value">
                            <?php if ($product['stock'] > 0): ?>
                                <span class="badge badge-in-stock">In Stock (<?php echo $product['stock']; ?>)</span>
                            <?php else: ?>
                                <span class="badge badge-out-of-stock">Out of Stock</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <!-- Add to Cart -->
                <?php if ($product['stock'] > 0): ?>
                <form id="add-to-cart-form" class="flex gap-4 items-center" style="margin-bottom: var(--space-6);">
                    <div class="quantity-control">
                        <label>Quantity:</label>
                        <div class="quantity-input">
                            <button type="button" onclick="changeQty(-1)"><i class="fas fa-minus"></i></button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" readonly>
                            <button type="button" onclick="changeQty(1)"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </form>
                <button class="btn btn-primary btn-lg w-full" onclick="addToCart(<?php echo $product['id']; ?>, document.getElementById('quantity').value)" style="margin-bottom: var(--space-4);">
                    <i class="fas fa-shopping-bag"></i> Add to Cart
                </button>
                <?php else: ?>
                <button class="btn btn-secondary btn-lg w-full" disabled style="margin-bottom: var(--space-4);">
                    <i class="fas fa-times"></i> Out of Stock
                </button>
                <?php endif; ?>

                <a href="<?php echo SITE_URL; ?>/pages/products.php" class="btn btn-secondary w-full">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
        <section style="margin-top: var(--space-16);">
            <h2 class="section-title">Related Products</h2>
            <p class="section-subtitle">You might also like</p>
            <div class="grid-products">
                <?php foreach ($relatedProducts as $rp): ?>
                <div class="product-card">
                    <div class="product-card-image">
                        <a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $rp['id']; ?>">
                            <img src="<?php echo getProductImage($rp['image']); ?>" alt="<?php echo sanitize($rp['name']); ?>">
                        </a>
                    </div>
                    <div class="product-card-body">
                        <div class="product-card-brand"><?php echo sanitize($rp['brand']); ?></div>
                        <h3 class="product-card-name">
                            <a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $rp['id']; ?>">
                                <?php echo sanitize($rp['name']); ?>
                            </a>
                        </h3>
                        <div class="product-card-footer">
                            <span class="product-card-price"><?php echo formatPrice($rp['price']); ?></span>
                            <?php if ($rp['stock'] > 0): ?>
                            <button class="btn-add-cart" onclick="addToCart(<?php echo $rp['id']; ?>)" title="Add to Cart">
                                <i class="fas fa-plus"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</div>

<script>
function changeQty(delta) {
    const input = document.getElementById('quantity');
    let val = parseInt(input.value) + delta;
    const max = parseInt(input.max);
    if (val < 1) val = 1;
    if (val > max) val = max;
    input.value = val;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
