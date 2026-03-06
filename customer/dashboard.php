<?php $pageTitle = "Dashboard"; ?>
<?php include 'header.php'; ?>

<?php
// 获取当前用户ID
session_start();
require_once __DIR__ . '/inc/data.php';    // 包含data.php
$customerId = $_SESSION['customer_id'] ?? null;


$cartCount = 0;
$favoriteProducts = [];
$recentOrders = [];
$randomProducts = [];

if ($customerId) {
    $cartCount = getCartItemCount($customerId);
    $favoriteProducts = getFavoriteProducts($customerId);
    $recentOrders = getRecentOrders($customerId);
}

$randomProducts = getRandomProducts();
$productsWithDetails = [];
// 在获取产品详情的地方，确保获取门店库存信息
foreach ($randomProducts as $p) {
    $product = getProductDetails($p['product_ID']);
    if ($product) {
        $product['stock'] = $product['stock'] ?? 0;
        $product['branches'] = getProductBranches($product['id']);
        
        // 获取产品SKU和图片URL
        $productSku = getProductSkuByName($product['name']);
        $product['sku'] = $productSku;
        $product['image_url'] = getProductImageUrl($productSku);
        
        if (!empty($product['branches'])) {
            $firstBranch = $product['branches'][0];
            $product['branch_stock_info'] = getProductStockInStore($product['id'], $firstBranch['id']);
        }
        
        $productsWithDetails[] = $product;
    }
}

?>
<script>
    const productsWithDetails = <?php echo json_encode($productsWithDetails); ?>;
</script>

