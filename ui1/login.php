<?php
session_start();
// 数据库配置
$servername = "localhost";
$username = "root";
$password = "@Wang200504230819";
$dbname = "mydb";

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
                
                header('Location: ../manager/overview.php');
                exit();
            }
        }
        $_SESSION['login_error'] = '经理账户验证失败，请检查用户名和密码！';
        
    } catch(PDOException $e) {
        $_SESSION['login_error'] = '数据库连接失败: ' . $e->getMessage();
    }
}
// 处理顾客登录（模仿经理的写法）
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
                
                $stmt->close();
                $conn->close();
                
                header('Location: ../customer/header.php');
                exit();
            }
        }
        
        $_SESSION['login_error'] = '顾客账户验证失败，请检查用户名和密码！';
        $conn->close();
        
    } catch(Exception $e) {
        $_SESSION['login_error'] = '登录出错: ' . $e->getMessage();
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
                    <button type="button" class="role-btn supplier" data-role="supplier" data-url="../supplier/index.php">供应商登录</button>
                </div>
            </form>
        </div>
    </div>

    <script>
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

        // 角色按钮点击事件
        roleBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                if (this.type === 'submit') return; // 经理按钮已经是submit类型
                
                const role = this.getAttribute('data-role');
                selectedRole.value = role;
                
                if (validateForm()) {
                    const targetUrl = this.getAttribute('data-url');
                    // 显示登录中状态
                    const originalText = this.innerHTML;
                    this.innerHTML = '<span class="loading"></span> 登录中...';
                    this.disabled = true;
                    
                    // 模拟登录过程
                    setTimeout(() => {
                        // 对于非经理用户，直接跳转
                        loginForm.action = ''; // 清除表单action
                        window.location.href = targetUrl;
                    }, 800);
                }
            });
        });
        

        // 经理登录按钮特殊处理
        const managerBtn = document.querySelector('.role-btn.manager');
        managerBtn.addEventListener('click', function(e) {
        // 设置角色为manager
        selectedRole.value = 'CEO';
    
        if (validateForm()) {
        // 显示登录中状态
           const originalText = this.innerHTML;
           this.innerHTML = '<span class="loading"></span> 登录中...';
           this.disabled = true;
        
           // 直接提交表单，验证在PHP端处理
            loginForm.action = '';
            loginForm.submit();
        } else {
            e.preventDefault();
          }
       });
       // 在角色按钮点击事件中，为顾客按钮添加类似经理的处理逻辑
       const customerBtn = document.querySelector('.role-btn.customer');
       customerBtn.addEventListener('click', function(e) {
        // 设置角色为customer
        selectedRole.value = 'customer';
    
       if (validateForm()) {
         // 显示登录中状态
         const originalText = this.innerHTML;
         this.innerHTML = '<span class="loading"></span> 登录中...';
         this.disabled = true;
        
         // 提交表单到后端验证
         loginForm.action = '<?php echo $_SERVER["PHP_SELF"]; ?>';
         loginForm.submit();
        } else {
           e.preventDefault();
        }
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