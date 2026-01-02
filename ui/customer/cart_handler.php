<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

$customerId = $_SESSION['customer_id'];
$action = $_POST['action'] ?? '';

// 引入数据处理函数
require_once 'inc/data.php';

if ($action === 'add_to_cart') {
    $productId = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    $branchId = intval($_POST['branch_id'] ?? 1); // 门店ID，可从前端传递

    // 调用FIFO加入购物车函数
    $result = addToCartWithFIFO($customerId, $productId, $quantity, $branchId);

    echo json_encode($result);
    exit;
}

echo json_encode(['success' => false, 'message' => '无效的操作']);
?>