<?php
session_start();
require_once __DIR__ . '/inc/data.php';

$servername = "localhost";
$username = "root";
$password = "NewRootPwd123!";
$dbname = "mydb";

if (!isset($_SESSION['supplier_logged_in']) || $_SESSION['supplier_logged_in'] !== true) {
    header('Location: ../login/login.php');
    exit();
}

// 获取筛选参数
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$branchFilter = isset($_GET['branch']) ? (int)$_GET['branch'] : 0;
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// 调用获取订单数据的函数（传入筛选参数）
$orders = getSupplierPurchaseOrders(
    $statusFilter !== '' ? $statusFilter : null,
    $branchFilter > 0 ? $branchFilter : null,
    $_SESSION['supplier_id'],
    $searchTerm !== '' ? $searchTerm : null
);

// 获取分店列表用于筛选
$branches = getBranches();

// 订单状态选项
$statusOptions = [
    'pending' => '待处理',
    'ordered' => '已下单',
    'received' => '已收货',
    'cancelled' => '已取消'
];

// 获取订单详细信息（用于模态框展示）
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $orderDetail = getPurchaseOrderDetail($_GET['view']);
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>鲜选生鲜 - 供应商端-订单管理</title>
    <style>
        /* 保持你原来的CSS样式不变 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Microsoft YaHei', Arial, sans-serif;
        }
        body {
            background-color: #f8f9fa;
            color: #333;
            margin: 20px;
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
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
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
        .status-ordered {
            background-color: #e3f2fd;
            color: #2196f3;
        }
        .status-received {
            background-color: #e8f5e9;
            color: #4caf50;
        }
        .status-cancelled {
            background-color: #ffebee;
            color: #e53935;
        }
        .action-btn {
            padding: 5px 10px;
            margin: 0 5px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .detail-btn {
            background: #2196f3;
            color: white;
        }
        .update-form {
            display: inline;
        }
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
            max-width: 800px;
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
            
            <?php if (isset($message)): ?>
                <div class="message" style="background-color: #e8f5e9; color: #4caf50; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="filter-bar">
                <form method="get" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <input type="text" class="filter-input" name="search" placeholder="搜索订单编号/门店名称" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    
                    <select class="filter-select" name="branch">
                        <option value="">所有分店</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_ID']; ?>" 
                                <?php echo $branchFilter == $branch['branch_ID'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($branch['branch_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select class="filter-select" name="status">
                        <option value="">全部状态</option>
                        <?php foreach ($statusOptions as $value => $label): ?>
                            <option value="<?php echo $value; ?>" 
                                <?php echo $statusFilter == $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">筛选</button>
                    <a href="orders.php" class="btn" style="background-color: #f5f5f5; color: #333;">重置</a>
                </form>
            </div>
            
            <div style="overflow-x: auto;">
                <table class="order-table">
                    <thead>
                        <tr>
                            <th>订单编号</th>
                            <th>分店</th>
                            <th>下单日期</th>
                            <th>总金额</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px;">暂无订单数据</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>PO-<?php echo str_pad($order['purchase_order_ID'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($order['branch_name']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($order['date'])); ?></td>
                                    <td><?php echo formatAmount($order['total_amount']); ?></td>
                                    <td>
                                        <span class="status-tag status-<?php echo $order['status']; ?>">
                                            <?php echo $statusOptions[$order['status']] ?? $order['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="action-btn detail-btn" onclick="viewOrderDetail(<?php echo $order['purchase_order_ID']; ?>)">
                                            查看详情
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- 订单详情模态框 -->
    <div id="orderDetailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">订单详情</h2>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body" id="orderDetailContent">
                <!-- 订单详情内容将通过JavaScript动态加载 -->
            </div>
            <div class="modal-footer">
                <button class="btn" onclick="closeModal()">关闭</button>
            </div>
        </div>
    </div>

    <script>
        function viewOrderDetail(orderId) {
            // 显示加载中
            document.getElementById('orderDetailContent').innerHTML = '<div style="text-align: center; padding: 40px;">加载中...</div>';
            document.getElementById('orderDetailModal').classList.add('show');
            
            // 使用AJAX获取订单详情
            fetch(`orders.php?view=${orderId}`)
                .then(response => response.text())
                .then(data => {
                    // 这里需要后端返回订单详情的HTML
                    // 我们可以直接重定向到带view参数的新页面，或者使用AJAX获取JSON数据
                    // 为了简化，我们可以直接跳转到新页面
                    window.location.href = `orders.php?view=${orderId}`;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('orderDetailContent').innerHTML = '<div style="color: red; text-align: center;">加载失败，请刷新页面重试</div>';
                });
        }
        
        function closeModal() {
            document.getElementById('orderDetailModal').classList.remove('show');
        }
        
        // 如果URL中有view参数，自动显示详情
        <?php if (isset($_GET['view']) && is_numeric($_GET['view']) && isset($orderDetail)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showOrderDetail(<?php echo json_encode($orderDetail); ?>);
            });
            
            function showOrderDetail(order) {
                if (!order) {
                    document.getElementById('orderDetailContent').innerHTML = '<div style="text-align: center; color: red;">订单不存在或无权访问</div>';
                    document.getElementById('orderDetailModal').classList.add('show');
                    return;
                }
                
                // 获取订单商品
                let itemsHtml = '';
                if (order.items && order.items.length > 0) {
                    itemsHtml = `
                        <table class="product-list">
                            <thead>
                                <tr>
                                    <th>商品名称</th>
                                    <th>SKU</th>
                                    <th>单价</th>
                                    <th>单位</th>
                                    <th>小计</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${order.items.map(item => `
                                    <tr>
                                        <td>${item.product_name}</td>
                                        <td>${item.sku}</td>
                                        <td>¥${(Number(item.supplier_price) || 0).toFixed(2)}</td>
                                        <td>${item.unit || '件'}</td>
                                        <td>¥${(Number(item.supplier_price) || 0).toFixed(2)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                } else {
                    itemsHtml = '<p>暂无商品信息</p>';
                }
                
                // 计算总金额
                const subtotal = order.items ? order.items.reduce((sum, item) => sum + parseFloat(item.total_cost), 0) : 0;
                const refundTotal = Number(order.refund_total) || 0;
                const orderTotal = parseFloat(order.total_amount);
                const baseTotal = Number.isFinite(orderTotal) ? orderTotal + refundTotal : subtotal + refundTotal;
                const finalAmount = Number(order.final_amount) || Math.max((Number.isFinite(orderTotal) ? orderTotal : subtotal) - refundTotal, 0);

                let returnsHtml = '';
                if (order.returns && order.returns.length > 0) {
                    returnsHtml = `
                        <h3 style="margin-top:18px;">退货明细</h3>
                        <table class="product-list">
                            <thead>
                                <tr>
                                    <th>商品名称</th>
                                    <th>SKU</th>
                                    <th>退货数量</th>
                                    <th>退款金额</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${order.returns.map(item => `
                                    <tr>
                                        <td>${item.product_name}</td>
                                        <td>${item.sku}</td>
                                        <td>${item.return_qty}</td>
                                        <td>¥${(Number(item.refund_amount) || 0).toFixed(2)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                }
                
                const modalContent = `
                    <div class="order-detail-header">
                        <div class="detail-row">
                            <div class="detail-label">订单编号：</div>
                            <div class="detail-value">PO-${String(order.purchase_order_ID).padStart(6, '0')}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">分店名称：</div>
                            <div class="detail-value">${order.branch_name}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">分店地址：</div>
                            <div class="detail-value">${order.branch_address}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">下单日期：</div>
                            <div class="detail-value">${order.date}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">订单状态：</div>
                            <div class="detail-value">
                                <span class="status-tag status-${order.status}">
                                    ${<?php echo json_encode($statusOptions); ?>[order.status] || order.status}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <h3>商品清单</h3>
                    ${itemsHtml}

                    ${returnsHtml}
                    
                    <div class="order-summary">
                        <div class="summary-item">
                            <span class="summary-label">商品总价：</span>
                            <span class="summary-value">¥${baseTotal.toFixed(2)}</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">退款金额：</span>
                            <span class="summary-value">${refundTotal > 0 ? '¥' + refundTotal.toFixed(2) : '¥0.00'}</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">订单最终金额：</span>
                            <span class="summary-value total-amount">¥${finalAmount.toFixed(2)}</span>
                        </div>
                    </div>
                `;
                
                document.getElementById('orderDetailContent').innerHTML = modalContent;
                document.getElementById('orderDetailModal').classList.add('show');
            }
        <?php endif; ?>
        
        // 点击模态框外部关闭
        window.onclick = function(event) {
            const modal = document.getElementById('orderDetailModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
