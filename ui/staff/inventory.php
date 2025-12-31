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
$flash_error_message = $_SESSION['inventory_error'] ?? '';
unset($_SESSION['inventory_success'], $_SESSION['inventory_error']);
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

function resolveRestockCategoryId(int $parentCategoryId, int $categoryId): int {
    $validParents = [2, 3, 4];
    if (in_array($parentCategoryId, $validParents, true)) {
        return $parentCategoryId;
    }
    if (in_array($categoryId, $validParents, true)) {
        return $categoryId;
    }
    return 0;
}

function mapSupplierCategoryByParent(int $parentCategoryId): string {
    switch ($parentCategoryId) {
        case 2:
            return '果蔬';
        case 3:
            return '肉禽蛋';
        case 4:
            return '水产';
        default:
            return '';
    }
}

function getLastBatchAdjustmentActor(mysqli $conn, string $batchId): ?array {
    $sql = "SELECT 
                c.transaction_ID AS staff_id,
                COALESCE(NULLIF(TRIM(CONCAT_WS(' ', u.first_name, u.last_name)), ''), u.user_name, CONCAT('员工#', c.transaction_ID)) AS staff_name,
                c.date AS action_time
            FROM StockItemCertificate c
            JOIN StockItem si ON si.item_ID = c.item_ID
            LEFT JOIN Staff s ON s.staff_ID = c.transaction_ID
            LEFT JOIN User u ON u.user_name = s.user_name
            WHERE si.batch_ID = ?
              AND c.transaction_type IN ('return','transfer','adjustment')
            ORDER BY c.date DESC
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('s', $batchId);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            if ($row) {
                return $row;
            }
        } else {
            $stmt->close();
        }
    }
    return null;
}

function getLastRestockActor(mysqli $conn, int $productId, int $branchId): ?array {
    $sql = "SELECT 
                c.transaction_ID AS purchase_order_id,
                c.date AS action_time,
                po.date AS order_date,
                s.company_name AS supplier_name
            FROM StockItemCertificate c
            JOIN StockItem si ON si.item_ID = c.item_ID
            LEFT JOIN PurchaseOrder po ON po.purchase_order_ID = c.transaction_ID
            LEFT JOIN Supplier s ON s.supplier_ID = po.supplier_ID
            WHERE si.product_ID = ?
              AND si.branch_ID = ?
              AND c.transaction_type = 'purchase'
              AND c.transaction_ID IS NOT NULL
            ORDER BY c.date DESC
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ii', $productId, $branchId);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            if ($row) {
                $labelParts = [];
                if (!empty($row['purchase_order_id'])) {
                    $labelParts[] = '采购单#' . $row['purchase_order_id'];
                }
                if (!empty($row['supplier_name'])) {
                    $labelParts[] = $row['supplier_name'];
                }
                $row['staff_name'] = implode(' - ', $labelParts);
                return $row;
            }
        } else {
            $stmt->close();
        }
    }
    return null;
}

function resolveActorContext(?array $actorInfo, string $defaultName = '其他员工'): array {
    $actorName = trim($actorInfo['staff_name'] ?? '');
    if ($actorName === '') {
        $actorName = $defaultName;
    }
    $actionTime = '';
    if (!empty($actorInfo['action_time'])) {
        $timestamp = strtotime($actorInfo['action_time']);
        if ($timestamp !== false) {
            $actionTime = ' 于 ' . date('m-d H:i', $timestamp);
        }
    }
    return [$actorName, $actionTime];
}

