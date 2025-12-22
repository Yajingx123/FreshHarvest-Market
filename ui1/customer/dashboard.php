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
    }

    @media (min-width: 992px) {
        .dashboard-container {
            grid-template-columns: 2fr 1fr;
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
    .sidebar-section {
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

    /* 推荐商品区域 */
    .recommendation-list {
        display: grid;
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .recommendation-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 10px;
        border-radius: 8px;
        transition: background-color 0.2s ease;
    }

    .recommendation-item:hover {
        background-color: #f9fcfb;
    }

    .product-img {
        width: 60px;
        height: 60px;
        border-radius: 6px;
        object-fit: cover;
        background-color: #f0f7f2;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #2d884d;
    }

    .product-info {
        flex: 1;
    }

    .product-name {
        font-weight: 500;
        margin-bottom: 4px;
    }

    .product-price {
        color: #2d884d;
        font-weight: 600;
    }

    .add-btn {
        background-color: #2d884d;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 5px 10px;
        font-size: 14px;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .add-btn:hover {
        background-color: #236b3d;
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

    <div class="sidebar-section">
    <h3 class="section-title">推荐商品</h3>
    <div class="recommendation-list">
        <?php foreach ($productsWithDetails as $product): ?>
        <!-- 商品项添加data-id，用于弹窗交互 -->
        <div class="recommendation-item" data-id="<?php echo $product['id']; ?>">
            <div class="product-img">📦</div>
            <div class="product-info">
                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                <div class="product-price">¥<?php echo number_format($product['price'], 2); ?></div>
            </div>
            <button class="quick-add-btn" data-id="<?php echo $product['id']; ?>">快速加入</button>
        </div>
        <?php endforeach; ?>
    </div>
<!-- 正确结构 -->
<div class="modal-overlay" id="productModal">
  <div class="product-modal">
    <span class="close-modal" id="closeModal">×</span>
    <div class="modal-content">
      <div class="modal-img" id="modalImg">📦</div>
      <div class="modal-details" id="modalDetails">
        <!-- 弹窗内容将通过JS动态填充 -->
      </div>
    </div>
  </div>
</div>

<!-- 引入商品数据到JS -->
<script>
  const productDetails = <?php echo json_encode($productsWithDetails); ?>;
</script>

<section class="sidebar-section">
  <h2 class="section-title">🚚 配送说明</h2>
  <p style="line-height: 1.6; color: #666; font-size: 14px; margin-top: 10px;">
    今日订单截止时间:18:00<br>
    次日达区域：市区及近郊<br>
    满59元免配送费<br>
    客服热线:400-123-4567
  </p>
</section>
</div> 

<script>
const modalOverlay = document.getElementById('productModal');
const modalDetails = document.getElementById('modalDetails');
const closeModal = document.getElementById('closeModal');

    // 点击商品项显示详情弹窗
    document.querySelectorAll('.recommendation-item').forEach(item => {
        item.addEventListener('click', function(e) {
            // 忽略快速加入按钮的点击（避免触发弹窗）
            if (e.target.classList.contains('quick-add')) return;
            
            const productId = this.getAttribute('data-id');
            const product = productDetails.find(p => p.id == productId);
            if (!product) return;

            // 填充弹窗内容
            const storeId = product.store_id || 1;
            let branchSelect = '<select id="branchSelect" style="margin: 10px 0; padding: 5px; width: 200px;">';
            if (product.branches && product.branches.length > 0) {
                product.branches.forEach(branch => {
                    branchSelect += `<option value="${branch.id}">${branch.name}</option>`;
                });
            } else {
                branchSelect += '<option value="1">默认门店</option>';
            }
            branchSelect += '</select>';

            modalDetails.innerHTML = `
                <h2>${htmlEscape(product.name)}</h2>
                <p><strong>价格:</strong> ¥${parseFloat(product.price).toFixed(2)}</p>
                <p><strong>分类:</strong> ${htmlEscape(product.category)}</p>
                <p><strong>库存状态:</strong> ${htmlEscape(product.stock_status)}</p>
                <p><strong>可购数量:</strong> ${product.stock}</p>
                ${branchSelect}
                <div class="quantity-control">
                    <button class="quantity-btn" onclick="changeQty(-1, ${product.stock})">-</button>
                    <input type="number" id="buyQty" value="1" min="1" max="${product.stock}" style="width: 50px; text-align: center;">
                    <button class="quantity-btn" onclick="changeQty(1, ${product.stock})">+</button>
                </div>
                <button class="add-to-cart-btn" 
                   onclick="addToCart(${product.id}, document.getElementById('buyQty').value, ${storeId})">加入购物车
                </button>
            `;
            modalOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.quick-add-btn').forEach(button => {
       button.addEventListener('click', function(e) {
          e.stopPropagation();
          const productId = this.getAttribute('data-id');
        
          // 从商品详情数据中找到对应产品的门店ID
          const product = productsWithDetails.find(p => p.id == productId);
          if (!product) {
              alert('未找到商品信息');
              return;
          }
          // 优先使用商品关联的门店ID，没有则使用第一个可用门店
          let storeId = product.store_id;
          if (!storeId && product.branches && product.branches.length > 0) {
              storeId = product.branches[0].id; // 从分店列表取第一个
          }
           if (!storeId) {
              alert('未找到可用门店');
              return;
           }
           // 使用实际获取的门店ID加入购物车
           addToCart(productId, 1, storeId);
        });
      });
    });
    closeModal.addEventListener('click', () => {
        modalOverlay.classList.remove('active');
        document.body.style.overflow = '';
    });
    // 加入购物车函数（复用products.php的逻辑）
    function addToCart(productId, quantity, storeId) {
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
            alert(data.message);
            closeModalFunc();
        } else {
            alert('加入购物车失败：' + data.message);
        }
       })
       .catch(error => {
        console.error('请求错误：', error);
        alert('加入购物车时发生网络错误11');
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