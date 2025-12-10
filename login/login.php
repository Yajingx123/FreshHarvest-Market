<?php
// 生鲜电商多角色登录界面 - 左右分栏布局版
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
            opacity: 1; /* 初始位置 */
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
            position: relative;
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
        
        /* 禁用浏览器原生密码可见性图标 */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none;
        }
        input[type="password"]::-webkit-eye-button {
            display: none !important;
        }
        .form-control[type="password"] {
            padding-right: 40px !important;
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
        
        /* 密码可见性切换按钮 - 适配图片图标 */
        .toggle-password {
            position: absolute;
            right: 16px;
            top: 42px;
            cursor: pointer;
            color: #666;
            background: none;
            border: none;
            padding: 0;
            width: 20px;  /* 图标宽度 */
            height: 20px; /* 图标高度 */
        }
        .toggle-password img {
            width: 100%;
            height: 100%;
            object-fit: contain;
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
        /* 注册相关样式 */
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        .register-link a {
            color: #1976d2;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        .register-link a:hover {
            color: #1565c0;
            text-decoration: underline;
        }
        .register-form {
            display: none;
            width: 100%;
        }
        .register-btn {
            width: 100%;
            padding: 14px 0;
            border-radius: 8px;
            border: none;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #fff;
            background-color: #43a047;
            margin-top: 30px;
            position: relative;
            overflow: hidden;
        }
        .register-btn::after {
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
        .register-btn:hover::after {
            width: 200px;
            height: 200px;
        }
        .register-btn:active {
            transform: scale(0.98);
        }
        .back-link {
            text-align: center;
            margin-top: 15px;
        }
        .back-link a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        .back-link a:hover {
            color: #333;
            text-decoration: underline;
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
            
            <form class="login-form" id="loginForm">
                <div class="form-group">
                    <label class="form-label" for="username">用户名</label>
                    <input type="text" class="form-control" id="username" placeholder="请输入用户名/手机号">
                    <div class="error-tip" id="usernameTip">用户名不能为空</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">密码</label>
                    <input type="password" class="form-control" id="password" placeholder="请输入密码">
                    <!-- 替换为图片图标 -->
                    <button type="button" class="toggle-password" data-target="password">
                        <img src="eye-close.png" alt="隐藏密码" class="eye-icon">
                    </button>
                    <div class="error-tip" id="passwordTip">密码不能为空</div>
                </div>
                
                <!-- 角色登录按钮组 -->
                <div class="role-btns">
                    <button type="button" class="role-btn customer" data-url="../customer/dashboard.php">顾客登录</button>
                    <button type="button" class="role-btn employee" data-url="../staff/dashboard.php">员工登录</button>
                    <button type="button" class="role-btn manager" data-url="../manager/overview.php">经理登录</button>
                    <button type="button" class="role-btn supplier" data-url="../supplier/index.php">供应商登录</button>
                </div>
                
                <!-- 顾客注册链接 -->
                <div class="register-link">
                    <a href="javascript:;" id="showRegister">顾客注册</a>
                </div>
            </form>
            
            <!-- 注册表单 -->
            <form class="register-form" id="registerForm">
                <div class="form-group">
                    <label class="form-label" for="regUsername">用户名</label>
                    <input type="text" class="form-control" id="regUsername" placeholder="请设置用户名/手机号">
                    <div class="error-tip" id="regUsernameTip">用户名不能为空</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="regPassword">密码</label>
                    <input type="password" class="form-control" id="regPassword" placeholder="请设置密码">
                    <!-- 替换为图片图标 -->
                    <button type="button" class="toggle-password" data-target="regPassword">
                        <img src="eye-close.png" alt="隐藏密码" class="eye-icon">
                    </button>
                    <div class="error-tip" id="regPasswordTip">密码不能为空</div>
                </div>
                
                <!-- 注册按钮 -->
                <button type="button" class="register-btn" id="registerBtn" data-url="../customer/dashboard.php">注册</button>
                
                <!-- 返回登录链接 -->
                <div class="back-link">
                    <a href="javascript:;" id="showLogin">已有账号？返回登录</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // 获取DOM元素
        const username = document.getElementById('username');
        const password = document.getElementById('password');
        const usernameTip = document.getElementById('usernameTip');
        const passwordTip = document.getElementById('passwordTip');
        const roleBtns = document.querySelectorAll('.role-btn');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const showRegister = document.getElementById('showRegister');
        const showLogin = document.getElementById('showLogin');
        const regUsername = document.getElementById('regUsername');
        const regPassword = document.getElementById('regPassword');
        const regUsernameTip = document.getElementById('regUsernameTip');
        const regPasswordTip = document.getElementById('regPasswordTip');
        const registerBtn = document.getElementById('registerBtn');
        const togglePasswordBtns = document.querySelectorAll('.toggle-password');

        // 密码可见性切换功能（适配图片图标）
        togglePasswordBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation(); // 阻止事件冒泡
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                const eyeIcon = this.querySelector('.eye-icon');
                
                // 切换密码可见性
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                
                // 切换图片图标（请替换为实际的图标路径）
                if (isPassword) {
                    eyeIcon.src = 'eye-open.png';
                    eyeIcon.alt = '显示密码';
                } else {
                    eyeIcon.src = 'eye-close.png';
                    eyeIcon.alt = '隐藏密码';
                }
            });
        });

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

        // 注册表单校验
        function validateRegisterForm() {
            let isValid = true;
            
            // 注册用户名校验
            if (!regUsername.value.trim()) {
                regUsername.classList.add('error');
                regUsernameTip.classList.add('show');
                isValid = false;
            } else {
                regUsername.classList.remove('error');
                regUsernameTip.classList.remove('show');
            }
            
            // 注册密码校验
            if (!regPassword.value.trim()) {
                regPassword.classList.add('error');
                regPasswordTip.classList.add('show');
                isValid = false;
            } else {
                regPassword.classList.remove('error');
                regPasswordTip.classList.remove('show');
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

        // 注册输入框实时校验
        regUsername.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('error');
                regUsernameTip.classList.remove('show');
            }
        });

        regPassword.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('error');
                regPasswordTip.classList.remove('show');
            }
        });

        // 角色按钮点击事件
        roleBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // 禁用按钮防止重复点击
                this.disabled = true;
                const originalText = this.innerHTML;
                
                if (validateForm()) {
                    const targetUrl = this.getAttribute('data-url');
                    // 显示登录中状态
                    this.innerHTML = '<span class="loading"></span> 登录中...';
                    // 模拟登录过程
                    setTimeout(() => {
                        window.location.href = targetUrl;
                    }, 800);
                } else {
                    // 校验失败时恢复按钮状态
                    this.disabled = false;
                }
            });
        });

        // 注册按钮点击事件
        registerBtn.addEventListener('click', function() {
            this.disabled = true;
            const originalText = this.innerHTML;
            
            if (validateRegisterForm()) {
                const targetUrl = this.getAttribute('data-url');
                this.innerHTML = '<span class="loading"></span> 注册中...';
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 800);
            } else {
                this.disabled = false;
            }
        });

        // 切换到注册表单
        showRegister.addEventListener('click', function() {
            loginForm.style.display = 'none';
            registerForm.style.display = 'block';
        });

        // 切换到登录表单
        showLogin.addEventListener('click', function() {
            registerForm.style.display = 'none';
            loginForm.style.display = 'block';
        });

        // 页面载入动画
        window.addEventListener('load', () => {
            // 初始隐藏
            const bannerImg = document.querySelector('.banner-img');
            const loginContainer = document.querySelector('.login-container');
            
            bannerImg.style.opacity = '0';
            loginContainer.style.opacity = '0';
            loginContainer.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                // 渐显动画
                bannerImg.style.transition = 'opacity 1s ease';
                bannerImg.style.opacity = '1';
                
                loginContainer.style.transition = 'opacity 0.8s ease 0.3s, transform 0.8s ease 0.3s';
                loginContainer.style.opacity = '1';
                loginContainer.style.transform = 'translateY(0)';
            }, 100);
        });

        window.addEventListener('beforeunload', function() {
            // 重置所有角色按钮状态
            roleBtns.forEach(btn => {
                btn.disabled = false;
                // 恢复原始文本
                const roleText = btn.textContent.trim();
                if(roleText.includes('登录中')) {
                    if(btn.classList.contains('customer')) btn.innerHTML = '顾客登录';
                    if(btn.classList.contains('employee')) btn.innerHTML = '员工登录';
                    if(btn.classList.contains('manager')) btn.innerHTML = '经理登录';
                    if(btn.classList.contains('supplier')) btn.innerHTML = '供应商登录';
                }
            });
            
            // 重置注册按钮状态
            registerBtn.disabled = false;
            if(registerBtn.textContent.includes('注册中')) {
                registerBtn.innerHTML = '注册';
            }
        });
    </script>
</body>
</html>