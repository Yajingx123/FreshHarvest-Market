<?php
// stores.php
require_once __DIR__ . '/inc/db_connect.php';
require_once __DIR__ . '/inc/data.php';
require_once __DIR__ . '/inc/header.php';

// 检查数据是否加载
$branches = getBranchesData();
if (!isset($branches) || !is_array($branches)) {
    $branches = [];
}
?>
<section class="section">
    <h2 class="section-title">Stores</h2>
    <div class="filter-bar" style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
        <input id="storeSearch" class="filter-input" placeholder="Search by store name, address, or manager" style="flex:1;max-width:300px;">
        <select id="storeStatusFilter" class="filter-select">
            <option value="">All statuses</option>
            <option value="Open">Open</option>
            <option value="Renovating">Renovating</option>
            <option value="Closed">Closed</option>
        </select>
        <button class="btn btn-primary" id="storeSearchBtn">Search</button>
        <button class="btn btn-success" id="storeAddBtn" style="margin-left:auto;">Add store</button>
    </div>
    <div style="max-height:520px;overflow:auto;">
        <table class="data-table" style="min-width:1300px;">
            <thead>
                <tr>
                    <th>Store ID</th>
                    <th>Store Name</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Manager</th>
                    <th>Manager Phone</th>
                    <th>Staff</th>
                    <th>Status</th>
                    <th>Established</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="storesTbody">
                <?php if (!empty($branches)): ?>
                    <?php foreach ($branches as $branch): ?>
                    <?php
                        $statusLabelMap = [
                            'active' => 'Open',
                            '营业中' => 'Open',
                            'Open' => 'Open',
                            'under_renovation' => 'Renovating',
                            '装修中' => 'Renovating',
                            'Renovating' => 'Renovating',
                            'inactive' => 'Closed',
                            '已停业' => 'Closed',
                            'Closed' => 'Closed'
                        ];
                        $statusLabel = $statusLabelMap[$branch['status']] ?? $branch['status'];
                        $statusClassMap = [
                            'Open' => 'status-accepted',
                            'Renovating' => 'status-pending',
                            'Closed' => 'status-rejected'
                        ];
                        $status_class = $statusClassMap[$statusLabel] ?? '';
                    ?>
                    <tr data-id="<?= htmlspecialchars($branch['id']) ?>" 
                        data-status="<?= htmlspecialchars($statusLabel) ?>">
                        <td>#<?= htmlspecialchars($branch['id']) ?></td>
                        <td><?= htmlspecialchars($branch['name']) ?></td>
                        <td><?= htmlspecialchars($branch['address']) ?></td>
                        <td><?= htmlspecialchars($branch['phone']) ?></td>
                        <td><?= htmlspecialchars($branch['email']) ?></td>
                        <td><?= htmlspecialchars($branch['manager_name']) ?></td>
                        <td><?= htmlspecialchars($branch['manager_phone']) ?></td>
                        <td><?= $branch['staff_count'] ?></td>
                        <td>
                            <span class="status-tag <?= $status_class ?>">
                                <?= htmlspecialchars($statusLabel) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($branch['established_date']) ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-primary btn-view" title="View details">View</button>
                                <button class="btn btn-warning btn-edit" title="Edit">Edit</button>
                                <button class="btn btn-info btn-manage" title="Manage staff">Staff</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" style="text-align:center;color:#666;padding:18px;">
                            No stores found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

<script>
// 全局门店数据（从PHP传递）
const branchesData = <?= json_encode($branches, JSON_UNESCAPED_UNICODE) ?>;
// 全局员工数据（从PHP传递）
const employeesData = <?= json_encode($employees, JSON_UNESCAPED_UNICODE) ?>;

// HTML转义函数
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatBranchStatus(status) {
    const map = {
        active: 'Open',
        inactive: 'Closed',
        under_renovation: 'Renovating',
        '营业中': 'Open',
        '装修中': 'Renovating',
        '已停业': 'Closed',
        'Open': 'Open',
        'Renovating': 'Renovating',
        'Closed': 'Closed'
    };
    return map[status] || status || 'Unknown';
}

