<?php
require_once 'inc/data.php'; // 确保路径正确
require_once 'header.php'; // 引入导航栏

// 获取分类和商品数据
$categories = getProductCategories();
$selectedCategory = $_GET['category'] ?? 'all';
$searchKeyword = $_GET['search'] ?? '';
// 使用修复后的函数获取商品
$products = getProductsByCategoryAndSearch($selectedCategory, $searchKeyword);

// 存储商品详情数据
$productsWithBranches = [];
if (!empty($products)) { // 判断$products是否非空
    foreach ($products as $product) {
           // 即使getProductBranches返回空，也给branches赋值为空数组
           $product['branches'] = getProductBranches($product['id']) ?? [];
           $productsWithBranches[] = $product;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品列表</title>
    <style>
    .product-section {
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        padding: 25px;
        margin-bottom: 30px;
        display: flex;
        gap: 25px;
        flex-wrap: wrap;
        height: calc(100vh - 155px);
        overflow: hidden;
    }
    .category-sidebar {
        width: 220px;
        flex-shrink: 0;
        border-right: 1px solid #eee;
        padding-right: 20px;
        height: 100%;
        overflow-y: auto;
        /* 侧边栏滚动优化 */
        scrollbar-width: thin;
        scrollbar-color: #2d884d40 #f0f0f0;
        transition: box-shadow 0.3s ease;
    }
    /* 滚动条美化 */
    .category-sidebar::-webkit-scrollbar {
        width: 6px;
    }
    .category-sidebar::-webkit-scrollbar-track {
        background: #f0f0f0;
        border-radius: 3px;
    }
    .category-sidebar::-webkit-scrollbar-thumb {
        background-color: #2d884d40;
        border-radius: 3px;
    }
    .category-sidebar:hover {
        box-shadow: 3px 0 10px rgba(0,0,0,0.03);
    }
    .category-header {
        padding: 10px 0;
        margin-bottom: 15px;
        position: sticky;
        top: 0;
        background-color: white;
        z-index: 10;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
    }
    .category-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 15px;
        color: #2d884d;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .category-title svg {
        width: 20px;
        height: 20px;
    }
    .category-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .category-item {
        margin-bottom: 5px;
        border-radius: 6px;
        overflow: hidden;
        /* 移除transform过渡效果 */
        transition: none;
    }
    /* 移除hover左移动画 */
    .category-item:hover {
        transform: none;
    }
    .category-link {
        display: block;
        padding: 12px 15px;
        border-radius: 6px;
        color: #333;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    /* 移除左侧深绿色指示条 */
    .category-link::after {
        display: none;
    }
    /* 移除hover时的左移padding效果 */
    .category-link:hover {
        background-color: #f0f7f2;
        color: #2d884d;
        padding-left: 15px;
    }
    .category-link.active {
        background-color: #f0f7f2;
        color: #2d884d;
        font-weight: 500;
    }
    /* 移除active状态的指示条 */
    .category-link.active::after {
        display: none;
    }
    .category-count {
        display: inline-block;
        background-color: #e6f7ef;
        color: #2d884d;
        font-size: 12px;
        padding: 2px 8px;
        border-radius: 10px;
        margin-left: 8px;
    }
    .search-container {
        margin-bottom: 20px;
        position: relative;
        transition: transform 0.3s ease;
    }
    .search-container:focus-within {
        transform: translateY(-2px);
    }
    .search-input {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 16px;
        padding-right: 40px;
        transition: all 0.3s ease;
    }
    .search-input:focus {
        border-color: #2d884d;
        outline: none;
        box-shadow: 0 0 0 3px rgba(45, 136, 77, 0.1);
    }
    .search-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
        cursor: pointer;
        transition: color 0.3s ease;
    }
    .search-icon:hover {
        color: #2d884d;
    }
    .sidebar-divider {
        height: 1px;
        background-color: #eee;
        margin: 20px 0;
    }
    .category-empty {
        color: #999;
        text-align: center;
        padding: 20px 0;
        font-size: 14px;
    }
    /* 移动端侧边栏优化 */
    @media (max-width: 768px) {
        .category-sidebar {
            width: 100%;
            border-right: none;
            border-bottom: 1px solid #eee;
            padding-right: 0;
            padding-bottom: 20px;
            height: auto;
            max-height: 180px;
        }
        .category-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .category-item {
            flex: 1;
            min-width: 100px;
        }
        .category-link {
            text-align: center;
            padding: 8px 10px;
            font-size: 14px;
        }
    }

    /* 其他原有样式保持不变 */
    .product-content {
        flex: 1;
        min-width: 300px;
        height: 100%;
        overflow-y: auto;
        padding-right: 10px;
    }
    .store-select {
        margin-bottom: 20px;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 16px;
        width: 300px;
    }
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 25px;
    }
    .product-card {
        border: 1px solid #eee;
        border-radius: 8px;
        overflow: hidden;
        transition: transform 0.3s, box-shadow 0.3s;
        display: block;
        cursor: pointer;
    }
    .product-card.active {
        display: block;
    }
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .product-img {
        height: 180px;
        background-color: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #999;
    }
    .product-info {
        padding: 15px;
    }
    .product-name {
        font-size: 16px;
        margin-bottom: 5px;
        font-weight: 500;
    }
    .product-price {
        font-size: 18px;
        color: #ff4d4f;
        font-weight: bold;
        margin-bottom: 10px;
    }
    .product-stock {
        font-size: 14px;
        color: #666;
        margin-bottom: 15px;
    }
    .add-to-cart-btn {
        width: 100%;
        padding: 10px;
        background-color: #2d884d;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    .add-to-cart-btn:hover {
        background-color: #236b3c;
    }
    .add-to-cart-btn:disabled {
        cursor: not-allowed;
        opacity: 0.9;
    }

    /* 产品详情弹窗样式 */
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
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    .modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }
    .product-modal {
        background-color: white;
        border-radius: 10px;
        width: 90%;
        max-width: 800px;
        max-height: 90vh;
        overflow-y: auto;
        padding: 30px;
        position: relative;
        transform: translateY(-20px);
        transition: transform 0.3s ease;
    }
    .modal-overlay.active .product-modal {
        transform: translateY(0);
    }
    .close-modal {
        position: absolute;
        top: 20px;
        right: 20px;
        font-size: 24px;
        cursor: pointer;
        color: #999;
        transition: color 0.3s;
    }
    .close-modal:hover {
        color: #ff4d4f;
    }
    .modal-content {
        display: flex;
        gap: 30px;
        flex-wrap: wrap;
    }
    .modal-product-img {
        flex: 1;
        min-width: 300px;
        height: 300px;
        background-color: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 80px;
        color: #2d884d;
    }
    .modal-product-info {
        flex: 1;
        min-width: 300px;
    }
    .modal-product-name {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 15px;
        color: #333;
    }
    .modal-product-price {
        font-size: 28px;
        color: #ff4d4f;
        font-weight: bold;
        margin-bottom: 20px;
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
    }
    .product-detail-item {
        margin-bottom: 15px;
        font-size: 16px;
    }
    .product-detail-item label {
        display: inline-block;
        width: 100px;
        color: #666;
        font-weight: 500;
    }
    .product-description {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }
    .product-description h4 {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 15px;
        color: #2d884d;
    }
    .product-description p {
        line-height: 1.6;
        color: #666;
    }

    @media (max-width: 768px) {
        .store-select {
            width: 100%;
        }
        .product-section {
            flex-direction: column;
            height: auto;
            max-height: calc(100vh - 180px);
        }
        .product-content {
            height: auto;
            max-height: calc(100% - 150px);
        }
        .modal-content {
            flex-direction: column;
        }
        .modal-product-img {
            height: 200px;
            font-size: 60px;
        }
    }
