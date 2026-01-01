<?php
session_start();
require_once __DIR__ . '/inc/data.php';
require_once __DIR__ . '/header.php';

$supplierId = getCurrentSupplierId();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_price'])) {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    if ($supplierId <= 0 || $productId <= 0 || $price <= 0) {
        $error = '请输入有效的单价。';
    } else {
        if (updateSupplierProductPrice($supplierId, $productId, $price)) {
            $message = '单价已更新。';
        } else {
            $error = '更新失败，请稍后重试。';
        }
    }
}

$products = getSupplierProducts($supplierId);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>鲜选生鲜 - 供应商产品管理</title>
    <style>
        body { background-color: #f8f9fa; color: #333; font-family: 'Microsoft YaHei', Arial, sans-serif; }
        .header {
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .nav-container {
            width: 90%;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #1976d2;
            text-decoration: none;
        }
        .nav-menu {
            display: flex;
            list-style: none;
        }
        .nav-item {
            margin-left: 30px;
        }
        .nav-link {
            text-decoration: none;
            color: #333;
            font-size: 16px;
            font-weight: 500;
            transition: color 0.3s;
            display: inline-flex;
            align-items: center;
        }
        .nav-link:hover {
            color: #1976d2;
        }
        .nav-link.active {
            color: #1976d2;
            font-weight: bold;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background-color: #e53935;
            color: white;
            font-size: 12px;
            font-weight: bold;
            margin-left: 5px;
            padding: 0;
            line-height: 1;
        }
        .main { width: 90%; margin: 30px auto; min-height: calc(100vh - 200px); }
        .section { background-color: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); padding: 25px; margin-bottom: 30px; }
        .section-title { font-size: 22px; color: #ff7043; margin-bottom: 18px; font-weight: 700; border-left: 4px solid #ff7043; padding-left: 10px; }
        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
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
        .alert-box {
            background:#fff3e0;
            border:1px solid #ffc107;
            color:#b26a00;
            padding:12px 16px;
            border-radius:8px;
            margin-bottom:20px;
        }
        .alert-box.alert-error {
            background:#ffebee;
            border-color:#e53935;
            color:#c62828;
        }
        @media (max-width: 768px) {
            .nav-menu { display: none; }
        }
    </style>
</head>
<body>
    <main class="main">
        <section class="section">
            <h2 class="section-title">产品管理</h2>
            <?php if ($message): ?>
                <div class="alert-box" id="priceSuccess"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert-box alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <div style="overflow-x:auto;">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>商品编号</th>
                            <th>商品名称</th>
                            <th>商品描述</th>
                            <th>单价</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center;color:#888;padding:24px;">暂无可供产品</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['sku'] ?? $product['product_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($product['product_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($product['description'] ?? '暂无描述'); ?></td>
                                    <td>¥<?php echo number_format((float)($product['price'] ?? 0), 2); ?></td>
                                    <td>
                                        <form method="post" style="display:flex;gap:8px;align-items:center;">
                                            <input type="hidden" name="update_price" value="1">
                                            <input type="hidden" name="product_id" value="<?php echo (int)$product['product_ID']; ?>">
                                            <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($product['price'] ?? 0); ?>" style="width:120px;">
                                            <button class="btn btn-primary" type="submit">编辑</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <script>
        const successNotice = document.getElementById('priceSuccess');
        if (successNotice) {
            setTimeout(() => {
                successNotice.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>