// 查看门店详情
function showBranchDetails(branchId) {
    const branch = branchesData.find(b => b.id == branchId);
    if (!branch) return;
    
    const html = `
        <div style="display:flex;gap:20px;align-items:flex-start;">
            <div style="width:150px;flex-shrink:0;">
                <div style="width:140px;height:140px;background:#f5f5f5;border:1px dashed #ddd;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#999;margin-bottom:12px;">
                    Store photo
                </div>
                <div style="text-align:center;">
                    <strong style="display:block;">${escapeHtml(branch.name)}</strong>
                    <small style="color:#666;">ID: #${escapeHtml(branch.id)}</small>
                </div>
            </div>
            
            <div style="flex:1;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                    <div>
                        <div style="color:#666;font-size:13px;margin-bottom:4px;">Phone</div>
                        <div style="color:#333;font-weight:500;">${escapeHtml(branch.phone || 'Not set')}</div>
                    </div>
                    <div>
                        <div style="color:#666;font-size:13px;margin-bottom:4px;">Email</div>
                        <div style="color:#333;">${escapeHtml(branch.email || 'Not set')}</div>
                    </div>
                    <div>
                        <div style="color:#666;font-size:13px;margin-bottom:4px;">Address</div>
                        <div style="color:#333;">${escapeHtml(branch.address)}</div>
                    </div>
                    <div>
                        <div style="color:#666;font-size:13px;margin-bottom:4px;">Status</div>
                        <div style="color:#333;font-weight:500;">${escapeHtml(formatBranchStatus(branch.status))}</div>
                    </div>
                </div>
                
                <div style="border-top:1px solid #eee;padding-top:16px;">
                    <h4 style="margin:0 0 8px 0;font-size:15px;">Manager Info</h4>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div>
                            <div style="color:#666;font-size:13px;margin-bottom:4px;">Manager Name</div>
                            <div style="color:#333;font-weight:500;">${escapeHtml(branch.manager_name)}</div>
                        </div>
                        <div>
                            <div style="color:#666;font-size:13px;margin-bottom:4px;">Manager Phone</div>
                            <div style="color:#333;">${escapeHtml(branch.manager_phone || 'Not set')}</div>
                        </div>
                    </div>
                </div>
                
                <div style="border-top:1px solid #eee;padding-top:16px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <div style="color:#666;font-size:13px;margin-bottom:4px;">Staff</div>
                            <div style="color:#333;font-weight:500;font-size:20px;">${branch.staff_count}</div>
                        </div>
                        <div>
                            <div style="color:#666;font-size:13px;margin-bottom:4px;">Established</div>
                            <div style="color:#333;">${escapeHtml(branch.established_date)}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="margin-top:20px;padding-top:20px;border-top:1px solid #eee;">
            <div style="display:flex;gap:10px;">
                <button class="btn btn-info" onclick="viewBranchStaff(${branch.id})" style="flex:1;">
                    <i class="fas fa-users" style="margin-right:6px;"></i>View staff
                </button>
                <button class="btn btn-warning" onclick="editBranch(${branch.id})" style="flex:1;">
                    <i class="fas fa-edit" style="margin-right:6px;"></i>Edit store
                </button>
                <button class="btn btn-success" onclick="viewBranchOrders(${branch.id})" style="flex:1;">
                    <i class="fas fa-shopping-cart" style="margin-right:6px;"></i>View orders
                </button>
            </div>
        </div>
    `;
    
    showAppModal('Store Details', html, {showCancel:false, okText:'Close', width:'800px'});
}

