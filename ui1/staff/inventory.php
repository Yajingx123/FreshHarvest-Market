<?php
// 库存管理界面
?>
<!DOCTYPE html>
<html lang="zh-CN">
<?php include 'header.php'; ?>
<style>
.restock-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    padding: 18px 22px;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 18px;
    animation: fadeInCard 0.5s;
    transition: box-shadow 0.2s, transform 0.2s;
}
.restock-card:hover {
    box-shadow: 0 6px 24px rgba(0,0,0,0.12);
    transform: translateY(-2px) scale(1.02);
}
@keyframes fadeInCard {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
.restock-card.low { border-left: 5px solid #e53935; }
.restock-card.warn { border-left: 5px solid #ffb300; }
.restock-card .restock-btn { margin-left: auto; }
.supplier-popup {
    min-width: 320px;
    max-width: 90vw;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.18);
    padding: 32px;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%,-50%);
    z-index: 9999;
    display: none;
    animation: popupScaleIn 0.35s cubic-bezier(.4,0,.2,1);
}
@keyframes popupScaleIn {
    from { opacity: 0; transform: scale(0.8) translate(-50%,-50%); }
    to { opacity: 1; transform: scale(1) translate(-50%,-50%); }
}
.supplier-popup h3 { color: #1976d2; margin-bottom: 16px; }
.supplier-popup .info { margin-bottom: 10px; }
.supplier-popup .actions { margin-top: 22px; text-align: right; }
#popupMask {
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100vw;
    height:100vh;
    background:rgba(0,0,0,0.18);
    z-index:9998;
    animation: maskFadeIn 0.3s;
}
@keyframes maskFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
.supplier-popup input[type="text"], .supplier-popup input[type="number"] {
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 5px 10px;
    font-size: 14px;
    margin-top: 4px;
    transition: border 0.2s;
}
.supplier-popup input:focus {
    border: 1.5px solid #1976d2;
    outline: none;
}
.supplier-popup form .info {
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.inventory-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 44px;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.07);
}
.inventory-table th, .inventory-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #f0f0f0;
    text-align: left;
    font-size: 15px;
}
.inventory-table th {
    background: #f8f9fa;
    color: #1976d2;
    letter-spacing: 1px;
}
.inventory-table tr:last-child td { border-bottom: none; }
.section-title {
    font-size: 22px;
    color: #1976d2;
    margin-bottom: 18px;
    letter-spacing: 1px;
    font-weight: 700;
}
#restockList {
    margin-bottom: 32px;
}
</style>
<body>
    <main class="main">
        <section class="section">
            <h2 class="section-title">库存预警与补货</h2>
            <div id="restockList"></div>
            <div id="supplierPopup" class="supplier-popup"></div>
            <div id="popupMask" onclick="closeSupplierPopup()"></div>
            <h2 class="section-title" style="margin-top:40px;">全部库存信息</h2>
            <div style="overflow-x:auto;">
                <table class="inventory-table" id="inventoryTable">
                    <thead>
                        <tr>
                            <th>商品编号</th>
                            <th>商品名称</th>
                            <th>当前库存</th>
                            <th>补货阈值</th>
                            <th>进货源</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </section>
    </main>
    <?php include 'footer.php'; ?>
    <script>
    // 示例库存数据
    const inventory = [
        { id: 'SP20240501', name: '有机生菜', stock: 8, threshold: 15, supplier: { name: '绿源蔬菜', phone: '13888888888', address: '西安市蔬菜批发市场A区', contact: '王经理' } },
        { id: 'SP20240502', name: '进口牛奶', stock: 18, threshold: 15, supplier: { name: '新鲜乳业', phone: '13999999999', address: '西安市乳品园区', contact: '李主管' } },
        { id: 'SP20240503', name: '新鲜鸡蛋', stock: 14, threshold: 12, supplier: { name: '农家禽蛋', phone: '13777777777', address: '西安市蛋品市场', contact: '赵老板' } },
        { id: 'SP20240504', name: '优质大米', stock: 30, threshold: 10, supplier: { name: '粮油供应商', phone: '13666666666', address: '西安市粮油市场', contact: '钱经理' } }
    ];
    // 低于阈值和接近阈值（10%内）
    function getRestockList() {
        const low = [], warn = [];
        inventory.forEach(item => {
            if (item.stock < item.threshold) low.push(item);
            else if (item.stock < item.threshold + Math.max(2, Math.ceil(item.threshold*0.1))) warn.push(item);
        });
        return { low, warn };
    }
    function renderRestockList() {
        const { low, warn } = getRestockList();
        const wrap = document.getElementById('restockList');
        wrap.innerHTML = '';
        if (!low.length && !warn.length) {
            wrap.innerHTML = '<div style="color:#888;padding:32px;text-align:center;">暂无需要补货或预警的商品</div>';
            return;
        }
        low.forEach(item => wrap.appendChild(createRestockCard(item, 'low')));
        warn.forEach(item => wrap.appendChild(createRestockCard(item, 'warn')));
    }
    function createRestockCard(item, type) {
        const card = document.createElement('div');
        card.className = 'restock-card ' + type;
        card.innerHTML = `
            <div style="font-size:16px;font-weight:600;">${item.name} <span style="color:#888;font-size:13px;">(${item.id})</span></div>
            <div style="font-size:14px;color:#e53935;">当前库存：${item.stock}</div>
            <div style="font-size:14px;color:#888;">补货阈值：${item.threshold}</div>
            <button class="btn btn-primary restock-btn" onclick='openSupplierPopup(${JSON.stringify(item)})'>补货</button>
        `;
        return card;
    }
    function openSupplierPopup(item) {
        const s = item.supplier;
        document.getElementById('supplierPopup').innerHTML = `
            <h3>供货商信息</h3>
            <div class='info'><b>名称：</b>${s.name}</div>
            <div class='info'><b>联系人：</b>${s.contact}</div>
            <div class='info'><b>电话：</b>${s.phone}</div>
            <div class='info'><b>地址：</b>${s.address}</div>
            <div class='info'><b>商品：</b>${item.name} (${item.id})</div>
            <div class='info'><b>当前库存：</b>${item.stock}，<b>补货阈值：</b>${item.threshold}</div>
            <div class='actions'>
                <button class='btn btn-success' onclick='openRestockOrderForm(${JSON.stringify(item)})'>确认补货</button>
                <button class='btn btn-warning' style='margin-left:10px;' onclick='closeSupplierPopup()'>取消</button>
            </div>
        `;
        document.getElementById('supplierPopup').style.display = 'block';
        document.getElementById('popupMask').style.display = 'block';
    }
    function openRestockOrderForm(item) {
        // 假设单价
        const price = item.price || 10.0;
        document.getElementById('supplierPopup').innerHTML = `
            <h3>补货下单</h3>
            <form id='restockForm' onsubmit='submitRestockOrder(event, "${item.id}", ${price})'>
                <div class='info'><b>商品：</b>${item.name} (${item.id})</div>
                <div class='info'><b>单价：</b>¥<span id='unitPrice'>${price.toFixed(2)}</span></div>
                <div class='info'><b>供货商：</b>${item.supplier.name}</div>
                <div class='info'><b>门店：</b><input type='text' name='store' required placeholder='请输入门店名称' style='width:60%;margin-left:8px;'></div>
                <div class='info'><b>申请人：</b><input type='text' name='staff' required placeholder='请输入您的姓名' style='width:60%;margin-left:8px;'></div>
                <div class='info'><b>补货数量：</b><input type='number' name='qty' id='restockQty' min='1' value='10' required style='width:60px;margin-left:8px;' oninput='updateRestockTotal(${price})'>
                </div>
                <div class='info'><b>总金额：</b>¥<span id='restockTotal'>${(price*10).toFixed(2)}</span></div>
                <div class='actions' style='margin-top:18px;'>
                    <button class='btn btn-success' type='submit'>提交订单</button>
                    <button class='btn btn-warning' type='button' style='margin-left:10px;' onclick='closeSupplierPopup()'>取消</button>
                </div>
            </form>
        `;
    }
    function updateRestockTotal(price) {
        const qty = parseInt(document.getElementById('restockQty').value) || 0;
        document.getElementById('restockTotal').textContent = (price * qty).toFixed(2);
    }
    function submitRestockOrder(e, id, price) {
        e.preventDefault();
        const form = e.target;
        const store = form.store.value.trim();
        const staff = form.staff.value.trim();
        const qty = parseInt(form.qty.value);
        const total = (price * qty).toFixed(2);
        if (!store || !staff || !qty) return;
        document.getElementById('supplierPopup').innerHTML = `
            <h3>补货下单成功</h3>
            <div class='info'>商品：${id}</div>
            <div class='info'>数量：${qty}</div>
            <div class='info'>门店：${store}</div>
            <div class='info'>申请人：${staff}</div>
            <div class='info'>总金额：¥${total}</div>
            <div class='info' style='color:#43a047;margin-top:12px;'>订单已提交，供货商将尽快处理！</div>
            <div class='actions' style='margin-top:18px;'>
                <button class='btn btn-primary' onclick='closeSupplierPopup()'>关闭</button>
            </div>
        `;
    }
    function closeSupplierPopup() {
        document.getElementById('supplierPopup').style.display = 'none';
        document.getElementById('popupMask').style.display = 'none';
    }
    function renderInventoryTable() {
        const tbody = document.querySelector('#inventoryTable tbody');
        tbody.innerHTML = '';
        inventory.forEach(item => {
            tbody.innerHTML += `<tr>
                <td>${item.id}</td>
                <td>${item.name}</td>
                <td>${item.stock}</td>
                <td>${item.threshold}</td>
                <td>${item.supplier.name}</td>
            </tr>`;
        });
    }
    renderRestockList();
    renderInventoryTable();
    </script>
</body>
</html>
