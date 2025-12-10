<?php $pageTitle = "我的订单"; ?>
<?php include 'header.php'; ?>

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
    /* 优化的展开样式 - 浅绿色底色 */
    .order-detail-content {
        border: 1px solid #eee;
        border-top: none;
        border-radius: 0 0 8px 8px;
        padding: 0 15px;
        margin-top: -15px;
        background-color: #f0f7f2; /* 原有浅绿色底色 */
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
    }
</style>

<!-- 我的订单 -->
<section id="orders" class="module">
    <div class="product-section">
        <h2 class="section-title">我的订单</h2>
        <div class="tabs">
            <div class="tab active">全部订单</div>
            <div class="tab">待支付</div>
            <div class="tab">配送中</div>
        </div>
        <div class="tab-content">
            <div class="order-list">
                <!-- 订单1 -->
                <div class="order-item" data-order="1">
                    <div class="order-details">
                        <h3>订单编号：ORD20240520001</h3>
                        <p>下单时间：2024-05-20 14:30</p>
                        <p>商品：有机生菜、草莓、牛油果</p>
                        <p>金额：¥78.50</p>
                    </div>
                    <span class="order-status status-delivering">配送中</span>
                </div>
                <!-- 订单1详情 -->
                <div class="order-detail-content" id="detail-1">
                    <div class="detail-item">
                        <div class="detail-name">有机生菜（500g）</div>
                        <div class="detail-price">¥12.90</div>
                        <div class="detail-quantity">1</div>
                        <div class="detail-total">¥12.90</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-name">红颜草莓（300g）</div>
                        <div class="detail-price">¥39.90</div>
                        <div class="detail-quantity">1</div>
                        <div class="detail-total">¥39.90</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-name">进口牛油果（2个装）</div>
                        <div class="detail-price">¥25.80</div>
                        <div class="detail-quantity">1</div>
                        <div class="detail-total">¥25.80</div>
                    </div>
                    <div class="detail-summary">
                        合计：¥78.60（含配送费¥5.00）
                    </div>
                </div>

                <!-- 订单2 -->
                <div class="order-item" data-order="2">
                    <div class="order-details">
                        <h3>订单编号：ORD20240519003</h3>
                        <p>下单时间：2024-05-19 09:15</p>
                        <p>商品：澳洲和牛牛排、生菜</p>
                        <p>金额：¥101.90</p>
                    </div>
                    <span class="order-status status-pending">待支付</span>
                </div>
                <!-- 订单2详情 -->
                <div class="order-detail-content" id="detail-2">
                    <div class="detail-item">
                        <div class="detail-name">澳洲和牛牛排（200g）</div>
                        <div class="detail-price">¥89.00</div>
                        <div class="detail-quantity">1</div>
                        <div class="detail-total">¥89.00</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-name">有机生菜（500g）</div>
                        <div class="detail-price">¥12.90</div>
                        <div class="detail-quantity">1</div>
                        <div class="detail-total">¥12.90</div>
                    </div>
                    <div class="detail-summary">
                        合计：¥101.90（含配送费¥5.00）
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    // 订单标签切换
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.tab').forEach(t => {
                t.classList.remove('active');
            });
            this.classList.add('active');
        });
    });

    // 订单详情展开/折叠 - 支持多订单同时展开
    document.querySelectorAll('.order-item').forEach(item => {
        item.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order');
            const detail = document.getElementById(`detail-${orderId}`);
            // 仅切换当前订单的展开状态，不关闭其他订单
            detail.classList.toggle('active');
        });
    });
</script>

</main>
</body>
</html>