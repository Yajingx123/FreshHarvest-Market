<?php
require_once __DIR__ . '/inc/data.php';
require_once __DIR__ . '/inc/header.php';
?>
<section class="section">
    <h2 class="section-title">货品信息</h2>
    <div style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">
        <input id="productSearchInput" class="filter-input" placeholder="搜索产品名称/编号">
        <!-- 分类已取消，保留状态筛选 -->
        <select id="productStatusSelect" class="filter-select">
            <option value="">全部状态</option>
            <option>已上架</option>
            <option>已下架</option>
            <option>库存预警</option>
        </select>
        <button id="productSearchBtn" class="btn btn-primary">搜索</button>
        <button id="productAddBtn" class="btn btn-success" style="margin-left:auto;">新增产品</button>
    </div>

    <!-- 横向卡片展示区 -->
    <div id="productCardsWrap" style="overflow-x:auto;padding-bottom:8px;">
        <div id="productCards" style="display:flex;gap:12px;min-height:160px;"></div>
    </div>
</section>

<!-- 货物流水管理（只读流水表） -->
<section class="section" style="margin-top:20px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
        <h2 class="section-title" style="margin:0;">货物流水</h2>
        <div style="margin-left:auto;display:flex;gap:8px;align-items:center;">
            <input id="txSearchInput" class="filter-input" placeholder="搜索产品ID/批次/备注">
            <select id="txTypeSelect" class="filter-select">
                <option value="">全部类型</option>
                <option value="in">入库</option>
                <option value="out">出库</option>
            </select>
            <button id="txSearchBtn" class="btn btn-primary">筛选</button>
        </div>
    </div>
    <div style="overflow:auto;max-height:420px;">
        <table class="data-table" style="min-width:1100px;">
            <thead>
                <tr>
                    <th>时间</th>
                    <th>产品ID</th>
                    <th>批次号</th>
                    <th>数量</th>
                    <th>单位</th>
                    <th>进or出</th>
                    <th>来源（供应商）</th>
                    <th>去向（门店）</th>
                    <th>备注</th>
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
            <h3 style="margin:0;font-size:18px;font-weight:600;" id="editModalTitle">编辑产品</h3>
            <button id="closeEditModal" style="background:transparent;border:none;font-size:20px;cursor:pointer;color:#666;">&times;</button>
        </div>
        <div style="padding:20px;" id="editModalContent">
            <!-- 编辑表单将通过JS动态生成 -->
        </div>
        <div style="padding:12px 20px;border-top:1px solid #eee;display:flex;justify-content:flex-end;gap:10px;">
            <button id="cancelEditBtn" class="btn btn-default">取消</button>
            <button id="saveEditBtn" class="btn btn-primary">保存修改</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

<script>
// 全局数据（深拷贝避免直接修改原始数据）
let products = JSON.parse(JSON.stringify(<?= json_encode($products, JSON_UNESCAPED_UNICODE) ?>));
const partnersList = <?= json_encode($partners, JSON_UNESCAPED_UNICODE) ?>;
const transactions = <?= json_encode($transactions, JSON_UNESCAPED_UNICODE) ?>;

// 当前编辑的产品ID
let currentEditProductId = null;

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

