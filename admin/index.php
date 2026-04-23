<?php
/**
 * Admin Dashboard
 */
$adminPageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

// ── Stats ─────────────────────────────────────────────────────────────────────
$totalProducts  = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders    = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalUsers     = $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalRevenue   = $db->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$pendingOrders  = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$todayOrders    = $db->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$todayRevenue   = $db->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE DATE(created_at)=CURDATE() AND status!='cancelled'")->fetchColumn();



// ── Top Products ───────────────────────────────────────────────────────────────
$topProducts = $db->query("
    SELECT p.name, SUM(oi.quantity) as units_sold, SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    GROUP BY oi.product_id
    ORDER BY units_sold DESC
    LIMIT 5
")->fetchAll();

// ── Recent orders ──────────────────────────────────────────────────────────────
$recentOrders = $db->query("
    SELECT o.*, u.name as user_name
    FROM orders o JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC LIMIT 10
")->fetchAll();

// ── Low stock products ─────────────────────────────────────────────────────────
$lowStock = $db->query("SELECT * FROM products WHERE stock <= 5 ORDER BY stock ASC LIMIT 5")->fetchAll();
?>

<!-- Stats Cards -->
<div class="admin-stats" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card">
        <div class="stat-card-icon cyan"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-card-value"><?php echo formatPrice($totalRevenue); ?></div>
        <div class="stat-card-label">Total Revenue</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon purple"><i class="fas fa-shopping-cart"></i></div>
        <div class="stat-card-value"><?php echo $totalOrders; ?></div>
        <div class="stat-card-label">Total Orders</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon green"><i class="fas fa-box"></i></div>
        <div class="stat-card-value"><?php echo $totalProducts; ?></div>
        <div class="stat-card-label">Products</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon orange"><i class="fas fa-users"></i></div>
        <div class="stat-card-value"><?php echo $totalUsers; ?></div>
        <div class="stat-card-label">Customers</div>
    </div>
</div>

<!-- Today's Stats -->
<div class="grid grid-2" style="margin-bottom:var(--space-8);">
    <div class="admin-card">
        <div class="admin-card-body" style="display:flex;align-items:center;gap:var(--space-4);">
            <div style="width:48px;height:48px;background:rgba(14,165,233,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-calendar-day" style="color:var(--accent-cyan);font-size:1.2rem;"></i>
            </div>
            <div>
                <div style="font-size:var(--font-size-xs);color:var(--text-muted);">Today's Orders</div>
                <div style="font-size:1.8rem;font-weight:800;"><?php echo $todayOrders; ?></div>
            </div>
        </div>
    </div>
    <div class="admin-card">
        <div class="admin-card-body" style="display:flex;align-items:center;gap:var(--space-4);">
            <div style="width:48px;height:48px;background:rgba(34,197,94,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-coins" style="color:var(--accent-green);font-size:1.2rem;"></i>
            </div>
            <div>
                <div style="font-size:var(--font-size-xs);color:var(--text-muted);">Today's Revenue</div>
                <div style="font-size:1.8rem;font-weight:800;"><?php echo formatPrice($todayRevenue); ?></div>
            </div>
        </div>
    </div>
</div>



<!-- Quick Actions -->
<div class="admin-card" style="margin-bottom:var(--space-8);">
    <div class="admin-card-header"><h3>Quick Actions</h3></div>
    <div class="admin-card-body">
        <div class="quick-actions">
            <a href="<?php echo SITE_URL; ?>/admin/add-product.php" class="quick-action-card">
                <i class="fas fa-plus" style="background:rgba(0,212,255,.1);color:var(--accent-cyan);"></i>
                <div class="qa-info"><h4>Add Product</h4><p>Add a new product to the store</p></div>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="quick-action-card">
                <i class="fas fa-clock" style="background:rgba(245,158,11,.1);color:var(--accent-orange);"></i>
                <div class="qa-info"><h4>Pending Orders (<?php echo $pendingOrders; ?>)</h4><p>Orders waiting for processing</p></div>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/coupons.php" class="quick-action-card">
                <i class="fas fa-ticket-alt" style="background:rgba(34,197,94,.1);color:var(--accent-green);"></i>
                <div class="qa-info"><h4>Coupons</h4><p>Create and manage discount codes</p></div>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/export-orders.php" class="quick-action-card">
                <i class="fas fa-download" style="background:rgba(124,58,237,.1);color:var(--accent-purple);"></i>
                <div class="qa-info"><h4>Export Orders</h4><p>Download all orders as CSV</p></div>
            </a>
        </div>
    </div>
</div>

<div class="grid grid-2" style="margin-bottom:var(--space-8);">
    <!-- Top Products -->
    <div class="admin-card">
        <div class="admin-card-header"><h3>Top Selling Products</h3></div>
        <?php if (empty($topProducts)): ?>
        <div class="admin-card-body" style="text-align:center;color:var(--text-muted);padding:var(--space-10);">
            <i class="fas fa-box" style="font-size:2rem;display:block;margin-bottom:var(--space-4);"></i>No sales data yet
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead><tr><th>Product</th><th>Units Sold</th><th>Revenue</th></tr></thead>
                <tbody>
                    <?php foreach ($topProducts as $tp): ?>
                    <tr>
                        <td><?php echo sanitize(truncateText($tp['name'], 30)); ?></td>
                        <td><strong><?php echo $tp['units_sold']; ?></strong></td>
                        <td><?php echo formatPrice($tp['revenue']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent Orders -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Recent Orders</h3>
            <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <?php if (empty($recentOrders)): ?>
        <div class="admin-card-body" style="text-align:center;color:var(--text-muted);padding:var(--space-10);">
            <i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:var(--space-4);"></i>No orders yet
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td><strong>#<?php echo $order['id']; ?></strong></td>
                        <td><?php echo sanitize($order['user_name']); ?></td>
                        <td><?php echo formatPrice($order['total_price']); ?></td>
                        <td><span class="badge badge-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Low Stock Alert -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fas fa-exclamation-triangle" style="color:var(--accent-orange);margin-right:8px;"></i>Low Stock Alert</h3>
        <a href="<?php echo SITE_URL; ?>/admin/products.php" class="btn btn-secondary btn-sm">View All</a>
    </div>
    <?php if (empty($lowStock)): ?>
    <div class="admin-card-body" style="text-align:center;color:var(--text-muted);padding:var(--space-10);">
        <i class="fas fa-check-circle" style="font-size:2rem;display:block;margin-bottom:var(--space-4);color:var(--accent-green);"></i>
        All products are well stocked
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead><tr><th>Product</th><th>Stock</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($lowStock as $product): ?>
                <tr>
                    <td>
                        <div class="table-product">
                            <img src="<?php echo getProductImage($product['image']); ?>" alt="">
                            <span><?php echo sanitize(truncateText($product['name'], 30)); ?></span>
                        </div>
                    </td>
                    <td>
                        <span class="badge <?php echo $product['stock'] == 0 ? 'badge-out-of-stock' : 'badge-pending'; ?>">
                            <?php echo $product['stock']; ?> left
                        </span>
                    </td>
                    <td>
                        <a href="<?php echo SITE_URL; ?>/admin/edit-product.php?id=<?php echo $product['id']; ?>"
                           class="btn btn-secondary btn-sm">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

    </div></main>
</div>


<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
</body>
</html>
