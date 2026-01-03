<?php
session_start();
require_once __DIR__ . '/inc/data.php';
require_once __DIR__ . '/header.php'; 

$supplierInfo = [];
$pendingCount = 0;
if (isset($_SESSION['supplier_username'])) {
    $supplierInfo = getSupplierInfo($_SESSION['supplier_username']);
    if ($supplierInfo && !empty($supplierInfo['supplier_ID'])) {
        $pendingCount = getPendingOrderCount($supplierInfo['supplier_ID']);
    }
}

// 处理保存编辑的请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $errors = [];
        $updateData = [];
        
        // 验证姓名 - 对应User表的first_name, last_name
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        if (empty($firstName) || empty($lastName)) {
            $errors[] = '姓名不能为空';
        } else {
            $updateData['first_name'] = $firstName;
            $updateData['last_name'] = $lastName;
        }
        
        // 验证邮箱 - 对应User表的user_email
        $email = trim($_POST['email'] ?? '');
        if (empty($email)) {
            $errors[] = '邮箱地址不能为空';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = '邮箱格式不正确';
        } else {
            $updateData['user_email'] = $email;
        }
        
        // 验证电话 - 对应User表的user_telephone
        $phone = trim($_POST['phone'] ?? '');
        if (empty($phone)) {
            $errors[] = '联系电话不能为空';
        } elseif (!preg_match('/^\d{10,15}$/', $phone)) { // 简化验证
            $errors[] = '电话号码必须是10-15位数字';
        } else {
            $updateData['user_telephone'] = $phone;
        }
        
        // 验证联系人 - 对应Supplier表的contact_person
        $contactPerson = trim($_POST['contact_person'] ?? '');
        if (empty($contactPerson)) {
            $errors[] = '联系人不能为空';
        } else {
            $updateData['contact_person'] = $contactPerson;
        }
        
        // 验证地址 - 对应Supplier表的address
        $address = trim($_POST['address'] ?? '');
        if (empty($address)) {
            $errors[] = '联系地址不能为空';
        } else {
            $updateData['address'] = $address;
        }
        
        // 如果没有错误，保存到数据库
        if (empty($errors) && !empty($updateData) && isset($_SESSION['supplier_username'])) {
            $result = updateSupplierInfo($_SESSION['supplier_username'], $updateData);
            if ($result) {
                $success = '信息更新成功！';
                // 重新获取最新信息
                $supplierInfo = getSupplierInfo($_SESSION['supplier_username']);
            } else {
                $errors[] = '保存失败，请稍后重试';
            }
        }
    } 
    // 处理密码修改
    elseif ($_POST['action'] === 'update_password') {
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');
        $passwordErrors = [];
        
        // 检查是否输入了密码
        if ($newPassword === '' && $confirmPassword === '') {
            // 没有输入密码，不做修改
        } else {
            // 验证密码
            if ($newPassword !== $confirmPassword) {
                $passwordErrors[] = '两次输入的密码不一致';
            } elseif (strlen($newPassword) < 6) {
                $passwordErrors[] = '密码长度至少为6位';
            } else {
                // 更新密码
                if (updateSupplierPassword($_SESSION['supplier_username'], $newPassword)) {
                    $passwordSuccess = '密码修改成功！';
                } else {
                    $passwordErrors[] = '密码修改失败，请重试';
                }
            }
        }
        
        // 如果有密码错误，合并到普通错误中
        if (!empty($passwordErrors)) {
            if (!isset($errors)) $errors = [];
            $errors = array_merge($errors, $passwordErrors);
        }
        
        // 如果有密码成功消息，显示
        if (!empty($passwordSuccess)) {
            $success = !empty($success) ? $success . '<br>' . $passwordSuccess : $passwordSuccess;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>鲜选生鲜 - 供应商信息</title>
    <style>
        /* 保持所有原有样式不变 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Microsoft YaHei', Arial, sans-serif;
        }
        body {
            background-color: #f8f9fa;
            color: #333;
        }
        .header {
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .nav-container {
            width: 90%;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #1976d2;
            text-decoration: none;
        }
        .nav-menu {
            display: flex;
            list-style: none;
        }
        .nav-item {
            margin-left: 30px;
        }
        .nav-link {
            text-decoration: none;
            color: #333;
            font-size: 16px;
            font-weight: 500;
            transition: color 0.3s;
            display: inline-flex;
            align-items: center;
        }
        .nav-link:hover {
            color: #1976d2;
        }
        .nav-link.active {
            color: #1976d2;
            font-weight: bold;
        }
        .main {
            width: 90%;
            margin: 30px auto;
            min-height: calc(100vh - 200px);
        }
        .section-title {
            font-size: 22px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .profile-wrapper {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .profile-title {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
            position: relative;
            display: inline-block;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .profile-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            border-radius: 2px;
        }
        
        .profile-subtitle {
            color: #7f8c8d;
            font-size: 18px;
            font-weight: 400;
            margin-top: 10px;
        }
        
        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .profile-card-header {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 25px 30px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: bold;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }
        
        .profile-info-header {
            flex: 1;
        }
        
        .profile-name {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .profile-username {
            font-size: 16px;
            opacity: 0.9;
            font-weight: 400;
        }
        
        .profile-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-top: 10px;
            backdrop-filter: blur(5px);
        }
        
        .profile-card-body {
            padding: 30px;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .profile-section {
            background: #f8fafc;
            border-radius: 15px;
            padding: 25px;
            border: 1px solid #eef2f7;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #3498db;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eef2f7;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            font-size: 20px;
        }
        
        .info-item {
            margin-bottom: 18px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eef2f7;
            transition: all 0.3s ease;
        }
        
        .info-item:hover {
            border-bottom-color: #3498db;
        }
        
        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .info-label {
            display: block;
            font-size: 13px;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 500;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background: rgba(46, 204, 113, 0.15);
            color: #27ae60;
        }
        
        .status-pending {
            background: rgba(241, 196, 15, 0.15);
            color: #f39c12;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            justify-content: center;
        }
        
        .btn {
            padding: 14px 32px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-width: 180px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(149, 165, 166, 0.3);
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(149, 165, 166, 0.4);
        }
        
        .icon {
            font-size: 18px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 30px;
            background: #f8fafc;
            border-radius: 15px;
            border: 2px dashed #e0e6ed;
        }
        
        .empty-icon {
            font-size: 60px;
            color: #bdc3c7;
            margin-bottom: 20px;
        }
        
        .empty-text {
            font-size: 18px;
            color: #7f8c8d;
            margin-bottom: 25px;
        }
        
        .notification-badge {
            position: relative;
            display: inline-block;
        }
        
        .notification-dot {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 8px;
            height: 8px;
            background: #e74c3c;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.7);
            }
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 10px rgba(231, 76, 60, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(231, 76, 60, 0);
            }
        }
        
        @media (max-width: 768px) {
            .profile-wrapper {
                margin: 20px auto;
                padding: 0 15px;
            }
            
            .profile-title {
                font-size: 26px;
            }
            
            .profile-card-header {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }
            
            .profile-avatar {
                width: 70px;
                height: 70px;
                font-size: 30px;
            }
            
            .profile-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .profile-card-body {
                padding: 20px;
            }
            
            .profile-section {
                padding: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
            }
        }
/* ========== 退出确认弹窗样式 ========== */
.logout-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.logout-modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.logout-modal {
    background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
    border-radius: 16px;
    width: 90%;
    max-width: 400px;
    padding: 40px 30px 30px;
    position: relative;
    transform: translateY(-30px) scale(0.95);
    transition: all 0.4s ease;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.logout-modal-overlay.active .logout-modal {
    transform: translateY(0) scale(1);
}

.logout-modal-icon {
    font-size: 64px;
    color: #ff6b6b;
    text-align: center;
    margin-bottom: 20px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.logout-modal-title {
    font-size: 24px;
    font-weight: 600;
    color: #333;
    text-align: center;
    margin-bottom: 10px;
}

.logout-modal-message {
    font-size: 16px;
    color: #666;
    text-align: center;
    line-height: 1.6;
    margin-bottom: 30px;
}

.logout-modal-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.logout-modal-btn {
    flex: 1;
    padding: 14px 0;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 120px;
}

.logout-modal-cancel {
    background-color: #f0f0f0;
    color: #666;
}

.logout-modal-cancel:hover {
    background-color: #e0e0e0;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.logout-modal-confirm {
    background: linear-gradient(135deg, #ff6b6b 0%, #ff4757 100%);
    color: white;
}

.logout-modal-confirm:hover {
    background: linear-gradient(135deg, #ff4757 0%, #ff3838 100%);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
}

.logout-modal-btn:active {
    transform: translateY(0);
}
        
        @media (max-width: 480px) {
            .profile-title {
                font-size: 22px;
            }
            
            .profile-name {
                font-size: 20px;
            }
            
            .section-title {
                font-size: 16px;
            }
            
            .info-value {
                font-size: 14px;
            }
        }
        
        /* 添加一些装饰性元素 */
        .decoration-circle {
            position: fixed;
            border-radius: 50%;
            z-index: -1;
        }
        
        .circle-1 {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1) 0%, rgba(41, 128, 185, 0.05) 100%);
            top: -150px;
            right: -150px;
        }
        
        .circle-2 {
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.1) 0%, rgba(39, 174, 96, 0.05) 100%);
            bottom: -100px;
            left: -100px;
        }

        .edit-mode .info-value {
          display: none;
        }

        .edit-mode .edit-input {
           display: block;
        }

         .info-value, .edit-input {
           transition: all 0.3s;
        }

        .edit-input {
          display: none;
          width: 100%;
          padding: 8px;
          border: 1px solid #ddd;
          border-radius: 4px;
          font-size: 16px;
        }

        .error {
          color: #e74c3c;
          font-size: 12px;
          margin-top: 4px;
        }

        .form-group {
          margin-bottom: 15px;
        }
        /* 在现有的按钮样式后面添加 */
.btn-danger {
    background: linear-gradient(135deg, #ff6b6b 0%, #ff4757 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
    padding: 14px 32px;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-width: 180px;
}

.btn-danger:hover {
    background: linear-gradient(135deg, #ff4757 0%, #ff3838 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
}

/* 新增：密码修改相关样式 */
.password-input-group {
    margin-top: 10px;
}

.password-input-item {
    margin-bottom: 10px;
}

.password-input-item:last-child {
    margin-bottom: 0;
}

.password-input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.password-hint {
    font-size: 12px;
    color: #95a5a6;
    margin-top: 4px;
    display: block;
}

.btn-success {
    background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(46, 204, 113, 0.4);
}

.btn-warning {
    background: linear-gradient(135deg, #f39c12 0%, #d35400 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
}

.btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(243, 156, 18, 0.4);
}
    </style>
</head>
<body>
    <!-- 装饰性背景圆圈 -->
    <div class="decoration-circle circle-1"></div>
    <div class="decoration-circle circle-2"></div>
    
    <div class="profile-wrapper">
        <div class="profile-header">
            <h1 class="profile-title">供应商信息中心</h1>
            <p class="profile-subtitle">查看和管理您的供应商账户信息</p>
        </div>
        
        <?php if (!empty($supplierInfo)): ?>
            <div class="profile-card">
                <div class="profile-card-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($supplierInfo['first_name'], 0, 1) . substr($supplierInfo['last_name'], 0, 1)); ?>
                    </div>
                    <div class="profile-info-header">
                        <h2 class="profile-name">
                            <?php echo htmlspecialchars($supplierInfo['first_name'] . ' ' . $supplierInfo['last_name']); ?>
                            <?php if ($pendingCount > 0): ?>
                                <span class="notification-badge">
                                    <span class="notification-dot"></span>
                                </span>
                            <?php endif; ?>
                        </h2>
                        <p class="profile-username">@<?php echo htmlspecialchars($supplierInfo['user_name']); ?></p>
                        <span class="profile-badge">
                            <?php 
                            $status = $supplierInfo['status'] ?? 'active';
                            echo $status === 'active' ? '✅ 账户正常' : '⏳ 账户审核中';
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="profile-card-body">
    <!-- 添加错误显示区域 -->
    <?php if (!empty($errors)): ?>
    <div class="error-message" style="
        background: #fee;
        border: 1px solid #e74c3c;
        border-radius: 6px;
        padding: 12px 15px;
        margin: 15px 0;
        color: #e74c3c;
        font-size: 14px;
    ">
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
    <div class="success-message" style="
        background: #dff0d8;
        border: 1px solid #27ae60;
        border-radius: 6px;
        padding: 12px 15px;
        margin: 15px 0;
        color: #27ae60;
        font-size: 14px;
    ">
        <?php echo htmlspecialchars($success); ?>
    </div>
    <?php endif; ?>
    
    <div class="profile-grid">
<div class="profile-section">
    <h3 class="section-title">
        <i>👤</i> 基本信息
    </h3>
    <div class="info-item">
        <span class="info-label">用户名</span>
        <span class="info-value">
            <i>👤</i>
            <?php echo htmlspecialchars($supplierInfo['user_name']); ?>
        </span>
    </div>
    <div class="info-item" data-field="name">
        <span class="info-label">姓名</span>
        <span class="info-value">
            <i>📝</i>
            <?php echo htmlspecialchars($supplierInfo['first_name'] . ' ' . $supplierInfo['last_name']); ?>
        </span>
    </div>
    <div class="info-item" data-field="email">
        <span class="info-label">邮箱地址</span>
        <span class="info-value">
            <i>📧</i>
            <?php echo htmlspecialchars($supplierInfo['user_email']); ?>
        </span>
    </div>
    <div class="info-item" data-field="phone">
        <span class="info-label">联系电话</span>
        <span class="info-value">
            <i>📱</i>
            <?php echo htmlspecialchars($supplierInfo['user_telephone'] ?? '未设置'); ?>
        </span>
    </div>
</div>
                        
                        <!-- 供应商信息 -->
<div class="profile-section">
    <h3 class="section-title">
        <i>🏢</i> 供应商信息
    </h3>
    <div class="info-item">
        <span class="info-label">供应商名称</span>
        <span class="info-value">
            <i>🏷️</i>
            <?php echo htmlspecialchars($supplierInfo['user_name'] ?? '未设置'); ?>
        </span>
    </div>
    <div class="info-item" data-field="contact_person">
        <span class="info-label">联系人</span>
        <span class="info-value">
            <i>👥</i>
            <?php echo htmlspecialchars($supplierInfo['contact_person'] ?? '未设置'); ?>
        </span>
    </div>
    <div class="info-item" data-field="address">
        <span class="info-label">联系地址</span>
        <span class="info-value">
            <i>📍</i>
            <?php echo htmlspecialchars($supplierInfo['address'] ?? '未设置'); ?>
        </span>
    </div>
    <div class="info-item">
        <span class="info-label">待处理订单</span>
        <span class="info-value">
            <i>📦</i>
            <?php echo $pendingCount > 0 ? 
                '<span style="color:#e74c3c; font-weight:bold;">' . $pendingCount . ' 个待处理</span>' : 
                '<span style="color:#27ae60;">无待处理订单</span>'; ?>
        </span>
    </div>
</div>
                        
                        <!-- 账户信息 -->
                        <div class="profile-section">
                            <h3 class="section-title">
                                <i>🔐</i> 账户信息
                            </h3>
                            <div class="info-item">
                                <span class="info-label">账户状态</span>
                                <span class="info-value">
                                    <?php if(($supplierInfo['status'] ?? 'active') === 'active'): ?>
                                        <span class="status-badge status-active">✅ 正常使用</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">⏳ 审核中</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">注册时间</span>
                                <span class="info-value">
                                    <i>📅</i>
                                    <?php echo htmlspecialchars(date('Y年m月d日 H:i', strtotime($supplierInfo['created_at']))); ?>
                                </span>
                            </div>
                            <!-- 新增密码修改区域 -->
                            <div class="info-item" data-field="password" id="passwordSection">
                                <span class="info-label">修改密码</span>
                                <div class="password-input-group" style="display: none;">
                                    <div class="password-input-item">
                                        <input type="password" name="new_password" class="password-input" 
                                               placeholder="请输入新密码" autocomplete="new-password">
                                        <span class="password-hint">密码长度至少6位</span>
                                    </div>
                                    <div class="password-input-item">
                                        <input type="password" name="confirm_password" class="password-input" 
                                               placeholder="请再次输入新密码" autocomplete="new-password">
                                        <span class="password-hint">两次输入的密码必须一致</span>
                                    </div>
                                </div>
                                <span class="info-value">
                                    <i>🔒</i>
                                    <span id="passwordDisplay">●●●●●●</span>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 表单提交 -->
                    <form id="profileForm" method="post" style="display: none;">
                        <input type="hidden" name="action" id="formAction" value="update_profile">
                        <!-- 其他字段将通过JavaScript动态填充 -->
                    </form>
                    
                    <div class="action-buttons">                        
                        <button type="button" class="btn btn-primary" id="editBtn">
                           <span class="icon">✏️</span>
                           编辑信息
                        </button>

                        <button type="button" class="btn btn-success" id="saveBtn" style="display:none;">
                           <span class="icon">💾</span>
                             保存修改
                           </button>

                        <button type="button" class="btn btn-warning" id="cancelBtn" style="display:none;">
                           <span class="icon">↩️</span>
                           取消编辑
                           </button>
                        <button type="button" class="btn btn-danger" onclick="showLogoutModal()">
                          <span class="icon">🚪</span>
                          退出登录
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">🔒</div>
                <h3 class="empty-text">未获取到供应商信息</h3>
                <p style="color: #95a5a6; margin-bottom: 30px;">请先登录您的供应商账户</p>
                <button class="btn btn-primary" onclick="location.href='../login/logout.php'">
                    <span class="icon">🔑</span>
                    前往登录
                </button>
            </div>
        <?php endif; ?>
    </div>
    <div class="logout-modal-overlay" id="logoutModal">
    <div class="logout-modal">
        <div class="logout-modal-icon">⚠️</div>
        <h3 class="logout-modal-title">确认退出</h3>
        <p class="logout-modal-message">确定要退出登录吗？<br>退出后需要重新登录才能访问账户。</p>
        <div class="logout-modal-actions">
            <button type="button" class="logout-modal-btn logout-modal-cancel" id="cancelLogout">取消</button>
            <button type="button" class="logout-modal-btn logout-modal-confirm" id="confirmLogout">确认退出</button>
        </div>
    </div>
</div>
<script>
const logoutModal = document.getElementById('logoutModal');
const cancelLogoutBtn = document.getElementById('cancelLogout');
const confirmLogoutBtn = document.getElementById('confirmLogout');
let originalLogoutBtnText = '';

// 显示退出确认弹窗
function showLogoutModal() {
    logoutModal.classList.add('active');
    document.body.style.overflow = 'hidden'; // 禁止背景滚动
}

// 隐藏退出确认弹窗
function hideLogoutModal() {
    logoutModal.classList.remove('active');
    document.body.style.overflow = ''; // 恢复背景滚动
}

// 点击弹窗外部关闭
logoutModal.addEventListener('click', function(e) {
    if (e.target === logoutModal) {
        hideLogoutModal();
    }
});

// 点击取消按钮
cancelLogoutBtn.addEventListener('click', hideLogoutModal);

// 点击确认退出按钮
confirmLogoutBtn.addEventListener('click', function() {
    // 显示加载状态
    const logoutBtn = document.querySelector('.btn-danger'); // 改为这个选择器
    originalLogoutBtnText = logoutBtn.innerHTML; // 改为innerHTML以保留图标
    
    // 保存原始HTML内容（包括图标）
    const originalContent = logoutBtn.innerHTML;
    
    logoutBtn.innerHTML = '<span class="icon">⏳</span>退出中...';
    logoutBtn.disabled = true;
    
    // 添加退出动画
    logoutModal.style.opacity = '0.5';
    
    // 延迟跳转，让用户看到加载状态
    setTimeout(() => {
        window.location.href = '../login/logout.php';
    }, 800);
});

// 键盘快捷键支持：ESC关闭弹窗
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && logoutModal.classList.contains('active')) {
        hideLogoutModal();
    }
    // 回车键确认退出
    if (e.key === 'Enter' && logoutModal.classList.contains('active')) {
        confirmLogoutBtn.click();
    }
});

// 编辑/保存状态管理
let isEditing = false;
let hasPasswordChanged = false;

// 编辑按钮点击事件
document.getElementById('editBtn').addEventListener('click', function() {
    if (!isEditing) {
        enterEditMode();
    }
});

// 保存按钮点击事件
document.getElementById('saveBtn').addEventListener('click', function() {
    if (validateForm()) {
        saveChanges();
    }
});

// 取消按钮点击事件
document.getElementById('cancelBtn').addEventListener('click', function() {
    exitEditMode();
});

// 进入编辑模式
function enterEditMode() {
    isEditing = true;
    
    // 切换按钮显示
    document.getElementById('editBtn').style.display = 'none';
    document.getElementById('saveBtn').style.display = 'flex';
    document.getElementById('cancelBtn').style.display = 'flex';
    
    // 显示密码输入框
    document.querySelector('.password-input-group').style.display = 'block';
    document.querySelector('#passwordDisplay').style.display = 'none';
    
    // 创建编辑输入框
    createEditInputs();
}

// 退出编辑模式
function exitEditMode() {
    isEditing = false;
    
    // 切换按钮显示
    document.getElementById('editBtn').style.display = 'flex';
    document.getElementById('saveBtn').style.display = 'none';
    document.getElementById('cancelBtn').style.display = 'none';
    
    // 隐藏密码输入框，清空密码字段
    document.querySelector('.password-input-group').style.display = 'none';
    document.querySelector('#passwordDisplay').style.display = 'inline';
    document.querySelectorAll('.password-input').forEach(input => {
        input.value = '';
    });
    
    // 移除所有编辑输入框
    removeEditInputs();
}

// 创建编辑输入框
function createEditInputs() {
    // 姓名（需要特殊处理，因为名和姓是分开存储的）
    const nameItem = document.querySelector('[data-field="name"]');
    if (nameItem) {
        const infoValue = nameItem.querySelector('.info-value');
        const nameText = infoValue.textContent.trim();
        
        // 查找图标并获取实际文本
        const nameParts = nameText.replace(/[📝👤]/g, '').trim().split(' ');
        
        // 创建容器
        const inputContainer = document.createElement('div');
        inputContainer.className = 'edit-input-container';
        inputContainer.style.cssText = 'display: flex; gap: 10px; margin-top: 5px;';
        
        // 名输入框（假设第一个词是名）
        const firstNameInput = document.createElement('input');
        firstNameInput.type = 'text';
        firstNameInput.name = 'first_name';
        firstNameInput.placeholder = '名';
        firstNameInput.value = nameParts[0] || '';
        firstNameInput.style.cssText = 'flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;';
        
        // 姓输入框（剩余部分是姓）
        const lastNameInput = document.createElement('input');
        lastNameInput.type = 'text';
        lastNameInput.name = 'last_name';
        lastNameInput.placeholder = '姓';
        lastNameInput.value = nameParts.slice(1).join(' ') || '';
        lastNameInput.style.cssText = 'flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;';
        
        inputContainer.appendChild(firstNameInput);
        inputContainer.appendChild(lastNameInput);
        nameItem.appendChild(inputContainer);
    }
    
    // 邮箱 - 直接使用email作为name
    createSimpleInput('email', 'email', '请输入邮箱', 'email');
    
    // 电话 - 直接使用phone作为name
    createSimpleInput('phone', 'tel', '请输入11位手机号', 'phone');
    
    // 联系人
    createSimpleInput('contact_person', 'text', '请输入联系人', 'contact_person');
    
    // 联系地址（使用textarea）
    createTextareaInput('address', 'address', '请输入联系地址');
}

// 创建简单输入框
function createSimpleInput(field, type, placeholder, name = null) {
    const item = document.querySelector(`[data-field="${field}"]`);
    if (item) {
        const infoValue = item.querySelector('.info-value');
        // 获取所有文本节点，排除图标
        const textNodes = Array.from(infoValue.childNodes)
            .filter(node => node.nodeType === Node.TEXT_NODE)
            .map(node => node.textContent.trim())
            .join('');
        
        const input = document.createElement('input');
        input.type = type;
        input.name = name || field;
        input.placeholder = placeholder;
        // 使用纯文本值，移除"未设置"字样
        const cleanValue = textNodes.replace('未设置', '').trim();
        input.value = cleanValue;
        input.style.cssText = 'width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;';
        item.appendChild(input);
    }
}

// 创建文本域输入框
function createTextareaInput(field, name, placeholder) {
    const item = document.querySelector(`[data-field="${field}"]`);
    if (item) {
        const infoValue = item.querySelector('.info-value');
        // 获取所有文本节点，排除图标
        const textNodes = Array.from(infoValue.childNodes)
            .filter(node => node.nodeType === Node.TEXT_NODE)
            .map(node => node.textContent.trim())
            .join('');
        
        const textarea = document.createElement('textarea');
        textarea.name = name;
        textarea.placeholder = placeholder;
        // 使用纯文本值，移除"未设置"字样
        const cleanValue = textNodes.replace('未设置', '').trim();
        textarea.value = cleanValue;
        textarea.style.cssText = 'width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px; min-height: 60px; resize: vertical;';
        item.appendChild(textarea);
    }
}

// 移除所有编辑输入框
function removeEditInputs() {
    const inputs = document.querySelectorAll('.edit-input-container, input, textarea');
    inputs.forEach(input => {
        if (input.name && input.parentNode && input.name !== 'new_password' && input.name !== 'confirm_password') {
            input.parentNode.removeChild(input);
        }
    });
}

// 验证表单
function validateForm() {
    let isValid = true;
    const errors = [];
    
    // 验证姓名
    const firstName = document.querySelector('input[name="first_name"]');
    const lastName = document.querySelector('input[name="last_name"]');
    if (!firstName || !lastName || !firstName.value.trim() || !lastName.value.trim()) {
        errors.push('姓名不能为空');
        isValid = false;
        highlightError(firstName);
        highlightError(lastName);
    }
    
    const email = document.querySelector('input[name="email"]');
    if (email) {
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!email.value.trim()) {
            errors.push('邮箱地址不能为空');
            isValid = false;
            highlightError(email);
        } else if (!emailRegex.test(email.value)) {
            errors.push('邮箱格式不正确，必须包含@和有效的域名后缀（如.com）');
            isValid = false;
            highlightError(email);
        }
    }
    
    // 验证电话
    const phone = document.querySelector('input[name="phone"]');
    if (phone) {
        const phoneRegex = /^1[3-9]\d{9}$/;
        if (!phone.value.trim()) {
            errors.push('联系电话不能为空');
            isValid = false;
            highlightError(phone);
        } else if (!phoneRegex.test(phone.value)) {
            errors.push('电话号码必须是11位有效手机号码');
            isValid = false;
            highlightError(phone);
        }
    }
    
    // 验证联系人
    const contactPerson = document.querySelector('input[name="contact_person"]');
    if (!contactPerson || !contactPerson.value.trim()) {
        errors.push('联系人不能为空');
        isValid = false;
        highlightError(contactPerson);
    }
    
    // 验证地址
    const address = document.querySelector('textarea[name="address"]');
    if (!address || !address.value.trim()) {
        errors.push('联系地址不能为空');
        isValid = false;
        highlightError(address);
    }
    
    // 验证密码
    const newPassword = document.querySelector('input[name="new_password"]');
    const confirmPassword = document.querySelector('input[name="confirm_password"]');
    
    // 检查是否输入了密码
    const hasPasswordInput = newPassword && confirmPassword && 
                            (newPassword.value.trim() || confirmPassword.value.trim());
    
    if (hasPasswordInput) {
        // 如果输入了密码，验证密码
        if (!newPassword.value.trim() || !confirmPassword.value.trim()) {
            errors.push('请输入完整的密码和确认密码');
            isValid = false;
            highlightError(newPassword);
            highlightError(confirmPassword);
        } else if (newPassword.value !== confirmPassword.value) {
            errors.push('两次输入的密码不一致');
            isValid = false;
            highlightError(newPassword);
            highlightError(confirmPassword);
        } else if (newPassword.value.length < 6) {
            errors.push('密码长度至少为6位');
            isValid = false;
            highlightError(newPassword);
        } else {
            hasPasswordChanged = true;
        }
    }
    
    // 显示错误信息
    if (errors.length > 0) {
        showErrorMessages(errors);
    }
    
    return isValid;
}

