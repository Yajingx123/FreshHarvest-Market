<?php
header("Content-Type: text/html; charset=UTF-8");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    header('Location: ../login/login.php');
    exit();
}

$servername = "localhost";
$username = "root";
$password = "NewRootPwd123!";
$dbname = "mydb";

$error_message = '';
$success_message = $_SESSION['inventory_success'] ?? '';
unset($_SESSION['inventory_success']);
$inventorySummary = [];
$inventoryBatches = [];
$supplierOptions = [];
$branchId = $_SESSION['staff_branch_id'] ?? null;
$currentBranchName = '';

function buildSupplierInfo(array $row): array {
    return [
        'id' => $row['supplier_id'] ?? null,
        'name' => $row['supplier_name'] ?? '未知供应商',
        'contact' => $row['supplier_contact'] ?? '未登记',
        'phone' => $row['supplier_phone'] ?? '未提供',
        'address' => $row['supplier_address'] ?? '未提供'
    ];
}

function generateBatchId(mysqli $conn, int $branchId, string $sku): string {
    $prefix = 'SKU';
    if (strpos($sku, '-') !== false) {
        $prefix = strtoupper(substr($sku, 0, strpos($sku, '-')));
    } else {
        $prefix = strtoupper(substr($sku, 0, 3));
    }
    $likePattern = sprintf('B%d-%s-%%', $branchId, $prefix);
    $stmt = $conn->prepare('SELECT batch_ID FROM Inventory WHERE branch_ID = ? AND batch_ID LIKE ? ORDER BY batch_ID DESC LIMIT 1');
    $nextNumber = 1;
    if ($stmt) {
        $stmt->bind_param('is', $branchId, $likePattern);
        if ($stmt->execute()) {
            $stmt->bind_result($lastBatch);
            if ($stmt->fetch()) {
                if (preg_match('/-(\d+)$/', $lastBatch, $matches)) {
                    $nextNumber = (int)$matches[1] + 1;
                }
            }
        }
        $stmt->close();
    }
    return sprintf('B%d-%s-%03d', $branchId, $prefix, $nextNumber);
}

function generateStockItemId(string $batchId, int $index): string {
    $clean = preg_replace('/[^A-Z0-9]/i', '', $batchId);
    return sprintf('SI-%s-%03d', strtoupper($clean), $index);
}

function getNextStockItemIndex(mysqli $conn, string $batchId): int {
    $stmt = $conn->prepare('SELECT item_ID FROM StockItem WHERE batch_ID = ? ORDER BY item_ID DESC LIMIT 1');
    $max = 0;
    if ($stmt) {
        $stmt->bind_param('s', $batchId);
        if ($stmt->execute()) {
            $stmt->bind_result($itemId);
            if ($stmt->fetch()) {
                if (preg_match('/-(\d+)$/', $itemId, $matches)) {
                    $max = (int)$matches[1];
                }
            }
        }
        $stmt->close();
    }
    return $max;
}

