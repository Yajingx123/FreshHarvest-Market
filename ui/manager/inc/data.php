<?php
require_once __DIR__ . '/db_connect.php';
// 获取产品数据（使用v_products_list视图）
function getProductsViewData() {
    global $conn;
    $products = [];
    try {
        $sql = "SELECT * FROM v_products_list";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // 翻译状态并处理库存预警
                $statusText = '';
                $stock = $row['stock'];
                if ($stock <= 0) {
                    $statusText = '已下架';
                } elseif ($stock < 10) { 
                    $statusText = '库存预警';
                } else {
                    $statusText = '已上架';
                }
                
                $products[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'sku' => $row['sku'],
                    'description' => $row['description'],
                    'spec' => $row['parent_category'] ?? '', // 用父分类作为规格补充
                    'unit' => $row['unit'],
                    'price' => '¥' . number_format($row['price'], 2),
                    'category' => $row['category'],
                    'status' => $row['status'],
                    'statusText' => $statusText,
                    'stock' => $stock,
                    'image' => '', // 保持原结构
                ];
            }
        }
    } catch (Exception $e) {
        error_log("获取产品数据失败: " . $e->getMessage());
    }
    return $products;
}

// 获取货物流水数据（使用v_transactions视图）
function getTransactionsData() {
    global $conn;
    $transactions = [];
    try {
        $sql = "SELECT * FROM v_transactions";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $transactions[] = [
                    'id' => $row['id'],
                    'time' => $row['time'],
                    'productId' => $row['productId'],
                    'productName' => $row['productName'],
                    'batchId' => $row['batchId'],
                    'type' => $row['direction'],
                    'qty' => $row['qty'],
                    'unit' => $row['unit'],
                    'note' => $row['note'],
                ];
            }
        }
    } catch (Exception $e) {
        error_log("获取流水数据失败: " . $e->getMessage());
    }
    return $transactions;
}

// 获取员工数据（使用v_employees视图）
function getEmployeesData() {
    global $conn;
    $employees = [];
    try {
        $sql = "SELECT * FROM v_employees";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // 翻译状态
                $statusText = '';
                switch ($row['status_raw'] ?? '') {
                    case 'active': $statusText = '在职'; break;
                    case 'on_leave': $statusText = '休假'; break;
                    case 'terminated': $statusText = '离职'; break;
                    default: $statusText = $row['status_raw'] ?? '未知';
                }
                
                $employees[] = [
                    'id' => $row['id'] ?? '',
                    'name' => $row['name'] ?? '',
                    'role' => $row['role'] ?? '',
                    'salary' => floatval($row['salary'] ?? 0),
                    'start_date' => $row['start_date'] ?? '',
                    'status' => $statusText,
                    'email' => $row['email'] ?? '',
                    'phone' => $row['phone'] ?? $row['staff_phone'] ?? '',
                    'branch_name' => $row['branch_name'] ?? '未分配',
                    'branch_id' => $row['branch_id'] ?? 0,
                    'username' => $row['username'] ?? '',
                    'created_at' => $row['created_at'] ?? '',
                    'hire_date' => $row['start_date'] ?? '',
                    'status_raw' => $row['status_raw'] ?? '',
                ];
            }
        } else {
            error_log("员工数据查询结果为空");
        }
    } catch (Exception $e) {
        error_log("获取员工数据失败: " . $e->getMessage());
    }
    return $employees;
}

