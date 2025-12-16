<?php
require_once __DIR__ . '/inc/data.php';
require_once __DIR__ . '/inc/header.php';
?>
<section class="section">
    <h2 class="section-title">员工信息</h2>
    <div class="filter-bar" style="align-items:center;">
        <input id="empSearch" class="filter-input" placeholder="按姓名或ID搜索">
        <button id="empSearchBtn" class="btn btn-primary">搜索</button>
        <button id="empAddBtn" class="btn btn-success" style="margin-left:auto;">新增员工</button>
    </div>

    <div style="overflow:auto;">
        <table class="data-table" id="employeesTable">
            <thead>
                <tr>
                    <th>员工ID</th>
                    <th>姓名</th>
                    <th>工种</th>
                    <th>薪资 (¥)</th>
                    <th>入职时间</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $e): ?>
                <tr data-id="<?= htmlspecialchars($e['id']) ?>">
                    <td><?= htmlspecialchars($e['id']) ?></td>
                    <td><?= htmlspecialchars($e['name']) ?></td>
                    <td><?= htmlspecialchars($e['role']) ?></td>
                    <td><?= number_format($e['salary'],2) ?></td>
                    <td><?= htmlspecialchars($e['start_date']) ?></td>
                    <td><span class="status-tag <?= $e['status'] === '在职' ? 'status-accepted' : 'status-pending' ?>"><?= htmlspecialchars($e['status']) ?></span></td>
                    <td>
                        <button class="btn btn-primary btn-view">查看</button>
                        <button class="btn btn-warning btn-manage" style="margin-left:8px;">管理</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

<script>
// helper: open manage page (id optional)
function openManage(id) {
    const url = 'employees_manage.php' + (id ? ('?id=' + encodeURIComponent(id)) : '');
    window.location.href = url;
}

// View modal: show photo placeholder + more details
document.getElementById('employeesTable').addEventListener('click', function(e){
    const btn = e.target.closest('.btn');
    if (!btn) return;
    const tr = btn.closest('tr');
    if (!tr) return;
    const id = tr.dataset.id;
    if (btn.classList.contains('btn-view')) {
        const cells = tr.querySelectorAll('td');
        const html = `
            <div style="display:flex;gap:14px;align-items:flex-start;">
                <div style="width:140px;height:140px;background:#f5f5f5;border:1px dashed #ddd;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#999;">
                    照片占位
                </div>
                <div style="flex:1;">
                    <h3 style="margin:0 0 8px 0">${cells[1].textContent} <small style="color:#666;font-weight:500">(${cells[0].textContent})</small></h3>
                    <div style="color:#333;margin-bottom:6px;">工种：<strong>${cells[2].textContent}</strong></div>
                    <div style="color:#333;margin-bottom:6px;">薪资：<strong>¥${cells[3].textContent}</strong></div>
                    <div style="color:#333;margin-bottom:6px;">入职时间：<strong>${cells[4].textContent}</strong></div>
                    <div style="color:#333;">状态：<strong>${cells[5].textContent}</strong></div>
                </div>
            </div>
        `;
        showAppModal('员工详情', html, {showCancel:false, okText:'关闭'});
    }
    if (btn.classList.contains('btn-manage')) {
        openManage(id);
    }
});

// 新增按钮 -> 打开管理页面用于新增
document.getElementById('empAddBtn').addEventListener('click', function(){ openManage(); });

// 简单搜索（前端过滤表格）
document.getElementById('empSearchBtn').addEventListener('click', function(){
    const q = (document.getElementById('empSearch').value || '').trim().toLowerCase();
    const rows = document.querySelectorAll('#employeesTable tbody tr');
    rows.forEach(r=>{
        const text = (r.textContent || '').toLowerCase();
        r.style.display = text.includes(q) ? '' : 'none';
    });
});
document.getElementById('empSearch').addEventListener('keydown', function(e){ if (e.key==='Enter') document.getElementById('empSearchBtn').click(); });
</script>
