<?php
require_once __DIR__ . '/../../config/db_connect.php';

// 建立数据库连接
try {
    $pdo = getPDOConnection();
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

/**
 * 获取当前登录供应商的ID
 * 实际应用中应从登录状态获取
 */
function getCurrentSupplierId() {
    return isset($_SESSION['supplier_id']) ? (int)$_SESSION['supplier_id'] : 0;
}

/**
 * 获取供应商的所有采购订单
 * @param string $status 订单状态筛选
 * @param int $branchId 分店ID筛选
 * @return array 订单列表
 */
function getSupplierPurchaseOrders($status = NULL, $branchId = 0, $search = '') {
    global $pdo;
    // 确保获取当前供应商ID的函数存在且能正确返回值
    $supplierId = getCurrentSupplierId();
    
    // 安全校验：如果没有获取到供应商ID，直接返回空数组
    if (!$supplierId) {
        return [];
    }

    // 基础SQL语句（使用命名参数，避免位置参数混用问题）
    $sql = "SELECT po.*, b.branch_name, b.address as branch_address 
            FROM PurchaseOrder po
            JOIN Branch b ON po.branch_ID = b.branch_ID
            WHERE po.supplier_ID = :supplier_id";
    
    // 初始化参数数组（统一使用命名参数，避免混淆）
    $params = [':supplier_id' => $supplierId];
    
    // 1. 状态筛选：仅当status不为空时添加筛选条件
    if ($status !== null && $status !== '') {
        $sql .= " AND po.status = :status";
        $params[':status'] = $status;
    }
    
    // 2. 分店筛选：仅当branchId>0时添加筛选条件
    if ($branchId > 0) {
        $sql .= " AND po.branch_ID = :branch_id";
        $params[':branch_id'] = $branchId;
    }
    
    // 3. 搜索功能：支持订单ID/分店名称搜索（可选，和前端搜索框对应）
    if (!empty(trim($search))) {
        $sql .= " AND (po.purchase_order_ID LIKE :search OR b.branch_name LIKE :search)";
        $params[':search'] = '%' . trim($search) . '%';
    }
    
    // 按订单日期倒序排列（最新订单在前）
    $sql .= " ORDER BY po.date DESC";

    try {
        // 预处理并执行SQL
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // 记录错误日志（生产环境），调试时可打印错误
        error_log("获取供应商订单失败: " . $e->getMessage());
        // 调试模式下可取消注释查看错误
        // echo "SQL错误: " . $e->getMessage();
        return [];
    }
}

/**
 * 获取单个采购订单详情
 * @param int $orderId 采购订单ID
 * @return array 订单详情
 */
function getPurchaseOrderDetail($orderId) {
    global $pdo;
    $supplierId = getCurrentSupplierId();
    
    // 获取订单基本信息
    $stmt = $pdo->prepare("
        SELECT po.*, b.branch_name, b.address as branch_address, b.phone as branch_phone
        FROM PurchaseOrder po
        JOIN Branch b ON po.branch_ID = b.branch_ID
        WHERE po.purchase_order_ID = :order_id AND po.supplier_ID = :supplier_id
    ");
    $stmt->execute([
        ':order_id' => $orderId,
        ':supplier_id' => $supplierId
    ]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        return null;
    }
    
    // 获取订单商品
    $stmt = $pdo->prepare("
        SELECT pi.*, p.product_name, p.sku 
        FROM PurchaseItem pi
        JOIN products p ON pi.product_ID = p.product_ID
        WHERE pi.purchase_order_ID = :order_id
    ");
    $stmt->execute([':order_id' => $orderId]);
    $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $order;
}

/**
 * 更新采购订单状态
 * @param int $orderId 订单ID
 * @param string $status 新状态
 * @return bool 操作结果
 */
function updatePurchaseOrderStatus($orderId, $status) {
    global $pdo;
    $supplierId = getCurrentSupplierId();
    
    try {
        $stmt = $pdo->prepare("
            UPDATE PurchaseOrder 
            SET status = :status 
            WHERE purchase_order_ID = :order_id AND supplier_ID = :supplier_id
        ");
        return $stmt->execute([
            ':status' => $status,
            ':order_id' => $orderId,
            ':supplier_id' => $supplierId
        ]);
    } catch(PDOException $e) {
        error_log("更新采购订单状态失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 获取所有分店列表（供供应商选择）
 * @return array 分店列表
 */
function getBranches() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT branch_ID, branch_name, address, phone 
        FROM Branch 
        WHERE status = 'active' 
        ORDER BY branch_name
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 获取订单状态统计
 * @return array 状态统计数据
 */
function getOrderStatusSummary() {
    global $pdo;
    $supplierId = getCurrentSupplierId();
    
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as count 
        FROM PurchaseOrder 
        WHERE supplier_ID = :supplier_id 
        GROUP BY status
    ");
    $stmt->execute([':supplier_id' => $supplierId]);
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

/**
 * 格式化金额显示
 * @param float $amount 金额
 * @return string 格式化后的金额
 */
function formatAmount($amount) {
    return '¥' . number_format($amount, 2);
}
