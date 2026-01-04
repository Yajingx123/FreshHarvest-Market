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
$username = "staff_user";
$password = "YourPassword123!";
$dbname = "mydb";

$error_message = '';
$success_message = $_SESSION['inventory_success'] ?? '';
$flash_error_message = $_SESSION['inventory_error'] ?? '';
unset($_SESSION['inventory_success'], $_SESSION['inventory_error']);
$inventorySummary = [];
$inventoryBatches = [];
$supplierOptions = [];
$supplierPricing = [];
$unstockedProducts = [];
$branchId = $_SESSION['staff_branch_id'] ?? null;
$currentBranchName = '';

function buildSupplierInfo(array $row): array {
    return [
        'id' => $row['supplier_id'] ?? null,
        'name' => $row['supplier_name'] ?? 'Unknown Supplier',
        'contact' => $row['supplier_contact'] ?? 'Not set',
        'phone' => $row['supplier_phone'] ?? 'Not provided',
        'address' => $row['supplier_address'] ?? 'Not provided'
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
                staff_id,
                staff_name,
                action_time
            FROM v_staff_stockitem_adjustment_actor
            WHERE batch_id = ?
              AND transaction_type IN ('return','transfer','adjustment')
            ORDER BY action_time DESC
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
                purchase_order_id,
                action_time,
                order_date,
                supplier_name
            FROM v_staff_restock_actor
            WHERE product_ID = ?
              AND branch_ID = ?
              AND transaction_type = 'purchase'
              AND purchase_order_id IS NOT NULL
            ORDER BY action_time DESC
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
                    $labelParts[] = 'PO#' . $row['purchase_order_id'];
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

function resolveActorContext(?array $actorInfo, string $defaultName = 'Other staff'): array {
    $actorName = trim($actorInfo['staff_name'] ?? '');
    if ($actorName === '') {
        $actorName = $defaultName;
    }
    $actionTime = '';
    if (!empty($actorInfo['action_time'])) {
        $timestamp = strtotime($actorInfo['action_time']);
        if ($timestamp !== false) {
            $actionTime = ' at ' . date('m-d H:i', $timestamp);
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
    $error_message = 'Unable to determine the current branch. Please sign in again.';
} else {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        $error_message = 'Database connection failed: ' . $conn->connect_error;
    } else {
        try {
            $conn->set_charset('utf8mb4');

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_action'])) {
                $batchId = trim($_POST['batch_id'] ?? '');
                $newQuantity = isset($_POST['new_quantity']) ? (int)$_POST['new_quantity'] : -1;
                $originalQuantity = isset($_POST['original_quantity']) ? (int)$_POST['original_quantity'] : null;
                $reason = $_POST['adjust_reason'] ?? '';
                $note = trim($_POST['adjust_note'] ?? '');
                $allowedReasons = ['return', 'transfer', 'adjustment'];

                if ($batchId === '' || $newQuantity < 0 || !in_array($reason, $allowedReasons, true)) {
                    $error_message = 'Please provide a valid adjustment.';
                } elseif ($reason === 'return') {
                    if ($originalQuantity !== null && $newQuantity > $originalQuantity) {
                        $error_message = 'Returns can only reduce stock.';
                    } elseif ($note === '') {
                        $error_message = 'Please provide a return reason.';
                    }
                } else {
                    try {
                        $staffId = $_SESSION['staff_id'] ?? null;
                        if (!$staffId) {
                            throw new Exception('Unable to identify the current staff member. Please sign in again.');
                        }

                        $expectedQuantity = $originalQuantity;
                        $stmtAdjust = $conn->prepare('CALL staff_adjust_inventory(?, ?, ?, ?, ?, ?, ?)');
                        if (!$stmtAdjust) {
                            throw new Exception('Failed to prepare inventory adjustment procedure: ' . $conn->error);
                        }
                        $stmtAdjust->bind_param('siisiis', $batchId, $branchId, $newQuantity, $reason, $staffId, $expectedQuantity, $note);
                        if (!$stmtAdjust->execute()) {
                            throw new Exception('Failed to execute inventory adjustment procedure: ' . $stmtAdjust->error);
                        }
                        $stmtAdjust->close();
                        while ($conn->more_results() && $conn->next_result()) {
                            $extraResult = $conn->store_result();
                            if ($extraResult) {
                                $extraResult->free();
                            }
                        }

                        $_SESSION['inventory_success'] = 'Inventory adjusted successfully.';
                        header('Location: inventory.php');
                        exit();
                    } catch (Exception $inner) {
                        $message = $inner->getMessage();
                        if (strpos($message, 'Quantity mismatch') !== false || strpos($message, 'Quantity unchanged') !== false) {
                            $actorInfo = getLastBatchAdjustmentActor($conn, $batchId);
                            [$actorName, $actionTime] = resolveActorContext($actorInfo);
                            if (strpos($message, 'Quantity mismatch') !== false) {
                                $reasonText = sprintf(
                                    'Inventory adjustment failed: %s%s updated this batch. Refresh and try again.',
                                    $actorName,
                                    $actionTime
                                );
                            } else {
                                $reasonText = sprintf('No inventory changes: %s%s updated this batch. Refresh and try again.', $actorName, $actionTime);
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
                    $error_message = 'Please complete all restock details (product, quantity, supplier, unit cost).';
                }

                if (!$error_message) {
                    try {
                        $stmtProductLock = $conn->prepare('SELECT product_ID FROM products WHERE product_ID = ? LIMIT 1 FOR UPDATE');
                        if (!$stmtProductLock) {
                            throw new Exception('Failed to prepare product lock: ' . $conn->error);
                        }
                        $stmtProductLock->bind_param('i', $productId);
                        if (!$stmtProductLock->execute()) {
                            throw new Exception('Failed to lock product: ' . $stmtProductLock->error);
                        }
                        $stmtProductLock->close();

                        $stmtProduct = $conn->prepare('SELECT sku, product_name, category_id, parent_category_id FROM v_staff_product_category WHERE product_ID = ? LIMIT 1');
                        if (!$stmtProduct) {
                            throw new Exception('Failed to prepare product query: ' . $conn->error);
                        }
                        $stmtProduct->bind_param('i', $productId);
                        if (!$stmtProduct->execute()) {
                            throw new Exception('Failed to query product: ' . $stmtProduct->error);
                        }
                        $rsProduct = $stmtProduct->get_result();
                        $productRow = $rsProduct->fetch_assoc();
                        $stmtProduct->close();
                        if (!$productRow) {
                            throw new Exception('Product not found. Unable to restock.');
                        }

                        $parentCategoryId = resolveRestockCategoryId(
                            (int)($productRow['parent_category_id'] ?? 0),
                            (int)($productRow['category_id'] ?? 0)
                        );
                        $supplierCategoryExpected = mapSupplierCategoryByParent($parentCategoryId);
                        if ($supplierCategoryExpected === '') {
                            throw new Exception('Product cannot be matched to a supplier category. Check product categorization.');
                        }

                        $stmtSupplier = $conn->prepare('SELECT supplier_ID, supplier_category FROM Supplier WHERE supplier_ID = ? LIMIT 1 FOR UPDATE');
                        if (!$stmtSupplier) {
                            throw new Exception('Failed to prepare supplier query: ' . $conn->error);
                        }
                        $stmtSupplier->bind_param('i', $supplierId);
                        if (!$stmtSupplier->execute()) {
                            throw new Exception('Failed to query supplier: ' . $stmtSupplier->error);
                        }
                        $rsSupplier = $stmtSupplier->get_result();
                        $supplierRow = $rsSupplier->fetch_assoc();
                        $stmtSupplier->close();
                        if (!$supplierRow) {
                            throw new Exception('Supplier not found.');
                        }
                        $supplierCategory = trim((string)($supplierRow['supplier_category'] ?? ''));
                        if ($supplierCategoryExpected !== $supplierCategory) {
                            throw new Exception('Supplier category does not match the product category. Choose another supplier.');
                        }

                        $snapshotLast = $snapshotLastReceived === '' ? null : $snapshotLastReceived;
                        $stmtRestock = $conn->prepare('CALL staff_restock(?, ?, ?, ?, ?, ?, ?, ?, @po_id, @batch_id)');
                        if (!$stmtRestock) {
                            throw new Exception('Failed to prepare restock procedure: ' . $conn->error);
                        }
                        $expiryDateParam = $expiryDate;
                        $stmtRestock->bind_param(
                            'iiiidiss',
                            $productId,
                            $branchId,
                            $supplierId,
                            $quantity,
                            $unitCost,
                            $snapshotStock,
                            $snapshotLast,
                            $expiryDateParam
                        );
                        if (!$stmtRestock->execute()) {
                            throw new Exception('Failed to execute restock procedure: ' . $stmtRestock->error);
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

                        $_SESSION['inventory_success'] = 'Restock successful. Inventory updated.';
                        header('Location: inventory.php');
                        exit();
                    } catch (Exception $inner) {
                        $message = $inner->getMessage();
                        if (strpos($message, 'Restock snapshot mismatch') !== false) {
                            $actorInfo = getLastRestockActor($conn, $productId, $branchId);
                            [$actorName, $actionTime] = resolveActorContext($actorInfo, 'Other staff');
                            $reasonText = sprintf('Restock failed: %s%s just updated this product. Refresh and try again.', $actorName, $actionTime);
                            $_SESSION['inventory_error'] = $reasonText;
                            header('Location: inventory.php');
                            exit();
                        }
                        $error_message = $message;
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
                           received_date, date_expired, product_name, sku, unit_cost, unit_price,
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
                            'product_name' => $row['product_name'] ?? ('Item ' . $pid),
                            'batch_id' => $row['batch_ID'],
                            'quantity_on_hand' => (int)$row['quantity_on_hand'],
                            'quantity_received' => (int)$row['quantity_received'],
                            'received_date' => $row['received_date'],
                            'date_expired' => $row['date_expired'],
                            'unit_cost' => $row['unit_cost'],
                            'unit_price' => $row['unit_price'],
                            'restock_category_id' => $restockCategoryId,
                            'supplier' => $supplierInfo
                        ];

                        if (!isset($inventorySummary[$pid])) {
                            $inventorySummary[$pid] = [
                                'product_id' => $pid,
                                'product_code' => $code,
                                'product_name' => $row['product_name'] ?? ('Item ' . $pid),
                                'total_stock' => 0,
                                'total_received' => 0,
                                'last_received' => $row['received_date'] ?? null,
                                'unit_cost' => $row['unit_cost'] ?? null,
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
                    $error_message = 'Failed to load inventory data: ' . $conn->error;
                }
                $stmt->close();
            } else {
                $error_message = 'Failed to prepare inventory query: ' . $conn->error;
            }

            $resultSuppliers = $conn->query('SELECT supplier_ID, company_name, supplier_category FROM Supplier ORDER BY company_name ASC');
            if ($resultSuppliers) {
                while ($row = $resultSuppliers->fetch_assoc()) {
                    $supplierOptions[] = $row;
                }
                $resultSuppliers->free();
            }

            $resultPricing = $conn->query("SELECT supplier_ID, product_ID, price, unit_price
                                            FROM v_staff_supplier_pricing");
            if ($resultPricing) {
                while ($row = $resultPricing->fetch_assoc()) {
                    $supplierPricing[] = $row;
                }
                $resultPricing->free();
            }

            if ($stmtUnstocked = $conn->prepare("SELECT branch_ID, product_ID, product_name, sku, unit_cost, unit_price, unit, description, category_id, parent_category_id, category_name
                                                 FROM v_staff_branch_unstocked_products
                                                 WHERE branch_ID = ?
                                                 ORDER BY product_name ASC")) {
                $stmtUnstocked->bind_param('i', $branchId);
                if ($stmtUnstocked->execute()) {
                    $rsUnstocked = $stmtUnstocked->get_result();
                    while ($row = $rsUnstocked->fetch_assoc()) {
                        $pid = (int)$row['product_ID'];
                        $restockCategoryId = resolveRestockCategoryId(
                            (int)($row['parent_category_id'] ?? 0),
                            (int)($row['category_id'] ?? 0)
                        );
                        $unstockedProducts[] = [
                            'product_id' => $pid,
                            'product_code' => $row['sku'] ?? ('P' . str_pad($pid, 4, '0', STR_PAD_LEFT)),
                            'product_name' => $row['product_name'] ?? ('Item ' . $pid),
                            'unit_cost' => $row['unit_cost'] ?? null,
                            'unit_price' => $row['unit_price'] ?? null,
                            'unit' => $row['unit'] ?? '',
                            'description' => $row['description'] ?? '',
                            'category_name' => $row['category_name'] ?? '',
                            'restock_category_id' => $restockCategoryId,
                            'total_stock' => 0,
                            'last_received' => null
                        ];
                    }
                } else {
                    $error_message = 'Failed to load purchasable products: ' . $conn->error;
                }
                $stmtUnstocked->close();
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
        'unit_cost' => $item['unit_cost'],
        'unit_price' => $item['unit_price'],
        'restock_category_id' => $item['restock_category_id'],
        'supplier' => $item['supplier']
    ];
}

$inventoryJson = json_encode($inventoryData, JSON_UNESCAPED_UNICODE);
$unstockedJson = json_encode($unstockedProducts, JSON_UNESCAPED_UNICODE);
$pricingJson = json_encode($supplierPricing, JSON_UNESCAPED_UNICODE);
$batchJson = json_encode($inventoryBatches, JSON_UNESCAPED_UNICODE);
if ($inventoryJson === false) {
    $inventoryJson = '[]';
} else {
    $inventoryJson = str_replace('</', '<\/', $inventoryJson);
}
$supplierOptionsJson = json_encode($supplierOptions, JSON_UNESCAPED_UNICODE);
if ($unstockedJson === false) {
    $unstockedJson = '[]';
} else {
    $unstockedJson = str_replace('</', '<\/', $unstockedJson);
}
if ($pricingJson === false) {
    $pricingJson = '[]';
} else {
    $pricingJson = str_replace('</', '<\/', $pricingJson);
}
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
<html lang="en">
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
.new-purchase-list {
    margin-top: 26px;
    margin-bottom: 32px;
}
.restock-card.new {
    border-left: 5px solid #1e88e5;
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
            <h2 class="section-title">Inventory Alerts & Restock</h2>
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
            <h2 class="section-title" style="margin-top:40px;">Inventory Overview</h2>
            <div style="overflow-x:auto;">
                <table class="inventory-table" id="inventoryTable">
                    <thead>
                        <tr>
                            <th>Product Code</th>
                            <th>Product Name</th>
                            <th>Current Stock</th>
                            <th>Reorder Level</th>
                            <th>Last Received</th>
                            <th>Supplier</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <h2 class="section-title" style="margin-top:40px;">New Product Purchases</h2>
            <div id="newPurchaseList" class="new-purchase-list"></div>
        </section>
    </main>
    <?php include 'footer.php'; ?>
    <script>
    const inventorySummary = <?php echo $inventoryJson ?: '[]'; ?>;
    const unstockedProducts = <?php echo $unstockedJson ?: '[]'; ?>;
    const supplierPricing = <?php echo $pricingJson ?: '[]'; ?>;
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
            wrap.innerHTML = '<div style="color:#888;padding:32px;text-align:center;">No inventory data for this branch</div>';
            return;
        }
        if (!low.length && !warn.length) {
            wrap.innerHTML = '<div style="color:#43a047;padding:32px;text-align:center;">Stock looks healthy, no restock needed</div>';
            return;
        }
        low.forEach(item => wrap.appendChild(createRestockCard(item, 'low')));
        warn.forEach(item => wrap.appendChild(createRestockCard(item, 'warn')));
    }

    function renderNewPurchaseList() {
        const wrap = document.getElementById('newPurchaseList');
        if (!wrap) return;
        wrap.innerHTML = '';
        if (!unstockedProducts.length) {
            wrap.innerHTML = '<div style="color:#888;padding:28px;text-align:center;">No new products available to purchase</div>';
            return;
        }
        unstockedProducts.forEach(item => wrap.appendChild(createNewPurchaseCard(item)));
    }


    function createNewPurchaseCard(item) {
        const card = document.createElement('div');
        card.className = 'restock-card new';
        const desc = item.description ? item.description : 'No description';
        card.innerHTML = `
            <div style="flex:1;">
                <div style="font-size:16px;font-weight:600;">${item.product_name} <span style="color:#888;font-size:13px;">(${item.product_code})</span></div>
                <div style="font-size:13px;color:#888;margin-top:6px;">${desc}</div>
            </div>
            <button class="btn btn-primary restock-btn" onclick='openRestockOrderForm(${JSON.stringify(item)}, "new")'>Purchase</button>
        `;
        return card;
    }

    function buildPricingMap() {
        const map = new Map();
        supplierPricing.forEach(row => {
            const key = `${row.supplier_ID}-${row.product_ID}`;
            map.set(key, {
                unit_cost: Number(row.price) || 0,
                unit_price: Number(row.unit_price) || 0
            });
        });
        return map;
    }

    const pricingMap = buildPricingMap();

    function resolvePricing(productId, supplierId, fallbackPrice) {
        const key = `${supplierId}-${productId}`;
        const entry = pricingMap.get(key);
        return {
            unit_cost: entry && entry.unit_cost > 0 ? entry.unit_cost : 0,
            unit_price: entry && entry.unit_price > 0 ? entry.unit_price : (Number(fallbackPrice) || 0)
        };
    }

    function createRestockCard(item, type) {
        const card = document.createElement('div');
        card.className = 'restock-card ' + type;
        card.innerHTML = `
            <div style="font-size:16px;font-weight:600;">${item.product_name} <span style="color:#888;font-size:13px;">(${item.product_code})</span></div>
            <div style="font-size:14px;color:${type === 'low' ? '#e53935' : '#ffb300'};">Current stock: ${item.total_stock}</div>
            <div style="font-size:14px;color:#888;">Reorder level: ${item.reorder_level}</div>
            <button class="btn btn-primary restock-btn" onclick='openSupplierPopup(${JSON.stringify(item)})'>Restock</button>
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
            return '<option value=\"\">No available suppliers</option>';
        }
        return filtered.map(opt => {
            const sel = Number(selectedId) === Number(opt.supplier_ID) ? 'selected' : '';
            return `<option value=\"${opt.supplier_ID}\" ${sel}>${opt.company_name}</option>`;
        }).join('');
    }

    function openRestockOrderForm(item, mode = 'restock') {
        const popup = document.getElementById('supplierPopup');
        const supplierId = item.supplier?.id || '';
        const parsedStock = Number(item.total_stock);
        const snapshotStock = Number.isFinite(parsedStock) ? parsedStock : '';
        const snapshotLastReceived = item.last_received || '';
        const titleText = mode === 'new' ? 'New Purchase Order' : 'Restock Order';
        const basePricing = resolvePricing(item.product_id, supplierId, item.unit_price);
        const costValue = basePricing.unit_cost > 0 ? basePricing.unit_cost : 0;
        const sellValue = basePricing.unit_price > 0 ? basePricing.unit_price : (Number(item.unit_price) || 0);
        popup.innerHTML = `
            <h3>${titleText}</h3>
            <form id='restockForm' method='post'>
                <input type='hidden' name='restock_action' value='1'>
                <input type='hidden' name='product_id' value='${item.product_id}'>
                <input type='hidden' name='snapshot_total_stock' value='${snapshotStock}'>
                <input type='hidden' name='snapshot_last_received' value='${snapshotLastReceived}'>
                <div class='info'><b>Product:</b> ${item.product_name} (${item.product_code})</div>
                <div class='info'><b>Branch:</b> <input type='text' value='${branchName}' readonly style='width:60%;margin-left:8px;background:#f5f5f5;'></div>
                <div class='info'><b>Supplier:</b>
                    <select name='supplier_id' required style='margin-left:8px;flex:1;' onchange="updateRestockPricing(${item.product_id}, this.value, ${Number(item.unit_price) || 0})">
                        ${buildSupplierOptions(item, supplierId)}
                    </select>
                </div>
                <div class='info'><b>Quantity:</b> <input type='number' name='qty' id='restockQty' min='1' value='10' required style='width:80px;margin-left:8px;' oninput='updateRestockTotal()'></div>
                <div class='info'><b>Unit cost:</b> ¥<span id='restockUnitCost'>${costValue.toFixed(2)}</span><input type='hidden' name='unit_cost' id='restockUnitCostInput' value='${costValue.toFixed(2)}'></div>
                <div class='info'><b>Selling price:</b> ¥<span id='restockUnitPrice'>${sellValue.toFixed(2)}</span></div>
                <div class='info'><b>Expected expiry (optional):</b> <input type='date' name='expiry_date' style='margin-left:8px;'></div>
                <div class='info'><b>Total:</b> ¥<span id='restockTotal'>${(costValue*10).toFixed(2)}</span></div>
                <div class='actions' style='margin-top:18px;'>
                    <button class='btn btn-success' type='submit'>Submit Order</button>
                    <button class='btn btn-warning' type='button' style='margin-left:10px;' onclick='closeSupplierPopup()'>Cancel</button>
                </div>
            </form>
        `;
        const supplierSelect = popup.querySelector("select[name='supplier_id']");
        if (supplierSelect && supplierSelect.value) {
            updateRestockPricing(item.product_id, supplierSelect.value, Number(item.unit_price) || 0);
        }
        popup.style.display = 'block';
        document.getElementById('popupMask').style.display = 'block';
    }

    function updateRestockTotal() {
        const qtyInput = document.getElementById('restockQty');
        const qty = qtyInput ? parseInt(qtyInput.value, 10) || 0 : 0;
        const costInput = document.getElementById('restockUnitCostInput');
        const unitCost = costInput ? parseFloat(costInput.value) || 0 : 0;
        const target = document.getElementById('restockTotal');
        if (target) {
            target.textContent = (unitCost * qty).toFixed(2);
        }
    }

    function updateRestockPricing(productId, supplierId, fallbackPrice) {
        const pricing = resolvePricing(productId, supplierId, fallbackPrice);
        const costSpan = document.getElementById('restockUnitCost');
        const priceSpan = document.getElementById('restockUnitPrice');
        const costInput = document.getElementById('restockUnitCostInput');
        if (costSpan) {
            costSpan.textContent = pricing.unit_cost.toFixed(2);
        }
        if (priceSpan) {
            priceSpan.textContent = pricing.unit_price.toFixed(2);
        }
        if (costInput) {
            costInput.value = pricing.unit_cost.toFixed(2);
        }
        updateRestockTotal();
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
        let html = `<h3>Batch Management - ${product ? `${product.product_name} (${product.product_code})` : ''}</h3>`;
        if (!batches.length) {
            html += `<div style="padding:16px 0;color:#888;">No adjustable batches for this product.</div>`;
        } else {
            html += '<div class="batch-list">';
            batches.forEach(batch => {
                const safeBatch = JSON.stringify(batch).replace(/"/g, '&quot;');
                const receivedDate = batch.received_date ? batch.received_date.split(' ')[0] : '--';
                const expireDate = batch.date_expired || '--';
                html += `
                    <div class="batch-row">
                        <div class="info-block">
                            <div><b>Batch:</b> ${batch.batch_id || '--'}</div>
                            <div><b>Stock:</b> ${batch.quantity_on_hand}, <b>Received:</b> ${receivedDate}, <b>Expires:</b> ${expireDate}</div>
                        </div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <button class="btn btn-danger" style="padding:6px 12px;" onclick="window.location.href='inventory_return.php?batch_id=${encodeURIComponent(batch.batch_id)}'">Return</button>
                            <button class="btn btn-primary" style="padding:6px 12px;" onclick="window.location.href='inventory_adjustment.php?batch_id=${encodeURIComponent(batch.batch_id)}'">Adjust</button>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
        }
        html += `<div class='actions' style='margin-top:18px;text-align:right;'><button class='btn btn-warning' onclick='closeSupplierPopup()'>Close</button></div>`;
        popup.innerHTML = html;
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
            row.innerHTML = `<td colspan="7" style="text-align:center;color:#888;padding:24px;">No inventory data</td>`;
            tbody.appendChild(row);
            return;
        }
        inventorySummary.forEach(item => {
            const supplierName = item.supplier?.name || 'Unknown Supplier';
            const lastReceived = item.last_received ? item.last_received.split(' ')[0] : '--';
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.product_code}</td>
                <td>${item.product_name}</td>
                <td>${item.total_stock}</td>
                <td>${item.reorder_level}</td>
                <td>${lastReceived}</td>
                <td>${supplierName}</td>
                <td><button class="btn btn-primary" style="padding:6px 12px;" onclick="openBatchManager(${item.product_id})">Manage Batches</button></td>
            `;
            tbody.appendChild(row);
        });
    }

    renderRestockList();
    renderNewPurchaseList();
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