// 渲染产品卡片（展示name、description、图片、spec、unit，可点击）
function renderProductCards(filteredProducts) {
    const container = document.getElementById('productCards');
    if (!container) return;
    container.innerHTML = '';

    if (filteredProducts.length === 0) {
        container.innerHTML = '<div style="flex:1;padding:24px;color:#666;text-align:center;">暂无匹配产品</div>';
        return;
    }

    filteredProducts.forEach(product => {
        // 图片处理：优先使用product.image，无则显示占位图
        const productImage = product.image 
            ? `<img src="${escapeHtml(product.image)}" alt="${escapeHtml(product.name)}" style="width:100%;height:120px;object-fit:cover;border-radius:4px;">`
            : '<div style="width:100%;height:120px;background:#f5f5f5;display:flex;align-items:center;justify-content:center;color:#999;border-radius:4px;">无图片</div>';

        const card = document.createElement('div');
        card.style.width = '220px';
        card.style.border = '1px solid #eee';
        card.style.borderRadius = '8px';
        card.style.padding = '12px';
        card.style.background = '#fff';
        card.style.boxShadow = '0 1px 3px rgba(0,0,0,0.05)';
        card.style.cursor = 'pointer';
        card.style.transition = 'box-shadow 0.2s ease';
        //  hover效果增强可点击提示
        card.addEventListener('mouseover', () => {
            card.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
        });
        card.addEventListener('mouseout', () => {
            card.style.boxShadow = '0 1px 3px rgba(0,0,0,0.05)';
        });
        // 点击卡片打开编辑弹窗
        card.addEventListener('click', () => openEditModal(product.id));

        card.innerHTML = `
            ${productImage}
            <h3 style="margin:10px 0 6px; font-size:16px; font-weight:600;">${escapeHtml(product.name)}</h3>
            <p style="margin:0 0 8px; font-size:13px; color:#666; line-height:1.4; height:40px; overflow:hidden;">
                ${escapeHtml(product.description || '无简介')}
            </p>
            <div style="margin:4px 0; font-size:12px; color:#888;">
                <span>规格：${escapeHtml(product.spec || '无')}</span>
            </div>
            <div style="margin:4px 0; font-size:12px; color:#888;">
                <span>单位：${escapeHtml(product.unit || '无')}</span>
            </div>
            <div style="margin-top:8px; padding-top:8px; border-top:1px dashed #eee; font-size:12px; color:#999;">
                状态：${escapeHtml(product.statusText || '未知')}
            </div>
            <!-- 可编辑提示 -->
            <div style="margin-top:6px; font-size:11px; color:#007bff; text-align:right;">
                点击编辑 →
            </div>
        `;
        container.appendChild(card);
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
        filteredTx = filteredTx.filter(tx => tx.type === typeFilter);
    }
    if (searchQuery) {
        filteredTx = filteredTx.filter(tx => {
            const searchStr = `${tx.productId || ''} ${tx.batchId || ''} ${tx.note || ''}`.toLowerCase();
            return searchStr.includes(searchQuery);
        });
    }

    if (filteredTx.length === 0) {
        tb.innerHTML = '<tr><td colspan="9" style="color:#666;padding:12px;text-align:center;">暂无流水记录</td></tr>';
        return;
    }

    // 渲染流水行
    filteredTx.forEach(tx => {
        const product = products.find(p => p.id === tx.productId) || {};
        const supplier = tx.supplier ? (partnersList.find(s => s.id === tx.supplier) || {}) : {};
        const store = tx.store ? (partnersList.find(s => s.id === tx.store) || {}) : {};

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${escapeHtml(formatDate(tx.time))}</td>
            <td>${escapeHtml(tx.productId || '')}</td>
            <td>${escapeHtml(tx.batchId || '')}</td>
            <td>${escapeHtml(String(tx.qty || 0))}</td>
            <td>${escapeHtml(tx.unit || product.unit || '')}</td>
            <td>${escapeHtml(tx.type === 'in' ? '入库' : '出库')}</td>
            <td>${escapeHtml(supplier.name ? `${supplier.name} (${supplier.id})` : '')}</td>
            <td>${escapeHtml(store.name ? `${store.name} (${store.id})` : '')}</td>
            <td>${escapeHtml(tx.note || '')}</td>
        `;
        tb.appendChild(row);
    });
}

// 产品筛选逻辑
function applyProductFilter() {
    const searchQuery = (document.getElementById('productSearchInput') || {}).value.trim().toLowerCase();
    const statusFilter = (document.getElementById('productStatusSelect') || {}).value;

    const filteredProducts = products.filter(product => {
        // 搜索筛选（产品ID、名称、描述、规格）
        const searchStr = `${product.id || ''} ${product.name || ''} ${product.description || ''} ${product.spec || ''}`.toLowerCase();
        const matchSearch = !searchQuery || searchStr.includes(searchQuery);
        
        // 状态筛选
        const matchStatus = !statusFilter || product.statusText === statusFilter;

        return matchSearch && matchStatus;
    });

    renderProductCards(filteredProducts);
}

// 打开编辑弹窗
function openEditModal(productId) {
    currentEditProductId = productId;
    const product = products.find(p => p.id === productId);
    if (!product) return;

    // 生成编辑表单
    const formHtml = `
        <div style="display:flex;gap:20px; margin-bottom:20px;">
            <!-- 图片预览与上传 -->
            <div style="width:160px; height:160px; border:1px dashed #ddd; border-radius:4px; overflow:hidden; position:relative;">
                <img id="previewImage" src="${escapeHtml(product.image || '')}" alt="产品图片" 
                     style="width:100%; height:100%; object-fit:cover; display:${product.image ? 'block' : 'none'};">
                <div id="imagePlaceholder" style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#999; ${product.image ? 'display:none' : ''}">
                    点击上传图片
                </div>
                <input type="file" id="imageUpload" accept="image/*" 
                       style="position:absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer;">
            </div>
            <div style="flex:1;">
                <div style="margin-bottom:16px;">
                    <label style="display:block; margin-bottom:6px; font-weight:500; color:#333;">产品ID</label>
                    <input type="text" id="editProductId" value="${escapeHtml(product.id)}" 
                           style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" readonly>
                    <small style="color:#666; font-size:11px;">产品ID不可修改</small>
                </div>
                <div style="margin-bottom:16px;">
                    <label style="display:block; margin-bottom:6px; font-weight:500; color:#333;">产品名称 <span style="color:red;">*</span></label>
                    <input type="text" id="editProductName" value="${escapeHtml(product.name || '')}" 
                           style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" required>
                </div>
            </div>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block; margin-bottom:6px; font-weight:500; color:#333;">产品描述</label>
            <textarea id="editProductDesc" rows="3" 
                      style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; resize:vertical;">${escapeHtml(product.description || '')}</textarea>
        </div>

        <div style="display:flex; gap:20px; margin-bottom:16px;">
            <div style="flex:1;">
                <label style="display:block; margin-bottom:6px; font-weight:500; color:#333;">规格</label>
                <input type="text" id="editProductSpec" value="${escapeHtml(product.spec || '')}" 
                       style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div style="flex:1;">
                <label style="display:block; margin-bottom:6px; font-weight:500; color:#333;">单位 <span style="color:red;">*</span></label>
                <input type="text" id="editProductUnit" value="${escapeHtml(product.unit || '')}" 
                       style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" required>
            </div>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block; margin-bottom:6px; font-weight:500; color:#333;">产品状态</label>
            <select id="editProductStatus" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                <option value="已上架" ${product.statusText === '已上架' ? 'selected' : ''}>已上架</option>
                <option value="已下架" ${product.statusText === '已下架' ? 'selected' : ''}>已下架</option>
                <option value="库存预警" ${product.statusText === '库存预警' ? 'selected' : ''}>库存预警</option>
            </select>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block; margin-bottom:6px; font-weight:500; color:#333;">单价（可选）</label>
            <input type="number" step="0.01" id="editProductPrice" value="${escapeHtml(product.price ? product.price.replace('¥', '') : '')}" 
                   style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block; margin-bottom:6px; font-weight:500; color:#333;">供应商（可选）</label>
            <select id="editProductSupplier" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                <option value="">-- 选择供应商 --</option>
                ${partnersList.map(partner => `
                    <option value="${escapeHtml(partner.id)}" ${product.supplier === partner.id ? 'selected' : ''}>
                        ${escapeHtml(partner.name)} (${escapeHtml(partner.id)})
                    </option>
                `).join('')}
            </select>
        </div>
    `;

    // 填充表单内容并显示弹窗
    document.getElementById('editModalContent').innerHTML = formHtml;
    document.getElementById('productEditModal').style.display = 'flex';

    // 绑定图片上传预览事件
    bindImageUploadPreview();
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
            alert('请上传图片格式文件（JPG、PNG、GIF、WebP）');
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
        alert('产品名称不能为空！');
        document.getElementById('editProductName').focus();
        return;
    }
    if (!productUnit) {
        alert('产品单位不能为空！');
        document.getElementById('editProductUnit').focus();
        return;
    }

    // 更新产品数据
    const updatedProduct = {
        ...products[productIndex],
        name: productName,
        description: document.getElementById('editProductDesc').value.trim(),
        spec: document.getElementById('editProductSpec').value.trim(),
        unit: productUnit,
        statusText: document.getElementById('editProductStatus').value,
        price: document.getElementById('editProductPrice').value ? `¥${Number(document.getElementById('editProductPrice').value).toFixed(2)}` : '',
        supplier: document.getElementById('editProductSupplier').value,
        // 图片：如果有预览图且不是原始图片，更新为base64（实际项目建议上传服务器，这里简化为base64）
        image: document.getElementById('previewImage').style.display === 'block' 
            ? document.getElementById('previewImage').src 
            : products[productIndex].image
    };

    // 更新全局数据
    products[productIndex] = updatedProduct;

    // 重新渲染卡片和流水表（流水表中产品相关信息会同步更新）
    applyProductFilter();
    renderTransactions();

    // 关闭弹窗
    closeEditModal();

    // 提示保存成功
    alert('产品信息修改成功！');
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

    // 新增产品按钮（保留原有功能，与编辑功能联动）
    document.getElementById('productAddBtn').addEventListener('click', async function() {
        const supplierOptions = partnersList.map(s => `<option value="${escapeHtml(s.id)}">${escapeHtml(s.name)} (${escapeHtml(s.id)})</option>`).join('');
        const html = `
            <div style="display:flex;gap:12px;">
                <div style="width:160px;height:140px;background:#f5f5f5;border:1px dashed #ddd;display:flex;align-items:center;justify-content:center;color:#999;">图片占位</div>
                <div style="flex:1;">
                    <div class="form-group"><label class="form-label">产品名称 <span style="color:red;">*</span></label><input id="pf_name" class="form-control" required></div>
                    <div class="form-row">
                        <div class="form-col"><label class="form-label">单价</label><input id="pf_price" class="form-control" type="number" step="0.01"></div>
                        <div class="form-col"><label class="form-label">单位 <span style="color:red;">*</span></label><input id="pf_unit" class="form-control" value="份" required></div>
                    </div>
                    <div class="form-group"><label class="form-label">规格</label><input id="pf_spec" class="form-control"></div>
                    <div class="form-group"><label class="form-label">供应商</label><select id="pf_supplier" class="form-control"><option value="">（未关联）</option>${supplierOptions}</select></div>
                    <div class="form-group"><label class="form-label">简介</label><textarea id="pf_desc" class="form-control" rows="3"></textarea></div>
                </div>
            </div>
        `;

        // 假设showAppModal是原有项目中的弹窗函数，如果没有则替换为原生alert或自定义弹窗
        if (typeof showAppModal === 'function') {
            const ok = await showAppModal('新增产品', html, { showCancel: true, okText: '添加' });
            if (!ok) return;

            const b = document.getElementById('appModalBody');
            const newP = {
                id: 'SP' + Date.now().toString().slice(-8),
                name: (b.querySelector('#pf_name') || {}).value || '未命名',
                price: (b.querySelector('#pf_price') || {}).value ? `¥${Number((b.querySelector('#pf_price') || {}).value).toFixed(2)}` : '¥0.00',
                unit: (b.querySelector('#pf_unit') || {}).value || '份',
                spec: (b.querySelector('#pf_spec') || {}).value || '',
                supplier: (b.querySelector('#pf_supplier') || {}).value || '',
                description: (b.querySelector('#pf_desc') || {}).value || '',
                stock: 0,
                statusText: '已上架',
                image: ''
            };

            // 添加新产品并重新渲染
            products.unshift(newP);
            applyProductFilter();
            await showAppModal('已添加', '<p>产品已添加成功！</p>', { showCancel: false });
        } else {
            // 如果没有showAppModal函数，使用原生prompt简化实现（实际项目建议保留原有弹窗逻辑）
            const productName = prompt('请输入产品名称：');
            if (!productName) return;

            const newP = {
                id: 'SP' + Date.now().toString().slice(-8),
                name: productName,
                price: '¥0.00',
                unit: '份',
                spec: '',
                supplier: '',
                description: '',
                stock: 0,
                statusText: '已上架',
                image: ''
            };

            products.unshift(newP);
            applyProductFilter();
            alert('产品已添加成功！');
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
    document.getElementById('productStatusSelect').addEventListener('change', applyProductFilter);

    // 流水搜索事件
    document.getElementById('txSearchBtn').addEventListener('click', renderTransactions);
    document.getElementById('txSearchInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') renderTransactions();
    });
    document.getElementById('txTypeSelect').addEventListener('change', renderTransactions);

    // 编辑弹窗事件
    bindEditModalEvents();
}

// 初始渲染和事件绑定
applyProductFilter();
renderTransactions();
bindAllEvents();
</script>