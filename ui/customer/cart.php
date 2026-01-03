<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/inc/data.php';    // 包含data.php

$customer_id = $_SESSION['customer_id'] ?? null;

// 获取所有购物车数据（多个门店）
$all_carts_data = getAllShoppingCarts($customer_id);
$has_cart_items = $all_carts_data['total_carts'] > 0;

// 获取详细的购物车项目用于显示
$cart_items_detailed = [];
$total_all_amount = 0;
$total_discount = 0;
$all_order_ids = [];

if ($has_cart_items) {
    // 收集所有订单的项目
    foreach ($all_carts_data['carts'] as $cart) {
        $order_id = $cart['order_id'];
        $all_order_ids[] = $order_id;
        
        // 获取该订单的详细项目
        $order_details = getOrderDetails($order_id);
        if ($order_details && !empty($order_details['items'])) {
            foreach ($order_details['items'] as $item) {
                $cart_items_detailed[] = [
                    'order_id' => $order_id,
                    'branch_name' => $cart['branch_name'],
                    'item_id' => $item['item_ID'],
                    'product_id' => $item['item_ID'],
                    'product_name' => $item['product_name'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'item_total' => $item['total'],
                    'stock_status' => '已锁定' // 默认状态
                ];
            }
        }
        $total_all_amount += $cart['final_amount'];
    }
    
    // 计算总折扣
    $discount_rate = getDiscountRate($customer_id);
    $original_total = 0;
    foreach ($cart_items_detailed as $item) {
        $original_total += $item['unit_price'] * $item['quantity'];
    }
    $total_discount = $original_total * $discount_rate;
}

// 处理多个订单一起结账的函数
function processMultipleOrders($customer_id, $shipping_address, $order_ids) {
    $conn = getDBConnection();
    $results = [];
    
    foreach ($order_ids as $order_id) {
        if ($order_id > 0) {
            try {
                $stmt = $conn->prepare("CALL ProcessCustomerOrder(?, ?, ?, @success, @message, @new_order_id)");
                $stmt->bind_param("isi", $customer_id, $shipping_address, $order_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("存储过程执行失败: " . $conn->error);
                }
                
                $stmt->close();
                
                $result = $conn->query("SELECT @success as success, @message as message, @new_order_id as order_id");
                $row = $result->fetch_assoc();
                $result->close();
                
                $results[] = [
                    'order_id' => $order_id,
                    'success' => (bool)$row['success'],
                    'message' => $row['message'],
                    'new_order_id' => $row['order_id']
                ];
                
            } catch (Exception $e) {
                $results[] = [
                    'order_id' => $order_id,
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }
    }
    
    $conn->close();
    return $results;
}

// AJAX结账处理
if (isset($_POST['action']) && $_POST['action'] === 'checkout_ajax') {
    
    if (!isset($_SESSION['customer_id'])) {
        echo json_encode(['success' => false, 'error' => '请先登录']);
        exit;
    }
    
    $shipping_address = $_POST['shipping_address'] ?? '';
    if (empty($shipping_address)) {
        echo json_encode(['success' => false, 'error' => '请填写收货地址']);
        exit;
    }
    
    $customer_id = $_SESSION['customer_id'];
    
    // 获取订单ID数组
    $order_ids = [];
    if (isset($_POST['order_ids'])) {
        $order_ids = json_decode($_POST['order_ids'], true);
    } else {
        // 如果未传递order_ids，从session或数据库中获取当前用户的所有待处理订单
        $all_carts = getAllShoppingCarts($customer_id);
        foreach ($all_carts['carts'] as $cart) {
            $order_ids[] = $cart['order_id'];
        }
    }
    
    // 处理所有订单
    $results = processMultipleOrders($customer_id, $shipping_address, $order_ids);
    
    // 检查所有订单是否都处理成功
    $all_success = true;
    foreach ($results as $result) {
        if (!$result['success']) {
            $all_success = false;
            break;
        }
    }
    
    // 返回结果
    echo json_encode([
        'success' => $all_success,
        'message' => $all_success ? '订单提交成功' : '部分订单处理失败',
        'results' => $results,
        'processed_count' => count($results)
    ]);
    exit;
}

$customer_info = getCustomerFullInfo();
if ($customer_info === null) {
    $customer_info = [];
}
$customer_info = array_merge([
    'full_name' => '',
    'customer_phone' => '',
    'email' => '',
    'phone' => '',
    'address' => ''
], $customer_info);

// 处理删除购物车
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'delete_cart') {
        $order_id = $_POST['order_id'] ?? 0;
        $customer_id = $_SESSION['customer_id'] ?? 0;
        
        if ($order_id > 0 && $customer_id > 0) {
            $conn = getDBConnection();
            
            // 1. 解锁库存
            $unlock_query = "UPDATE Inventory inv
                           JOIN StockItem si ON inv.batch_ID = si.batch_ID 
                              AND inv.product_ID = si.product_ID 
                              AND inv.branch_ID = si.branch_ID
                           SET inv.locked_inventory = inv.locked_inventory - 1
                           WHERE si.customer_order_ID = ? 
                             AND si.status = 'pending'";
            $stmt1 = $conn->prepare($unlock_query);
            $stmt1->bind_param("i", $order_id);
            $stmt1->execute();
            $stmt1->close();
            
            // 2. 恢复StockItem状态
            $update_stock_query = "UPDATE StockItem 
                                  SET status = 'in_stock', customer_order_ID = NULL 
                                  WHERE customer_order_ID = ? AND status = 'pending'";
            $stmt2 = $conn->prepare($update_stock_query);
            $stmt2->bind_param("i", $order_id);
            $stmt2->execute();
            $stmt2->close();
            
            // 3. 删除OrderItem
            $delete_items_query = "DELETE FROM OrderItem WHERE order_ID = ?";
            $stmt3 = $conn->prepare($delete_items_query);
            $stmt3->bind_param("i", $order_id);
            $stmt3->execute();
            $stmt3->close();
            
            // 4. 删除CustomerOrder
            $delete_order_query = "DELETE FROM CustomerOrder WHERE order_ID = ? AND customer_id = ?";
            $stmt4 = $conn->prepare($delete_order_query);
            $stmt4->bind_param("ii", $order_id, $customer_id);
            $stmt4->execute();
            $stmt4->close();
            
            $conn->close();
        }
    }elseif ($_POST['action'] === 'remove_item') {
        // 删除单个商品
        $item_id = $_POST['item_id'] ?? '';
        
        if (!empty($item_id) && $customer_id > 0) {
            $result = removeItemFromCart($item_id, $customer_id);
            
            if ($result['success']) {
                $_SESSION['success_message'] = $result['message'];
            } else {
                $_SESSION['error_message'] = $result['message'];
            }
            
            header('Location: cart.php');
            exit();
        }
    }
    
    header('Location: cart.php');
    exit();
}
?>


<?php $pageTitle = "Shopping Cart"; ?>
<?php include 'header.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
    /* 美观的确认弹窗样式 */
.confirm-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    visibility: hidden;
    opacity: 0;
    transition: all 0.3s ease;
}