if ($branchId === null) {
    $error_message = '无法确定当前门店，请重新登录。';
} else {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        $error_message = '数据库连接失败：' . $conn->connect_error;
    } else {
        try {
            $conn->set_charset('utf8mb4');

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_action'])) {
                $batchId = trim($_POST['batch_id'] ?? '');
                $newQuantity = isset($_POST['new_quantity']) ? (int)$_POST['new_quantity'] : -1;
                $reason = $_POST['adjust_reason'] ?? '';
                $allowedReasons = ['return', 'transfer', 'adjustment'];

                if ($batchId === '' || $newQuantity < 0 || !in_array($reason, $allowedReasons, true)) {
                    $error_message = '请填写正确的调整信息。';
                } else {
                    $stmtBatch = $conn->prepare('SELECT batch_ID, product_ID, branch_ID, quantity_on_hand, order_ID, date_expired, received_date FROM Inventory WHERE batch_ID = ? AND branch_ID = ? LIMIT 1');
                    $stmtBatch->bind_param('si', $batchId, $branchId);
                    $stmtBatch->execute();
                    $batchRow = $stmtBatch->get_result()->fetch_assoc();
                    $stmtBatch->close();

                    if (!$batchRow) {
                        $error_message = '未找到该批次或不属于当前门店。';
                    } else {
                        $currentQty = (int)$batchRow['quantity_on_hand'];
                        $nextItemIndex = getNextStockItemIndex($conn, $batchId);
                        $delta = $newQuantity - $currentQty;
                        if ($delta === 0) {
                            $_SESSION['inventory_success'] = '库存未发生变化。';
                            header('Location: inventory.php');
                            exit();
                        }

                        $conn->begin_transaction();
                        try {
                            $statusMap = [
                                'return' => 'returned',
                                'transfer' => 'sold',
                                'adjustment' => 'damaged'
                            ];

                        if ($delta > 0) {
                            $nextIndex = $nextItemIndex;
                                $stmtStock = $conn->prepare('INSERT INTO StockItem (item_ID, batch_ID, product_ID, branch_ID, purchase_order_ID, customer_order_ID, received_date, expiry_date, status) VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?)');
                                $stmtCert = $conn->prepare('INSERT INTO StockItemCertificate (item_ID, transaction_type, date, transaction_ID) VALUES (?, ?, ?, ?)');
                                $now = date('Y-m-d H:i:s');
                                $receivedDate = $batchRow['received_date'] ?? date('Y-m-d');
                                for ($i = 1; $i <= $delta; $i++) {
                                    $nextIndex++;
                                    $itemId = generateStockItemId($batchId, $nextIndex);
                                    $stmtStock->bind_param(
                                        'ssiiisss',
                                        $itemId,
                                        $batchId,
                                        $batchRow['product_ID'],
                                        $branchId,
                                        $batchRow['order_ID'],
                                        $receivedDate,
                                        $batchRow['date_expired'],
                                        'in_stock'
                                    );
                                    if (!$stmtStock->execute()) {
                                        throw new Exception('新增单件库存失败：' . $stmtStock->error);
                                    }
                                    $transactionId = null; // 调整没有外部单据
                                    $stmtCert->bind_param('sssi', $itemId, $reason, $now, $transactionId);
                                    if (!$stmtCert->execute()) {
                                        throw new Exception('记录库存凭证失败：' . $stmtCert->error);
                                    }
                                }
                                $stmtStock->close();
                                $stmtCert->close();
                            } else {
                                $needRemove = abs($delta);
                                $stmtFetchItems = $conn->prepare("SELECT item_ID FROM StockItem WHERE batch_ID = ? AND status = 'in_stock' ORDER BY item_ID DESC");
                                $stmtFetchItems->bind_param('s', $batchId);
                                $stmtFetchItems->execute();
                                $rsItems = $stmtFetchItems->get_result();
                                $itemsToUpdate = [];
                                while ($row = $rsItems->fetch_assoc()) {
                                    $itemsToUpdate[] = $row['item_ID'];
                                    if (count($itemsToUpdate) >= $needRemove) {
                                        break;
                                    }
                                }
                                $stmtFetchItems->close();
                                if (count($itemsToUpdate) < $needRemove) {
                                    $missing = $needRemove - count($itemsToUpdate);
                                    $stmtInsertVirtual = $conn->prepare('INSERT INTO StockItem (item_ID, batch_ID, product_ID, branch_ID, purchase_order_ID, customer_order_ID, received_date, expiry_date, status) VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?)');
                                    if (!$stmtInsertVirtual) {
                                        throw new Exception('准备虚拟库存插入失败：' . $conn->error);
                                    }
                                    for ($i = 1; $i <= $missing; $i++) {
                                        $nextItemIndex++;
                                        $virtualId = generateStockItemId($batchId, $nextItemIndex);
                                        $statusValue = 'in_stock';
                                        $stmtInsertVirtual->bind_param(
                                            'ssiiisss',
                                            $virtualId,
                                            $batchId,
                                            $batchRow['product_ID'],
                                            $branchId,
                                            $batchRow['order_ID'],
                                            $batchRow['received_date'],
                                            $batchRow['date_expired'],
                                            $statusValue
                                        );
                                        if (!$stmtInsertVirtual->execute()) {
                                            throw new Exception('补全单件记录失败：' . $stmtInsertVirtual->error);
                                        }
                                        $itemsToUpdate[] = $virtualId;
                                    }
                                    $stmtInsertVirtual->close();
                                }
                                $targetStatus = $statusMap[$reason] ?? 'damaged';
                                $stmtUpdateItem = $conn->prepare('UPDATE StockItem SET status = ? WHERE item_ID = ?');
                                $stmtCert = $conn->prepare('INSERT INTO StockItemCertificate (item_ID, transaction_type, date, transaction_ID) VALUES (?, ?, ?, ?)');
                                $now = date('Y-m-d H:i:s');
                                foreach ($itemsToUpdate as $itemId) {
                                    $stmtUpdateItem->bind_param('ss', $targetStatus, $itemId);
                                    if (!$stmtUpdateItem->execute()) {
                                        throw new Exception('更新单件状态失败：' . $stmtUpdateItem->error);
                                    }
                                    $transactionId = null; // 调整记录为内部事务
                                    $stmtCert->bind_param('sssi', $itemId, $reason, $now, $transactionId);
                                    if (!$stmtCert->execute()) {
                                        throw new Exception('记录库存凭证失败：' . $stmtCert->error);
                                    }
                                }
                                $stmtUpdateItem->close();
                                $stmtCert->close();
                            }

                            $stmtUpdateBatch = $conn->prepare('UPDATE Inventory SET quantity_on_hand = ? WHERE batch_ID = ? AND branch_ID = ?');
                            $stmtUpdateBatch->bind_param('isi', $newQuantity, $batchId, $branchId);
                            if (!$stmtUpdateBatch->execute()) {
                                throw new Exception('更新批次库存失败：' . $stmtUpdateBatch->error);
                            }
                            $stmtUpdateBatch->close();

                            $conn->commit();
                            $_SESSION['inventory_success'] = '库存已调整。';
                            header('Location: inventory.php');
                            exit();
                        } catch (Exception $inner) {
                            $conn->rollback();
                            $error_message = $inner->getMessage();
                        }
                    }
                }
            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restock_action'])) {
                $productId = (int)($_POST['product_id'] ?? 0);
                $quantity = (int)($_POST['qty'] ?? 0);
                $supplierId = (int)($_POST['supplier_id'] ?? 0);
                $unitCost = (float)($_POST['unit_cost'] ?? 0);
                $expiryDate = trim($_POST['expiry_date'] ?? '');
                if ($expiryDate === '') {
                    $expiryDate = null;
                }

                if ($productId <= 0 || $quantity <= 0 || $supplierId <= 0 || $unitCost <= 0) {
                    $error_message = '请完整填写补货信息（商品、数量、供应商、单价）。';
                } else {
                    // 校验商品
                    $stmtProduct = $conn->prepare('SELECT sku, product_name FROM products WHERE product_ID = ? LIMIT 1');
                    if (!$stmtProduct) {
                        throw new Exception('准备商品查询失败：' . $conn->error);
                    }
                    $stmtProduct->bind_param('i', $productId);
                    $stmtProduct->execute();
                    $rsProduct = $stmtProduct->get_result();
                    $productRow = $rsProduct->fetch_assoc();
                    $stmtProduct->close();
                    if (!$productRow) {
                        $error_message = '未找到该商品，无法补货。';
                    } else {
                        // 校验供应商
                        $stmtSupplier = $conn->prepare('SELECT supplier_ID FROM Supplier WHERE supplier_ID = ? LIMIT 1');
                        if (!$stmtSupplier) {
                            throw new Exception('准备供应商查询失败：' . $conn->error);
                        }
                        $stmtSupplier->bind_param('i', $supplierId);
                        $stmtSupplier->execute();
                        $rsSupplier = $stmtSupplier->get_result();
                        $stmtSupplier->close();
                        if (!$rsSupplier->fetch_assoc()) {
                            $error_message = '供应商不存在。';
                        }
                    }
                }

                if (!$error_message) {
                    $productSku = $productRow['sku'] ?? '';
                    $conn->begin_transaction();
                    try {
                        $orderDate = date('Y-m-d');
                        $status = 'received';
                        $totalAmount = $unitCost * $quantity;
                        $stmtPO = $conn->prepare('INSERT INTO PurchaseOrder (supplier_ID, branch_ID, date, status, total_amount) VALUES (?, ?, ?, ?, ?)');
                        if (!$stmtPO) {
                            throw new Exception('创建采购单失败：' . $conn->error);
                        }
                        $stmtPO->bind_param('iissd', $supplierId, $branchId, $orderDate, $status, $totalAmount);
                        if (!$stmtPO->execute()) {
                            throw new Exception('保存采购单失败：' . $stmtPO->error);
                        }
                        $purchaseOrderId = $stmtPO->insert_id;
                        $stmtPO->close();

                        $batchId = generateBatchId($conn, $branchId, $productSku);
                        $receivedDate = date('Y-m-d');
                        $stmtInv = $conn->prepare('INSERT INTO Inventory (batch_ID, product_ID, branch_ID, quantity_received, quantity_on_hand, unit_cost, received_date, order_ID, date_expired) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                        if (!$stmtInv) {
                            throw new Exception('准备库存插入失败：' . $conn->error);
                        }
                        $stmtInv->bind_param(
                            'siiiidsis',
                            $batchId,
                            $productId,
                            $branchId,
                            $quantity,
                            $quantity,
                            $unitCost,
                            $receivedDate,
                            $purchaseOrderId,
                            $expiryDate
                        );
                        if (!$stmtInv->execute()) {
                            throw new Exception('新增库存批次失败：' . $stmtInv->error);
                        }
                        $stmtInv->close();

                        $stmtStock = $conn->prepare('INSERT INTO StockItem (item_ID, batch_ID, product_ID, branch_ID, purchase_order_ID, customer_order_ID, received_date, expiry_date, status) VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?)');
                        if (!$stmtStock) {
                            throw new Exception('准备 StockItem 插入失败：' . $conn->error);
                        }
                        $stmtCert = $conn->prepare('INSERT INTO StockItemCertificate (item_ID, transaction_type, date, transaction_ID) VALUES (?, ?, ?, ?)');
                        if (!$stmtCert) {
                            throw new Exception('准备凭证插入失败：' . $conn->error);
                        }
                        $transactionType = 'purchase';
                        $now = date('Y-m-d H:i:s');
                        for ($i = 1; $i <= $quantity; $i++) {
                            $itemId = generateStockItemId($batchId, $i);
                            $statusValue = 'in_stock';
                            $stmtStock->bind_param(
                                'ssiiisss',
                                $itemId,
                                $batchId,
                                $productId,
                                $branchId,
                                $purchaseOrderId,
                                $receivedDate,
                                $expiryDate,
                                $statusValue
                            );
                            if (!$stmtStock->execute()) {
                                throw new Exception('写入 StockItem 失败：' . $stmtStock->error);
                            }

                            $stmtCert->bind_param('sssi', $itemId, $transactionType, $now, $purchaseOrderId);
                            if (!$stmtCert->execute()) {
                                throw new Exception('写入库存凭证失败：' . $stmtCert->error);
                            }
                        }
                        $stmtStock->close();
                        $stmtCert->close();

                        $conn->commit();
                        $_SESSION['inventory_success'] = '补货成功，库存已更新。';
                        header('Location: inventory.php');
                        exit();
                    } catch (Exception $inner) {
                        $conn->rollback();
                        $error_message = $inner->getMessage();
                    }
                }
            }

            if ($stmtBranch = $conn->prepare('SELECT branch_name FROM Branch WHERE branch_ID = ? LIMIT 1')) {
                $stmtBranch->bind_param('i', $branchId);
                if ($stmtBranch->execute()) {
                    $rsBranch = $stmtBranch->get_result();
                    $branchRow = $rsBranch->fetch_assoc();
                    $currentBranchName = $branchRow['branch_name'] ?? '';
                }
                $stmtBranch->close();
            }

            $sql = "SELECT i.product_ID, i.batch_ID, i.branch_ID, i.quantity_on_hand, i.quantity_received,
                           i.received_date, i.date_expired, p.product_name, p.sku, p.unit_price,
                           b.branch_name,
                           s.supplier_ID AS supplier_id,
                           s.company_name AS supplier_name, s.contact_person AS supplier_contact,
                           s.phone AS supplier_phone, s.address AS supplier_address
                    FROM Inventory i
                    JOIN products p ON i.product_ID = p.product_ID
                    LEFT JOIN Branch b ON i.branch_ID = b.branch_ID
                    LEFT JOIN PurchaseOrder po ON i.order_ID = po.purchase_order_ID
                    LEFT JOIN Supplier s ON po.supplier_ID = s.supplier_ID
                    WHERE i.branch_ID = ?
                    ORDER BY i.product_ID, i.received_date DESC";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param('i', $branchId);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        $pid = (int)$row['product_ID'];
                        $code = $row['sku'] ?? ('P' . str_pad($pid, 4, '0', STR_PAD_LEFT));
                        $supplierInfo = buildSupplierInfo($row);

                        $inventoryBatches[] = [
                            'product_id' => $pid,
                            'product_code' => $code,
                            'product_name' => $row['product_name'] ?? ('商品' . $pid),
                            'batch_id' => $row['batch_ID'],
                            'quantity_on_hand' => (int)$row['quantity_on_hand'],
                            'quantity_received' => (int)$row['quantity_received'],
                            'received_date' => $row['received_date'],
                            'date_expired' => $row['date_expired'],
                            'unit_price' => $row['unit_price'],
                            'supplier' => $supplierInfo
                        ];

                        if (!isset($inventorySummary[$pid])) {
                            $inventorySummary[$pid] = [
                                'product_id' => $pid,
                                'product_code' => $code,
                                'product_name' => $row['product_name'] ?? ('商品' . $pid),
                                'total_stock' => 0,
                                'total_received' => 0,
                                'last_received' => $row['received_date'] ?? null,
                                'unit_price' => $row['unit_price'] ?? null,
                                'supplier' => $supplierInfo
                            ];
                        }
                        $inventorySummary[$pid]['total_stock'] += (int)$row['quantity_on_hand'];
                        $inventorySummary[$pid]['total_received'] += (int)$row['quantity_received'];
                        $currentLast = $inventorySummary[$pid]['last_received'];
                        if ($row['received_date'] && ($currentLast === null || $row['received_date'] > $currentLast)) {
                            $inventorySummary[$pid]['last_received'] = $row['received_date'];
                            $inventorySummary[$pid]['supplier'] = $supplierInfo;
                        }
                        if (!$currentBranchName && !empty($row['branch_name'])) {
                            $currentBranchName = $row['branch_name'];
                        }
                    }
                } else {
                    $error_message = '查询库存数据失败：' . $conn->error;
                }
                $stmt->close();
            } else {
                $error_message = '准备库存查询失败：' . $conn->error;
            }

            $resultSuppliers = $conn->query('SELECT supplier_ID, company_name FROM Supplier ORDER BY company_name ASC');
            if ($resultSuppliers) {
                while ($row = $resultSuppliers->fetch_assoc()) {
                    $supplierOptions[] = $row;
                }
                $resultSuppliers->free();
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
        $conn->close();
    }
}

