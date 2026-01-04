<?php
require_once __DIR__ . '/inc/data.php';
require_once __DIR__ . '/inc/db_connect.php';
require_once __DIR__ . '/inc/header.php';

$suppliersData = getSuppliersFromDB();
?>
<section class="section">
    <h2 class="section-title">Suppliers</h2>
    <div class="filter-bar" style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
        <input id="supplierSearch" class="filter-input" placeholder="Search by supplier name, contact, or phone" style="flex:1;max-width:350px;">
        <select id="supplierCategoryFilter" class="filter-select">
            <option value="">All categories</option>
            <option value="果蔬">Produce</option>
            <option value="肉禽蛋">Meat & Eggs</option>
            <option value="水产">Seafood</option>
        </select>
        <select id="supplierStatusFilter" class="filter-select">
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
        <button class="btn btn-primary" id="supplierSearchBtn">Search</button>
        <button class="btn btn-success" id="supplierAddBtn" style="margin-left:auto;">Add supplier</button>
    </div>
    <div style="max-height:520px;overflow:auto;">
        <table class="data-table" style="min-width:1300px;">
            <thead>
                <tr>
                    <th>Supplier ID</th>
                    <th>Supplier Name</th>
                    <th>Category</th>
                    <th>Contact</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Tax ID</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="suppliersTbody">
                <!-- 数据将通过JavaScript动态生成 -->
            </tbody>
        </table>
    </div>
</section>

<!-- 供应商编辑/新增弹窗 -->
<div id="supplierModal" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:999;display:none;">
    <div style="background:#fff;width:600px;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.15);overflow:hidden;max-height:90vh;overflow-y:auto;">
        <div style="padding:16px;border-bottom:1px solid #eee;display:flex;align-items:center;justify-content:space-between;">
            <h3 style="margin:0;font-size:18px;font-weight:600;" id="supplierModalTitle">Add Supplier</h3>
            <button id="closeSupplierModal" style="background:transparent;border:none;font-size:20px;cursor:pointer;color:#666;">&times;</button>
        </div>
        <div style="padding:20px;" id="supplierModalContent">
            <!-- 表单将通过JS动态生成 -->
        </div>
        <div style="padding:12px 20px;border-top:1px solid #eee;display:flex;justify-content:flex-end;gap:10px;">
            <button id="cancelSupplierBtn" class="btn btn-default">Cancel</button>
            <button id="saveSupplierBtn" class="btn btn-primary">Save</button>
        </div>
    </div>
</div>

<!-- 供应商详情弹窗（新增的优化功能） -->
<div id="supplierDetailModal" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:9999;display:none;">
    <div style="background:#fff;width:800px;max-height:80vh;overflow:auto;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.15);">
        <div style="padding:16px;border-bottom:1px solid #eee;display:flex;align-items:center;justify-content:space-between;">
            <h3 style="margin:0;font-size:18px;font-weight:600;">Supplier Details</h3>
            <button id="closeDetailModal" style="background:transparent;border:none;font-size:20px;cursor:pointer;color:#666;">&times;</button>
        </div>
        <div style="padding:20px;" id="supplierDetailContent">
            <!-- 详情内容将通过JS动态生成 -->
        </div>
        <div style="padding:12px 20px;border-top:1px solid #eee;display:flex;justify-content:center;gap:10px;">
            <button id="closeDetailBtn" class="btn btn-primary">Close</button>
        </div>
    </div>
</div>

<!-- 供应产品弹窗（新增的优化功能） -->
<div id="supplierProductsModal" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:9999;display:none;">
    <div style="background:#fff;width:600px;max-height:80vh;overflow:auto;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.15);">
        <div style="padding:16px;border-bottom:1px solid #eee;display:flex;align-items:center;justify-content:space-between;">
            <h3 style="margin:0;font-size:18px;font-weight:600;" id="supplierProductsTitle">Supplied Products</h3>
            <button id="closeProductsModal" style="background:transparent;border:none;font-size:20px;cursor:pointer;color:#666;">&times;</button>
        </div>
        <div style="padding:20px;" id="supplierProductsContent">
            <!-- 产品内容将通过JS动态生成 -->
        </div>
        <div style="padding:12px 20px;border-top:1px solid #eee;display:flex;justify-content:center;gap:10px;">
            <button id="closeProductsBtn" class="btn btn-primary">Close</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

