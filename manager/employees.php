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
    <h2 class="section-title">Employees</h2>
    <div class="filter-bar" style="align-items:center;">
        <input id="empSearch" class="filter-input" placeholder="Search by name, ID, or role">
        <select id="empRoleFilter" class="filter-select">
            <option value="">All roles</option>
            <option value="Manager">Manager</option>
            <option value="Sales">Sales</option>
            <option value="Deliveryman">Delivery</option>
        </select>
        <select id="empStatusFilter" class="filter-select">
            <option value="">All statuses</option>
            <option value="Active">Active</option>
            <option value="On leave">On leave</option>
            <option value="Terminated">Terminated</option>
        </select>
        <select id="empBranchFilter" class="filter-select">
           <option value="">All branches</option>
           <option value="Unassigned">Unassigned</option>
           <?php 
           // 提取唯一门店列表
           $branchNames = array_unique(array_map(function($branchName) {
               if ($branchName === '' || $branchName === null || $branchName === '未分配' || $branchName === 'Unassigned') {
                   return 'Unassigned';
               }
               return $branchName;
           }, array_column($employees, 'branch_name')));
           foreach ($branchNames as $branch) {
              $isUnassigned = ($branch === 'Unassigned' || $branch === '');
              $value = $isUnassigned ? 'Unassigned' : $branch;
              $label = $isUnassigned ? 'Unassigned' : $branch;
              echo "<option value=\"" . htmlspecialchars($value) . "\">" . htmlspecialchars($label) . "</option>";
           }
    ?>
</select>
        <button id="empSearchBtn" class="btn btn-primary">Search</button>
        <button id="empAddBtn" class="btn btn-success" style="margin-left:auto;">Add employee</button>
    </div>

    <div style="overflow:auto;">
        <table class="data-table" id="employeesTable">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Salary (¥)</th>
                    <th>Hire Date</th>
                    <th>Branch</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($employees)): ?>
                <tr>
                    <td colspan="9" style="text-align:center;padding:20px;color:#666;">
                        No employee data
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($employees as $e): ?>
                <?php
                   $statusMap = [
                       'active' => 'Active',
                       'on_leave' => 'On leave',
                       'terminated' => 'Terminated',
                       '在职' => 'Active',
                       '休假' => 'On leave',
                       '离职' => 'Terminated',
                       'Active' => 'Active',
                       'On leave' => 'On leave',
                       'Terminated' => 'Terminated'
                   ];
                   $statusLabel = $statusMap[$e['status']] ?? $e['status'];
                   $statusClassMap = [
                       'Active' => 'status-accepted',
                       'On leave' => 'status-pending',
                       'Terminated' => 'status-cancelled'
                   ];
                   $statusClass = $statusClassMap[$statusLabel] ?? '';
                   $branchDisplay = $e['branch_name'] ?? '';
                   if ($branchDisplay === '' || $branchDisplay === '未分配' || $branchDisplay === 'Unassigned') {
                       $branchDisplay = 'Unassigned';
                   }
                ?>
                <tr data-id="<?= htmlspecialchars($e['id']) ?>"
                    data-role="<?= htmlspecialchars($e['role']) ?>"
                    data-status="<?= htmlspecialchars($statusLabel) ?>"
                    data-branch="<?= htmlspecialchars($branchDisplay) ?>">
                    <td><?= htmlspecialchars($e['id']) ?></td>
                    <td><?= htmlspecialchars($e['name']) ?></td>
                    <td><?= htmlspecialchars($e['role']) ?></td>
                    <td><?= number_format($e['salary'], 2) ?></td>
                    <td><?= htmlspecialchars($e['hire_date'] ?? $e['start_date']) ?></td>
                    <td><?= htmlspecialchars($branchDisplay) ?></td>
                    <td><?= htmlspecialchars($e['phone'] ?? 'Not set') ?></td>
                    <td style="white-space: nowrap;">
                    <span class="status-tag <?= $statusClass ?>" style="display: inline-block; min-width: 60px; text-align: center;">
                    <?= htmlspecialchars($statusLabel) ?>
                    </span>
                    </td>
                    <td style="white-space: nowrap;">
                         <button class="btn btn-primary btn-view" title="View details" style="width: 70px;">View</button>
                         <button class="btn btn-warning btn-manage" title="Manage" style="width: 70px; margin-left: 8px;">Manage</button>
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