// 查看门店员工
function viewBranchStaff(branchId) {
    // 找到对应的门店信息
    const branch = branchesData.find(b => b.id == branchId);
    if (!branch) return;
    
    // 筛选该门店的员工，过滤掉状态为terminated的员工
     const branchEmployees = employeesData.filter(emp => 
      emp.branch_id == branchId && emp.status_raw !== 'terminated'
    );
    
    // 构建员工列表HTML
    let employeesHtml = '';
    if (branchEmployees.length > 0) {
        employeesHtml = `
        <table class="data-table" style="width:100%;">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Phone</th>
                    <th>Hire Date</th>
                </tr>
            </thead>
            <tbody>
        `;
        
        branchEmployees.forEach(emp => {
            employeesHtml += `
            <tr>
                <td>#${escapeHtml(emp.id)}</td>
                <td>${escapeHtml(emp.name)}</td>
                <td>${escapeHtml(emp.role || 'Not set')}</td>
                <td>${escapeHtml(emp.phone || 'Not set')}</td>
                <td>${escapeHtml(emp.hire_date || 'Not set')}</td>
            </tr>
            `;
        });
        employeesHtml += `
            </tbody>
        </table>
        `;
    } else {
        employeesHtml = `
        <div style="text-align:center;padding:30px;color:#666;">
            No staff data for this store
        </div>
        `;
    }
    
    // 显示弹窗
    const html = `
        <div>
            <h4 style="margin-top:0;margin-bottom:16px;">${escapeHtml(branch.name)} staff list (${branchEmployees.length})</h4>
            <div style="max-height:400px;overflow:auto;">
                ${employeesHtml}
            </div>
        </div>
    `;
    
    showAppModal('Store Staff', html, {showCancel:false, okText:'Close', width:'800px'});
}

// 编辑门店信息
function editBranch(branchId) {
    window.location.href = `branch_edit.php?id=${branchId}`;
}

// 查看门店订单
function viewBranchOrders(branchId) {
    window.location.href = `orders.php?branch_id=${branchId}`;
}

// 管理门店员工
function manageBranchStaff(branchId) {
    window.location.href = `branch_staff.php?id=${branchId}`;
}

// 高级搜索过滤
function filterStores() {
    const searchQuery = (document.getElementById('storeSearch').value || '').trim().toLowerCase();
    const statusFilter = (document.getElementById('storeStatusFilter').value || '').trim();
    
    const rows = document.querySelectorAll('#storesTbody tr');
    rows.forEach(row => {
        if (row.cells.length < 2) return; // 跳过空行提示
        
        const text = (row.textContent || '').toLowerCase();
        const rowStatus = row.dataset.status || '';
        
        // 应用所有过滤器
        const matchSearch = !searchQuery || text.includes(searchQuery);
        const matchStatus = !statusFilter || rowStatus === statusFilter;
        
        row.style.display = (matchSearch && matchStatus) ? '' : 'none';
    });
}

// 事件监听
document.addEventListener('DOMContentLoaded', function() {
    // 查看按钮
    document.getElementById('storesTbody').addEventListener('click', function(e) {
        const btn = e.target.closest('.btn');
        if (!btn) return;
        const tr = btn.closest('tr');
        if (!tr) return;
        const id = tr.dataset.id;
        
        if (btn.classList.contains('btn-view')) {
            showBranchDetails(id);
        }
        else if (btn.classList.contains('btn-edit')) {
            editBranch(id);
        }
        else if (btn.classList.contains('btn-manage')) {
            manageBranchStaff(id);
        }
    });
    
    // 新增门店按钮
    document.getElementById('storeAddBtn').addEventListener('click', function() {
        // 直接跳转到新增门店页面
       window.location.href = 'add_branch.php';
    });
    
    // 搜索功能
    document.getElementById('storeSearchBtn').addEventListener('click', filterStores);
    document.getElementById('storeSearch').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') filterStores();
    });
    document.getElementById('storeStatusFilter').addEventListener('change', filterStores);
});

// 初始过滤
setTimeout(filterStores, 100);
</script>

<style>
.status-tag {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    display: inline-block;
}
.status-accepted {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.status-pending {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}
.status-rejected {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.action-buttons {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: nowrap;
}
</style>