// 根据ID获取单个员工信息（复用v_employees视图）
function getEmployeeById($id) {
    global $conn;
    try {
        $sql = "SELECT * FROM v_employees WHERE id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // 翻译状态
            $statusText = '';
            switch ($row['status_raw'] ?? '') {
                case 'active': $statusText = '在职'; break;
                case 'on_leave': $statusText = '休假'; break;
                case 'terminated': $statusText = '离职'; break;
                default: $statusText = $row['status_raw'] ?? '未知';
            }
            
            // 从用户名获取姓和名
            $firstName = '';
            $lastName = '';
            
            // 从完整姓名中解析姓和名（假设格式为"姓 名"）
            if (!empty($row['name'])) {
                $nameParts = explode(' ', $row['name']);
                if (count($nameParts) >= 2) {
                    $firstName = $nameParts[0]; // 姓
                    $lastName = $nameParts[1];  // 名
                } else {
                    // 如果只有一个名字，全部当作姓
                    $firstName = $row['name'];
                    $lastName = '';
                }
            }
            
            return [
                'id' => $row['id'],
                'name' => $row['name'],
                'username' => $row['username'],
                'first_name' => $firstName,
                'last_name' => $lastName,
                'role' => $row['role'],
                'salary' => $row['salary'],
                'start_date' => date('Y-m-d', strtotime($row['start_date'])),
                'status' => $statusText,
                'status_raw' => $row['status_raw'],
                'email' => $row['email'],
                'phone' => $row['phone'] ?? $row['staff_phone'],
                'branch_name' => $row['branch_name'],
                'branch_id' => $row['branch_id'],
            ];
        }
        return null;
    } catch (Exception $e) {
        error_log("获取单个员工数据失败: " . $e->getMessage());
        return null;
    }
}
function getBranchesForSelect() {
    global $conn;
    $branches = [];
    
    try {
        $sql = "SELECT branch_ID as id, branch_name as name FROM Branch WHERE status = 'active' ORDER BY branch_name";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $branches[] = [
                    'id' => 'BR-' . $row['id'], // 添加前缀以便区分
                    'name' => $row['name'],
                    'branch_id' => $row['id'] // 原始ID
                ];
            }
        }
    } catch (Exception $e) {
        error_log("获取门店列表失败: " . $e->getMessage());
    }
    
    return $branches;
}
function saveEmployee($data) {
    global $conn;
    try {
        // 开始事务
        $conn->begin_transaction();
        
        // 从表单数据中获取用户名、姓、名
        $username = $data['username'] ?? '';
        $firstName = $data['first_name'] ?? '';
        $lastName = $data['last_name'] ?? '';
        
        if ($data['is_new']) {
            // ========== 新增员工 ==========
            if (empty($username)) {
                throw new Exception("用户名不能为空");
            }
            
            if (empty($firstName) || empty($lastName)) {
                throw new Exception("姓氏和名字不能为空");
            }
            
            // 1. 检查用户名是否已存在
            $checkSql = "SELECT user_name FROM User WHERE user_name = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("s", $username);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            if ($checkResult->num_rows > 0) {
                throw new Exception("用户名已存在，请选择其他用户名");
            }
            $checkStmt->close();
            
            // 2. 在 User 表中创建用户
            $userSql = "INSERT INTO User (user_name, password_hash, user_type, user_email, user_telephone, first_name, last_name, is_active) 
                       VALUES (?, ?, 'staff', ?, ?, ?, ?, TRUE)";
            $userStmt = $conn->prepare($userSql);
            
            // 使用 md5 加密密码（与你现有的登录逻辑保持一致）
            $passwordHash = md5('Test1234'); // 默认密码
            
            $userStmt->bind_param("ssssss", 
                $username,
                $passwordHash,
                $data['email'],
                $data['phone'],
                $firstName,
                $lastName
            );
            
            if (!$userStmt->execute()) {
                throw new Exception("创建用户失败: " . $userStmt->error);
            }
            $userId = $conn->insert_id;
            $userStmt->close();
            
            // 3. 在 Staff 表中创建员工记录
            $branchId = str_replace('BR-', '', $data['branch_id']);
            $statusRaw = '';
            switch ($data['status']) {
                case '在职': $statusRaw = 'active'; break;
                case '休假': $statusRaw = 'on_leave'; break;
                case '离职': $statusRaw = 'terminated'; break;
                default: $statusRaw = 'active';
            }
            
            $hireDate = $data['start_date'];
            
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hireDate)) {
                throw new Exception("无效的日期格式，请使用YYYY-MM-DD");
            }
            
            $dateParts = explode('-', $hireDate);
            $year = intval($dateParts[0]);
            $month = intval($dateParts[1]);
            $day = intval($dateParts[2]);
            
            if (!checkdate($month, $day, $year)) {
                throw new Exception("日期不存在，请检查年、月、日是否有效");
            }
            
            $staffSql = "INSERT INTO Staff (user_name, branch_ID, position, phone, salary, hire_date, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
            $staffStmt = $conn->prepare($staffSql);
            $staffStmt->bind_param("sissdss", 
                $username,
                $branchId,
                $data['role'],
                $data['phone'],
                $data['salary'],
                $hireDate,
                $statusRaw
            );
            
            if (!$staffStmt->execute()) {
                throw new Exception("创建员工记录失败: " . $staffStmt->error);
            }
            $staffId = $conn->insert_id;
            $staffStmt->close();
            
        } else {
            // ========== 更新员工信息 ==========
            // 1. 先获取原有的用户名（如果表单没有提供新的用户名，则使用原有的）
            $getUsernameSql = "SELECT user_name FROM Staff WHERE staff_ID = ?";
            $getUsernameStmt = $conn->prepare($getUsernameSql);
            $getUsernameStmt->bind_param("i", $data['id']);
            $getUsernameStmt->execute();
            $getUsernameResult = $getUsernameStmt->get_result();
            $existingEmployee = $getUsernameResult->fetch_assoc();
            $getUsernameStmt->close();
            
            $currentUsername = $existingEmployee['user_name'] ?? '';
            
            // 如果提供了新用户名且不同于原用户名，检查是否已存在
            if (!empty($username) && $username !== $currentUsername) {
                $checkSql = "SELECT user_name FROM User WHERE user_name = ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->bind_param("s", $username);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                if ($checkResult->num_rows > 0) {
                    throw new Exception("用户名已存在，请选择其他用户名");
                }
                $checkStmt->close();
            }
            
            // 2. 更新 User 表
            $userSql = "UPDATE User 
                       SET user_name = ?, user_email = ?, user_telephone = ?, first_name = ?, last_name = ?
                       WHERE user_name = ?";
            $userStmt = $conn->prepare($userSql);
            
            // 如果提供了新用户名，使用新用户名，否则保持原用户名
            $newUsername = !empty($username) ? $username : $currentUsername;
            
            $userStmt->bind_param("ssssss", 
                $newUsername,
                $data['email'],
                $data['phone'],
                $firstName,
                $lastName,
                $currentUsername  // WHERE条件：原来的用户名
            );
            
            if (!$userStmt->execute()) {
                throw new Exception("更新用户信息失败: " . $userStmt->error);
            }
            $userStmt->close();
            
            // 3. 更新 Staff 表
            $branchId = str_replace('BR-', '', $data['branch_id']);
            $statusRaw = '';
            switch ($data['status']) {
                case '在职': $statusRaw = 'active'; break;
                case '休假': $statusRaw = 'on_leave'; break;
                case '离职': $statusRaw = 'terminated'; break;
                default: $statusRaw = 'active';
            }
            
            $hireDate = $data['start_date'];
            
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hireDate)) {
                throw new Exception("无效的日期格式，请使用YYYY-MM-DD");
            }
            
            $dateParts = explode('-', $hireDate);
            $year = intval($dateParts[0]);
            $month = intval($dateParts[1]);
            $day = intval($dateParts[2]);
            
            if (!checkdate($month, $day, $year)) {
                throw new Exception("日期不存在，请检查年、月、日是否有效");
            }
            
            $staffSql = "UPDATE Staff 
                        SET user_name = ?, branch_ID = ?, position = ?, phone = ?, salary = ?, hire_date = ?, status = ?
                        WHERE staff_ID = ?";
            $staffStmt = $conn->prepare($staffSql);
            
            $staffStmt->bind_param("sissdssi", 
                $newUsername,  // 更新后的用户名
                $branchId,
                $data['role'],
                $data['phone'],
                $data['salary'],
                $hireDate,
                $statusRaw,
                $data['id']
            );
            
            if (!$staffStmt->execute()) {
                throw new Exception("更新员工信息失败: " . $staffStmt->error);
            }
            $staffStmt->close();
        }
        
        // 提交事务
        $conn->commit();
        return ['success' => true, 'message' => '保存成功'];
        
    } catch (Exception $e) {
        // 回滚事务
        $conn->rollback();
        error_log("保存员工信息失败: " . $e->getMessage());
        return ['success' => false, 'message' => '保存失败: ' . $e->getMessage()];
    }
}

