<?php
/**
 * Product Listing Page
 */
$pageTitle = 'Products';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// Get filter parameters
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$brand = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$featured = isset($_GET['featured']) ? (int)$_GET['featured'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($categoryId > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryId;
}

if ($brand) {
    $where[] = "p.brand = ?";
    $params[] = $brand;
}

if ($minPrice > 0) {
    $where[] = "p.price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice > 0) {
    $where[] = "p.price <= ?";
    $params[] = $maxPrice;
}

if ($search) {
    $where[] = "(p.name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($featured) {
    $where[] = "p.featured = 1";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Sort
$orderBy = match($sort) {
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'name_asc' => 'p.name ASC',
    'name_desc' => 'p.name DESC',
    'oldest' => 'p.created_at ASC',
    default => 'p.created_at DESC',
};

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM products p $whereClause");
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Fetch products
$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $whereClause ORDER BY $orderBy LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories and brands for filters
$categories = getCategories();
$brands = getBrands();

// Build query string for pagination
$queryParams = $_GET;
unset($queryParams['page']);
$queryString = http_build_query($queryParams);
?>

<div class="page-content">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="breadcrumb">
                <a href="<?php echo SITE_URL; ?>">Home</a>
                <span class="separator">/</span>
                <span>Products</span>
                <?php if ($categoryId > 0): ?>
                    <?php foreach ($categories as $cat): ?>
                        <?php if ($cat['id'] == $categoryId): ?>
                        <span class="separator">/</span>
                        <span><?php echo sanitize($cat['name']); ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="flex justify-between items-center">
                <h1 class="page-title">
                    <?php if ($search): ?>
                        Search: "<?php echo sanitize($search); ?>"
                    <?php elseif ($featured): ?>
                        Featured Products
                    <?php else: ?>
                        All Products
                    <?php endif; ?>
                </h1>
                <span style="color: var(--text-muted); font-size: var(--font-size-sm);"><?php echo $totalProducts; ?> products found</span>
            </div>
        </div>

        <div class="products-layout">
            <!-- Filter Sidebar -->
            <aside class="filter-sidebar">
                <form method="GET" action="" id="filter-form">
                    <?php if ($search): ?>
                    <input type="hidden" name="search" value="<?php echo sanitize($search); ?>">
                    <?php endif; ?>

                    <!-- Categories -->
                    <div class="filter-section">
                        <h3>Categories</h3>
                        <label class="filter-option">
                            <input type="radio" name="category" value="0" <?php echo $categoryId == 0 ? 'checked' : ''; ?> onchange="this.form.submit()">
                            All Categories
                        </label>
                        <?php foreach ($categories as $cat): ?>
                        <label class="filter-option">
                            <input type="radio" name="category" value="<?php echo $cat['id']; ?>" <?php echo $categoryId == $cat['id'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <?php echo sanitize($cat['name']); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- Brands -->
                    <div class="filter-section">
                        <h3>Brands</h3>
                        <?php foreach ($brands as $b): ?>
                        <label class="filter-option">
                            <input type="checkbox" name="brand" value="<?php echo sanitize($b); ?>" <?php echo $brand === $b ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <?php echo sanitize($b); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- Price Range -->
                    <div class="filter-section">
                        <h3>Price Range</h3>
                        <div class="price-range">
                            <input type="number" name="min_price" placeholder="Min" value="<?php echo $minPrice > 0 ? $minPrice : ''; ?>" min="0" step="1">
                            <span>—</span>
                            <input type="number" name="max_price" placeholder="Max" value="<?php echo $maxPrice > 0 ? $maxPrice : ''; ?>" min="0" step="1">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-full" style="margin-top: var(--space-3);">Apply Filter</button>
                    </div>

                    <!-- Sort -->
                    <div class="filter-section">
                        <h3>Sort By</h3>
                        <select name="sort" class="form-control" onchange="this.form.submit()">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                            <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                        </select>
                    </div>

                    <!-- Clear Filters -->
                    <a href="<?php echo SITE_URL; ?>/pages/products.php" class="btn btn-secondary btn-sm w-full">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                </form>
            </aside>

            <!-- Products Grid -->
            <div>
                <?php if (empty($products)): ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>No products found</h3>
                    <p>Try adjusting your filters or search terms</p>
                    <a href="<?php echo SITE_URL; ?>/pages/products.php" class="btn btn-primary">View All Products</a>
                </div>
                <?php else: ?>
                <div class="grid-products">
                    <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-card-image">
                            <a href="<?php echo SITE_URL; ?>/pages/product-detail.php?id=<?php echo $product['id']; ?>">
                                <img src="<?php echo getProductImage($product['image']); ?>" alt="<?php echo sanitize($product['name']); ?>">
                            </a>
                            <?php if ($product['featured']): ?>
                            <span class="product-card-badge">Featured</span>
                            <?php endif; ?>
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

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?<?php echo $queryString; ?>&page=<?php echo $page - 1; ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                        <?php elseif ($i <= 2 || $i >= $totalPages - 1 || abs($i - $page) <= 1): ?>
                        <a href="?<?php echo $queryString; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php elseif ($i == 3 || $i == $totalPages - 2): ?>
                        <span class="dots">...</span>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                    <a href="?<?php echo $queryString; ?>&page=<?php echo $page + 1; ?>"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
