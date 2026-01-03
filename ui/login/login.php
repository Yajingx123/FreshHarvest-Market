<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "NewRootPwd123!"; // 你的密码
$dbname = "mydb";
// 数据库配置
function getDBConnection() {
    $servername = "localhost";
    $username = "root";
    $password = "NewRootPwd123!"; // 你的密码
    $dbname = "mydb";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("数据库连接失败: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}


function updateExpiredInventory() {
    $conn = getDBConnection();
    // 获取当天日期
    $currentDate = date('Y-m-d');
    
    // 1. 查询所有已过期的库存批次（date_expired < 当前日期）
    $query = "SELECT batch_ID, product_ID, branch_ID, quantity_on_hand 
              FROM Inventory 
              WHERE date_expired IS NOT NULL 
                AND date_expired < ?
                AND quantity_on_hand > 0";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $currentDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $expiredBatches = [];
    while ($row = $result->fetch_assoc()) {
        $expiredBatches[] = $row;
    }
    $stmt->close();
    
    if (empty($expiredBatches)) {
        $conn->close();
        return ['updated' => 0, 'message' => '没有找到过期库存'];
    }
    
    // 2. 开始事务，更新过期库存
    $conn->autocommit(false);
    try {
        $updateQuery = "UPDATE Inventory 
                        SET quantity_on_hand = 0,
                            locked_inventory = 0  -- 同时清空锁定库存
                        WHERE batch_ID = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updatedCount = 0;
        foreach ($expiredBatches as $batch) {
            $updateStmt->bind_param("s", $batch['batch_ID']);
            $updateStmt->execute();
            $updatedCount += $updateStmt->affected_rows;
    
            $certificateQuery = "INSERT INTO StockItemCertificate 
                        (item_ID, transaction_type, transaction_id, note, date) 
                        VALUES (?, 'adjustment', ?, ?, NOW())";
            $certificateStmt = $conn->prepare($certificateQuery);

            $itemQuery = "SELECT item_ID FROM StockItem WHERE batch_ID = ?";
            $itemStmt = $conn->prepare($itemQuery);
            $itemStmt->bind_param("s", $batch['batch_ID']);
            $itemStmt->execute();
            $itemResult = $itemStmt->get_result();
    
            while ($item = $itemResult->fetch_assoc()) {
               $note = "批次过期清零：批次 " . $batch['batch_ID'] . 
                  ",产品ID " . $batch['product_ID'] . 
                  "，清零数量 " . $batch['quantity_on_hand'];
        
               $certificateStmt->bind_param("sis", 
                 $item['item_ID'], 
                 $batch['batch_ID'],
                 $note
              );
               $certificateStmt->execute();
            }
    
            $itemStmt->close();
            $certificateStmt->close();
        }
    
        $updateStmt->close();
        $conn->commit(); 
        return ['success' => true, 'updated' => $updatedCount];
        
    } catch (Exception $e) {
        $conn->rollback();
        $conn->close();
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// 处理顾客注册
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $input_username = trim($_POST['username']);
    $input_password = trim($_POST['password']);
    $input_email = trim($_POST['email']);
    $input_phone = trim($_POST['phone']);
    $input_first_name = trim($_POST['first_name']);
    $input_last_name = trim($_POST['last_name']);
    $input_gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
    $input_address = isset($_POST['address']) ? trim($_POST['address']) : '';
    
    try {
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            throw new Exception("数据库连接失败: " . $conn->connect_error);
        }
        
        // 检查用户名是否已存在
        $checkUser = "SELECT user_ID FROM User WHERE user_name = ?";
        $stmt = $conn->prepare($checkUser);
        $stmt->bind_param("s", $input_username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['register_error'] = '用户名已存在，请选择其他用户名';
            header('Location: login.php#register');
            exit();
        }
        $stmt->close();
        
        // 检查邮箱是否已存在
        $checkEmail = "SELECT user_ID FROM User WHERE user_email = ?";
        $stmt = $conn->prepare($checkEmail);
        $stmt->bind_param("s", $input_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['register_error'] = '邮箱已被注册，请使用其他邮箱';
            header('Location: login.php#register');
            exit();
        }
        $stmt->close();
        
        // 开始事务
        $conn->autocommit(false);
        
        // 1. 插入用户表
        $hashed_password = md5($input_password);
        $insertUser = "INSERT INTO User (user_name, password_hash, user_type, user_email, user_telephone, first_name, last_name, created_at, is_active) 
                       VALUES (?, ?, 'customer', ?, ?, ?, ?, NOW(), TRUE)";
        $stmt = $conn->prepare($insertUser);
        $stmt->bind_param("ssssss", $input_username, $hashed_password, $input_email, $input_phone, $input_first_name, $input_last_name);
        
        if (!$stmt->execute()) {
            throw new Exception("用户注册失败: " . $stmt->error);
        }
        $user_id = $stmt->insert_id;
        $stmt->close();
        
        // 2. 插入顾客表
        $insertCustomer = "INSERT INTO Customer (user_name, phone, email, gender, address, accu_cost, loyalty_level, created_at) 
                           VALUES (?, ?, ?, ?, ?, 0, 'Regular', NOW())";
        $stmt = $conn->prepare($insertCustomer);
        $stmt->bind_param("sssss", $input_username, $input_phone, $input_email, $input_gender, $input_address);
        
        if (!$stmt->execute()) {
            throw new Exception("顾客信息创建失败: " . $stmt->error);
        }
        $customer_id = $stmt->insert_id;
        $stmt->close();
        
        // 提交事务
        $conn->commit();
        
        // 不自动登录，只显示成功消息
        $_SESSION['register_success'] = '注册成功！请使用您的密码登录';
        
        // 同时保存用户名到会话，以便自动填充登录表单
        $_SESSION['last_registered_username'] = $input_username;
        
        $conn->close();
        
        // 重定向回登录页面，自动切换到登录选项卡
        header('Location: login.php');
        exit();
        
    } catch (Exception $e) {
        if (isset($conn) && $conn->autocommit === false) {
            $conn->rollback();
        }
        $_SESSION['register_error'] = '注册失败: ' . $e->getMessage();
        header('Location: login.php#register');
        exit();
    }
}
// 处理经理登录
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role']) && $_POST['role'] === 'CEO') {
    $input_username = trim($_POST['username']);
    $input_password = trim($_POST['password']);
    
    try {
        // 使用PDO连接
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "SELECT user_ID, user_name, password_hash FROM User WHERE user_name = ? AND user_type = 'CEO'";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$input_username]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $hashed_password = md5($input_password);
            
            if ($hashed_password === $user['password_hash']) {
                $_SESSION['manager_logged_in'] = true;
                $_SESSION['manager_id'] = $user['user_ID'];
                $_SESSION['manager_username'] = $user['user_name'];
                $_SESSION['user_role'] = 'CEO';

                updateExpiredInventory();
                
                header('Location: ../manager/overview.php');
                exit();
            }
        }
        $_SESSION['login_error'] = '经理账户验证失败，请检查用户名和密码！';
        header('Location: login.php');
        exit();
        
    } catch(PDOException $e) {
        $_SESSION['login_error'] = '数据库连接失败: ' . $e->getMessage();
        header('Location: login.php');
        exit();
    }
}