$inventoryData = [];
foreach ($inventorySummary as $item) {
    $totalReceived = max($item['total_received'], $item['total_stock']);
    $reorderLevel = max(10, (int)ceil($totalReceived * 0.3));
    $inventoryData[] = [
        'product_id' => $item['product_id'],
        'product_code' => $item['product_code'],
        'product_name' => $item['product_name'],
        'total_stock' => $item['total_stock'],
        'reorder_level' => $reorderLevel,
        'last_received' => $item['last_received'],
        'unit_price' => $item['unit_price'],
        'supplier' => $item['supplier']
    ];
}

$inventoryJson = json_encode($inventoryData, JSON_UNESCAPED_UNICODE);
$batchJson = json_encode($inventoryBatches, JSON_UNESCAPED_UNICODE);
if ($inventoryJson === false) {
    $inventoryJson = '[]';
} else {
    $inventoryJson = str_replace('</', '<\/', $inventoryJson);
}
$supplierOptionsJson = json_encode($supplierOptions, JSON_UNESCAPED_UNICODE);
if ($batchJson === false) {
    $batchJson = '[]';
} else {
    $batchJson = str_replace('</', '<\/', $batchJson);
}
$supplierOptionsJson = $supplierOptionsJson === false ? '[]' : str_replace('</', '<\/', $supplierOptionsJson);

