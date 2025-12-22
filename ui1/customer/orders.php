<?php $pageTitle = "我的订单"; ?>
<?php include 'header.php'; ?>

<?php
session_start();
require_once __DIR__ . '/inc/data.php';    // 包含上面的data.php
// 这里需要确保$_SESSION['customer_id']已正确设置
// 检查登录
if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true) {
    header('Location: ../login/login.php');
    exit();
}

$customerId = $_SESSION['customer_id'];
$orders = getCustomerOrders($customerId);
$currentOrderId = $_GET['order_id'] ?? 0;
$currentOrder = $currentOrderId ? getOrderDetails($currentOrderId) : null;
?>

<style>
    .product-section {
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
        color: #2d884d;
        border-left: 4px solid #2d884d;
        padding-left: 10px;
    }
    .tabs {
        display: flex;
        border-bottom: 1px solid #eee;
        margin-bottom: 20px;
    }
    .tab {
        padding: 10px 20px;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    .tab.active {
        border-bottom-color: #2d884d;
        color: #2d884d;
    }
    .tab-content {
        padding: 10px;
    }
    .order-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .order-item {
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }
    .order-item:hover {
        background-color: #f9f9f9;
    }
    .order-details {
        flex: 1;
    }
    .order-status {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 500;
    }
    .status-pending {
        background-color: #fff7e6;
        color: #faad14;
    }
    .status-delivering {
        background-color: #e6f7ff;
        color: #1890ff;
    }
    .status-completed {
        background-color: #f0f9f0;
        color: #52c41a;
    }
    /* 优化的展开样式 - 浅绿色底色 */
    .order-detail-content {
        border: 1px solid #eee;
        border-top: none;
        border-radius: 0 0 8px 8px;
        padding: 0 15px;
        margin-top: -15px;
        background-color: #f0f7f2; /* 原有浅绿色底色 */
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease, padding 0.3s ease;
    }
    .order-detail-content.active {
        padding: 15px;
        max-height: 400px;
    }
    .detail-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px dashed #eee;
    }
    .detail-item:last-child {
        border-bottom: none;
    }
    .detail-name {
        flex: 2;
    }
    .detail-price {
        flex: 1;
        text-align: center;
    }
    .detail-quantity {
        flex: 1;
        text-align: center;
    }
    .detail-total {
        flex: 1;
        text-align: right;
        color: #ff4d4f;
    }
    .detail-summary {
        margin-top: 15px;
        text-align: right;
        font-weight: bold;
    }
    @media (max-width: 768px) {
        .order-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        .detail-item {
            flex-wrap: wrap;
        }
        .detail-name {
            flex-basis: 100%;
            margin-bottom: 5px;
        }
        .detail-price, .detail-quantity, .detail-total {
            flex-basis: 33.33%;
        }
    }
</style>

<!-- 我的订单 -->
<main class="order-page">
    <section class="order-filters">
        <h2>我的订单</h2>
        <div class="filter-tabs">
            <button class="tab active" data-status="all">全部订单</button>
            <button class="tab" data-status="Pending">待支付</button>
            <button class="tab" data-status="Completed">已完成</button>
            <button class="tab" data-status="Cancelled">已取消</button>
        </div>
    </section>

    <section class="order-list">
        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-item" data-order="<?= $order['id'] ?>" data-status="<?= $order['status'] ?>">
                    <div class="order-header">
                        <div class="order-info">
                            <h3>订单编号：<?= $order['order_number'] ?></h3>
                            <p>下单时间：<?= $order['order_date'] ?></p>
                            <p>门店：<?= $order['store_name'] ?></p>
                            <p>商品：<?= $order['product_details'] ?></p>
                        </div>
                        <div class="order-summary">
                            <p class="total">实付：¥<?= number_format($order['total_amount'], 2) ?></p>
                            <span class="status status-<?= strtolower($order['status']) ?>">
                                <?= [
                                    'Pending' => '待支付',
                                    'Completed' => '已完成',
                                    'Cancelled' => '已取消'
                                ][$order['status']] ?? $order['status'] ?>
                            </span>
                            <a href="?order_id=<?= $order['id'] ?>" class="view-detail">查看详情</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <img src="images/empty-orders.png" alt="暂无订单">
                <p>您暂无订单记录，快去购物吧~</p>
                <a href="products.php" class="btn-primary">去逛逛</a>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($currentOrder): ?>
        <section class="order-detail-modal">
            <div class="modal-content">
                <h3>订单详情 #<?= $currentOrder['order_number'] ?></h3>
                <div class="detail-section">
                    <h4>订单信息</h4>
                    <p>订单编号：<?= $currentOrder['order_number'] ?></p>
                    <p>下单时间：<?= $currentOrder['order_date'] ?></p>
                    <p>门店：<?= $currentOrder['store_name'] ?></p>
                    <p>收货地址：<?= $currentOrder['shipping_address'] ?></p>
                    <p>订单状态：<?= [
                        'Pending' => '待支付',
                        'Completed' => '已完成',
                        'Cancelled' => '已取消'
                    ][$currentOrder['order_status']] ?></p>
                </div>

                <div class="detail-section">
                    <h4>商品明细</h4>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>商品名称</th>
                                <th>单价</th>
                                <th>数量</th>
                                <th>小计</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($currentOrder['items'] as $item): ?>
                                <tr>
                                    <td><?= $item['product_name'] ?></td>
                                    <td>¥<?= number_format($item['unit_price'], 2) ?></td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td>¥<?= number_format($item['total'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="detail-section total-section">
                    <p>原始总金额：¥<?= number_format($currentOrder['total_amount'], 2) ?></p>
                    <p>折扣后金额：¥<?= number_format($currentOrder['final_amount'], 2) ?></p>
                </div>

                <button class="close-modal" onclick="window.location='orders.php'">关闭</button>
            </div>
        </section>
    <?php endif; ?>
</main>

<script>
    // 订单状态筛选
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const status = this.getAttribute('data-status');
            
            // 更新标签状态
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // 筛选订单
            document.querySelectorAll('.order-item').forEach(item => {
                if (status === 'all' || item.getAttribute('data-status') === status) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
</script>
</main>
</body>
</html>