<?php 
session_start();
require_once __DIR__ . '/inc/header.php';  // 页头
require_once __DIR__ . '/inc/db_connect.php'; // 数据库连接文件
require_once __DIR__ . '/inc/data.php';       // 数据函数文件
// 第二步：获取数据（调用data.php中的函数）
$salesTrend = getSalesTrend();
$orderStatusDistribution = getOrderStatusDistribution();
$branchSalesComparison = getBranchSalesComparison();
$alertSummary = getAlertSummary();
?>
<!DOCTYPE html>
<html>
<head>
    <title>数据概览</title>
    <style>
        .dashboard { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 20px; }
        .chart-container { height: 300px; border: 1px solid #eee; padding: 10px; }
        canvas { width: 100%; height: 100%; }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- 销售趋势图 -->
        <div class="chart-container">
            <h3>近30天销售趋势</h3>
            <canvas id="salesTrendChart"></canvas>
        </div>
        
        <!-- 订单状态分布图 -->
        <div class="chart-container">
            <h3>订单状态分布</h3>
            <canvas id="orderStatusChart"></canvas>
        </div>
        
        <!-- 门店销售对比图 -->
        <div class="chart-container">
            <h3>门店销售对比</h3>
            <canvas id="branchSalesChart"></canvas>
        </div>
        
        <!-- 库存预警图 -->
        <div class="chart-container">
            <h3>库存预警统计</h3>
            <canvas id="alertChart"></canvas>
        </div>
    </div>

    <script>
    // 从PHP获取数据
    const salesTrend = <?php echo json_encode($salesTrend, JSON_UNESCAPED_UNICODE); ?>;
    const orderStatus = <?php echo json_encode($orderStatusDistribution, JSON_UNESCAPED_UNICODE); ?>;
    const branchSales = <?php echo json_encode($branchSalesComparison, JSON_UNESCAPED_UNICODE); ?>;
    const alertSummary = <?php echo json_encode($alertSummary, JSON_UNESCAPED_UNICODE); ?>;

    // 绘图工具函数（扩展原有功能）
    function drawPie(id, data, labels, colors) {
        const cvs = document.getElementById(id);
        if (!cvs) return;
        const ctx = cvs.getContext('2d');
        const w = cvs.width, h = cvs.height;
        const cx = w/2, cy = h/2, r = Math.min(w,h)/2 - 8;
        ctx.clearRect(0,0,w,h);
        
        const total = data.reduce((s,v)=>s+v,0) || 1;
        let ang = -Math.PI/2;
        
        data.forEach((v,i)=>{
            const a = (v/total) * Math.PI * 2;
            ctx.beginPath();
            ctx.moveTo(cx,cy);
            ctx.arc(cx,cy, r, ang, ang + a);
            ctx.closePath();
            ctx.fillStyle = colors[i % colors.length];
            ctx.fill();
            
            // 绘制标签
            const midAng = ang + a/2;
            const labelX = cx + (r+10)*Math.cos(midAng);
            const labelY = cy + (r+10)*Math.sin(midAng);
            ctx.fillStyle = '#333';
            ctx.textAlign = midAng > Math.PI/2 || midAng < -Math.PI/2 ? 'right' : 'left';
            ctx.fillText(`${labels[i]}: ${v}`, labelX, labelY);
            
            ang += a;
        });
    }

    function drawBar(id, labels, values, color) {
        const cvs = document.getElementById(id);
        if (!cvs) return;
        const ctx = cvs.getContext('2d');
        const w = cvs.width, h = cvs.height;
        ctx.clearRect(0,0,w,h);
        
        const padding = 30;
        const max = Math.max(...values, 1);
        const barW = (w - padding*2) / values.length * 0.7;
        const barH = h - padding*2;
        
        // 绘制坐标轴
        ctx.beginPath();
        ctx.moveTo(padding, padding);
        ctx.lineTo(padding, h - padding);
        ctx.lineTo(w - padding, h - padding);
        ctx.stroke();
        
        values.forEach((v,i)=>{
            const x = padding + i*(barW + 20) + 10;
            const barHeight = (v/max) * barH;
            const y = h - padding - barHeight;
            
            ctx.fillStyle = color;
            ctx.fillRect(x, y, barW, barHeight);
            
            // 绘制数值
            ctx.fillStyle = '#333';
            ctx.textAlign = 'center';
            ctx.fillText(v, x + barW/2, y - 5);
            
            // 绘制标签
            ctx.fillText(labels[i], x + barW/2, h - padding + 15);
        });
    }

    function drawLine(id, labels, values, color) {
        const cvs = document.getElementById(id);
        if (!cvs) return;
        const ctx = cvs.getContext('2d');
        const w = cvs.width, h = cvs.height;
        ctx.clearRect(0,0,w,h);
        
        const padding = 30; // 内边距
        const max = Math.max(...values, 1); // 最大值（避免除以0）
        const min = Math.min(...values, 0); // 最小值
        const range = max - min; // 数值范围
        const pointCount = values.length;
        const stepX = (w - padding*2) / (pointCount - 1 || 1); // X轴步长
        const stepY = (h - padding*2) / range; // Y轴步长（基于数值范围）

        // 定义纵坐标刻度数量（建议5-8个，这里选5个）
        const tickCount = 5;
        const tickStep = range / (tickCount - 1); // 刻度间隔

        // 1. 绘制纵坐标刻度和数值
        ctx.fillStyle = '#666';
        ctx.textAlign = 'right'; // 数值在刻度左侧
        ctx.textBaseline = 'middle'; // 数值垂直居中
        for (let i = 0; i < tickCount; i++) {
            // 计算刻度的数值和位置
            const tickValue = min + i * tickStep;
            // 四舍五入保留2位小数（根据需求调整）
            const tickValueFormatted = Math.round(tickValue * 100) / 100;
            const y = h - padding - (tickValue - min) * stepY;

            // 绘制刻度线（小横线）
            ctx.beginPath();
            ctx.moveTo(padding - 5, y); // 刻度线向左延伸5px
            ctx.lineTo(padding, y); // 刻度线到Y轴
            ctx.strokeStyle = '#ccc';
            ctx.stroke();

            // 绘制刻度数值
            ctx.fillText(tickValueFormatted, padding - 8, y); // 数值在刻度线左侧8px
        }

        // 2. 绘制坐标轴（X轴+Y轴）
        ctx.beginPath();
        ctx.moveTo(padding, padding); // Y轴顶部
        ctx.lineTo(padding, h - padding); // Y轴底部
        ctx.lineTo(w - padding, h - padding); // X轴右侧
        ctx.strokeStyle = '#333';
        ctx.stroke();

        // 3. 绘制折线和数据点
        ctx.beginPath();
        ctx.strokeStyle = color;
        ctx.lineWidth = 2; // 折线宽度
        values.forEach((v,i)=>{
            const x = padding + i * stepX;
            // 计算Y轴位置（基于最小值偏移）
            const y = h - padding - (v - min) * stepY;

            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }

            // 绘制数据点（小圆点）
            ctx.fillStyle = color;
            ctx.beginPath();
            ctx.arc(x, y, 4, 0, Math.PI*2);
            ctx.fill();

            // 绘制X轴标签（日期，每隔3个点显示，避免拥挤）
            if (i % 3 === 0) {
                ctx.fillStyle = '#333';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'top';
                ctx.fillText(labels[i], x, h - padding + 5);
            }
        });
        ctx.stroke();
    }

    // 初始化图表
    window.onload = function() {
        // 销售趋势图
        const trendLabels = salesTrend.map(item => item.date);
        const trendValues = salesTrend.map(item => parseFloat(item.total_sales));
        drawLine('salesTrendChart', trendLabels, trendValues, '#3498db');
        
        // 订单状态分布图
        const statusLabels = orderStatus.map(item => item.order_status);
        const statusValues = orderStatus.map(item => parseInt(item.count));
        drawPie('orderStatusChart', statusValues, statusLabels, ['#2ecc71', '#e74c3c', '#f39c12', '#9b59b6']);
        
        // 门店销售对比图
        const branchLabels = branchSales.map(item => item.branch_name);
        const branchValues = branchSales.map(item => parseFloat(item.total_sales));
        drawBar('branchSalesChart', branchLabels, branchValues, '#1abc9c');
        
        // 库存预警图
        const alertLabels = ['过期预警', '低库存预警'];
        const alertValues = [alertSummary.expiry || 0, alertSummary.low_stock || 0];
        drawPie('alertChart', alertValues, alertLabels, ['#e74c3c', '#f39c12']);
    };
    </script>
</body>
</html>