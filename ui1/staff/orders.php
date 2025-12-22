<?php
// 订单管理页面
?>
<!DOCTYPE html>
<html lang="zh-CN">
<?php include 'header.php'; ?>
<style>
/* 卡片淡入动画 */
.order-card {
    animation: fadeInCard 0.5s ease;
    transition: box-shadow 0.2s;
}
@keyframes fadeInCard {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
.order-card:hover {
    box-shadow: 0 6px 24px rgba(0,0,0,0.12);
}
/* 详情展开动画 */
.order-detail {
    transition: max-height 0.4s cubic-bezier(.4,0,.2,1), opacity 0.3s;
    overflow: hidden;
    max-height: 0;
    opacity: 0;
}
.order-detail.show {
    max-height: 400px;
    opacity: 1;
}
/* 弹窗卡片动画 */
#popupCard {
    animation: popupScaleIn 0.35s cubic-bezier(.4,0,.2,1);
}
@keyframes popupScaleIn {
    from { opacity: 0; transform: scale(0.8) translate(-50%,-50%); }
    to { opacity: 1; transform: scale(1) translate(-50%,-50%); }
}
#popupMask {
    animation: maskFadeIn 0.3s;
}
@keyframes maskFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>
<body>
    <main class="main">
        <section class="section">
            <h2 class="section-title">门店全部订单</h2>
            <div class="filter-bar">
                <input type="text" class="filter-input" id="orderSearch" placeholder="搜索订单编号/顾客姓名">
                <button class="btn btn-primary" onclick="filterOrders()">搜索</button>
            </div>
            <div id="ordersList" style="display:flex;flex-wrap:wrap;gap:24px;"></div>
            <div id="popupCard" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:32px;z-index:9999;min-width:320px;max-width:90vw;"></div>
            <div id="popupMask" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9998;" onclick="closePopup()"></div>
        </section>
    </main>
    <?php include 'footer.php'; ?>
    <script>
    // 产品库（模拟真实数据）
    const productDict = {
        'SP20240501': {
            name: '有机苹果',
            img: 'https://img1.baidu.com/it/u=1234567890,1234567890&fm=253&fmt=auto&app=138&f=JPEG?w=200&h=200',
            price: 12.8
        },
        'SP20240502': {
            name: '进口牛奶',
            img: 'https://img1.baidu.com/it/u=2345678901,2345678901&fm=253&fmt=auto&app=138&f=JPEG?w=200&h=200',
            price: 19.9
        },
        'SP20240503': {
            name: '新鲜鸡蛋',
            img: 'https://img1.baidu.com/it/u=3456789012,3456789012&fm=253&fmt=auto&app=138&f=JPEG?w=200&h=200',
            price: 8.5
        },
        'SP20240506': {
            name: '优质大米',
            img: 'https://img1.baidu.com/it/u=4567890123,4567890123&fm=253&fmt=auto&app=138&f=JPEG?w=200&h=200',
            price: 32.0
        }
        // ...更多产品
    };
    // 示例订单数据
    const orders = [
        {
            id: 'ORD20240520012',
            products: [ {id: 'SP20240501', qty: 2}, {id: 'SP20240502', qty: 3} ],
            customer: {name: '李女士', phone: '13800001111', address: '高新路1号'},
            type: '支付宝',
            amount: 89.50,
            courier: {name: '王强', phone: '13512345678', gender: '男'},
            address: '高新路1号',
            time: '2024-05-20 10:15'
        },
        {
            id: 'ORD20240520013',
            products: [ {id: 'SP20240503', qty: 1}, {id: 'SP20240506', qty: 2} ],
            customer: {name: '张先生', phone: '13900002222', address: '曲江商圈2号'},
            type: '微信支付',
            amount: 45.90,
            courier: {name: '李静', phone: '13698765432', gender: '女'},
            address: '曲江商圈2号',
            time: '2024-05-20 11:00'
        }
        // ...更多订单
    ];
    function renderOrders(list) {
        const wrap = document.getElementById('ordersList');
        wrap.innerHTML = '';
        if (!list.length) {
            wrap.innerHTML = '<div style="padding:32px;color:#888;text-align:center;width:100%;">暂无订单</div>';
            return;
        }
        list.forEach(order => {
            const card = document.createElement('div');
            card.className = 'order-card';
            card.style = 'background:#fff;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.06);padding:20px;width:340px;position:relative;';
            card.innerHTML = `
                <div style="font-size:15px;font-weight:600;color:#ff7043;margin-bottom:8px;">订单号：${order.id}</div>
                <div style="font-size:13px;color:#666;margin-bottom:8px;">下单时间：${order.time}</div>
                <div style="margin-bottom:8px;">
                    <span style="font-size:13px;color:#888;">交易方式：</span>${order.type}
                </div>
                <div style="margin-bottom:8px;">
                    <span style="font-size:13px;color:#888;">订单金额：</span><span style="color:#43a047;font-weight:600;">¥${order.amount.toFixed(2)}</span>
                </div>
                <div style="margin-bottom:8px;">
                    <span style="font-size:13px;color:#888;">配送地址：</span>${order.address}
                </div>
                <div style="margin-bottom:8px;">
                    <span style="font-size:13px;color:#888;">顾客：</span><a href="javascript:void(0)" style="color:#1976d2;text-decoration:underline;" onclick='showCustomer(${JSON.stringify(order.customer)})'>${order.customer.name}</a>
                </div>
                <div style="margin-bottom:8px;">
                    <span style="font-size:13px;color:#888;">配送员：</span><a href="javascript:void(0)" style="color:#ad1457;text-decoration:underline;" onclick='showCourier(${JSON.stringify(order.courier)})'>${order.courier.name}</a>
                </div>
                <button class="btn btn-primary" style="margin-top:10px;width:100%;" onclick="toggleDetail(this)">展开详情</button>
                <div class="order-detail" style="margin-top:12px;background:#f8f9fa;border-radius:8px;padding:12px;">
                    <div style="font-size:13px;color:#888;margin-bottom:6px;">产品明细：</div>
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        ${order.products.map(p=>{
                            const prod = productDict[p.id] || {};
                            return `<div style='display:flex;align-items:center;background:#fff;border-radius:8px;padding:8px 12px;border:1px solid #eee;box-shadow:0 1px 4px rgba(0,0,0,0.04);'>
                                <img src='${prod.img||'https://via.placeholder.com/48x48?text=No+Img'}' alt='${prod.name||p.id}' style='width:48px;height:48px;border-radius:6px;object-fit:cover;margin-right:12px;border:1px solid #eee;'>
                                <div style='flex:1;'>
                                    <div style='font-size:14px;font-weight:500;color:#333;'>${prod.name||p.id}</div>
                                    <div style='font-size:13px;color:#888;'>单价：<span style='color:#43a047;'>¥${prod.price ? prod.price.toFixed(2) : '--'}</span></div>
                                    <div style='font-size:13px;color:#888;'>数量：<span style='color:#1976d2;'>${p.qty}</span></div>
                                </div>
                                <div style='font-size:14px;color:#ff7043;font-weight:600;'>¥${prod.price ? (prod.price*p.qty).toFixed(2) : '--'}</div>
                            </div>`;
                        }).join('')}
                    </div>
                </div>
            `;
            wrap.appendChild(card);
        });
    }
    function toggleDetail(btn) {
        const detail = btn.parentElement.querySelector('.order-detail');
        if (!detail) return;
        if (!detail.classList.contains('show')) {
            detail.classList.add('show');
            btn.textContent = '收起详情';
        } else {
            detail.classList.remove('show');
            btn.textContent = '展开详情';
        }
    }
    function showCustomer(cust) {
        showPopupCard(`<h3 style='color:#1976d2;font-size:18px;margin-bottom:12px;'>顾客信息</h3>
            <div style='font-size:15px;margin-bottom:8px;'><b>姓名：</b>${cust.name}</div>
            <div style='font-size:15px;margin-bottom:8px;'><b>电话：</b>${cust.phone}</div>
            <div style='font-size:15px;margin-bottom:8px;'><b>地址：</b>${cust.address}</div>
            <div style='font-size:15px;margin-bottom:8px;'><b>VIP：</b>${cust.vip ? '是' : '否'}</div>
            <button class='btn btn-primary' style='margin-top:16px;width:100%;' onclick='closePopup()'>关闭</button>
        `);
    }
    function showCourier(courier) {
        showPopupCard(`<h3 style='color:#ad1457;font-size:18px;margin-bottom:12px;'>配送员信息</h3>
            <div style='font-size:15px;margin-bottom:8px;'><b>姓名：</b>${courier.name}</div>
            <div style='font-size:15px;margin-bottom:8px;'><b>电话：</b>${courier.phone}</div>
            <div style='font-size:15px;margin-bottom:8px;'><b>性别：</b>${courier.gender}</div>
            <button class='btn btn-primary' style='margin-top:16px;width:100%;' onclick='closePopup()'>关闭</button>
        `);
    }
    function showPopupCard(html) {
        const popup = document.getElementById('popupCard');
        popup.innerHTML = html;
        popup.style.display = 'block';
        popup.style.animation = 'popupScaleIn 0.35s cubic-bezier(.4,0,.2,1)';
        document.getElementById('popupMask').style.display = 'block';
        document.getElementById('popupMask').style.animation = 'maskFadeIn 0.3s';
    }
    function closePopup() {
        document.getElementById('popupCard').style.display = 'none';
        document.getElementById('popupMask').style.display = 'none';
    }
    function filterOrders() {
        const q = document.getElementById('orderSearch').value.trim().toLowerCase();
        if (!q) { renderOrders(orders); return; }
        renderOrders(orders.filter(o => o.id.toLowerCase().includes(q) || o.customer.name.toLowerCase().includes(q)));
    }
    renderOrders(orders);
    </script>
</body>
</html>
