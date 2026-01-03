<?php
require_once __DIR__ . '/../../config/db_connect.php';

$customer_id = $_SESSION['customer_id'] ?? null;

function getCustomerFullInfo() { 
    if (!isset($_SESSION['customer_id'])) {
        return null; 
    }
    $current_customer_id = $_SESSION['customer_id'];
    
    $conn = getDBConnection();
    
    $query = "SELECT * FROM v_customer_profile WHERE customer_ID = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $current_customer_id); 
    $stmt->execute();
    $result = $stmt->get_result();
    
    $customer_info = null;
    if ($result && $result->num_rows > 0) {
        $customer_info = $result->fetch_assoc();

        if (isset($customer_info['first_name']) && isset($customer_info['last_name'])) {
            $customer_info['full_name'] = $customer_info['first_name'] . ' ' . $customer_info['last_name'];
        }
        if (isset($customer_info['phone'])) {
            $customer_info['customer_phone'] = $customer_info['phone'];
        }
    }
    
    $stmt->close();
    $conn->close();
    
    return $customer_info;
}
function getDiscountRate($customer_id) {
    $conn = getDBConnection();
    $discount_rate = 0;
    
    $customer_query = "SELECT loyalty_level FROM v_customer_profile WHERE customer_id = ?";
    $stmt = $conn->prepare($customer_query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer_data = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    if ($customer_data) {
        switch ($customer_data['loyalty_level']) {
            case 'VIP': $discount_rate = 0.05; break;
            case 'VVIP': $discount_rate = 0.10; break;
        }
    }
    return $discount_rate;
}
function calculateFinalAmount($customer_id, $total_amount) {
    $conn = getDBConnection();
    $discount_rate = getDiscountRate($customer_id);
    return $total_amount * (1 - $discount_rate);
}


function getOrderDetails($orderId) {
    $conn = getDBConnection();
    
    $orderQuery = "SELECT * FROM v_order_history WHERE order_ID = ?";
    $orderStmt = $conn->prepare($orderQuery);
    $orderStmt->bind_param("i", $orderId);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    $order = $orderResult->fetch_assoc();
    $orderStmt->close();

    if (!$order) {
        $conn->close();
        return null;
    }
    $order['order_number'] = 'ORD' . str_pad($order['order_ID'], 6, '0', STR_PAD_LEFT);
    $itemsQuery = "SELECT 
                    oi.item_ID,
                    p.product_name,
                    oi.unit_price,
                    oi.quantity,
                    (oi.unit_price * oi.quantity) AS total
                   FROM OrderItem oi
                   JOIN products p ON oi.product_ID = p.product_ID
                   WHERE oi.order_ID = ?";
    $itemsStmt = $conn->prepare($itemsQuery);
    $itemsStmt->bind_param("i", $orderId);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();
    
    $order['items'] = [];
    while ($item = $itemsResult->fetch_assoc()) {
        $order['items'][] = $item;
    }
    $itemsStmt->close();
    $conn->close();
    return $order;
}

function getCustomerOrders($customerId, $status = NULL) {
    if (!$customerId) return [];
    
    $conn = getDBConnection();
    $sql = "SELECT * FROM v_order_history WHERE customer_ID = ?";
    $params = [$customerId];
    $types = "i";
    if ($status !== null && $status !== 'all') {
        $sql .= " AND order_status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $sql .= " ORDER BY order_date DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (count($params) > 1) {
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->bind_param($types, $params[0]);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = [
            'id' => $row['order_ID'],
            'order_number' => 'ORD' . str_pad($row['order_ID'], 6, '0', STR_PAD_LEFT),
            'order_date' => date('Y-m-d H:i', strtotime($row['order_date'])),
            'status' => $row['order_status'],
            'total_amount' => $row['final_amount'], 
            'product_details' => $row['product_details'],
            'store_name' => $row['store_name'],
            'shipping_address' => $row['shipping_address'] ?? '无'
        ];
    }
    $stmt->close();
    $conn->close();
    return $orders;
}

function getCustomerProfile($customer_id) {
    if (!$customer_id) return null;
    
    $conn = getDBConnection();
    
    $query = "SELECT * FROM v_customer_profile WHERE customer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $profile = null;
    if ($result && $result->num_rows > 0) {
        $profile = $result->fetch_assoc();
    }
    
    $stmt->close();
    $conn->close();
    return $profile;
}

function getProductCatalog($branch_id = null, $category = null) {
    $conn = getDBConnection();
    
    $query = "SELECT * FROM v_customer_product_info WHERE 1=1";
    $params = [];
    $types = "";
    
    if ($category) {
        $query .= " AND category_name = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    $query .= " ORDER BY product_name";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($query);
    }
    
    $products = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $attributes = [];
            if (!empty($row['attributes'])) {
                $attrParts = explode(', ', $row['attributes']);
                foreach ($attrParts as $part) {
                    list($name, $value) = explode(': ', $part, 2);
                    $attributes[$name] = $value;
                }
            }
            $row['attributes'] = $attributes;
            $products[] = $row;
        }
    }
    
    if (isset($stmt)) $stmt->close();
    $conn->close();
    return $products;
}

