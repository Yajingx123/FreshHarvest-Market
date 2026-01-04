<?php $pageTitle = "My orders"; ?>
<?php include 'header.php'; ?>

<?php
session_start();
require_once __DIR__ . '/inc/data.php';
// Check login status
if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true) {
    header('Location: ../login/login.php');
    exit();
}

$customerId = $_SESSION['customer_id'];
$currentOrderId = $_GET['order_id'] ?? 0;
$currentOrder = $currentOrderId ? getOrderDetails($currentOrderId) : null;
$currentStatus = $_GET['status'] ?? 'all';
$orders = getCustomerOrders($customerId, $currentStatus === 'all' ? null : $currentStatus);
$statusMap = [
    'Pending' => 'Pending',
    'Delivering' => 'Delivering',
    'Completed' => 'Completed',
    'Cancelled' => 'Cancelled',
    '已完成' => 'Completed',
    '处理中' => 'Processing',
    '已取消' => 'Cancelled',
    '待处理' => 'Pending',
    '已下单' => 'Ordered',
    '已收货' => 'Received'
];
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
    /* Expanded details styles */
    .order-detail-content {
        border: 1px solid #eee;
        border-top: none;
        border-radius: 0 0 8px 8px;
        padding: 0 15px;
        margin-top: -15px;
        background-color: #f0f7f2;
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
    /* Modal styles */
    .order-detail-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }
    .modal-content {
        background-color: white;
        padding: 30px;
        border-radius: 10px;
        max-width: 800px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
    }
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
    }
    .items-table th, .items-table td {
        padding: 10px;
        border: 1px solid #eee;
        text-align: center;
    }
    .items-table th {
        background-color: #f5f5f5;
        font-weight: 600;
    }
    .total-section {
        text-align: right;
        font-size: 16px;
        font-weight: bold;
        margin-top: 20px;
    }
    .close-modal {
        display: block;
        margin: 20px auto 0;
        padding: 10px 30px;
        background-color: #2d884d;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
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
        .tabs {
            flex-wrap: wrap;
        }
        .tab {
            padding: 8px 15px;
            font-size: 14px;
        }
    }
</style>

<!-- Orders -->
<main class="order-page">
    <section class="product-section">
        <h2 class="section-title">My Orders</h2>
        
        <!-- Order status tabs -->
        <div class="tabs">
            <div class="tab <?= ($currentStatus === 'all') ? 'active' : '' ?>" data-status="all">All orders</div>
            <div class="tab <?= ($currentStatus === 'Pending') ? 'active' : '' ?>" data-status="Pending">Pending</div>
            <div class="tab <?= ($currentStatus === 'Delivering') ? 'active' : '' ?>" data-status="Delivering">Delivering</div>
            <div class="tab <?= ($currentStatus === 'Completed') ? 'active' : '' ?>" data-status="Completed">Completed</div>
            <div class="tab <?= ($currentStatus === 'Cancelled') ? 'active' : '' ?>" data-status="Cancelled">Cancelled</div>
        </div>
        
        <div class="tab-content">
            <div class="order-list">
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $statusLabel = $statusMap[$order['status']] ?? $order['status'];
                        $statusKey = strtolower(str_replace(' ', '-', $statusLabel));
                        ?>
                        <div class="order-item" 
                             data-order="<?= $order['id'] ?>" 
                             data-status="<?= $statusLabel ?>">
                            <div class="order-details">
                                <h3>Order #: <?= $order['order_number'] ?></h3>
                                <p>Order time: <?= $order['order_date'] ?></p>
                                <p>Store: <?= $order['store_name'] ?></p>
                                <p>Items: <?= $order['product_details'] ?></p>
                                <p>Amount: ¥<?= number_format($order['total_amount'], 2) ?></p>
                            </div>
                            <span class="order-status status-<?= $statusKey ?>">
                                <?= $statusLabel ?>
                            </span>
                        </div>
                        
                        <!-- Inline order details -->
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
                                Total: ¥<?= number_format($orderDetails['final_amount'], 2) ?>
                                <?php if (isset($orderDetails['shipping_fee']) && $orderDetails['shipping_fee'] > 0): ?>
                                    (Includes delivery fee ¥<?= number_format($orderDetails['shipping_fee'], 2) ?>)
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                      <p>You don't have any orders yet. Start shopping!</p>
                       <a href="products.php" class="btn-primary">Browse products</a>
                     </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if ($currentOrder): ?>
        <!-- Modal order details -->
        <section class="order-detail-modal">
            <div class="modal-content">
                <h3>Order details: #<?= $currentOrder['order_number'] ?></h3>
                <div class="detail-section">
                    <h4>Order info</h4>
                    <p>Order #: <?= $currentOrder['order_number'] ?></p>
                    <p>Order time: <?= $currentOrder['order_date'] ?></p>
                    <p>Store: <?= $currentOrder['store_name'] ?></p>
                    <p>Address: <?= $currentOrder['shipping_address'] ?? 'Not set' ?></p>
                    <p>Status: <?= $statusMap[$currentOrder['order_status']] ?? $currentOrder['order_status'] ?></p>
                </div>

                <div class="detail-section">
                    <h4>Items</h4>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Unit Price</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
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
                    <p>Subtotal: ¥<?= number_format($currentOrder['total_amount'], 2) ?></p>
                    <p>Total: ¥<?= number_format($currentOrder['final_amount'], 2) ?></p>
                    <?php if (isset($currentOrder['shipping_fee']) && $currentOrder['shipping_fee'] > 0): ?>
                        <p>Delivery fee: ¥<?= number_format($currentOrder['shipping_fee'], 2) ?></p>
                    <?php endif; ?>
                </div>

                <button class="close-modal" onclick="window.location='orders.php?status=<?= htmlspecialchars($currentStatus) ?>'">Close</button>
            </div>
        </section>
    <?php endif; ?>
</main>

<script>
    // Status tab switching
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // Update active tab
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Navigate to selected status
            const status = this.getAttribute('data-status');
            window.location.href = 'orders.php?status=' + status;
        });
    });

    // Toggle inline order details
    document.querySelectorAll('.order-item').forEach(item => {
        item.addEventListener('click', function(e) {
            // Avoid toggling when clicking links
            if (e.target.tagName === 'A') return;
            
            const orderId = this.getAttribute('data-order');
            const detail = document.getElementById(`detail-${orderId}`);
            
            // Close other details
            document.querySelectorAll('.order-detail-content').forEach(d => {
                if (d !== detail) d.classList.remove('active');
            });
            
            // Toggle current detail
            detail.classList.toggle('active');
        });
    });
    
    // Auto-open details if order_id is provided
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const orderId = urlParams.get('order_id');
        const status = urlParams.get('status') || 'all';
        
        if (orderId) {
            // Keep inline expand when order_id is present
            const detail = document.getElementById(`detail-${orderId}`);
            if (detail) {
                // Scroll to order
                detail.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                // Expand detail
                detail.classList.add('active');
            }
        }
    });
</script>

</main>
</body>
</html>
