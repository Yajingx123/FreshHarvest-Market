<?php
// get_branch_detail.php
session_start();
require_once __DIR__ . '/inc/data.php';

// 验证登录状态
if (!isset($_SESSION['supplier_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Not signed in']);
    exit;
}

// 验证参数
if (!isset($_GET['branch_id']) || !is_numeric($_GET['branch_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

$branchId = (int)$_GET['branch_id'];

// 获取门店详情（已在data.php中实现关联查询逻辑）
$branch = getBranchDetail($branchId);

if ($branch) {
    // 确保输出正确的Content-Type
    header('Content-Type: application/json');
    echo json_encode($branch);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Branch not found or access denied']);
}
?>
