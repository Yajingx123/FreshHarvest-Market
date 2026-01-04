<?php
function getDBConnection() {
    $servername = "localhost";
    $username = "supplier_user";
    $password = "YourPassword123!"; // 统一为当前root密码
    $dbname = "mydb";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("数据库连接失败: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

function getCurrentSupplierId() {
    return isset($_SESSION['supplier_id']) ? (int)$_SESSION['supplier_id'] : 0;
}

/**
 * 获取供应商的采购订单列表
 */
function getSupplierPurchaseOrders($status = NULL, $branchId = 0, $supplierId = 0, $search = '') {
    $conn = getDBConnection();
    error_log("supplierId: {$supplierId} | branchId: {$branchId} | status: {$status} | search: {$search}");
    
    // 安全校验
    if (!$supplierId) {
        return [];
    }
    
    // 基础SQL语句
    $sql = "SELECT po.*, b.branch_name, b.address as branch_address 
            FROM PurchaseOrder po
            JOIN Branch b ON po.branch_ID = b.branch_ID
            WHERE po.supplier_ID = ?";
    
    // 参数数组
    $params = [$supplierId];
    $paramTypes = 'i'; // 初始参数类型（supplierId为整数）
    
    // 状态筛选
    if ($status !== null && $status !== '') {
        $sql .= " AND po.status = ?";
        $params[] = $status;
        $paramTypes .= 's';
    } 
    
    // 分店筛选
    if ($branchId > 0) {
        $sql .= " AND po.branch_ID = ?";
        $params[] = $branchId;
        $paramTypes .= 'i';
    } 
    
    // 搜索功能
    if ($search !== null && trim($search) !== '') {
        $sql .= " AND (po.purchase_order_ID LIKE ? OR b.branch_name LIKE ?)";
        $searchVal = '%' . trim($search) . '%';
        $params[] = $searchVal;
        $params[] = $searchVal;
        $paramTypes .= 'ss';
    } 
    
    // 排序
    $sql .= " ORDER BY po.date DESC";

    // 预处理执行
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("SQL准备失败: " . $conn->error);
        return [];
    }
    
    // 绑定参数
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    $stmt->close();
    return $orders;
}

/**
 * 获取单个采购订单详情
 */
function getPurchaseOrderDetail($orderId) {
    $conn = getDBConnection();
    $supplierId = getCurrentSupplierId();
    
    // 获取订单基本信息
    $stmt = $conn->prepare("
        SELECT po.*, b.branch_name, b.address as branch_address, b.phone as branch_phone
        FROM PurchaseOrder po
        JOIN Branch b ON po.branch_ID = b.branch_ID
        WHERE po.purchase_order_ID = ? AND po.supplier_ID = ?
    ");
    $stmt->bind_param("ii", $orderId, $supplierId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        return null;
    }
    
    // 获取订单商品
    $stmt = $conn->prepare("
        SELECT pi.*, p.product_name, p.sku, sp.price AS supplier_price
        FROM PurchaseItem pi
        JOIN products p ON pi.product_ID = p.product_ID
        LEFT JOIN SupplierProduct sp
            ON sp.product_ID = pi.product_ID AND sp.supplier_ID = ?
        WHERE pi.purchase_order_ID = ?
    ");
    $stmt->bind_param("ii", $supplierId, $orderId);
    $stmt->execute();
    $order['items'] = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $order['items'][] = $row;
    }
    $stmt->close();

    // 获取退回商品和退款金额
    $stmt = $conn->prepare("
        SELECT
            p.product_ID,
            p.product_name,
            p.sku,
            COUNT(*) AS return_qty,
            COALESCE(SUM(pi.unit_cost), 0) AS refund_amount
        FROM StockItemCertificate sc
        JOIN StockItem si ON sc.item_ID = si.item_ID
        JOIN PurchaseItem pi ON pi.item_ID = si.item_ID
        JOIN products p ON p.product_ID = pi.product_ID
        JOIN PurchaseOrder po ON po.purchase_order_ID = pi.purchase_order_ID
        WHERE sc.transaction_type = 'return'
          AND po.purchase_order_ID = ?
          AND po.supplier_ID = ?
        GROUP BY p.product_ID, p.product_name, p.sku
        ORDER BY p.product_name
    ");
    $stmt->bind_param("ii", $orderId, $supplierId);
    $stmt->execute();
    $order['returns'] = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $order['returns'][] = $row;
    }
    $stmt->close();

    $refundTotal = 0.0;
    foreach ($order['returns'] as $row) {
        $refundTotal += (float)$row['refund_amount'];
    }
    $orderTotal = isset($order['total_amount']) ? (float)$order['total_amount'] : 0.0;
    $order['refund_total'] = $refundTotal;
    $order['final_amount'] = max($orderTotal - $refundTotal, 0.0);
    
    return $order;
}

/**
 * 获取供应商可供产品清单（使用视图）
 */
function getSupplierProducts($supplierId) {
    $conn = getDBConnection();
    $products = [];
    if (!$supplierId) {
        return $products;
    }
    $stmt = $conn->prepare("SELECT product_ID, product_name, sku, description, price FROM v_supplier_products WHERE supplier_ID = ? ORDER BY product_name");
    if (!$stmt) {
        return $products;
    }
    $stmt->bind_param("i", $supplierId);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    $stmt->close();
    return $products;
}

function updateSupplierProductPrice($supplierId, $productId, $price) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE SupplierProduct SET price = ? WHERE supplier_ID = ? AND product_ID = ?");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("dii", $price, $supplierId, $productId);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * 更新采购订单状态
 */
function updatePurchaseOrderStatus($orderId, $status) {
    $conn = getDBConnection();
    $supplierId = getCurrentSupplierId();
    
    $stmt = $conn->prepare("
        UPDATE PurchaseOrder 
        SET status = ? 
        WHERE purchase_order_ID = ? AND supplier_ID = ?
    ");
    $stmt->bind_param("sii", $status, $orderId, $supplierId);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * 获取所有分店列表
 */
function getBranches() {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT branch_ID, branch_name, address, phone 
        FROM Branch 
        WHERE status = 'active' 
        ORDER BY branch_name
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $branches = [];
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }
    
    $stmt->close();
    return $branches;
}

/**
 * 获取订单状态统计
 */
function getOrderStatusSummary() {
    $conn = getDBConnection();
    $supplierId = getCurrentSupplierId();
    
    $stmt = $conn->prepare("
        SELECT status, COUNT(*) as count 
        FROM PurchaseOrder 
        WHERE supplier_ID = ? 
        GROUP BY status
    ");
    $stmt->bind_param("i", $supplierId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $summary = [];
    while ($row = $result->fetch_assoc()) {
        $summary[$row['status']] = $row['count'];
    }
    
    $stmt->close();
    return $summary;
}

/**
 * 格式化金额显示
 */
function formatAmount($amount) {
    return '¥' . number_format($amount, 2);
}
function getSupplierBranches($search = '', $area = '') {
    $conn = getDBConnection();
    $supplierId = getCurrentSupplierId();
    
    if (!$supplierId) {
        return [];
    }
    
    // 关联Staff表和User表获取联系人姓名
    $sql = "SELECT DISTINCT b.branch_ID, b.branch_name, b.address, b.phone, b.manager_phone, b.status,
                   b.manager_ID, 
                   CONCAT(u.first_name, ' ', u.last_name) as contact_person,  -- 组合姓名
                   COUNT(DISTINCT po.purchase_order_ID) as order_count,
                   SUM(po.total_amount) as total_amount
            FROM Branch b
            JOIN PurchaseOrder po ON b.branch_ID = po.branch_ID
            LEFT JOIN Staff s ON b.manager_ID = s.staff_ID  -- 通过manager_id关联Staff表
            LEFT JOIN User u ON s.user_name = u.user_name    -- 通过username关联User表
            WHERE po.supplier_ID = ?";
    
    $params = [$supplierId];
    $paramTypes = 'i';
    
    // 搜索筛选
    if (!empty(trim($search))) {
        $sql .= " AND (b.branch_ID LIKE ? OR b.branch_name LIKE ?)";
        $searchVal = '%' . trim($search) . '%';
        $params[] = $searchVal;
        $params[] = $searchVal;
        $paramTypes .= 'ss';
    }
    
    $sql .= " GROUP BY b.branch_ID 
              ORDER BY b.branch_name";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("SQL准备失败: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $branches = [];
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }
    
    $stmt->close();
    return $branches;
}

/**
 * 获取门店统计数据
 */
function getBranchStatistics() {
    $conn = getDBConnection();
    $supplierId = getCurrentSupplierId();
    
    if (!$supplierId) {
        return [
            'total_branches' => 0,
            'today_order_branches' => 0,
            'completion_rate' => 0
        ];
    }
    
    // 总合作门店数
    $totalStmt = $conn->prepare("
        SELECT COUNT(DISTINCT branch_ID) as count 
        FROM PurchaseOrder 
        WHERE supplier_ID = ?
    ");
    $totalStmt->bind_param("i", $supplierId);
    $totalStmt->execute();
    $totalResult = $totalStmt->get_result();
    $totalBranches = $totalResult->fetch_assoc()['count'] ?? 0;
    $totalStmt->close();
    
    // 今日有订单的门店数
    $today = date('Y-m-d');
    $todayStmt = $conn->prepare("
        SELECT COUNT(DISTINCT branch_ID) as count 
        FROM PurchaseOrder 
        WHERE supplier_ID = ? AND DATE(date) = ?
    ");
    $todayStmt->bind_param("is", $supplierId, $today);
    $todayStmt->execute();
    $todayResult = $todayStmt->get_result();
    $todayBranches = $todayResult->fetch_assoc()['count'] ?? 0;
    $todayStmt->close();
    
    // 订单完成率
    $completedStmt = $conn->prepare("
        SELECT 
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            COUNT(*) as total
        FROM PurchaseOrder 
        WHERE supplier_ID = ?
    ");
    $completedStmt->bind_param("i", $supplierId);
    $completedStmt->execute();
    $completedResult = $completedStmt->get_result();
    $completionData = $completedResult->fetch_assoc();
    $completionRate = 0;
    if ($completionData['total'] > 0) {
        $completionRate = round(($completionData['completed'] / $completionData['total']) * 100);
    }
    $completedStmt->close();
    
    return [
        'total_branches' => $totalBranches,
        'today_order_branches' => $todayBranches,
        'completion_rate' => $completionRate
    ];
}

/**
 * 获取门店详情
 */
function getBranchDetail($branchId) {
    $conn = getDBConnection();
    $supplierId = getCurrentSupplierId();
    
    // 验证该门店是否与供应商有合作
    $checkStmt = $conn->prepare("
        SELECT 1 FROM PurchaseOrder 
        WHERE branch_ID = ? AND supplier_ID = ? 
        LIMIT 1
    ");
    $checkStmt->bind_param("ii", $branchId, $supplierId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult->num_rows === 0) {
        return null;
    }
    $checkStmt->close();
    
    // 获取门店详情（关联Staff和User表）
    $stmt = $conn->prepare("
        SELECT b.*, 
               CONCAT(u.first_name, ' ', u.last_name) as contact_person,  -- 组合姓名
               (SELECT COUNT(*) FROM PurchaseOrder 
                WHERE branch_ID = b.branch_ID AND supplier_ID = ?) as total_orders,
               (SELECT COUNT(*) FROM PurchaseOrder 
                WHERE branch_ID = b.branch_ID AND supplier_ID = ? AND DATE(date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as monthly_orders,
               (SELECT SUM(total_amount) FROM PurchaseOrder 
                WHERE branch_ID = b.branch_ID AND supplier_ID = ? AND DATE(date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as monthly_amount,
               (SELECT MIN(date) FROM PurchaseOrder 
                WHERE branch_ID = b.branch_ID AND supplier_ID = ?) as first_cooperation_date
        FROM Branch b 
        LEFT JOIN Staff s ON b.manager_id = s.staff_id  -- 关联Staff表
        LEFT JOIN User u ON s.user_name = u.user_name    -- 关联User表
        WHERE b.branch_ID = ?
    ");
    $stmt->bind_param("iiiii", $supplierId, $supplierId, $supplierId, $supplierId, $branchId);
    $stmt->execute();
    $result = $stmt->get_result();
    $branch = $result->fetch_assoc();
    $stmt->close();
    
    return $branch;
}

/**
 * 获取供应商信息
 */
function getSupplierInfo($username) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT u.user_ID, u.user_name, u.first_name, u.last_name, 
                            u.user_email, u.user_telephone, u.created_at, u.last_login,
                            s.supplier_ID, s.user_name, s.address, s.company_name,s.contact_person, 
                            s.status 
                            FROM User u
                            LEFT JOIN Supplier s ON u.user_name = s.user_name
                            WHERE u.user_name = ? AND u.user_type = 'supplier'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = $result->fetch_assoc() ?: false;
    $stmt->close();
    return $data;
}

/**
 * 获取待处理订单数量
 */
function getPendingOrderCount($supplierId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM PurchaseOrder 
                            WHERE supplier_ID = ? AND status = 'pending'");
    $stmt->bind_param("i", $supplierId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['count'] ?? 0;
}

/**
 * 获取供应商能提供的产品总数
 * @param int $supplierId 供应商ID
 * @return int 产品数量
 */
function getSupplierProductCount() {
    $conn = getDBConnection();
    $supplierId = getCurrentSupplierId();
    $query = "SELECT COUNT(DISTINCT product_ID) as product_count 
              FROM SupplierProduct 
              WHERE supplier_ID = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $supplierId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $row['product_count'] ?? 0;
}

function updateSupplierPassword($username, $newPassword) {
    $conn = getDBConnection();
    
    if (!$conn) {
        error_log("数据库连接失败");
        return false;
    }
    
    // 使用MD5加密（与其他页面保持一致）
    $hashedPassword = md5($newPassword);
    
    try {
        // 更新User表中的密码
        $sql = "UPDATE User SET password_hash = ? WHERE user_name = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("准备查询失败: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("ss", $hashedPassword, $username);
        
        if ($stmt->execute()) {
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            if ($affectedRows > 0) {
                error_log("成功更新供应商{$username}的密码");
                return true;
            } else {
                error_log("未找到用户名为{$username}的用户");
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

function updateSupplierInfo($username, $updateData) {
    try {
        $conn = getDBConnection();
        
        if ($conn->connect_error) {
            throw new Exception("数据库连接失败: " . $conn->connect_error);
        }
        
        // 开始事务
        $conn->autocommit(false);
        
        $success = true;
        
        // 1. 准备更新 User 表的数据
        $userUpdateData = [];
        if (isset($updateData['first_name'])) {
            $userUpdateData['first_name'] = $updateData['first_name'];
        }
        if (isset($updateData['last_name'])) {
            $userUpdateData['last_name'] = $updateData['last_name'];
        }
        if (isset($updateData['user_email'])) {
            $userUpdateData['user_email'] = $updateData['user_email'];
        }
        if (isset($updateData['user_telephone'])) {
            $userUpdateData['user_telephone'] = $updateData['user_telephone'];
        }
        
        // 2. 更新 User 表
        if (!empty($userUpdateData)) {
            $userSetParts = [];
            $userParams = [];
            $userTypes = '';
            
            foreach ($userUpdateData as $field => $value) {
                $userSetParts[] = "{$field} = ?";
                $userParams[] = $value;
                $userTypes .= 's'; // 所有字段都是字符串类型
            }
            
            // 添加用户名作为 WHERE 条件
            $userTypes .= 's';
            $userParams[] = $username;
            
            $userSql = "UPDATE User SET " . implode(', ', $userSetParts) . " WHERE user_name = ?";
            $userStmt = $conn->prepare($userSql);
            
            if ($userStmt === false) {
                throw new Exception("User表更新语句准备失败: " . $conn->error);
            }
            
            $userStmt->bind_param($userTypes, ...$userParams);
            if (!$userStmt->execute()) {
                $success = false;
                error_log("更新User表失败: " . $userStmt->error);
            }
            $userStmt->close();
        }
        
        // 3. 准备更新 Supplier 表的数据
        $supplierUpdateData = [];
        if (isset($updateData['contact_person'])) {
            $supplierUpdateData['contact_person'] = $updateData['contact_person'];
        }
        if (isset($updateData['address'])) {
            $supplierUpdateData['address'] = $updateData['address'];
        }
        
        // 4. 更新 Supplier 表
        if (!empty($supplierUpdateData)) {
            $supplierSetParts = [];
            $supplierParams = [];
            $supplierTypes = '';
            
            foreach ($supplierUpdateData as $field => $value) {
                $supplierSetParts[] = "{$field} = ?";
                $supplierParams[] = $value;
                $supplierTypes .= 's';
            }
            
            // 添加用户名作为 WHERE 条件
            $supplierTypes .= 's';
            $supplierParams[] = $username;
            
            $supplierSql = "UPDATE Supplier SET " . implode(', ', $supplierSetParts) . " WHERE user_name = ?";
            $supplierStmt = $conn->prepare($supplierSql);
            
            if ($supplierStmt === false) {
                throw new Exception("Supplier表更新语句准备失败: " . $conn->error);
            }
            
            $supplierStmt->bind_param($supplierTypes, ...$supplierParams);
            if (!$supplierStmt->execute()) {
                $success = false;
                error_log("更新Supplier表失败: " . $supplierStmt->error);
            }
            $supplierStmt->close();
        }
        
        // 5. 根据结果提交或回滚事务
        if ($success) {
            $conn->commit();
            $conn->close();
            return true;
        } else {
            $conn->rollback();
            $conn->close();
            return false;
        }
        
    } catch (Exception $e) {
        // 发生异常时回滚事务
        if (isset($conn) && $conn->autocommit === false) {
            $conn->rollback();
        }
        error_log("更新供应商信息失败: " . $e->getMessage());
        return false;
    }
}
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    logout();
}

// 注意：移除了全局的 $conn->close()，由各函数内部在使用后关闭语句
// 连接会在脚本结束时自动关闭，避免提前关闭导致后续调用失败
?>
