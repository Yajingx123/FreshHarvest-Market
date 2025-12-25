<?php $pageTitle = "我的订单"; ?>
<?php include 'header.php'; ?>

<?php
session_start();
require_once __DIR__ . '/inc/data.php';
// 检查登录状态
if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true) {
    header('Location: ../login/login.php');
    exit();
}

$customerId = $_SESSION['customer_id'];
$orders = getCustomerOrders($customerId);
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
    .status-cancelled {
        background-color: #fff1f0;
        color: #ff4d4f;
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
    .empty-state {
        text-align: center;
        padding: 50px 0;
    }
    .empty-state img {
        width: 150px;
        margin-bottom: 20px;
    }
    .btn-primary {
        display: inline-block;
        padding: 10px 20px;
        background-color: #2d884d;
        color: white;
        border-radius: 5px;
        text-decoration: none;
        margin-top: 10px;
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
<section id="orders" class="module">
    <div class="product-section">
        <h2 class="section-title">我的订单</h2>
        <div class="tabs">
            <div class="tab active" data-status="all">全部订单</div>
            <div class="tab" data-status="Pending">待支付</div>
            <div class="tab" data-status="Delivering">配送中</div>
            <div class="tab" data-status="Completed">已完成</div>
            <div class="tab" data-status="Cancelled">已取消</div>
        </div>
        <div class="tab-content">
            <div class="order-list">
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $order): ?>
                        <!-- 订单项 -->
                        <div class="order-item" 
                             data-order="<?= $order['id'] ?>" 
                             data-status="<?= $order['status'] ?>">
                            <div class="order-details">
                                <h3>订单编号：<?= $order['order_number'] ?></h3>
                                <p>下单时间：<?= $order['order_date'] ?></p>
                                <p>门店：<?= $order['store_name'] ?></p>
                                <p>商品：<?= $order['product_details'] ?></p>
                                <p>金额：¥<?= number_format($order['total_amount'], 2) ?></p>
                            </div>
                            <span class="order-status status-<?= strtolower($order['status']) ?>">
                                <?= [
                                    'Pending' => '待支付',
                                    'Delivering' => '配送中',
                                    'Completed' => '已完成',
                                    'Cancelled' => '已取消'
                                ][$order['status']] ?? $order['status'] ?>
                            </span>
                        </div>
                        
                        <!-- 订单详情 -->
                        <div class="order-detail-content" id="detail-<?= $order['id'] ?>">
                            <?php 
                            $orderDetails = getOrderDetails($order['id']);
                            foreach ($orderDetails['items'] as $item): 
                            ?>
                            <div class="detail-item">
                                <div class="detail-name"><?= $item['product_name'] ?></div>
                                <div class="detail-price">¥<?= number_format($item['unit_price'], 2) ?></div>
                                <div class="detail-quantity"><?= $item['quantity'] ?></div>
                                <div class="detail-total">¥<?= number_format($item['total'], 2) ?></div>
                            </div>
                            <?php endforeach; ?>
                            <div class="detail-summary">
                                合计：¥<?= number_format($orderDetails['final_amount'], 2) ?>
                                <?php if (isset($orderDetails['shipping_fee']) && $orderDetails['shipping_fee'] > 0): ?>
                                    （含配送费¥<?= number_format($orderDetails['shipping_fee'], 2) ?>）
                                <?php endif; ?>
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
            </div>
        </div>
    </div>
</section>

<script>
    // 订单标签切换
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // 更新标签状态
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // 筛选订单
            const status = this.getAttribute('data-status');
            document.querySelectorAll('.order-item').forEach(item => {
                const itemStatus = item.getAttribute('data-status');
                const detail = document.getElementById(`detail-${item.getAttribute('data-order')}`);
                
                if (status === 'all' || itemStatus === status) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                    detail.classList.remove('active'); // 隐藏时关闭详情
                }
            });
        });
    });

    // 订单详情展开/折叠
    document.querySelectorAll('.order-item').forEach(item => {
        item.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order');
            const detail = document.getElementById(`detail-${orderId}`);
            detail.classList.toggle('active');
        });
    });
</script>

</main>
</body>
</html>