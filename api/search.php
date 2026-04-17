<?php
/**
 * Search API — Live Search
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode(['success' => true, 'products' => []]);
    exit;
}

$db = getDB();
$searchTerm = "%{$query}%";

$stmt = $db->prepare("SELECT id, name, brand, price, image FROM products WHERE name LIKE ? OR brand LIKE ? ORDER BY name LIMIT 8");
$stmt->execute([$searchTerm, $searchTerm]);
$products = $stmt->fetchAll();

// Add image URLs
foreach ($products as &$product) {
    $product['image_url'] = getProductImage($product['image']);
    $product['price_formatted'] = formatPrice($product['price']);
    $product['url'] = SITE_URL . '/pages/product-detail.php?id=' . $product['id'];
}

echo json_encode(['success' => true, 'products' => $products]);
