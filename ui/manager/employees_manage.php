<?php
require_once __DIR__ . '/inc/data.php';
require_once __DIR__ . '/inc/header.php';

// find employee by GET id if provided (server-side fallback)
$empId = isset($_GET['id']) ? trim($_GET['id']) : '';
$empServer = null;
if ($empId) {
    foreach ($employees as $ee) if ($ee['id'] === $empId) { $empServer = $ee; break; }
}
?>
<section class="section">
    <h2 class="section-title"><?= $empId ? '管理员工' : '新增员工' ?></h2>

    <div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap;">
        <div style="width:260px;">
            <div style="width:220px;height:220px;background:#f5f5f5;border:1px dashed #ddd;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#999;">
                员工照片占位
            </div>
        </div>

        <div style="flex:1;min-width:320px;">
            <form id="manageForm">
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label">员工ID</label>
                            <input id="mf_id" class="form-control" value="<?= $empServer ? htmlspecialchars($empServer['id']) : '' ?>" <?= $empServer ? 'readonly' : '' ?> />
                        </div>
                        <div class="form-group">
                            <label class="form-label">姓名</label>
                            <input id="mf_name" class="form-control" value="<?= $empServer ? htmlspecialchars($empServer['name']) : '' ?>" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">薪资 (¥)</label>
                            <input id="mf_salary" type="number" step="0.01" class="form-control" value="<?= $empServer ? htmlspecialchars($empServer['salary']) : '' ?>" />
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label">所属门店</label>
                            <select id="mf_store" class="form-control">
                                <option value="">（未关联）</option>
                                <?php foreach ($partners as $p): ?>
                                    <option value="<?= htmlspecialchars($p['id']) ?>"><?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['id']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">岗位</label>
                            <select id="mf_role" class="form-control">
                                <option value="manager">manager</option>
                                <option value="staff">staff</option>
                                <option value="delivery">delivery</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">状态</label>
                            <select id="mf_status" class="form-control">
                                <option value="在职">在职</option>
                                <option value="休假">休假</option>
                                <option value="离职">离职</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">入职时间</label>
                            <input id="mf_start" type="date" class="form-control" value="<?= $empServer ? htmlspecialchars($empServer['start_date']) : '' ?>" />
                        </div>
                    </div>
                </div>

                <div style="margin-top:12px;">
                    <button type="button" id="mf_save" class="btn btn-primary">保存</button>
                    <button type="button" id="mf_cancel" class="btn" style="margin-left:8px;">取消</button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

<script>
// Manage page logic: persist to localStorage key supplier_employees_v1
const LS_KEY = 'supplier_employees_v1';

// helper: load list from localStorage or server snapshot embedded by PHP
function loadLocalEmployees() {
    try {
        const raw = localStorage.getItem(LS_KEY);
        if (raw) return JSON.parse(raw);
    } catch (e) {}
    // fallback to server-side data injected via a small JSON
    try {
        return <?= json_encode($employees, JSON_UNESCAPED_UNICODE) ?>;
    } catch(e) { return []; }
}

function saveLocalEmployees(list) {
    try { localStorage.setItem(LS_KEY, JSON.stringify(list)); } catch(e) {}
}

document.addEventListener('DOMContentLoaded', function(){
    // prefill form from localStorage if matching id exists
    const id = new URLSearchParams(window.location.search).get('id');
    const list = loadLocalEmployees();
    let emp = null;
    if (id) emp = list.find(x=>x.id === id) || null;
    if (!emp && id) {
        // if not found in localStorage, try server-rendered values already in inputs (handled by PHP)
        // nothing to do
    }
    if (emp) {
        document.getElementById('mf_id').value = emp.id;
        document.getElementById('mf_id').setAttribute('readonly','readonly');
        document.getElementById('mf_name').value = emp.name || '';
        document.getElementById('mf_salary').value = emp.salary || '';
        document.getElementById('mf_start').value = emp.start_date || '';
        document.getElementById('mf_role').value = emp.role || 'staff';
        document.getElementById('mf_status').value = emp.status || '在职';
        if (emp.store) document.getElementById('mf_store').value = emp.store;
    }

    document.getElementById('mf_save').addEventListener('click', function(){
        const idv = (document.getElementById('mf_id').value || '').trim() || ('EMP' + Date.now().toString().slice(-6));
        const obj = {
            id: idv,
            name: (document.getElementById('mf_name').value||'').trim(),
            salary: parseFloat(document.getElementById('mf_salary').value || '0') || 0,
            start_date: (document.getElementById('mf_start').value || ''),
            role: document.getElementById('mf_role').value || 'staff',
            status: document.getElementById('mf_status').value || '在职',
            store: document.getElementById('mf_store').value || ''
        };
        // load -> upsert -> save
        const cur = loadLocalEmployees();
        const idx = cur.findIndex(x=>x.id === obj.id);
        if (idx >= 0) cur[idx] = Object.assign(cur[idx], obj);
        else cur.push(obj);
        saveLocalEmployees(cur);
        alert('已保存（本地）');
        window.location.href = 'employees.php';
    });

    document.getElementById('mf_cancel').addEventListener('click', function(){
        window.location.href = 'employees.php';
    });
});
</script>