.confirm-modal-overlay.active {
    visibility: visible;
    opacity: 1;
}

.confirm-modal {
    background-color: white;
    border-radius: 16px;
    width: 90%;
    max-width: 400px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    transform: translateY(20px) scale(0.95);
    transition: all 0.3s ease;
    overflow: hidden;
}

.confirm-modal-overlay.active .confirm-modal {
    transform: translateY(0) scale(1);
}

.confirm-modal-icon {
    text-align: center;
    padding: 30px 20px 20px;
    background: linear-gradient(135deg, #ff6b6b, #ff4757);
    color: white;
}

.confirm-modal-icon i {
    font-size: 48px;
    margin-bottom: 10px;
}

.confirm-modal-content {
    padding: 30px;
    text-align: center;
}

.confirm-modal-title {
    font-size: 22px;
    font-weight: 600;
    color: #333;
    margin-bottom: 15px;
    font-family: 'PingFang SC', 'Microsoft YaHei', sans-serif;
}

.confirm-modal-message {
    font-size: 16px;
    color: #666;
    line-height: 1.6;
    margin-bottom: 30px;
}

.confirm-modal-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.confirm-btn {
    padding: 12px 30px;
    border-radius: 8px;
    border: none;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 100px;
}

.confirm-btn-cancel {
    background-color: #f5f5f5;
    color: #666;
}

.confirm-btn-cancel:hover {
    background-color: #e0e0e0;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.confirm-btn-confirm {
    background: linear-gradient(135deg, #2d884d, #236b3c);
    color: white;
}

.confirm-btn-confirm:hover {
    background: linear-gradient(135deg, #236b3c, #1a522d);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(45, 136, 77, 0.3);
}

/* 成功弹窗样式 */
.success-modal-icon {
    background: linear-gradient(135deg, #2d884d, #236b3c);
}

.success-modal-icon i {
    font-size: 48px;
}

/* 加载动画 */
.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* 按钮中的加载状态 */
.confirm-btn.loading {
    position: relative;
    color: transparent;
}

.confirm-btn.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 1s ease-in-out infinite;
}

.confirm-btn-cancel.loading::after {
    border-color: rgba(102, 102, 102, 0.3);
    border-top-color: #666;
}
    
</style>

<!-- 购物车 -->
<section id="cart" class="module">
    <div class="product-section">
        <!-- 配送地址侧边栏 -->
        <div class="address-sidebar">
            <h2 class="section-title">Confirmation of Delivery address</h2>
            <form class="account-form">
                <div class="form-group">
                    <label class="form-label" for="receiver-name">Receiver</label>
                    <input type="text" id="receiver-name" class="form-input" readonly 
       value="<?php echo htmlspecialchars($customer_info['full_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="receiver-phone">Phone</label>
                    <input type="text" id="receiver-phone" class="form-input" readonly 
       value="<?php echo htmlspecialchars($customer_info['customer_phone'] ?? $customer_info['phone'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="receiver-email">Email</label>
                    <input type="text" id="receiver-email" class="form-input" readonly 
       value="<?php echo htmlspecialchars($customer_info['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="receiver-address">Address</label>
                    <input type="text" id="receiver-address" class="form-input" readonly value="<?php echo htmlspecialchars($customer_info['address'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="receiver-note">Tips</label>
                    <textarea id="receiver-note" class="form-input" rows="3" style="resize: none;">放门口即可</textarea>
                </div>
            </form>
        </div>

<!-- 购物车主内容区 -->
<div class="cart-main">
    <h2 class="section-title">My shopping cart</h2>
    
    <!-- 购物车商品列表 -->
    <div class="cart-list-wrapper">
        <?php if (!$has_cart_items): ?>
            <div style="text-align: center; padding: 50px 0; color: #999;">
                The shopping cart is empty.
            </div>
        <?php else: ?>
            <!-- 按门店分组显示 -->
            <?php foreach ($all_carts_data['carts'] as $cart): ?>
                <div class="cart-group" style="margin-bottom: 30px; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #f0f0f0;">
                        <h4 style="margin: 0; color: #2d884d; font-size: 16px;">
                            门店: <?php echo htmlspecialchars($cart['branch_name']); ?>
                        </h4>
                        <form method="POST" action="cart.php" style="margin: 0;">
                            <input type="hidden" name="order_id" value="<?php echo $cart['order_id']; ?>">
                            <input type="hidden" name="action" value="delete_cart">
                            <button type="submit" class="remove-from-cart" style="color: #ff4d4f;">删除整个门店购物车</button>
                        </form>
                    </div>
                    
                    <?php 
                    $order_details = getOrderDetails($cart['order_id']);
                    if ($order_details && !empty($order_details['items'])): 
                    ?>
                        <?php foreach ($order_details['items'] as $item): ?>
                            <div class="cart-item" data-item-id="<?php echo $item['item_ID']; ?>" style="padding-left: 0;">
                                <div class="product-img"> 
                                    <!-- 商品图片 -->
                                </div>
                                <div class="cart-item-info" style="flex: 1;">
                                    <div class="cart-item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    <div class="cart-item-price">¥<?php echo number_format($item['unit_price'], 2); ?></div>
                                </div>
                                <div class="cart-item-quantity">
            <span style="margin-right: 10px;">数量: <?php echo $item['quantity']; ?></span>
        </div>
        <form method="POST" action="cart.php" style="display: inline;">
            <input type="hidden" name="item_id" value="<?php echo $item['item_ID']; ?>">
            <input type="hidden" name="action" value="remove_item">
            <button type="submit" class="remove-from-cart">删除</button>
        </form>
        </div>
        <?php endforeach; ?>
                        
                        <div style="text-align: right; margin-top: 15px; padding-top: 10px; border-top: 1px dashed #f0f0f0;">
                            <div>小计: ¥<?php echo number_format($cart['final_amount'], 2); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- 分隔线 -->
    <div class="checkout-divider"></div>
    
    <!-- 结算栏 -->
    <div class="checkout-bar">
        <div style="text-align: right;">
            <div>商品总数: <?php echo count($cart_items_detailed); ?> 件</div>
            <div>涉及门店: <?php echo count($all_carts_data['carts']); ?> 家</div>
            <div>商品总价: <span style="text-decoration: line-through; color: #999;">¥<?php echo number_format($total_all_amount + $total_discount, 2); ?></span></div>
            <?php if ($total_discount > 0): ?>
                <div>会员折扣: <span style="color: #ff4d4f;">-¥<?php echo number_format($total_discount, 2); ?></span></div>
            <?php endif; ?>
            <div class="total-amount">实付金额<span>¥<?php echo number_format($total_all_amount, 2); ?></span></div>
        </div>
        <button class="checkout-btn" type="button" <?php echo !$has_cart_items ? 'disabled' : ''; ?>>合并结算</button>
    </div>
</div>  <!-- 这里关闭 cart-main -->
</section>

<!-- 结算弹窗 -->
<div class="modal-overlay" id="checkoutModal">
    <div class="modal">
        <button class="modal-close">&times;</button>
        <h3 class="modal-title">Order confirmation</h3>
        <div class="order-details">
            <div class="detail-section">
                <h4 class="detail-title">Products details</h4>
                <div id="order-items"></div>
            </div>
            <div class="detail-section">
                <h4 class="detail-title">Address</h4>
                <div id="shipping-info"></div>
            </div>
            <div class="detail-section">
    <h4 class="detail-title">Total amount</h4>
    <div style="margin-bottom: 8px;">Original price:<span style="text-decoration: line-through; color: #999;">¥<?php echo number_format($total_all_amount + $total_discount, 2); ?></span></div>
    <?php if ($total_discount > 0): ?>
        <div style="margin-bottom: 8px;">VIP Discounts:<?php echo $total_discount * 100; ?>%:<span style="color: #ff4d4f;">-¥<?php echo number_format($total_discount, 2); ?></span></div>
    <?php endif; ?>
    <div id="order-total" class="cart-item-price">Actual payment:¥<?php echo number_format($total_all_amount, 2); ?></div>
</div>

</div>        
        <div class="detail-section">
            <h4 class="detail-title">Pattern of payment</h4>
            <div class="payment-methods">
                <div class="payment-option selected" data-method="wechat">
                    <div>We chat</div>
                </div>
                <div class="payment-option" data-method="alipay">
                    <div>Alipay</div>
                </div>
            </div>
        </div>
        
        <button class="submit-order-btn">Submit order</button>
    </div>
</div>

<!-- 修改成功弹窗内容 -->
<div class="modal-overlay" id="successModal">
    <div class="modal">
        <button class="modal-close">&times;</button>
        <div class="success-message">
            <div class="success-icon">✓</div>
            <h3 class="modal-title">payment success</h3>
            <p>Your order has been paid for. Order number:<span id="success-order-id"></span></p>
            <p style="margin-top: 15px;">
                <span id="countdown">3</span> seconds later, the shopping cart page will be automatically returned.
            </p>
        </div>
    </div>
</div>

<!-- 删除确认弹窗 -->
<div class="confirm-modal-overlay" id="deleteConfirmModal">
    <div class="confirm-modal">
        <div class="confirm-modal-icon">
            <i class="fas fa-trash-alt"></i>
        </div>
        <div class="confirm-modal-content">
            <h3 class="confirm-modal-title">确认删除</h3>
            <p class="confirm-modal-message" id="deleteConfirmMessage">确定要删除这个商品吗？</p>
            <div class="confirm-modal-buttons">
                <button type="button" class="confirm-btn confirm-btn-cancel" id="deleteCancelBtn">取消</button>
                <button type="button" class="confirm-btn confirm-btn-confirm" id="deleteConfirmBtn">确定删除</button>
            </div>
        </div>
    </div>
</div>

<!-- 删除门店确认弹窗 -->
<div class="confirm-modal-overlay" id="deleteCartConfirmModal">
    <div class="confirm-modal">
        <div class="confirm-modal-icon">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="confirm-modal-content">
            <h3 class="confirm-modal-title">确认删除</h3>
            <p class="confirm-modal-message" id="deleteCartConfirmMessage">确定要删除整个门店的购物车吗？</p>
            <div class="confirm-modal-buttons">
                <button type="button" class="confirm-btn confirm-btn-cancel" id="deleteCartCancelBtn">取消</button>
                <button type="button" class="confirm-btn confirm-btn-confirm" id="deleteCartConfirmBtn">确定删除</button>
            </div>
        </div>
    </div>
</div>

<!-- 操作成功提示 -->
<div class="confirm-modal-overlay" id="successToast">
    <div class="confirm-modal" style="max-width: 300px;">
        <div class="confirm-modal-icon success-modal-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="confirm-modal-content">
            <h3 class="confirm-modal-title">操作成功</h3>
            <p class="confirm-modal-message" id="successToastMessage">操作已完成</p>
        </div>
    </div>
</div>

<!-- 加载中提示 -->
<div class="confirm-modal-overlay" id="loadingModal">
    <div class="confirm-modal" style="max-width: 200px; padding: 40px 20px; text-align: center;">
        <div class="loading-spinner" style="width: 40px; height: 40px; border-width: 4px; margin: 0 auto 20px;"></div>
        <p style="color: #666; font-size: 16px;">处理中...</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 弹窗元素
    const deleteConfirmModal = document.getElementById('deleteConfirmModal');
    const deleteCartConfirmModal = document.getElementById('deleteCartConfirmModal');
    const successToast = document.getElementById('successToast');
    const loadingModal = document.getElementById('loadingModal');
    const checkoutModal = document.getElementById('checkoutModal');
    const successModal = document.getElementById('successModal');
    const checkoutBtn = document.querySelector('.checkout-btn');
    const closeButtons = document.querySelectorAll('.modal-close');
    const submitOrderBtn = document.querySelector('.submit-order-btn');
    const paymentOptions = document.querySelectorAll('.payment-option');
    const countdownElement = document.getElementById('countdown');
    
    let currentForm = null;
    let currentAction = '';
    
    // === 工具函数 ===
    function showLoading() {
        loadingModal.classList.add('active');
    }
    
    function hideLoading() {
        loadingModal.classList.remove('active');
    }
    
    function showSuccess(message) {
        document.getElementById('successToastMessage').textContent = message;
        successToast.classList.add('active');
        setTimeout(() => successToast.classList.remove('active'), 2000);
    }
    
    // === 删除确认逻辑 ===
document.addEventListener('submit', function(e) {
    if (e.target && e.target.tagName === 'FORM') {
        const form = e.target;
        const actionInput = form.querySelector('input[name="action"]');
        
        // 检查是否为删除相关的表单
        if (!actionInput) return;
        
        // 单个商品删除
        if (actionInput.value === 'remove_item') {
            e.preventDefault();
            e.stopPropagation(); // 阻止事件冒泡
            
            currentForm = form;
            currentAction = 'remove_item';
            
            // 获取商品名称
            const itemName = form.closest('.cart-item').querySelector('.cart-item-name').textContent;
            document.getElementById('deleteConfirmMessage').textContent = `确定要删除 "${itemName}" 吗？`;
            deleteConfirmModal.classList.add('active');
            return false;
        }
        
        // 整个门店购物车删除
        if (actionInput.value === 'delete_cart') {
            e.preventDefault();
            e.stopPropagation(); // 阻止事件冒泡
            
            currentForm = form;
            currentAction = 'delete_cart';
            
            // 获取门店名称
            const branchName = form.closest('.cart-group').querySelector('h4').textContent;
            document.getElementById('deleteCartConfirmMessage').textContent = `确定要删除 "${branchName}" 的全部商品吗？`;
            deleteCartConfirmModal.classList.add('active');
            return false;
        }
    }
});
    
    // === 确认按钮事件 ===
    document.getElementById('deleteConfirmBtn').addEventListener('click', performDelete);
    document.getElementById('deleteCartConfirmBtn').addEventListener('click', performDelete);
    
    // === 取消按钮事件 ===
    document.getElementById('deleteCancelBtn').addEventListener('click', cancelDelete);
    document.getElementById('deleteCartCancelBtn').addEventListener('click', cancelDelete);
    
    // === 删除执行函数 ===
    function performDelete() {
        if (!currentForm) return;
        
        const isItemDelete = currentAction === 'remove_item';
        const confirmBtn = isItemDelete ? document.getElementById('deleteConfirmBtn') : 
                                         document.getElementById('deleteCartConfirmBtn');
        const confirmModal = isItemDelete ? deleteConfirmModal : deleteCartConfirmModal;
        
        const originalText = confirmBtn.textContent;
        confirmBtn.classList.add('loading');
        confirmModal.classList.remove('active');
        showLoading();
        
        const formData = new FormData(currentForm);
        
        fetch('cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                showSuccess(isItemDelete ? '商品删除成功' : '购物车删除成功');
                performDeleteAnimation(currentForm, isItemDelete ? 'item' : 'cart');
            } else {
                throw new Error('删除失败');
            }
        })
        .catch(error => {
            console.error('删除错误:', error);
            showError('删除失败，请重试');
        })
        .finally(() => {
            confirmBtn.classList.remove('loading');
            confirmBtn.textContent = originalText;
            hideLoading();
            currentForm = null;
        });
    }
    
    function cancelDelete() {
        deleteConfirmModal.classList.remove('active');
        deleteCartConfirmModal.classList.remove('active');
        currentForm = null;
    }
    
    // === 删除动画 ===
    function performDeleteAnimation(form, type) {
        let element = type === 'item' ? 
            form.closest('.cart-item') : 
            form.closest('.cart-group');
        
        if (!element) {
            setTimeout(() => location.reload(), 500);
            return;
        }
        
        element.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
        element.style.opacity = '0';
        element.style.transform = 'translateX(-100px)';
        element.style.height = element.offsetHeight + 'px';
        
        setTimeout(() => {
            element.style.height = '0';
            element.style.margin = '0';
            element.style.padding = '0';
            element.style.border = 'none';
            
            setTimeout(() => {
                element.remove();
                
                if (type === 'item') {
                    const cartGroup = element.closest('.cart-group');
                    if (cartGroup) {
                        const remainingItems = cartGroup.querySelectorAll('.cart-item');
                        if (remainingItems.length === 0) {
                            const deleteForm = cartGroup.querySelector('form[action*="cart.php"]');
                            if (deleteForm) {
                                currentForm = deleteForm;
                                performDeleteAnimation(deleteForm, 'cart');
                                return;
                            }
                        }
                    }
                }
                
                const remainingGroups = document.querySelectorAll('.cart-group');
                if (remainingGroups.length === 0) {
                    setTimeout(() => location.reload(), 1000);
                } else {
                    setTimeout(() => location.reload(), 500);
                }
            }, 400);
        }, 100);
    }
    
    // === 错误提示函数 ===
    function showError(message) {
        alert(message); // 可以换成更美观的弹窗
    }
    
    // 点击弹窗外部关闭
    window.addEventListener('click', function(event) {
        if (event.target === deleteConfirmModal || event.target === deleteCartConfirmModal) {
            cancelDelete();
        }
        if (event.target === checkoutModal) {
            checkoutModal.classList.remove('active');
        }
        if (event.target === successModal) {
            successModal.classList.remove('active');
        }
    });
    
    // === 结算相关代码 ===
    if (!checkoutBtn) {
        console.error('结算按钮未找到!');
        return;
    }
    
    console.log('has_cart_items:', <?php echo $has_cart_items ? 'true' : 'false'; ?>);
    <?php if ($has_cart_items): ?>
    console.log('orderIds:', <?php echo json_encode($all_order_ids); ?>);
    <?php endif; ?>

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

    // 选择支付方式
    if (paymentOptions.length > 0) {
        paymentOptions.forEach(option => {
            option.addEventListener('click', function() {
                paymentOptions.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
            });
        });
    }

    // 填充订单详情
    function populateOrderDetails() {
        console.log('开始填充订单详情');
        
        const orderItemsContainer = document.getElementById('order-items');
        const shippingInfoContainer = document.getElementById('shipping-info');
        const orderTotalContainer = document.getElementById('order-total');
        
        if (!orderItemsContainer) {
            console.error('order-items容器未找到');
            return;
        }
        
        orderItemsContainer.innerHTML = '';
        const cartGroups = document.querySelectorAll('.cart-group');
        
        if (cartGroups.length === 0) {
            orderItemsContainer.innerHTML = '<p>购物车为空</p>';
            return;
        }
        
        let totalItems = 0;
        cartGroups.forEach(group => {
            const branchName = group.querySelector('h4')?.textContent || '未知门店';
            const cartItems = group.querySelectorAll('.cart-item');
            
            const branchHeader = document.createElement('div');
            branchHeader.style.padding = '10px 0';
            branchHeader.style.fontWeight = 'bold';
            branchHeader.style.color = '#2d884d';
            branchHeader.style.borderBottom = '1px solid #f0f0f0';
            branchHeader.style.marginBottom = '10px';
            branchHeader.textContent = branchName;
            orderItemsContainer.appendChild(branchHeader);
            
            cartItems.forEach(item => {
                const name = item.querySelector('.cart-item-name')?.textContent || '未知商品';
                const priceText = item.querySelector('.cart-item-price')?.textContent || '¥0';
                const price = priceText.replace('¥', '');
                const quantityText = item.querySelector('.cart-item-quantity span')?.textContent || '数量: 0';
                const quantity = parseInt(quantityText.replace('数量: ', '')) || 0;
                const itemTotal = (parseFloat(price) * quantity).toFixed(2);
                
                const itemElement = document.createElement('div');
                itemElement.style.padding = '8px 0';
                itemElement.style.display = 'flex';
                itemElement.style.justifyContent = 'space-between';
                itemElement.style.borderBottom = '1px dashed #f0f0f0';
                itemElement.innerHTML = `<span>${name} x ${quantity}</span><span>¥${itemTotal}</span>`;
                orderItemsContainer.appendChild(itemElement);
                totalItems++;
            });
            
            const spacer = document.createElement('div');
            spacer.style.height = '15px';
            orderItemsContainer.appendChild(spacer);
        });
        
        const receiverName = document.getElementById('receiver-name')?.value || '';
        const receiverPhone = document.getElementById('receiver-phone')?.value || '';
        const receiverAddress = document.getElementById('receiver-address')?.value || '';
        const receiverNote = document.getElementById('receiver-note')?.value || '';
        
        if (shippingInfoContainer) {
            shippingInfoContainer.innerHTML = `
                <p>收件人: ${receiverName}</p>
                <p>电话: ${receiverPhone}</p>
                <p>地址: ${receiverAddress}</p>
                <p>备注: ${receiverNote}</p>
            `;
        }
        
        if (orderTotalContainer) {
            const totalAmountSpan = document.querySelector('.total-amount span');
            if (totalAmountSpan) {
                orderTotalContainer.textContent = `实付金额: ${totalAmountSpan.textContent}`;
            }
        }
    }

    // 提交订单
    if (submitOrderBtn) {
        submitOrderBtn.addEventListener('click', function() {
            const receiverName = document.getElementById('receiver-name').value;
            const receiverPhone = document.getElementById('receiver-phone').value;
            const receiverAddress = document.getElementById('receiver-address').value;
            const receiverNote = document.getElementById('receiver-note').value;
            
            if (!receiverName || !receiverPhone || !receiverAddress) {
                showError('请填写完整的收货信息');
                return;
            }
            
            <?php if ($has_cart_items): ?>
                const orderIds = <?php echo json_encode($all_order_ids); ?>;
            <?php else: ?>
                const orderIds = [];
            <?php endif; ?>
            
            if (orderIds.length === 0) {
                showError('购物车为空，无法提交订单');
                return;
            }
            
            const shippingAddress = `${receiverAddress} (${receiverName}, ${receiverPhone}) 备注: ${receiverNote}`;
            const originalText = this.textContent;
            this.disabled = true;
            this.textContent = '处理中...';
            
            fetch('cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    'action': 'checkout_ajax',
                    'shipping_address': shippingAddress,
                    'order_ids': JSON.stringify(orderIds)
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('服务器响应:', data);
                if (data.success) {
                    checkoutModal.classList.remove('active');
                    
                    let successMessage = `成功处理 ${data.processed_count} 个订单`;
                    if (data.results) {
                        successMessage += '<br>';
                        data.results.forEach(result => {
                            const status = result.success ? '成功' : '失败';
                            successMessage += `订单 ${result.order_id}: ${status} ${result.message}<br>`;
                        });
                    }
                    
                    document.getElementById('success-order-id').innerHTML = successMessage;
                    successModal.classList.add('active');
                    
                    let countdown = 5;
                    if (countdownElement) {
                        countdownElement.textContent = countdown;
                        const countdownInterval = setInterval(() => {
                            countdown--;
                            countdownElement.textContent = countdown;
                            if (countdown <= 0) {
                                clearInterval(countdownInterval);
                                window.location.href = 'orders.php';
                            }
                        }, 1000);
                    }
                } else {
                    showError(data.error || '订单提交失败');
                }
            })
            .catch(error => {
                console.error('请求错误:', error);
                showError('网络错误，请稍后重试');
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = originalText;
            });
        });
    }
});
</script>

</main>
</body>
</html>