// 修改：根据门店获取或创建购物车
function getOrCreatePendingOrder($customerId, $branchId = 1) {
    $conn = getDBConnection();
    $query = "SELECT order_ID FROM CustomerOrder WHERE customer_id = ? AND branch_id = ? AND status = 'Pending' LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $customerId, $branchId);
    $stmt->execute();
    $result = $stmt->get_result();

    $orderId = null;
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $orderId = $row['order_ID'];
    } else {
        $createQuery = "INSERT INTO CustomerOrder (customer_id, branch_id, status, order_date, total_amount) 
                        VALUES (?, ?, 'Pending', NOW(), 0.00)";
        $createStmt = $conn->prepare($createQuery);
        $createStmt->bind_param("ii", $customerId, $branchId);
        $createStmt->execute();
        $orderId = $conn->insert_id;
        $createStmt->close();
    }

    $stmt->close();
    $conn->close();
    return $orderId;
}

function getShoppingCartData($customer_id, $branch_id = null) {
    if (!$customer_id) {
        return [
            'cart_id' => null,
            'items' => [],
            'total_items' => 0,
            'total_quantity' => 0,
            'total_amount' => 0,
            'final_amount' => 0,
            'discount' => 0,
            'has_items' => false
        ];
    }

    $conn = getDBConnection();
    
    // 查询购物车数据
    $query = "SELECT * FROM v_wishlist_products WHERE customer_id = ?";
    $params = [$customer_id];
    $paramTypes = "i";
    
    if ($branch_id !== null) {
        $query .= " AND branch_id = ?";
        $params[] = $branch_id;
        $paramTypes .= "i";
    }
    
    $stmt = $conn->prepare($query);
    
    if ($branch_id !== null) {
        $stmt->bind_param($paramTypes, $customer_id, $branch_id);
    } else {
        $stmt->bind_param($paramTypes, $customer_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cart_items = [];
    $cart_total = 0;
    $total_quantity = 0;
    $cart_by_branch = [];
    $order_ids = [];
    
    while($row = $result->fetch_assoc()) {
        $item_total = $row['item_total'];
        $branch_id_val = $row['branch_id'];
        $order_id = $row['order_ID'];
        
        // 记录唯一的订单ID
        if (!in_array($order_id, $order_ids)) {
            $order_ids[] = $order_id;
        }
        
        // 按门店分组
        if (!isset($cart_by_branch[$branch_id_val])) {
            $cart_by_branch[$branch_id_val] = [
                'order_id' => $order_id,
                'branch_name' => $row['branch_name'],
                'items' => [],
                'total' => 0,
                'quantity' => 0,
                'item_count' => 0
            ];
        }
        
        $cart_by_branch[$branch_id_val]['items'][] = $row;
        $cart_by_branch[$branch_id_val]['total'] += $item_total;
        $cart_by_branch[$branch_id_val]['quantity'] += $row['quantity'];
        $cart_by_branch[$branch_id_val]['item_count']++;
        
        $cart_items[] = $row;
        $cart_total += $item_total;
        $total_quantity += $row['quantity'];
    }
    
    $stmt->close();
    $conn->close();
    
    // 计算折扣
    $discount_rate = getDiscountRate($customer_id);
    $discount_amount = $cart_total * $discount_rate;
    $final_amount = $cart_total - $discount_amount;
    
    // 如果指定了门店
    if ($branch_id !== null && isset($cart_by_branch[$branch_id])) {
        $branch_cart = $cart_by_branch[$branch_id];
        $branch_final_amount = calculateFinalAmount($customer_id, $branch_cart['total']);
        
        return [
            'cart_id' => $branch_cart['order_id'],
            'branch_id' => $branch_id,
            'branch_name' => $branch_cart['branch_name'],
            'items' => $branch_cart['items'],
            'total_items' => $branch_cart['item_count'],
            'total_quantity' => $branch_cart['quantity'],
            'total_amount' => $branch_cart['total'],
            'final_amount' => $branch_final_amount,
            'discount' => $discount_rate,
            'has_items' => !empty($branch_cart['items']),
            'order_ids' => $order_ids
        ];
    } else {
        // 返回所有门店
        return [
            'cart_id' => null,
            'branches' => $cart_by_branch,
            'items' => $cart_items,
            'total_items' => count($cart_items),
            'total_quantity' => $total_quantity,
            'total_amount' => $cart_total,
            'final_amount' => $final_amount,
            'discount' => $discount_rate,
            'has_items' => !empty($cart_items),
            'order_ids' => $order_ids
        ];
    }
}

function getAllShoppingCarts($customer_id) {
    if (!$customer_id) {
        return ['carts' => [], 'total_carts' => 0, 'total_all_amount' => 0];
    }

    $conn = getDBConnection();
    
    // 使用视图查询
    $query = "SELECT * FROM v_customer_carts_by_branch WHERE customer_id = ? ORDER BY branch_name";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $carts = [];
    $total_all = 0;
    
    while($row = $result->fetch_assoc()) {
        $final_amount = calculateFinalAmount($customer_id, $row['total_amount']);
        $carts[] = [
            'order_id' => $row['order_ID'],
            'branch_id' => $row['branch_id'],
            'branch_name' => $row['branch_name'],
            'unique_products' => $row['unique_products'],
            'total_items' => $row['total_items'],
            'total_quantity' => $row['total_quantity'],
            'total_amount' => $row['total_amount'],
            'final_amount' => $final_amount,
            'product_list' => $row['product_list'],
            'created_at' => $row['created_at'],
        ];
        $total_all += $final_amount;
    }
    
    $stmt->close();
    $conn->close();
    
    return [
        'carts' => $carts,
        'total_carts' => count($carts),
        'total_all_amount' => $total_all
    ];
}

function getFIFOStockItems($productId, $quantity, $branchId) {
    $conn = getDBConnection();
    $batches = [];
    
    $currentDate = date('Y-m-d');

    $query = "SELECT batch_ID, (quantity_on_hand - locked_inventory) AS available_qty 
              FROM Inventory 
              WHERE product_ID = ? 
                AND branch_ID = ? 
                AND (quantity_on_hand - locked_inventory) > 0
                AND (date_expired IS NULL OR date_expired >= ?)  
              ORDER BY received_date ASC, date_expired ASC"; 

    $stmt = $conn->prepare($query);
    $stmt->bind_param("iis", $productId, $branchId, $currentDate);
    $stmt->execute();
    $result = $stmt->get_result();

    $totalAvailable = 0;
    $availableBatches = [];
    while ($row = $result->fetch_assoc()) {
        $totalAvailable += $row['available_qty'];
        $availableBatches[] = $row;
    }
    $stmt->close();

    if ($totalAvailable < $quantity) {
        $conn->close();
        return [];
    }
    $remaining = $quantity;
    foreach ($availableBatches as $batch) {
        if ($remaining <= 0) break;
        $take = min($remaining, $batch['available_qty']);
        $batches[] = [
            'batch_id' => $batch['batch_ID'],
            'lock_qty' => $take
        ];
        $remaining -= $take;
    }

    $conn->close();
    return $batches;
}
function addToCartWithFIFO($customerId, $productId, $quantity, $branchId = 1) {
    if ($customerId <= 0 || $productId <= 0 || $quantity < 1) {
        return ['success' => false, 'message' => '无效的参数'];
    }

    $conn = getDBConnection();
    $conn->begin_transaction();

    try {
        $storeCheckQuery = "SELECT 
                           b.branch_ID as store_id,
                           b.branch_name as store_name,
                           SUM(i.quantity_on_hand - i.locked_inventory) as available_stock
                           FROM Inventory i
                           JOIN Branch b ON i.branch_ID = b.branch_ID
                           WHERE i.product_ID = ? AND i.branch_ID = ?
                           GROUP BY i.branch_ID";
        
        $storeStmt = $conn->prepare($storeCheckQuery);
        $storeStmt->bind_param("ii", $productId, $branchId);
        $storeStmt->execute();
        $storeResult = $storeStmt->get_result();
        
        if ($storeResult->num_rows === 0) {
            throw new Exception("该商品在所选门店不存在。");
        }
        
        $storeInfo = $storeResult->fetch_assoc();
        $availableStock = $storeInfo['available_stock'] ?? 0;
        
        if ($availableStock < $quantity) {
            throw new Exception("库存不足！所选门店可用库存为{$availableStock}");
        }
        
        $storeStmt->close();

        // 获取或创建该门店的购物车
        $orderId = getOrCreatePendingOrder($customerId, $branchId);
        if (!$orderId) {
            throw new Exception("订单创建失败！");
        }

        $priceQuery = "SELECT unit_price FROM products WHERE product_ID = ? AND status = 'active'";
        $priceStmt = $conn->prepare($priceQuery);
        $priceStmt->bind_param("i", $productId);
        $priceStmt->execute();
        $priceResult = $priceStmt->get_result();
        if ($priceResult->num_rows === 0) {
            throw new Exception("The product does not exist or has been taken off the shelves!");
        }
        $product = $priceResult->fetch_assoc();
        $unitPrice = $product['unit_price'];
        $priceStmt->close();

        $currentDate = date('Y-m-d');
        $batchesQuery = "SELECT 
                    inv.batch_ID,
                    inv.quantity_on_hand,
                    inv.locked_inventory,
                    (inv.quantity_on_hand - inv.locked_inventory) AS available_qty,
                    inv.received_date,
                    inv.date_expired
                 FROM Inventory inv
                 WHERE inv.product_ID = ? 
                   AND inv.branch_ID = ?
                   AND (inv.quantity_on_hand - inv.locked_inventory) > 0
                 ORDER BY 
                    COALESCE(inv.date_expired, '9999-12-31') ASC,
                    inv.received_date ASC";

       $batchesStmt = $conn->prepare($batchesQuery);
       $batchesStmt->bind_param("ii", $productId, $branchId);
       $batchesStmt->execute();
       $batchesResult = $batchesStmt->get_result();

        $totalAvailable = 0;
        $availableBatches = [];
        $batchDetails = []; 
        
        while ($row = $batchesResult->fetch_assoc()) {
            $batchAvailable = $row['available_qty'];
            $totalAvailable += $batchAvailable;
            $availableBatches[] = $row;
            
            $batchDetails[$row['batch_ID']] = [
                'quantity_on_hand' => $row['quantity_on_hand'],
                'locked_inventory' => $row['locked_inventory'],
                'available_qty' => $batchAvailable,
                'date_expired' => $row['date_expired']
            ];
        }
        $batchesStmt->close();

        if ($totalAvailable < $quantity) {
            throw new Exception("The actual batch inventory is insufficient to meet the purchase quantity. Available: {$totalAvailable}, need: {$quantity}");
        }

        $remaining = $quantity;
        $batchAllocations = [];
        $batchIds = []; 

        foreach ($availableBatches as $batch) {
            if ($remaining <= 0) break;
            
            $batchId = $batch['batch_ID'];
            $batchAvailable = $batch['available_qty'];
            
            $take = min($remaining, $batchAvailable);
            
            if ($take > 0) {
                $batchAllocations[] = [
                    'batch_id' => $batchId,
                    'lock_qty' => $take,
                    'date_expired' => $batch['date_expired'],
                    'received_date' => $batch['received_date']
                ];
                $remaining -= $take;
                $batchIds[] = $batchId;
            }
        }

        error_log("FEFO distribute - Product_ID: $productId, Branch_ID: $branchId, Quantity: $quantity");
        foreach ($batchAllocations as $alloc) {
            error_log("  -> batch: {$alloc['batch_id']}, Quantity: {$alloc['lock_qty']}, expiration time: {$alloc['date_expired']}");
        }
        foreach ($batchAllocations as $allocation) {
            $batchId = $allocation['batch_id'];
            $need = $allocation['lock_qty'];
            $checkLockQuery = "SELECT (quantity_on_hand - locked_inventory) AS current_available 
                              FROM Inventory 
                              WHERE batch_ID = ? 
                                AND product_ID = ?
                                AND branch_ID = ?";
            
            $checkStmt = $conn->prepare($checkLockQuery);
            $checkStmt->bind_param("sii", $batchId, $productId, $branchId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $currentAvailable = 0;
            
            if ($checkRow = $checkResult->fetch_assoc()) {
                $currentAvailable = $checkRow['current_available'];
            }
            $checkStmt->close();
            
            if ($currentAvailable < $need) {
                throw new Exception("Batch {$batchId} The current available inventory is insufficient. Current: {$currentAvailable}, need: {$need}");
            }
            
            $lockQuery = "UPDATE Inventory 
                         SET locked_inventory = locked_inventory + ? 
                         WHERE batch_ID = ? 
                           AND product_ID = ?
                           AND branch_ID = ?
                           AND (quantity_on_hand - locked_inventory) >= ?";
            
            $lockStmt = $conn->prepare($lockQuery);
            $lockStmt->bind_param("isiii", $need, $batchId, $productId, $branchId, $need);
            $lockStmt->execute();
            
            if ($lockStmt->affected_rows <= 0) {
                throw new Exception("Locked inventory {$batchId} fails!");
            }
            $lockStmt->close();
        }

        $totalProcessed = 0;
        foreach ($batchAllocations as $allocation) {
            $batchId = $allocation['batch_id'];
            $need = $allocation['lock_qty'];
            $stockQuery = "SELECT item_ID FROM StockItem 
               WHERE batch_ID = ? 
                 AND product_ID = ? 
                 AND branch_ID = ?
                 AND status = 'in_stock' 
                 AND customer_order_ID IS NULL
               ORDER BY received_date ASC
               LIMIT ?";
            
            $stockStmt = $conn->prepare($stockQuery);
            $stockStmt->bind_param("siii", $batchId, $productId, $branchId, $need);
            $stockStmt->execute();
            $stockResult = $stockStmt->get_result();

            $foundItems = [];
            while ($row = $stockResult->fetch_assoc()) {
                $foundItems[] = $row['item_ID'];
            }
            $stockStmt->close();
            
            $foundCount = count($foundItems);
            
            if ($foundCount >= $need) {
                for ($i = 0; $i < $need; $i++) {
                    $itemId = $foundItems[$i];
                    
                    $insertOrderItem = "INSERT INTO OrderItem 
                   (order_ID, item_ID, unit_price, product_ID, quantity, status)
                   VALUES (?, ?, ?, ?, 1, 'pending')";

                   $orderItemStmt = $conn->prepare($insertOrderItem);
                   $orderItemStmt->bind_param("issd", $orderId, $itemId, $unitPrice, $productId);
                   $orderItemStmt->execute();
                    
                    $updateStock = "UPDATE StockItem 
                                   SET status = 'pending', customer_order_ID = ? 
                                   WHERE item_ID = ?";
                    
                    $updateStmt = $conn->prepare($updateStock);
                    $updateStmt->bind_param("is", $orderId, $itemId);
                    $updateStmt->execute();
                    $updateStmt->close();
                    
                    $totalProcessed++;
                }
            } 
            else {
                foreach ($foundItems as $itemId) {
                    $insertOrderItem = "INSERT INTO OrderItem 
                                       (order_ID, item_ID, unit_price, product_ID, quantity, status)
                                       VALUES (?, ?, ?, ?, 1, 'pending')";
                    
                    $orderItemStmt = $conn->prepare($insertOrderItem);
                    $orderItemStmt->bind_param("issd", $orderId, $itemId, $unitPrice, $productId);
                    $orderItemStmt->execute();
                    $orderItemStmt->close();
                    $updateStock = "UPDATE StockItem 
                                   SET status = 'pending', customer_order_ID = ? 
                                   WHERE item_ID = ?";
                    
                    $updateStmt = $conn->prepare($updateStock);
                    $updateStmt->bind_param("is", $orderId, $itemId);
                    $updateStmt->execute();
                    $updateStmt->close();
                    
                    $totalProcessed++;
                    $need--;
                }
                
                for ($i = 0; $i < $need; $i++) {
                    $newItemId = uniqid('SI_', true); 
                    $expiryDate = $allocation['date_expired'] ?: NULL;
                    
                    $createStockItem = "INSERT INTO StockItem 
                                       (item_ID, batch_ID, product_ID, branch_ID, status, 
                                        received_date, expiry_date, customer_order_ID)
                                       VALUES (?, ?, ?, ?, 'pending', NOW(), ?, ?)";
                    
                    $createStmt = $conn->prepare($createStockItem);
                    $createStmt->bind_param("ssiiss", $newItemId, $batchId, $productId, $branchId, $expiryDate, $orderId);
                    $createStmt->execute();
                    $createStmt->close();
                    

                    $insertOrderItem = "INSERT INTO OrderItem 
                                       (order_ID, item_ID, unit_price, product_ID, quantity, status)
                                       VALUES (?, ?, ?, ?, 1, 'pending')";
                    
                    $orderItemStmt = $conn->prepare($insertOrderItem);
                    $orderItemStmt->bind_param("issd", $orderId, $newItemId, $unitPrice, $productId);
                    $orderItemStmt->execute();
                    $orderItemStmt->close();
                    
                    $totalProcessed++;
                }
            }
        }

        if ($totalProcessed !== $quantity) {
            throw new Exception("Abnormal inventory allocation, actual processing quantity ({$totalProcessed}) and the quantity of demand ({$quantity}) mismatch!");
        }

        // ========== 步骤10：更新订单总金额 ==========
        $updateOrderQuery = "UPDATE CustomerOrder 
                            SET total_amount = (
                                SELECT COALESCE(SUM(unit_price * quantity), 0) 
                                FROM OrderItem 
                                WHERE order_ID = ?
                            ), order_date = NOW() 
                            WHERE order_ID = ?";
        
        $updateOrderStmt = $conn->prepare($updateOrderQuery);
        $updateOrderStmt->bind_param("ii", $orderId, $orderId);
        $updateOrderStmt->execute();
        $updateOrderStmt->close();

        // ========== 步骤11：提交事务 ==========
        $conn->commit();
        $conn->close();
        
        error_log("Shopping cart added successfully - Product_ID: $productId, Branch_ID: $branchId, Quantity: $quantity, Order_ID: $orderId");
        
        return [
            'success' => true, 
            'message' => 'The product has been successfully added to the shopping cart!', 
            'order_id' => $orderId,
            'processed_qty' => $totalProcessed,
            'allocated_batches' => $batchIds
        ];
        
    } catch (Exception $e) {
        // 回滚事务
        if (isset($conn) && $conn) {
            $conn->rollback();
            $conn->close();
        }
        
        error_log("Failed to add to cart - Product_ID: $productId, Branch_ID: $branchId, Quantity: $quantity, Error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
// 检查用户名是否已存在（排除当前用户）
function checkUsernameExists($newUsername, $excludeCustomerId) {
    $conn = getDBConnection();
    
    // 1. 先通过customer_id从Customer表获取当前用户的旧username（用于排除自身）
    $stmt = $conn->prepare("SELECT user_name FROM Customer WHERE customer_ID = ?");
    $stmt->bind_param("i", $excludeCustomerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    $stmt->close();
    
    if (!$customer || empty($customer['username'])) {
        $conn->close();
        return false; // 当前用户不存在或无username，无需校验
    }
    $oldUsername = $customer['username'];
    
    // 2. 检查新用户名是否已被其他用户使用（排除自身）
    $stmt = $conn->prepare("SELECT user_name FROM Customer 
                           WHERE user_name = ? 
                           AND user_name != ?"); // 排除当前用户的旧用户名
    $stmt->bind_param("ss", $newUsername, $oldUsername);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0; // 存在其他用户使用该用户名
    
    $stmt->close();
    $conn->close();
    return $exists;
}

// 更新用户信息（包含修改username）
function updateCustomerInfo($customerId, $data) {
    $conn = getDBConnection();
    $conn->begin_transaction(); // 开启事务，确保两表更新同时成功/失败
    $success = false; // 初始化成功状态

    try {
        // ========== 1. 先获取当前用户的关联信息（Customer + User表） ==========
        $stmt = $conn->prepare("SELECT user_name, gender, phone, email, address FROM Customer WHERE customer_ID = ?");
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
        $stmt->close();

        if (!$customer || empty($customer['user_name'])) {
            throw new Exception("No associated user information was found, customer_ID:{$customerId}");
        }
        $oldUsername = $customer['user_name'];

        // 1.2 从user表获取原有信息（用于对比是否需要更新）
        $stmt = $conn->prepare("SELECT user_telephone, user_email FROM user WHERE user_name = ?");
        $stmt->bind_param("s", $oldUsername);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            throw new Exception("未找到关联的user表信息,user_name:{$oldUsername}");
        }

        // ========== 2. 处理用户名更新（同步更新Customer和user表的user_name） ==========
        $newUsername = $data['username'] ?? null;
        if ($newUsername !== null && $newUsername !== '' && $newUsername !== $oldUsername) {
            // 2.1 更新Customer表的user_name
            $stmt = $conn->prepare("UPDATE Customer SET user_name = ? WHERE customer_ID = ?");
            $stmt->bind_param("si", $newUsername, $customerId);
            $stmt->execute();
            $stmt->close();

            // 2.2 更新user表的user_name（如果user表的主键是user_name，需同步更新）
            $stmt = $conn->prepare("UPDATE user SET user_name = ? WHERE user_name = ?");
            $stmt->bind_param("ss", $newUsername, $oldUsername);
            $stmt->execute();
            $stmt->close();

            error_log("用户{$customerId}更新用户名：{$oldUsername} → {$newUsername}");
            $oldUsername = $newUsername; // 更新后，后续操作使用新的username
        }

        // ========== 3. 处理字段更新（分表更新：user表存电话/邮箱，Customer表存性别/地址） ==========
        $userUpdateParts = [];
        $userParams = [];
        $userParamTypes = '';
        // 定义user表可更新的字段（数据库字段名 => 前端传递的键名）
        $userUpdatableFields = [
            'user_telephone' => 'phone', 
            'user_email' => 'email'      
        ];
        foreach ($userUpdatableFields as $dbField => $dataKey) {
            if (isset($data[$dataKey]) && $data[$dataKey] !== '' && $data[$dataKey] !== $user[$dbField]) {
                $userUpdateParts[] = "{$dbField} = ?";
                $userParams[] = $data[$dataKey];
                $userParamTypes .= 's';
            }
        }
        // 执行user表的更新
        if (!empty($userUpdateParts)) {
            $sql = "UPDATE user SET " . implode(', ', $userUpdateParts) . " WHERE user_name = ?";
            $userParams[] = $oldUsername;
            $userParamTypes .= 's';

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($userParamTypes, ...$userParams);
            $stmt->execute();
            $stmt->close();
            error_log("用户{$customerId}更新user表字段:" . implode(', ', array_keys($userUpdatableFields)));
        }

        // 3.2 更新Customer表的字段（gender、phone、email、address，根据业务调整）
        $customerUpdateParts = [];
        $customerParams = [];
        $customerParamTypes = '';
        // 定义Customer表可更新的字段（排除已在user表更新的字段，避免冗余）
        $customerUpdatableFields = [
            'gender' => 'gender',
            'phone' => 'phone',
            'email' => 'email',
            'address' => 'address'
        ];
        foreach ($customerUpdatableFields as $dbField => $dataKey) {
            if (isset($data[$dataKey]) && $data[$dataKey] !== '' && $data[$dataKey] !== $customer[$dbField]) {
                $customerUpdateParts[] = "{$dbField} = ?";
                $customerParams[] = $data[$dataKey];
                $customerParamTypes .= 's';
            }
        }
        // 执行Customer表的更新
        if (!empty($customerUpdateParts)) {
            $sql = "UPDATE Customer SET " . implode(', ', $customerUpdateParts) . " WHERE customer_ID = ?";
            $customerParams[] = $customerId;
            $customerParamTypes .= 'i';

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($customerParamTypes, ...$customerParams);
            $stmt->execute();
            $stmt->close();
            error_log("用户{$customerId}更新Customer表字段:" . implode(', ', array_keys($customerUpdatableFields)));
        }

        // ========== 4. 提交事务 ==========
        $conn->commit();
        $success = true;
        error_log("用户{$customerId}信息更新成功同步更新Customer和user表");

    } catch (Exception $e) {
        $conn->rollback();
        $success = false;
        error_log("用户{$customerId}信息更新失败：" . $e->getMessage());
    }

    $conn->close();
    return $success;
}

// data.php 新增和修改部分
function getProductCategories() {
    $conn = getDBConnection();
    $query = "SELECT DISTINCT category_name AS category_name, COUNT(*) AS product_count 
             FROM product_catalog_view 
             GROUP BY category_name 
             ORDER BY category_name";
    
    $result = $conn->query($query);
    $categories = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = [
                'name' => $row['category_name'], 
                'count' => $row['product_count']
            ];
        }
    }
    
    $conn->close();
    return $categories;
}

function getProductsByCategory($category = null) {
    $conn = getDBConnection();
    $query = "SELECT * FROM product_catalog_view";
    $params = [];
    $types = "";
    
    if ($category && $category !== 'all') {
        $query .= " WHERE category_name = ?";
        $params[] = $category;
        $types = "s";
    }
    
    $query .= " ORDER BY product_name";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($query);
    }
    
    $products = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = [
                'id' => $row['product_ID'], // 视图的product_ID
                'name' => $row['product_name'], // 视图的product_name
                'description' => $row['description'], // 视图的description
                'price' => $row['unit_price'], // 视图的unit_price
                'category' => $row['category_name'], // 视图的category_name
                'stock' => $row['available_stock'], // 视图的available_stock
                'stock_status' => $row['stock_status'], // 视图的stock_status
                'store_id' => $row['store_id'], // 门店ID（关键）
                'branch' => $row['store_name'] ?? '无' // 视图的branch_name（如果有）
            ];
        }
    }
    
    if (isset($stmt)) $stmt->close();
    $conn->close();
    return $products;
}

function getProductsByCategoryAndSearch($category = null, $search = null) {
    $conn = getDBConnection();
    // 关键：WHERE 1=1 拼接条件，字段名全部使用视图的实际名称
    $query = "SELECT * FROM product_catalog_view WHERE 1=1";
    $params = [];
    $types = "";
    
    // 分类筛选：使用category_name
    if ($category && $category !== 'all') {
        $query .= " AND category_name = ?"; // 修复字段名
        $params[] = $category;
        $types .= "s";
    }
    
    // 搜索筛选：使用product_name
    if ($search) {
        $query .= " AND product_name LIKE ?"; // 视图的product_name
        $params[] = "%{$search}%";
        $types .= "s";
    }
    
    $query .= " ORDER BY product_name";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($query);
    }
    
    $products = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = [
                'id' => $row['product_ID'],
                'name' => $row['product_name'],
                'description' => $row['description'],
                'price' => $row['unit_price'],
                'category' => $row['category_name'],
                'stock' => $row['available_stock'], // 视图的可用库存字段
                'stock_status' => $row['stock_status'],
                //'store_id' => $row['store_id'], // 门店ID（关键）
                'branch' => $row['store_name'] ?? '无'
            ];
        }
    }
    
    if (isset($stmt)) $stmt->close();
    $conn->close();
    return $products;
}