$jsBranchName = json_encode($currentBranchName ?? '');
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
.alert-box {
    background:#fff3e0;
    border:1px solid #ffc107;
    color:#b26a00;
    padding:12px 16px;
    border-radius:8px;
    margin-bottom:20px;
}
</style>
<body>
    <main class="main">
        <section class="section">
            <h2 class="section-title">库存预警与补货</h2>
            <?php if ($error_message): ?>
                <div class="alert-box" id="errorNotice"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="alert-box" id="successNotice" style="background:#e8f5e9;border-color:#66bb6a;color:#2e7d32;"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <div id="restockList"></div>
            <div id="supplierPopup" class="supplier-popup"></div>
            <div id="popupMask" onclick="closeSupplierPopup()"></div>
            <h2 class="section-title" style="margin-top:40px;">按批次的库存信息</h2>
            <div style="overflow-x:auto;">
                <table class="inventory-table" id="inventoryTable">
                    <thead>
                        <tr>
                            <th>商品编号</th>
                            <th>批次编号</th>
                            <th>商品名称</th>
                            <th>当前库存</th>
                            <th>补货阈值</th>
                            <th>最近入库</th>
                            <th>到期日期</th>
                            <th>供应商</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </section>
    </main>
    <?php include 'footer.php'; ?>
    <script>
    const inventorySummary = <?php echo $inventoryJson ?: '[]'; ?>;
    const inventoryBatches = <?php echo $batchJson ?: '[]'; ?>;
    const supplierOptions = <?php echo $supplierOptionsJson ?: '[]'; ?>;
    const branchName = <?php echo $jsBranchName; ?> || '';

    function getRestockList() {
        const low = [], warn = [];
        inventorySummary.forEach(item => {
            const threshold = item.reorder_level || 10;
            const stock = item.total_stock || 0;
            if (stock < threshold) {
                low.push(item);
            } else if (stock < threshold + Math.max(2, Math.ceil(threshold * 0.15))) {
                warn.push(item);
            }
        });
        return { low, warn };
    }

    function renderRestockList() {
        const { low, warn } = getRestockList();
        const wrap = document.getElementById('restockList');
        wrap.innerHTML = '';
        if (!inventorySummary.length) {
            wrap.innerHTML = '<div style="color:#888;padding:32px;text-align:center;">当前门店暂无库存数据</div>';
            return;
        }
        if (!low.length && !warn.length) {
            wrap.innerHTML = '<div style="color:#43a047;padding:32px;text-align:center;">库存健康，暂无补货需求</div>';
            return;
        }
        low.forEach(item => wrap.appendChild(createRestockCard(item, 'low')));
        warn.forEach(item => wrap.appendChild(createRestockCard(item, 'warn')));
    }

    function createRestockCard(item, type) {
        const card = document.createElement('div');
        card.className = 'restock-card ' + type;
        card.innerHTML = `
            <div style="font-size:16px;font-weight:600;">${item.product_name} <span style="color:#888;font-size:13px;">(${item.product_code})</span></div>
            <div style="font-size:14px;color:${type === 'low' ? '#e53935' : '#ffb300'};">当前库存：${item.total_stock}</div>
            <div style="font-size:14px;color:#888;">补货阈值：${item.reorder_level}</div>
            <button class="btn btn-primary restock-btn" onclick='openSupplierPopup(${JSON.stringify(item)})'>补货</button>
        `;
        return card;
    }

    function openSupplierPopup(item) {
        const s = item.supplier || {};
        const popup = document.getElementById('supplierPopup');
        popup.innerHTML = `
            <h3>供货商信息</h3>
            <div class='info'><b>商品：</b>${item.product_name} (${item.product_code})</div>
            <div class='info'><b>当前库存：</b>${item.total_stock}，<b>补货阈值：</b>${item.reorder_level}</div>
            <div class='info'><b>供应商：</b>${s.name || '未知'}</div>
            <div class='info'><b>联系人：</b>${s.contact || '未登记'}</div>
            <div class='info'><b>电话：</b>${s.phone || '未提供'}</div>
            <div class='info'><b>地址：</b>${s.address || '未提供'}</div>
            <div class='actions'>
                <button class='btn btn-success' onclick='openRestockOrderForm(${JSON.stringify(item)})'>确认补货</button>
                <button class='btn btn-warning' style='margin-left:10px;' onclick='closeSupplierPopup()'>取消</button>
            </div>
        `;
        popup.style.display = 'block';
        document.getElementById('popupMask').style.display = 'block';
    }

    function buildSupplierOptions(selectedId) {
        if (!supplierOptions.length) {
            return '<option value=\"\">暂无供应商</option>';
        }
        return supplierOptions.map(opt => {
            const sel = Number(selectedId) === Number(opt.supplier_ID) ? 'selected' : '';
            return `<option value=\"${opt.supplier_ID}\" ${sel}>${opt.company_name}</option>`;
        }).join('');
    }

    function openRestockOrderForm(item) {
        const price = Number(item.unit_price) > 0 ? Number(item.unit_price) : 10.0;
        const popup = document.getElementById('supplierPopup');
        const supplierId = item.supplier?.id || '';
        popup.innerHTML = `
            <h3>补货下单</h3>
            <form id='restockForm' method='post'>
                <input type='hidden' name='restock_action' value='1'>
                <input type='hidden' name='product_id' value='${item.product_id}'>
                <div class='info'><b>商品：</b>${item.product_name} (${item.product_code})</div>
                <div class='info'><b>门店：</b><input type='text' value='${branchName}' readonly style='width:60%;margin-left:8px;background:#f5f5f5;'></div>
                <div class='info'><b>申请人：</b><input type='text' name='staff' placeholder='申请人姓名' style='width:60%;margin-left:8px;'></div>
                <div class='info'><b>供应商：</b>
                    <select name='supplier_id' required style='margin-left:8px;flex:1;'>
                        ${buildSupplierOptions(supplierId)}
                    </select>
                </div>
                <div class='info'><b>补货数量：</b><input type='number' name='qty' id='restockQty' min='1' value='10' required style='width:80px;margin-left:8px;' oninput='updateRestockTotal(${price})'></div>
                <div class='info'><b>采购单价：</b>¥${price.toFixed(2)}<input type='hidden' name='unit_cost' value='${price.toFixed(2)}'></div>
                <div class='info'><b>预计到期（选填）：</b><input type='date' name='expiry_date' style='margin-left:8px;'></div>
                <div class='info'><b>总金额：</b>¥<span id='restockTotal'>${(price*10).toFixed(2)}</span></div>
                <div class='actions' style='margin-top:18px;'>
                    <button class='btn btn-success' type='submit'>提交订单</button>
                    <button class='btn btn-warning' type='button' style='margin-left:10px;' onclick='closeSupplierPopup()'>取消</button>
                </div>
            </form>
        `;
    }

    function updateRestockTotal(price) {
        const qtyInput = document.getElementById('restockQty');
        const qty = qtyInput ? parseInt(qtyInput.value, 10) || 0 : 0;
        const target = document.getElementById('restockTotal');
        if (target) {
            target.textContent = (price * qty).toFixed(2);
        }
    }

    function openAdjustForm(item) {
        const popup = document.getElementById('supplierPopup');
        popup.innerHTML = `
            <h3>库存调整</h3>
            <form id='adjustForm' method='post'>
                <input type='hidden' name='adjust_action' value='1'>
                <input type='hidden' name='batch_id' value='${item.batch_id}'>
                <div class='info'><b>商品：</b>${item.product_name} (${item.product_code})</div>
                <div class='info'><b>当前库存：</b>${item.quantity_on_hand}</div>
                <div class='info'><b>调整后库存：</b><input type='number' name='new_quantity' min='0' value='${item.quantity_on_hand}' required style='width:100px;margin-left:8px;'></div>
                <div class='info'><b>调整类型：</b>
                    <select name='adjust_reason' required style='margin-left:8px;'>
                        <option value='return'>return（退回）</option>
                        <option value='transfer'>transfer（调拨）</option>
                        <option value='adjustment'>adjustment（盘点）</option>
                    </select>
                </div>
                <div class='actions' style='margin-top:18px;'>
                    <button class='btn btn-success' type='submit'>提交</button>
                    <button class='btn btn-warning' type='button' style='margin-left:10px;' onclick='closeSupplierPopup()'>取消</button>
                </div>
            </form>
        `;
        popup.style.display = 'block';
        document.getElementById('popupMask').style.display = 'block';
    }

    function closeSupplierPopup() {
        document.getElementById('supplierPopup').style.display = 'none';
        document.getElementById('popupMask').style.display = 'none';
    }

    function renderInventoryTable() {
        const tbody = document.querySelector('#inventoryTable tbody');
        tbody.innerHTML = '';
        const reorderMap = {};
        inventorySummary.forEach(item => {
            reorderMap[item.product_id] = item.reorder_level;
        });
        if (!inventoryBatches.length) {
            const row = document.createElement('tr');
            row.innerHTML = `<td colspan="9" style="text-align:center;color:#888;padding:24px;">暂无库存数据</td>`;
            tbody.appendChild(row);
            return;
        }
        inventoryBatches.forEach(item => {
            const row = document.createElement('tr');
            const threshold = reorderMap[item.product_id] ?? '--';
            row.innerHTML = `
                <td>${item.product_code}</td>
                <td>${item.batch_id || '--'}</td>
                <td>${item.product_name}</td>
                <td>${item.quantity_on_hand}</td>
                <td>${threshold}</td>
                <td>${item.received_date ? item.received_date.split(' ')[0] : '--'}</td>
                <td>${item.date_expired || '--'}</td>
                <td>${item.supplier?.name || '未知供应商'}</td>
                <td><button class="btn btn-primary" style="padding:6px 12px;" onclick='openAdjustForm(${JSON.stringify(item)})'>编辑</button></td>
            `;
            tbody.appendChild(row);
        });
    }

    renderRestockList();
    renderInventoryTable();

    ['successNotice','errorNotice'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            setTimeout(() => {
                el.style.display = 'none';
            }, 3000);
        }
    });
    </script>
</body>
</html>