// 门店数据查询（保持原逻辑，如需优化可创建门店视图）
function getBranchesData() {
    global $conn;
    $branches = [];
    try {
        // 先查询所有门店基础信息
        $sql = "SELECT * FROM Branch";
        $result = $conn->query($sql);
        
        if (!$result) {
            error_log("门店数据查询失败: " . $conn->error);
            return [];
        }
        
        // 一次性获取所有经理信息（职位为manager的员工），避免重复查询
        $managers = [];
        $managerSql = "SELECT s.staff_ID, CONCAT(u.first_name, ' ', u.last_name) AS name, 
                             u.user_telephone AS phone 
                      FROM Staff s
                      JOIN User u ON s.user_name = u.user_name
                      WHERE s.position = 'Manager' AND s.status = 'active'";
        $managerResult = $conn->query($managerSql);
        if ($managerResult && $managerResult->num_rows > 0) {
            while ($mgr = $managerResult->fetch_assoc()) {
                $managers[$mgr['staff_ID']] = [
                    'name' => $mgr['name'],
                    'phone' => $mgr['phone']
                ];
            }
        }

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $branchId = $row['branch_ID'] ?? 0;
                
                // 统计该门店的员工数量（使用data.php中已有的员工数据过滤）
                $employees = getEmployeesData();
                $staffCount = 0;
                foreach ($employees as $emp) {
                    if ($emp['branch_id'] == $branchId && $emp['status_raw'] == 'active') {
                        $staffCount++;
                    }
                }

                // 获取经理信息（从预加载的经理列表中匹配）
                $managerId = $row['manager_ID'] ?? '';
                $managerName = '未设置';
                $managerPhone = '';
                if (!empty($managerId) && isset($managers[$managerId])) {
                    $managerName = $managers[$managerId]['name'];
                    $managerPhone = $managers[$managerId]['phone'];
                }

                // 翻译状态
                $statusText = '';
                switch ($row['status'] ?? '') {
                    case 'active': $statusText = '营业中'; break;
                    case 'inactive': $statusText = '已停业'; break;
                    case 'under_renovation': $statusText = '装修中'; break;
                    default: $statusText = $row['status'] ?? '未知';
                }
                
                $branches[] = [
                    'id' => $row['branch_ID'] ?? '',
                    'name' => $row['branch_name'] ?? '',
                    'address' => $row['address'] ?? '',
                    'phone' => $row['phone'] ?? '',
                    'email' => $row['email'] ?? '',
                    'manager_id' => $managerId,
                    'manager_name' => $managerName,
                    'manager_phone' => $managerPhone,
                    'staff_count' => $staffCount,
                    'status' => $statusText,
                    'status_raw' => $row['status'] ?? '',
                    'established_date' => date('Y-m-d', strtotime($row['established_date'] ?? 'now')),
                    'created_at' => $row['established_date'] ?? '',
                ];
            }
        } else {
            error_log("门店数据查询结果为空");
        }
    } catch (Exception $e) {
        error_log("获取门店数据失败: " . $e->getMessage());
    }
    return $branches;
}
// 员工更新逻辑（保持原逻辑，基于表结构直接操作）
function updateEmployee($data) {
    global $conn;
    try {
        // 1. 更新User表
        $nameParts = explode(' ', $data['name']);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? $firstName;
        
        $userSql = "UPDATE User 
                   SET user_email = ?, user_telephone = ?, first_name = ?, last_name = ?
                   WHERE user_name = (SELECT user_name FROM Staff WHERE staff_ID = ?)";
        $stmt = $conn->prepare($userSql);
        $stmt->bind_param("ssssi", 
            $data['email'],
            $data['phone'],
            $firstName,
            $lastName,
            $data['id']
        );
        $stmt->execute();
        
        // 2. 更新Staff表
        $branchId = str_replace('BR-', '', $data['branch_id']);
        $statusRaw = '';
        switch ($data['status']) {
            case '在职': $statusRaw = 'active'; break;
            case '休假': $statusRaw = 'on_leave'; break;
            case '离职': $statusRaw = 'terminated'; break;
            default: $statusRaw = 'active';
        }
        
        $staffSql = "UPDATE Staff 
                    SET branch_ID = ?, position = ?, phone = ?, salary = ?, hire_date = ?, status = ?
                    WHERE staff_ID = ?";
        $staffStmt = $conn->prepare($staffSql);

        $timestamp = strtotime($data['start_date']);
        if ($timestamp === false) {
           throw new Exception("无效的日期格式,请使用YYYY-MM-DD");
        }
        $hireDate = date('Y-m-d', $timestamp);

        $year = date('Y', $timestamp);
        if ($year < 1970 || $year > 2100) {
          throw new Exception("日期年份必须在1970-2100之间");
        }
        $staffStmt->bind_param("issdsii", 
            $branchId,
            $data['role'],
            $data['phone'],
            $data['salary'],
            $hireDate,
            $statusRaw,
            $data['id']
        );
        $staffStmt->execute();
        return true;
    } catch (Exception $e) {
        error_log("更新员工数据失败: " . $e->getMessage());
        return false;
    }
}
function getCustomersData() {
    global $conn;
    $customers = [];
    
    try {
        // 检查数据库连接
        if (!$conn) {
            error_log("数据库连接不存在");
            return [];
        }
        
        // 简化查询：只获取基本信息
        $sql = "
    SELECT 
        customer_ID as id,
        full_name as name,
        phone,
        email,
        registered_date as registered,
        loyalty_level,
        order_count,
        total_spent
    FROM v_customer_profile
    ORDER BY customer_ID ASC";
        
        $result = $conn->query($sql);
        
        if (!$result) {
            error_log("顾客数据查询失败: " . $conn->error);
            return [];
        }
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // 简化数据处理
                $customers[] = [
                    'id' => $row['id'] ?? '',
                    'name' => $row['name'] ?? '',
                    'phone' => $row['phone'] ?? '',
                    'registered' => $row['registered'] ?? '',
                    'orders' => intval($row['order_count'] ?? 0),
                    'total_spent' => floatval($row['total_spent'] ?? 0),
                    'loyalty_level' => $row['loyalty_level'] ?? 'Regular'
                ];
            }
        } else {
            error_log("顾客数据查询结果为空");
        }
        
    } catch (Exception $e) {
        error_log("获取顾客数据失败: " . $e->getMessage());
    }
    
    return $customers;
}
function getPartnersData() {
    global $conn;
    $partners = [];
    
    try {
        // 供应商
        $sqlSuppliers = "SELECT supplier_ID as id, company_name as name FROM Supplier WHERE status = 'active'";
        $result = $conn->query($sqlSuppliers);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $partners[] = [
                    'id' => 'SUP-' . $row['id'],
                    'name' => $row['name'],
                    'type' => 'supplier',
                ];
            }
        }
    } catch (Exception $e) {
        error_log("获取合作伙伴数据失败: " . $e->getMessage());
    }
    
    return $partners;
}
// 获取供应商数据
function getSuppliersFromDB() {
    global $conn;
    $suppliers = [];
    
    try {
        $sql = "SELECT 
                    s.supplier_ID as id,
                    s.company_name as name,
                    s.supplier_category as category,
                    s.contact_person,
                    s.phone,
                    s.email,
                    s.address,
                    s.tax_number,
                    s.status,
                    DATE(s.created_at) as created_at
                FROM Supplier s
                ORDER BY s.created_at DESC, s.supplier_ID DESC";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $suppliers[] = [
                    'id' => $row['id'] ?? 0,
                    'name' => $row['name'] ?? '',
                    'category' => $row['category'] ?? '',
                    'contact_person' => $row['contact_person'] ?? '',
                    'phone' => $row['phone'] ?? '',
                    'email' => $row['email'] ?? '',
                    'address' => $row['address'] ?? '',
                    'tax_number' => $row['tax_number'] ?? '',
                    'status' => $row['status'] ?? 'active',
                    'created_at' => $row['created_at'] ?? '',
                    'updated_at' => $row['updated_at'] ?? $row['created_at']
                ];
            }
        }
    } catch (Exception $e) {
        error_log("获取供应商数据失败: " . $e->getMessage());
    }
    
    return $suppliers;
}

