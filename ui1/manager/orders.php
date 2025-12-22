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
<html>
<head>
    <title><?= $branch['branch_name'] ?> - 订单管理</title>
</head>
<body>
    <h1><?= $branch['branch_name'] ?> - 订单管理</h1>
    <a href="stores.php">返回门店列表</a>

    <table border="1">
        <tr>
            <th>订单ID</th>
            <th>客户</th>
            <th>日期</th>
            <th>总金额</th>
            <th>状态</th>
            <th>商品详情</th>
        </tr>
        <?php foreach ($orders as $order): ?>
        <tr>
            <td><?= $order['order_ID'] ?></td>
            <td><?= $order['customer_name'] ?></td>
            <td><?= $order['order_date'] ?></td>
            <td>¥<?= $order['total_amount'] ?></td>
            <td><?= $order['order_status'] ?></td>
            <td><?= $order['product_details'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>