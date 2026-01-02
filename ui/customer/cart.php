<?php 
session_start();
require_once __DIR__ . '/inc/data.php';    // 包含data.php

$cart_item = [];
$query = "SELECT * FROM v_wishlist_products WHERE customer_id = ?";
$conn = getDBConnection();
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_item = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (isset($_POST['action']) && $_POST['action'] === 'checkout_ajax') {
    header('Content-Type: application/json');
    
    // 验证用户登录状态
    if (!isset($_SESSION['customer_id'])) {
        echo json_encode(['success' => false, 'error' => '请先登录']);
        exit;
    }
    
    $customer_id = $_SESSION['customer_id'];
    $shipping_address = $_POST['shipping_address'] ?? '';
    
    if (empty($shipping_address)) {
        echo json_encode(['success' => false, 'error' => '请填写收货地址']);
        exit;
    }
    
    // 获取购物车数据（仅用于验证）
    $cart_data = getShoppingCartData($customer_id);
    
    // 调试信息
    error_log("结账请求 - customer_id: $customer_id, cart_id: " . ($cart_data['cart_id'] ?? 'null'));
    error_log("购物车项目数: " . count($cart_data['items']));

    // 简单验证
    if (!$cart_data['cart_id']) {
        echo json_encode(['success' => false, 'error' => '购物车不存在']);
        exit;
    }

    if (count($cart_data['items']) <= 0) {
        echo json_encode(['success' => false, 'error' => '购物车无商品']);
        exit;
    }
    
    $cart_id = $cart_data['cart_id'];
    $conn = getDBConnection();
    
    try {
        // 调用存储过程
        $stmt = $conn->prepare("CALL ProcessCustomerOrder(?, ?, ?, @success, @message, @order_id)");
        $stmt->bind_param("isi", $customer_id, $shipping_address, $cart_id);
        
        if (!$stmt->execute()) {
            throw new Exception("存储过程执行失败: " . $conn->error);
        }
        
        $stmt->close();
        
        // 获取结果
        $result = $conn->query("SELECT @success as success, @message as message, @order_id as order_id");
        
        if (!$result) {
            throw new Exception("无法获取存储过程结果: " . $conn->error);
        }
        
        $row = $result->fetch_assoc();
        $result->close();
        
        if (!$row) {
            throw new Exception("存储过程无返回结果");
        }
        
        // 记录详细的返回信息
        error_log("存储过程返回 - success: " . ($row['success'] ? 'true' : 'false') . 
                  ", message: " . $row['message'] . 
                  ", order_id: " . $row['order_id']);
        
        // 返回存储过程的结果
        echo json_encode([
            'success' => (bool)$row['success'],
            'message' => $row['message'],
            'order_id' => $row['order_id']
        ]);
        
    } catch (mysqli_sql_exception $e) {
        error_log("结账异常: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => '数据库错误：' . $e->getMessage()
        ]);
    } catch (Exception $e) {
        error_log("结账错误: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    } finally {
        if ($conn) {
            $conn->close();
        }
    }
    exit;
}

// 检查登录
if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true) {
    header('Location: ../login/login.php');
    exit();
}

// 获取顾客信息并确保返回数组
$customer_info = getCustomerFullInfo();
// 处理可能的null值，设置默认空数组
if ($customer_info === null) {
    $customer_info = [];
}
// 为必要字段设置默认值，避免未定义索引错误
$customer_info = array_merge([
    'full_name' => '',
    'customer_phone' => '',
    'email' => '',
    'phone' => '' 
], $customer_info);

// 获取购物车数据
$cart_data = getShoppingCartData($_SESSION['customer_id'] ?? null);
// 从cart_data中提取所需数据（适配新函数返回结构）
$cart_items = $cart_data['items'];
$cart_id = $cart_data['cart_id'];
$cart_total_items = $cart_data['total_items'];
$cart_total_quantity = $cart_data['total_quantity'];
$cart_total_amount = $cart_data['total_amount'];
$cart_Final_Amount = $cart_data['finanl_Amount'];
$cart_discount_rate = $cart_data['discount'];
$has_cart_items = $cart_data['has_items'];
$discount_amount = $cart_total_amount * $cart_discount_rate;