// 新增门店
function addBranch($data) {
    global $conn;
    try {
        $conn->begin_transaction();
        
        $sql = "INSERT INTO Branch (branch_name, address, phone, email, manager_ID, status)
                VALUES (?, ?, ?, ?, ?, 'active')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", 
            $data['branch_name'],
            $data['address'],
            $data['phone'],
            $data['email'],
            $data['manager_id']
        );
        $stmt->execute();
        $branchId = $conn->insert_id;
        
        // 更新经理所属门店
        if (!empty($data['manager_id'])) {
            $updateSql = "UPDATE Staff SET branch_ID = ? WHERE staff_ID = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ii", $branchId, $data['manager_id']);
            $updateStmt->execute();
        }
        
        $conn->commit();
        return ['success' => true, 'branch_id' => $branchId];
    } catch (Exception $e) {
        $conn->rollback();
        error_log("新增门店失败: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// 更新门店信息
function updateBranch($data) {
    global $conn;
    try {
        $sql = "UPDATE Branch SET 
                branch_name = ?, 
                address = ?, 
                phone = ?, 
                email = ?, 
                manager_ID = ?,
                status = ?
                WHERE branch_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssisi", 
            $data['branch_name'],
            $data['address'],
            $data['phone'],
            $data['email'],
            $data['manager_id'],
            $data['status'],
            $data['id']
        );
        $stmt->execute();
        return ['success' => true];
    } catch (Exception $e) {
        error_log("更新门店失败: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
/**
 * 生成订单的商品详情字符串（如：商品1 x2；商品2 x1）
 * @param int $orderId 订单ID
 * @return string 商品详情文本
 */
function getOrderProductDetails($orderId) {
    global $conn;
    try {
        // 关联订单项、商品表，获取商品名称和数量
        $sql = "SELECT 
                p.product_name,
                oi.quantity
                FROM OrderItem oi
                LEFT JOIN products p ON oi.product_ID = p.product_ID
                WHERE oi.order_ID = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();

        $details = [];
        while ($item = $result->fetch_assoc()) {
            // 拼接格式：商品名称 x数量
            $details[] = $item['product_name'] . " x" . $item['quantity'];
        }
        // 用分号分隔多个商品
        return implode(';', $details);
    } catch (Exception $e) {
        error_log("获取商品详情失败: " . $e->getMessage() . ",orderId: " . $orderId);
        return "获取失败";
    }
}
/**
 * 获取指定分店的订单列表（正确关联客户姓名）
 * @param int $branchId 分店ID
 * @return array 订单列表数组
 */
function getBranchOrdersWithDetails($branchId) {
    global $conn;
    try {
        // 按分支查询已完成订单
         $sql = "SELECT 
            order_ID,
            customer_name,
            order_date,
            final_amount,
            total_amount,
            order_status,
            branch_ID,
            discount_amount
        FROM BranchAllOrders
        WHERE branch_ID = ? 
          AND order_status = 'Completed'
        ORDER BY order_date DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $result = $stmt->get_result();

        $orders = [];
        while ($row = $result->fetch_assoc()) {
            // 调用之前的函数获取商品详情
            $row['product_details'] = getOrderProductDetails($row['order_ID']);
            $orders[] = $row;
        }
        return $orders;
    } catch (Exception $e) {
        error_log("获取订单详情失败: " . $e->getMessage() . ",branchId: " . $branchId);
        return [];
    }
}

// 获取可选的经理列表（在职员工）
function getAvailableManagers() {
    global $conn;
    $managers = [];
    $sql = "SELECT s.staff_ID, CONCAT(u.first_name, ' ', u.last_name) AS name 
            FROM Staff s
            JOIN User u ON s.user_name = u.user_name
            WHERE s.status = 'active' AND s.position = 'Manager'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $managers[] = $row;
        }
    }
    return $managers;
}

function getBranch($id) {
    global $conn;
    $sql = "SELECT * FROM Branch WHERE branch_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?: null;
}
// 获取门店员工
function getBranchStaff($branchId) {
    global $conn;
    $staff = [];
    $sql = "SELECT s.staff_ID, CONCAT(u.first_name, ' ', u.last_name) AS name, 
                   s.position, s.status, u.user_email, s.phone
            FROM Staff s
            JOIN User u ON s.user_name = u.user_name
            WHERE s.branch_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $branchId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $staff[] = $row;
    }
    return $staff;
}

// 获取所有不在当前门店的员工（用于添加）
function getAvailableStaff($branchId) {
    global $conn;
    $staff = [];
    $sql = "SELECT s.staff_ID, CONCAT(u.first_name, ' ', u.last_name) AS name, s.position
        FROM Staff s
        JOIN User u ON s.user_name = u.user_name
        WHERE (s.branch_ID != ? OR s.branch_ID IS NULL)
        AND s.status != 'terminated'"; 
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $branchId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $staff[] = $row;
    }
    return $staff;
}