function generateBatchId(mysqli $conn, int $branchId, string $sku, bool $lockForUpdate = false): string {
    $prefix = 'SKU';
    if (strpos($sku, '-') !== false) {
        $prefix = strtoupper(substr($sku, 0, strpos($sku, '-')));
    } else {
        $prefix = strtoupper(substr($sku, 0, 3));
    }
    $likePattern = sprintf('B%d-%s-%%', $branchId, $prefix);
    $sql = 'SELECT batch_ID FROM Inventory WHERE branch_ID = ? AND batch_ID LIKE ? ORDER BY batch_ID DESC LIMIT 1';
    if ($lockForUpdate) {
        $sql .= ' FOR UPDATE';
    }
    $stmt = $conn->prepare($sql);
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

function getNextStockItemIndex(mysqli $conn, string $batchId, bool $lockForUpdate = false): int {
    $sql = 'SELECT item_ID FROM StockItem WHERE batch_ID = ? ORDER BY item_ID DESC LIMIT 1';
    if ($lockForUpdate) {
        $sql .= ' FOR UPDATE';
    }
    $stmt = $conn->prepare($sql);
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

function summarizeProductInventory(mysqli $conn, int $productId, int $branchId, bool $lockRows = false): array {
    $sql = 'SELECT batch_ID, quantity_on_hand, received_date FROM Inventory WHERE product_ID = ? AND branch_ID = ?';
    if ($lockRows) {
        $sql .= ' FOR UPDATE';
    }
    $stmt = $conn->prepare($sql);
    $total = 0;
    $lastReceived = null;
    if ($stmt) {
        $stmt->bind_param('ii', $productId, $branchId);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $total += (int)$row['quantity_on_hand'];
                $dateVal = $row['received_date'] ?? null;
                if ($dateVal !== null) {
                    if ($lastReceived === null || $dateVal > $lastReceived) {
                        $lastReceived = $dateVal;
                    }
                }
            }
        }
        $stmt->close();
    }
    return [
        'total_stock' => $total,
        'last_received' => $lastReceived
    ];
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
                $originalQuantity = isset($_POST['original_quantity']) ? (int)$_POST['original_quantity'] : null;
                $reason = $_POST['adjust_reason'] ?? '';
                $allowedReasons = ['return', 'transfer', 'adjustment'];

                if ($batchId === '' || $newQuantity < 0 || !in_array($reason, $allowedReasons, true)) {
                    $error_message = '请填写正确的调整信息。';
                } else {
                    try {
                        $staffId = $_SESSION['staff_id'] ?? null;
                        if (!$staffId) {
                            throw new Exception('无法识别当前员工，请重新登录。');
                        }

                        $expectedQuantity = $originalQuantity;
                        $stmtAdjust = $conn->prepare('CALL staff_adjust_inventory(?, ?, ?, ?, ?, ?)');
                        if (!$stmtAdjust) {
                            throw new Exception('准备库存调整存储过程失败：' . $conn->error);
                        }
                        $stmtAdjust->bind_param('siisii', $batchId, $branchId, $newQuantity, $reason, $staffId, $expectedQuantity);
                        if (!$stmtAdjust->execute()) {
                            throw new Exception('执行库存调整存储过程失败：' . $stmtAdjust->error);
                        }
                        $stmtAdjust->close();
                        while ($conn->more_results() && $conn->next_result()) {
                            $extraResult = $conn->store_result();
                            if ($extraResult) {
                                $extraResult->free();
                            }
                        }

                        $_SESSION['inventory_success'] = '库存已调整。';
                        header('Location: inventory.php');
                        exit();
                    } catch (Exception $inner) {
                        $message = $inner->getMessage();
                        if (strpos($message, 'Quantity mismatch') !== false || strpos($message, 'Quantity unchanged') !== false) {
                            $actorInfo = getLastBatchAdjustmentActor($conn, $batchId);
                            [$actorName, $actionTime] = resolveActorContext($actorInfo);
                            if (strpos($message, 'Quantity mismatch') !== false) {
                                $reasonText = sprintf(
                                    '库存调整失败：%s%s已将该批次更新，请刷新后重试。',
                                    $actorName,
                                    $actionTime
                                );
                            } else {
                                $reasonText = sprintf('库存未发生变化：%s%s已将该批次更新，请刷新后重试。', $actorName, $actionTime);
                            }
                            $_SESSION['inventory_error'] = $reasonText;
                            header('Location: inventory.php');
                            exit();
                        }
                        $error_message = $message;
                    }
                }
            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restock_action'])) {
                $productId = (int)($_POST['product_id'] ?? 0);
                $quantity = (int)($_POST['qty'] ?? 0);
                $supplierId = (int)($_POST['supplier_id'] ?? 0);
                $unitCost = (float)($_POST['unit_cost'] ?? 0);
                $snapshotStock = isset($_POST['snapshot_total_stock']) && $_POST['snapshot_total_stock'] !== '' ? (int)$_POST['snapshot_total_stock'] : null;
                $snapshotLastReceived = trim($_POST['snapshot_last_received'] ?? '');
                $expiryDate = trim($_POST['expiry_date'] ?? '');
                if ($expiryDate === '') {
                    $expiryDate = null;
                }
                if ($productId <= 0 || $quantity <= 0 || $supplierId <= 0 || $unitCost <= 0) {
                    $error_message = '请完整填写补货信息（商品、数量、供应商、单价）。';
                }

                if (!$error_message) {
                    try {
                        $stmtProduct = $conn->prepare('SELECT p.sku, p.product_name, p.category_id, c.parent_category_id FROM products p LEFT JOIN Categories c ON p.category_id = c.category_id WHERE p.product_ID = ? LIMIT 1 FOR UPDATE');
                        if (!$stmtProduct) {
                            throw new Exception('准备商品查询失败：' . $conn->error);
                        }
                        $stmtProduct->bind_param('i', $productId);
                        if (!$stmtProduct->execute()) {
                            throw new Exception('查询商品失败：' . $stmtProduct->error);
                        }
                        $rsProduct = $stmtProduct->get_result();
                        $productRow = $rsProduct->fetch_assoc();
                        $stmtProduct->close();
                        if (!$productRow) {
                            throw new Exception('未找到该商品，无法补货。');
                        }

                        $parentCategoryId = resolveRestockCategoryId(
                            (int)($productRow['parent_category_id'] ?? 0),
                            (int)($productRow['category_id'] ?? 0)
                        );
                        $supplierCategoryExpected = mapSupplierCategoryByParent($parentCategoryId);
                        if ($supplierCategoryExpected === '') {
                            throw new Exception('该商品无法匹配供应商类别，请检查商品分类设置。');
                        }

                        $stmtSupplier = $conn->prepare('SELECT supplier_ID, supplier_category FROM Supplier WHERE supplier_ID = ? LIMIT 1 FOR UPDATE');
                        if (!$stmtSupplier) {
                            throw new Exception('准备供应商查询失败：' . $conn->error);
                        }
                        $stmtSupplier->bind_param('i', $supplierId);
                        if (!$stmtSupplier->execute()) {
                            throw new Exception('查询供应商失败：' . $stmtSupplier->error);
                        }
                        $rsSupplier = $stmtSupplier->get_result();
                        $supplierRow = $rsSupplier->fetch_assoc();
                        $stmtSupplier->close();
                        if (!$supplierRow) {
                            throw new Exception('供应商不存在。');
                        }
                        $supplierCategory = trim((string)($supplierRow['supplier_category'] ?? ''));
                        if ($supplierCategoryExpected !== $supplierCategory) {
                            throw new Exception('供应商类别与商品父类不匹配，请重新选择供应商。');
                        }

                        $inventoryState = summarizeProductInventory($conn, $productId, $branchId, true);
                        $currentTotalStock = (int)$inventoryState['total_stock'];
                        $currentLastReceived = $inventoryState['last_received'] ?? null;
                        if ($currentLastReceived !== null) {
                            $timestamp = strtotime($currentLastReceived);
                            if ($timestamp !== false) {
                                $currentLastReceived = date('Y-m-d', $timestamp);
                            }
                        }
                        $snapshotLast = $snapshotLastReceived === '' ? null : $snapshotLastReceived;
                        if (($snapshotStock !== null && $snapshotStock !== $currentTotalStock) ||
                            ($snapshotLast !== null && $currentLastReceived !== null && $snapshotLast !== $currentLastReceived)) {
                            $actorInfo = getLastRestockActor($conn, $productId, $branchId);
                            [$actorName, $actionTime] = resolveActorContext($actorInfo, '其他员工');
                            $reasonText = sprintf('补货失败：%s%s刚刚完成了该商品的入库，请刷新库存后再试。', $actorName, $actionTime);
                            $_SESSION['inventory_error'] = $reasonText;
                            header('Location: inventory.php');
                            exit();
                        }

                        $stmtRestock = $conn->prepare('CALL staff_restock(?, ?, ?, ?, ?, ?, @po_id, @batch_id)');
                        if (!$stmtRestock) {
                            throw new Exception('准备补货存储过程失败：' . $conn->error);
                        }
                        $expiryDateParam = $expiryDate;
                        $stmtRestock->bind_param('iiiids', $productId, $branchId, $supplierId, $quantity, $unitCost, $expiryDateParam);
                        if (!$stmtRestock->execute()) {
                            throw new Exception('执行补货存储过程失败：' . $stmtRestock->error);
                        }
                        $stmtRestock->close();
                        while ($conn->more_results() && $conn->next_result()) {
                            $extraResult = $conn->store_result();
                            if ($extraResult) {
                                $extraResult->free();
                            }
                        }

                        $resultOut = $conn->query('SELECT @po_id AS purchase_order_id, @batch_id AS batch_id');
                        if ($resultOut) {
                            $resultOut->free();
                        }

                        $_SESSION['inventory_success'] = '补货成功，库存已更新。';
                        header('Location: inventory.php');
                        exit();
                    } catch (Exception $inner) {
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

            $sql = "SELECT product_ID, batch_ID, branch_ID, quantity_on_hand, quantity_received,
                           received_date, date_expired, product_name, sku, unit_price,
                           category_id, parent_category_id,
                           branch_name,
                           supplier_id,
                           supplier_name, supplier_contact,
                           supplier_phone, supplier_address
                    FROM v_staff_inventory_batches
                    WHERE branch_ID = ?
                    ORDER BY product_ID, received_date DESC";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param('i', $branchId);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        $pid = (int)$row['product_ID'];
                        $code = $row['sku'] ?? ('P' . str_pad($pid, 4, '0', STR_PAD_LEFT));
                        $supplierInfo = buildSupplierInfo($row);
                        $restockCategoryId = resolveRestockCategoryId(
                            (int)($row['parent_category_id'] ?? 0),
                            (int)($row['category_id'] ?? 0)
                        );

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
                            'restock_category_id' => $restockCategoryId,
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
                                'restock_category_id' => $restockCategoryId,
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

            $resultSuppliers = $conn->query('SELECT supplier_ID, company_name, supplier_category FROM Supplier ORDER BY company_name ASC');
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
        'restock_category_id' => $item['restock_category_id'],
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
$errorNotices = [];
if ($flash_error_message !== '') {
    $errorNotices[] = $flash_error_message;
}
if ($error_message !== '') {
    $errorNotices[] = $error_message;
}
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
.alert-box.alert-error {
    background:#ffebee;
    border-color:#e53935;
    color:#c62828;
}
.batch-list {
    max-height: 360px;
    overflow-y: auto;
    margin-top: 10px;
}
.batch-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
    gap: 12px;
}
.batch-row:last-child {
    border-bottom: none;
}
.batch-row .info-block {
    font-size: 14px;
    color: #555;
}
</style>
<body>
    <main class="main">
        <section class="section">
            <h2 class="section-title">库存预警与补货</h2>
            <?php if (!empty($errorNotices)): ?>
                <div class="alert-box alert-error" id="errorNotice">
                    <?php foreach ($errorNotices as $idx => $msg): ?>
                        <?php if ($idx > 0): ?><br><?php endif; ?>
                        <?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="alert-box" id="successNotice" style="background:#e8f5e9;border-color:#66bb6a;color:#2e7d32;"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <div id="restockList"></div>
            <div id="supplierPopup" class="supplier-popup"></div>
            <div id="popupMask" onclick="closeSupplierPopup()"></div>
            <h2 class="section-title" style="margin-top:40px;">商品库存概览</h2>
            <div style="overflow-x:auto;">
                <table class="inventory-table" id="inventoryTable">
                    <thead>
                        <tr>
                            <th>商品编号</th>
                            <th>商品名称</th>
                            <th>当前库存</th>
                            <th>补货阈值</th>
                            <th>最近入库</th>
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
    const supplierCategoryMap = {
        2: '果蔬',
        3: '肉禽蛋',
        4: '水产'
    };

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
        openRestockOrderForm(item);
    }

    function buildSupplierOptions(item, selectedId) {
        const categoryLabel = supplierCategoryMap[Number(item.restock_category_id)] || '';
        const filtered = categoryLabel
            ? supplierOptions.filter(opt => opt.supplier_category === categoryLabel)
            : [];
        if (!filtered.length) {
            return '<option value=\"\">暂无可用供应商</option>';
        }
        return filtered.map(opt => {
            const sel = Number(selectedId) === Number(opt.supplier_ID) ? 'selected' : '';
            return `<option value=\"${opt.supplier_ID}\" ${sel}>${opt.company_name}</option>`;
        }).join('');
    }

    function openRestockOrderForm(item) {
        const price = Number(item.unit_price) > 0 ? Number(item.unit_price) : 10.0;
        const popup = document.getElementById('supplierPopup');
        const supplierId = item.supplier?.id || '';
        const parsedStock = Number(item.total_stock);
        const snapshotStock = Number.isFinite(parsedStock) ? parsedStock : '';
        const snapshotLastReceived = item.last_received || '';
        popup.innerHTML = `
            <h3>补货下单</h3>
            <form id='restockForm' method='post'>
                <input type='hidden' name='restock_action' value='1'>
                <input type='hidden' name='product_id' value='${item.product_id}'>
                <input type='hidden' name='snapshot_total_stock' value='${snapshotStock}'>
                <input type='hidden' name='snapshot_last_received' value='${snapshotLastReceived}'>
                <div class='info'><b>商品：</b>${item.product_name} (${item.product_code})</div>
                <div class='info'><b>门店：</b><input type='text' value='${branchName}' readonly style='width:60%;margin-left:8px;background:#f5f5f5;'></div>
                <div class='info'><b>供应商：</b>
                    <select name='supplier_id' required style='margin-left:8px;flex:1;'>
                        ${buildSupplierOptions(item, supplierId)}
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
        popup.style.display = 'block';
        document.getElementById('popupMask').style.display = 'block';
    }

    function updateRestockTotal(price) {
        const qtyInput = document.getElementById('restockQty');
        const qty = qtyInput ? parseInt(qtyInput.value, 10) || 0 : 0;
        const target = document.getElementById('restockTotal');
        if (target) {
            target.textContent = (price * qty).toFixed(2);
        }
    }

    function openBatchManager(productId) {
        const popup = document.getElementById('supplierPopup');
        const product = inventorySummary.find(p => Number(p.product_id) === Number(productId));
        const batches = inventoryBatches
            .filter(b => Number(b.product_id) === Number(productId))
            .sort((a, b) => {
                const left = (a.batch_id || '').toUpperCase();
                const right = (b.batch_id || '').toUpperCase();
                if (left < right) return -1;
                if (left > right) return 1;
                return 0;
            });
        let html = `<h3>批次管理 - ${product ? `${product.product_name} (${product.product_code})` : ''}</h3>`;
        if (!batches.length) {
            html += `<div style="padding:16px 0;color:#888;">该商品暂无批次可调整。</div>`;
        } else {
            html += '<div class="batch-list">';
            batches.forEach(batch => {
                const safeBatch = JSON.stringify(batch).replace(/"/g, '&quot;');
                const receivedDate = batch.received_date ? batch.received_date.split(' ')[0] : '--';
                const expireDate = batch.date_expired || '--';
                html += `
                    <div class="batch-row">
                        <div class="info-block">
                            <div><b>批次：</b>${batch.batch_id || '--'}</div>
                            <div><b>库存：</b>${batch.quantity_on_hand}，<b>入库：</b>${receivedDate}，<b>到期：</b>${expireDate}</div>
                        </div>
                        <button class="btn btn-primary" style="padding:6px 12px;" onclick="openAdjustForm(${safeBatch})">调整</button>
                    </div>
                `;
            });
            html += '</div>';
        }
        html += `<div class='actions' style='margin-top:18px;text-align:right;'><button class='btn btn-warning' onclick='closeSupplierPopup()'>关闭</button></div>`;
        popup.innerHTML = html;
        popup.style.display = 'block';
        document.getElementById('popupMask').style.display = 'block';
    }

    function openAdjustForm(item) {
        const popup = document.getElementById('supplierPopup');
        popup.innerHTML = `
            <h3>库存调整</h3>
            <form id='adjustForm' method='post'>
                <input type='hidden' name='adjust_action' value='1'>
                <input type='hidden' name='batch_id' value='${item.batch_id}'>
                <input type='hidden' name='original_quantity' value='${item.quantity_on_hand}'>
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
        if (!inventorySummary.length) {
            const row = document.createElement('tr');
            row.innerHTML = `<td colspan="7" style="text-align:center;color:#888;padding:24px;">暂无库存数据</td>`;
            tbody.appendChild(row);
            return;
        }
        inventorySummary.forEach(item => {
            const supplierName = item.supplier?.name || '未知供应商';
            const lastReceived = item.last_received ? item.last_received.split(' ')[0] : '--';
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.product_code}</td>
                <td>${item.product_name}</td>
                <td>${item.total_stock}</td>
                <td>${item.reorder_level}</td>
                <td>${lastReceived}</td>
                <td>${supplierName}</td>
                <td><button class="btn btn-primary" style="padding:6px 12px;" onclick="openBatchManager(${item.product_id})">批次管理</button></td>
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
            }, 8000);
        }
    });
    </script>
</body>
</html>
