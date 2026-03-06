<?php
// 供应商端 - 货品信息（状态实时刷新版）
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshHarvest - Supplier Products</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Microsoft YaHei', Arial, sans-serif;
        }
        body {
            background-color: #f8f9fa;
            color: #333;
        }
        .header {
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .nav-container {
            width: 90%;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #1976d2;
            text-decoration: none;
        }
        .nav-menu {
            display: flex;
            list-style: none;
        }
        .nav-item {
            margin-left: 30px;
        }
        .nav-link {
            text-decoration: none;
            color: #333;
            font-size: 16px;
            font-weight: 500;
            transition: color 0.3s;
            display: inline-flex;
            align-items: center;
        }
        .nav-link:hover {
            color: #1976d2;
        }
        .nav-link.active {
            color: #1976d2;
            font-weight: bold;
        }
        /* 消息红点样式 */
        .badge {
            display: inline-block;
            min-width: 18px;
            height: 18px;
            line-height: 18px;
            text-align: center;
            border-radius: 50%;
            background-color: #e53935;
            color: white;
            font-size: 12px;
            margin-left: 5px;
            vertical-align: middle;
            transition: all 0.3s ease;
        }
        .badge:empty {
            display: none;
        }
        .main {
            width: 90%;
            margin: 30px auto;
            min-height: calc(100vh - 200px);
        }
        .section {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1976d2;
            border-left: 4px solid #1976d2;
            padding-left: 10px;
        }
        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            width: 200px;
            transition: border-color 0.3s;
        }
        .filter-input:focus {
            border-color: #1976d2;
            outline: none;
        }
        .filter-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            width: 180px;
            transition: border-color 0.3s;
        }
        .filter-select:focus {
            border-color: #1976d2;
            outline: none;
        }
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .product-table th, .product-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .product-table th {
            background-color: #f5f9ff;
            color: #1976d2;
            font-weight: 600;
        }
        .product-table tr {
            transition: background-color 0.2s;
        }
        .product-table tr:hover {
            background-color: #f5f9ff;
        }
        .price-tag {
            font-weight: bold;
            color: #e53935;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            margin: 0 2px;
        }
        .btn:active {
            transform: scale(0.98);
        }
        .btn-primary {
            background-color: #1976d2;
            color: white;
        }
        .btn-primary:hover {
            background-color: #1565c0;
        }
        .btn-success {
            background-color: #43a047;
            color: white;
        }
        .btn-success:hover {
            background-color: #388e3c;
        }
        .btn-warning {
            background-color: #fbc02d;
            color: #333;
        }
        /* 弹窗样式 */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.show {
            display: flex;
        }
        .modal-content {
            background-color: white;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: #1976d2;
        }
        .modal-close {
            font-size: 24px;
            cursor: pointer;
            color: #999;
            transition: color 0.3s;
        }
        .modal-close:hover {
            color: #333;
        }
        .modal-body {
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #666;
        }
        .form-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .form-input:focus {
            border-color: #1976d2;
            outline: none;
        }
        .status-tag {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-accepted {
            background-color: #e8f5e9;
            color: #43a047;
        }
        .status-rejected {
            background-color: #ffebee;
            color: #e53935;
        }
        .footer {
            background-color: #333;
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }
        .footer-container {
            width: 90%;
            margin: 0 auto;
            text-align: center;
        }
        .copyright {
            margin-top: 20px;
            font-size: 14px;
            color: #999;
        }
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            .filter-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            .filter-input, .filter-select {
                width: 100%;
            }
            .product-table th, .product-table td {
                padding: 8px 10px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="main">
        <section class="section">
            <h2 class="section-title">Product Management</h2>
            <div class="filter-bar">
                <input type="text" class="filter-input" placeholder="Search product name / ID" id="productSearch" oninput="filterProducts()">
                <select class="filter-select" id="categoryFilter" onchange="filterProducts()">
                    <option value="">All categories</option>
                    <option value="vegetable">Vegetables</option>
                    <option value="fruit">Fruit</option>
                    <option value="meat">Meat</option>
                    <option value="seafood">Seafood</option>
                    <option value="grain">Grain & Oil</option>
                </select>
                <select class="filter-select" id="statusFilter" onchange="filterProducts()">
                    <option value="">All statuses</option>
                    <option value="online">Available</option>
                    <option value="offline">Unavailable</option>
                </select>
                <button class="btn btn-primary" onclick="openAddModal()">Add product</button>
                <button class="btn btn-warning" onclick="resetFilter()">Reset</button>
            </div>

            <table class="product-table">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Spec</th>
                        <th>Supply Price</th>
                        <th>Suggested Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="productTableBody">
                    <!-- 货品数据行将通过JavaScript动态生成或从服务器加载 -->
                    <tr data-id="P001" data-name="Organic Lettuce" data-category="vegetable" data-spec="500g/pack" data-price="3.5" data-sellprice="5.9" data-status="online">
                        <td>P001</td>
                        <td>Organic Lettuce</td>
                        <td>Vegetables</td>
                        <td>500g/pack</td>
                        <td class="price-tag">¥3.50</td>
                        <td>¥5.90</td>
                        <td><span class="status-tag status-accepted">Available</span></td>
                        <td>
                            <button class="btn btn-primary" onclick="openEditModal('P001')">Edit</button>
                            <button class="btn btn-success" onclick="toggleStatus('P001')">Disable</button>
                        </td>
                    </tr>
                    <!-- 更多货品行... -->
                </tbody>
            </table>
        </section>
    </main>

    <!-- 货品编辑/新增弹窗 -->
    <div class="modal" id="productModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Add Product</h3>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="productForm">
                    <input type="hidden" id="formProductId">
                    <div class="form-group">
                        <label class="form-label">Product Name</label>
                        <input type="text" class="form-input" id="productName" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select class="form-input" id="productCategory" required>
                            <option value="vegetable">Vegetables</option>
                            <option value="fruit">Fruit</option>
                            <option value="meat">Meat</option>
                            <option value="seafood">Seafood</option>
                            <option value="grain">Grain & Oil</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Spec</label>
                        <input type="text" class="form-input" id="productSpec" required placeholder="e.g. 500g/pack">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Supply price (CNY)</label>
                        <input type="number" step="0.01" min="0" class="form-input" id="productPrice" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Suggested price (CNY)</label>
                        <input type="number" step="0.01" min="0" class="form-input" id="productSellPrice" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-input" id="productStatus" required>
                            <option value="online">Available</option>
                            <option value="offline">Unavailable</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Save</button>
                </form>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-container">
            <h3 class="logo" style="color: white; margin-bottom: 20px;">FreshHarvest - Supplier Portal</h3>
            <div style="display: flex; justify-content: center; gap: 30px; margin-bottom: 20px;">
                <a href="#" style="color: #ccc; text-decoration: none;">Supplier Help Center</a>
                <a href="#" style="color: #ccc; text-decoration: none;">Partnership Agreement</a>
                <a href="#" style="color: #ccc; text-decoration: none;">Feedback</a>
                <a href="#" style="color: #ccc; text-decoration: none;">Contact Platform</a>
            </div>
            <div class="copyright">© 2024 FreshHarvest. All rights reserved | Support: 400-888-XXXX</div>
        </div>
    </footer>

    <script>
        // 原始货品数据（用于筛选重置）
        let originalProducts = [];
        // 分类名称映射
        const categoryMap = {
            'vegetable': 'Vegetables',
            'fruit': 'Fruit',
            'meat': 'Meat',
            'seafood': 'Seafood',
            'grain': 'Grain & Oil'
        };

        // 初始化
        document.addEventListener('DOMContentLoaded', function() {
            // 保存原始数据
            const rows = document.querySelectorAll('#productTableBody tr');
            rows.forEach(row => {
                originalProducts.push({
                    id: row.dataset.id,
                    name: row.dataset.name,
                    category: row.dataset.category,
                    spec: row.dataset.spec,
                    price: row.dataset.price,
                    sellprice: row.dataset.sellprice,
                    status: row.dataset.status,
                    html: row.outerHTML
                });
            });

            // 初始化未处理订单数量
            updateUnhandledOrderCount();
            
            // 每30秒刷新一次未处理订单数量
            setInterval(updateUnhandledOrderCount, 30000);

            // 为订单管理导航项添加点击事件
            const orderNav = document.querySelector('.nav-item a[href="orders.php"]');
            if (orderNav) {
                orderNav.addEventListener('click', function(e) {
                    // 如果点击了红点，自动筛选待确认订单
                    if (e.target.classList.contains('badge')) {
                        // 在实际应用中，可以通过localStorage等方式传递筛选状态
                        window.location.href = 'orders.php?status=pending';
                    }
                });
            }
        });

        // 1. 筛选货品
        function filterProducts() {
            const searchValue = document.getElementById('productSearch').value.toLowerCase();
            const categoryValue = document.getElementById('categoryFilter').value;
            const statusValue = document.getElementById('statusFilter').value;
            
            // 筛选逻辑
            const filteredProducts = originalProducts.filter(product => {
                const matchesSearch = product.name.toLowerCase().includes(searchValue) || 
                                     product.id.toLowerCase().includes(searchValue);
                const matchesCategory = !categoryValue || product.category === categoryValue;
                const matchesStatus = !statusValue || product.status === statusValue;
                
                return matchesSearch && matchesCategory && matchesStatus;
            });
            
            // 更新表格显示
            const tbody = document.getElementById('productTableBody');
            tbody.innerHTML = '';
            
            if (filteredProducts.length > 0) {
                filteredProducts.forEach(product => {
                    tbody.innerHTML += product.html;
                });
            } else {
                // 无结果时显示提示
                const noResultRow = document.createElement('tr');
                noResultRow.innerHTML = `<td colspan="8" style="text-align: center; padding: 20px;">No matching products found</td>`;
                tbody.appendChild(noResultRow);
            }
        }

        // 2. 重置筛选条件
        function resetFilter() {
            document.getElementById('productSearch').value = '';
            document.getElementById('categoryFilter').value = '';
            document.getElementById('statusFilter').value = '';
            
            // 恢复原始表格数据
            const tbody = document.getElementById('productTableBody');
            tbody.innerHTML = '';
            originalProducts.forEach(product => {
                tbody.innerHTML += product.html;
            });
        }

        // 以下为原有功能代码（保持不变）
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Product';
            document.getElementById('formProductId').value = '';
            document.getElementById('productForm').reset();
            document.getElementById('productModal').classList.add('show');
        }

        function openEditModal(id) {
            // 实际应用中应从服务器获取数据
            const product = originalProducts.find(p => p.id === id);
            if (product) {
                document.getElementById('modalTitle').textContent = 'Edit Product';
                document.getElementById('formProductId').value = product.id;
                document.getElementById('productName').value = product.name;
                document.getElementById('productCategory').value = product.category;
                document.getElementById('productSpec').value = product.spec;
                document.getElementById('productPrice').value = product.price;
                document.getElementById('productSellPrice').value = product.sellprice;
                document.getElementById('productStatus').value = product.status;
                document.getElementById('productModal').classList.add('show');
            }
        }

        function closeModal() {
            document.getElementById('productModal').classList.remove('show');
        }

        // 切换货品状态
        function toggleStatus(id) {
            const product = originalProducts.find(p => p.id === id);
            if (product) {
                product.status = product.status === 'online' ? 'offline' : 'online';
                filterProducts(); // 刷新表格显示
                alert(`Product ${product.name} status updated to: ${product.status === 'online' ? 'Available' : 'Unavailable'}`);
            }
        }

         document.addEventListener('DOMContentLoaded', function() {
            // 计算并更新未处理订单数量（待确认状态）
            function updateUnhandledOrderCount() {
                // 实际应用中应通过AJAX从服务器获取数据
                // 此处模拟数据，与订单页保持一致
                const pendingCount = 1; // 待确认订单数量
                const badge = document.getElementById('unhandledOrderBadge');
                badge.textContent = pendingCount > 0 ? pendingCount : '';
            }

            // 初始化红点
            updateUnhandledOrderCount();
            
            // 点击红点跳转到订单管理页并筛选待确认订单
            const badge = document.getElementById('unhandledOrderBadge');
            if (badge) {
                badge.parentElement.addEventListener('click', function(e) {
                    window.location.href = 'orders.php?status=pending';
                });
            }
        });
    </script>
</body>
</html>
