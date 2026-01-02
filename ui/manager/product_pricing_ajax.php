<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/inc/db_connect.php';

$action = $_POST['action'] ?? '';
if ($action !== 'update_selling_price') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$sellingPrice = isset($_POST['selling_price']) ? (float)$_POST['selling_price'] : 0;

if ($productId <= 0 || $sellingPrice <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$stmt = $conn->prepare("UPDATE products SET unit_price = ? WHERE product_ID = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed']);
    exit;
}
$stmt->bind_param('di', $sellingPrice, $productId);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
    $stmt->close();
    exit;
}
$stmt->close();

echo json_encode(['success' => true]);