/**
 * 获取销售趋势数据（近30天）
 */
function getSalesTrend() {
    global $conn;
    try {
        $sql = "SELECT date, total_sales, order_count FROM v_sales_trend";
        $result = $conn->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    } catch (Exception $e) {
        error_log("获取销售趋势失败: " . $e->getMessage());
        return [];
    }
}

/**
 * 获取订单状态分布
 */
function getOrderStatusDistribution() {
    global $conn;
    try {
        $sql = "SELECT order_status, count FROM v_order_status_distribution";
        $result = $conn->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    } catch (Exception $e) {
        error_log("获取订单状态分布失败: " . $e->getMessage());
        return [];
    }
}

/**
 * 获取门店销售对比数据
 */
function getBranchSalesComparison() {
    global $conn;
    try {
        $sql = "SELECT branch_name, total_sales, order_count FROM v_branch_sales_comparison";
        $result = $conn->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    } catch (Exception $e) {
        error_log("获取门店销售对比失败: " . $e->getMessage());
        return [];
    }
}

/**
 * 获取库存预警统计
 */
function getAlertSummary() {
    global $conn;
    try {
        $sql = "SELECT alert_type, count FROM v_alert_summary";
        $result = $conn->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['alert_type']] = $row['count'];
        }
        return $data;
    } catch (Exception $e) {
        error_log("获取预警统计失败: " . $e->getMessage());
        return [];
    }
}

