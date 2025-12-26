<?php $pageTitle = "我的仪表盘"; ?>
<?php include 'header.php'; ?>

<?php
// 获取当前用户ID
session_start();
require_once __DIR__ . '/inc/data.php';    // 包含data.php
$customerId = $_SESSION['customer_id'] ?? null;

// 初始化数据
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
foreach ($randomProducts as $p) {
    $product = getProductDetails($p['product_ID']);
    if ($product) {
        $product['stock'] = $product['stock'] ?? 0;
        $product['branches'] = getProductBranches($product['id']);
        $productsWithDetails[] = $product;
    }
}
?>

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
</style>

<div class="dashboard-container">
    <div class="main-content">
        <section id="dashboard" class="dashboard">
            <h2 class="section-title">📊 我的仪表盘</h2>
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-icon">🛒</div>
                    <div class="card-title">购物车数量</div>
                    <div class="card-value"><?php echo $cartCount; ?></div>
                </div>
                <div class="card">
                    <div class="card-icon">😋</div>
                    <div class="card-title">您最喜爱的产品</div>
                    <div class="card-value">
                        <?php
                        if (empty($favoriteProducts)) {
                            echo '无';
                        } else {
                            $names = array_column($favoriteProducts, 'product_name');
                            echo implode('、', $names);
                        }
                        ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="dashboard">
            <h2 class="section-title">📦 最近订单</h2>
            <ul class="order-list">
                <?php if (empty($recentOrders)): ?>
                    <li class="order-item">
                        <div class="order-info">
                            <div class="order-name">暂无订单记录</div>
                        </div>
                    </li>
                <?php else: ?>
                    <?php foreach ($recentOrders as $order): ?>
                        <li class="order-item">
                            <div class="order-info">
                                <div class="order-name"><?php echo $order['product_details']; ?></div>
                                <div class="order-date"><?php echo date('Y-m-d H:i', strtotime($order['order_date'])); ?></div>
                            </div>
                            <span class="order-status"><?php echo $order['order_status']; ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </section>
    </div>

    <!-- 配送说明模块 - 移至右侧，位于推荐商品上方 -->
    <div class="delivery-section">
        <h2 class="section-title">🚚 配送说明</h2>
        <p style="line-height: 1.6; color: #666; font-size: 14px; margin-top: 10px;">
            今日订单截止时间:18:00<br>
            次日达区域：市区及近郊<br>
            满59元免配送费<br>
            客服热线:400-123-4567
        </p>
    </div>

    <!-- 推荐商品模块 -->
    <div class="recommendation-section sidebar-section">
        <h3 class="section-title">推荐商品</h3>
        <div class="recommendation-list">
            <?php foreach ($productsWithDetails as $index => $product): ?>
            <!-- 商品项 -->
            <div class="recommendation-item" data-id="<?php echo $product['id']; ?>">
                <div class="product-img">📦</div>
                <div class="product-info">
                    <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                    <div class="product-price">¥<?php echo number_format($product['price'], 2); ?></div>
                </div>
                <button class="quick-add-btn" data-id="<?php echo $product['id']; ?>" data-index="<?php echo $index; ?>">显示详情</button>
            </div>
            
            <!-- 商品详情展开区域 - 优化排版 -->
            <div class="product-details" id="details-<?php echo $index; ?>">
                <!-- 商品详情内容 -->
                <div class="product-details-content-wrapper">
                    <div class="product-details-header">
                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                        <div class="product-price">¥<?php echo number_format($product['price'], 2); ?></div>
                    </div>
                    
                    <div class="product-details-content">
                        <div class="product-info-group">
                            <p class="product-info-item"><strong>分类:</strong> <?php echo htmlspecialchars($product['category'] ?? '未分类'); ?></p>
                            <p class="product-info-item"><strong>库存状态:</strong> <?php echo htmlspecialchars($product['stock_status'] ?? '正常'); ?></p>
                            <p class="product-info-item"><strong>可购数量:</strong> <?php echo $product['stock']; ?></p>
                        </div>
                        
                        <div class="branch-select-container">
                            <label for="branchSelect-<?php echo $index; ?>">选择门店</label>
                            <select id="branchSelect-<?php echo $index; ?>" style="width: 100%;">
                                <?php if ($product['branches'] && count($product['branches']) > 0): ?>
                                    <?php foreach ($product['branches'] as $branch): ?>
                                        <option value="<?php echo $branch['id']; ?>"><?php echo $branch['name']; ?></option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="1">默认门店</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="product-actions">
                            <div class="quantity-control">
                                <button class="quantity-btn" onclick="changeQty(-1, <?php echo $product['stock']; ?>, <?php echo $index; ?>)">-</button>
                                <input type="number" id="buyQty-<?php echo $index; ?>" value="1" min="1" max="<?php echo $product['stock']; ?>" style="width: 50px; text-align: center;">
                                <button class="quantity-btn" onclick="changeQty(1, <?php echo $product['stock']; ?>, <?php echo $index; ?>)">+</button>
                            </div>
                            
                            <button class="add-to-cart-btn" 
                               onclick="addToCart(<?php echo $product['id']; ?>, document.getElementById('buyQty-<?php echo $index; ?>').value, <?php echo $storeId ?? 1; ?>, <?php echo $index; ?>)">
                                加入购物车
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- 加入成功提示（默认隐藏） -->
                <div class="success-message">
                    <i>✓</i>
                    <div>加入成功！</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div> 

<!-- 引入商品数据到JS -->
<script>
  const productDetails = <?php echo json_encode($productsWithDetails); ?>;
</script>

<script>
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
    formData.append('action', 'add_to_cart');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    formData.append('branch_id', storeId); // 传递门店ID到后端

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
        alert('加入购物车失败：' + data.message);
    }
   })
   .catch(error => {
    console.error('请求错误：', error);
    alert('加入购物车时发生网络错误');
   });
}

// XSS防护辅助函数
function htmlEscape(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>

<div class="dashboard-footer">
    鲜选生鲜 © 2023 版权所有 | 新鲜直达 品质保证
</div>

</main>
</body>
</html>