<?php $pageTitle = "我的仪表盘"; ?>
<?php include 'header.php'; ?>

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

<!-- 仪表盘主内容区 -->
<div class="dashboard-container">
    <!-- 左侧主内容 -->
    <div class="main-content">
        <!-- 数据卡片区域 -->
        <section id="dashboard" class="dashboard">
            <h2 class="section-title">📊 我的仪表盘</h2>
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-icon">🛒</div>
                    <div class="card-title">购物车数量</div>
                    <div class="card-value">3</div>
                </div>
                <div class="card">
                    <div class="card-icon">🚚</div>
                    <div class="card-title">配送中订单</div>
                    <div class="card-value">2</div>
                </div>
                <div class="card">
                    <div class="card-icon">😋</div>
                    <div class="card-title">您最喜爱的产品</div>
                    <div class="card-value">苹果</div>
                </div>
            </div>
        </section>

        <!-- 最近订单区域 -->
        <section class="dashboard">
            <h2 class="section-title">📦 最近订单</h2>
            <ul class="order-list">
                <li class="order-item">
                    <div class="order-info">
                        <div class="order-name">新鲜蔬菜组合</div>
                        <div class="order-date">2023-10-15 14:30</div>
                    </div>
                    <span class="order-status">已完成</span>
                </li>
                <li class="order-item">
                    <div class="order-info">
                        <div class="order-name">进口水果礼盒</div>
                        <div class="order-date">2023-10-12 09:15</div>
                    </div>
                    <span class="order-status">已完成</span>
                </li>
                <li class="order-item">
                    <div class="order-info">
                        <div class="order-name">有机鸡蛋 + 鲜奶</div>
                        <div class="order-date">2023-10-10 18:45</div>
                    </div>
                    <span class="order-status">已完成</span>
                </li>
            </ul>
        </section>
    </div>

    <!-- 右侧边栏 -->
    <div class="sidebar">
        <!-- 今日推荐 -->
        <section class="sidebar-section">
            <h2 class="section-title">🌟 今日推荐</h2>
            <div class="recommendation-list">
                <div class="recommendation-item">
                    <div class="product-img">🥬</div>
                    <div class="product-info">
                        <div class="product-name">有机生菜</div>
                        <div class="product-price">¥5.90/份</div>
                    </div>
                    <!-- <button class="add-btn">加入</button> -->
                </div>
                <div class="recommendation-item">
                    <div class="product-img">🍅</div>
                    <div class="product-info">
                        <div class="product-name">新鲜番茄</div>
                        <div class="product-price">¥3.50/斤</div>
                    </div>
                    <!-- <button class="add-btn">加入</button> -->
                </div>
                <div class="recommendation-item">
                    <div class="product-img">🥕</div>
                    <div class="product-info">
                        <div class="product-name">胡萝卜</div>
                        <div class="product-price">¥2.80/斤</div>
                    </div>
                    <!-- <button class="add-btn">加入</button> -->
                </div>
            </div>
        </section>

        <!-- 配送信息 -->
        <section class="sidebar-section">
            <h2 class="section-title">🚚 配送说明</h2>
            <p style="line-height: 1.6; color: #666; font-size: 14px; margin-top: 10px;">
                今日订单截止时间：18:00<br>
                次日达区域：市区及近郊<br>
                满59元免配送费<br>
                客服热线：400-123-4567
            </p>
        </section>
    </div>
</div>

<!-- 页脚 -->
<div class="dashboard-footer">
    鲜选生鲜 © 2023 版权所有 | 新鲜直达 品质保证
</div>

</main>
</body>
</html>