<?php
// 工作概览界面
?>
<!DOCTYPE html>
<html lang="zh-CN">
<?php include 'header.php'; ?>
<body>
    <main class="main">
        <section class="section">
            <h2 class="section-title">今日工作概览</h2>
            <div class="dashboard-cards">
                <div class="card"><div class="card-icon">🔄</div><div class="card-title">待补货</div><div class="card-value">7</div></div>
                <div class="card"><div class="card-icon">💰</div><div class="card-title">今日成交订单数</div><div class="card-value">19</div></div>
                <div class="card"><div class="card-icon">👥</div><div class="card-title">在职员工数</div><div class="card-value">12</div></div>
            </div>
            <div style="display:flex;gap:32px;margin-top:32px;flex-wrap:wrap;">
                <div style="flex:1;min-width:320px;background:#fff3e0;border-radius:8px;padding:24px;text-align:center;">
                    <h3 style="font-size:16px;color:#ff7043;margin-bottom:16px;">门店男女比例</h3>
                    <canvas id="pieChart" width="220" height="220"></canvas>
                </div>
                <div style="flex:1;min-width:320px;background:#e3f2fd;border-radius:8px;padding:24px;text-align:center;">
                    <h3 style="font-size:16px;color:#1976d2;margin-bottom:16px;">门店收益（折线图）</h3>
                    <canvas id="lineChart" width="320" height="220"></canvas>
                </div>
                <div style="flex:1;min-width:320px;background:#fce4ec;border-radius:8px;padding:24px;text-align:center;">
                    <h3 style="font-size:16px;color:#ad1457;margin-bottom:16px;">一周订单量（柱状图）</h3>
                    <canvas id="barChart" width="320" height="220"></canvas>
                </div>
            </div>
        </section>
    </main>
    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // 饼图：男女比例
    new Chart(document.getElementById('pieChart'), {
        type: 'pie',
        data: {
            labels: ['男', '女'],
            datasets: [{
                data: [7, 5],
                backgroundColor: ['#42a5f5', '#ef5350']
            }]
        },
        options: {responsive: false}
    });
    // 折线图：门店收益
    new Chart(document.getElementById('lineChart'), {
        type: 'line',
        data: {
            labels: ['周一','周二','周三','周四','周五','周六','周日'],
            datasets: [{
                label: '收益（元）',
                data: [1200,1500,1800,1700,2100,2400,2200],
                borderColor: '#1976d2',
                backgroundColor: 'rgba(25,118,210,0.08)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {responsive: false}
    });
    // 柱状图：一周订单量
    new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: ['周一','周二','周三','周四','周五','周六','周日'],
            datasets: [{
                label: '订单量',
                data: [32,28,35,30,40,45,38],
                backgroundColor: '#ad1457'
            }]
        },
        options: {responsive: false}
    });
    </script>
</body>
</html>
