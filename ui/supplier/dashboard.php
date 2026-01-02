<?php
session_start();
// 引入数据库操作函数
require_once __DIR__.'/inc/data.php';

// 获取当前供应商ID
$supplierId = getCurrentSupplierId();

// 初始化数据变量
$todayOrders = 0;
$cooperativeStores = 0;
$todaySales = 0;
$recentSales = [];
$dateLabels = [];
$salesData = [];
$orderStatus = [
    'pending' => 0,
    'ordered' => 0,
    'received' => 0,
    'cancelled' => 0
];
$ProductCount = getSupplierProductCount();

// 只有当供应商ID有效时才获取数据
if ($supplierId > 0) {
    // 获取数据库连接
    $conn = getDBConnection();
    
    // 1. 获取今日订单数
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM PurchaseOrder 
                          WHERE supplier_ID = ? AND DATE(date) = ?");
    $stmt->bind_param("is", $supplierId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $todayOrders = $row['count'] ?? 0;
    $stmt->close();
    
    // 2. 获取合作门店数
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT branch_ID) AS count 
                          FROM PurchaseOrder 
                          WHERE supplier_ID = ?");
    $stmt->bind_param("i", $supplierId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $cooperativeStores = $row['count'] ?? 0;
    $stmt->close();
    
    // 3. 获取今日销售额
    $stmt = $conn->prepare("SELECT SUM(total_amount) AS sum FROM PurchaseOrder 
                          WHERE supplier_ID = ? AND DATE(date) = ?");
    $stmt->bind_param("is", $supplierId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $todaySales = $row['sum'] ?? 0;
    $stmt->close();
    
    // 4. 获取订单状态分布
    $statusSummary = getOrderStatusSummary();
    foreach ($orderStatus as $status => $value) {
        $orderStatus[$status] = $statusSummary[$status] ?? 0;
    }
    // 修改销售数据获取部分
    for ($i = 6; $i >= 0; $i--) {
      $date = date('Y-m-d', strtotime("-$i days"));
      $dateLabels[] = date('m-d', strtotime("-$i days"));
    
      $stmt = $conn->prepare("SELECT SUM(total_amount) AS daily_sales 
                          FROM PurchaseOrder 
                          WHERE supplier_ID = ? 
                          AND DATE(date) = ? 
                          AND status IN ('ordered', 'received')");
    
      if (!$stmt) {
          error_log("SQL 准备失败: " . $conn->error);
          $salesData[] = 0;
          continue;
      }
    
      $stmt->bind_param("is", $supplierId, $date);
    
      if (!$stmt->execute()) {
          error_log("SQL 执行失败: " . $stmt->error);
          $salesData[] = 0;
          $stmt->close();
          continue;
      }
    
      $result = $stmt->get_result();
    
      if (!$result) {
        error_log("获取结果失败: " . $stmt->error);
        $salesData[] = 0;
        $stmt->close();
        continue;
      }
    
      $row = $result->fetch_assoc();
      $dailySales = $row['daily_sales'] ?? 0;
    
      // 调试日志
      error_log("日期: $date, 销售额: " . $dailySales);
    
      $salesData[] = floatval($dailySales);
      $stmt->close();
    
      $recentSales[$date] = $dailySales;
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>鲜选生鲜 - 供应商端-数据概览</title>
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
        .main {
            width: 90%;
            margin: 30px auto;
            min-height: calc(100vh - 200px);
        }
        .overview-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        .icon-order {
            background-color: #1976d2;
        }
        .icon-product {
            background-color: #43a047;
        }
        .icon-sales {
            background-color: #fbc02d;
        }
        .icon-store {
            background-color: #e53935;
        }
        .card-content h3 {
            font-size: 14px;
            color: #999;
            margin-bottom: 5px;
        }
        .card-content .number {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .chart-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        .chart-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            padding: 20px;
        }
        .chart-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }
        .sales-chart {
            height: 300px;
            border-radius: 8px;
        }
        .order-status {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .status-label {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        .dot-pending {
            background-color: #ff9800;
        }
        .dot-accepted {
            background-color: #43a047;
        }
        .dot-shipped {
            background-color: #1976d2;
        }
        .dot-completed {
            background-color: #8e24aa;
        }
        .status-value {
            font-weight: bold;
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
            font-size: 14px;
            color: #999;
        }
        /* 红点提示样式 */
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background-color: #e53935;
            color: white;
            font-size: 12px;
            font-weight: bold;
            margin-left: 5px;
            padding: 0;
            line-height: 1;
        }
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            .chart-container {
                grid-template-columns: 1fr;
            }
            .overview-cards {
                grid-template-columns: 1fr;
            }
        }
        /* 添加这些样式 */
        .chart-wrap {
           position: relative;
           height: 260px; /* 固定高度，与 overview.php 一致 */
        }
        #salesChart {
          width: 100% !important;
          height: 100% !important;
        }
        .sales-chart {
          height: 260px !important; /* 覆盖之前的高度设置 */
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="main">
        <h2 style="margin-bottom: 30px; color: #333;">数据概览</h2>

        <!-- 核心指标卡片 -->
        <div class="overview-cards">
            <div class="card">
                <div class="card-icon icon-order">
                    <i>📝</i>
                </div>
                <div class="card-content">
                    <h3>今日订单数</h3>
                    <div class="number"><?php echo $todayOrders; ?></div>
                </div>
            </div>
            <div class="card">
                <div class="card-icon icon-product">
                    <i>🥬</i>
                </div>
                <div class="card-content">
                    <h3>可供应货品数</h3>
                    <div class="number"><?php echo $ProductCount; ?></div>
                </div>
            </div>
            <div class="card">
                <div class="card-icon icon-sales">
                    <i>💰</i>
                </div>
                <div class="card-content">
                    <h3>今日销售额</h3>
                    <div class="number"><?php echo '¥' . number_format($todaySales, 2); ?></div>
                </div>
            </div>
            <div class="card">
                <div class="card-icon icon-store">
                    <i>🏪</i>
                </div>
                <div class="card-content">
                    <h3>合作门店数</h3>
                    <div class="number"><?php echo $cooperativeStores; ?></div>
                </div>
            </div>
        </div>

        <!-- 图表区域 -->
        <div class="chart-container">
          <div class="chart-card">
           <div class="chart-title">近7日销售额趋势</div>
             <div class="chart-wrap">
               <canvas id="salesChart"></canvas>
            </div>
        </div>
            <div class="chart-card">
                <div class="chart-title">订单状态分布</div>
                <div class="order-status">
                    <div class="status-item">
                        <div class="status-label">
                            <div class="status-dot dot-pending"></div>
                            <span>待确认</span>
                        </div>
                        <div class="status-value"><?php echo $orderStatus['pending']; ?></div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">
                            <div class="status-dot dot-accepted"></div>
                            <span>已下单</span>
                        </div>
                        <div class="status-value"><?php echo $orderStatus['ordered']; ?></div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">
                            <div class="status-dot dot-shipped"></div>
                            <span>已收货</span>
                        </div>
                        <div class="status-value"><?php echo $orderStatus['received']; ?></div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">
                            <div class="status-dot dot-completed"></div>
                            <span>已取消</span>
                        </div>
                        <div class="status-value"><?php echo $orderStatus['cancelled']; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <h3 class="logo" style="color: white; margin-bottom: 20px;">鲜选生鲜 - 供应商管理平台</h3>
            <div style="display: flex; justify-content: center; gap: 30px; margin-bottom: 20px;">
                <a href="#" style="color: #ccc; text-decoration: none;">供应商帮助中心</a>
                <a href="#" style="color: #ccc; text-decoration: none;">合作协议</a>
                <a href="#" style="color: #ccc; text-decoration: none;">结算规则</a>
                <a href="#" style="color: #ccc; text-decoration: none;">投诉反馈</a>
                <a href="#" style="color: #ccc; text-decoration: none;">联系平台</a>
            </div>
            <div class="copyright">© 2024 鲜选生鲜 版权所有 | 平台客服电话:400-888-XXXX</div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const salesData = <?php echo json_encode($salesData); ?>;
const dateLabels = <?php echo json_encode($dateLabels); ?>;

console.log('销售数据:', salesData);
console.log('日期标签:', dateLabels);

// 使用与 overview.php 相同的 buildChart 函数
function buildChart(ctx, config) {
    if (!ctx) {
        console.error('Canvas context 不存在');
        return null;
    }
    return new Chart(ctx, config);
}

// 修改 renderSalesChart 函数
function renderSalesChart() {
    const canvas = document.getElementById('salesChart');
    
    if (!canvas) {
        console.error('找不到 salesChart 元素');
        return;
    }
    
    // 清理之前的图表实例
    if (window.salesChartInstance) {
        window.salesChartInstance.destroy();
    }
    
    window.salesChartInstance = buildChart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: dateLabels,
            datasets: [{
                label: '销售额（元）',
                data: salesData,
                borderColor: '#1976d2',
                backgroundColor: 'rgba(25, 118, 210, 0.15)',
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointBackgroundColor: '#1976d2',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // 关键：让图表填充容器
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: {
                            size: 14
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '销售额: ¥' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '¥' + value;
                        }
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
    
    console.log('图表创建成功');
}

// DOM 加载完成后执行
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM 加载完成');
    
    // 立即渲染图表
    if (document.getElementById('salesChart')) {
        renderSalesChart();
    } else {
        console.error('找不到 salesChart 元素');
    }
    
    // 更新红点
    updateUnhandledOrderCount();
});

function updateUnhandledOrderCount() {
    const pendingCount = <?php echo $orderStatus['pending']; ?>;
    const badge = document.getElementById('unhandledOrderBadge');
    if (badge) {
        badge.textContent = pendingCount > 0 ? pendingCount : '';
    }
}
</script>
</body>
</html>