<script>
// 从PHP传递的供应商数据
const suppliersData = <?php echo json_encode($suppliersData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

let currentEditSupplierId = null;
let currentDeleteSupplierId = null;

// HTML转义函数
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function mapSupplierCategory(category) {
    const map = {
        '果蔬': 'Produce',
        '肉禽蛋': 'Meat & Eggs',
        '水产': 'Seafood'
    };
    return map[category] || category || 'Unknown';
}

// 渲染供应商表格
function renderSuppliersTable() {
    const tbody = document.getElementById('suppliersTbody');
    if (!tbody) return;
    
    if (suppliersData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;color:#666;padding:18px;">No suppliers found</td></tr>';
        return;
    }
    
    let html = '';
    suppliersData.forEach(supplier => {
        const categoryClass = supplier.category === '果蔬' ? 'category-veg' : 
                             supplier.category === '肉禽蛋' ? 'category-meat' : 'category-seafood';
        const statusClass = supplier.status === 'active' ? 'status-accepted' : 'status-rejected';
        const statusText = supplier.status === 'active' ? 'Active' : 'Inactive';
        const categoryLabel = mapSupplierCategory(supplier.category);
        
        html += `
        <tr data-id="${supplier.id}" 
            data-category="${supplier.category}"
            data-status="${supplier.status}">
            <td>SUP-${supplier.id}</td>
            <td>${escapeHtml(supplier.name)}</td>
            <td>
                <span class="category-tag ${categoryClass}">
                    ${escapeHtml(categoryLabel)}
                </span>
            </td>
            <td>${escapeHtml(supplier.contact_person)}</td>
            <td>${escapeHtml(supplier.phone)}</td>
            <td>${escapeHtml(supplier.email)}</td>
            <td>${escapeHtml(supplier.address)}</td>
            <td>${escapeHtml(supplier.tax_number)}</td>
            <td>
                <span class="status-tag ${statusClass}">
                    ${statusText}
                </span>
            </td>
            <td>${escapeHtml(supplier.created_at)}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-primary btn-view" title="View details">View</button>
                    <button class="btn btn-warning btn-edit" title="Edit">Edit</button>
                </div>
            </td>
        </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// 查看供应商详情（新增的优化功能）
function showSupplierDetails(supplierId) {
    const supplier = suppliersData.find(s => s.id == supplierId);
    if (!supplier) return;
    
    const categoryClass = supplier.category === '果蔬' ? 'category-veg' : 
                         supplier.category === '肉禽蛋' ? 'category-meat' : 'category-seafood';
    const statusClass = supplier.status === 'active' ? 'status-accepted' : 'status-rejected';
    const statusText = supplier.status === 'active' ? 'Active' : 'Inactive';
    const categoryLabel = mapSupplierCategory(supplier.category);
    
    const html = `
        <div style="display:flex;gap:20px;align-items:flex-start;">
            <div style="width:150px;flex-shrink:0;">
                <div style="width:140px;height:140px;background:#f5f5f5;border:1px dashed #ddd;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#999;margin-bottom:12px;">
                    Supplier logo
                </div>
                <div style="text-align:center;">
                    <strong style="display:block;">${escapeHtml(supplier.name)}</strong>
                    <small style="color:#666;">ID: SUP-${escapeHtml(supplier.id)}</small>
                </div>
            </div>
            
            <div style="flex:1;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                    <div>
                        <div style="color:#666;font-size:13px;margin-bottom:4px;">Category</div>
                        <div style="color:#333;font-weight:500;">
                            <span class="category-tag ${categoryClass}">
                                ${escapeHtml(categoryLabel)}
                            </span>
                        </div>
                    </div>
                    <div>
                        <div style="color:#666;font-size:13px;margin-bottom:4px;">Contact</div>
                        <div style="color:#333;font-weight:500;">${escapeHtml(supplier.contact_person)}</div>
                    </div>
                    <div>
                        <div style="color:#666;font-size:13px;margin-bottom:4px;">Phone</div>
                        <div style="color:#333;">${escapeHtml(supplier.phone)}</div>
                    </div>
                    <div>
                        <div style="color:#666;font-size:13px;margin-bottom:4px;">Email</div>
                        <div style="color:#333;">${escapeHtml(supplier.email || 'Not set')}</div>
                    </div>
                </div>
                
                <div style="border-top:1px solid #eee;padding-top:16px;">
                    <div style="margin-bottom:12px;">
                        <div style="color:#666;font-size:13px;margin-bottom:4px;">Address</div>
                        <div style="color:#333;">${escapeHtml(supplier.address || 'Not set')}</div>
                    </div>
                    <div style="margin-bottom:12px;">
                        <div style="color:#666;font-size:13px;margin-bottom:4px;">Tax ID</div>
                        <div style="color:#333;">${escapeHtml(supplier.tax_number || 'Not set')}</div>
                    </div>
                </div>
                
                <div style="border-top:1px solid #eee;padding-top:16px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <div style="color:#666;font-size:13px;margin-bottom:4px;">Status</div>
                            <div style="color:#333;font-weight:500;">
                                <span class="status-tag ${statusClass}">
                                    ${statusText}
                                </span>
                            </div>
                        </div>
                        <div>
                            <div style="color:#666;font-size:13px;margin-bottom:4px;">Created</div>
                            <div style="color:#333;">${escapeHtml(supplier.created_at)}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="margin-top:20px;padding-top:20px;border-top:1px solid #eee;">
            <div style="display:flex;gap:10px;">
                <button class="btn btn-warning" onclick="editSupplierFromDetail(${supplier.id})" style="flex:1;">
                    <i class="fas fa-edit" style="margin-right:6px;"></i>Edit supplier
                </button>
                <button class="btn btn-success" onclick="viewSupplierProducts(${supplier.id})" style="flex:1;">
                    <i class="fas fa-box" style="margin-right:6px;"></i>View products
                </button>
            </div>
        </div>
    `;
    
    // 显示详情弹窗
    document.getElementById('supplierDetailContent').innerHTML = html;
    document.getElementById('supplierDetailModal').style.display = 'flex';
}

// 从详情弹窗编辑供应商（新增的优化功能）
function editSupplierFromDetail(supplierId) {
    closeDetailModal();
    editSupplier(supplierId);
}

// 查看供应商的产品（新增的优化功能）
function viewSupplierProducts(supplierId) {
    const supplier = suppliersData.find(s => s.id == supplierId);
    if (!supplier) return;
    
    // 这里可以改为从后端获取产品数据
    // 暂时使用模拟数据，实际项目中可以调用AJAX获取真实数据
    const sampleProducts = [
        { id: 'P001', name: 'Organic Spinach', unit: 'jin', price: '¥12.50' },
        { id: 'P002', name: 'Fresh Tomatoes', unit: 'jin', price: '¥8.80' },
        { id: 'P003', name: 'Premium Potatoes', unit: 'jin', price: '¥4.50' }
    ];
    
    let productsHtml = '';
    sampleProducts.forEach(product => {
        productsHtml += `
        <tr>
            <td>${product.id}</td>
            <td>${product.name}</td>
            <td>${product.unit}</td>
            <td>${product.price}</td>
        </tr>
        `;
    });
    
    const html = `
        <div>
            <h4 style="margin-top:0;margin-bottom:16px;">${escapeHtml(supplier.name)} products</h4>
            <div style="max-height:300px;overflow:auto;">
                <table class="data-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>Unit</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${productsHtml}
                    </tbody>
                </table>
            </div>
            <div style="margin-top:16px;color:#666;font-size:13px;">
                Note: sample data; production should load from the backend.
            </div>
        </div>
    `;
    
    // 显示产品弹窗
    document.getElementById('supplierProductsTitle').textContent = `${supplier.name} - Products`;
    document.getElementById('supplierProductsContent').innerHTML = html;
    document.getElementById('supplierProductsModal').style.display = 'flex';
}

// 编辑供应商
function editSupplier(supplierId) {
    currentEditSupplierId = supplierId;
    const supplier = suppliersData.find(s => s.id == supplierId);
    
    const isEdit = !!supplierId && supplier;
    const title = isEdit ? 'Edit Supplier' : 'Add Supplier';
    
    // 生成表单HTML
    const formHtml = `
        <form id="supplierForm">
            <input type="hidden" id="supplier_id" name="supplier_id" value="${supplierId || ''}">
            
            <div class="form-group" style="margin-bottom:16px;">
                <label style="display:block;margin-bottom:6px;font-weight:500;color:#333;">Supplier Name <span style="color:red;">*</span></label>
                <input type="text" id="supplier_name" name="name" class="form-control" 
                       value="${supplier ? escapeHtml(supplier.name) : ''}" required>
            </div>
            
            <div class="form-group" style="margin-bottom:16px;">
                <label style="display:block;margin-bottom:6px;font-weight:500;color:#333;">Category <span style="color:red;">*</span></label>
                <select id="supplier_category" name="category" class="form-control" required>
                    <option value="">Select a category</option>
                    <option value="果蔬" ${supplier && supplier.category === '果蔬' ? 'selected' : ''}>Produce</option>
                    <option value="肉禽蛋" ${supplier && supplier.category === '肉禽蛋' ? 'selected' : ''}>Meat & Eggs</option>
                    <option value="水产" ${supplier && supplier.category === '水产' ? 'selected' : ''}>Seafood</option>
                </select>
            </div>
            
            <div class="form-row" style="display:flex;gap:16px;margin-bottom:16px;">
                <div class="form-col" style="flex:1;">
                    <label style="display:block;margin-bottom:6px;font-weight:500;color:#333;">Contact <span style="color:red;">*</span></label>
                    <input type="text" id="supplier_contact" name="contact_person" class="form-control"
                           value="${supplier ? escapeHtml(supplier.contact_person) : ''}" required>
                </div>
                <div class="form-col" style="flex:1;">
                    <label style="display:block;margin-bottom:6px;font-weight:500;color:#333;">Phone <span style="color:red;">*</span></label>
                    <input type="tel" id="supplier_phone" name="phone" class="form-control"
                           value="${supplier ? escapeHtml(supplier.phone) : ''}" required>
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom:16px;">
                <label style="display:block;margin-bottom:6px;font-weight:500;color:#333;">Email</label>
                <input type="email" id="supplier_email" name="email" class="form-control"
                       value="${supplier ? escapeHtml(supplier.email) : ''}">
            </div>
            
            <div class="form-group" style="margin-bottom:16px;">
                <label style="display:block;margin-bottom:6px;font-weight:500;color:#333;">Address</label>
                <textarea id="supplier_address" name="address" class="form-control" rows="3">${supplier ? escapeHtml(supplier.address) : ''}</textarea>
            </div>
            
            <div class="form-group" style="margin-bottom:16px;">
                <label style="display:block;margin-bottom:6px;font-weight:500;color:#333;">Tax ID</label>
                <input type="text" id="supplier_tax" name="tax_number" class="form-control"
                       value="${supplier ? escapeHtml(supplier.tax_number) : ''}">
            </div>
            
            <div class="form-group" style="margin-bottom:16px;">
                <label style="display:block;margin-bottom:6px;font-weight:500;color:#333;">Status</label>
                <select id="supplier_status" name="status" class="form-control" required>
                    <option value="active" ${supplier && supplier.status === 'active' ? 'selected' : ''}>Active</option>
                    <option value="inactive" ${supplier && supplier.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                </select>
            </div>
        </form>
    `;
    
    // 显示弹窗
    document.getElementById('supplierModalTitle').textContent = title;
    document.getElementById('supplierModalContent').innerHTML = formHtml;
    document.getElementById('supplierModal').style.display = 'flex';
}


// 邮箱验证函数
function isValidEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

// 保存供应商信息（AJAX版本）- 增强验证
function saveSupplier() {
    const form = document.getElementById('supplierForm');
    if (!form) return;
    
    // 基础验证
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.style.borderColor = '#dc3545';
        } else {
            field.style.borderColor = '';
        }
    });
    
    if (!isValid) {
        alert('Please fill in all required fields (*).');
        return;
    }
    
    // 手机号验证
    const phoneField = document.getElementById('supplier_phone');
    const phoneRegex = /^1[3-9]\d{9}$/;
    if (phoneField.value && !phoneRegex.test(phoneField.value)) {
        alert('Please enter a valid phone number.');
        phoneField.style.borderColor = '#dc3545';
        phoneField.focus();
        return;
    }
    
    // 邮箱验证
    const emailField = document.getElementById('supplier_email');
    if (emailField.value && !isValidEmail(emailField.value)) {
        alert('Please enter a valid email address.');
        emailField.style.borderColor = '#dc3545';
        emailField.focus();
        return;
    }
    
    // 收集表单数据
    const formData = new FormData();
    formData.append('action', 'save_supplier');
    formData.append('supplier_id', currentEditSupplierId || '');
    formData.append('name', document.getElementById('supplier_name').value.trim());
    formData.append('category', document.getElementById('supplier_category').value);
    formData.append('contact_person', document.getElementById('supplier_contact').value.trim());
    formData.append('phone', document.getElementById('supplier_phone').value.trim());
    formData.append('email', document.getElementById('supplier_email').value.trim());
    formData.append('address', document.getElementById('supplier_address').value.trim());
    formData.append('tax_number', document.getElementById('supplier_tax').value.trim());
    formData.append('status', document.getElementById('supplier_status').value);
    
    // 发送AJAX请求
    fetch('supplier_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // 重新加载页面以获取最新数据
            window.location.reload();
        } else {
            alert('Save failed: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Failed to save supplier:', error);
        alert('Save failed. Please check your network connection.');
    });
    
    closeSupplierModal();
}



// 高级搜索过滤
function applyFilters() {
    const searchQuery = (document.getElementById('supplierSearch').value || '').trim().toLowerCase();
    const categoryFilter = (document.getElementById('supplierCategoryFilter').value || '').trim();
    const statusFilter = (document.getElementById('supplierStatusFilter').value || '').trim();
    
    const rows = document.querySelectorAll('#suppliersTbody tr');
    rows.forEach(row => {
        if (row.cells.length < 2) return; // 跳过空行提示
        
        const text = (row.textContent || '').toLowerCase();
        const rowCategory = row.dataset.category || '';
        const rowStatus = row.dataset.status || '';
        
        // 应用所有过滤器
        const matchSearch = !searchQuery || text.includes(searchQuery);
        const matchCategory = !categoryFilter || rowCategory === categoryFilter;
        const matchStatus = !statusFilter || rowStatus === statusFilter;
        
        row.style.display = (matchSearch && matchCategory && matchStatus) ? '' : 'none';
    });
}

// 事件监听
document.addEventListener('DOMContentLoaded', function() {
    // 初始渲染表格
    renderSuppliersTable();
    
    // 查看、编辑、删除按钮事件委托
    document.getElementById('suppliersTbody').addEventListener('click', function(e) {
        const btn = e.target.closest('.btn');
        if (!btn) return;
        const tr = btn.closest('tr');
        if (!tr) return;
        const id = tr.dataset.id;
        
        if (btn.classList.contains('btn-view')) {
            showSupplierDetails(id);
        }
        else if (btn.classList.contains('btn-edit')) {
            editSupplier(id);
        }
        else if (btn.classList.contains('btn-delete')) {
            deleteSupplier(id);
        }
    });
    
    // 新增供应商按钮
    document.getElementById('supplierAddBtn').addEventListener('click', function() {
        editSupplier(null);
    });
    
    // 搜索功能
    document.getElementById('supplierSearchBtn').addEventListener('click', applyFilters);
    document.getElementById('supplierSearch').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') applyFilters();
    });
    document.getElementById('supplierCategoryFilter').addEventListener('change', applyFilters);
    document.getElementById('supplierStatusFilter').addEventListener('change', applyFilters);
    
    // 供应商弹窗事件
    document.getElementById('closeSupplierModal').addEventListener('click', closeSupplierModal);
    document.getElementById('cancelSupplierBtn').addEventListener('click', closeSupplierModal);
    document.getElementById('saveSupplierBtn').addEventListener('click', saveSupplier);
    
    // 删除确认弹窗事件
    document.getElementById('cancelDeleteBtn').addEventListener('click', closeDeleteConfirmModal);
    document.getElementById('confirmDeleteBtn').addEventListener('click', confirmDeleteSupplier);
    
    // 详情弹窗事件（新增的优化功能）
    document.getElementById('closeDetailModal').addEventListener('click', closeDetailModal);
    document.getElementById('closeDetailBtn').addEventListener('click', closeDetailModal);
    
    // 产品弹窗事件（新增的优化功能）
    document.getElementById('closeProductsModal').addEventListener('click', closeProductsModal);
    document.getElementById('closeProductsBtn').addEventListener('click', closeProductsModal);
    
    // 点击弹窗背景关闭
    const modals = ['supplierModal', 'deleteConfirmModal', 'supplierDetailModal', 'supplierProductsModal'];
    modals.forEach(modalId => {
        document.getElementById(modalId).addEventListener('click', function(e) {
            if (e.target === this) {
                if (modalId === 'supplierModal') closeSupplierModal();
                else if (modalId === 'deleteConfirmModal') closeDeleteConfirmModal();
                else if (modalId === 'supplierDetailModal') closeDetailModal();
                else if (modalId === 'supplierProductsModal') closeProductsModal();
            }
        });
    });
});

// 关闭供应商弹窗
function closeSupplierModal() {
    document.getElementById('supplierModal').style.display = 'none';
    currentEditSupplierId = null;
    document.getElementById('supplierModalContent').innerHTML = '';
}

// 关闭删除确认弹窗
function closeDeleteConfirmModal() {
    document.getElementById('deleteConfirmModal').style.display = 'none';
    currentDeleteSupplierId = null;
}

// 关闭详情弹窗（新增的优化功能）
function closeDetailModal() {
    document.getElementById('supplierDetailModal').style.display = 'none';
    document.getElementById('supplierDetailContent').innerHTML = '';
}

// 关闭产品弹窗（新增的优化功能）
function closeProductsModal() {
    document.getElementById('supplierProductsModal').style.display = 'none';
    document.getElementById('supplierProductsContent').innerHTML = '';
}

// 初始过滤
setTimeout(applyFilters, 100);
</script>

<style>
.category-tag {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    display: inline-block;
    border: 1px solid;
}
.category-veg {
    background-color: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}
.category-meat {
    background-color: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}
.category-seafood {
    background-color: #cce5ff;
    color: #004085;
    border-color: #b8daff;
}
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
.status-rejected {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}
.form-control:focus {
    border-color: #1976d2;
    outline: none;
    box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.1);
}
.action-buttons {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: nowrap;
}
</style>
