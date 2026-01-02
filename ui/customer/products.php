<?php
require_once 'inc/data.php'; // 确保路径正确
require_once 'header.php'; // 引入导航栏

// 获取分类和商品数据
$categories = getProductCategories();
$selectedCategory = $_GET['category'] ?? 'all';
$searchKeyword = $_GET['search'] ?? '';

$products = getProductsByCategoryAndSearch($selectedCategory, $searchKeyword);

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
    
    /* 排序选择样式 */
    .sort-select {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        background-color: white;
        color: #333;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
    }
    .sort-select:focus {
        border-color: #2d884d;
        outline: none;
        box-shadow: 0 0 0 3px rgba(45, 136, 77, 0.1);
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
    .product-content h2 {
        color: #2d884d;
        margin-bottom: 25px;
    }
    .store-select {
        margin-bottom: 20px;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 16px;
        width: 300px;
    }
    .product-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 5px;
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
        font-size: 48px;
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
    .original-price {
        color: #999;
        font-size: 14px;
        text-decoration: line-through;
        margin-left: 8px;
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

    /* 产品详情弹窗样式 - 优化版 */
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
        border-radius: 8px;
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
        display: flex;
        align-items: center;
    }
    .product-detail-item label {
        display: inline-block;
        width: 100px;
        color: #666;
        font-weight: 500;
        flex-shrink: 0;
    }
    .product-detail-item span {
        flex: 1;
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
    
    .quantity-control {
        margin: 20px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .quantity-control input {
        width: 60px;
        text-align: center;
        padding: 5px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .quantity-btn {
        width: 30px;
        height: 30px;
        background-color: #f0f7f2;
        border: 1px solid #2d884d;
        color: #2d884d;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .quantity-btn:hover {
        background-color: #2d884d;
        color: white;
    }
    
    .cart-success-message {
        text-align: center;
        padding: 40px 0;
    }
    
    .cart-success-icon {
        font-size: 60px;
        color: #2d884d;
        margin-bottom: 20px;
    }
    
    .cart-success-text {
        font-size: 24px;
        color: #333;
        font-weight: 500;
    }
    .branch-item {
      margin-bottom: 8px; 
      padding-bottom: 8px;
      border-bottom: 1px solid #eee;
    }

    .branch-item:last-child {
      margin-bottom: 0;
      padding-bottom: 0;
      border-bottom: none;
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
            color: #333;
            max-height: calc(100% - 150px);
        }
        .modal-content {
            flex-direction: column;
        }
        .modal-product-img {
            height: 200px;
            font-size: 60px;
        }
        .product-detail-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
        .product-detail-item label {
            width: auto;
        }
    }
</style>
</head>
<body>
    <div class="container">
        <!-- 产品选择区 -->
        <section id="products" class="module">
            <div class="product-section">
                <!-- 分类侧边栏 -->
                <div class="category-sidebar">
                    <div class="category-header">
                        <h3 class="category-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            产品搜索
                        </h3>
                        <div class="search-container">
                            <input type="text" class="search-input" id="searchInput" placeholder="搜索产品名称..." value="<?php echo htmlspecialchars($searchKeyword); ?>">
                            <span class="search-icon" id="searchIcon">🔍︎</span>
                        </div>
                        
                        <h3 class="category-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            产品排序
                        </h3>
                        <select class="sort-select" id="sortSelect">
                            <option value="default">默认排序</option>
                            <option value="price-asc">价格从低到高</option>
                            <option value="price-desc">价格从高到低</option>
                        </select>
                    </div>
                    
                    <div class="sidebar-divider"></div>
                    
                    <h3 class="category-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 6h18M3 12h18M3 18h18"></path>
                        </svg>
                        产品分类
                    </h3>
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

                <!-- 产品内容区 -->
                <div class="product-content">
                    <div class="product-header">
                        <h2><?php echo $selectedCategory === 'all' ? '全部商品' : htmlspecialchars($selectedCategory); ?></h2>
                    </div>
                    <?php if ($searchKeyword): ?>
                        <p style="margin-top: -10px; margin-bottom: 10px;">搜索关键词：<?php echo htmlspecialchars($searchKeyword); ?>（共<?php echo count($products); ?>个商品）</p>
                    <?php endif; ?>
                    
                    <div class="product-grid" id="productGrid">
                        <?php if (empty($products)): ?>
                            <p style="grid-column: 1 / -1; text-align: center; color: #666; padding: 40px;">没有找到相关商品</p>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                            <div class="product-card" data-id="<?php echo $product['id']; ?>">
                                <div class="product-img">📦</div>
                                <div class="product-info">
                                    <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                    <div class="product-price">
                                        ¥<?php echo number_format($product['price'], 2); ?>
                                        <span class="original-price">¥<?php echo number_format($product['price'], 2); ?></span>
                                    </div>
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
        </section>
    </div>
    
    <!-- 产品详情弹窗 - 优化版 -->
    <div class="modal-overlay" id="productModal">
        <div class="product-modal">
            <span class="close-modal" id="closeModal">×</span>
            <div class="modal-content">
                <div class="modal-product-img" id="modalProductImg">📦</div>
                <div class="modal-product-info" id="modalProductInfo">
                    <!-- 商品详情将通过JS动态填充 -->
                </div>
            </div>
        </div>
    </div>

    <script>
        const productDetails = <?php echo json_encode($productsWithBranches); ?>;
        // 获取DOM元素
        const modalOverlay = document.getElementById('productModal');
        const modalProductInfo = document.getElementById('modalProductInfo');
        const modalProductImg = document.getElementById('modalProductImg');
        const closeModal = document.getElementById('closeModal');

        // 创建全局函数用于获取门店库存详情
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
                console.error('获取库存信息失败:', error);
                return null;
            }
        }
        document.addEventListener('change', function(e) {
            if (e.target && e.target.id === 'branchSelect') {
                const branchSelect = e.target;
                const productId = branchSelect.getAttribute('data-product-id');
                const storeId = branchSelect.value;
                
                // 显示加载中
                const stockInfoElement = document.getElementById('storeStockInfo');
                const quantityInput = document.getElementById('quantity');
                const maxQuantityText = document.getElementById('maxQuantityText');
                
                if (stockInfoElement) {
                    stockInfoElement.textContent = '加载中...';
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
                            maxQuantityText.textContent = `最多可购 ${availableStock} 件`;
                        }
                    }
                });
            }
        });

