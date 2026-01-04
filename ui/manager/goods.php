<?php
// goods.php
require_once __DIR__ . '/inc/db_connect.php';      // 数据库连接
require_once __DIR__ . '/inc/data.php';    // 数据逻辑
require_once __DIR__ . '/inc/header.php';  // 页头

$categories=getProductCategories();
$categoryHierarchy = buildCategoryTree(); // 获取分类树结构
$suppliers = getSuppliersFromDB(); 
$inventoryByBranch = getManagerProductInventoryByBranch();
$productSupplierPricing = getManagerProductSupplierPricing();
?>
<style>
    .product-list {
        margin-top: 10px;
    }
    .product-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        padding: 18px 22px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 18px;
        transition: box-shadow 0.2s, transform 0.2s;
    }
    .product-card:hover {
        box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        transform: translateY(-2px);
    }
    .product-card-actions {
        margin-left: auto;
        display: flex;
        gap: 10px;
    }
    .manager-modal-mask {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.25);
        z-index: 999;
    }
    .manager-modal {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        min-width: 420px;
        max-width: 90vw;
        max-height: 80vh;
        overflow: auto;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 28px rgba(0,0,0,0.2);
        padding: 24px;
        z-index: 1000;
    }
    .manager-modal h3 {
        margin-bottom: 12px;
        color: #ff7043;
    }
    .manager-modal table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    .manager-modal th,
    .manager-modal td {
        padding: 10px 12px;
        border-bottom: 1px solid #f0f0f0;
        text-align: left;
        font-size: 14px;
    }
    .manager-modal th {
        background: #fff8e1;
        color: #ff7043;
    }
