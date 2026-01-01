<?php 
session_start();
require_once __DIR__ . '/inc/header.php';  // 页头
require_once __DIR__ . '/inc/db_connect.php'; // 数据库连接文件
require_once __DIR__ . '/inc/data.php';       // 数据函数文件

$salesTrend = getSalesTrend();
$orderStatusDistribution = getOrderStatusDistribution();
$branchSalesComparison = getBranchSalesComparison();
$alertSummary = getAlertSummary();

$trendLabels = [];
$trendSales = [];
foreach ($salesTrend as $row) {
    $dateLabel = $row['date'] ?? '';
    $trendLabels[] = $dateLabel ? date('m-d', strtotime($dateLabel)) : '';
    $trendSales[] = (float)($row['total_sales'] ?? 0);
}

$orderStatusLabels = [];
$orderStatusCounts = [];
foreach ($orderStatusDistribution as $row) {
    $orderStatusLabels[] = $row['order_status'] ?? '未知';
    $orderStatusCounts[] = (int)($row['count'] ?? 0);
}

$branchLabels = [];
$branchSales = [];
foreach ($branchSalesComparison as $row) {
    $branchLabels[] = $row['branch_name'] ?? '未知门店';
    $branchSales[] = (float)($row['total_sales'] ?? 0);
}

$alertLabels = ['过期预警', '低库存预警'];
$alertValues = [
    (int)($alertSummary['expiry'] ?? 0),
    (int)($alertSummary['low_stock'] ?? 0)
];
?>
<style>
    .overview-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(320px, 1fr));
        gap: 20px;
    }
    .chart-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        padding: 18px 20px 14px;
    }
    .chart-card h3 {
        font-size: 16px;
        color: #ff7043;
        margin-bottom: 12px;
    }
    .chart-wrap {
        position: relative;
        height: 260px;
    }
    @media (max-width: 900px) {
        .overview-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
<section class="section">
    <h2 class="section-title">工作概览</h2>
    <div class="overview-grid">
        <div class="chart-card">
            <h3>近30天销售趋势</h3>
            <div class="chart-wrap"><canvas id="salesTrendChart"></canvas></div>
        </div>
        <div class="chart-card">
            <h3>订单状态分布</h3>
            <div class="chart-wrap"><canvas id="orderStatusChart"></canvas></div>
        </div>
        <div class="chart-card">
            <h3>门店销售对比</h3>
            <div class="chart-wrap"><canvas id="branchSalesChart"></canvas></div>
        </div>
        <div class="chart-card">
            <h3>库存预警统计</h3>
            <div class="chart-wrap"><canvas id="alertChart"></canvas></div>
        </div>
    </div>
</section>

<script>
const trendLabels = <?php echo json_encode($trendLabels, JSON_UNESCAPED_UNICODE); ?>;
const trendSales = <?php echo json_encode($trendSales, JSON_UNESCAPED_UNICODE); ?>;
const statusLabels = <?php echo json_encode($orderStatusLabels, JSON_UNESCAPED_UNICODE); ?>;
const statusCounts = <?php echo json_encode($orderStatusCounts, JSON_UNESCAPED_UNICODE); ?>;
const branchLabels = <?php echo json_encode($branchLabels, JSON_UNESCAPED_UNICODE); ?>;
const branchSales = <?php echo json_encode($branchSales, JSON_UNESCAPED_UNICODE); ?>;
const alertLabels = <?php echo json_encode($alertLabels, JSON_UNESCAPED_UNICODE); ?>;
const alertValues = <?php echo json_encode($alertValues, JSON_UNESCAPED_UNICODE); ?>;

function buildChart(ctx, config) {
    if (!ctx) return;
    return new Chart(ctx, config);
}

buildChart(document.getElementById('salesTrendChart'), {
    type: 'line',
    data: {
        labels: trendLabels,
        datasets: [{
            label: '销售额（元）',
            data: trendSales,
            borderColor: '#ff7043',
            backgroundColor: 'rgba(255,112,67,0.15)',
            fill: true,
            tension: 0.3,
            pointRadius: 3,
            pointBackgroundColor: '#ff7043'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

buildChart(document.getElementById('orderStatusChart'), {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusCounts,
            backgroundColor: ['#ff7043', '#66bb6a', '#42a5f5', '#ffa726']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

buildChart(document.getElementById('branchSalesChart'), {
    type: 'bar',
    data: {
        labels: branchLabels,
        datasets: [{
            label: '销售额（元）',
            data: branchSales,
            backgroundColor: '#ffa726'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

buildChart(document.getElementById('alertChart'), {
    type: 'doughnut',
    data: {
        labels: alertLabels,
        datasets: [{
            data: alertValues,
            backgroundColor: ['#ef5350', '#ffb74d']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>
