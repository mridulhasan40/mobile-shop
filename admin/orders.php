<?php
/**
 * Admin — Manage Orders
 */
$adminPageTitle = 'Orders';
require_once __DIR__ . '/includes/header.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    $validStatuses = ['pending', 'processing', 'delivered', 'cancelled'];

    if (in_array($newStatus, $validStatuses)) {
        // Get current order status
        $currentStmt = $db->prepare("SELECT status FROM orders WHERE id = ?");
        $currentStmt->execute([$orderId]);
        $currentOrder = $currentStmt->fetch();

        if ($currentOrder && $currentOrder['status'] !== $newStatus) {
            try {
                $db->beginTransaction();

                // Get order items for stock adjustment
                $itemsStmt = $db->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                $itemsStmt->execute([$orderId]);
                $items = $itemsStmt->fetchAll();

                // If changing TO cancelled → restore stock
                if ($newStatus === 'cancelled' && $currentOrder['status'] !== 'cancelled') {
                    foreach ($items as $item) {
                        $stockStmt = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                        $stockStmt->execute([$item['quantity'], $item['product_id']]);
                    }
                }

                // If changing FROM cancelled to another status → re-deduct stock
                if ($currentOrder['status'] === 'cancelled' && $newStatus !== 'cancelled') {
                    foreach ($items as $item) {
                        $stockStmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
                        $stockStmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
                        if ($stockStmt->rowCount() === 0) {
                            throw new Exception("Insufficient stock to restore order.");
                        }
                    }
                }

                // Update order status
                $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $orderId]);

                $db->commit();
                setFlash('success', "Order #$orderId status updated to " . ucfirst($newStatus));
            } catch (Exception $e) {
                $db->rollBack();
                setFlash('error', "Failed to update order: " . $e->getMessage());
            }
        }
    }
    redirect(SITE_URL . '/admin/orders.php');
}

