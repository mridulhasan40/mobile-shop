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

// Track recently viewed
addRecentlyViewed($productId);

// Related products
$relatedStmt = $db->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? ORDER BY RAND() LIMIT 4");
$relatedStmt->execute([$product['category_id'], $product['id']]);
$relatedProducts = $relatedStmt->fetchAll();

// Reviews
$reviews      = getProductReviews($productId);
$ratingData   = getProductRating($productId);
$userReviewed = userHasReviewed($productId);
$canReview    = isLoggedIn() && !$userReviewed;
$inWishlist   = isInWishlist($productId);

// Rating distribution
$dist = [5=>0, 4=>0, 3=>0, 2=>0, 1=>0];
foreach ($reviews as $r) $dist[(int)$r['rating']]++;

$pageTitle   = $product['name'];
$pageDesc    = truncateText($product['description'] ?? '', 160);
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
            <a href="<?php echo SITE_URL; ?>/pages/products.php?category=<?php echo $product['category_id']; ?>">
                <?php echo sanitize($product['category_name']); ?>
            </a>
            <?php endif; ?>
            <span class="separator">/</span>
            <span><?php echo sanitize($product['name']); ?></span>
        </div>

        <!-- Product Detail -->
        <div class="product-detail">
            <div class="product-detail-image">
                <img src="<?php echo getProductImage($product['image']); ?>"
                     alt="<?php echo sanitize($product['name']); ?>"
                     id="main-product-img">
            </div>

            <div class="product-detail-info">
                <div class="product-detail-brand"><?php echo sanitize($product['brand']); ?></div>
                <h1 class="product-detail-name"><?php echo sanitize($product['name']); ?></h1>

                <!-- Rating Summary -->
                <?php if ($ratingData['total'] > 0): ?>
                <div class="flex items-center gap-3" style="margin-bottom: var(--space-4);">
                    <?php echo renderStars((float)$ratingData['avg']); ?>
                    <span style="font-weight:700; color:var(--text-primary);"><?php echo number_format($ratingData['avg'],1); ?></span>
                    <span style="color:var(--text-muted); font-size:var(--font-size-sm);">(<?php echo $ratingData['total']; ?> reviews)</span>
                </div>
                <?php else: ?>
                <div style="margin-bottom:var(--space-4);color:var(--text-muted);font-size:var(--font-size-sm);">
                    <i class="far fa-star"></i> No reviews yet — be the first!
                </div>
                <?php endif; ?>

                <?php if (hasDiscount($product)): ?>
                <div class="product-detail-price-group">
                    <span class="price-current"><?php echo formatPrice($product['discount_price']); ?></span>
                    <span class="price-was"><?php echo formatPrice($product['price']); ?></span>
                    <span class="discount-badge" style="margin-left:var(--space-2);">-<?php echo getDiscountPercent($product); ?>% OFF</span>
                    <div class="price-savings">
                        <i class="fas fa-tag"></i>
                        You save <?php echo formatPrice($product['price'] - $product['discount_price']); ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="product-detail-price"><?php echo formatPrice($product['price']); ?></div>
                <?php endif; ?>

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

                <!-- Add to Cart + Wishlist -->
                <?php if ($product['stock'] > 0): ?>
                <form id="add-to-cart-form" class="flex gap-4 items-center" style="margin-bottom: var(--space-4);">
                    <div class="quantity-control">
                        <label>Quantity:</label>
                        <div class="quantity-input">
                            <button type="button" onclick="changeQty(-1)"><i class="fas fa-minus"></i></button>
                            <input type="number" id="quantity" name="quantity" value="1"
                                   min="1" max="<?php echo $product['stock']; ?>" readonly>
                            <button type="button" onclick="changeQty(1)"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </form>
                <button class="btn btn-primary btn-lg w-full"
                    onclick="addToCart(<?php echo $product['id']; ?>, document.getElementById('quantity').value)"
                    style="margin-bottom: var(--space-3);">
                    <i class="fas fa-shopping-bag"></i> Add to Cart
                </button>
                <?php else: ?>
                <button class="btn btn-secondary btn-lg w-full" disabled style="margin-bottom: var(--space-3);">
                    <i class="fas fa-times"></i> Out of Stock
                </button>
                <?php endif; ?>

                <!-- Wishlist Button -->
                <button class="btn w-full <?php echo $inWishlist ? 'btn-primary' : 'btn-secondary'; ?>"
                    id="wishlist-btn"
                    onclick="toggleWishlistBtn(<?php echo $product['id']; ?>, this)"
                    style="margin-bottom:var(--space-4);">
                    <i class="<?php echo $inWishlist ? 'fas' : 'far'; ?> fa-heart" id="wishlist-icon"></i>
                    <span id="wishlist-label"><?php echo $inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?></span>
                </button>

                <a href="<?php echo SITE_URL; ?>/pages/products.php" class="btn btn-secondary w-full">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
            </div>
        </div>

        <!-- ─── Reviews Section ─────────────────────────────────────────── -->
        <section style="margin-top: var(--space-16);">
            <h2 class="section-title">Customer Reviews</h2>

            <?php if ($ratingData['total'] > 0): ?>
            <!-- Rating Overview -->
            <div style="display:grid;grid-template-columns:auto 1fr;gap:var(--space-8);align-items:center;background:var(--bg-glass);border-radius:var(--radius-lg);padding:var(--space-8);margin-bottom:var(--space-8);border:1px solid var(--border-color);">
                <div style="text-align:center;">
                    <div style="font-size:4rem;font-weight:800;color:var(--accent-cyan);line-height:1;"><?php echo number_format($ratingData['avg'],1); ?></div>
                    <div style="margin:8px 0;"><?php echo renderStars((float)$ratingData['avg']); ?></div>
                    <div style="color:var(--text-muted);font-size:var(--font-size-sm);"><?php echo $ratingData['total']; ?> reviews</div>
                </div>
                <div>
                    <?php for ($s = 5; $s >= 1; $s--): ?>
                    <?php $pct = $ratingData['total'] > 0 ? ($dist[$s] / $ratingData['total'] * 100) : 0; ?>
                    <div class="flex items-center gap-3" style="margin-bottom:8px;">
                        <span style="font-size:var(--font-size-sm);width:16px;color:var(--text-muted);"><?php echo $s; ?></span>
                        <i class="fas fa-star" style="color:#f59e0b;font-size:.75rem;"></i>
                        <div style="flex:1;height:8px;background:var(--border-color);border-radius:4px;overflow:hidden;">
                            <div style="height:100%;width:<?php echo $pct; ?>%;background:linear-gradient(90deg,#f59e0b,#f97316);border-radius:4px;transition:width .6s;"></div>
                        </div>
                        <span style="font-size:var(--font-size-xs);color:var(--text-muted);width:28px;"><?php echo $dist[$s]; ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Write a Review -->
            <?php if (isLoggedIn() && !$userReviewed): ?>
            <div class="profile-card" style="margin-bottom:var(--space-8);" id="review-form-section">
                <h3 style="margin-bottom:var(--space-4);"><i class="fas fa-pen" style="color:var(--accent-cyan);margin-right:8px;"></i>Write a Review</h3>
                <form id="review-form">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <div class="form-group">
                        <label class="form-label">Your Rating *</label>
                        <div class="star-picker" id="star-picker">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="far fa-star star-pick" data-val="<?php echo $i; ?>"
                               style="font-size:1.8rem;cursor:pointer;color:#d1d5db;transition:color .15s;margin-right:4px;"></i>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="rating-input" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Comment (optional)</label>
                        <textarea name="comment" class="form-control" rows="4"
                            placeholder="Share your experience with this product..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Review
                    </button>
                    <div id="review-msg" style="margin-top:var(--space-3);display:none;"></div>
                </form>
            </div>
            <?php elseif (!isLoggedIn()): ?>
            <div class="alert alert-warning" style="margin-bottom:var(--space-8);">
                <span><i class="fas fa-info-circle"></i>
                    <a href="<?php echo SITE_URL; ?>/pages/login.php" style="color:inherit;font-weight:600;">Sign in</a>
                    to write a review.
                </span>
            </div>
            <?php elseif ($userReviewed): ?>
            <div class="alert alert-success" style="margin-bottom:var(--space-8);">
                <span><i class="fas fa-check-circle"></i> You have already reviewed this product. Thank you!</span>
            </div>
            <?php endif; ?>

            <!-- Review List -->
            <?php if (empty($reviews)): ?>
            <div class="empty-state" style="padding:var(--space-10);">
                <i class="far fa-comment-dots"></i>
                <h3>No reviews yet</h3>
                <p>Be the first to review this product!</p>
            </div>
            <?php else: ?>
            <div id="reviews-list">
            <?php foreach ($reviews as $review): ?>
            <div class="profile-card" style="margin-bottom:var(--space-4);">
                <div class="flex justify-between items-start" style="margin-bottom:var(--space-3);">
                    <div>
                        <div style="font-weight:600;margin-bottom:2px;"><?php echo sanitize($review['user_name']); ?></div>
                        <div style="font-size:var(--font-size-xs);color:var(--text-muted);">
                            <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                        </div>
                    </div>
                    <div><?php echo renderStars($review['rating']); ?></div>
                </div>
                <?php if (!empty($review['comment'])): ?>
                <p style="color:var(--text-secondary);font-size:var(--font-size-sm);margin:0;">
                    <?php echo sanitize($review['comment']); ?>
                </p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

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
                        <?php if (hasDiscount($rp)): ?>
                        <span class="discount-badge-card">-<?php echo getDiscountPercent($rp); ?>% OFF</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-card-body">
                        <div class="product-card-brand"><?php echo sanitize($rp['brand']); ?></div>
                        <h3 class="product-card-name">
                            <a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $rp['id']; ?>">
                                <?php echo sanitize($rp['name']); ?>
                            </a>
                        </h3>
                        <div class="product-card-footer">
                            <?php echo renderCardPrice($rp); ?>
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
// Qty control
function changeQty(delta) {
    const input = document.getElementById('quantity');
    let val = parseInt(input.value) + delta;
    const max = parseInt(input.max);
    if (val < 1) val = 1;
    if (val > max) val = max;
    input.value = val;
}

