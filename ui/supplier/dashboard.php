<?php
// 供应商端 - 数据概览（带订单红点提示）
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
                    <div class="number">28</div>
                </div>
            </div>
            <div class="card">
                <div class="card-icon icon-product">
                    <i>🥬</i>
                </div>
                <div class="card-content">
                    <h3>可供应货品数</h3>
                    <div class="number">42</div>
                </div>
            </div>
            <div class="card">
                <div class="card-icon icon-sales">
                    <i>💰</i>
                </div>
                <div class="card-content">
                    <h3>今日销售额</h3>
                    <div class="number">¥18,650</div>
                </div>
            </div>
            <div class="card">
                <div class="card-icon icon-store">
                    <i>🏪</i>
                </div>
                <div class="card-content">
                    <h3>合作门店数</h3>
                    <div class="number">12</div>
                </div>
            </div>
        </div>

        <!-- 图表区域 -->
        <div class="chart-container">
            <div class="chart-card">
                <div class="chart-title">近7日销售额趋势</div>
                <div id="salesChart" class="sales-chart"></div>
            </div>
            <div class="chart-card">
                <div class="chart-title">订单状态分布</div>
                <div class="order-status">
                    <div class="status-item">
                        <div class="status-label">
                            <div class="status-dot dot-pending"></div>
                            <span>待确认</span>
                        </div>
                        <div class="status-value">8</div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">
                            <div class="status-dot dot-accepted"></div>
                            <span>已确认</span>
                        </div>
                        <div class="status-value">12</div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">
                            <div class="status-dot dot-shipped"></div>
                            <span>已发货</span>
                        </div>
                        <div class="status-value">25</div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">
                            <div class="status-dot dot-completed"></div>
                            <span>已完成</span>
                        </div>
                        <div class="status-value">47</div>
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
            <div class="copyright">© 2024 鲜选生鲜 版权所有 | 平台客服电话：400-888-XXXX</div>
        </div>
    </footer>

    <script>
        // 计算并更新未处理订单数量（待确认状态）
        function updateUnhandledOrderCount() {
            // 实际应用中应通过AJAX从服务器获取数据
            // 此处模拟数据，与订单页保持一致
            const pendingCount = 1; // 待确认订单数量
            const badge = document.getElementById('unhandledOrderBadge');
            badge.textContent = pendingCount > 0 ? pendingCount : '';
        }

        // 页面加载完成后初始化红点
        document.addEventListener('DOMContentLoaded', function() {
            updateUnhandledOrderCount();
            
            // 点击红点跳转到订单管理页并筛选待确认订单
            const badge = document.getElementById('unhandledOrderBadge');
            if (badge) {
                badge.parentElement.addEventListener('click', function(e) {
                    // 跳转到订单页并自动筛选待确认订单
                    window.location.href = 'orders.php?status=pending';
                });
            }
        });
    </script>
</body>
</html>