`;
</style>
<section class="section">
    <h2 class="section-title">Products</h2>
    <div style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">
        <input id="productSearchInput" class="filter-input" placeholder="Search product name / ID">
        <button id="productSearchBtn" class="btn btn-primary">Search</button>
        <button id="productAddBtn" class="btn btn-success" style="margin-left:auto;">Add product</button>
    </div>

    <div id="productList" class="product-list"></div>
</section>
<div id="managerModalMask" class="manager-modal-mask" onclick="closeManagerModal()"></div>
<div id="managerModal" class="manager-modal"></div>

<!-- 货物流水管理（只读流水表） -->
<section class="section" style="margin-top:20px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
        <h2 class="section-title" style="margin:0;">Inventory Transactions</h2>
        <div style="margin-left:auto;display:flex;gap:8px;align-items:center;">
            <input id="txSearchInput" class="filter-input" placeholder="Search product name / batch / notes">
            <select id="txTypeSelect" class="filter-select">
                <option value="">All types</option>
                <option value="sale">Sale</option>
                <option value="adjustment">Adjustment</option>
                <option value="purchase">Purchase</option>
            </select>
            <button id="txSearchBtn" class="btn btn-primary">Filter</button>
        </div>
    </div>
    <div style="overflow:auto;max-height:420px;">
        <table class="data-table" style="min-width:1100px;">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Product</th>
                    <th>Batch</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Type</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody id="transactionsBody"></tbody>
        </table>
    </div>
</section>

<!-- 产品编辑弹窗（默认隐藏） -->
<div id="productEditModal" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z:999;display:none;">
    <div style="background:#fff;width:600px;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.15);overflow:hidden;">
        <div style="padding:16px;border-bottom:1px solid #eee;display:flex;align-items:center;justify-content:space-between;">
            <h3 style="margin:0;font-size:18px;font-weight:600;" id="editModalTitle">Edit Product</h3>
            <button id="closeEditModal" style="background:transparent;border:none;font-size:20px;cursor:pointer;color:#666;">&times;</button>
        </div>
        <div style="padding:20px;" id="editModalContent">
            <!-- 编辑表单将通过JS动态生成 -->
        </div>
        <div style="padding:12px 20px;border-top:1px solid #eee;display:flex;justify-content:flex-end;gap:10px;">
            <button id="cancelEditBtn" class="btn btn-default">Cancel</button>
            <button id="saveEditBtn" class="btn btn-primary">Save</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

<script>
// 全局数据（从PHP传递过来）
let products = <?= json_encode($products, JSON_UNESCAPED_UNICODE) ?>;
let transactions = <?= json_encode($transactions, JSON_UNESCAPED_UNICODE) ?>;
const partnersList = <?= json_encode($partners, JSON_UNESCAPED_UNICODE) ?>;
const categoriesData = <?= json_encode($categories, JSON_UNESCAPED_UNICODE) ?>;
const suppliersData = <?= json_encode($suppliers, JSON_UNESCAPED_UNICODE) ?>;
const inventoryByBranch = <?= json_encode($inventoryByBranch, JSON_UNESCAPED_UNICODE) ?>;
const supplierPricing = <?= json_encode($productSupplierPricing, JSON_UNESCAPED_UNICODE) ?>;

const categoryHierarchy = <?= json_encode($categoryHierarchy, JSON_UNESCAPED_UNICODE) ?>;

// 当前编辑的产品ID
let currentEditProductId = null;



// 从分类ID查找其第二级父类
function findSecondLevelParent(categoryId) {
    const id = parseInt(categoryId);
    
    // 遍历第一级（生鲜）
    for (const [topId, topCategory] of Object.entries(categoryHierarchy)) {
        // 遍历第二级（果蔬、肉禽蛋、水产）
        for (const [secondId, secondCategory] of Object.entries(topCategory.children)) {
            // 检查是否是第三级
            if (secondCategory.children && secondCategory.children[id]) {
                return {
                    secondLevelId: parseInt(secondId),
                    secondLevelName: secondCategory.name,
                    thirdLevelName: secondCategory.children[id]
                };
            }
        }
    }
    return null;
}

// 获取某个第二级分类下的所有第三级子类
function getThirdLevelCategories(secondLevelId) {
    const id = parseInt(secondLevelId);
    const result = [];
    
    // 遍历第一级
    for (const [topId, topCategory] of Object.entries(categoryHierarchy)) {
        // 遍历第二级
        for (const [secondId, secondCategory] of Object.entries(topCategory.children)) {
            if (parseInt(secondId) === id && secondCategory.children) {
                // 获取该第二级下的所有第三级
                for (const [thirdId, thirdName] of Object.entries(secondCategory.children)) {
                    result.push({
                        id: parseInt(thirdId),
                        name: thirdName
                    });
                }
                return result;
            }
        }
    }
    return [];
}

// 根据供应商的category字段（第二级名称）找到对应的第二级ID
function findSecondLevelIdByCategoryName(categoryName) {
    for (const [topId, topCategory] of Object.entries(categoryHierarchy)) {
        for (const [secondId, secondCategory] of Object.entries(topCategory.children)) {
            if (secondCategory.name === categoryName) {
                return parseInt(secondId);
            }
        }
    }
    return null;
}

// HTML转义函数
function escapeHtml(s) {
    return (s === null || s === undefined) ? '' : String(s).replace(/[&<>"]/g, c => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;'
    }[c]));
}

// 日期格式化函数
function formatDate(ts) {
    if (!ts) return '';
    const d = new Date(ts);
    if (isNaN(d.getTime())) return ts;
    return d.toISOString().replace('T', ' ').slice(0, 19);
}

// 渲染产品列表（进货新商品样式）
function renderProductList(filteredProducts) {
    const wrap = document.getElementById('productList');
    if (!wrap) return;
    wrap.innerHTML = '';

    if (filteredProducts.length === 0) {
        wrap.innerHTML = '<div style="color:#666;padding:24px;text-align:center;">No matching products</div>';
        return;
    }

    filteredProducts.forEach(product => {
        const card = document.createElement('div');
        card.className = 'product-card';
        const desc = product.description || 'No description';
        card.innerHTML = `
            <div style="flex:1;">
                <div style="font-size:16px;font-weight:600;">${escapeHtml(product.name || '')} <span style="color:#888;font-size:13px;">(${escapeHtml(product.sku || product.id || '')})</span></div>
                <div style="font-size:13px;color:#888;margin-top:6px;">${escapeHtml(desc)}</div>
            </div>
            <div class="product-card-actions">
                <button class="btn btn-primary" onclick="openInventoryModal(${product.id})">Inventory</button>
                <button class="btn btn-warning" onclick="openSupplierPricingModal(${product.id})">Supplier Pricing</button>
            </div>
        `;
        wrap.appendChild(card);
    });
}

// 渲染只读流水表
function renderTransactions() {
    const tb = document.getElementById('transactionsBody');
    if (!tb) return;
    tb.innerHTML = '';

    const searchQuery = (document.getElementById('txSearchInput') || {}).value.trim().toLowerCase();
    const typeFilter = (document.getElementById('txTypeSelect') || {}).value;

    // 筛选流水数据
    let filteredTx = transactions;
    
    if (typeFilter) {
        // 使用中文类型映射
        const typeMap = {
            'sale': 'Sale',
            'adjustment': 'Adjustment', 
            'purchase': 'Purchase'
        };
        
        // 根据显示的中文类型筛选
        filteredTx = filteredTx.filter(tx => {
            const txType = resolveTransactionLabel(tx);
            return txType === typeMap[typeFilter];
        });
    }
    
    if (searchQuery) {
        filteredTx = filteredTx.filter(tx => {
            const searchStr = `${tx.productName || ''} ${tx.productId || ''} ${tx.batchId || ''} ${tx.note || ''}`.toLowerCase();
            return searchStr.includes(searchQuery);
        });
    }

    if (filteredTx.length === 0) {
        tb.innerHTML = '<tr><td colspan="9" style="color:#666;padding:12px;text-align:center;">No transaction records</td></tr>';
        return;
    }
    // 渲染流水行
    filteredTx.forEach(tx => {
        const product = products.find(p => p.id === tx.productId) || {};

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${escapeHtml(formatDate(tx.time))}</td>
            <td>${escapeHtml(tx.productName || product.name || tx.productId || '')}</td>
            <td>${escapeHtml(tx.batchId || '')}</td>
            <td>${escapeHtml(String(tx.qty || 0))}</td>
            <td>${escapeHtml(tx.unit || product.unit || '')}</td>
            <td>${escapeHtml(resolveTransactionLabel(tx))}</td>
            <td>${escapeHtml(tx.source || '')}</td>
            <td>${escapeHtml(tx.destination || '')}</td>
            <td>${escapeHtml(tx.note || '')}</td>
        `;
        tb.appendChild(row);
    });
}

