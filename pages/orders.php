<?php
/**
 * Order History Page
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db = getDB();
$pageTitle = 'My Orders';

// Fetch user orders
$stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Get order details if viewing a specific order
$orderDetail = null;
$orderItems = [];
if (isset($_GET['id'])) {
    $orderId = (int)$_GET['id'];
    $detailStmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $detailStmt->execute([$orderId, $_SESSION['user_id']]);
    $orderDetail = $detailStmt->fetch();

    if ($orderDetail) {
        $itemsStmt = $db->prepare("SELECT oi.*, p.name, p.image, p.brand FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $itemsStmt->execute([$orderId]);
        $orderItems = $itemsStmt->fetchAll();
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
                <span>My Orders</span>
            </div>
            <h1 class="page-title">My Orders</h1>
        </div>

        <?php if ($orderDetail): ?>
        <!-- Order Detail View -->
        <div style="margin-bottom: var(--space-6);">
            <a href="<?php echo SITE_URL; ?>/pages/orders.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>

        <div class="profile-card" style="margin-bottom: var(--space-6);">
            <div class="flex justify-between items-center" style="margin-bottom: var(--space-6);">
                <h3>Order #<?php echo $orderDetail['id']; ?></h3>
                <span class="badge badge-<?php echo $orderDetail['status']; ?>"><?php echo ucfirst($orderDetail['status']); ?></span>
            </div>

            <div class="grid grid-3" style="margin-bottom: var(--space-6);">
                <div>
                    <div style="color: var(--text-muted); font-size: var(--font-size-xs); margin-bottom: 4px;">Date</div>
                    <div style="font-weight: 600;"><?php echo date('M d, Y h:i A', strtotime($orderDetail['created_at'])); ?></div>
                </div>
                <div>
                    <div style="color: var(--text-muted); font-size: var(--font-size-xs); margin-bottom: 4px;">Payment</div>
                    <div style="font-weight: 600;"><?php echo $orderDetail['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online'; ?></div>
                </div>
                <div>
                    <div style="color: var(--text-muted); font-size: var(--font-size-xs); margin-bottom: 4px;">Total</div>
                    <div style="font-weight: 700; color: var(--accent-cyan);"><?php echo formatPrice($orderDetail['total_price']); ?></div>
                </div>
            </div>

            <div style="padding: var(--space-4); background: var(--bg-glass); border-radius: var(--radius-md); margin-bottom: var(--space-6);">
                <div style="color: var(--text-muted); font-size: var(--font-size-xs); margin-bottom: 4px;">Shipping To</div>
                <div style="font-weight: 600;"><?php echo sanitize($orderDetail['shipping_name']); ?></div>
                <div style="color: var(--text-secondary); font-size: var(--font-size-sm);"><?php echo sanitize($orderDetail['shipping_address']); ?></div>
                <div style="color: var(--text-secondary); font-size: var(--font-size-sm);"><?php echo sanitize($orderDetail['shipping_phone']); ?></div>
            </div>

            <h4 style="margin-bottom: var(--space-4);">Order Items</h4>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td>
                                <div class="table-product">
                                    <img src="<?php echo getProductImage($item['image']); ?>" alt="">
                                    <div>
                                        <div style="font-weight: 600;"><?php echo sanitize($item['name']); ?></div>
                                        <div style="font-size: var(--font-size-xs); color: var(--accent-cyan);"><?php echo sanitize($item['brand']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo formatPrice($item['price']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td style="font-weight: 600;"><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php else: ?>
        <!-- Orders List -->
        <?php if (empty($orders)): ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <h3>No orders yet</h3>
            <p>You haven't placed any orders. Start shopping to see your orders here!</p>
            <a href="<?php echo SITE_URL; ?>/pages/products.php" class="btn btn-primary btn-lg">
                <i class="fas fa-shopping-bag"></i> Start Shopping
            </a>
        </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong>#<?php echo $order['id']; ?></strong></td>
                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                        <td style="font-weight: 600;"><?php echo formatPrice($order['total_price']); ?></td>
                        <td><?php echo $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online'; ?></td>
                        <td><span class="badge badge-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                        <td>
                            <a href="<?php echo SITE_URL; ?>/pages/orders.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary btn-sm">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