// 处理购物车操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_quantity':
                $item_id = $_POST['item_id'];
                $quantity = intval($_POST['quantity']);
                
                if ($quantity > 0 && $cart_id) {
                    $query = "UPDATE OrderItem SET quantity = ? WHERE item_ID = ? AND order_ID = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("isi", $quantity, $item_id, $cart_id);
                    $stmt->execute();
                    $stmt->close();
                    
                    // 更新订单总金额
                    $update_total_query = "UPDATE CustomerOrder SET total_amount = (
                        SELECT SUM(quantity * unit_price) FROM OrderItem WHERE order_id = ?
                    ) WHERE order_id = ?";
                    $update_stmt = $conn->prepare($update_total_query);
                    $update_stmt->bind_param("ii", $cart_id, $cart_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
                break;
                
            case 'remove_item':
                $item_id = $_POST['item_id'];
                
                if ($cart_id) {
                    $query = "DELETE FROM OrderItem WHERE item_ID = ? AND order_ID = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("si", $item_id, $cart_id);
                    $stmt->execute();
                    $stmt->close();
                    
                    // 检查购物车是否为空
                    $query2 = "SELECT COUNT(*) as item_count FROM OrderItem WHERE order_ID = ?";
                    $stmt2 = $conn->prepare($query2);
                    $stmt2->bind_param("i", $cart_id);
                    $stmt2->execute();
                    $result = $stmt2->get_result();
                    $row = $result->fetch_assoc();
                    $stmt2->close();
                    
                    if ($row['item_count'] == 0) {
                        // 删除空购物车
                        $query3 = "DELETE FROM CustomerOrder WHERE order_ID = ?";
                        $stmt3 = $conn->prepare($query3);
                        $stmt3->bind_param("i", $cart_id);
                        $stmt3->execute();
                        $stmt3->close();
                    } else {
                        // 更新订单总金额
                        $update_total_query = "UPDATE CustomerOrder SET total_amount = (
                            SELECT SUM(quantity * unit_price) FROM OrderItem WHERE order_id = ?
                        ) WHERE order_id = ?";
                        $update_stmt = $conn->prepare($update_total_query);
                        $update_stmt->bind_param("ii", $cart_id, $cart_id);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                }
                break;
        }
    }
    $conn->close();
    
    // 刷新页面
    header('Location: cart.php');
    exit();
}


?>

<?php $pageTitle = "购物车"; ?>
<?php include 'header.php'; ?>

