<?php
// 在header.php的顶部添加
require_once __DIR__ . '/inc/data.php';
$statusSummary = getOrderStatusSummary();
// 确定当前页面以设置active类
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<header class="header">
    <div class="nav-container">
        <a href="dashboard.php" class="logo">鲜选生鲜 - 供应商管理平台</a>
        <ul class="nav-menu">
            <li class="nav-item"><a href="dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">数据概览</a></li>
            <li class="nav-item"><a href="stores.php" class="nav-link <?php echo $currentPage === 'stores.php' ? 'active' : ''; ?>">合作门店</a></li>
            <li class="nav-item">
             <a href="orders.php" class="nav-link <?php echo $currentPage === 'orders.php' ? 'active' : ''; ?>">
             订单管理 
             <span class="badge" id="unhandledOrderBadge">
               <?php 
               echo isset($statusSummary['pending']) && $statusSummary['pending'] > 0 
                ? $statusSummary['pending'] 
                : '0'; 
              ?>
              </span>
            </a>
         </li>
            <li class="nav-item"><a href="products_manage.php" class="nav-link <?php echo $currentPage === 'products_manage.php' ? 'active' : ''; ?>">产品管理</a></li>
            <li class="nav-item"><a href="profile.php" class="nav-link <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">供货商信息</a></li>
        </ul>
    </div>
</header>