// Filter
$statusFilter = $_GET['status'] ?? '';
$where = '';
$params = [];
if ($statusFilter && in_array($statusFilter, ['pending', 'processing', 'delivered', 'cancelled'])) {
    $where = 'WHERE o.status = ?';
    $params = [$statusFilter];
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$countStmt = $db->prepare("SELECT COUNT(*) FROM orders o $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $db->prepare("SELECT o.*, u.name as user_name, u.email as user_email FROM orders o JOIN users u ON o.user_id = u.id $where ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Order detail
$orderDetail = null;
$orderItems = [];
if (isset($_GET['view'])) {
    $viewId = (int)$_GET['view'];
    $detailStmt = $db->prepare("SELECT o.*, u.name as user_name, u.email as user_email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $detailStmt->execute([$viewId]);
    $orderDetail = $detailStmt->fetch();

    if ($orderDetail) {
        $itemsStmt = $db->prepare("SELECT oi.*, p.name, p.image, p.brand FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $itemsStmt->execute([$viewId]);
        $orderItems = $itemsStmt->fetchAll();
    }
}
?>

<?php if ($orderDetail): ?>
<!-- Order Detail -->
<div style="margin-bottom: var(--space-6);">
    <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="grid grid-2" style="margin-bottom: var(--space-6);">
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Order #<?php echo $orderDetail['id']; ?></h3>
            <span class="badge badge-<?php echo $orderDetail['status']; ?>"><?php echo ucfirst($orderDetail['status']); ?></span>
        </div>
        <div class="admin-card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                <div>
                    <div style="color: var(--text-muted); font-size: var(--font-size-xs);">Customer</div>
                    <div style="font-weight: 600;"><?php echo sanitize($orderDetail['user_name']); ?></div>
                    <div style="color: var(--text-secondary); font-size: var(--font-size-sm);"><?php echo sanitize($orderDetail['user_email']); ?></div>
                </div>
                <div>
                    <div style="color: var(--text-muted); font-size: var(--font-size-xs);">Date</div>
                    <div style="font-weight: 600;"><?php echo date('M d, Y h:i A', strtotime($orderDetail['created_at'])); ?></div>
                </div>
                <div>
                    <div style="color: var(--text-muted); font-size: var(--font-size-xs);">Shipping</div>
                    <div style="font-weight: 600;"><?php echo sanitize($orderDetail['shipping_name']); ?></div>
                    <div style="color: var(--text-secondary); font-size: var(--font-size-sm);"><?php echo sanitize($orderDetail['shipping_address']); ?></div>
                    <div style="color: var(--text-secondary); font-size: var(--font-size-sm);"><?php echo sanitize($orderDetail['shipping_phone']); ?></div>
                </div>
                <div>
                    <div style="color: var(--text-muted); font-size: var(--font-size-xs);">Payment</div>
                    <div style="font-weight: 600;"><?php echo $orderDetail['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online'; ?></div>
                    <div style="font-size: var(--font-size-2xl); font-weight: 800; color: var(--accent-cyan); margin-top: var(--space-2);"><?php echo formatPrice($orderDetail['total_price']); ?></div>
                </div>
            </div>

            <!-- Update Status -->
            <form method="POST" style="margin-top: var(--space-6); padding-top: var(--space-4); border-top: 1px solid var(--border-color); display: flex; gap: var(--space-3); align-items: center;">
                <input type="hidden" name="order_id" value="<?php echo $orderDetail['id']; ?>">
                <label style="font-size: var(--font-size-sm); font-weight: 600;">Update Status:</label>
                <select name="status" class="status-select">
                    <option value="pending" <?php echo $orderDetail['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo $orderDetail['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="delivered" <?php echo $orderDetail['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo $orderDetail['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Update</button>
            </form>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header"><h3>Order Items</h3></div>
        <table class="table">
            <thead>
                <tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th></tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $item): ?>
                <tr>
                    <td>
                        <div class="table-product">
                            <img src="<?php echo getProductImage($item['image']); ?>" alt="">
                            <div>
                                <div style="font-weight:600;"><?php echo sanitize($item['name']); ?></div>
                                <div style="font-size: var(--font-size-xs); color: var(--accent-cyan);"><?php echo sanitize($item['brand']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?php echo formatPrice($item['price']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td style="font-weight:600;"><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<!-- Orders List -->
<div class="flex justify-between items-center" style="margin-bottom: var(--space-6);">
    <div class="flex gap-2">
        <a href="?status=" class="btn <?php echo !$statusFilter ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">All</a>
        <a href="?status=pending" class="btn <?php echo $statusFilter === 'pending' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Pending</a>
        <a href="?status=processing" class="btn <?php echo $statusFilter === 'processing' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Processing</a>
        <a href="?status=delivered" class="btn <?php echo $statusFilter === 'delivered' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Delivered</a>
        <a href="?status=cancelled" class="btn <?php echo $statusFilter === 'cancelled' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Cancelled</a>
    </div>
    <span style="color: var(--text-muted); font-size: var(--font-size-sm);"><?php echo $total; ?> orders</span>
</div>

<div class="admin-card">
    <?php if (empty($orders)): ?>
    <div class="admin-card-body" style="text-align: center; color: var(--text-muted); padding: var(--space-10);">
        <i class="fas fa-inbox" style="font-size: 2rem; display: block; margin-bottom: var(--space-4);"></i>
        No orders found.
    </div>
    <?php else: ?>
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><strong>#<?php echo $order['id']; ?></strong></td>
                    <td>
                        <div><?php echo sanitize($order['user_name']); ?></div>
                        <div style="font-size: var(--font-size-xs); color: var(--text-muted);"><?php echo sanitize($order['user_email']); ?></div>
                    </td>
                    <td style="font-weight: 600;"><?php echo formatPrice($order['total_price']); ?></td>
                    <td><?php echo $order['payment_method'] === 'cod' ? 'COD' : 'Online'; ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <select name="status" class="status-select" onchange="this.form.submit()">
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </form>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                    <td>
                        <a href="?view=<?php echo $order['id']; ?>" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i == $page): ?>
        <span class="active"><?php echo $i; ?></span>
        <?php else: ?>
        <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</div>
<?php endif; ?>
<?php endif; ?>

        </div>
    </main>
</div>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
</body>
</html>