// 处理顾客登录
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role']) && $_POST['role'] === 'customer') {
    $input_username = trim($_POST['username']);
    $input_password = trim($_POST['password']);
    
    try {
        // 直接连接数据库（不要用函数）
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            die("数据库连接失败: " . $conn->connect_error);
        }
        
        // 查询顾客账户
        $sql = "SELECT u.*, c.customer_ID 
                FROM User u 
                LEFT JOIN Customer c ON u.user_name = c.user_name 
                WHERE u.user_name = ? AND u.user_type = 'customer'";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $input_username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $hashed_password = md5($input_password);
            
            if ($hashed_password === $user['password_hash']) {
                $_SESSION['customer_logged_in'] = true;
                $_SESSION['customer_id'] = $user['customer_ID'];
                $_SESSION['customer_username'] = $user['user_name'];
                $_SESSION['user_role'] = 'customer';

                updateExpiredInventory();
                
                $stmt->close();
                $conn->close();
                
                header('Location: ../customer/dashboard.php');
                exit();
            }
        }
        
        $_SESSION['login_error'] = '顾客账户验证失败，请检查用户名和密码！';
        $conn->close();
        
    } catch(Exception $e) {
        $_SESSION['login_error'] = '登录出错: ' . $e->getMessage();
    }
}

