<?php
session_start();
require_once __DIR__ . '/inc/data.php';

// 处理订单状态更新请求（原有逻辑）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = $_POST['order_id'];
    $status = $_POST['status'];
    
    $result = updatePurchaseOrderStatus($orderId, $status);
    $message = $result ? "订单状态已更新" : "更新失败，请重试";
}

$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$branchFilter = isset($_GET['branch']) ? (int)$_GET['branch'] : 0;
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

$statusParam = $statusFilter === '' ? null : $statusFilter;
$statusbParam = $branchFilter === '' ? null : $branchFilter;
$statussParam = $searchTerm === '' ? null : $searchTerm;

$orders = getSupplierPurchaseOrders($statusParam, $statusbParam, $statussParam);
$branches = getBranches();
$statusSummary = getOrderStatusSummary();

$statusOptions = [
    'pending' => '待处理',
    'ordered' => '已下单',
    'received' => '已收货',
    'cancelled' => '已取消'
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>鲜选生鲜 - 供应商端-订单管理</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Microsoft YaHei', Arial, sans-serif;
        }
        body {
            background-color: #f8f9fa;
            color: #333;
            margin: 20px;
        }
        .header {
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .nav-container {
            width: 90%;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #1976d2;
            text-decoration: none;
        }
        .nav-menu {
            display: flex;
            list-style: none;
        }
        .nav-item {
            margin-left: 30px;
        }
        .nav-link {
            text-decoration: none;
            color: #333;
            font-size: 16px;
            font-weight: 500;
            transition: color 0.3s;
            display: inline-flex;
            align-items: center;
        }
        .nav-link:hover {
            color: #1976d2;
        }
        .nav-link.active {
            color: #1976d2;
            font-weight: bold;
        }
        /* 消息红点样式 */
        .badge {
            display: inline-block;
            min-width: 18px;
            height: 18px;
            line-height: 18px;
            text-align: center;
            border-radius: 50%;
            background-color: #e53935;
            color: white;
            font-size: 12px;
            margin-left: 5px;
            vertical-align: middle;
            transition: all 0.3s ease;
        }
        .badge:empty {
            display: none;
        }
        .main {
            width: 90%;
            margin: 30px auto;
            min-height: calc(100vh - 200px);
        }
        .section {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1976d2;
            border-left: 4px solid #1976d2;
            padding-left: 10px;
        }
        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .filter-input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            width: 250px;
            transition: border-color 0.3s;
        }
        .filter-input:focus {
            border-color: #1976d2;
            outline: none;
        }
        .filter-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            width: 180px;
            transition: border-color 0.3s;
        }
        .filter-select:focus {
            border-color: #1976d2;
            outline: none;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-primary {
            background-color: #1976d2;
            color: white;
        }
        .btn-primary:hover {
            background-color: #1565c0;
        }
        .btn-success {
            background-color: #43a047;
            color: white;
        }
        .btn-success:hover {
            background-color: #388e3c;
        }
        .btn-danger {
            background-color: #e53935;
            color: white;
        }
        .btn-danger:hover {
            background-color: #d32f2f;
        }
        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .order-table th, .order-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .order-table th {
            background-color: #f5f9ff;
            color: #1976d2;
            font-weight: 600;
        }
        .order-table tr:hover {
            background-color: #f5f9ff;
        }
        .status-tag {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-pending {
            background-color: #fff3e0;
            color: #ff9800;
        }
        .status-ordered {
            background-color: #e3f2fd;
            color: #2196f3;
        }
        .status-accepted {
            background-color: #e8f5e9;
            color: #43a047;
        }
        .status-shipped {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        .status-received {
            background-color: #e8f5e9;
            color: #4caf50;
        }
        .status-completed {
            background-color: #f3e5f5;
            color: #8e24aa;
        }
        .status-cancelled {
            background-color: #ffebee;
            color: #e53935;
        }
        /* 弹窗样式 */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal.show {
            display: flex;
        }
        .modal-content {
            background-color: white;
            border-radius: 10px;
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        .modal-close {
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        .modal-close:hover {
            color: #333;
        }
        .modal-body {
            padding: 20px;
        }
        .order-detail-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        .detail-label {
            flex: 0 0 120px;
            font-weight: 500;
            color: #666;
        }
        .detail-value {
            flex: 1;
        }
        .product-list {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .product-list th, .product-list td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .product-list th {
            background-color: #f5f9ff;
            color: #666;
            font-weight: 500;
        }
        .order-summary {
            text-align: right;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .summary-item {
            margin-bottom: 8px;
        }
        .summary-label {
            display: inline-block;
            width: 100px;
            text-align: left;
        }
        .summary-value {
            font-weight: bold;
        }
        .total-amount {
            color: #e53935;
            font-size: 16px;
        }
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #666;
        }
        .form-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .form-input:focus {
            border-color: #1976d2;
            outline: none;
        }
        .detail-info {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .detail-info:last-child {
            border-bottom: none;
        }
        .footer {
            background-color: #333;
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }
        .footer-container {
            width: 90%;
            margin: 0 auto;
            text-align: center;
        }
        .copyright {
            margin-top: 20px;
            font-size: 14px;
            color: #999;
        }
        .status-summary {
            margin: 20px 0;
        }
        .action-btn {
            padding: 5px 10px;
            margin: 0 5px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .detail-btn {
            background: #2196f3;
            color: white;
        }
        .update-form {
            display: inline;
        }
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-input, .filter-select {
                width: 100%;
            }
            .order-table th:nth-child(4), .order-table td:nth-child(4),
            .order-table th:nth-child(5), .order-table td:nth-child(5) {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="main">
        <section class="section">
            <h2 class="section-title">订单管理</h2>
            
            <?php if (isset($message)): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="status-summary">
                <h3>订单状态汇总</h3>
                <?php foreach ($statusSummary as $status => $count): ?>
                    <span class="status-tag status-<?php echo $status; ?>">
                        <?php echo $statusOptions[$status] . ": " . $count; ?>
                    </span>
                <?php endforeach; ?>
            </div>
            
            <div class="filter-bar">
                <form method="get">
                    <input type="text" class="filter-input" id="orderSearch" name="search" placeholder="搜索订单编号/门店名称" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    
                    <select class="filter-select" name="branch" id="branchFilter">
                        <option value="">所有分店</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_ID']; ?>" 
                                <?php echo $branchFilter == $branch['branch_ID'] ? 'selected' : ''; ?>>
                                <?php echo $branch['branch_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select class="filter-select" name="status" id="statusFilter">
    <option value="">全部状态</option>
    <option value="pending" <?php echo $statusFilter == 'pending' ? 'selected' : ''; ?>>待确认</option>
    <option value="received" <?php echo $statusFilter == 'received' ? 'selected' : ''; ?>>已收货</option>
    <option value="ordered" <?php echo $statusFilter == 'ordered' ? 'selected' : ''; ?>>已下单</option>
    <option value="cancelled" <?php echo $statusFilter == 'cancelled' ? 'selected' : ''; ?>>已取消</option>
</select>