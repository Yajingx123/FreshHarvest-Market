<?php
// 供应商端 - 订单管理（带消息红点交互）
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>鲜选生鲜 - 供应商端-订单管理</title>
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
            width: 250px;
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
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
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
        .btn-danger {
            background-color: #e53935;
            color: white;
        }
        .btn-danger:hover {
            background-color: #d32f2f;
        }
        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .order-table th, .order-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .order-table th {
            background-color: #f5f9ff;
            color: #1976d2;
            font-weight: 600;
        }
        .order-table tr:hover {
            background-color: #f5f9ff;
        }
        .status-tag {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-pending {
            background-color: #fff3e0;
            color: #ff9800;
        }
        .status-accepted {
            background-color: #e8f5e9;
            color: #43a047;
        }
        .status-shipped {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        .status-completed {
            background-color: #f3e5f5;
            color: #8e24aa;
        }
        .status-cancelled {
            background-color: #ffebee;
            color: #e53935;
        }
        /* 弹窗样式 */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal.show {
            display: flex;
        }
        .modal-content {
            background-color: white;
            border-radius: 10px;
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
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
            color: #333;
        }
        .modal-close {
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        .modal-close:hover {
            color: #333;
        }
        .modal-body {
            padding: 20px;
        }
        .order-detail-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        .detail-label {
            flex: 0 0 120px;
            font-weight: 500;
            color: #666;
        }
        .detail-value {
            flex: 1;
        }
        .product-list {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .product-list th, .product-list td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .product-list th {
            background-color: #f5f9ff;
            color: #666;
            font-weight: 500;
        }
        .order-summary {
            text-align: right;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .summary-item {
            margin-bottom: 8px;
        }
        .summary-label {
            display: inline-block;
            width: 100px;
            text-align: left;
        }
        .summary-value {
            font-weight: bold;
        }
        .total-amount {
            color: #e53935;
            font-size: 16px;
        }
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
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
        .detail-info {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .detail-info:last-child {
            border-bottom: none;
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
                align-items: stretch;
            }
            .filter-input, .filter-select {
                width: 100%;
            }
            .order-table th:nth-child(4), .order-table td:nth-child(4),
            .order-table th:nth-child(5), .order-table td:nth-child(5) {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="main">
        <section class="section">
            <h2 class="section-title">订单管理</h2>
            
            <div class="filter-bar">
                <input type="text" class="filter-input" id="orderSearch" placeholder="搜索订单编号/门店名称">
                <select class="filter-select" id="statusFilter">
                    <option value="">全部状态</option>
                    <option value="pending">待确认</option>
                    <option value="accepted">已确认</option>
                    <option value="shipped">已发货</option>
                    <option value="completed">已完成</option>
                    <option value="cancelled">已取消</option>
                </select>
                <select class="filter-select" id="dateFilter">
                    <option value="">全部时间</option>
                    <option value="today">今天</option>
                    <option value="yesterday">昨天</option>
                    <option value="week">近7天</option>
                    <option value="month">本月</option>
                </select>
                <button class="btn btn-primary">导出订单</button>
            </div>

            <table class="order-table">
                <thead>
                    <tr>
                        <th>订单编号</th>
                        <th>门店名称</th>
                        <th>下单时间</th>
                        <th>商品数量</th>
                        <th>订单金额</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <tr data-id="ORD20240520001" data-status="pending">
                        <td>ORD20240520001</td>
                        <td>鲜选生鲜（高新店）</td>
                        <td>2024-05-20 08:30:25</td>
                        <td>8</td>
                        <td>¥1,256.00</td>
                        <td><span class="status-tag status-pending">待确认</span></td>
                        <td>
                            <button class="btn btn-primary" onclick="viewOrder('ORD20240520001')">查看</button>
                            <button class="btn btn-success" onclick="acceptOrder('ORD20240520001')">确认</button>
                            <button class="btn btn-danger" onclick="cancelOrder('ORD20240520001')">拒绝</button>
                        </td>
                    </tr>
                    <!-- 更多订单数据行... -->
                </tbody>
            </table>
        </section>
    </main>

    <!-- 订单详情弹窗 -->
    <div class="modal" id="orderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">订单详情</h3>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body" id="orderDetailContent">
                <!-- 订单详情内容将通过JavaScript动态填充 -->
            </div>
            <div class="modal-footer" id="orderActionButtons">
                <!-- 操作按钮将通过JavaScript动态填充 -->
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-container">
            <h3 class="logo" style="color: white; margin-bottom: 20px;">鲜选生鲜 - 供应商管理平台</h3>
            <div style="display: flex; justify-content: center; gap: 30px; margin-bottom: 20px;">
                <a href="#" style="color: #ccc; text-decoration: none;">供应商帮助中心</a>
                <a href="#" style="color: #ccc; text-decoration: none;">合作协议</a>
                <a href="#" style="color: #ccc; text-decoration: none;">投诉反馈</a>
                <a href="#" style="color: #ccc; text-decoration: none;">联系平台</a>
            </div>
            <div class="copyright">© 2024 鲜选生鲜 版权所有 | 平台客服电话：400-888-XXXX</div>
        </div>
    </footer>

    <script>
        // 页面加载时检查URL参数并自动筛选
        document.addEventListener('DOMContentLoaded', function() {
            // 检查URL参数是否有状态筛选
            const urlParams = new URLSearchParams(window.location.search);
            const statusParam = urlParams.get('status');
            if (statusParam) {
                const statusFilter = document.getElementById('statusFilter');
                statusFilter.value = statusParam;
                filterOrders(); // 应用筛选
            }
            
            // 初始化未处理订单数量
            updateUnhandledOrderCount();
            
            // 每30秒刷新一次未处理订单数量
            setInterval(updateUnhandledOrderCount, 30000);
        });

        // 计算并更新未处理订单数量（待确认状态）
        function updateUnhandledOrderCount() {
            // 筛选所有状态为"待确认"的订单行
            const pendingOrders = document.querySelectorAll('tr[data-status="pending"]');
            const count = pendingOrders.length;
            
            // 更新红点显示
            const badge = document.getElementById('unhandledOrderBadge');
            badge.textContent = count > 0 ? count : '';
        }

        // 订单筛选功能
        function filterOrders() {
            const searchValue = document.getElementById('orderSearch').value.toLowerCase();
            const statusValue = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('.order-table tbody tr');
            
            rows.forEach(row => {
                const orderId = row.dataset.id.toLowerCase();
                const status = row.dataset.status;
                const storeName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                
                const matchesSearch = orderId.includes(searchValue) || storeName.includes(searchValue);
                const matchesStatus = !statusValue || status === statusValue;
                
                row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            });
        }

        // 查看订单详情
        function viewOrder(orderId) {
            // 实际应用中应通过AJAX从服务器获取订单详情
            const detailContent = `
                <div class="order-detail-header">
                    <div class="detail-row">
                        <div class="detail-label">订单编号：</div>
                        <div class="detail-value">ORD20240520001</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">门店名称：</div>
                        <div class="detail-value">鲜选生鲜（高新店）</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">门店地址：</div>
                        <div class="detail-value">西安市高新区科技路88号</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">联系人：</div>
                        <div class="detail-value">张三（13800138000）</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">下单时间：</div>
                        <div class="detail-value">2024-05-20 08:30:25</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">要求送达时间：</div>
                        <div class="detail-value">2024-05-20 14:00前</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">订单状态：</div>
                        <div class="detail-value"><span class="status-tag status-pending">待确认</span></div>
                    </div>
                </div>

                <h4 style="margin-bottom: 10px;">商品清单</h4>
                <table class="product-list">
                    <thead>
                        <tr>
                            <th>商品名称</th>
                            <th>规格</th>
                            <th>单价（元）</th>
                            <th>数量</th>
                            <th>小计（元）</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>有机生菜</td>
                            <td>500g/份</td>
                            <td>3.50</td>
                            <td>20</td>
                            <td>70.00</td>
                        </tr>
                        <tr>
                            <td>精品西红柿</td>
                            <td>500g/份</td>
                            <td>4.80</td>
                            <td>15</td>
                            <td>72.00</td>
                        </tr>
                        <!-- 更多商品行... -->
                    </tbody>
                </table>

                <div class="order-summary">
                    <div class="summary-item">
                        <span class="summary-label">商品总金额：</span>
                        <span class="summary-value">¥1,156.00</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">配送费：</span>
                        <span class="summary-value">¥100.00</span>
                    </div>
                    <div class="summary-item total-amount">
                        <span class="summary-label">订单总金额：</span>
                        <span class="summary-value">¥1,256.00</span>
                    </div>
                </div>
            `;
            
            // 待确认状态的订单显示确认和拒绝按钮
            const actionButtons = `
                <button class="btn btn-danger" onclick="cancelOrder('${orderId}')">拒绝订单</button>
                <button class="btn btn-success" onclick="acceptOrder('${orderId}')">确认订单</button>
            `;
            
            document.getElementById('orderDetailContent').innerHTML = detailContent;
            document.getElementById('orderActionButtons').innerHTML = actionButtons;
            document.getElementById('orderModal').classList.add('show');
        }

        // 确认订单
        function acceptOrder(orderId) {
            if (confirm('确定要确认此订单吗？')) {
                // 实际应用中应通过AJAX更新服务器订单状态
                const row = document.querySelector(`tr[data-id="${orderId}"]`);
                if (row) {
                    row.dataset.status = 'accepted';
                    row.querySelector('.status-tag').className = 'status-tag status-accepted';
                    row.querySelector('.status-tag').textContent = '已确认';
                    row.querySelector('td:last-child').innerHTML = `
                        <button class="btn btn-primary" onclick="viewOrder('${orderId}')">查看</button>
                        <button class="btn btn-success" onclick="shipOrder('${orderId}')">发货</button>
                    `;
                }
                closeModal();
                updateUnhandledOrderCount(); // 更新红点数量
                alert('订单已确认');
            }
        }

        // 取消订单
        function cancelOrder(orderId) {
            const reason = prompt('请输入拒绝订单的原因：');
            if (reason) {
                // 实际应用中应通过AJAX更新服务器订单状态
                const row = document.querySelector(`tr[data-id="${orderId}"]`);
                if (row) {
                    row.dataset.status = 'cancelled';
                    row.querySelector('.status-tag').className = 'status-tag status-cancelled';
                    row.querySelector('.status-tag').textContent = '已取消';
                    row.querySelector('td:last-child').innerHTML = `
                        <button class="btn btn-primary" onclick="viewOrder('${orderId}')">查看</button>
                    `;
                }
                closeModal();
                updateUnhandledOrderCount(); // 更新红点数量
                alert('订单已拒绝');
            }
        }

        // 发货订单
        function shipOrder(orderId) {
            if (confirm('确定要标记此订单为已发货吗？')) {
                // 实际应用中应通过AJAX更新服务器订单状态
                const row = document.querySelector(`tr[data-id="${orderId}"]`);
                if (row) {
                    row.dataset.status = 'shipped';
                    row.querySelector('.status-tag').className = 'status-tag status-shipped';
                    row.querySelector('.status-tag').textContent = '已发货';
                    row.querySelector('td:last-child').innerHTML = `
                        <button class="btn btn-primary" onclick="viewOrder('${orderId}')">查看</button>
                        <button class="btn btn-success" onclick="completeOrder('${orderId}')">完成</button>
                    `;
                }
                closeModal();
                alert('订单已标记为已发货');
            }
        }

        // 完成订单
        function completeOrder(orderId) {
            if (confirm('确定要标记此订单为已完成吗？')) {
                // 实际应用中应通过AJAX更新服务器订单状态
                const row = document.querySelector(`tr[data-id="${orderId}"]`);
                if (row) {
                    row.dataset.status = 'completed';
                    row.querySelector('.status-tag').className = 'status-tag status-completed';
                    row.querySelector('.status-tag').textContent = '已完成';
                    row.querySelector('td:last-child').innerHTML = `
                        <button class="btn btn-primary" onclick="viewOrder('${orderId}')">查看</button>
                    `;
                }
                closeModal();
                alert('订单已标记为已完成');
            }
        }

        // 关闭弹窗
        function closeModal() {
            document.getElementById('orderModal').classList.remove('show');
        }

        // 为筛选框添加事件监听
        document.getElementById('orderSearch').addEventListener('input', filterOrders);
        document.getElementById('statusFilter').addEventListener('change', filterOrders);
        document.getElementById('dateFilter').addEventListener('change', filterOrders);
    </script>
</body>
</html>