// 处理员工登录
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role']) && $_POST['role'] === 'employee') {
    $input_username = trim($_POST['username']);
    $input_password = trim($_POST['password']);

    try {
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            throw new Exception("数据库连接失败: " . $conn->connect_error);
        }

        // 查询员工账户
        $sql = "SELECT u.user_ID, u.user_name, u.password_hash, s.staff_ID, s.branch_ID 
                FROM User u 
                JOIN Staff s ON u.user_name = s.user_name 
                WHERE u.user_name = ? AND u.user_type = 'staff'";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $input_username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $hashed_password = md5($input_password);
            
            if ($hashed_password === $user['password_hash']) {
                $_SESSION['staff_logged_in'] = true;
                $_SESSION['staff_id'] = $user['staff_ID'];
                $_SESSION['staff_branch_id'] = $user['branch_ID'];
                $_SESSION['staff_username'] = $user['user_name'];
                $_SESSION['user_role'] = 'staff';

                updateExpiredInventory();

                $stmt->close();
                $conn->close();

                header('Location: ../staff/dashboard.php');
                exit();
            }
        }

        $_SESSION['login_error'] = '员工账户验证失败，请检查用户名和密码！';
        $stmt->close();
        $conn->close();
        header('Location: login.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['login_error'] = '登录出错: ' . $e->getMessage();
        header('Location: login.php');
        exit();
    }
}