<style>
    /* 基础样式优化 */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        background-color: #f5f7f4;
        color: #333;
    }
    
    .product-section {
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.04);
        margin: 25px auto;
        max-width: 1400px;
        overflow: hidden;
    }
    
    .section-title {
        font-size: 19px;
        font-weight: 600;
        color: #2d884d;
        border-left: 3px solid #2d884d;
        padding: 8px 15px;
        margin-bottom: 22px;
        display: inline-block;
    }
    
    .dashboard {
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.04);
        padding: 25px;
        margin-bottom: 30px;
    }
    
    .account-form {
        max-width: 100%;
        padding: 0;
    }
    
    .form-group {
        margin-bottom: 18px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    
    .form-label {
        font-weight: 500;
        color: #555;
        font-size: 14px;
    }
    
    .form-input {
        padding: 11px 15px;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        font-size: 15px;
        width: 100%;
        background-color: #fafafa;
        transition: all 0.3s;
    }
    
    .form-input:focus {
        border-color: #2d884d;
        background-color: #fff;
        outline: none;
        box-shadow: 0 0 0 2px rgba(45, 136, 77, 0.1);
    }
    
    /* 购物车布局重构 */
    #cart .product-section {
        display: flex;
        height: calc(100vh - 160px);
    }
    
    /* 配送地址侧边栏 */
    .address-sidebar {
        width: 340px;
        flex-shrink: 0;
        background-color: #f9faf9;
        padding: 28px 25px;
        height: 100%;
        overflow-y: auto;
        border-right: 1px solid #f0f0f0;
    }
    
    /* 购物车主内容区 */
    .cart-main {
        flex: 1;
        padding: 28px 30px;
        height: 100%;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-height: 400px; /* 添加最小高度 */
    }
    
    /* 购物车列表区域 */
    .cart-list-wrapper {
        flex: 1;
        overflow-y: auto;
        margin-bottom: 20px;
        padding-right: 10px;
        min-height: 150px; /* 添加最小高度 */
    }
    
    /* 购物车项目样式优化 */
    .cart-item {
        display: flex;
        align-items: center;
        padding: 18px 15px;
        border-bottom: 1px solid #f5f5f5;
        transition: background-color 0.2s;
    }
    
    .cart-item:hover {
        background-color: #fafafa;
    }
    
    .cart-item-info {
        flex: 1;
        padding-right: 15px;
    }
    
    .cart-item-name {
        font-weight: 500;
        margin-bottom: 6px;
        color: #333;
        font-size: 16px;
    }
    
    .cart-item-price {
        color: #ff4d4f;
        font-weight: bold;
        font-size: 15px;
    }
    
    .cart-item-quantity {
        display: flex;
        align-items: center;
        margin-right: 25px;
    }
    
    .quantity-btn {
        width: 32px;
        height: 32px;
        border: 1px solid #e0e0e0;
        background-color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border-radius: 4px;
        transition: all 0.2s;
    }
    
    .quantity-btn:hover {
        background-color: #f5f5f5;
    }
    
    .quantity-input {
        width: 55px;
        height: 32px;
        text-align: center;
        border: 1px solid #e0e0e0;
        border-left: none;
        border-right: none;
        font-size: 15px;
    }
    
    .remove-from-cart {
        color: #999;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 14px;
        padding: 5px 10px;
        transition: color 0.2s;
    }
    
    .remove-from-cart:hover {
        color: #ff4d4f;
    }
    
    /* 结算栏优化 */
    .checkout-divider {
        height: 1px;
        background-color: #f0f0f0;
        margin: 0 -30px 20px -30px;
    }
    
    .checkout-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 30px;
        background-color: white;
        border-top: 1px solid #f0f0f0;
        position: sticky;
        bottom: 0;
        width: 100%;
        margin: 0 -30px;
        margin-top: auto;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.02);
        /* z-index: 10; */
    }
    
    .total-amount {
        font-size: 18px;
        font-weight: bold;
        color: #333;
    }
    
    .total-amount span {
        color: #ff4d4f;
        margin-left: 8px;
    }
    
    .checkout-btn {
        padding: 12px 24px;
        background-color: #2d884d;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s;
        font-weight: 500;
    }
    
    .checkout-btn:hover {
        background-color: #236b3c;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(45, 136, 77, 0.2);
    }
    
    .checkout-btn:disabled {
        background-color: #cccccc;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    /* 弹窗样式优化 */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        visibility: hidden;
        opacity: 0;
        transition: visibility 0s linear 0.25s, opacity 0.25s;
    }
    
    .modal-overlay.active {
        visibility: visible;
        opacity: 1;
        transition-delay: 0s;
    }
    
    .modal {
        background-color: white;
        border-radius: 12px;
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        transform: translateY(10px);
        transition: transform 0.3s;
    }
    
    .modal-overlay.active .modal {
        transform: translateY(0);
    }
    
    .modal-close {
        position: absolute;
        top: 20px;
        right: 20px;
        background: none;
        border: none;
        font-size: 22px;
        cursor: pointer;
        color: #777;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    
    .modal-close:hover {
        background-color: #f5f5f5;
        color: #333;
    }
    
    .modal-title {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 25px;
        color: #2d884d;
        padding-bottom: 10px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .order-details {
        margin-bottom: 25px;
    }
    
    .detail-section {
        margin-bottom: 22px;
    }
    
    .detail-title {
        font-weight: 600;
        margin-bottom: 12px;
        color: #555;
        border-bottom: 1px solid #f0f0f0;
        padding-bottom: 8px;
        font-size: 16px;
    }
    
    .payment-methods {
        display: flex;
        gap: 15px;
        margin-bottom: 25px;
    }
    
    .payment-option {
        flex: 1;
        padding: 18px 15px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        position: relative;
    }
    
    .payment-option.selected {
        border-color: #2d884d;
        background-color: rgba(45, 136, 77, 0.05);
    }
    
    .payment-option.selected::after {
        content: "✓";
        position: absolute;
        bottom: 8px;
        right: 8px;
        color: #2d884d;
        font-weight: bold;
    }
    
    .submit-order-btn {
        width: 100%;
        padding: 14px;
        background-color: #2d884d;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .submit-order-btn:hover {
        background-color: #236b3c;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(45, 136, 77, 0.2);
    }
    
    .success-message {
        text-align: center;
        padding: 40px 0;
    }
    
    .success-icon {
        font-size: 70px;
        color: #2d884d;
        margin-bottom: 25px;
    }
    
    .success-text {
        font-size: 24px;
        color: #333;
        font-weight: 500;
    }
    
    /* 商品图片样式优化 */
    .product-img {
        width: 85px;
        height: 85px;
        margin-right: 18px;
        flex-shrink: 0;
        background-color: #f8f8f8;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
    }
    
    /* 响应式优化 */
    @media (max-width: 992px) {
        #cart .product-section {
            flex-direction: column;
            height: auto !important;
            max-height: calc(100vh - 155px);
        }
        
        .address-sidebar {
            width: 100%;
            max-height: 320px;
            border-right: none;
            border-bottom: 1px solid #f0f0f0;
            padding: 20px 20px;
        }
        
        .cart-main {
            width: 100%;
            padding: 20px 20px;
        }
        
        .cart-item {
            padding: 15px 10px;
        }
        
        .checkout-divider {
            margin: 0 -20px 15px -20px;
        }
        
        .checkout-bar {
            padding: 15px 20px;
            margin: 0 -20px;
        }
    }
    
    @media (max-width: 576px) {
        .address-sidebar {
            width: 100%;
            max-height: none;
        }
        
        .cart-item {
            flex-wrap: wrap;
        }
        
        .cart-item-quantity {
            margin-right: 10px;
            margin-top: 10px;
        }
        
        .payment-methods {
            flex-direction: column;
        }
    }
</style>

<!-- 购物车 -->
<section id="cart" class="module">
    <div class="product-section">
        <!-- 配送地址侧边栏 -->
        <div class="address-sidebar">
            <h2 class="section-title">配送地址确认</h2>
            <form class="account-form">
                <div class="form-group">
                    <label class="form-label" for="receiver-name">收件人</label>
                    <input type="text" id="receiver-name" class="form-input" readonly 
       value="<?php echo htmlspecialchars($customer_info['full_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="receiver-phone">联系电话</label>
                    <input type="text" id="receiver-phone" class="form-input" readonly 
       value="<?php echo htmlspecialchars($customer_info['customer_phone'] ?? $customer_info['phone'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="receiver-email">邮箱地址</label>
                    <input type="text" id="receiver-email" class="form-input" readonly 
       value="<?php echo htmlspecialchars($customer_info['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="receiver-address">详细地址</label>
                    <input type="text" id="receiver-address" class="form-input" readonly value="<?php echo htmlspecialchars($customer_info['address'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="receiver-note">备注信息</label>
                    <textarea id="receiver-note" class="form-input" rows="3" style="resize: none;">放门口即可</textarea>
                </div>
            </form>
        </div>

        <!-- 购物车主内容区 -->
        <div class="cart-main">
            <h2 class="section-title">我的购物车</h2>
            
            <!-- 购物车商品列表 -->
            <div class="cart-list-wrapper">
                <?php if (!$has_cart_items): // 使用has_cart_items判断 ?>
                    <div style="text-align: center; padding: 50px 0; color: #999;">
                        购物车为空
                    </div>
                <?php else: ?>
                    <?php foreach ($cart_item as $item): ?>
                        <div class="cart-item" data-item-id="<?php echo $item['item_id']; ?>">
                            <div class="product-img"> 
                                <?php 
                                  
                                ?>
                            </div>
                            <div class="cart-item-info">
                                <div class="cart-item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                <div class="cart-item-price">¥<?php echo number_format($item['unit_price'], 2); ?></div>
                            </div>
                            <div class="cart-item-quantity">
                                <form method="POST" action="cart.php" style="display: flex;">
                                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                    <button type="button" class="quantity-btn minus" 
                                            onclick="this.nextElementSibling.value = Math.max(1, parseInt(this.nextElementSibling.value)-1); this.form.quantity.value = this.nextElementSibling.value; this.form.submit();">-</button>
                                    <input type="number" class="quantity-input" 
                                           value="<?php echo $item['quantity']; ?>" min="1" readonly>
                                    <button type="button" class="quantity-btn plus" 
                                            onclick="this.previousElementSibling.value = parseInt(this.previousElementSibling.value)+1; this.form.quantity.value = this.previousElementSibling.value; this.form.submit();">+</button>
                                    <input type="hidden" name="quantity" value="<?php echo $item['quantity']; ?>">
                                    <input type="hidden" name="action" value="update_quantity">
                                </form>
                            </div>
                            <form method="POST" action="cart.php" style="display: inline;">
                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                <input type="hidden" name="action" value="remove_item">
                                <button type="submit" class="remove-from-cart">删除</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- 分隔线 -->
            <div class="checkout-divider"></div>
            
            <!-- 结算栏 -->
            <div class="checkout-bar">
                <div style="text-align: right;">
                    <div>原价：<span style="text-decoration: line-through; color: #999;">¥<?php echo number_format($cart_total_amount, 2); ?></span></div>
                    <?php if ($cart_discount_rate > 0): ?>
                        <div>折扣(<?php echo $cart_discount_rate * 100; ?>%):<span style="color: #ff4d4f;">-¥<?php echo number_format($discount_amount, 2); ?></span></div>
                    <?php endif; ?>
                    <div class="total-amount">实付：<span>¥<?php echo number_format($cart_Final_Amount, 2); ?></span></div>
                </div>
                <button class="checkout-btn" type="button" <?php echo !$has_cart_items ? 'disabled' : ''; ?>>结算</button>
            </div>
        </div> 
    </div> 
</section>
<!-- 结算弹窗 -->
<div class="modal-overlay" id="checkoutModal">
    <div class="modal">
        <button class="modal-close">&times;</button>
        <h3 class="modal-title">订单确认</h3>
        <div class="order-details">
            <div class="detail-section">
                <h4 class="detail-title">商品明细</h4>
                <div id="order-items"></div>
            </div>
            
            <div class="detail-section">
                <h4 class="detail-title">收货信息</h4>
                <div id="shipping-info"></div>
            </div>
            <div class="detail-section">
    <h4 class="detail-title">订单总额</h4>
    <div style="margin-bottom: 8px;">原价：<span style="text-decoration: line-through; color: #999;">¥<?php echo number_format($cart_total_amount, 2); ?></span></div>
    <?php if ($cart_discount_rate > 0): ?>
        <div style="margin-bottom: 8px;">会员折扣<?php echo $cart_discount_rate * 100; ?>%:<span style="color: #ff4d4f;">-¥<?php echo number_format($discount_amount, 2); ?></span></div>
    <?php endif; ?>
    <div id="order-total" class="cart-item-price">实付：¥<?php echo number_format($cart_Final_Amount, 2); ?></div>
</div>
        </div>
        
        <div class="detail-section">
            <h4 class="detail-title">支付方式</h4>
            <div class="payment-methods">
                <div class="payment-option selected" data-method="wechat">
                    <div>微信支付</div>
                </div>
                <div class="payment-option" data-method="alipay">
                    <div>支付宝</div>
                </div>
            </div>
        </div>
        
        <button class="submit-order-btn">提交订单</button>
    </div>
</div>

<!-- 修改成功弹窗内容 -->
<div class="modal-overlay" id="successModal">
    <div class="modal">
        <button class="modal-close">&times;</button>
        <div class="success-message">
            <div class="success-icon">✓</div>
            <h3 class="modal-title">支付成功</h3>
            <p>您的订单已支付完成，订单号：<span id="success-order-id"></span></p>
            <p style="margin-top: 15px;">
                <span id="countdown">3</span>秒后将自动返回购物车页面
            </p>
        </div>
    </div>
</div>

<script>
    // 修改 JavaScript 代码开头部分
document.addEventListener('DOMContentLoaded', function() {
    // 弹窗相关元素
    const checkoutModal = document.getElementById('checkoutModal');
    const successModal = document.getElementById('successModal');
    const checkoutBtn = document.querySelector('.checkout-btn');
    const closeButtons = document.querySelectorAll('.modal-close');
    const submitOrderBtn = document.querySelector('.submit-order-btn');
    const paymentOptions = document.querySelectorAll('.payment-option');
    const countdownElement = document.getElementById('countdown');

    // 检查元素是否存在
    if (!checkoutBtn) {
        console.error('结算按钮未找到！');
        return;
    }
    
    console.log('结算按钮找到，开始绑定事件...');

    // 打开结算弹窗
    checkoutBtn.addEventListener('click', function() {
        console.log('结算按钮被点击');
        populateOrderDetails();
        checkoutModal.classList.add('active');
    });

    // 关闭弹窗
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            checkoutModal.classList.remove('active');
            successModal.classList.remove('active');
        });
    });

    // 点击弹窗外部关闭
    window.addEventListener('click', function(event) {
        if (event.target === checkoutModal) {
            checkoutModal.classList.remove('active');
        }
        if (event.target === successModal) {
            successModal.classList.remove('active');
        }
    });

    // 选择支付方式
    paymentOptions.forEach(option => {
        option.addEventListener('click', function() {
            paymentOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
        });
    });

    // 提交订单（AJAX版本）
    submitOrderBtn.addEventListener('click', function() {
        // 获取表单数据
        const receiverName = document.getElementById('receiver-name').value;
        const receiverPhone = document.getElementById('receiver-phone').value;
        const receiverAddress = document.getElementById('receiver-address').value;
        const receiverNote = document.getElementById('receiver-note').value;
    
        // 验证必要信息
        if (!receiverName || !receiverPhone || !receiverAddress) {
            alert('请填写完整的收货信息');
            return;
        }
        
        // 检查购物车项目
        const cartItems = document.querySelectorAll('.cart-item');
        if (cartItems.length === 0) {
            alert('购物车为空，无法提交订单');
            this.disabled = false;
            this.textContent = '提交订单';
            return;
        }
    
        // 修改这里：移除省份字段，因为HTML中没有这个字段
        const shippingAddress = `${receiverAddress} (${receiverName}, ${receiverPhone}) 备注: ${receiverNote}`;
    
        // 显示加载状态
        this.disabled = true;
        this.textContent = '处理中...';
    
        // AJAX提交订单
        fetch('cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'action': 'checkout_ajax',
                'shipping_address': shippingAddress
            })
        })
        .then(response => response.text())
        .then(text => {
            console.log('完整响应内容:', text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    // 关闭结算弹窗
                    checkoutModal.classList.remove('active');
                    document.getElementById('success-order-id').textContent = data.order_id || '未知';
                    // 显示成功弹窗
                    successModal.classList.add('active');
                    
                    // 倒计时功能
                    let countdown = 3;
                    countdownElement.textContent = countdown;
                    const countdownInterval = setInterval(() => {
                        countdown--;
                        countdownElement.textContent = countdown;
                        if (countdown <= 0) {
                            clearInterval(countdownInterval);
                            window.location.reload();
                        }
                    }, 1000);
                    
                } else {
                    alert(data.error || '订单提交失败');
                }
            } catch (e) {
                console.error('JSON解析失败:', e);
                alert('服务器响应格式错误');
            }
        })
        .finally(() => {
            // 恢复按钮状态
            this.disabled = false;
            this.textContent = '提交订单';
        });
    });
});

