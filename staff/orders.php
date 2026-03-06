<?php
header("Content-Type: text/html; charset=UTF-8");
session_start();
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    header('Location: ../login/login.php');
    exit();
}

$servername = "localhost";
$username = "staff_user";
$password = "YourPassword123!";
$dbname = "mydb";

$ordersData = [];
$error_message = '';
$branchId = $_SESSION['staff_branch_id'] ?? null;

/**
 * Generate a badge image for products without photos.
 */
function buildProductBadge($productId, $productName)
{
    $palette = ['#ffb74d','#ffd180','#4dd0e1','#a5d6a7','#ce93d8','#ff8a80'];
    $color = $palette[$productId % count($palette)];
    $label = $productName ?: 'Item';
    if (function_exists('mb_substr')) {
        $label = mb_substr($label, 0, 2, 'UTF-8');
    } else {
        $label = substr($label, 0, 2);
    }
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60">'
        . '<rect width="60" height="60" rx="10" fill="' . $color . '"/>'
        . '<text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" '
        . 'font-size="24" fill="#ffffff" font-family="Arial, sans-serif">' . $label . '</text>'
        . '</svg>';
    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    $error_message = "Database connection failed: " . $conn->connect_error;
} elseif ($branchId === null) {
    $error_message = "Unable to determine the current branch. Please sign in again.";
} else {
    $conn->set_charset("utf8mb4");

    $sql_orders = "SELECT order_ID, order_date, total_amount, status, shipping_address,
                          customer_ID, customer_phone, customer_email, loyalty_level,
                          first_name, last_name
                   FROM v_staff_order_overview
                   WHERE branch_ID = ?
                   ORDER BY order_date DESC
                   LIMIT 100";

    if ($stmt_orders = $conn->prepare($sql_orders)) {
        $stmt_orders->bind_param("i", $branchId);
        if ($stmt_orders->execute()) {
            $result_orders = $stmt_orders->get_result();
            while ($row = $result_orders->fetch_assoc()) {
                $customerName = trim(($row['last_name'] ?? '') . ' ' . ($row['first_name'] ?? ''));
                if ($customerName === '') {
                    $customerName = 'Anonymous Customer';
                }
                $orderId = (int)$row['order_ID'];
                $ordersData[$orderId] = [
                    'id' => $orderId,
                    'display_id' => 'ORD' . str_pad($orderId, 6, '0', STR_PAD_LEFT),
                    'order_time' => $row['order_date'] ? date('Y-m-d H:i', strtotime($row['order_date'])) : '--',
                    'status' => $row['status'] ?? 'Pending',
                    'shipping_address' => $row['shipping_address'] ?: 'No shipping address',
                    'total_amount' => (float)$row['total_amount'],
                    'customer' => [
                        'name' => $customerName,
                        'phone' => $row['customer_phone'] ?: 'Not provided',
                        'email' => $row['customer_email'] ?: 'Not provided',
                        'loyalty' => $row['loyalty_level'] ?: 'Regular'
                    ],
                    'items' => []
                ];
            }
        } else {
            $error_message = "Failed to load orders: " . $conn->error;
        }
        $stmt_orders->close();
    } else {
        $error_message = "Failed to prepare order query: " . $conn->error;
    }

    if (!$error_message && count($ordersData) > 0) {
        $sql_items = "SELECT order_ID, product_ID, product_name, sku, quantity, unit_price
                      FROM v_staff_order_items_summary
                      WHERE branch_ID = ?
                      ORDER BY order_ID DESC";
        if ($stmt_items = $conn->prepare($sql_items)) {
            $stmt_items->bind_param("i", $branchId);
            if ($stmt_items->execute()) {
                $result_items = $stmt_items->get_result();
                while ($row = $result_items->fetch_assoc()) {
                    $oid = (int)$row['order_ID'];
                    if (!isset($ordersData[$oid])) {
                        continue;
                    }
                    $unitPrice = $row['unit_price'] !== null ? (float)$row['unit_price'] : 0;
                    $quantity = (int)$row['quantity'];
                    $ordersData[$oid]['items'][] = [
                        'product_id' => (int)$row['product_ID'],
                        'name' => $row['product_name'],
                        'sku' => $row['sku'],
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => round($unitPrice * $quantity, 2),
                        'image' => buildProductBadge((int)$row['product_ID'], $row['product_name'])
                    ];
                }
            } else {
                $error_message = "Failed to load order items: " . $conn->error;
            }
            $stmt_items->close();
        } else {
            $error_message = "Failed to prepare order items query: " . $conn->error;
        }
    }
}