// 处理供应商登录
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role']) && $_POST['role'] === 'supplier') {
    $input_username = trim($_POST['username']);
    $input_password = trim($_POST['password']);
    
    try {
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            throw new Exception("数据库连接失败: " . $conn->connect_error);
        }
        
        // 修正查询语句，查询供应商类型用户
        $sql = "SELECT u.user_ID, u.user_name, u.password_hash, s.supplier_ID 
                FROM User u 
                JOIN Supplier s ON u.user_name = s.user_name 
                WHERE u.user_name = ? AND u.user_type = 'supplier'";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $input_username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $hashed_password = md5($input_password);
            
            if ($hashed_password === $user['password_hash']) {
                $_SESSION['supplier_logged_in'] = true;
                $_SESSION['supplier_id'] = $user['supplier_ID'];
                $_SESSION['supplier_username'] = $user['user_name'];
                $_SESSION['user_role'] = 'supplier';

                updateExpiredInventory();
                
                $stmt->close();
                $conn->close();
                header('Location: ../supplier/dashboard.php');
                exit();
            }
        }
        
        $_SESSION['login_error'] = '供应商账户验证失败，请检查用户名和密码！';
        $stmt->close();
        $conn->close();
        header('Location: login.php');
        exit();
        
    } catch(Exception $e) {
        $_SESSION['login_error'] = '登录出错: ' . $e->getMessage();
        header('Location: login.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>鲜选生鲜 - 统一登录平台</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Microsoft YaHei', Arial, sans-serif;
        }
        body {
            min-height: 100vh;
            display: flex;
            overflow: hidden;
        }
        /* 左侧宣传图区域 */
        .banner-area {
            flex: 65%;
            height: 100vh;
            position: relative;
            overflow: hidden;
        }
        .banner-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 8s ease;
            opacity: 1; /* 初始透明度 */
        }
        .banner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(0,0,0,0.5), rgba(0,0,0,0.3));
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 10%;
        }
        .banner-title {
            color: #fff;
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 20px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            max-width: 600px;
        }
        .banner-desc {
            color: rgba(255,255,255,0.9);
            font-size: 18px;
            line-height: 1.6;
            max-width: 500px;
            text-shadow: 0 1px 5px rgba(0,0,0,0.2);
        }
        
        /* 右侧登录表单区域 - 毛玻璃效果 */
        .login-area {
            flex: 35%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 50px;
            position: relative;
            background: url("background.jpg") no-repeat center center;
            background-size: cover;
        }
        .login-area::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .login-container {
            position: relative;
            width: 100%;
            max-width: 400px;
            opacity: 1; /* 初始透明度 */
            transform: translateY(0); /* 初始位置 */
        }
        .login-title {
            font-size: 30px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 40px;
            text-align: center;
        }
        .login-subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 15px;
        }
        
        /* 选项卡样式 */
        .tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }
        .tab {
            flex: 1;
            padding: 12px 0;
            text-align: center;
            font-size: 16px;
            font-weight: 500;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }
        .tab.active {
            color: #1976d2;
            border-bottom-color: #1976d2;
        }
        .tab:hover {
            color: #1976d2;
        }
        
        /* 表单样式 */
        .form-container {
            transition: all 0.3s ease;
        }
        .login-form, .register-form {
            width: 100%;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid rgba(200, 200, 200, 0.8);
            border-radius: 6px;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: #1976d2;
            box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.15);
        }
        .form-control.error {
            border-color: #e53935;
        }
        .error-tip {
            color: #e53935;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }
        .error-tip.show {
            display: block;
        }
        
        /* 按钮样式 */
        .btn {
            width: 100%;
            padding: 14px 0;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .btn-primary {
            background-color: #1976d2;
            color: white;
        }
        .btn-primary:hover {
            background-color: #1565c0;
        }
        .btn-secondary {
            background-color: #43a047;
            color: white;
            margin-top: 10px;
        }
        .btn-secondary:hover {
            background-color: #388e3c;
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        /* 右侧登录表单区域 - 添加滚动支持 */
        .login-area {
           flex: 35%;
           height: 100vh;
           display: flex;
           align-items: flex-start; /* 改为顶部对齐 */
           justify-content: center;
           padding: 50px 50px 20px; /* 增加顶部内边距 */
           position: relative;
            background: url("background.jpg") no-repeat center center;
           background-size: cover;
           overflow-y: auto; /* 允许垂直滚动 */
        }

        .login-container {
           position: relative;
           width: 100%;
           max-width: 400px;
           min-height: 500px; /* 最小高度 */
           opacity: 1;
           transform: translateY(0);
        }

        .form-container {
          transition: all 0.3s ease;
          max-height: 500px; /* 限制最大高度 */
          overflow-y: auto; /* 添加滚动条 */
          padding-right: 10px; /* 为滚动条留空间 */
        }


        .form-container::-webkit-scrollbar {
           width: 6px;
        }

        .form-container::-webkit-scrollbar-track {
          background: rgba(0,0,0,0.05);
           border-radius: 3px;
        }

        .form-container::-webkit-scrollbar-thumb {
           background: rgba(0,0,0,0.2);
           border-radius: 3px;
        }

        .form-container::-webkit-scrollbar-thumb:hover {
          background: rgba(0,0,0,0.3);
        }

        .form-group {
           margin-bottom: 15px; /* 减小间距 */
        }

        .form-label {
           display: block;
           margin-bottom: 6px; /* 减小间距 */
          font-weight: 500;
          color: #555;
          font-size: 14px;
        }

        .form-control {
           width: 100%;
           padding: 10px 12px; /* 减小内边距 */
           border: 1px solid rgba(200, 200, 200, 0.8);
           border-radius: 6px;
           font-size: 14px;
            background: rgba(255, 255, 255, 0.9);
           transition: all 0.3s ease;
        }
        
        /* 角色按钮组 */
        .role-btns {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 20px;
        }
        .role-btn {
            padding: 12px 0;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .role-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }
        .role-btn:hover::after {
            width: 200px;
            height: 200px;
        }
        .role-btn:active {
            transform: scale(0.98);
        }
        .role-btn.customer {
            background-color: #43a047;
        }
        .role-btn.employee {
            background-color: #ff9800;
        }
        .role-btn.manager {
            background-color: #7b1fa2;
        }
        .role-btn.supplier {
            background-color: #1976d2;
        }

        /* 注册按钮样式 */
        #registerBtn {
           background-color: #43a047;
           color: white;
           padding: 12px 0;
           border: none;
           border-radius: 8px;
           font-size: 16px;
           font-weight: 500;
           cursor: pointer;
           transition: all 0.3s ease;
           width: 100%;
           margin-top: 10px;
         }

         #registerBtn:hover {
           background-color: #388e3c;
        }

        #registerBtn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* 加载动画样式 */
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* 注册表单默认隐藏 */
        .register-form {
            display: none;
        }
        .register-form.active {
            display: block;
        }
        
        /* 成功消息 */
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #2e7d32;
        }
        
        /* 响应式调整 */
        @media (max-width: 992px) {
            .banner-area {
                flex: 60%;
            }
            .login-area {
                flex: 40%;
            }
            .banner-title {
                font-size: 40px;
            }
        }
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            .banner-area {
                flex: none;
                height: 35vh;
            }
            .login-area {
                flex: none;
                height: 65vh;
                width: 100%;
                padding: 0 30px;
            }
            .banner-title {
                font-size: 32px;
            }
            .banner-desc {
                font-size: 16px;
            }
        }
        @media (max-width: 576px) {
            .role-btns {
                grid-template-columns: 1fr;
            }
            .banner-overlay {
                padding: 0 5%;
            }
            .banner-title {
                font-size: 26px;
            }
        }
    </style>