// 修改弹窗显示逻辑，当用户选择门店时动态获取库存
document.addEventListener('DOMContentLoaded', function() {
    // 监听门店选择变化
    document.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'branchSelect') {
            const branchSelect = e.target;
            const productId = branchSelect.getAttribute('data-product-id');
            const storeId = branchSelect.value;
            
            // 显示加载中
            const stockInfoElement = document.getElementById('storeStockInfo');
            const quantityInput = document.getElementById('quantity');
            const maxQuantityText = document.getElementById('maxQuantityText');
            
            if (stockInfoElement) {
                stockInfoElement.textContent = '加载中...';
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
                        maxQuantityText.textContent = `最多可购 ${availableStock} 件`;
                    }
                }
            });
        }
    });
});

        // 点击商品卡片显示详情
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                const product = productDetails.find(p => p.id == productId);
                
                if (product) {
                    // 构建门店选择下拉框
                    let branchSelectHtml = '';
                    if (product.branches && product.branches.length > 0) {
                        branchSelectHtml = `<div class="product-detail-item">
                            <label>选择门店：</label>
                            <select id="branchSelect" data-product-id="${productId}" 
                                    style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 250px;">
                                ${product.branches.map(branch => 
                                    `<option value="${branch.id}" 
                                             data-available="${branch.available_stock}">
                                        ${branch.name} (库存: ${branch.available_stock})
                                    </option>`
                                ).join('')}
                            </select>
                        </div>`;
                    }
                    
                    // 构建弹窗内容
                    modalProductImg.textContent = '📦';
                    modalProductInfo.innerHTML = `
                        <h3 class="modal-product-name">${htmlEscape(product.name)}</h3>
                        <div class="modal-product-price">
                            ¥${parseFloat(product.price).toFixed(2)}
                            <span class="original-price">¥${parseFloat(product.price).toFixed(2)}</span>
                        </div>
                        
                        <div class="product-detail-item">
                            <label>分类：</label>
                            <span>${htmlEscape(product.category)}</span>
                        </div>
                        <div class="product-detail-item">
                            <label>全公司库存：</label>
                            <span>${product.stock} (${product.stock_status})</span>
                        </div>
                        
                        ${branchSelectHtml}
                        
                        <div class="product-detail-item">
                            <label>门店库存：</label>
                            <span id="storeStockInfo">${product.branches.length > 0 ? product.branches[0].available_stock : 0}</span>
                        </div>
                        
                        ${product.branches.length > 0 ? `
                         <div class="branch-info">
                            <h4>各门店库存情况：</h4>
                            ${product.branches.map(branch => `
                            <div class="branch-item">
                        <div>
                        <div class="branch-name">${htmlEscape(branch.name)}</div>
                              ${branch.address ? `<div class="branch-address">${htmlEscape(branch.address)}</div>` : ''}
                        </div>
                           <div class="branch-stock">${branch.available_stock} 件</div>
                        </div>
                        `).join(' ')}
                       </div>
                      ` : ''}
                        
                        <div class="product-description">
                            <h4>产品详情</h4>
                            <p>${htmlEscape(product.description || '暂无描述')}</p>
                        </div>
                        
                        <!-- 数量控制 -->
                        <div class="quantity-control">
                            <button class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                            <input type="number" id="quantity" value="1" min="1" 
                                   max="${product.branches.length > 0 ? product.branches[0].available_stock : 0}" 
                                   style="width: 60px; text-align: center; padding: 5px; border: 1px solid #ddd; border-radius: 4px;">
                            <button class="quantity-btn" onclick="changeQuantity(1)">+</button>
                            <span style="margin-left: 10px; color: #666;" id="maxQuantityText">
                                最多可购 ${product.branches.length > 0 ? product.branches[0].available_stock : 0} 件
                            </span>
                        </div>
                        
                        <!-- 加入购物车按钮 -->
                        <button class="add-to-cart-btn" onclick="addToCart(${productId}, document.getElementById('quantity').value)" style="margin-top: 15px;">
                            加入购物车
                        </button>
                    `;
                    
                    modalOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            });
        });
        
        // 数量控制函数
        function changeQuantity(change) {
            const quantityInput = document.getElementById('quantity');
            if (!quantityInput) return;
            
            // 获取当前选中的门店库存
            const branchSelect = document.getElementById('branchSelect');
            let maxStock = 0;
            
            if (branchSelect) {
                const selectedOption = branchSelect.options[branchSelect.selectedIndex];
                maxStock = parseInt(selectedOption.getAttribute('data-available')) || 0;
            }
            
            let current = parseInt(quantityInput.value);
            current += change;
            
            if (current < 1) current = 1;
            if (current > maxStock) current = maxStock;
            
            quantityInput.value = current;
        }
        
        // 加入购物车函数
        function addToCart(productId, quantity) {
            const branchSelect = document.getElementById('branchSelect');
            const selectedBranchId = branchSelect ? branchSelect.value : 1;
            
            // 验证库存
            const selectedOption = branchSelect ? branchSelect.options[branchSelect.selectedIndex] : null;
            const availableStock = selectedOption ? parseInt(selectedOption.getAttribute('data-available')) || 0 : 0;
            
            if (quantity > availableStock) {
                alert(`库存不足！当前门店可用库存为 ${availableStock} 件`);
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'add_to_cart');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            formData.append('branch_id', selectedBranchId);
            
            fetch('cart_handler.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    modalProductInfo.innerHTML = `
                        <div class="cart-success-message">
                            <div class="cart-success-icon">✓</div>
                            <div class="cart-success-text">已加入购物车</div>
                        </div>
                    `;
                    
                    setTimeout(closeModalFunc, 1000);
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
        
        // 排序功能
        sortSelect.addEventListener('change', function() {
            const sortValue = this.value;
            const currentCategory = <?php echo json_encode($selectedCategory); ?>;
            const searchKeyword = <?php echo json_encode($searchKeyword); ?>;
    
            if (sortValue === 'default') {
              let url = `products.php?category=${encodeURIComponent(currentCategory)}`;
              if (searchKeyword) {
                  url += `&search=${encodeURIComponent(searchKeyword)}`;
               }
              window.location.href = url;
              return;
            }
            const products = Array.from(productGrid.querySelectorAll('.product-card'));
            
            if (products.length === 0) return;
            
            // 对产品进行排序
            products.sort((a, b) => {
                const priceA = parseFloat(a.querySelector('.product-price').textContent.replace('¥', ''));
                const priceB = parseFloat(b.querySelector('.product-price').textContent.replace('¥', ''));
                
                switch(sortValue) {
                    case 'price-asc':
                        return priceA - priceB;
                    case 'price-desc':
                        return priceB - priceA;
                    default:
                        return 0;
                }
            });
            
            // 重新排列产品
            products.forEach(product => productGrid.appendChild(product));
        });
        
        // 辅助函数：关闭弹窗
        function closeModalFunc() {
            modalOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        // 关闭弹窗事件
        closeModal.addEventListener('click', closeModalFunc);
        
        // 点击弹窗外部关闭
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) {
                closeModalFunc();
            }
        });
        
        // 辅助函数：防止XSS攻击
        function htmlEscape(str) {
            if (!str) return '';
            return str.replace(/&/g, '&amp;')
                     .replace(/</g, '&lt;')
                     .replace(/>/g, '&gt;')
                     .replace(/"/g, '&quot;')
                     .replace(/'/g, '&#039;');
        }
    </script>
</body>
</html>