// 4. 获取单个商品详情（可选，用于弹窗）
function getProductDetails($productId) {
    $conn = getDBConnection();
    $query = "SELECT * FROM product_branch_view WHERE product_ID = ?"; // 视图的product_ID
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $product = null;
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $product = [
            'id' => $row['product_ID'],
            'name' => $row['product_name'],
            'description' => $row['description'],
            'price' => $row['unit_price'],
            'category' => $row['category_name'],
            'stock' => $row['available_stock_in_store'],
            'stock_status' => $row['stock_status_in_store'],
            'store_id' => $row['store_id'], 
            'branch' => $row['store_name'] ?? '无'
        ];
    }
    
    $stmt->close();
    $conn->close();
    return $product;
}

function getProductBranches($productId) {
    $conn = getDBConnection();
    
    $query = "SELECT 
                store_id, 
                store_name, 
                available_stock_in_store,
                total_stock_in_store,
                stock_status_in_store,
                store_address,
                store_phone,
                batch_count
              FROM product_branch_view 
              WHERE product_ID = ? 
                AND store_status = 'active'
                AND available_stock_in_store > 0  -- 只显示有库存的门店
              ORDER BY store_name";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();

    $branches = [];
    while ($row = $result->fetch_assoc()) {
        $branches[] = [
            'id' => $row['store_id'],
            'name' => $row['store_name'],
            'available_stock' => $row['available_stock_in_store'] ?? 0,
            'total_stock' => $row['total_stock_in_store'] ?? 0,
            'stock_status' => $row['stock_status_in_store'] ?? '有货',
            'address' => $row['store_address'] ?? '',
            'phone' => $row['store_phone'] ?? '',
            'batch_count' => $row['batch_count'] ?? 0
        ];
    }

    $stmt->close();
    $conn->close();
    return $branches;
}
/**
 * 获取用户最喜欢的产品（消费金额最高）
 * @param int $customerId 客户ID
 * @return array 产品列表
 */
