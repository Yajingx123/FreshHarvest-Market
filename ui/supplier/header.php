<?php
// 在header.php的顶部添加
require_once __DIR__ . '/inc/data.php';

// 确定当前页面以设置active类
$currentPage = basename($_SERVER['PHP_SELF']);

// 获取待处理订单数量
$pending_order_count = 0;
if (isset($_SESSION['supplier_id'])) {
    $pending_order_count = getPendingOrderCount($_SESSION['supplier_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshHarvest - Supplier Portal</title>
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        /* 顶部导航栏 */
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
            color: #2d884d; /* 生鲜绿色系 */
            text-decoration: none;
        }
        .nav-menu {
            display: flex;
            list-style: none;
        }
        .nav-item {
            margin-left: 30px;
            position: relative;
        }
        .nav-link {
            text-decoration: none;
            color: #333;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .nav-link:hover {
            color: #2d884d;
        }
        .nav-link.active {
            color: #2d884d;
        }
        .nav-link .badge {
            background-color: #ff4d4f;
            color: white;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
            display: inline-block;
        }
        .main {
            width: 90%;
            margin: 30px auto;
            flex: 1;
        }
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- 顶部导航栏 -->
    <header class="header">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">FreshHarvest - Supplier Portal</a>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i> Overview
                    </a>
                </li>
                <li class="nav-item">
                    <a href="stores.php" class="nav-link <?php echo $currentPage === 'stores.php' ? 'active' : ''; ?>">
                        <i class="fas fa-store"></i> Partner Stores
                    </a>
                </li>
                <li class="nav-item">
                    <a href="orders.php" class="nav-link <?php echo $currentPage === 'orders.php' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-cart"></i> Orders
                        <?php if ($pending_order_count > 0): ?>
                            <span class="badge" id="pendingOrderBadge"><?php echo $pending_order_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="products_manage.php" class="nav-link <?php echo $currentPage === 'products_manage.php' ? 'active' : ''; ?>">
                        <i class="fas fa-box-open"></i> Products
                    </a>
                </li>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user"></i> Supplier Profile
                    </a>
                </li>
            </ul>
        </div>
    </header>
    <main class="main">
