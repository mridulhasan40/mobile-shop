<?php
/**
 * Homepage — MobileShop
 */
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Featured products
$featuredStmt = $db->query("SELECT * FROM products WHERE featured = 1 ORDER BY created_at DESC LIMIT 8");
$featuredProducts = $featuredStmt->fetchAll();

// Latest products
$latestStmt = $db->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 8");
$latestProducts = $latestStmt->fetchAll();

// Categories with product count
$catStmt = $db->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.name");
$categories = $catStmt->fetchAll();

$categoryIcons = [
    'Smartphones' => 'fa-mobile-screen',
    'Tablets' => 'fa-tablet-screen-button',
    'Accessories' => 'fa-headphones',
    'Chargers & Cables' => 'fa-plug',
    'Cases & Covers' => 'fa-shield-halved',
];
?>

<!-- Hero -->
<section class="hero" id="hero">
    <div class="container">
        <p class="hero-label">New Collection 2026</p>
        <h1 class="hero-title">The Next Generation<br>of <span>Mobile Technology</span></h1>
        <p class="hero-desc">Premium smartphones and accessories from world-class brands.<br>Experience innovation, performance, and style — all in one place.</p>
        <div class="hero-actions">
            <a href="<?php echo SITE_URL; ?>/pages/products.php" class="btn btn-primary btn-lg">
                Explore Products <i class="fas fa-arrow-right"></i>
            </a>
            <a href="<?php echo SITE_URL; ?>/pages/products.php?featured=1" class="btn btn-ghost btn-lg">
                <i class="fas fa-star"></i> Featured Deals
            </a>
        </div>
        <div class="hero-metrics">
            <div class="hero-metric">
                <strong>500+</strong>
                <span>Products</span>
            </div>
            <div class="hero-metric-divider"></div>
            <div class="hero-metric">
                <strong>50+</strong>
                <span>Brands</span>
            </div>
            <div class="hero-metric-divider"></div>
            <div class="hero-metric">
                <strong>24/7</strong>
                <span>Support</span>
            </div>
            <div class="hero-metric-divider"></div>
            <div class="hero-metric">
                <strong>Free</strong>
                <span>Shipping 99$+</span>
            </div>
        </div>
    </div>
</section>

<!-- Features Strip -->
<section class="features-strip">
    <div class="container">
        <div class="features-grid">
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-truck-fast"></i></div>
                <div>
                    <strong>Free Delivery</strong>
                    <span>On orders over $99</span>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-shield-halved"></i></div>
                <div>
                    <strong>Official Warranty</strong>
                    <span>1 Year guarantee</span>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-rotate-left"></i></div>
                <div>
                    <strong>Easy Returns</strong>
                    <span>7-day return policy</span>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-headset"></i></div>
                <div>
                    <strong>24/7 Support</strong>
                    <span>Dedicated care</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories -->
<section class="section" id="categories">
    <div class="container">
        <div class="section-top">
            <h2>Shop by Category</h2>
            <p>Find exactly what you need</p>
        </div>
        <div class="category-grid">
            <?php foreach ($categories as $cat): ?>
            <a href="<?php echo SITE_URL; ?>/pages/products.php?category=<?php echo $cat['id']; ?>" class="category-card">
                <div class="category-icon">
                    <i class="fas <?php echo $categoryIcons[$cat['name']] ?? 'fa-tag'; ?>"></i>
                </div>
                <h4><?php echo sanitize($cat['name']); ?></h4>
                <span><?php echo $cat['product_count']; ?> products</span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="section section--gray" id="featured">
    <div class="container">
        <div class="section-top section-top--row">
            <div>
                <h2>Featured Products</h2>
                <p>Handpicked by our experts</p>
            </div>
            <a href="<?php echo SITE_URL; ?>/pages/products.php?featured=1" class="btn btn-ghost">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="grid-products">
            <?php foreach ($featuredProducts as $product): ?>
            <div class="product-card">
                <div class="product-card-image">
                    <a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $product['id']; ?>">
                        <img src="<?php echo getProductImage($product['image']); ?>" alt="<?php echo sanitize($product['name']); ?>">
                    </a>
                    <span class="product-card-badge">Featured</span>
                </div>
                <div class="product-card-body">
                    <div class="product-card-brand"><?php echo sanitize($product['brand']); ?></div>
                    <h3 class="product-card-name">
                        <a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $product['id']; ?>">
                            <?php echo sanitize($product['name']); ?>
                        </a>
                    </h3>
                    <div class="product-card-footer">
                        <span class="product-card-price"><?php echo formatPrice($product['price']); ?></span>
                        <?php if ($product['stock'] > 0): ?>
                        <button class="btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>)" title="Add to Cart">
                            <i class="fas fa-plus"></i>
                        </button>
                        <?php else: ?>
                        <span class="badge badge-out-of-stock">Sold Out</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Latest Products -->
<section class="section" id="latest">
    <div class="container">
        <div class="section-top section-top--row">
            <div>
                <h2>Latest Arrivals</h2>
                <p>Newest additions to our collection</p>
            </div>
            <a href="<?php echo SITE_URL; ?>/pages/products.php" class="btn btn-ghost">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="grid-products">
            <?php foreach ($latestProducts as $product): ?>
            <div class="product-card">
                <div class="product-card-image">
                    <a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $product['id']; ?>">
                        <img src="<?php echo getProductImage($product['image']); ?>" alt="<?php echo sanitize($product['name']); ?>">
                    </a>
                </div>
                <div class="product-card-body">
                    <div class="product-card-brand"><?php echo sanitize($product['brand']); ?></div>
                    <h3 class="product-card-name">
                        <a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $product['id']; ?>">
                            <?php echo sanitize($product['name']); ?>
                        </a>
                    </h3>
                    <div class="product-card-footer">
                        <span class="product-card-price"><?php echo formatPrice($product['price']); ?></span>
                        <?php if ($product['stock'] > 0): ?>
                        <button class="btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>)" title="Add to Cart">
                            <i class="fas fa-plus"></i>
                        </button>
                        <?php else: ?>
                        <span class="badge badge-out-of-stock">Sold Out</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
