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
    }
    
    /* 购物车列表区域 */
    .cart-list-wrapper {
        flex: 1;
        overflow-y: auto;
        margin-bottom: 20px;
        padding-right: 10px;
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
                    <input type="text" id="receiver-name" class="form-input" value="张三">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="receiver-phone">联系电话</label>
                    <input type="tel" id="receiver-phone" class="form-input" value="13800138000">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="receiver-province">所在地区</label>
                    <select id="receiver-province" class="form-input">
                        <option value="北京" selected>北京市</option>
                        <option value="上海">上海市</option>
                        <option value="广东">广东省</option>
                        <option value="江苏">江苏省</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="receiver-address">详细地址</label>
                    <input type="text" id="receiver-address" class="form-input" value="朝阳区建国路88号8号楼1单元501室">
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
                <!-- 购物车项目1 -->
                <div class="cart-item">
                    <div class="product-img">🥬</div>
                    <div class="cart-item-info">
                        <div class="cart-item-name">有机生菜（500g）</div>
                        <div class="cart-item-price">¥12.90</div>
                    </div>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn minus">-</button>
                        <input type="number" class="quantity-input" value="1" min="1">
                        <button class="quantity-btn plus">+</button>
                    </div>
                    <button class="remove-from-cart">删除</button>
                </div>
                
                <!-- 购物车项目2 -->
                <div class="cart-item">
                    <div class="product-img">🍓</div>
                    <div class="cart-item-info">
                        <div class="cart-item-name">红颜草莓（300g）</div>
                        <div class="cart-item-price">¥39.90</div>
                    </div>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn minus">-</button>
                        <input type="number" class="quantity-input" value="1" min="1">
                        <button class="quantity-btn plus">+</button>
                    </div>
                    <button class="remove-from-cart">删除</button>
                </div>
                
                <!-- 购物车项目3 -->
                <div class="cart-item">
                    <div class="product-img">🥑</div>
                    <div class="cart-item-info">
                        <div class="cart-item-name">进口牛油果（2个装）</div>
                        <div class="cart-item-price">¥25.80</div>
                    </div>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn minus">-</button>
                        <input type="number" class="quantity-input" value="1" min="1">
                        <button class="quantity-btn plus">+</button>
                    </div>
                    <button class="remove-from-cart">删除</button>
                </div>
            </div>
            
            <!-- 分隔线 -->
            <div class="checkout-divider"></div>
            
            <!-- 结算栏 -->
            <div class="checkout-bar">
                <div class="total-amount">总计：<span>¥78.60</span></div>
                <button class="checkout-btn">结算</button>
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
                <div id="order-total" class="cart-item-price"></div>
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

<!-- 提交成功弹窗 -->
<div class="modal-overlay" id="successModal">
    <div class="modal">
        <button class="modal-close">&times;</button>
        <div class="success-message">
            <div class="success-icon">✓</div>
            <h3 class="modal-title">提交成功</h3>
            <p>您的订单已提交，我们将尽快为您处理</p>
        </div>
    </div>
</div>

<script>
    // 购物车数量调整
    document.querySelectorAll('.quantity-btn.plus').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.quantity-input');
            input.value = parseInt(input.value) + 1;
            updateTotal();
        });
    });

    document.querySelectorAll('.quantity-btn.minus').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.quantity-input');
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
                updateTotal();
            }
        });
    });

    // 数量输入框直接修改
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            if (this.value < 1 || isNaN(this.value)) {
                this.value = 1;
            }
            updateTotal();
        });
    });

    // 删除购物车项目
    document.querySelectorAll('.remove-from-cart').forEach(btn => {
        btn.addEventListener('click', function() {
            // 添加删除动画
            const item = this.closest('.cart-item');
            item.style.height = item.offsetHeight + 'px';
            item.style.overflow = 'hidden';
            item.style.transition = 'all 0.3s';
            item.style.opacity = '0';
            item.style.transform = 'translateX(20px)';
            
            setTimeout(() => {
                item.remove();
                updateTotal();
            }, 300);
        });
    });

    // 更新总金额
    function updateTotal() {
        let total = 0;
        document.querySelectorAll('.cart-item').forEach(item => {
            const price = parseFloat(item.querySelector('.cart-item-price').textContent.replace('¥', ''));
            const quantity = parseInt(item.querySelector('.quantity-input').value);
            total += price * quantity;
        });
        
        // 总金额动画效果
        const totalElement = document.querySelector('.total-amount span');
        const oldValue = parseFloat(totalElement.textContent.replace('¥', ''));
        const newValue = total;
        
        if (oldValue !== newValue) {
            totalElement.style.transition = 'color 0.3s';
            totalElement.style.color = '#ff7a7a';
            
            setTimeout(() => {
                totalElement.textContent = `¥${newValue.toFixed(2)}`;
                totalElement.style.color = '';
            }, 200);
        } else {
            totalElement.textContent = `¥${newValue.toFixed(2)}`;
        }
    }

    // 弹窗相关元素
    const checkoutModal = document.getElementById('checkoutModal');
    const successModal = document.getElementById('successModal');
    const checkoutBtn = document.querySelector('.checkout-btn');
    const closeButtons = document.querySelectorAll('.modal-close');
    const submitOrderBtn = document.querySelector('.submit-order-btn');
    const paymentOptions = document.querySelectorAll('.payment-option');

    // 打开结算弹窗
    checkoutBtn.addEventListener('click', function() {
        // 检查购物车是否为空
        if (document.querySelectorAll('.cart-item').length === 0) {
            alert('购物车为空，无法结算');
            return;
        }
        
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

    // 提交订单
    submitOrderBtn.addEventListener('click', function() {
        checkoutModal.classList.remove('active');
        
        // 显示成功弹窗前的短暂延迟
        setTimeout(() => {
            successModal.classList.add('active');
            
            // 5秒后自动关闭成功弹窗
            setTimeout(() => {
                successModal.classList.remove('active');
            }, 5000);
        }, 300);
    });

    // 填充订单详情
    function populateOrderDetails() {
        const orderItemsContainer = document.getElementById('order-items');
        const shippingInfoContainer = document.getElementById('shipping-info');
        const orderTotalContainer = document.getElementById('order-total');
        
        // 清空现有内容
        orderItemsContainer.innerHTML = '';
        
        // 填充商品明细
        document.querySelectorAll('.cart-item').forEach(item => {
            const name = item.querySelector('.cart-item-name').textContent;
            const price = item.querySelector('.cart-item-price').textContent;
            const quantity = item.querySelector('.quantity-input').value;
            const itemTotal = (parseFloat(price.replace('¥', '')) * parseInt(quantity)).toFixed(2);
            
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
        
        // 填充收货信息
        const receiverName = document.getElementById('receiver-name').value;
        const receiverPhone = document.getElementById('receiver-phone').value;
        const receiverProvince = document.getElementById('receiver-province').value;
        const receiverAddress = document.getElementById('receiver-address').value;
        const receiverNote = document.getElementById('receiver-note').value;
        
        shippingInfoContainer.innerHTML = `
            <p>收件人：${receiverName}</p>
            <p>联系电话：${receiverPhone}</p>
            <p>地址：${receiverProvince} ${receiverAddress}</p>
            <p>备注：${receiverNote}</p>
        `;
        
        // 填充总金额
        orderTotalContainer.textContent = document.querySelector('.total-amount span').textContent;
    }
</script>

</main>
</body>
</html>