</style>
    </style>
</head>
<body>
    <div class="container">
        <!-- 分类侧边栏 -->
        <div class="category-sidebar">
            <h3 class="category-title">商品分类</h3>
            
            <!-- 搜索框 -->
            <div class="search-container">
                <input type="text" class="search-input" id="searchInput" placeholder="搜索商品名称..." value="<?php echo htmlspecialchars($searchKeyword); ?>">
                <span class="search-icon" id="searchIcon">🔍</span>
            </div>
            
            <ul class="category-list">
                <li class="category-item">
                    <a href="products.php?category=all<?php echo $searchKeyword ? '&search=' . urlencode($searchKeyword) : ''; ?>" class="category-link <?php echo $selectedCategory === 'all' ? 'active' : ''; ?>">
                        全部商品
                        <span class="category-count">
                            <?php 
                            $total = 0;
                            foreach ($categories as $cat) $total += $cat['count'];
                            echo $total;
                            ?>
                        </span>
                    </a>
                </li>
                <?php foreach ($categories as $category): ?>
                <li class="category-item">
                    <a href="products.php?category=<?php echo urlencode($category['name']); ?><?php echo $searchKeyword ? '&search=' . urlencode($searchKeyword) : ''; ?>" 
                       class="category-link <?php echo $selectedCategory === $category['name'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                        <span class="category-count"><?php echo $category['count']; ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- 商品内容区 -->
        <div class="product-content">
            <h2><?php echo $selectedCategory === 'all' ? '全部商品' : htmlspecialchars($selectedCategory); ?></h2>
            <?php if ($searchKeyword): ?>
                <p>搜索关键词：<?php echo htmlspecialchars($searchKeyword); ?>（共<?php echo count($products); ?>个商品）</p>
            <?php endif; ?>
            
            <div class="product-grid">
                <?php if (empty($products)): ?>
                    <p>没有找到相关商品</p>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                    <div class="product-card" data-id="<?php echo $product['id']; ?>">
                        <div class="product-img">📦</div>
                        <div class="product-info">
                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="product-price">¥<?php echo number_format($product['price'], 2); ?></div>
                            <div class="product-stock">
                                <?php echo $product['stock_status']; ?> (剩余: <?php echo $product['stock_status'] === '无货' ? 0 : $product['stock']; ?>)
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 商品详情弹窗 -->
    <div class="modal-overlay" id="productModal">
        <div class="product-modal">
            <span class="close-modal" id="closeModal">×</span>
            <div class="modal-content">
                <div class="modal-img" id="modalImg">📦</div>
                <div class="modal-details" id="modalDetails">
                    <!-- 商品详情将通过JS动态填充 -->
                </div>
            </div>
        </div>
    </div>

    <script>
        const productDetails = <?php echo json_encode($productsWithBranches); ?>;
        // 获取DOM元素
        const modalOverlay = document.getElementById('productModal');
        const modalDetails = document.getElementById('modalDetails');
        const closeModal = document.getElementById('closeModal');
        const searchInput = document.getElementById('searchInput');
        const searchIcon = document.getElementById('searchIcon');
        
        // 点击商品卡片显示详情
        document.querySelectorAll('.product-card').forEach(card => {
           card.addEventListener('click', function() {
           const productId = this.getAttribute('data-id');
           const product = productDetails.find(p => p.id == productId);
        
        if (product) {
            // 存储产品的门店ID（关键）
            const storeId = product.store_id || 1; // 兜底为1，防止空值
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
                ${branchSelect} <!-- 插入门店选择下拉框 -->
                <p><strong>描述:</strong> ${htmlEscape(product.description || '暂无描述')}</p>
                <!-- 数量控制 -->
                <div class="quantity-control" style="margin: 15px 0;">
                    <button class="quantity-btn" onclick="changeQty(-1, ${product.stock})">-</button>
                    <input type="number" id="buyQty" value="1" min="1" max="${product.stock}" style="width: 50px; text-align: center; margin: 0 5px;">
                    <button class="quantity-btn" onclick="changeQty(1, ${product.stock})">+</button>
                </div>
                <!-- 加入购物车按钮:传递productId、数量、门店ID -->
                <button class="add-to-cart-btn" style="background: #2d884d; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;"
                        onclick="addToCart(${productId}, document.getElementById('buyQty').value, ${storeId})">
                    加入购物车
                </button>
            `;
            modalOverlay.classList.add('active');
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
        }
    });
});
        
        // 关闭弹窗
        closeModal.addEventListener('click', closeModalFunc);
        // 数量控制逻辑
       modalOverlay.addEventListener('click', function(e) {
         const quantityInput = document.getElementById('quantity');
         if (!quantityInput) return;
    
        if (e.target.id === 'decreaseQty') {
          const current = parseInt(quantityInput.value);
          if (current > 1) {
            quantityInput.value = current - 1;
          }
        } else if (e.target.id === 'increaseQty') {
           const current = parseInt(quantityInput.value);
          const max = parseInt(quantityInput.max);
          if (current < max) {
            quantityInput.value = current + 1;
          }
        } else if (e.target.classList.contains('add-to-cart-btn')) {
           const productId = e.target.getAttribute('data-id');
          const quantity = parseInt(document.getElementById('quantity').value);
           addToCart(productId, quantity);
        }
    });


// 加入购物车函数
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
        alert('加入购物车时发生网络错误');
    });
}
        
        // 搜索功能
        searchIcon.addEventListener('click', function() {
            const keyword = searchInput.value.trim();
            const currentCategory = <?php echo json_encode($selectedCategory); ?>;
            let url = `products.php?category=${encodeURIComponent(currentCategory)}`;
            if (keyword) {
                url += `&search=${encodeURIComponent(keyword)}`;
            }
            window.location.href = url;
        });
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchIcon.click();
            }
        });
        
        // 辅助函数：关闭弹窗
        function closeModalFunc() {
            modalOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        // 辅助函数：防止XSS攻击
        function htmlEscape(str) {
            if (!str) return '';
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }
    </script>
</body>
</html>