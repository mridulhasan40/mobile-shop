<?php
/**
 * Reviews API — Submit product review
 */
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to submit a review.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$rating    = (int)($_POST['rating']    ?? 0);
$comment   = trim($_POST['comment']    ?? '');

if ($productId <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

// Check user already reviewed
if (userHasReviewed($productId)) {
    echo json_encode(['success' => false, 'message' => 'You have already reviewed this product.']);
    exit;
}

$db = getDB();
try {
    $stmt = $db->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$productId, $_SESSION['user_id'], $rating, $comment]);
    $data = getProductRating($productId);
    echo json_encode([
        'success'    => true,
        'message'    => 'Review submitted successfully!',
        'avg_rating' => round($data['avg'], 1),
        'total'      => $data['total'],
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Could not submit review. Please try again.']);
}