</head>
<body>
    <!-- 左侧宣传图区域 -->
    <div class="banner-area">
        <img src="background.jpg" alt="鲜选生鲜宣传图" class="banner-img">
        <div class="banner-overlay">
            <h1 class="banner-title">新鲜直达，品质生活</h1>
            <p class="banner-desc">鲜选生鲜致力于为您提供最新鲜的食材，从产地到餐桌，全程冷链保鲜，让健康饮食成为日常。</p>
        </div>
    </div>
    
    <!-- 右侧登录表单区域 -->
    <div class="login-area">
        <div class="login-container">
            <h2 class="login-title">鲜选生鲜</h2>
            
            <!-- 错误消息显示区域 -->
            <?php if (isset($_SESSION['login_error'])): ?>
                <div style="background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #c62828;">
                    <?php echo $_SESSION['login_error']; unset($_SESSION['login_error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['register_error'])): ?>
                <div style="background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #c62828;">
                    <?php echo $_SESSION['register_error']; unset($_SESSION['register_error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['register_success'])): ?>
                <div class="success-message">
                    <?php echo $_SESSION['register_success']; unset($_SESSION['register_success']); ?>
                </div>
            <?php endif; ?>
            
            <!-- 选项卡 -->
            <div class="tabs">
                <div class="tab active" data-tab="login">登录</div>
                <div class="tab" data-tab="register">注册</div>
            </div>
            
            <!-- 表单容器 -->
            <div class="form-container">
                <!-- 登录表单 -->
                <form class="login-form active" id="loginForm" method="POST">
                    <div class="form-group">
                        <label class="form-label" for="login_username">用户名</label>
                        <input type="text" class="form-control" id="login_username" name="username" placeholder="请输入用户名/手机号" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        <div class="error-tip" id="login_usernameTip">用户名不能为空</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="login_password">密码</label>
                        <input type="password" class="form-control" id="login_password" name="password" placeholder="请输入密码">
                        <div class="error-tip" id="login_passwordTip">密码不能为空</div>
                    </div>
                    
                    <!-- 隐藏的角色选择字段 -->
                    <input type="hidden" id="selectedRole" name="role" value="">
                    
                    <!-- 角色登录按钮组 -->
                    <div class="role-btns">
                        <button type="button" class="role-btn customer" data-role="customer">顾客登录</button>
                        <button type="button" class="role-btn employee" data-role="employee">员工登录</button>
                        <button type="submit" class="role-btn manager" data-role="manager">经理登录</button>
                        <button type="button" class="role-btn supplier" data-role="supplier">供应商登录</button>
                    </div>
                </form>
                
                <!-- 注册表单 -->
                <form class="register-form" id="registerForm" method="POST">
                    <input type="hidden" name="register" value="1">
                    
                    <div class="form-group">
                        <label class="form-label" for="register_username">用户名 *</label>
                        <input type="text" class="form-control" id="register_username" name="username" placeholder="请输入用户名（用于登录）">
                        <div class="error-tip" id="register_usernameTip">用户名不能为空</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="register_password">密码 *</label>
                        <input type="password" class="form-control" id="register_password" name="password" placeholder="请输入密码（至少6位）">
                        <div class="error-tip" id="register_passwordTip">密码不能少于6位</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="register_email">邮箱 *</label>
                        <input type="email" class="form-control" id="register_email" name="email" placeholder="请输入常用邮箱">
                        <div class="error-tip" id="register_emailTip">请输入有效的邮箱地址</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="register_phone">手机号 *</label>
                        <input type="tel" class="form-control" id="register_phone" name="phone" placeholder="请输入手机号">
                        <div class="error-tip" id="register_phoneTip">请输入有效的手机号</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="register_first_name">姓氏</label>
                        <input type="text" class="form-control" id="register_first_name" name="first_name" placeholder="请输入姓氏">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="register_last_name">名字</label>
                        <input type="text" class="form-control" id="register_last_name" name="last_name" placeholder="请输入名字">
                    </div>
                        <div class="form-group">
                          <label class="form-label" for="register_gender">性别 *</label>
                            <select class="form-control" id="register_gender" name="gender">
                               <option value="">请选择性别</option>
                                  <option value="Male">Male</option>
                                  <option value="Female">Female</option>
                            </select>
                        <div class="error-tip" id="register_genderTip">请选择性别</div>
                    </div>

                    <div class="form-group">
                         <label class="form-label" for="register_address">地址</label>
                           <textarea class="form-control" id="register_address" name="address" placeholder="请输入详细地址" rows="2"></textarea>
                     </div>

                    <button type="button" class="btn btn-primary" id="registerBtn">注册</button>
                </form>
            </div>
            <?php if (isset($_SESSION['logout_success'])): ?>
             <div class="success-message" style="background-color: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #4CAF50;">
            ✅ <?php echo $_SESSION['logout_success']; unset($_SESSION['logout_success']); ?>
            </div>
<?php endif; ?>
        </div>
    </div>

    <script>
        // 获取DOM元素
        const loginTab = document.querySelector('.tab[data-tab="login"]');
        const registerTab = document.querySelector('.tab[data-tab="register"]');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        // 选项卡切换 - 完全显示/隐藏
        loginTab.addEventListener('click', function() {
           this.classList.add('active');
           registerTab.classList.remove('active');
    
           loginForm.style.display = 'block';
            registerForm.style.display = 'none';
    
           registerForm.reset();
        });
        registerTab.addEventListener('click', function() {
            this.classList.add('active');
            loginTab.classList.remove('active');
    
             // 完全隐藏登录表单，显示注册表单
            loginForm.style.display = 'none';
            registerForm.style.display = 'block';
        });
        
        
        // 登录表单验证和提交
        const loginUsername = document.getElementById('login_username');
        const loginPassword = document.getElementById('login_password');
        const selectedRole = document.getElementById('selectedRole');
        const loginUsernameTip = document.getElementById('login_usernameTip');
        const loginPasswordTip = document.getElementById('login_passwordTip');
        const loginRoleBtns = document.querySelectorAll('#loginForm .role-btn');
        
        function validateLoginForm() {
            let isValid = true;
            
            if (!loginUsername.value.trim()) {
                loginUsername.classList.add('error');
                loginUsernameTip.classList.add('show');
                isValid = false;
            } else {
                loginUsername.classList.remove('error');
                loginUsernameTip.classList.remove('show');
            }
            
            if (!loginPassword.value.trim()) {
                loginPassword.classList.add('error');
                loginPasswordTip.classList.add('show');
                isValid = false;
            } else {
                loginPassword.classList.remove('error');
                loginPasswordTip.classList.remove('show');
            }
            
            return isValid;
        }
        
        loginRoleBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                
                const role = this.getAttribute('data-role');
                selectedRole.value = role === 'manager' ? 'CEO' : role;
                
                if (validateLoginForm()) {
                    // 显示登录中状态
                    const originalText = this.innerHTML;
                    this.innerHTML = '<span class="loading"></span> 登录中...';
                    this.disabled = true;
                    
                    setTimeout(() => {
                        loginForm.submit();
                    }, 500);
                }
            });
        });
        
        // 注册表单验证和提交
