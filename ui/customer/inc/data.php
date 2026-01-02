<?php

function getDBConnection() {
    $servername = "localhost";
    $username = "root";
    $password = "8049023544Aaa?"; // 你的密码
    $dbname = "mydb";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("数据库连接失败: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// 获取当前登录顾客的ID
$customer_id = $_SESSION['customer_id'] ?? null;

// 顾客信息视图
function getCustomerFullInfo() { 
    // 从 session 中获取当前登录用户的 customer_id（与 cart.php 中逻辑保持一致）
    if (!isset($_SESSION['customer_id'])) {
        return null; // 未登录或未存储 customer_id，返回空
    }
    $current_customer_id = $_SESSION['customer_id'];
    
    $conn = getDBConnection();
    
    // 使用 customer_ID 筛选（视图中已包含该字段）
    $query = "SELECT * FROM v_customer_profile WHERE customer_ID = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $current_customer_id); // 绑定整数类型的 customer_ID
    $stmt->execute();
    $result = $stmt->get_result();
    
    $customer_info = null;
    if ($result && $result->num_rows > 0) {
        $customer_info = $result->fetch_assoc();

        if (isset($customer_info['first_name']) && isset($customer_info['last_name'])) {
            $customer_info['full_name'] = $customer_info['first_name'] . ' ' . $customer_info['last_name'];
        }
        // 字段映射（视图的 phone 对应代码中的 customer_phone）
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
    
    // 查询顾客等级
    $customer_query = "SELECT loyalty_level FROM v_customer_profile WHERE customer_id = ?";
    $stmt = $conn->prepare($customer_query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer_data = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    // 计算折扣率
    if ($customer_data) {
        switch ($customer_data['loyalty_level']) {
            case 'VIP': $discount_rate = 0.05; break;
            case 'VVIP': $discount_rate = 0.10; break;
        }
    }
    return $discount_rate;
}
/**
 * 计算订单最终金额（含折扣）
 * @param int $customer_id 顾客ID
 * @param float $total_amount 原始总金额
 * @return float 折扣后最终金额
 */
function calculateFinalAmount($customer_id, $total_amount) {
    $conn = getDBConnection();
    $discount_rate = getDiscountRate($customer_id);
    return $total_amount * (1 - $discount_rate);
}

function getShoppingCartData($customer_id) {
    $conn = getDBConnection();
    $cart_id = null; // 优先初始化cart_id
    $discount=getDiscountRate($customer_id);
    $cart_query = "SELECT order_id FROM CustomerOrder WHERE customer_id = ? AND status = 'Pending' LIMIT 1";
    $cart_stmt = $conn->prepare($cart_query);
    $cart_stmt->bind_param("i", $customer_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    if ($cart_result && $cart_result->num_rows > 0) {
        $cart_row = $cart_result->fetch_assoc();
        $cart_id = $cart_row['order_id']; // 这里用小写order_id，匹配数据库字段
    }
    $cart_stmt->close();

    // 1. 利用视图中的customer_ID筛选商品
    $query = "SELECT 
                v.*,
                si.item_ID AS stock_item_id,  -- StockItem的唯一ID（区分视图中的item_id）
                si.batch_ID,                  -- 库存批次ID（关联Inventory）
                si.status AS stock_status,    -- StockItem的状态（in_stock/pending/sold）
                si.received_date,             -- StockItem的入库日期
                si.expiry_date                -- StockItem的过期日期
              FROM v_wishlist_products v
              LEFT JOIN StockItem si ON v.item_id = si.item_ID AND v.product_id = si.product_ID AND v.branch_id = si.branch_id
              WHERE v.customer_id = ?";  
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customer_id); // 绑定customer_id，确保数据归属正确
    $stmt->execute(); 
    $result = $stmt->get_result();
    
    $cart_items = [];
    $cart_total = 0;
    $customer_name = null;
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // 计算单项总价
            $item_total = $row['unit_price'] * $row['quantity'];
            $row['item_total'] = $item_total;
            $cart_items[] = $row;
            $cart_total += $item_total;
            
            // 兼容视图中的order_ID/order_id字段（不管大小写都能拿到）
            if ($cart_id === null) {
                $cart_id = $row['order_ID'] ?? $row['order_id'] ?? null;
            }
        }
    }
    $cart_final_amount=calculateFinalAmount($customer_id, $cart_total);
    
    $stmt->close();
    $conn->close();
    
    // 3. 计算总数量（兼容视图中的quantity字段）
    $total_quantity = 0;
    foreach ($cart_items as $item) {
        $total_quantity += isset($item['quantity']) ? intval($item['quantity']) : 0;
    }

    return [
        'cart_id' => $cart_id,
        'customer_name' => $customer_name,
        'items' => $cart_items,
        'total_items' => count($cart_items), // 商品种类数
        'total_quantity' => $total_quantity, // 商品总数量
        'total_amount' => $cart_total,       // 商品总金额
        'finanl_Amount' => $cart_final_amount, //商品打折金额
        'discount'=>$discount,
        'has_items' => !empty($cart_items)
    ];
}


// 获取顾客订单数据 - 使用订单历史视图
/**
 * 获取订单详情（包含订单项明细）
 * @param int $orderId 订单ID
 * @return array 订单详情数组
 */
function getOrderDetails($orderId) {
    $conn = getDBConnection();
    
    // 查询订单主信息
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

    // 查询订单项明细
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

/**
 * 获取客户所有订单（基于v_order_history）
 * @param int $customerId 客户ID
 * @return array 订单列表
 */
function getCustomerOrders($customerId, $status = NULL) {
    if (!$customerId) return [];
    
    $conn = getDBConnection();
    
    // 构建基础查询
    $sql = "SELECT * FROM v_order_history WHERE customer_ID = ?";
    $params = [$customerId];
    $types = "i";
    
    // 添加状态筛选条件
    if ($status !== null && $status !== 'all') {
        $sql .= " AND order_status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    // 添加排序
    $sql .= " ORDER BY order_date DESC";
    
    // 准备查询
    $stmt = $conn->prepare($sql);
    
    // 绑定参数
    if (count($params) > 1) {
        // 如果有状态参数
        $stmt->bind_param($types, ...$params);
    } else {
        // 如果只有顾客ID参数
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
            'total_amount' => $row['final_amount'],  // 显示折扣后金额
            'product_details' => $row['product_details'],
            'store_name' => $row['store_name'],
            'shipping_address' => $row['shipping_address'] ?? '无'
        ];
    }
    
    $stmt->close();
    $conn->close();
    return $orders;
}

// 获取顾客个人信息（使用视图）
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

// 获取可浏览的商品目录
function getProductCatalog($branch_id = null, $category = null) {
    $conn = getDBConnection();
    
    $query = "SELECT * FROM v_customer_product_info WHERE 1=1";
    $params = [];
    $types = "";
    
    // 按分类筛选
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
            // 解析属性
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

/**
 * 获取用户的Pending订单（购物车），没有则创建
 * @param int $customerId 客户ID
 * @param int $branchId 门店ID（可根据需求调整，这里默认传1）
 * @return int 订单ID（order_ID）
 */
function getOrCreatePendingOrder($customerId, $branchId = 1) {
    $conn = getDBConnection();
    // 1. 查询是否有Pending状态的订单
    $query = "SELECT order_ID FROM CustomerOrder WHERE customer_id = ? AND status = 'Pending' LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();

    $orderId = null;
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $orderId = $row['order_ID'];
    } else {
        // 2. 没有则创建新订单
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

/**
 * 按FIFO原则获取StockItem的item_ID（先进先出：received_date升序）
 * @param int $productId 产品ID
 * @param int $quantity 购买数量
 * @param int $branchId 门店ID（可选，按门店筛选）
 */
function getFIFOStockItems($productId, $quantity, $branchId) {
    $conn = getDBConnection();
    $batches = [];
    
    // 获取当前时间
    $currentDate = date('Y-m-d');

    // 直接从Inventory表查询可用库存（按FIFO排序）
    $query = "SELECT batch_ID, (quantity_on_hand - locked_inventory) AS available_qty 
              FROM Inventory 
              WHERE product_ID = ? 
                AND branch_ID = ? 
                AND (quantity_on_hand - locked_inventory) > 0
                AND (date_expired IS NULL OR date_expired >= ?)  -- 过滤已过期产品
              ORDER BY received_date ASC, date_expired ASC"; // FIFO原则：先入库先出库

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

    // 库存不足直接返回空
    if ($totalAvailable < $quantity) {
        $conn->close();
        return [];
    }

    // 计算需要从每个批次锁定的数量（按FIFO分配）
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

/**
 * 加入购物车核心逻辑（按FEFO原则分配批次库存）
 * @param int $customerId 客户ID
 * @param int $productId 产品ID
 * @param int $quantity 购买数量
 * @param int $branchId 门店ID
 * @return array 结果：success（bool） + message（字符串）
 */
function addToCartWithFIFO($customerId, $productId, $quantity, $branchId = 1) {
    // 验证参数
    if ($customerId <= 0 || $productId <= 0 || $quantity < 1) {
        return ['success' => false, 'message' => '无效的参数'];
    }

    $conn = getDBConnection();
    $conn->begin_transaction(); // 开启事务

    try {
        // ========== 步骤1：验证门店和产品的关联性（使用视图） ==========
        $storeCheckQuery = "SELECT store_id, store_name, available_stock_in_store 
                           FROM product_branch_view 
                           WHERE product_ID = ? AND store_id = ?";
        $storeStmt = $conn->prepare($storeCheckQuery);
        $storeStmt->bind_param("ii", $productId, $branchId);
        $storeStmt->execute();
        $storeResult = $storeStmt->get_result();
        
        if ($storeResult->num_rows === 0) {
            throw new Exception("该产品在所选门店不存在");
        }
        
        $storeInfo = $storeResult->fetch_assoc();
        $availableStock = $storeInfo['available_stock_in_store'];
        
        if ($availableStock < $quantity) {
            throw new Exception("库存不足！所选门店可用库存为 {$availableStock} 件");
        }
        
        $storeStmt->close();

        // ========== 步骤2：获取或创建用户的Pending订单 ==========
        $orderId = getOrCreatePendingOrder($customerId, $branchId);
        if (!$orderId) {
            throw new Exception("无法创建或获取订单");
        }

        // ========== 步骤3：获取产品单价 ==========
        $priceQuery = "SELECT unit_price FROM products WHERE product_ID = ? AND status = 'active'";
        $priceStmt = $conn->prepare($priceQuery);
        $priceStmt->bind_param("i", $productId);
        $priceStmt->execute();
        $priceResult = $priceStmt->get_result();
        if ($priceResult->num_rows === 0) {
            throw new Exception("产品不存在或已下架");
        }
        $product = $priceResult->fetch_assoc();
        $unitPrice = $product['unit_price'];
        $priceStmt->close();

        // ========== 步骤4：获取该门店下该产品的可用批次（FEFO原则：先过期先出） ==========
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

        // ========== 步骤5：计算总可用库存并分配批次 ==========
        $totalAvailable = 0;
        $availableBatches = [];
        $batchDetails = []; // 存储批次详细信息
        
        while ($row = $batchesResult->fetch_assoc()) {
            $batchAvailable = $row['available_qty'];
            $totalAvailable += $batchAvailable;
            $availableBatches[] = $row;
            
            // 存储批次详细信息用于后续处理
            $batchDetails[$row['batch_ID']] = [
                'quantity_on_hand' => $row['quantity_on_hand'],
                'locked_inventory' => $row['locked_inventory'],
                'available_qty' => $batchAvailable,
                'date_expired' => $row['date_expired']
            ];
        }
        $batchesStmt->close();

        // 再次验证库存（基于实际批次库存）
        if ($totalAvailable < $quantity) {
            throw new Exception("实际批次库存不足，无法满足购买数量。可用: {$totalAvailable}, 需要: {$quantity}");
        }

        // ========== 步骤6：按FEFO原则分配批次库存 ==========
        $remaining = $quantity;
        $batchAllocations = [];
        $batchIds = []; // 用于记录哪些批次被分配了

        // 遍历所有可用批次，按FEFO顺序分配
        foreach ($availableBatches as $batch) {
            if ($remaining <= 0) break;
            
            $batchId = $batch['batch_ID'];
            $batchAvailable = $batch['available_qty'];
            
            // 计算这个批次能分配多少
            $take = min($remaining, $batchAvailable);
            
            if ($take > 0) {
                $batchAllocations[] = [
                    'batch_id' => $batchId,
                    'lock_qty' => $take,
                    'date_expired' => $batch['date_expired'],
                    'received_date' => $batch['received_date']
                ];
                $remaining -= $take;
                $batchIds[] = $batchId; // 记录被分配的批次ID
            }
        }

        // 记录分配详情（用于调试和日志）
        error_log("FEFO分配详情 - 产品ID: $productId, 门店ID: $branchId, 数量: $quantity");
        foreach ($batchAllocations as $alloc) {
            error_log("  -> 批次: {$alloc['batch_id']}, 分配数量: {$alloc['lock_qty']}, 过期时间: {$alloc['date_expired']}");
        }

        // ========== 步骤7：更新Inventory表的锁定库存 ==========
        foreach ($batchAllocations as $allocation) {
            $batchId = $allocation['batch_id'];
            $need = $allocation['lock_qty'];
            
            // 先检查当前实际可用库存是否足够
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
                throw new Exception("批次 {$batchId} 当前可用库存不足。当前: {$currentAvailable}, 需要: {$need}");
            }
            
            // 执行锁定更新
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
                throw new Exception("锁定批次 {$batchId} 库存失败");
            }
            $lockStmt->close();
        }

        // ========== 步骤8：处理StockItem记录 ==========
        $totalProcessed = 0;
        
        foreach ($batchAllocations as $allocation) {
            $batchId = $allocation['batch_id'];
            $need = $allocation['lock_qty'];
            
            // 查找该批次下可用的StockItem（状态为in_stock且未关联订单）
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
            
            // 情况1：找到足够的现有StockItem
            if ($foundCount >= $need) {
                for ($i = 0; $i < $need; $i++) {
                    $itemId = $foundItems[$i];
                    
                    $insertOrderItem = "INSERT INTO OrderItem 
                   (order_ID, item_ID, unit_price, product_ID, quantity, status)
                   VALUES (?, ?, ?, ?, 1, 'pending')";

                   $orderItemStmt = $conn->prepare($insertOrderItem);
                   $orderItemStmt->bind_param("issd", $orderId, $itemId, $unitPrice, $productId);
                   $orderItemStmt->execute();
                    
                    // 更新StockItem状态
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
            // 情况2：现有StockItem不足，需要创建新的
            else {
                // 先处理找到的现有StockItem
                foreach ($foundItems as $itemId) {
                    // 插入OrderItem
                    $insertOrderItem = "INSERT INTO OrderItem 
                                       (order_ID, item_ID, unit_price, product_ID, quantity, status)
                                       VALUES (?, ?, ?, ?, 1, 'pending')";
                    
                    $orderItemStmt = $conn->prepare($insertOrderItem);
                    $orderItemStmt->bind_param("issd", $orderId, $itemId, $unitPrice, $productId);
                    $orderItemStmt->execute();
                    $orderItemStmt->close();
                    
                    // 更新StockItem状态
                    $updateStock = "UPDATE StockItem 
                                   SET status = 'pending', customer_order_ID = ? 
                                   WHERE item_ID = ?";
                    
                    $updateStmt = $conn->prepare($updateStock);
                    $updateStmt->bind_param("is", $orderId, $itemId);
                    $updateStmt->execute();
                    $updateStmt->close();
                    
                    $totalProcessed++;
                    $need--; // 减少还需要创建的数量
                }
                
                // 创建新的StockItem记录（用于剩余的分配数量）
                for ($i = 0; $i < $need; $i++) {
                    $newItemId = uniqid('SI_', true); // 生成唯一的item_ID
                    
                    // 获取批次的过期时间
                    $expiryDate = $allocation['date_expired'] ?: NULL;
                    
                    $createStockItem = "INSERT INTO StockItem 
                                       (item_ID, batch_ID, product_ID, branch_ID, status, 
                                        received_date, expiry_date, customer_order_ID)
                                       VALUES (?, ?, ?, ?, 'pending', NOW(), ?, ?)";
                    
                    $createStmt = $conn->prepare($createStockItem);
                    $createStmt->bind_param("ssiiss", $newItemId, $batchId, $productId, $branchId, $expiryDate, $orderId);
                    $createStmt->execute();
                    $createStmt->close();
                    
                    // 插入对应的OrderItem
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

        // ========== 步骤9：验证处理数量 ==========
        if ($totalProcessed !== $quantity) {
            throw new Exception("库存分配异常，实际处理数量({$totalProcessed})与需求数量({$quantity})不匹配");
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
        
        error_log("购物车添加成功 - 产品ID: $productId, 门店ID: $branchId, 数量: $quantity, 订单ID: $orderId");
        
        return [
            'success' => true, 
            'message' => '商品已成功加入购物车', 
            'order_id' => $orderId,
            'processed_qty' => $totalProcessed,
            'allocated_batches' => $batchIds // 返回分配的批次ID用于调试
        ];
        
    } catch (Exception $e) {
        // 回滚事务
        if (isset($conn) && $conn) {
            $conn->rollback();
            $conn->close();
        }
        
        error_log("加入购物车失败 - 产品ID: $productId, 门店ID: $branchId, 数量: $quantity, 错误: " . $e->getMessage());
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
            throw new Exception("未找到关联的用户信息,customer_ID:{$customerId}");
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
        // 回滚事务，记录错误
        $conn->rollback();
        $success = false;
        error_log("用户{$customerId}信息更新失败：" . $e->getMessage());
        // 临时打印错误（生产环境删除）
        // var_dump("错误信息：", $e->getMessage());
    }

    $conn->close();
    return $success;
}

// data.php 新增和修改部分
function getProductCategories() {
    $conn = getDBConnection();
    // 关键修改：将Category改为category_name，与视图字段一致
    $query = "SELECT DISTINCT category_name AS category_name, COUNT(*) AS product_count 
             FROM product_catalog_view 
             GROUP BY category_name 
             ORDER BY category_name";
    
    $result = $conn->query($query);
    $categories = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = [
                'name' => $row['category_name'], // 对应视图的category_name
                'count' => $row['product_count']
            ];
        }
    }
    
    $conn->close();
    return $categories;
}

// 2. 按分类获取商品（旧函数，可保留但建议用新的搜索函数）
function getProductsByCategory($category = null) {
    $conn = getDBConnection();
    // 关键修改：WHERE条件使用category_name，而非Category
    $query = "SELECT * FROM product_catalog_view";
    $params = [];
    $types = "";
    
    if ($category && $category !== 'all') {
        $query .= " WHERE category_name = ?"; // 修复字段名
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

/**
 * 获取最近3条订单
 * @param int $customerId 客户ID
 * @return array 订单列表
 */
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

/**
 * 获取3个随机产品
 * @return array 产品列表
 */
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
?>