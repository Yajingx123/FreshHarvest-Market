<?php
// employees.php
require_once __DIR__ . '/inc/db_connect.php';
require_once __DIR__ . '/inc/data.php';
require_once __DIR__ . '/inc/header.php';

// 检查数据是否加载
if (!isset($employees) || !is_array($employees)) {
    $employees = [];
    error_log("警告: employees 数据未加载或为空");
}
?>
<section class="section">
    <h2 class="section-title">员工信息</h2>
    <div class="filter-bar" style="align-items:center;">
        <input id="empSearch" class="filter-input" placeholder="按姓名、ID、职位搜索">
        <select id="empRoleFilter" class="filter-select">
            <option value="">所有职位</option>
            <option value="Manager">经理</option>
            <option value="Sales">销售</option>
            <option value="Deliveryman">配送员</option>
        </select>
        <select id="empStatusFilter" class="filter-select">
            <option value="">所有状态</option>
            <option value="在职">在职</option>
            <option value="休假">休假</option>
            <option value="离职">离职</option>
        </select>
        <select id="empBranchFilter" class="filter-select">
           <option value="">所有门店</option>
           <option value="未分配">未分配门店</option>  <!-- 添加这个 -->
           <?php 
           // 提取唯一门店列表
           $branchNames = array_unique(array_column($employees, 'branch_name'));
           foreach ($branchNames as $branch) {
              $displayName = empty($branch) ? '未分配' : $branch;
            echo "<option value=\"" . htmlspecialchars($displayName) . "\">" . htmlspecialchars($displayName) . "</option>";
}
    ?>
</select>
        <button id="empSearchBtn" class="btn btn-primary">搜索</button>
        <button id="empAddBtn" class="btn btn-success" style="margin-left:auto;">新增员工</button>
    </div>

    <div style="overflow:auto;">
        <table class="data-table" id="employeesTable">
            <thead>
                <tr>
                    <th>员工ID</th>
                    <th>姓名</th>
                    <th>职位</th>
                    <th>薪资 (¥)</th>
                    <th>入职时间</th>
                    <th>门店</th>
                    <th>联系电话</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($employees)): ?>
                <tr>
                    <td colspan="9" style="text-align:center;padding:20px;color:#666;">
                        暂无员工数据
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($employees as $e): ?>
                <tr data-id="<?= htmlspecialchars($e['id']) ?>"
                    data-role="<?= htmlspecialchars($e['role']) ?>"
                    data-status="<?= htmlspecialchars($e['status']) ?>"
                    data-branch="<?= htmlspecialchars($e['branch_name'] ?? '') ?>">
                    <td><?= htmlspecialchars($e['id']) ?></td>
                    <td><?= htmlspecialchars($e['name']) ?></td>
                    <td><?= htmlspecialchars($e['role']) ?></td>
                    <td><?= number_format($e['salary'], 2) ?></td>
                    <td><?= htmlspecialchars($e['hire_date'] ?? $e['start_date']) ?></td>
                    <td><?= htmlspecialchars($e['branch_name'] ?? '未分配') ?></td>
                    <td><?= htmlspecialchars($e['phone'] ?? '未设置') ?></td>
                    <td style="white-space: nowrap;">
                    <?php 
                       $statusClass = '';
                        if ($e['status'] === '在职') $statusClass = 'status-accepted';
                        elseif ($e['status'] === '休假') $statusClass = 'status-pending';
                        elseif ($e['status'] === '离职') $statusClass = 'status-cancelled';
                    ?>
                    <span class="status-tag <?= $statusClass ?>" style="display: inline-block; min-width: 60px; text-align: center;">
                    <?= htmlspecialchars($e['status']) ?>
                    </span>
                    </td>
                    <td style="white-space: nowrap;">
                         <button class="btn btn-primary btn-view" title="查看详情" style="width: 60px;">查看</button>
                         <button class="btn btn-warning btn-manage" title="编辑信息" style="width: 60px; margin-left: 8px;">管理</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

<script>
// 全局员工数据（从PHP传递，用于详情弹窗）
const employeesData = <?= json_encode($employees, JSON_UNESCAPED_UNICODE) ?>;