const registerUsername = document.getElementById('register_username');
const registerPassword = document.getElementById('register_password');
const registerEmail = document.getElementById('register_email');
const registerPhone = document.getElementById('register_phone');
const registerFirstName = document.getElementById('register_first_name');
const registerLastName = document.getElementById('register_last_name');
const registerUsernameTip = document.getElementById('register_usernameTip');
const registerPasswordTip = document.getElementById('register_passwordTip');
const registerEmailTip = document.getElementById('register_emailTip');
const registerPhoneTip = document.getElementById('register_phoneTip');
const registerBtn = document.getElementById('registerBtn'); // 现在这个元素存在了

function validateRegisterForm() {
    let isValid = true;
    
    // 用户名验证
    if (!registerUsername.value.trim()) {
        registerUsername.classList.add('error');
        registerUsernameTip.textContent = '用户名不能为空';
        registerUsernameTip.classList.add('show');
        isValid = false;
    } else {
        registerUsername.classList.remove('error');
        registerUsernameTip.classList.remove('show');
    }
    
    // 密码验证
    if (!registerPassword.value.trim() || registerPassword.value.length < 6) {
        registerPassword.classList.add('error');
        registerPasswordTip.textContent = '密码不能少于6位';
        registerPasswordTip.classList.add('show');
        isValid = false;
    } else {
        registerPassword.classList.remove('error');
        registerPasswordTip.classList.remove('show');
    }
    
    // 邮箱验证
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!registerEmail.value.trim() || !emailRegex.test(registerEmail.value)) {
        registerEmail.classList.add('error');
        registerEmailTip.textContent = '请输入有效的邮箱地址';
        registerEmailTip.classList.add('show');
        isValid = false;
    } else {
        registerEmail.classList.remove('error');
        registerEmailTip.classList.remove('show');
    }
    
    // 手机号验证（简单验证）
    const phoneRegex = /^1[3-9]\d{9}$/;
    if (!registerPhone.value.trim() || !phoneRegex.test(registerPhone.value)) {
        registerPhone.classList.add('error');
        registerPhoneTip.textContent = '请输入有效的11位手机号';
        registerPhoneTip.classList.add('show');
        isValid = false;
    } else {
        registerPhone.classList.remove('error');
        registerPhoneTip.classList.remove('show');
    }
    
    return isValid;
}

