<?php
require_once __DIR__ . '/inc/data.php';
require_once __DIR__ . '/inc/header.php';
?>
<!-- replaced dashboard: add two pie charts, one bar chart and one extra line chart -->
<section class="section">
    <h2 class="section-title">数据概览</h2>
    <div class="dashboard-cards" style="display:flex;gap:18px;flex-wrap:wrap;">
        <div class="card"><div class="card-icon">🏬</div><div class="card-title">门店总数</div><div class="card-value"><?= htmlspecialchars($storesCount) ?></div></div>
        <div class="card"><div class="card-icon">🤝</div><div class="card-title">供应商数量</div><div class="card-value"><?= htmlspecialchars($suppliersCount) ?></div></div>
        <div class="card"><div class="card-icon">👨‍👩‍👧‍👦</div><div class="card-title">员工数</div><div class="card-value"><?= htmlspecialchars($employeesCount) ?></div></div>
        <div class="card"><div class="card-icon">🧑‍💳</div><div class="card-title">顾客数</div><div class="card-value"><?= htmlspecialchars($customersCount) ?></div></div>

        <!-- pie chart 1 -->
        <div class="card" style="min-width:240px;background:#fff;">
            <div style="font-weight:600;color:#1976d2;margin-bottom:8px;">门店类型占比</div>
            <canvas id="pieChart1" width="260" height="160" style="width:100%;height:160px;"></canvas>
        </div>

        <!-- pie chart 2 -->
        <div class="card" style="min-width:240px;background:#fff;">
            <div style="font-weight:600;color:#1976d2;margin-bottom:8px;">销售渠道占比</div>
            <canvas id="pieChart2" width="260" height="160" style="width:100%;height:160px;"></canvas>
        </div>

        <!-- bar chart -->
        <div class="card" style="min-width:360px;background:#fff;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                <div style="font-weight:600;color:#1976d2;">月度成交（柱状图）</div>
                <div style="font-size:13px;color:#666;">单位：笔</div>
            </div>
            <canvas id="barChart" width="360" height="160" style="width:100%;height:160px;"></canvas>
        </div>

        <!-- revenue line chart (existing) -->
        <div class="card" style="min-width:320px;background:#fff;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                <div style="font-weight:600;color:#1976d2;">收益（近12期）</div><div style="font-size:13px;color:#666;">单位：¥</div>
            </div>
            <canvas id="revenueChart" width="520" height="150" style="width:100%;height:150px;"></canvas>
        </div>

        <!-- extra trend line chart -->
        <div class="card" style="min-width:320px;background:#fff;">
            <div style="font-weight:600;color:#1976d2;margin-bottom:8px;">流量趋势（折线）</div>
            <canvas id="trendChart" width="520" height="150" style="width:100%;height:150px;"></canvas>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

<script>
// draw helpers (very small, no libs) -------------------------------------------------
function drawPie(id, data, colors) {
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
        ang += a;
    });
    // small legend (right)
    let lx = 8, ly = 8;
    ctx.font = '12px Arial';
    data.forEach((v,i)=>{
        ctx.fillStyle = colors[i % colors.length];
        ctx.fillRect(w - 90, ly, 12, 12);
        ctx.fillStyle = '#333';
        ctx.fillText(`${v}`, w - 72, ly + 10);
        ly += 18;
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
    values.forEach((v,i)=>{
        const x = padding + i * ((w - padding*2)/values.length) + ((w - padding*2)/values.length - barW)/2;
        const barH = (v / max) * (h - 2*padding);
        ctx.fillStyle = color;
        ctx.fillRect(x, h - padding - barH, barW, barH);
        ctx.fillStyle = '#666';
        ctx.font = '12px Arial';
        ctx.fillText(labels[i], x, h - 6);
    });
}

function drawLine(id, data, color) {
    const cvs = document.getElementById(id);
    if (!cvs) return;
    const ctx = cvs.getContext('2d');
    const w = cvs.width, h = cvs.height;
    ctx.clearRect(0,0,w,h);
    const padding = 20;
    const max = Math.max(...data) || 1;
    const stepX = (w - padding*2) / (data.length - 1 || 1);
    ctx.beginPath();
    data.forEach((v,i)=>{
        const x = padding + i * stepX;
        const y = h - padding - (v / max) * (h - padding*2);
        if (i === 0) ctx.moveTo(x,y); else ctx.lineTo(x,y);
    });
    ctx.strokeStyle = color;
    ctx.lineWidth = 2;
    ctx.stroke();
    // dots
    ctx.fillStyle = color;
    data.forEach((v,i)=>{
        const x = padding + i * stepX;
        const y = h - padding - (v / max) * (h - padding*2);
        ctx.beginPath(); ctx.arc(x,y,2.5,0,Math.PI*2); ctx.fill();
    });
}

// initialize charts on DOM ready ----------------------------------------------------
document.addEventListener('DOMContentLoaded', function(){
    // sample data
    const pie1 = [45, 30, 25];
    const pie2 = [60, 25, 15];
    const barLabels = ['1月','2月','3月','4月','5月','6月'];
    const barValues = [120, 200, 150, 180, 220, 170];
    const revenue = <?= json_encode($revenueSample, JSON_UNESCAPED_UNICODE) ?> || [1200,1500,1800,1700,2100,2400,2200,2600,3000,2800,3200,3600];
    const trend = [80,90,100,95,120,140,130,150,160,170,180,190];

    drawPie('pieChart1', pie1, ['#1976d2','#43a047','#fbc02d']);
    drawPie('pieChart2', pie2, ['#8e24aa','#1976d2','#ff7043']);
    drawBar('barChart', barLabels, barValues, '#1976d2');
    drawLine('revenueChart', revenue, '#1976d2');
    drawLine('trendChart', trend, '#43a047');
});
</script>
