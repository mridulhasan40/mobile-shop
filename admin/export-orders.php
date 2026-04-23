<?php
/**
 * Admin — Export Orders to CSV
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

// Build query with optional filters
$where  = [];
$params = [];

if (!empty($_GET['status'])) {
    $where[]  = 'o.status = ?';
    $params[] = $_GET['status'];
}
if (!empty($_GET['from'])) {
    $where[]  = 'DATE(o.created_at) >= ?';
    $params[] = $_GET['from'];
}
if (!empty($_GET['to'])) {
    $where[]  = 'DATE(o.created_at) <= ?';
    $params[] = $_GET['to'];
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("
    SELECT o.id, u.name as customer, u.email, o.shipping_name, o.shipping_address,
           o.shipping_phone, o.payment_method, o.status, o.total_price, o.discount_amount,
           o.created_at
    FROM orders o
    JOIN users u ON o.user_id = u.id
    {$whereSql}
    ORDER BY o.created_at DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Output CSV
$filename = 'orders-' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
// BOM for Excel compatibility
fwrite($out, "\xEF\xBB\xBF");

// Headers
fputcsv($out, [
    'Order ID', 'Customer Name', 'Email', 'Shipping Name', 'Shipping Address',
    'Shipping Phone', 'Payment', 'Status', 'Discount', 'Total', 'Date'
]);

foreach ($orders as $row) {
    fputcsv($out, [
        '#' . $row['id'],
        $row['customer'],
        $row['email'],
        $row['shipping_name'],
        $row['shipping_address'],
        $row['shipping_phone'],
        $row['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online',
        ucfirst($row['status']),
        '$' . number_format($row['discount_amount'], 2),
        '$' . number_format($row['total_price'], 2),
        date('Y-m-d H:i', strtotime($row['created_at'])),
    ]);
}

fclose($out);
exit;