registerBtn.addEventListener('click', function() {
    if (validateRegisterForm()) {
        // 显示注册中状态
        const originalText = this.innerHTML;
        this.innerHTML = '<span class="loading"></span> 注册中...';
        this.disabled = true;
        
        // 直接提交表单，后端会处理注册逻辑
        registerForm.submit();
    }
});
        
        // 页面载入动画
        // 页面加载完成后检查是否有注册成功消息
    window.addEventListener('load', () => {
       const bannerImg = document.querySelector('.banner-img');
       const loginContainer = document.querySelector('.login-container');
    
      bannerImg.style.opacity = '0';
      loginContainer.style.opacity = '0';
      loginContainer.style.transform = 'translateY(20px)';
    
      setTimeout(() => {
        bannerImg.style.transition = 'opacity 1s ease';
        bannerImg.style.opacity = '1';
        
        loginContainer.style.transition = 'opacity 0.8s ease 0.3s, transform 0.8s ease 0.3s';
        loginContainer.style.opacity = '1';
        loginContainer.style.transform = 'translateY(0)';
        
        // 检查是否有注册成功消息，如果有则自动切换到登录选项卡
        if (document.querySelector('.success-message')) {
            // 延迟500毫秒执行，确保DOM已完全加载
            setTimeout(() => {
                // 自动点击登录选项卡
                const loginTab = document.querySelector('.tab[data-tab="login"]');
                if (loginTab) {
                    loginTab.click();
                }
                
                // 如果有保存的用户名，自动填充到登录表单
                const loginUsernameInput = document.getElementById('login_username');
                if (loginUsernameInput) {
                    // 从PHP传过来的会话中获取用户名
                    const lastUsername = '<?php echo isset($_SESSION["last_registered_username"]) ? $_SESSION["last_registered_username"] : ""; ?>';
                    if (lastUsername) {
                        loginUsernameInput.value = lastUsername;
                        // 清除会话中的用户名
                        <?php unset($_SESSION['last_registered_username']); ?>
                    }
                }
            }, 500);
        }
      }, 100);
    });

        // 页面卸载前重置按钮状态
       window.addEventListener('beforeunload', function() {
         loginRoleBtns.forEach(btn => {
           btn.disabled = false;
           const roleText = btn.textContent.trim();
           if(roleText.includes('登录中')) {
              const role = btn.getAttribute('data-role');
              switch(role) {
                case 'customer': btn.innerHTML = '顾客登录'; break;
                case 'employee': btn.innerHTML = '员工登录'; break;
                case 'manager': btn.innerHTML = '经理登录'; break;
                case 'supplier': btn.innerHTML = '供应商登录'; break;
               }
            }
        });
    
       if (registerBtn) {
          registerBtn.disabled = false;
          if(registerBtn.textContent.includes('注册中')) {
            registerBtn.innerHTML = '注册';
          }
        }
    });
    </script>
</body>
</html>