function resolveTransactionLabel(tx) {
    const raw = (tx.txn_type || '').toLowerCase();
    if (raw === 'purchase') return 'Purchase';
    if (raw === 'sale') return 'Sale';
    if (raw === 'return' || raw === 'transfer' || raw === 'adjustment') return 'Adjustment';
    return tx.type === 'in' ? 'Purchase' : (tx.type === 'out' ? 'Sale' : 'Adjustment');
}

function openInventoryModal(productId) {
    const modal = document.getElementById('managerModal');
    const mask = document.getElementById('managerModalMask');
    const product = products.find(p => p.id === productId) || {};
    const rows = inventoryByBranch.filter(r => Number(r.product_ID) === Number(productId));
    let total = 0;
    let bodyHtml = '';

    if (rows.length === 0) {
        bodyHtml = '<div style="color:#888;padding:16px 0;">No inventory data</div>';
    } else {
        const rowHtml = rows.map(r => {
            const qty = Number(r.total_stock) || 0;
            total += qty;
            return `<tr><td>${escapeHtml(r.branch_name)}</td><td>${qty}</td></tr>`;
        }).join('');
        bodyHtml = `
            <table>
                <thead>
                    <tr><th>Branch</th><th>Stock</th></tr>
                </thead>
                <tbody>
                    ${rowHtml}
                    <tr>
                        <td><b>Company total</b></td>
                        <td><b>${total}</b></td>
                    </tr>
                </tbody>
            </table>
        `;
    }

    modal.innerHTML = `
        <h3>Inventory - ${escapeHtml(product.name || '')}</h3>
        ${bodyHtml}
        <div style="text-align:right;margin-top:16px;">
            <button class="btn btn-warning" onclick="closeManagerModal()">Close</button>
        </div>
    `;
    modal.style.display = 'block';
    mask.style.display = 'block';
}

function openSupplierPricingModal(productId) {
    const modal = document.getElementById('managerModal');
    const mask = document.getElementById('managerModalMask');
    const product = products.find(p => p.id === productId) || {};
    const rows = supplierPricing.filter(r => Number(r.product_ID) === Number(productId));

    let bodyHtml = '';
    if (rows.length === 0) {
        bodyHtml = '<div style="color:#888;padding:16px 0;">No supplier data</div>';
    } else {
        const rowHtml = rows.map(r => `
            <tr id="pricing-row-${r.product_ID}-${r.supplier_ID}">
                <td>${escapeHtml(r.supplier_name)}</td>
                <td>¥${Number(r.unit_cost).toFixed(2)}</td>
                <td class="pricing-value" data-value="${Number(r.selling_price).toFixed(2)}">¥${Number(r.selling_price).toFixed(2)}</td>
                <td class="pricing-action">
                    <button class="btn btn-primary" onclick="editSellingPrice(${r.product_ID}, ${r.supplier_ID})">Edit</button>
                </td>
            </tr>
        `).join('');

        bodyHtml = `
            <table>
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Cost</th>
                        <th>Selling Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>${rowHtml}</tbody>
            </table>
        `;
    }

    modal.innerHTML = `
        <h3>Supplier Pricing - ${escapeHtml(product.name || '')}</h3>
        ${bodyHtml}
        <div style="text-align:right;margin-top:16px;">
            <button class="btn btn-warning" onclick="closeManagerModal()">Close</button>
        </div>
    `;
    modal.style.display = 'block';
    mask.style.display = 'block';
}

