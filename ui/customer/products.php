<?php $pageTitle = "产品选择"; ?>
<?php include 'header.php'; ?>

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
    .add-to-cart {
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
    .add-to-cart:hover {
        background-color: #236b3c;
    }
    .add-to-cart:disabled {
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
                    <input type="text" class="search-input" id="productSearch" placeholder="搜索产品名称...">
                    <span class="search-icon">🔍︎</span>
                </div>
            </div>
            
            <div class="sidebar-divider"></div>
            
            <h3 class="category-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18M3 12h18M3 18h18"></path>
                </svg>
                产品分类
            </h3>
            <ul class="category-list">
                <li class="category-item"><a class="category-link active" data-category="all">全部商品 <span class="category-count">4</span></a></li>
                <li class="category-item"><a class="category-link" data-category="vegetables">新鲜蔬菜 <span class="category-count">1</span></a></li>
                <li class="category-item"><a class="category-link" data-category="fruits">时令水果 <span class="category-count">1</span></a></li>
                <li class="category-item"><a class="category-link" data-category="meat">肉类生鲜 <span class="category-count">1</span></a></li>
                <li class="category-item"><a class="category-link" data-category="imported">进口食材 <span class="category-count">1</span></a></li>
            </ul>
        </div>

        <!-- 产品内容区（保持不变） -->
        <div class="product-content">
            <select class="store-select" id="storeSelect">
                <option value="1" selected>默认排序</option>
                <option value="2">按价格从高到低</option>
                <option value="3">按价格从低到高</option>
            </select>
            <div class="product-grid">
                <!-- 产品卡片1 - 蔬菜类 -->
                <div class="product-card all vegetables" 
                     data-id="1"
                     data-name="有机生菜（500g）" 
                     data-price="12.90" 
                     data-stock="28"
                     data-unit="500g/份"
                     data-origin="山东寿光"
                     data-shelf="7天"
                     data-desc="有机种植生菜，无农药残留，脆嫩爽口，富含维生素和膳食纤维，适合生食、沙拉、涮锅等多种吃法。">
                    <div class="product-img">🥬 新鲜生菜</div>
                    <div class="product-info">
                        <div class="product-name">有机生菜（500g）</div>
                        <div class="product-price">¥12.90</div>
                        <div class="product-stock">剩余：28份</div>
                        <button class="add-to-cart">加入购物车</button>
                    </div>
                </div>
                <!-- 产品卡片2 - 水果类 -->
                <div class="product-card all fruits"
                     data-id="2"
                     data-name="红颜草莓（300g）" 
                     data-price="39.90" 
                     data-stock="15"
                     data-unit="300g/盒"
                     data-origin="辽宁丹东"
                     data-shelf="3天"
                     data-desc="丹东九九红颜草莓，果肉饱满，酸甜多汁，果香浓郁，富含维生素C，现摘现发，保证新鲜。">
                    <div class="product-img">🍓 新鲜草莓</div>
                    <div class="product-info">
                        <div class="product-name">红颜草莓（300g）</div>
                        <div class="product-price">¥39.90</div>
                        <div class="product-stock">剩余：15份</div>
                        <button class="add-to-cart">加入购物车</button>
                    </div>
                </div>
                <!-- 产品卡片3 - 肉类 -->
                <div class="product-card all meat"
                     data-id="3"
                     data-name="澳洲和牛牛排（200g）" 
                     data-price="89.00" 
                     data-stock="8"
                     data-unit="200g/片"
                     data-origin="澳大利亚"
                     data-shelf="冷冻180天"
                     data-desc="澳洲进口和牛M7级西冷牛排，纹理清晰，脂肪分布均匀，口感鲜嫩多汁，煎烤后风味浓郁，营养丰富。">
                    <div class="product-img">🥩 精品牛肉</div>
                    <div class="product-info">
                        <div class="product-name">澳洲和牛牛排（200g）</div>
                        <div class="product-price">¥89.00</div>
                        <div class="product-stock">剩余：8份</div>
                        <button class="add-to-cart">加入购物车</button>
                    </div>
                </div>
                <!-- 产品卡片4 - 进口食材 -->
                <div class="product-card all imported"
                     data-id="4"
                     data-name="进口牛油果（2个装）" 
                     data-price="25.80" 
                     data-stock="22"
                     data-unit="2个/份"
                     data-origin="墨西哥"
                     data-shelf="5-7天"
                     data-desc="墨西哥进口牛油果，果肉绵密，口感醇厚，富含不饱和脂肪酸和多种维生素，适合直接食用、制作沙拉或辅食。">
                    <div class="product-img">🥑 牛油果</div>
                    <div class="product-info">
                        <div class="product-name">进口牛油果（2个装）</div>
                        <div class="product-price">¥25.80</div>
                        <div class="product-stock">剩余：22份</div>
                        <button class="add-to-cart">加入购物车</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 产品详情弹窗（保持不变） -->
<div class="modal-overlay" id="productModal">
    <div class="product-modal">
        <span class="close-modal" id="closeModal">×</span>
        <div class="modal-content">
            <div class="modal-product-img" id="modalProductImg">🥬</div>
            <div class="modal-product-info">
                <h3 class="modal-product-name" id="modalProductName">有机生菜（500g）</h3>
                <div class="modal-product-price" id="modalProductPrice">¥12.90</div>
                
                <div class="product-detail-item">
                    <label>规格：</label>
                    <span id="modalProductUnit">500g/份</span>
                </div>
                <div class="product-detail-item">
                    <label>产地：</label>
                    <span id="modalProductOrigin">山东寿光</span>
                </div>
                <div class="product-detail-item">
                    <label>保质期：</label>
                    <span id="modalProductShelf">7天</span>
                </div>
                <div class="product-detail-item">
                    <label>库存：</label>
                    <span id="modalProductStock">28份</span>
                </div>
                
                <div class="product-description">
                    <h4>产品详情</h4>
                    <p id="modalProductDesc">有机种植生菜，无农药残留，脆嫩爽口，富含维生素和膳食纤维，适合生食、沙拉、涮锅等多种吃法。</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // 产品分类筛选（保持不变）
    document.querySelectorAll('.category-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // 移除所有分类活跃状态
            document.querySelectorAll('.category-link').forEach(item => {
                item.classList.remove('active');
            });
            
            // 添加当前分类活跃状态
            this.classList.add('active');
            const category = this.getAttribute('data-category');
            
            // 筛选产品
            document.querySelectorAll('.product-card').forEach(card => {
                if (category === 'all' || card.classList.contains(category)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // 搜索功能（保持不变）
    document.getElementById('productSearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('.product-card').forEach(card => {
            const productName = card.getAttribute('data-name').toLowerCase();
            if (productName.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });

    // 产品详情弹窗功能（保持不变）
    const modalOverlay = document.getElementById('productModal');
    const closeModal = document.getElementById('closeModal');
    const modalProductImg = document.getElementById('modalProductImg');
    const modalProductName = document.getElementById('modalProductName');
    const modalProductPrice = document.getElementById('modalProductPrice');
    const modalProductUnit = document.getElementById('modalProductUnit');
    const modalProductOrigin = document.getElementById('modalProductOrigin');
    const modalProductShelf = document.getElementById('modalProductShelf');
    const modalProductStock = document.getElementById('modalProductStock');
    const modalProductDesc = document.getElementById('modalProductDesc');
    
    // 存储当前选中的产品信息
    let currentProduct = null;

    // 点击产品卡片打开弹窗
    document.querySelectorAll('.product-card').forEach(card => {
        // 为产品卡片添加点击事件（排除加入购物车按钮）
        card.addEventListener('click', function(e) {
            if (e.target.classList.contains('add-to-cart')) {
                // 如果点击的是加入购物车按钮，执行原有逻辑
                e.stopPropagation();
                return;
            }
            
            // 存储当前产品信息
            currentProduct = {
                id: this.getAttribute('data-id'),
                name: this.getAttribute('data-name'),
                price: this.getAttribute('data-price'),
                unit: this.getAttribute('data-unit'),
                origin: this.getAttribute('data-origin'),
                shelf: this.getAttribute('data-shelf'),
                stock: this.getAttribute('data-stock'),
                desc: this.getAttribute('data-desc'),
                icon: this.querySelector('.product-img').textContent.trim().charAt(0)
            };
            
            // 更新弹窗内容
            modalProductImg.textContent = currentProduct.icon;
            modalProductName.textContent = currentProduct.name;
            modalProductPrice.textContent = `¥${currentProduct.price}`;
            modalProductUnit.textContent = currentProduct.unit;
            modalProductOrigin.textContent = currentProduct.origin;
            modalProductShelf.textContent = currentProduct.shelf;
            modalProductStock.textContent = `${currentProduct.stock}份`;
            modalProductDesc.textContent = currentProduct.desc;
            
            // 显示弹窗
            modalOverlay.classList.add('active');
            // 禁止背景滚动
            document.body.style.overflow = 'hidden';
        });
    });

    // 关闭弹窗
    closeModal.addEventListener('click', function() {
        modalOverlay.classList.remove('active');
        document.body.style.overflow = '';
    });

    // 点击弹窗外部关闭
    modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) {
            modalOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    // 产品卡片上的加入购物车按钮逻辑（保持不变）
    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', function(e) {
            // 阻止事件冒泡到产品卡片
            e.stopPropagation(); 
            // 防止重复点击
            if (this.disabled) return;

            const productName = this.closest('.product-card').getAttribute('data-name');
            const originalText = this.textContent;
            const originalBg = this.style.backgroundColor;

            // 修改按钮状态和样式
            this.disabled = true;
            this.textContent = '已加入购物车√';
            this.style.backgroundColor = '#52c41a';

            // 1秒后恢复原始状态
            setTimeout(() => {
                this.textContent = originalText;
                this.style.backgroundColor = originalBg;
                this.disabled = false;
            }, 1000);
        });
    });
</script>

</main>
</body>
</html>