<style>
    /* 主容器布局优化 */
    .dashboard-container {
        display: grid;
        grid-template-columns: 1fr;
        gap: 30px;
        margin-bottom: 40px;
        align-items: start;
    }

    @media (min-width: 992px) {
        .dashboard-container {
            grid-template-columns: 1fr 1fr 1fr; /* 保持三列布局 */
            grid-template-areas:
                "main main delivery"
                "main main recommendation";
            /* 关键修改：缩小配送说明和推荐商品之间的间距 */
            gap: 30px 30px;
        }
        .delivery-section {
            grid-area: delivery;
            /* 移除底部外边距，让推荐商品紧贴 */
            margin-bottom: 0;
        }
        .main-content {
            grid-area: main;
        }
        .recommendation-section {
            grid-area: recommendation;
            /* 移除顶部外边距，紧贴配送说明 */
            margin-top: 0;
            /* 移除底部外边距，保持统一 */
            margin-bottom: 30px;
        }
    }

    /* 仪表盘样式优化 */
    .dashboard {
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        padding: 25px;
        margin-bottom: 30px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .dashboard:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }

    .section-title {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #2d884d;
        border-left: 4px solid #2d884d;
        padding-left: 10px;
        display: flex;
        align-items: center;
    }

    .section-title i {
        margin-right: 8px;
        font-size: 18px;
    }

    /* 卡片样式增强 */
    .dashboard-cards {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .card {
        flex: 1;
        min-width: 200px;
        background-color: #f0f7f2;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background-color: #2d884d;
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(45, 136, 77, 0.1);
    }

    .card:hover::before {
        transform: scaleX(1);
    }

    .card-icon {
        font-size: 32px;
        color: #2d884d;
        margin-bottom: 10px;
    }

    .card-title {
        font-size: 16px;
        margin-bottom: 5px;
        color: #666;
    }

    .card-value {
        font-size: 24px;
        font-weight: bold;
        color: #333;
    }

    /* 侧边区域样式 */
    .sidebar-section, .delivery-section {
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        padding: 25px;
        margin-bottom: 30px;
    }

    /* 最近订单列表 */
    .order-list {
        list-style: none;
    }

    .order-item {
        padding: 15px;
        border-bottom: 1px solid #f0f7f2;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background-color 0.2s ease;
    }

    .order-item:last-child {
        border-bottom: none;
    }

    .order-item:hover {
        background-color: #f9fcfb;
    }

    .order-info {
        flex: 1;
    }

    .order-name {
        font-weight: 500;
        margin-bottom: 3px;
    }

    .order-date {
        font-size: 12px;
        color: #999;
    }

    .order-status {
        font-size: 14px;
        padding: 3px 10px;
        border-radius: 12px;
        background-color: #e6f7ef;
        color: #2d884d;
    }

    /* 推荐商品区域 - 关键修改：移除滚动条，紧贴配送说明 */
    .recommendation-section {
        /* 移除最大高度限制，让内容自然展开 */
        max-height: none;
        /* 移除滚动相关样式 */
        overflow-y: visible;
        /* 确保没有滚动条 */
        overflow: visible;
    }
    
    /* 完全移除滚动条相关样式 */
    .recommendation-section::-webkit-scrollbar {
        display: none;
    }

    .recommendation-list {
        display: grid;
        grid-template-columns: 1fr;
        gap: 15px;
    }

    /* 推荐商品项 - 优化样式 */
    .recommendation-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        border-radius: 8px;
        transition: all 0.2s ease;
        border: 1px solid #f0f7f2;
        cursor: pointer;
    }

    .recommendation-item:hover {
        background-color: #f9fcfb;
        border-color: #e6f7ef;
    }

    .product-img {
        width: 70px;
        height: 70px;
        border-radius: 6px;
        object-fit: cover;
        background-color: #f0f7f2;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #2d884d;
        font-size: 24px;
        flex-shrink: 0;
    }

    .product-info {
        flex: 1;
        min-width: 0;
    }

    .product-name {
        font-weight: 500;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .product-price {
        color: #2d884d;
        font-weight: 600;
    }

    .quick-add-btn {
        background-color: #2d884d;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 6px 12px;
        font-size: 14px;
        cursor: pointer;
        transition: background-color 0.2s ease;
        flex-shrink: 0;
    }

    .quick-add-btn:hover {
        background-color: #236b3d;
    }

    /* 商品详情展开区域 - 优化排版 */
    .product-details {
        grid-column: 1 / -1;
        padding: 20px;
        border-radius: 8px;
        background-color: #f9fcfb;
        border: 1px solid #e6f7ef;
        display: none;
        animation: fadeIn 0.3s ease;
        margin-bottom: 10px;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .product-details.active {
        display: block;
    }
    
    .product-details-header {
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e6f7ef;
    }
    
    .product-details h4 {
        margin: 0 0 5px 0;
        font-size: 18px;
        color: #333;
    }
    
    .product-details-content {
        display: grid;
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .product-info-group {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    .product-info-item {
        margin: 0;
        font-size: 14px;
        color: #666;
    }
    
    .product-info-item strong {
        color: #333;
    }
    
    .product-actions {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-top: 10px;
    }

    .quantity-control {
        display: flex;
        align-items: center;
        margin: 5px 0;
    }

    .quantity-btn {
        width: 30px;
        height: 30px;
        border: 1px solid #2d884d;
        background-color: white;
        color: #2d884d;
        cursor: pointer;
        border-radius: 4px;
    }

    .quantity-btn:hover {
        background-color: #f0f7f2;
    }

    .add-to-cart-btn {
        background-color: #2d884d;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 10px 15px;
        font-size: 15px;
        cursor: pointer;
        margin-top: 5px;
        transition: all 0.2s ease;
    }

    .add-to-cart-btn:hover {
        background-color: #236b3d;
        transform: translateY(-2px);
    }
    
    .branch-select-container {
        margin: 10px 0;
    }
    
    .branch-select-container label {
        display: block;
        margin-bottom: 5px;
        font-size: 14px;
        color: #666;
    }
    
    .branch-select-container select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    /* 加入成功提示样式 */
    .success-message {
        text-align: center;
        padding: 30px 20px;
        font-size: 18px;
        color: #2d884d;
        display: none;
    }

    .success-message i {
        font-size: 48px;
        margin-bottom: 15px;
        display: block;
    }

    /* 页脚区域 */
    .dashboard-footer {
        text-align: center;
        padding: 20px;
        color: #999;
        font-size: 14px;
        border-top: 1px solid #f0f7f2;
        margin-top: 40px;
    }
    /* 产品图片容器样式 - 确保图片正确显示 */
.product-img {
    width: 70px;
    height: 70px;
    border-radius: 6px;
    background-color: #f0f7f2;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #2d884d;
    font-size: 24px;
    flex-shrink: 0;
    overflow: hidden; /* 添加这行确保图片不会溢出 */
}

/* 产品图片样式 */
.product-img img {
    width: 100%;
    height: 100%;
    object-fit: cover; /* 保持图片比例并填充容器 */
    border-radius: 6px;
}
</style>

<div class="dashboard-container">
    <div class="main-content">
        <section id="dashboard" class="dashboard">
            <h2 class="section-title">📊 My dashboard</h2>
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-icon">🛒</div>
                    <div class="card-title">Cart items</div>
                    <div class="card-value"><?php echo $cartCount; ?></div>
                </div>
                <div class="card">
                    <div class="card-icon">😋</div>
                    <div class="card-title">Your favorite products</div>
                    <div class="card-value">
                        <?php
                        if (empty($favoriteProducts)) {
                            echo 'None yet';
                        } else {
                            $names = array_column($favoriteProducts, 'product_name');
                            echo implode(', ', $names);
                        }
                        ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="dashboard">
            <h2 class="section-title">📦 Recent orders</h2>
            <ul class="order-list">
                <?php if (empty($recentOrders)): ?>
                    <li class="order-item">
                        <div class="order-info">
                            <div class="order-name">No orders yet</div>
                        </div>
                    </li>
                <?php else: ?>
                    <?php foreach ($recentOrders as $order): ?>
                        <?php
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
                        $statusLabel = $statusMap[$order['order_status']] ?? $order['order_status'];
                        ?>
                        <li class="order-item">
                            <div class="order-info">
                                <div class="order-name"><?php echo $order['product_details']; ?></div>
                                <div class="order-date"><?php echo date('Y-m-d H:i', strtotime($order['order_date'])); ?></div>
                            </div>
                            <span class="order-status"><?php echo $statusLabel; ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </section>
    </div>

    <!-- 配送说明模块 - 移至右侧，位于推荐商品上方 -->
    <div class="delivery-section">
        <h2 class="section-title">🚚 Delivery info</h2>
        <p style="line-height: 1.6; color: #666; font-size: 14px; margin-top: 10px;">
            Order cutoff time today: 18:00<br>
            Next-day delivery: urban areas and nearby suburbs<br>
            Free delivery on orders over ¥59<br>
            Customer support: 400-123-4567
        </p>
    </div>

    <!-- 推荐商品模块 -->
    <div class="recommendation-section sidebar-section">
        <h3 class="section-title">Recommended products</h3>
        <div class="recommendation-list">
            <?php foreach ($productsWithDetails as $index => $product): ?>
            <!-- 商品项 -->
            <div class="recommendation-item" data-id="<?php echo $product['id']; ?>">
                <div class="product-img">
    <?php if (!empty($product['image_url'])): ?>
        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
             alt="<?php echo htmlspecialchars($product['name']); ?>"
             style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;">
    <?php else: ?>
        📦 
    <?php endif; ?>
</div>
                <div class="product-info">
                    <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                    <div class="product-price">¥<?php echo number_format($product['price'], 2); ?></div>
                </div>
                <button class="quick-add-btn" data-id="<?php echo $product['id']; ?>" data-index="<?php echo $index; ?>">View details</button>
            </div>
            
<div class="product-details" id="details-<?php echo $index; ?>">
    <div class="product-details-content-wrapper">
        <div class="product-details-header">
    <?php if (!empty($product['image_url'])): ?>
    <div class="product-details-image" style="float: right; margin-left: 15px; margin-bottom: 10px;">
        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
             alt="<?php echo htmlspecialchars($product['name']); ?>"
             style="width: 100px; height: 100px; object-fit: cover; border-radius: 6px;">
    </div>
    <?php endif; ?>
    
    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
    <div class="product-price">¥<?php echo number_format($product['price'], 2); ?></div>
</div>
        
        <div class="product-details-content">
            <div class="product-info-group">
                <p class="product-info-item"><strong>Category:</strong> <?php echo htmlspecialchars($product['category'] ?? 'None'); ?></p>
                <p class="product-info-item"><strong>Stock status:</strong> <?php echo htmlspecialchars($product['stock_status'] ?? 'Normal'); ?></p>
                <p class="product-info-item"><strong>Quantity:</strong> <span id="storeStockInfo-<?php echo $index; ?>">
                    <?php echo !empty($product['branches']) ? $product['branches'][0]['available_stock'] ?? 0 : 0; ?>
                </span></p>
            </div>
            
            <!-- 门店选择区域 -->
            <div class="branch-select-container">
                <label for="branchSelect-<?php echo $index; ?>">Select a store</label>
                <select id="branchSelect-<?php echo $index; ?>" 
                        class="branch-select"
                        data-product-id="<?php echo $product['id']; ?>"
                        data-index="<?php echo $index; ?>"
                        style="width: 100%;">
                    <?php if ($product['branches'] && count($product['branches']) > 0): ?>
                        <?php foreach ($product['branches'] as $branch): ?>
                            <option value="<?php echo $branch['id']; ?>" 
                                    data-available="<?php echo $branch['available_stock']; ?>">
                                <?php echo $branch['name']; ?> (Stock: <?php echo $branch['available_stock']; ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="1">Default store</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <!-- 门店库存详情（类似 products.php 的显示） -->
            <?php if ($product['branches'] && count($product['branches']) > 0): ?>
            <div class="branch-info">
                <h5>Store availability:</h5>
                <?php foreach ($product['branches'] as $branch): ?>
                <div class="branch-item" style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee;">
                    <div>
                        <div class="branch-name"><?php echo htmlspecialchars($branch['name']); ?></div>
                        <?php if (!empty($branch['address'])): ?>
                        <div class="branch-address" style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($branch['address']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="branch-stock" style="color: <?php echo $branch['available_stock'] > 0 ? '#2d884d' : '#ff4d4f'; ?>;">
                        <?php echo $branch['available_stock']; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="product-actions">
                <div class="quantity-control">
                    
                    <input type="number" id="buyQty-<?php echo $index; ?>" value="1" min="1" max="<?php echo !empty($product['branches']) ? $product['branches'][0]['available_stock'] : 0; ?>" style="width: 50px; text-align: center;">
                    
                    <span style="margin-left: 10px; color: #666; font-size: 12px;" id="maxQuantityText-<?php echo $index; ?>">
                        Max available: <?php echo !empty($product['branches']) ? $product['branches'][0]['available_stock'] : 0; ?>
                    </span>
                </div>
                
                <button class="add-to-cart-btn" 
                       onclick="addToCart(<?php echo $product['id']; ?>, document.getElementById('buyQty-<?php echo $index; ?>').value, document.getElementById('branchSelect-<?php echo $index; ?>').value, <?php echo $index; ?>)">
                    Add to cart
                </button>
            </div>
        </div>
    </div>
    
    <!-- 加入成功提示 -->
    <div class="success-message">
        <i>✓</i>
        <div>Added to cart</div>
    </div>
</div>
            <?php endforeach; ?>
        </div>
    </div>
</div> 

<script>
async function getStoreStockInfo(productId, storeId) {
    try {
        const response = await fetch('stock_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_store_stock&product_id=${productId}&store_id=${storeId}`
        });
        
        const data = await response.json();
        if (data.success) {
            return data.stock_info;
        }
        return null;
    } catch (error) {
        console.error('Failed to obtain inventory information:', error);
        return null;
    }
}

// 在 document.addEventListener 或 product-details 生成后添加
document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('branch-select')) {
        const branchSelect = e.target;
        const productId = branchSelect.getAttribute('data-product-id');
        const storeId = branchSelect.value;
        const index = branchSelect.getAttribute('data-index');
        
        // 获取库存信息元素
        const stockInfoElement = document.getElementById(`storeStockInfo-${index}`);
        const quantityInput = document.getElementById(`buyQty-${index}`);
        const maxQuantityText = document.getElementById(`maxQuantityText-${index}`);
        
        if (stockInfoElement) {
            stockInfoElement.textContent = 'Loading...';
        }
        
        // 动态获取库存信息
        getStoreStockInfo(productId, storeId).then(stockInfo => {
            if (stockInfo) {
                const availableStock = stockInfo.available_stock_in_store || 0;
                
                // 更新显示
                if (stockInfoElement) {
                    stockInfoElement.textContent = availableStock;
                }
                
                // 更新数量输入框
                if (quantityInput) {
                    quantityInput.max = availableStock;
                    // 如果当前数量超过库存，自动调整
                    if (parseInt(quantityInput.value) > availableStock) {
                        quantityInput.value = availableStock;
                    }
                }
                
                // 更新提示文本
                if (maxQuantityText) {
                    maxQuantityText.textContent = `Max available: ${availableStock}`;
                }
            }
        });
    }
});

// 商品详情展开/收起功能
document.querySelectorAll('.quick-add-btn').forEach(button => {
    button.addEventListener('click', function(e) {
        e.stopPropagation();
        const index = this.getAttribute('data-index');
        const detailsElement = document.getElementById(`details-${index}`);
        const isActive = detailsElement.classList.contains('active');
        
        // 关闭所有已展开的详情
        document.querySelectorAll('.product-details').forEach(el => {
            el.classList.remove('active');
            // 重置所有详情显示内容
            const contentWrapper = el.querySelector('.product-details-content-wrapper');
            const successMsg = el.querySelector('.success-message');
            if (contentWrapper && successMsg) {
                contentWrapper.style.display = 'block';
                successMsg.style.display = 'none';
            }
        });
        
        // 如果当前是关闭状态，则展开
        if (!isActive) {
            detailsElement.classList.add('active');
            // 滚动到当前详情区域
            detailsElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });
});

// 点击商品项也可以展开详情
document.querySelectorAll('.recommendation-item').forEach(item => {
    item.addEventListener('click', function() {
        const btn = this.querySelector('.quick-add-btn');
        if (btn) {
            btn.click();
        }
    });
});

// 数量调整函数
function changeQty(change, maxStock, index) {
    const qtyInput = document.getElementById(`buyQty-${index}`);
    let currentQty = parseInt(qtyInput.value);
    let newQty = currentQty + change;
    
    // 确保数量在有效范围内
    if (newQty < 1) newQty = 1;
    if (newQty > maxStock) newQty = maxStock;
    
    qtyInput.value = newQty;
}

// 加入购物车函数
function addToCart(productId, quantity, storeId, index) {
    const formData = new FormData();
    const branchSelect = document.getElementById(`branchSelect-${index}`);
    const selectedBranchId = branchSelect ? branchSelect.value : storeId;

    
    formData.append('action', 'add_to_cart');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    formData.append('branch_id', selectedBranchId); // 传递门店ID到后端

    fetch('cart_handler.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const detailsElement = document.getElementById(`details-${index}`);
            const contentWrapper = detailsElement.querySelector('.product-details-content-wrapper');
            const successMsg = detailsElement.querySelector('.success-message');
            
            // 隐藏详情内容，显示成功提示
            contentWrapper.style.display = 'none';
            successMsg.style.display = 'block';
            
            // 1秒后自动收起详情
            setTimeout(() => {
                detailsElement.classList.remove('active');
                // 恢复内容显示，为下次打开做准备
                contentWrapper.style.display = 'block';
                successMsg.style.display = 'none';
            }, 1000);
        } else {
            alert('Failed to add to cart: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Request error:', error);
        alert('A network error occurred while adding to the cart.');
    });
}

// XSS防护辅助函数
function htmlEscape(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>

<div class="dashboard-footer">
    FreshHarvest © 2023. Fresh delivery, quality guaranteed.
</div>

</main>
</body>
</html>