function editSellingPrice(productId, supplierId) {
    const row = document.getElementById(`pricing-row-${productId}-${supplierId}`);
    if (!row) return;
    const price = row.querySelector('.pricing-value')?.dataset.value || '0.00';
    const valueCell = row.querySelector('.pricing-value');
    const actionCell = row.querySelector('.pricing-action');
    valueCell.innerHTML = `<input type="number" step="0.01" id="selling-input-${productId}" value="${price}" style="width:120px;">`;
    actionCell.innerHTML = `
        <button class="btn btn-success" onclick="saveSellingPrice(${productId})">Save</button>
        <button class="btn btn-warning" style="margin-left:6px;" onclick="openSupplierPricingModal(${productId})">Cancel</button>
    `;
}

function saveSellingPrice(productId) {
    const input = document.getElementById(`selling-input-${productId}`);
    const newPrice = input ? parseFloat(input.value) : NaN;
    if (!Number.isFinite(newPrice) || newPrice <= 0) {
        alert('Please enter a valid selling price.');
        return;
    }
    const payload = new URLSearchParams({
        action: 'update_selling_price',
        product_id: productId,
        selling_price: newPrice.toFixed(2)
    });

    fetch('product_pricing_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: payload.toString()
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            alert(data.message || 'Update failed.');
            return;
        }
        supplierPricing.forEach(r => {
            if (Number(r.product_ID) === Number(productId)) {
                r.selling_price = Number(newPrice).toFixed(2);
            }
        });
        openSupplierPricingModal(productId);
    })
    .catch(() => {
        alert('Update failed. Please try again later.');
    });
}

function closeManagerModal() {
    const modal = document.getElementById('managerModal');
    const mask = document.getElementById('managerModalMask');
    if (modal) modal.style.display = 'none';
    if (mask) mask.style.display = 'none';
}

// 填充所有第三级分类到下拉列表
function populateAllThirdLevelCategories(selectElement) {
    const categories = getAllThirdLevelCategories();
    updateCategorySelect(categories, selectElement); // 使用复用函数
}