// HTML转义函数
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// helper: open manage page (id optional)
function openManage(id) {
    const url = 'employees_manage.php' + (id ? ('?id=' + encodeURIComponent(id)) : '');
    window.location.href = url;
}

// View modal: 显示完整员工信息
document.getElementById('employeesTable').addEventListener('click', function(e){
    const btn = e.target.closest('.btn');
    if (!btn) return;
    const tr = btn.closest('tr');
    if (!tr) return;
    const id = tr.dataset.id;
    
    if (btn.classList.contains('btn-view')) {
        // 查找对应的员工数据
        const employee = employeesData.find(emp => emp.id == id);
        if (!employee) {
            // 如果找不到，使用表格中的数据
            const cells = tr.querySelectorAll('td');
            const html = `
                <div style="display:flex;gap:14px;align-items:flex-start;">
                    <div style="width:140px;height:140px;background:#f5f5f5;border:1px dashed #ddd;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#999;">
                        员工照片
                    </div>
                    <div style="flex:1;">
                        <h3 style="margin:0 0 8px 0">${escapeHtml(cells[1].textContent)} <small style="color:#666;font-weight:500">(${escapeHtml(cells[0].textContent)})</small></h3>
                        <div style="color:#333;margin-bottom:6px;">职位：<strong>${escapeHtml(cells[2].textContent)}</strong></div>
                        <div style="color:#333;margin-bottom:6px;">薪资：<strong>¥${escapeHtml(cells[3].textContent)}</strong></div>
                        <div style="color:#333;margin-bottom:6px;">入职时间：<strong>${escapeHtml(cells[4].textContent)}</strong></div>
                        <div style="color:#333;margin-bottom:6px;">门店：<strong>${escapeHtml(cells[5].textContent)}</strong></div>
                        <div style="color:#333;margin-bottom:6px;">电话：<strong>${escapeHtml(cells[6].textContent)}</strong></div>
                        <div style="color:#333;">状态：<strong>${escapeHtml(cells[7].textContent)}</strong></div>
                    </div>
                </div>
            `;
            showAppModal('员工详情', html, {showCancel:false, okText:'关闭'});
            return;
        }
        
        // 创建详情弹窗内容
        const html = `
            <div style="display:flex;gap:20px;align-items:flex-start;">
                <!-- 左侧：照片和基本信息 -->
                <div style="width:150px;flex-shrink:0;">
                    <div style="width:140px;height:140px;background:#f5f5f5;border:1px dashed #ddd;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#999;margin-bottom:12px;">
                        员工照片
                    </div>
                    <div style="text-align:center;">
                        <strong style="display:block;">${escapeHtml(employee.name)}</strong>
                        <small style="color:#666;">ID: ${escapeHtml(employee.id)}</small>
                    </div>
                </div>
                
                <!-- 右侧：详细信息 -->
                <div style="flex:1;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                        <div>
                            <div style="color:#666;font-size:13px;margin-bottom:4px;">职位</div>
                            <div style="color:#333;font-weight:500;">${escapeHtml(employee.role)}</div>
                        </div>
                        <div>
                            <div style="color:#666;font-size:13px;margin-bottom:4px;">薪资</div>
                            <div style="color:#333;font-weight:500;">¥${employee.salary ? employee.salary.toFixed(2) : '0.00'}</div>
                        </div>
                        <div>
                            <div style="color:#666;font-size:13px;margin-bottom:4px;">入职时间</div>
                            <div style="color:#333;font-weight:500;">${escapeHtml(employee.hire_date || employee.start_date)}</div>
                        </div>
                        <div>
                            <div style="color:#666;font-size:13px;margin-bottom:4px;">状态</div>
                            <div style="color:#333;font-weight:500;">${escapeHtml(employee.status)}</div>
                        </div>
                    </div>
                    
                    <div style="border-top:1px solid #eee;padding-top:16px;">
                        <h4 style="margin:0 0 8px 0;font-size:15px;">联系信息</h4>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div>
                                <div style="color:#666;font-size:13px;margin-bottom:4px;">邮箱</div>
                                <div style="color:#333;">${escapeHtml(employee.email || '未设置')}</div>
                            </div>
                            <div>
                                <div style="color:#666;font-size:13px;margin-bottom:4px;">电话</div>
                                <div style="color:#333;">${escapeHtml(employee.phone || '未设置')}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="border-top:1px solid #eee;padding-top:16px;">
                        <h4 style="margin:0 0 8px 0;font-size:15px;">工作信息</h4>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div>
                                <div style="color:#666;font-size:13px;margin-bottom:4px;">所属门店</div>
                                <div style="color:#333;">${escapeHtml(employee.branch_name || '未分配')}</div>
                            </div>
                            <div>
                                <div style="color:#666;font-size:13px;margin-bottom:4px;">用户名</div>
                                <div style="color:#333;">${escapeHtml(employee.username || '未设置')}</div>
                            </div>
                        </div>
                        ${employee.created_at ? `
                        <div style="margin-top:8px;">
                            <div style="color:#666;font-size:13px;margin-bottom:4px;">创建时间</div>
                            <div style="color:#333;">${escapeHtml(employee.created_at)}</div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
        
        if (typeof showAppModal === 'function') {
            showAppModal('员工详情', html, {showCancel:false, okText:'关闭', width:'700px'});
        } else {
            // 备用方案：使用原生弹窗
            const modal = document.createElement('div');
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100%';
            modal.style.height = '100%';
            modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
            modal.style.zIndex = '9999';
            
            modal.innerHTML = `
                <div style="background:white;padding:20px;border-radius:8px;max-width:700px;max-height:80vh;overflow:auto;">
                    <h3 style="margin-top:0;">员工详情</h3>
                    ${html}
                    <div style="text-align:right;margin-top:20px;">
                        <button onclick="this.closest('div[style*=\"position: fixed\"]').remove()" class="btn btn-primary">关闭</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
    }
    
    if (btn.classList.contains('btn-manage')) {
        openManage(id);
    }
});

// 新增按钮 -> 打开管理页面用于新增
document.getElementById('empAddBtn').addEventListener('click', function(){ 
    openManage(); 
});

// 高级搜索过滤
function filterEmployees() {
    const searchQuery = (document.getElementById('empSearch').value || '').trim().toLowerCase();
    const roleFilter = (document.getElementById('empRoleFilter').value || '').trim();
    const statusFilter = (document.getElementById('empStatusFilter').value || '').trim();
    const branchFilter = (document.getElementById('empBranchFilter').value || '').trim();
    
    const rows = document.querySelectorAll('#employeesTable tbody tr');
    rows.forEach(row => {
        if (row.cells.length < 2) return; // 跳过空行提示
        
        const text = (row.textContent || '').toLowerCase();
        const rowRole = row.dataset.role || '';
        const rowStatus = row.dataset.status || '';
        const rowBranch = row.dataset.branch || '';
        
        // 应用所有过滤器
        const matchSearch = !searchQuery || text.includes(searchQuery);
        const matchRole = !roleFilter || rowRole === roleFilter;
        const matchStatus = !statusFilter || rowStatus === statusFilter;
        const matchBranch = !branchFilter || 
                   (branchFilter === "未分配" && (rowBranch === "" || rowBranch === "未分配")) || 
                   (branchFilter !== "未分配" && rowBranch === branchFilter);
        
        row.style.display = (matchSearch && matchRole && matchStatus && matchBranch) ? '' : 'none';
    });
}

// 绑定搜索事件
document.getElementById('empSearchBtn').addEventListener('click', filterEmployees);
document.getElementById('empSearch').addEventListener('keydown', function(e){ 
    if (e.key === 'Enter') filterEmployees();
});
document.getElementById('empRoleFilter').addEventListener('change', filterEmployees);
document.getElementById('empStatusFilter').addEventListener('change', filterEmployees);
document.getElementById('empBranchFilter').addEventListener('change', filterEmployees);

// 初始加载后执行一次过滤
setTimeout(filterEmployees, 100);
</script>