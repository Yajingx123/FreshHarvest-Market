<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "8049023544Aaa?"; // 你的密码
$dbname = "mydb";
// 数据库配置
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
        
        /* 表单样式 */
        .login-form {
            width: 100%;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: #555;
            font-size: 15px;
        }
        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid rgba(200, 200, 200, 0.8);
            border-radius: 8px;
            font-size: 15px;
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
            font-size: 13px;
            margin-top: 6px;
            display: none;
        }
        .error-tip.show {
            display: block;
        }
        
        /* 角色按钮组 */
        .role-btns {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 30px;
        }
        .role-btn {
            padding: 14px 0;
            border-radius: 8px;
            border: none;
            font-size: 15px;
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
            <p class="login-subtitle">请输入账号信息并选择角色登录</p>
            
            <!-- 错误消息显示区域 -->
            <?php if (isset($_SESSION['login_error'])): ?>
                <div style="background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #c62828;">
                    <?php echo $_SESSION['login_error']; unset($_SESSION['login_error']); ?>
                </div>
            <?php endif; ?>
            
            <form class="login-form" id="loginForm" method="POST">
                <div class="form-group">
                    <label class="form-label" for="username">用户名</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="请输入用户名/手机号" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    <div class="error-tip" id="usernameTip">用户名不能为空</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">密码</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="请输入密码">
                    <div class="error-tip" id="passwordTip">密码不能为空</div>
                </div>
                
                <!-- 隐藏的角色选择字段 -->
                <input type="hidden" id="selectedRole" name="role" value="">
                
                <!-- 角色登录按钮组 -->
                <div class="role-btns">
                    <button type="button" class="role-btn customer" data-role="customer" data-url="../customer/dashboard.php">顾客登录</button>
                    <button type="button" class="role-btn employee" data-role="employee" data-url="../staff/dashboard.php">员工登录</button>
                    <button type="submit" class="role-btn manager" data-role="manager">经理登录</button>
                    <button type="button" class="role-btn supplier" data-role="supplier" data-url="../supplier/dashboard.php">供应商登录</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // 修改JavaScript部分，确保所有角色都通过表单提交验证
        // 获取DOM元素
        const username = document.getElementById('username');
        const password = document.getElementById('password');
        const selectedRole = document.getElementById('selectedRole');
        const usernameTip = document.getElementById('usernameTip');
        const passwordTip = document.getElementById('passwordTip');
        const roleBtns = document.querySelectorAll('.role-btn');
        const loginForm = document.getElementById('loginForm');

        // 非空校验函数
        function validateForm() {
            let isValid = true;
            
            // 用户名校验
            if (!username.value.trim()) {
                username.classList.add('error');
                usernameTip.classList.add('show');
                isValid = false;
            } else {
                username.classList.remove('error');
                usernameTip.classList.remove('show');
            }
            
            // 密码校验
            if (!password.value.trim()) {
                password.classList.add('error');
                passwordTip.classList.add('show');
                isValid = false;
            } else {
                password.classList.remove('error');
                passwordTip.classList.remove('show');
            }
            
            return isValid;
        }

        // 输入框实时校验
        username.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('error');
                usernameTip.classList.remove('show');
            }
        });

        password.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('error');
                passwordTip.classList.remove('show');
            }
        });

        // 角色按钮点击事件 - 统一通过表单提交
        roleBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); // 阻止默认行为
                
                const role = this.getAttribute('data-role');
                selectedRole.value = role === 'manager' ? 'CEO' : role; // 处理经理角色映射
                
                if (validateForm()) {
                    // 显示登录中状态
                    const originalText = this.innerHTML;
                    this.innerHTML = '<span class="loading"></span> 登录中...';
                    this.disabled = true;
                    
                    // 提交表单进行后端验证
                    setTimeout(() => {
                        loginForm.submit();
                    }, 500);
                }
            });
        });

        // 页面载入动画
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
            }, 100);
        });
    </script>
</body>
</html>