/**
 * 获取分店销售数据（支持时间筛选和排序）
 * @param string $timeFilter 时间筛选类型：year、month、day
 * @param string $timeValue 时间值：2024、2024-01、2024-01-08
 * @param string $branchFilter 分店筛选（空表示所有分店）
 * @param string $sortField 排序字段：total_sales、order_count、branch_name
 * @param string $sortOrder 排序顺序：asc、desc
 * @return array 分店销售数据
 */
function getBranchSalesData($timeFilter = '', $timeValue = '', $branchFilter = '', $sortField = 'total_sales', $sortOrder = 'desc') {
    global $conn;
    $salesData = [];
    
    try {
        // 基础查询
        $sql = "
            SELECT 
                b.branch_ID,
                b.branch_name,
                COALESCE(SUM(co.final_amount), 0) AS total_sales,
                COUNT(DISTINCT co.order_ID) AS order_count,
                COALESCE(AVG(co.final_amount), 0) AS avg_order_value,
                COUNT(DISTINCT co.customer_ID) AS unique_customers
            FROM Branch b
            LEFT JOIN CustomerOrder co ON b.branch_ID = co.branch_ID
            WHERE co.status = 'Completed'
        ";
        
        $params = [];
        $types = "";
        
        // 时间筛选
        if (!empty($timeFilter) && !empty($timeValue)) {
            switch ($timeFilter) {
                case 'year':
                    $sql .= " AND YEAR(co.order_date) = ?";
                    $params[] = $timeValue;
                    $types .= "s";
                    break;
                case 'month':
                    $sql .= " AND DATE_FORMAT(co.order_date, '%Y-%m') = ?";
                    $params[] = $timeValue;
                    $types .= "s";
                    break;
                case 'day':
                    $sql .= " AND DATE(co.order_date) = ?";
                    $params[] = $timeValue;
                    $types .= "s";
                    break;
            }
        }
        
        // 分店筛选
        if (!empty($branchFilter)) {
            $sql .= " AND b.branch_name LIKE ?";
            $params[] = "%" . $branchFilter . "%";
            $types .= "s";
        }
        
        // 分组
        $sql .= " GROUP BY b.branch_ID, b.branch_name";
        
        // 排序
        $validSortFields = ['total_sales', 'order_count', 'avg_order_value', 'unique_customers', 'branch_name'];
        $validSortOrder = ['asc', 'desc'];
        
        if (in_array($sortField, $validSortFields) && in_array($sortOrder, $validSortOrder)) {
            $sql .= " ORDER BY " . $sortField . " " . $sortOrder;
        } else {
            $sql .= " ORDER BY total_sales DESC";
        }
        
        // 准备查询
        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($sql);
        }
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $salesData[] = [
                    'branch_id' => $row['branch_ID'],
                    'branch_name' => $row['branch_name'],
                    'total_sales' => floatval($row['total_sales']),
                    'order_count' => intval($row['order_count']),
                    'avg_order_value' => floatval($row['avg_order_value']),
                    'unique_customers' => intval($row['unique_customers']),
                    'formatted_sales' => '¥' . number_format($row['total_sales'], 2),
                    'formatted_avg' => '¥' . number_format($row['avg_order_value'], 2)
                ];
            }
        }
        
    } catch (Exception $e) {
        error_log("获取分店销售数据失败: " . $e->getMessage());
    }
    
    return $salesData;
}