// 产品筛选逻辑
function applyProductFilter() {
    const searchQuery = (document.getElementById('productSearchInput') || {}).value.trim().toLowerCase();
    const filteredProducts = products.filter(product => {
        // 搜索筛选（产品ID、名称、描述、规格）
        const searchStr = `${product.id || ''} ${product.name || ''} ${product.description || ''} ${product.spec || ''} ${product.sku || ''}`.toLowerCase();
        const matchSearch = !searchQuery || searchStr.includes(searchQuery);

        return matchSearch;
    });

    renderProductList(filteredProducts);
}
function getAllThirdLevelCategories() {
    const result = [];
    for (const [topId, topCategory] of Object.entries(categoryHierarchy)) {
        for (const [secondId, secondCategory] of Object.entries(topCategory.children)) {
            if (secondCategory.children) {
                for (const [thirdId, thirdName] of Object.entries(secondCategory.children)) {
                    result.push({
                        id: parseInt(thirdId),
                        name: thirdName
                    });
                }
            }
        }
    }
    return result;
}
// 绑定图片上传预览
function bindImageUploadPreview() {
    const uploadInput = document.getElementById('imageUpload');
    const previewImage = document.getElementById('previewImage');
    const imagePlaceholder = document.getElementById('imagePlaceholder');

    uploadInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // 验证图片格式
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please upload an image file (JPG, PNG, GIF, WebP).');
            return;
        }

        // 预览图片
        const reader = new FileReader();
        reader.onload = function(event) {
            previewImage.src = event.target.result;
            previewImage.style.display = 'block';
            imagePlaceholder.style.display = 'none';
        };
        reader.readAsDataURL(file);
    });
}
function showNotification(type, message, duration = 3000, callback) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-icon">${type === 'success' ? '✓' : type === 'error' ? '✗' : '!'}</div>
        <div class="notification-content">${message}</div>
        <button class="notification-close">&times;</button>
    `;
    
    document.body.appendChild(notification);
    
    // 自动移除
    const timer = setTimeout(() => {
        notification.remove();
        if (callback) callback();
    }, duration);
    
    // 点击关闭
    notification.querySelector('.notification-close').addEventListener('click', () => {
        clearTimeout(timer);
        notification.remove();
        if (callback) callback();
    });
}
function getThirdLevelCategoriesForEdit() {
    const categories = getAllThirdLevelCategories();
    const currentProduct = products.find(p => p.id === currentEditProductId);
    const currentCategoryId = currentProduct ? currentProduct.category_id : '';
    
    return categories.map(cat => 
        `<option value="${escapeHtml(cat.id)}" ${currentCategoryId == cat.id ? 'selected' : ''}>
            ${escapeHtml(cat.name)}
        </option>`
    ).join('');
}

function populateAllSuppliers(selectElement) {
    const currentValue = selectElement.value;
    const originalOptions = Array.from(selectElement.options);
    const defaultOption = originalOptions[0]; // 保留空选项
    
    selectElement.innerHTML = '';
    selectElement.appendChild(defaultOption);
    
    suppliersData.forEach(supplier => {
        const option = document.createElement('option');
        option.value = supplier.id;
        option.textContent = `${supplier.name} (${supplier.category || ''})`;
        selectElement.appendChild(option);
    });
    
    if (currentValue) {
        selectElement.value = currentValue;
    }
}

// 编辑表单：根据选择的分类筛选供应商
function filterSuppliersBySelectedCategoryForEdit() {
    const categoryId = document.getElementById('editProductCategory')?.value;
    const supplierSelect = document.getElementById('editProductSupplier');
    
    if (!supplierSelect) return;
    
    if (!categoryId) {
        populateAllSuppliersForEdit(supplierSelect);
        return;
    }
    
    const parentInfo = findSecondLevelParent(categoryId);
    if (!parentInfo) {
        populateAllSuppliersForEdit(supplierSelect);
        return;
    }
    
    const secondLevelName = parentInfo.secondLevelName;
    const filteredSuppliers = suppliersData.filter(supplier => 
        supplier.category === secondLevelName
    );
    
    updateSupplierSelectForEdit(filteredSuppliers, supplierSelect);
}
function updateSupplierSelect(suppliers, selectElement) {
    const currentValue = selectElement.value;
    const originalOptions = Array.from(selectElement.options);
    const defaultOption = originalOptions[0]; // 保留空选项
    
    selectElement.innerHTML = '';
    selectElement.appendChild(defaultOption);
    
    suppliers.forEach(supplier => {
        const option = document.createElement('option');
        option.value = supplier.id;
        option.textContent = `${supplier.name} (${supplier.category || ''})`;
        selectElement.appendChild(option);
    });
    
    // 如果当前选择的供应商不符合筛选条件，清空选择
    if (currentValue) {
        const selectedSupplier = suppliersData.find(s => s.id == currentValue);
        if (selectedSupplier && !suppliers.find(s => s.id == currentValue)) {
            selectElement.value = '';
        } else {
            selectElement.value = currentValue;
        }
    }
}
// 编辑表单：根据选择的供应商筛选分类
function filterCategoriesBySelectedSupplierForEdit() {
    const supplierId = document.getElementById('editProductSupplier')?.value;
    const categorySelect = document.getElementById('editProductCategory');
    
    if (!categorySelect) return;
    
    if (!supplierId) {
        populateAllThirdLevelCategoriesForEdit(categorySelect);
        return;
    }
    
    const selectedSupplier = suppliersData.find(s => s.id == supplierId);
    if (!selectedSupplier || !selectedSupplier.category) {
        populateAllThirdLevelCategoriesForEdit(categorySelect);
        return;
    }
    
    const secondLevelId = findSecondLevelIdByCategoryName(selectedSupplier.category);
    if (!secondLevelId) {
        populateAllThirdLevelCategoriesForEdit(categorySelect);
        return;
    }
    
    const availableCategories = getThirdLevelCategories(secondLevelId);
    updateCategorySelectForEdit(availableCategories, categorySelect);
}

// 编辑表单：填充所有供应商
function populateAllSuppliersForEdit(selectElement) {
    const currentValue = selectElement.value;
    const originalOptions = Array.from(selectElement.options);
    const defaultOption = originalOptions[0];
    
    selectElement.innerHTML = '';
    selectElement.appendChild(defaultOption);
    
    suppliersData.forEach(supplier => {
        const option = document.createElement('option');
        option.value = supplier.id;
        option.textContent = `${supplier.name} (${supplier.category || ''})`;
        selectElement.appendChild(option);
    });
    
    if (currentValue) {
        selectElement.value = currentValue;
    }
}

// 编辑表单：填充所有第三级分类
function populateAllThirdLevelCategoriesForEdit(selectElement) {
    const categories = getAllThirdLevelCategories();
    const currentValue = selectElement.value;
    
    const originalOptions = Array.from(selectElement.options);
    const defaultOption = originalOptions[0];
    
    selectElement.innerHTML = '';
    selectElement.appendChild(defaultOption);
    
    categories.forEach(category => {
        const option = document.createElement('option');
        option.value = category.id;
        option.textContent = category.name;
        selectElement.appendChild(option);
    });
    
    if (currentValue) {
        selectElement.value = currentValue;
    }
}

// 编辑表单：更新供应商选择
function updateSupplierSelectForEdit(suppliers, selectElement) {
    const currentValue = selectElement.value;
    const originalOptions = Array.from(selectElement.options);
    const defaultOption = originalOptions[0];
    
    selectElement.innerHTML = '';
    selectElement.appendChild(defaultOption);
    
    suppliers.forEach(supplier => {
        const option = document.createElement('option');
        option.value = supplier.id;
        option.textContent = `${supplier.name} (${supplier.category || ''})`;
        selectElement.appendChild(option);
    });
    
    if (currentValue) {
        const selectedSupplier = suppliersData.find(s => s.id == currentValue);
        if (selectedSupplier && !suppliers.find(s => s.id == currentValue)) {
            selectElement.value = '';
        } else {
            selectElement.value = currentValue;
        }
    }
}


function updateCategorySelectForEdit(categories, selectElement) {
    const currentValue = selectElement.value;
    const originalOptions = Array.from(selectElement.options);
    const defaultOption = originalOptions[0];
    
    selectElement.innerHTML = '';
    selectElement.appendChild(defaultOption);
    
    categories.forEach(category => {
        const option = document.createElement('option');
        option.value = category.id;
        option.textContent = category.name;
        selectElement.appendChild(option);
    });
    
    if (currentValue) {
        const parentInfo = findSecondLevelParent(currentValue);
        if (parentInfo && !categories.find(c => c.id == currentValue)) {
            selectElement.value = '';
        } else {
            selectElement.value = currentValue;
        }
    }
}
// 保存编辑内容
function saveEditProduct() {
    if (!currentEditProductId) return;

    const productIndex = products.findIndex(p => p.id === currentEditProductId);
    if (productIndex === -1) return;

    // 获取表单值
    const productName = document.getElementById('editProductName').value.trim();
    const productUnit = document.getElementById('editProductUnit').value.trim();

    // 基础校验
    if (!productName) {
        alert('Product name is required.');
        document.getElementById('editProductName').focus();
        return;
    }
    if (!productUnit) {
        alert('Unit is required.');
        document.getElementById('editProductUnit').focus();
        return;
    }

    // 更新产品数据（移除statusText和spec的更新）
    const updatedProduct = {
        ...products[productIndex],
        name: productName,
        description: document.getElementById('editProductDesc').value.trim(),
        // spec: document.getElementById('editProductSpec').value.trim(), // 移除这行
        unit: productUnit,
        // statusText: document.getElementById('editProductStatus').value, // 移除这行
        price: document.getElementById('editProductPrice').value ? `¥${Number(document.getElementById('editProductPrice').value).toFixed(2)}` : '',
        supplier: document.getElementById('editProductSupplier').value,
        // 图片：如果有预览图且不是原始图片，更新为base64
        image: document.getElementById('previewImage').style.display === 'block' 
            ? document.getElementById('previewImage').src 
            : products[productIndex].image
    };

    // 更新全局数据
    products[productIndex] = updatedProduct;

    // 重新渲染卡片和流水表
    applyProductFilter();
    renderTransactions();

    // 关闭弹窗
    closeEditModal();

    // 提示保存成功
    alert('Product updated successfully.');
}

// 关闭编辑弹窗
function closeEditModal() {
    document.getElementById('productEditModal').style.display = 'none';
    currentEditProductId = null;
    // 清空表单内容（避免缓存）
    document.getElementById('editModalContent').innerHTML = '';
}

// 绑定编辑弹窗相关事件
function bindEditModalEvents() {
    // 关闭弹窗（右上角X）
    document.getElementById('closeEditModal').addEventListener('click', closeEditModal);

    // 取消编辑
    document.getElementById('cancelEditBtn').addEventListener('click', closeEditModal);

    // 保存修改
    document.getElementById('saveEditBtn').addEventListener('click', saveEditProduct);

    // 点击弹窗背景关闭
    document.getElementById('productEditModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });

    // 新增产品按钮
document.getElementById('productAddBtn').addEventListener('click', async function() {
    const categories = <?= json_encode(getProductCategories(), JSON_UNESCAPED_UNICODE) ?>;
    
    const supplierOptions = suppliersData.map(s => 
        `<option value="${escapeHtml(s.id)}">${escapeHtml(s.name)} (${escapeHtml(s.category || '')})</option>`
    ).join('');
    
    const categoryOptions = categories.map(cat => 
        `<option value="${escapeHtml(cat.category_id)}">${escapeHtml(cat.category_name)}</option>`
    ).join('');

const html = `
    <form id="addProductForm" style="display:flex;gap:12px;">
        <div style="width:160px;height:140px;background:#f5f5f5;border:1px dashed #ddd;display:flex;align-items:center;justify-content:center;color:#999;">Image placeholder</div>
        <div style="flex:1;">
            <div class="form-group">
                <label class="form-label">Product Name <span style="color:red;">*</span></label>
                <input type="text" name="name" id="pf_name" class="form-control" required>
            </div>
            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Unit Price</label>
                    <input type="number" step="0.01" name="price" id="pf_price" class="form-control" value="0">
                </div>
                <div class="form-col">
                  <label class="form-label">Unit <span style="color:red;">*</span></label>
                  <select name="unit" id="pf_unit" class="form-control" required>
                     <option value="">Select unit</option>
                     <option value="g">g</option>
                     <option value="kg">kg</option>
                     <option value="只">piece</option>
                     <option value="个">item</option>
                     <option value="枚">piece</option>
                     <option value="斤">jin</option>
                     <option value="公斤">kilogram</option>
                     <option value="盒">box</option>
                     <option value="包">pack</option>
                     <option value="袋">bag</option>
                     <option value="份" selected>serving</option>
                    </select>
                </div>

          <div class="form-col">
               <label class="form-label">Sales Qty <span style="color:red;">*</span></label>
               <input type="number" name="sale_quantity" id="pf_sale_quantity" 
                 class="form-control" min="1" required value="1">
          </div>
             </div>
            <div class="form-group">
                <label class="form-label">Category <span style="color:red;">*</span></label>
                <select name="category_id" id="pf_category" class="form-control" required onchange="filterSuppliersByCategory()">
                    <option value="">-- Select --</option>
                    ${categoryOptions}
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Supplier</label>
                <select name="supplier" id="pf_supplier" class="form-control" onchange="filterCategoriesBySupplier()">
                    <option value="">(Not linked)</option>
                    ${supplierOptions}
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" id="pf_desc" class="form-control" rows="3"></textarea>
            </div>
        </div>
    </form>