if ($conn instanceof mysqli) {
    $conn->close();
}

$ordersJson = json_encode(array_values($ordersData), JSON_UNESCAPED_UNICODE);
if ($ordersJson === false) {
    $ordersJson = '[]';
} else {
    $ordersJson = str_replace('</', '<\/', $ordersJson);
}
?>
<!DOCTYPE html>
<html lang="en">
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
.order-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
.status-tag {
    display:inline-flex;
    align-items:center;
    padding:2px 10px;
    border-radius:999px;
    font-size:12px;
    color:#fff;
}
.status-completed { background:#43a047; }
.status-pending { background:#fb8c00; }
.status-cancelled { background:#e53935; }
.order-item-card {
    display:flex;
    align-items:center;
    background:#fff;
    border-radius:8px;
    padding:10px 14px;
    border:1px solid #eee;
    box-shadow:0 1px 4px rgba(0,0,0,0.04);
}
.product-thumb {
    width:48px;
    height:48px;
    border-radius:8px;
    margin-right:12px;
    border:1px solid #eee;
    object-fit:cover;
}
.error-box {
    background:#fff3e0;
    border:1px solid #ffc107;
    color:#b26a00;
    padding:12px 16px;
    border-radius:8px;
    margin-bottom:16px;
}
</style>
<body>
    <main class="main">
        <section class="section">
            <h2 class="section-title">All Branch Orders</h2>
            <div class="filter-bar">
                <input type="text" class="filter-input" id="orderSearch" placeholder="Search order ID / customer name">
                <button class="btn btn-primary" onclick="filterOrders()">Search</button>
            </div>
            <?php if ($error_message): ?>
            <div class="error-box"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <div id="ordersList" style="display:flex;flex-wrap:wrap;gap:24px;"></div>
            <div id="popupCard" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.18);padding:32px;z-index:9999;min-width:320px;max-width:90vw;"></div>
            <div id="popupMask" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9998;" onclick="closePopup()"></div>
        </section>
    </main>
    <?php include 'footer.php'; ?>
    <script>
    const orders = <?php echo $ordersJson ?: '[]'; ?>;

    function getStatusMeta(status) {
        const normalized = (status || '').toLowerCase();
        if (normalized === 'completed') return { label: 'Completed', cls: 'status-completed' };
        if (normalized === 'cancelled') return { label: 'Cancelled', cls: 'status-cancelled' };
        return { label: normalized === 'pending' ? 'Pending' : (status || 'Pending'), cls: 'status-pending' };
    }

    function formatCurrency(value) {
        const num = Number(value);
        return Number.isFinite(num) ? num.toFixed(2) : '0.00';
    }

    function renderOrders(list) {
        const wrap = document.getElementById('ordersList');
        wrap.innerHTML = '';
        if (!list || !list.length) {
            wrap.innerHTML = '<div style="padding:32px;color:#888;text-align:center;width:100%;">No orders found</div>';
            return;
        }
        list.forEach(order => {
            const card = document.createElement('div');
            card.className = 'order-card';
            card.style = 'background:#fff;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.06);padding:20px;width:340px;position:relative;';
            const statusMeta = getStatusMeta(order.status);
            const customer = order.customer || {};
            const customerJson = JSON.stringify(customer || {});
            const addressJson = JSON.stringify(order.shipping_address || '');
            const detailHtml = (order.items && order.items.length)
                ? order.items.map(item => `
                    <div class="order-item-card">
                        <img class="product-thumb" src="${item.image}" alt="${item.name || 'Item'}">
                        <div style="flex:1;">
                            <div style="font-size:14px;font-weight:500;color:#333;">${item.name || 'Item'}</div>
                            <div style="font-size:13px;color:#888;">SKU：${item.sku || '--'}</div>
                            <div style="font-size:13px;color:#888;">Qty: <span style="color:#1976d2;">${item.quantity || 0}</span> × Unit ¥${formatCurrency(item.unit_price)}</div>
                        </div>
                        <div style="font-size:14px;color:#ff7043;font-weight:600;">¥${formatCurrency(item.total_price)}</div>
                    </div>
                `).join('')
                : '<div style="padding:8px 0;color:#999;">No item details</div>';
            card.innerHTML = `
                <div class="order-header">
                    <div style="font-size:15px;font-weight:600;color:#ff7043;">Order: ${order.display_id || ('#' + order.id)}</div>
                    <span class="status-tag ${statusMeta.cls}">${statusMeta.label}</span>
                </div>
                <div style="font-size:13px;color:#666;margin-bottom:8px;">Placed at: ${order.order_time || '--'}</div>
                <div style="margin-bottom:8px;">
                    <span style="font-size:13px;color:#888;">Order total:</span><span style="color:#43a047;font-weight:600;"> ¥${formatCurrency(order.total_amount)}</span>
                </div>
                <div style="margin-bottom:8px;">
                    <span style="font-size:13px;color:#888;">Shipping address:</span> ${order.shipping_address || 'No shipping address'}
                </div>
                <div style="margin-bottom:8px;">
                    <span style="font-size:13px;color:#888;">Customer:</span>
                    <a href="javascript:void(0)" style="color:#1976d2;text-decoration:underline;" onclick='showCustomer(${customerJson}, ${addressJson})'>
                        ${customer.name || 'Anonymous Customer'}
                    </a>
                </div>
                <button class="btn btn-primary" style="margin-top:10px;width:100%;" onclick="toggleDetail(this)">View details</button>
                <div class="order-detail" style="margin-top:12px;background:#f8f9fa;border-radius:8px;padding:12px;">
                    <div style="font-size:13px;color:#888;margin-bottom:6px;">Items:</div>
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        ${detailHtml}
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
            btn.textContent = 'Hide details';
        } else {
            detail.classList.remove('show');
            btn.textContent = 'View details';
        }
    }

    function showCustomer(cust, address) {
        showPopupCard(`<h3 style='color:#1976d2;font-size:18px;margin-bottom:12px;'>Customer Details</h3>
            <div style='font-size:15px;margin-bottom:8px;'><b>Name:</b> ${cust.name || 'Anonymous Customer'}</div>
            <div style='font-size:15px;margin-bottom:8px;'><b>Phone:</b> ${cust.phone || 'Not provided'}</div>
            <div style='font-size:15px;margin-bottom:8px;'><b>Email:</b> ${cust.email || 'Not provided'}</div>
            <div style='font-size:15px;margin-bottom:8px;'><b>Loyalty:</b> ${cust.loyalty || 'Regular'}</div>
            <div style='font-size:15px;margin-bottom:8px;'><b>Shipping address:</b> ${address || 'No shipping address'}</div>
            <button class='btn btn-primary' style='margin-top:16px;width:100%;' onclick='closePopup()'>Close</button>
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
        if (!q) {
            renderOrders(orders);
            return;
        }
        const filtered = orders.filter(order => {
            const idText = ((order.display_id || '') + String(order.id || '')).toLowerCase();
            const nameText = order.customer && order.customer.name ? order.customer.name.toLowerCase() : '';
            return idText.includes(q) || nameText.includes(q);
        });
        renderOrders(filtered);
    }

    renderOrders(orders);
    </script>
</body>
</html>