function getFavoriteProducts($customerId) {
    $conn = getDBConnection();
    $query = "SELECT product_ID, product_name, total_spent 
              FROM v_favorite_products 
              WHERE customer_ID = ?
              ORDER BY total_spent DESC";  // 保持排序
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    $stmt->close();
    $conn->close();
    return $products;
}

/**
 * 获取购物车商品数量
 * @param int $customerId 客户ID
 * @return int 商品总数
 */
function getCartItemCount($customerId) {
    $conn = getDBConnection();
    $query = "SELECT SUM(quantity) AS total 
              FROM OrderItem oi
              JOIN CustomerOrder co ON oi.order_ID = co.order_ID
              WHERE co.customer_ID = ? AND co.status = 'Pending'";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $count = 0;
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $count = $row['total'] ?? 0;
    }
    
    $stmt->close();
    $conn->close();
    return $count;
}
function getRecentOrders($customerId) {
    $conn = getDBConnection();
    $query = "SELECT * FROM v_order_history 
              WHERE customer_ID = ? 
              ORDER BY order_date DESC 
              LIMIT 3";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
    
    $stmt->close();
    $conn->close();
    return $orders;
}
function getRandomProducts() {
    $conn = getDBConnection();
    $query = "SELECT * FROM product_catalog_view 
              WHERE available_stock > 0 
              AND product_status = 'active'
              ORDER BY RAND() 
              LIMIT 3";
    
    $result = $conn->query($query);
    $products = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    $conn->close();
    return $products;
}