// 高亮错误字段
function highlightError(element) {
    if (element) {
        element.style.borderColor = '#e74c3c';
        element.style.boxShadow = '0 0 0 2px rgba(231, 76, 60, 0.2)';
        
        // 3秒后清除高亮
        setTimeout(() => {
            element.style.borderColor = '#ddd';
            element.style.boxShadow = 'none';
        }, 3000);
    }
}

// 显示错误信息
function showErrorMessages(errors) {
    // 移除旧的错误信息
    const oldErrors = document.querySelectorAll('.error-message, .error-message-container');
    oldErrors.forEach(error => error.remove());
    
    // 创建错误提示容器 - 使用和PHP相同的类名
    const errorContainer = document.createElement('div');
    errorContainer.className = 'error-message';
    errorContainer.style.cssText = `
        background: #fee;
        border: 1px solid #e74c3c;
        border-radius: 6px;
        padding: 12px 15px;
        margin: 15px 0;
        color: #e74c3c;
        font-size: 14px;
    `;
    
    // 添加错误列表
    const errorList = document.createElement('ul');
    errorList.style.cssText = 'margin: 0; padding-left: 20px;';
    
    errors.forEach(error => {
        const li = document.createElement('li');
        li.textContent = error;
        errorList.appendChild(li);
    });
    
    errorContainer.appendChild(errorList);
    
    // 插入到合适位置
    const cardBody = document.querySelector('.profile-card-body');
    const profileGrid = document.querySelector('.profile-grid');
    
    if (cardBody && profileGrid) {
        cardBody.insertBefore(errorContainer, profileGrid);
    } else if (cardBody) {
        cardBody.insertBefore(errorContainer, cardBody.firstChild);
    }
    
    // 滚动到错误位置
    errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// 保存更改
function saveChanges() {
    if (!validateForm()) {
        return; // 如果前端验证失败，直接返回
    }
    
    // 显示加载状态
    const saveBtn = document.getElementById('saveBtn');
    saveBtn.innerHTML = '<span class="icon">⏳</span>保存中...';
    saveBtn.disabled = true;
    
    // 收集表单数据
    const formData = new URLSearchParams();
    
    // 根据是否修改密码决定提交哪个action
    if (hasPasswordChanged) {
        formData.append('action', 'update_password');
        formData.append('new_password', document.querySelector('input[name="new_password"]').value.trim());
        formData.append('confirm_password', document.querySelector('input[name="confirm_password"]').value.trim());
    } else {
        formData.append('action', 'update_profile');
        
        // 收集所有输入值（排除密码字段）
        const inputs = document.querySelectorAll('input[name]:not([name*="password"]), textarea');
        inputs.forEach(input => {
            if (input.name && input.value !== undefined) {
                formData.append(input.name, input.value.trim());
            }
        });
    }
    
    // 提交到服务器
    fetch('', {  // 空字符串表示提交到当前页面
        method: 'POST',
        body: formData,
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.text())
    .then(html => {
        // 使用DOMParser解析返回的HTML
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        // 检查是否有PHP错误信息
        const errorMessage = doc.querySelector('.error-message');
        
        if (errorMessage) {
            // 有PHP错误，显示错误信息
            const errors = Array.from(errorMessage.querySelectorAll('li')).map(li => li.textContent.trim());
            showErrorMessages(errors);
            saveBtn.innerHTML = '<span class="icon">💾</span>保存修改';
            saveBtn.disabled = false;
        } else {
            // 没有错误，重新加载页面
            location.reload();
        }
    })
    .catch(error => {
        console.error('保存失败:', error);
        saveBtn.innerHTML = '<span class="icon">💾</span>保存修改';
        saveBtn.disabled = false;
        alert('保存失败，请稍后重试');
    });
}

// 添加事件监听器，监控密码输入变化
document.addEventListener('DOMContentLoaded', function() {
    const passwordInputs = document.querySelectorAll('.password-input');
    passwordInputs.forEach(input => {
        input.addEventListener('input', function() {
            // 当密码输入时，设置hasPasswordChanged标志
            const newPassword = document.querySelector('input[name="new_password"]');
            const confirmPassword = document.querySelector('input[name="confirm_password"]');
            
            hasPasswordChanged = (newPassword.value.trim() !== '' || confirmPassword.value.trim() !== '');
        });
    });
});
</script>
</body>
</html>