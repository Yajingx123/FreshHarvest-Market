<?php
require_once __DIR__ . '/inc/data.php';
require_once __DIR__ . '/inc/header.php';
?>
<section class="section">
    <h2 class="section-title">顾客信息</h2>
    <div class="filter-bar" style="align-items:center;">
        <input id="custSearch" class="filter-input" placeholder="按姓名/手机号/ID搜索">
        <button id="custSearchBtn" class="btn btn-primary">搜索</button>
        <button id="custExportBtn" class="btn btn-success" style="margin-left:auto;">导出列表</button>
    </div>

    <div style="overflow:auto;">
        <table class="data-table" id="customersTable">
            <thead>
                <tr>
                    <th>顾客ID</th>
                    <th>姓名</th>
                    <th>手机</th>
                    <th>注册日期</th>
                    <th>购买次数</th>
                    <th>总消费 (¥)</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $c): ?>
                <tr data-id="<?= htmlspecialchars($c['id']) ?>">
                    <td><?= htmlspecialchars($c['id']) ?></td>
                    <td><?= htmlspecialchars($c['name']) ?></td>
                    <td><?= htmlspecialchars($c['phone']) ?></td>
                    <td><?= htmlspecialchars($c['registered']) ?></td>
                    <td><?= (int)$c['orders'] ?></td>
                    <td><?= number_format($c['total_spent'],2) ?></td>
                    <td><button class="btn btn-primary btn-view">查看</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

<script>
document.getElementById('customersTable').addEventListener('click', function(e){
    const btn = e.target.closest('.btn');
    if (!btn) return;
    const tr = btn.closest('tr'); if (!tr) return;
    if (btn.classList.contains('btn-view')) {
        const tds = tr.querySelectorAll('td');
        const html = `<div style="line-height:1.6;">
            <strong>${tds[1].textContent} (${tds[0].textContent})</strong>
            <div>手机：${tds[2].textContent}</div>
            <div>注册日期：${tds[3].textContent}</div>
            <div>购买次数：${tds[4].textContent}</div>
            <div>总消费：¥${tds[5].textContent}</div>
        </div>`;
        showAppModal('顾客详情', html, {showCancel:false, okText:'关闭'});
    }
});
</script>