// 填充订单详情
function populateOrderDetails() {
    const orderItemsContainer = document.getElementById('order-items');
    const shippingInfoContainer = document.getElementById('shipping-info');
    const orderTotalContainer = document.getElementById('order-total');
    
    // 清空现有内容
    orderItemsContainer.innerHTML = '';
    
    // 获取购物车项目
    const cartItems = document.querySelectorAll('.cart-item');
    if (cartItems.length === 0) {
        orderItemsContainer.innerHTML = '<p>购物车为空</p>';
        return;
    }
    
    // 填充商品明细
    cartItems.forEach(item => {
        const name = item.querySelector('.cart-item-name').textContent;
        const price = item.querySelector('.cart-item-price').textContent.replace('¥', '');
        const quantity = item.querySelector('.quantity-input').value;
        const itemTotal = (parseFloat(price) * parseInt(quantity)).toFixed(2);
        
        const itemElement = document.createElement('div');
        itemElement.style.padding = '8px 0';
        itemElement.style.display = 'flex';
        itemElement.style.justifyContent = 'space-between';
        itemElement.innerHTML = `
            <span>${name} x ${quantity}</span>
            <span>¥${itemTotal}</span>
        `;
        orderItemsContainer.appendChild(itemElement);
    });
    
    // 填充收货信息 - 修改这里，移除省份字段
    const receiverName = document.getElementById('receiver-name').value;
    const receiverPhone = document.getElementById('receiver-phone').value;
    const receiverAddress = document.getElementById('receiver-address').value;
    const receiverNote = document.getElementById('receiver-note').value;
    
    shippingInfoContainer.innerHTML = `
        <p>收件人：${receiverName}</p>
        <p>联系电话：${receiverPhone}</p>
        <p>地址：${receiverAddress}</p>
        <p>备注：${receiverNote}</p>
    `;
    
    // 填充总金额
    orderTotalContainer.textContent = document.querySelector('.total-amount span').textContent;
}
</script>

</main>
</body>
</html>