// ── Star Picker ────────────────────────────────────────────────────────────────
const stars = document.querySelectorAll('.star-pick');
const ratingInput = document.getElementById('rating-input');

stars.forEach(star => {
    star.addEventListener('mouseover', function() {
        const val = parseInt(this.dataset.val);
        stars.forEach((s, i) => {
            s.className = i < val ? 'fas fa-star star-pick' : 'far fa-star star-pick';
            s.style.color = i < val ? '#f59e0b' : '#d1d5db';
        });
    });
    star.addEventListener('click', function() {
        ratingInput.value = this.dataset.val;
    });
    star.addEventListener('mouseout', function() {
        const selected = parseInt(ratingInput.value);
        stars.forEach((s, i) => {
            s.className = i < selected ? 'fas fa-star star-pick' : 'far fa-star star-pick';
            s.style.color = i < selected ? '#f59e0b' : '#d1d5db';
        });
    });
});

// ── Submit Review ──────────────────────────────────────────────────────────────
const reviewForm = document.getElementById('review-form');
if (reviewForm) {
    reviewForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const msg = document.getElementById('review-msg');
        const rating = parseInt(document.getElementById('rating-input').value);
        if (rating < 1) {
            msg.style.display = 'block';
            msg.className = 'alert alert-error';
            msg.textContent = 'Please select a star rating.';
            return;
        }
        const fd = new FormData(this);
        try {
            const res  = await fetch('<?php echo SITE_URL; ?>/api/reviews.php', { method:'POST', body: fd });
            const data = await res.json();
            msg.style.display = 'block';
            if (data.success) {
                msg.className = 'alert alert-success';
                msg.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                reviewForm.reset();
                stars.forEach(s => { s.className='far fa-star star-pick'; s.style.color='#d1d5db'; });
                ratingInput.value = 0;
                setTimeout(() => location.reload(), 1500);
            } else {
                msg.className = 'alert alert-error';
                msg.textContent = data.message;
            }
        } catch(err) {
            msg.style.display='block';
            msg.className='alert alert-error';
            msg.textContent='An error occurred. Please try again.';
        }
    });
}

// ── Wishlist Toggle ────────────────────────────────────────────────────────────
async function toggleWishlistBtn(productId, btn) {
    try {
        const res  = await fetch('<?php echo SITE_URL; ?>/api/wishlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId })
        });
        const data = await res.json();
        if (!data.success) { alert(data.message); return; }

        const icon  = document.getElementById('wishlist-icon');
        const label = document.getElementById('wishlist-label');
        if (data.action === 'added') {
            icon.className  = 'fas fa-heart';
            label.textContent = 'Remove from Wishlist';
            btn.className   = 'btn w-full btn-primary';
        } else {
            icon.className  = 'far fa-heart';
            label.textContent = 'Add to Wishlist';
            btn.className   = 'btn w-full btn-secondary';
        }
    } catch(e) { console.error(e); }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