function updateCustomerPassword($customerId, $newPassword) {
    $conn = getDBConnection();
    
    if (!$conn) {
        error_log("数据库连接失败");
        return false;
    }
    
    $hashedPassword = md5($newPassword);
    
    try {
        // 使用JOIN查询，根据customer_ID找到对应的User记录
        $sql = "UPDATE User u 
                INNER JOIN Customer c ON u.user_name = c.user_name
                SET u.password_hash = ? 
                WHERE c.customer_ID = ?";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("准备查询失败: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("si", $hashedPassword, $customerId);
        
        if ($stmt->execute()) {
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            if ($affectedRows > 0) {
                error_log("成功更新customer_ID:{$customerId}的密码");
                return true;
            } else {
                error_log("未找到customer_ID为{$customerId}的对应记录");
                return false;
            }
        } else {
            error_log("执行更新失败: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
    } catch (Exception $e) {
        error_log("更新密码过程中发生异常: " . $e->getMessage());
        return false;
    }
}
function getProductStockInStore($productId, $storeId) {
    $conn = getDBConnection();
    
    $query = "SELECT 
                product_ID,
                product_name,
                store_id,
                store_name,
                total_stock_in_store,
                available_stock_in_store,
                stock_status_in_store,
                store_address,
                store_phone,
                batch_count,
                earliest_received_date
              FROM product_branch_view 
              WHERE product_ID = ? AND store_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $productId, $storeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $stockInfo = null;
    if ($result && $result->num_rows > 0) {
        $stockInfo = $result->fetch_assoc();
    }
    
    $stmt->close();
    $conn->close();
    return $stockInfo;
}

// 在 data.php 中添加这个函数
function removeItemFromCart($itemId, $customerId) {
    $conn = getDBConnection();
    $conn->begin_transaction();
    
    try {
        // 1. 先获取订单ID和商品信息
        $query = "SELECT oi.order_ID, oi.product_ID, si.batch_ID 
                 FROM OrderItem oi
                 LEFT JOIN StockItem si ON oi.item_ID = si.item_ID
                 WHERE oi.item_ID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        $itemInfo = $result->fetch_assoc();
        $stmt->close();
        
        if (!$itemInfo) {
            throw new Exception("商品不存在");
        }
        
        $orderId = $itemInfo['order_ID'];
        $productId = $itemInfo['product_ID'];
        $batchId = $itemInfo['batch_ID'];
        
        // 2. 验证订单属于当前用户
        $checkQuery = "SELECT customer_id FROM CustomerOrder WHERE order_ID = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("i", $orderId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $orderInfo = $checkResult->fetch_assoc();
        $checkStmt->close();
        
        if (!$orderInfo || $orderInfo['customer_id'] != $customerId) {
            throw new Exception("无权删除此商品");
        }
        
        // 3. 解锁库存（如果有批次信息）
        if ($batchId) {
            $unlockQuery = "UPDATE Inventory 
                           SET locked_inventory = locked_inventory - 1 
                           WHERE batch_ID = ? AND product_ID = ?";
            $unlockStmt = $conn->prepare($unlockQuery);
            $unlockStmt->bind_param("si", $batchId, $productId);
            $unlockStmt->execute();
            $unlockStmt->close();
        }
        
        // 4. 恢复StockItem状态
        $updateStockQuery = "UPDATE StockItem 
                           SET status = 'in_stock', customer_order_ID = NULL 
                           WHERE item_ID = ?";
        $updateStmt = $conn->prepare($updateStockQuery);
        $updateStmt->bind_param("s", $itemId);
        $updateStmt->execute();
        $updateStmt->close();
        
        // 5. 删除OrderItem
        $deleteItemQuery = "DELETE FROM OrderItem WHERE item_ID = ?";
        $deleteStmt = $conn->prepare($deleteItemQuery);
        $deleteStmt->bind_param("s", $itemId);
        $deleteStmt->execute();
        $deleteStmt->close();
        
        // 6. 检查订单是否还有商品
        $checkItemsQuery = "SELECT COUNT(*) as item_count FROM OrderItem WHERE order_ID = ?";
        $checkItemsStmt = $conn->prepare($checkItemsQuery);
        $checkItemsStmt->bind_param("i", $orderId);
        $checkItemsStmt->execute();
        $checkItemsResult = $checkItemsStmt->get_result();
        $itemCount = $checkItemsResult->fetch_assoc()['item_count'];
        $checkItemsStmt->close();
        
        // 7. 如果没有商品了，删除订单
        if ($itemCount == 0) {
            $deleteOrderQuery = "DELETE FROM CustomerOrder WHERE order_ID = ?";
            $deleteOrderStmt = $conn->prepare($deleteOrderQuery);
            $deleteOrderStmt->bind_param("i", $orderId);
            $deleteOrderStmt->execute();
            $deleteOrderStmt->close();
        } else {
            // 8. 更新订单总金额
            $updateOrderQuery = "UPDATE CustomerOrder 
                               SET total_amount = (
                                   SELECT COALESCE(SUM(unit_price * quantity), 0) 
                                   FROM OrderItem 
                                   WHERE order_ID = ?
                               ) 
                               WHERE order_ID = ?";
            $updateOrderStmt = $conn->prepare($updateOrderQuery);
            $updateOrderStmt->bind_param("ii", $orderId, $orderId);
            $updateOrderStmt->execute();
            $updateOrderStmt->close();
        }
        
        $conn->commit();
        return ['success' => true, 'message' => '商品删除成功'];
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    } finally {
        $conn->close();
    }
}
?>
