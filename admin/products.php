<?php
/**
 * Admin — Manage Products
 */
$adminPageTitle = 'Products';
require_once __DIR__ . '/includes/header.php';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $stmt = $db->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$deleteId]);
    $product = $stmt->fetch();

    if ($product) {
        // Delete image file
        if ($product['image'] && $product['image'] !== 'default.png') {
            $imagePath = UPLOAD_DIR . $product['image'];
            if (file_exists($imagePath)) unlink($imagePath);
        }
        $db->prepare("DELETE FROM products WHERE id = ?")->execute([$deleteId]);
        setFlash('success', 'Product deleted successfully.');
    }
    redirect(SITE_URL . '/admin/products.php');
}

// Search
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];
if ($search) {
    $where = "WHERE p.name LIKE ? OR p.brand LIKE ?";
    $searchTerm = "%{$search}%";
    $params = [$searchTerm, $searchTerm];
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM products p $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $where ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<!-- Toolbar -->
<div class="flex justify-between items-center" style="margin-bottom: var(--space-6);">
    <form method="GET" class="header-search" style="max-width: 300px; display: flex;">
        <i class="fas fa-search"></i>
        <input type="text" name="search" placeholder="Search products..." value="<?php echo sanitize($search); ?>">
    </form>
    <a href="<?php echo SITE_URL; ?>/admin/add-product.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Product
    </a>
</div>

<!-- Products Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3>All Products (<?php echo $total; ?>)</h3>
    </div>
    <?php if (empty($products)): ?>
    <div class="admin-card-body" style="text-align: center; color: var(--text-muted); padding: var(--space-10);">
        No products found.
    </div>
    <?php else: ?>
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Featured</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td>
                        <div class="table-product">
                            <img src="<?php echo getProductImage($p['image']); ?>" alt="">
                            <span style="font-weight: 600;"><?php echo sanitize(truncateText($p['name'], 35)); ?></span>
                        </div>
                    </td>
                    <td><?php echo sanitize($p['category_name'] ?? 'N/A'); ?></td>
                    <td><?php echo sanitize($p['brand']); ?></td>
                    <td style="font-weight: 600;"><?php echo formatPrice($p['price']); ?></td>
                    <td>
                        <span class="badge <?php echo $p['stock'] > 5 ? 'badge-in-stock' : ($p['stock'] > 0 ? 'badge-pending' : 'badge-out-of-stock'); ?>">
                            <?php echo $p['stock']; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($p['featured']): ?>
                            <i class="fas fa-star" style="color: var(--accent-orange);"></i>
                        <?php else: ?>
                            <i class="far fa-star" style="color: var(--text-muted);"></i>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="admin-actions">
                            <a href="<?php echo SITE_URL; ?>/admin/edit-product.php?id=<?php echo $p['id']; ?>" class="btn btn-secondary btn-sm" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?php echo SITE_URL; ?>/admin/products.php?delete=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this product?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i == $page): ?>
        <span class="active"><?php echo $i; ?></span>
        <?php else: ?>
        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</div>
<?php endif; ?>

        </div>
    </main>
</div>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
</body>
</html>