`;

    if (typeof showAppModal === 'function') {
        const ok = await showAppModal('Add Product', html, { showCancel: true, okText: 'Add' });
        if (!ok) return;

        const form = document.getElementById('addProductForm');
        const formData = new FormData(form);
        formData.append('action', 'add_product');
        formData.append('sale_quantity', document.getElementById('pf_sale_quantity').value);
        formData.append('unit', document.getElementById('pf_unit').value);
        
        try {
            const response = await fetch('add_goods.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
           if (result.success) {
             showNotification('success', 
              result.message + '<br><small>SKU: ' + result.sku + '</small>',
              3000, // 3秒后自动关闭
              function() {
                location.reload(); // 刷新页面
             }
           );
          } else {
            showNotification('error', result.error || 'Add failed.', 5000);
          }
        } catch (error) {
            showNotification('error', 'Network error. Please try again.', 2000);
        }
    }
});
}

// 绑定所有事件
function bindAllEvents() {
    // 产品搜索事件
    document.getElementById('productSearchBtn').addEventListener('click', applyProductFilter);
    document.getElementById('productSearchInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') applyProductFilter();
    });

    // 流水搜索事件
    document.getElementById('txSearchBtn').addEventListener('click', renderTransactions);
    document.getElementById('txSearchInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') renderTransactions();
    });
    document.getElementById('txTypeSelect').addEventListener('change', renderTransactions);

    // 编辑弹窗事件
    bindEditModalEvents();
}
// 全局变量
let allSuppliers = [];
let allCategories = [];

// 初始化供应商数据
function initSupplierData() {
    // 使用专门的供应商数据而不是 partnersList
    allSuppliers = suppliersData.map(supplier => ({
        id: supplier.id,
        name: supplier.name,
        category: supplier.category || '' // 确保有这个字段
    }));
    
    // 从PHP传入的数据中提取分类信息
    allCategories = categoriesData.map(cat => ({
        id: cat.category_id,
        name: cat.category_name
    }));
    
    console.log('Initialized supplier data:', allSuppliers); // Debug
    console.log('Initialized category data:', allCategories); // Debug
}

// 根据产品类型筛选供应商
// 替换右边的 filterSuppliersByCategory 函数
function filterSuppliersByCategory() {
    const categoryId = document.getElementById('pf_category')?.value;
    const supplierSelect = document.getElementById('pf_supplier');
    
    if (!supplierSelect) return;
    
    if (!categoryId) {
        // 显示所有供应商
        populateAllSuppliers(supplierSelect);
        return;
    }
    
    // 找到第二级父类
    const parentInfo = findSecondLevelParent(categoryId);
    if (!parentInfo) {
        populateAllSuppliers(supplierSelect);
        return;
    }
    
    const secondLevelName = parentInfo.secondLevelName;
    
    // 筛选能提供该第二级品类的供应商
    const filteredSuppliers = suppliersData.filter(supplier => 
        supplier.category === secondLevelName
    );
    
    updateSupplierSelect(filteredSuppliers, supplierSelect);
}

function filterCategoriesBySupplier() {
    const supplierId = document.getElementById('pf_supplier')?.value;
    const categorySelect = document.getElementById('pf_category');
    
    if (!categorySelect) return;
    
    if (!supplierId) {
        // 显示所有第三级分类
        populateAllThirdLevelCategories(categorySelect);
        return;
    }
    
    // 获取选中的供应商
    const selectedSupplier = suppliersData.find(s => s.id == supplierId);
    if (!selectedSupplier || !selectedSupplier.category) {
        populateAllThirdLevelCategories(categorySelect);
        return;
    }
    
    const secondLevelId = findSecondLevelIdByCategoryName(selectedSupplier.category);
    if (!secondLevelId) {
        populateAllThirdLevelCategories(categorySelect);
        return;
    }
    const availableCategories = getThirdLevelCategories(secondLevelId);
    
    updateCategorySelect(availableCategories, categorySelect);
}

function updateCategorySelect(categories, selectElement) {
    const currentValue = selectElement.value;
    const originalOptions = Array.from(selectElement.options);
    const defaultOption = originalOptions[0]; // 保留空选项
    
    selectElement.innerHTML = '';
    selectElement.appendChild(defaultOption);
    
    categories.forEach(category => {
        const option = document.createElement('option');
        option.value = category.id;
        option.textContent = category.name;
        selectElement.appendChild(option);
    });
    
    // 如果当前选择的分类不符合筛选条件，清空选择
    if (currentValue) {
        const parentInfo = findSecondLevelParent(currentValue);
        if (parentInfo && !categories.find(c => c.id == currentValue)) {
            selectElement.value = '';
        } else {
            selectElement.value = currentValue;
        }
    }
}
// 填充分类下拉列表
function populateCategoryOptions(categories, selectElement) {
    const currentValue = selectElement.value;
    const originalOptions = Array.from(selectElement.options);
    const defaultOption = originalOptions[0]; // 保留第一个选项（空选项）
    
    selectElement.innerHTML = '';
    selectElement.appendChild(defaultOption);
    
    categories.forEach(category => {
        const option = document.createElement('option');
        option.value = category.id;
        option.textContent = category.name;
        selectElement.appendChild(option);
    });
    
    // 保持之前的选择（如果仍然在列表中）
    if (currentValue) {
        selectElement.value = currentValue;
    }
}

// 在页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded. Starting initialization.'); // Debug
    // 添加CSS样式
    const notificationCSS = `
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            min-width: 300px;
            max-width: 400px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            padding: 16px;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            border-left: 4px solid #ccc;
        }

        .notification-success {
            border-left-color: #10b981;
        }

        .notification-error {
            border-left-color: #ef4444;
        }

        .notification-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            flex-shrink: 0;
            font-weight: bold;
        }

        .notification-success .notification-icon {
            background: #d1fae5;
            color: #10b981;
        }

        .notification-error .notification-icon {
            background: #fee2e2;
            color: #ef4444;
        }

        .notification-content {
            flex: 1;
            font-size: 14px;
            line-height: 1.4;
        }

        .notification-close {
            background: none;
            border: none;
            font-size: 20px;
            color: #9ca3af;
            cursor: pointer;
            padding: 0;
            margin-left: 8px;
            line-height: 1;
        }

        .notification-close:hover {
            color: #6b7280;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9998;
        }

        .loading-modal {
            background: white;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            text-align: center;
            min-width: 200px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #f3f4f6;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }

        .loading-text {
            color: #6b7280;
            font-size: 14px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;

    const style = document.createElement('style');
    style.textContent = notificationCSS;
    document.head.appendChild(style);
    // 初始化供应商数据
    initSupplierData();
    
    // 初始渲染和事件绑定
    applyProductFilter();
    renderTransactions();
    bindAllEvents();
    console.log('Initialization complete.'); // Debug
});
</script>