/**
 * 获取所有分店名称列表（用于筛选）
 */
function getAllBranchNames() {
    global $conn;
    $branches = [];
    
    try {
        $sql = "SELECT branch_ID, branch_name FROM Branch WHERE status = 'active' ORDER BY branch_name";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $branches[] = [
                    'id' => $row['branch_ID'],
                    'name' => $row['branch_name']
                ];
            }
        }
    } catch (Exception $e) {
        error_log("获取分店列表失败: " . $e->getMessage());
    }
    
    return $branches;
}

/**
 * 获取最近几年的年份列表（用于时间筛选）
 */
function getRecentYears($limit = 5) {
    global $conn;
    $years = [];
    
    try {
        $sql = "SELECT DISTINCT YEAR(order_date) as year 
                FROM CustomerOrder 
                WHERE order_date IS NOT NULL 
                ORDER BY year DESC 
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $years[] = $row['year'];
        }
    } catch (Exception $e) {
        error_log("获取年份列表失败: " . $e->getMessage());
        // 提供默认年份
        $currentYear = date('Y');
        for ($i = 0; $i < $limit; $i++) {
            $years[] = $currentYear - $i;
        }
    }
    
    return $years;
}

function addProductToDB($name, $unit, $price, $spec, $supplier, $description, $category_id, $sale_quantity) {
    global $conn;
    
    try {
        // 1. 生成SKU（产品编号）
        $sku = generateProductSKU($category_id, $name,$sale_quantity);
        if($sku == ''){
            return [
            'success' => false,
            'message' => '添加产品失败: sku重复,可以考虑进货.'
        ];
        }
        // 2. 开始事务
        $conn->begin_transaction();

        $full_product_name = $name . ' ' . $sale_quantity . $unit;
        // 3. 插入产品到products表
        $sql = "INSERT INTO products (sku, product_name, unit_price, unit, description, category_id, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'active')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdssi", $sku, $full_product_name, $price, $unit, $description, $category_id);
        $stmt->execute();
        
        $product_id = $conn->insert_id; // 获取刚插入的产品ID
        
        // 4. 如果有规格信息，插入到ProductAttribute表
        if (!empty($spec)) {
            $attrSql = "INSERT INTO ProductAttribute (product_id, attr_name, attr_value) 
                        VALUES (?, 'spec', ?)";
            $attrStmt = $conn->prepare($attrSql);
            $attrStmt->bind_param("is", $product_id, $spec);
            $attrStmt->execute();
            $attrStmt->close();
        }
        
        // 6. 提交事务
        $conn->commit();
        $stmt->close();
        
        return [
            'success' => true,
            'product_id' => $product_id,
            'sku' => $sku,
            'message' => '产品添加成功'
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        return [
            'success' => false,
            'error' => '添加产品失败: ' . $e->getMessage()
        ];
    }
}