function formatEmployeeStatus(status) {
    const map = {
        active: 'Active',
        on_leave: 'On leave',
        terminated: 'Terminated',
        '在职': 'Active',
        '休假': 'On leave',
        '离职': 'Terminated',
        'Active': 'Active',
        'On leave': 'On leave',
        'Terminated': 'Terminated'
    };
    return map[status] || status || 'Unknown';
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
                        Employee photo
                    </div>
                    <div style="flex:1;">
                        <h3 style="margin:0 0 8px 0">${escapeHtml(cells[1].textContent)} <small style="color:#666;font-weight:500">(${escapeHtml(cells[0].textContent)})</small></h3>
                        <div style="color:#333;margin-bottom:6px;">Role: <strong>${escapeHtml(cells[2].textContent)}</strong></div>
                        <div style="color:#333;margin-bottom:6px;">Salary: <strong>¥${escapeHtml(cells[3].textContent)}</strong></div>
                        <div style="color:#333;margin-bottom:6px;">Hire date: <strong>${escapeHtml(cells[4].textContent)}</strong></div>
                        <div style="color:#333;margin-bottom:6px;">Branch: <strong>${escapeHtml(cells[5].textContent)}</strong></div>
                        <div style="color:#333;margin-bottom:6px;">Phone: <strong>${escapeHtml(cells[6].textContent)}</strong></div>
                        <div style="color:#333;">Status: <strong>${escapeHtml(cells[7].textContent)}</strong></div>
                    </div>
                </div>
            `;
            showAppModal('Employee Details', html, {showCancel:false, okText:'Close'});
            return;
        }
        
        // 创建详情弹窗内容
        const html = `
            <div style="display:flex;gap:20px;align-items:flex-start;">
                <!-- 左侧：照片和基本信息 -->
                <div style="width:150px;flex-shrink:0;">
                    <div style="width:140px;height:140px;background:#f5f5f5;border:1px dashed #ddd;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#999;margin-bottom:12px;">
                        Employee photo
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
                            <div style="color:#666;font-size:13px;margin-bottom:4px;">Role</div>
                            <div style="color:#333;font-weight:500;">${escapeHtml(employee.role)}</div>
                        </div>
                        <div>
                            <div style="color:#666;font-size:13px;margin-bottom:4px;">Salary</div>
                            <div style="color:#333;font-weight:500;">¥${employee.salary ? employee.salary.toFixed(2) : '0.00'}</div>
                        </div>
                        <div>
                            <div style="color:#666;font-size:13px;margin-bottom:4px;">Hire date</div>
                            <div style="color:#333;font-weight:500;">${escapeHtml(employee.hire_date || employee.start_date)}</div>
                        </div>
                        <div>
                            <div style="color:#666;font-size:13px;margin-bottom:4px;">Status</div>
                            <div style="color:#333;font-weight:500;">${escapeHtml(formatEmployeeStatus(employee.status))}</div>
                        </div>
                    </div>
                    
                    <div style="border-top:1px solid #eee;padding-top:16px;">
                        <h4 style="margin:0 0 8px 0;font-size:15px;">Contact Info</h4>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div>
                                <div style="color:#666;font-size:13px;margin-bottom:4px;">Email</div>
                                <div style="color:#333;">${escapeHtml(employee.email || 'Not set')}</div>
                            </div>
                            <div>
                                <div style="color:#666;font-size:13px;margin-bottom:4px;">Phone</div>
                                <div style="color:#333;">${escapeHtml(employee.phone || 'Not set')}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="border-top:1px solid #eee;padding-top:16px;">
                        <h4 style="margin:0 0 8px 0;font-size:15px;">Work Info</h4>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div>
                                <div style="color:#666;font-size:13px;margin-bottom:4px;">Branch</div>
                                <div style="color:#333;">${escapeHtml(employee.branch_name || 'Unassigned')}</div>
                            </div>
                            <div>
                                <div style="color:#666;font-size:13px;margin-bottom:4px;">Username</div>
                                <div style="color:#333;">${escapeHtml(employee.username || 'Not set')}</div>
                            </div>
                        </div>
                        ${employee.created_at ? `
                        <div style="margin-top:8px;">
                            <div style="color:#666;font-size:13px;margin-bottom:4px;">Created</div>
                            <div style="color:#333;">${escapeHtml(employee.created_at)}</div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
        
        if (typeof showAppModal === 'function') {
            showAppModal('Employee Details', html, {showCancel:false, okText:'Close', width:'700px'});
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
                    <h3 style="margin-top:0;">Employee Details</h3>
                    ${html}
                    <div style="text-align:right;margin-top:20px;">
                        <button onclick="this.closest('div[style*=\"position: fixed\"]').remove()" class="btn btn-primary">Close</button>
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
        const isUnassigned = rowBranch === "" || rowBranch === "Unassigned" || rowBranch === "未分配";
        const matchBranch = !branchFilter ||
                   (branchFilter === "Unassigned" && isUnassigned) || 
                   (branchFilter !== "Unassigned" && rowBranch === branchFilter);
        
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
