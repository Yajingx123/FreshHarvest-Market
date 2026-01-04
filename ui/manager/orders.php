<?php
session_start();
require_once __DIR__ .'/inc/db_connect.php';
require_once __DIR__ .'/inc/data.php';

$branchId = $_GET['branch_id'];

// 获取门店订单
$orders = getBranchOrdersWithDetails($branchId);
$branch = getBranch($branchId); // 复用之前的函数
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= $branch['branch_name'] ?> - Orders</title>
    <style>
        /* ===== 这是我追加的CSS美化样式 ===== */
        /* 这部分只改变外观，不影响任何功能 */
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            margin: 0;
            padding: 20px;
            font-family: 'Microsoft YaHei', Arial, sans-serif;
            min-height: 100vh;
        }
        h1 {
            color: #1976d2;
            text-align: center;
            margin: 30px 0;
            font-size: 28px;
            font-weight: 600;
            padding-bottom: 15px;
            border-bottom: 2px solid #1976d2;
        }
        a[href="stores.php"] {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 10px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            margin: 20px 0;
            transition: all 0.3s ease;
        }
        a[href="stores.php"]:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
            background: white;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        table th {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            color: white;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            white-space: nowrap;
        }
        table td {
            padding: 14px 16px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }
        table tr:hover {
            background-color: #f5f9ff;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        table tr:nth-child(even):hover {
            background-color: #f5f9ff;
        }
        .order-id {
            font-weight: 600;
            color: #1976d2;
        }
        .customer-name {
            font-weight: 500;
        }
        .order-date {
            color: #666;
        }
        .amount {
            font-weight: 600;
            color: #d32f2f;
        }
        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-processing {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .status-completed {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        .status-cancelled {
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            color: #d32f2f;
            border: 1px solid #ffcdd2;
        }
        .product-details {
            max-width: 300px;
            word-wrap: break-word;
            color: #666;
            font-size: 13px;
            line-height: 1.5;
        }
        .empty-state {
            text-align: center;
            padding: 50px 30px;
            color: #999;
            font-size: 16px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            margin: 20px 0;
            background: #f9f9f9;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            text-align: center;
            border: 1px solid #e0e0e0;
        }
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #1976d2;
            margin-bottom: 8px;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            h1 {
                font-size: 24px;
                margin: 20px 0;
            }
            table {
                font-size: 12px;
            }
            table th, table td {
                padding: 10px;
            }
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
        /* ===== CSS美化样式结束 ===== */
    </style>
</head>
<body>
    <h1><?= $branch['branch_name'] ?> - Orders</h1>
    <a href="stores.php">Back to stores</a>

    <?php if (!empty($orders)): ?>
        <!-- 统计信息 -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-value"><?= count($orders) ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">¥<?= array_sum(array_column($orders, 'total_amount')) ?></div>
                <div class="stat-label">Order Total</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">¥<?= array_sum(array_column($orders, 'final_amount')) ?></div>
                <div class="stat-label">Net Total</div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($orders)): ?>
        <div class="table-container">
            <table border="1">
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Status</th>
                    <th>Items</th>
                </tr>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td class="order-id">#<?= $order['order_ID'] ?></td>
                    <td class="customer-name"><?= $order['customer_name'] ?></td>
                    <td class="order-date"><?= $order['order_date'] ?></td>
                    <td class="amount">¥<?= $order['total_amount'] ?></td>
                    <td class="amount">¥<?= $order['final_amount'] ?></td>
                    <td>
                        <?php
                        $statusClass = '';
                        $statusLabel = $order['order_status'];
                        switch($order['order_status']) {
                            case '已完成':
                            case 'Completed':
                                $statusClass = 'status-completed';
                                $statusLabel = 'Completed';
                                break;
                            case '处理中':
                            case 'Processing':
                                $statusClass = 'status-processing';
                                $statusLabel = 'Processing';
                                break;
                            case '已取消':
                            case 'Cancelled':
                                $statusClass = 'status-cancelled';
                                $statusLabel = 'Cancelled';
                                break;
                            default:
                                $statusClass = 'status-processing';
                        }
                        ?>
                        <span class="status <?= $statusClass ?>"><?= $statusLabel ?></span>
                    </td>
                    <td class="product-details"><?= $order['product_details'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            No orders for this store
        </div>
    <?php endif; ?>
</body>
</html>