// 生成SKU的函数
function generateProductSKU($category_id, $product_name = '',$sale_quantity = '') {
    global $conn;
    
    // 根据分类生成前缀
    $prefixes = [
        5 => 'VEG',
        6 => 'FRU',
        7 => 'MEAT',
        8 => 'EGG',
        9 => 'FISH',
        10 => 'SHRIMP',
        11 => 'SEA'
    ];
    
    $prefix = $prefixes[$category_id] ?? 'PD';
    
    // 生成产品名字的缩写（取前2-3个大写字母）
    $name_abbr = '';
    if (!empty($product_name)) {
        // 1. 移除空格和特殊字符
        $clean_name = preg_replace('/[^a-zA-Z0-9]/', '', $product_name);
        
        // 2. 提取大写字母
        $uppercase_chars = '';
        for ($i = 0; $i < strlen($clean_name); $i++) {
            if (ctype_upper($clean_name[$i])) {
                $uppercase_chars .= $clean_name[$i];
            }
        }
        
        // 3. 如果有大写字母，取前3个
        if (!empty($uppercase_chars)) {
            $name_abbr = substr($uppercase_chars, 0, 3);
        } else {
            // 4. 如果没有大写字母，取前3个字符转大写
            $name_abbr = strtoupper(substr($clean_name, 0, 3));
        }
        
        // 5. 确保至少2个字符
        if (strlen($name_abbr) < 2) {
            $name_abbr = strtoupper(substr($clean_name, 0, 3));
        }
    } else {
        $name_abbr = 'GEN'; // 通用缩写
    }
    
    // 查询该分类下已有产品数量
    $countSql = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param("i", $category_id);
    $countStmt->execute();
    $result = $countStmt->get_result();
    $data = $result->fetch_assoc();
    $countStmt->close();
    
    $count = $data['count'] + 1;
    
    // 生成SKU：前缀-产品缩写-序号（2位数字）
    $sku = $prefix . '-' . 
           $name_abbr . '-' .
           str_pad($sale_quantity, 2, '0', STR_PAD_LEFT);
    
    // 确保SKU唯一
    $checkSql = "SELECT product_ID FROM products WHERE sku = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $sku);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $sku = '';
    }
    
    $checkStmt->close();
    return $sku;
}


function getProductCategories() {
    global $conn;
    $categories = [];
    
    try {
        // 只获取果蔬、肉禽蛋、水产这几个父类
        $sql = "SELECT category_id, category_name FROM Categories 
                WHERE category_id IN (5, 6, 7, 8, 9, 10, 11) 
                ORDER BY category_id";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("获取分类失败: " . $e->getMessage());
    }
    
    return $categories;
}

function buildCategoryTree() {
    global $conn;
    $hierarchy = [];
    
    try {
        // 获取所有分类，按层级排序
        $sql = "SELECT category_id, category_name, parent_category_id 
                FROM Categories 
                ORDER BY 
                    CASE WHEN parent_category_id IS NULL THEN 0 ELSE 1 END,
                    parent_category_id,
                    category_id";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $allCategories = [];
            while ($row = $result->fetch_assoc()) {
                $allCategories[] = $row;
            }
            
            // 构建层次结构
            foreach ($allCategories as $cat) {
                $catId = $cat['category_id'];
                $parentId = $cat['parent_category_id'];
                
                if ($parentId === null) {
                    // 第一级分类
                    $hierarchy[$catId] = [
                        'name' => $cat['category_name'],
                        'children' => []
                    ];
                } else {
                    // 遍历第一级分类
                    foreach ($hierarchy as $topId => &$topCat) {
                        if ($topId == $parentId) {
                            // 第二级分类
                            $topCat['children'][$catId] = [
                                'name' => $cat['category_name'],
                                'children' => []
                            ];
                            break;
                        } else {
                            // 检查是否属于某个第二级分类
                            foreach ($topCat['children'] as $secondId => &$secondCat) {
                                if ($secondId == $parentId) {
                                    // 第三级分类
                                    $secondCat['children'][$catId] = $cat['category_name'];
                                    break 2; // 跳出两层循环
                                }
                            }
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("构建分类树失败: " . $e->getMessage());
    }
    
    return $hierarchy;
}
// 经理端：产品库存（按门店汇总，使用视图）
function getManagerProductInventoryByBranch() {
    global $conn;
    $data = [];
    try {
        $sql = "SELECT * FROM v_manager_product_inventory_by_branch";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("获取产品库存汇总失败: " . $e->getMessage());
    }
    return $data;
}

// 经理端：产品供应商进货价与售价（使用视图）
function getManagerProductSupplierPricing() {
    global $conn;
    $data = [];
    try {
        $sql = "SELECT * FROM v_manager_product_supplier_pricing";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("获取供应商售价失败: " . $e->getMessage());
    }
    return $data;
}

// 初始化数据
$products = getProductsViewData();
$transactions = getTransactionsData();
$branches = getBranchesData();
$employees = getEmployeesData();
$customers = getCustomersData(); 
$partners = getPartnersData();  
?>