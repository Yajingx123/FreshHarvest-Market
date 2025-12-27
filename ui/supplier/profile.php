<?php
// 供应商端 - 供货商信息（含退出按钮版，带订单红点提示）
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
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>鲜选生鲜 - 供应商端-供货商信息</title>
    <style>
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
        .profile-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }
        .profile-group {
            margin-bottom: 25px;
        }
        .profile-label {
            display: block;
            font-weight: 500;
            color: #666;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .profile-value {
            font-size: 16px;
            padding: 10px 15px;
            background-color: #f5f9ff;
            border-radius: 6px;
            border: 1px solid #e3f2fd;
        }
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-primary {
            background-color: #1976d2;
            color: white;
        }
        .btn-primary:hover {
            background-color: #1565c0;
        }
        .btn-danger {
            background-color: #e53935;
            color: white;
        }
        .btn-danger:hover {
            background-color: #d32f2f;
        }
        .footer {
            background-color: #333;
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }
        .footer-container {
            width: 90%;
            margin: 0 auto;
            text-align: center;
        }
        .copyright {
            margin-top: 20px;
            font-size: 14px;
            color: #999;
        }
        /* 红点提示样式 */
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background-color: #e53935;
            color: white;
            font-size: 12px;
            font-weight: bold;
            margin-left: 5px;
            padding: 0;
            line-height: 1;
        }
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            .profile-card {
                padding: 20px;
            }
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <h2 class="profile-title">供应商信息</h2>
        <?php if (!empty($supplierInfo)): ?>
            <div class="profile-item">
                <span class="profile-label">用户名：</span>
                <span><?php echo htmlspecialchars($supplierInfo['user_name']); ?></span>
            </div>
            <div class="profile-item">
                <span class="profile-label">姓名：</span>
                <span><?php echo htmlspecialchars($supplierInfo['first_name'] . ' ' . $supplierInfo['last_name']); ?></span>
            </div>
            <div class="profile-item">
                <span class="profile-label">邮箱：</span>
                <span><?php echo htmlspecialchars($supplierInfo['user_email']); ?></span>
            </div>
            <div class="profile-item">
                <span class="profile-label">电话：</span>
                <span><?php echo htmlspecialchars($supplierInfo['user_telephone'] ?? '未设置'); ?></span>
            </div>
            <div class="profile-item">
                <span class="profile-label">供应商名称：</span>
                <span><?php echo htmlspecialchars($supplierInfo['user_name'] ?? '未设置'); ?></span>
            </div>
            <div class="profile-item">
                <span class="profile-label">联系人：</span>
                <span><?php echo htmlspecialchars($supplierInfo['contact_person'] ?? '未设置'); ?></span>
            </div>
            <div class="profile-item">
                <span class="profile-label">地址：</span>
                <span><?php echo htmlspecialchars($supplierInfo['address'] ?? '未设置'); ?></span>
            </div>
            <div class="profile-item">
                <span class="profile-label">账号状态：</span>
                <span><?php echo htmlspecialchars($supplierInfo['status'] ?? 'active'); ?></span>
            </div>
            <div class="profile-item">
                <span class="profile-label">注册时间：</span>
                <span><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($supplierInfo['created_at']))); ?></span>
            </div>
            <div class="profile-item">
                <span class="profile-label">最后登录：</span>
                <span><?php echo $supplierInfo['last_login'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($supplierInfo['last_login']))) : '从未登录'; ?></span>
            </div>
            <button class="logout-btn" onclick="location.href='profile.php?action=logout'">退出登录</button>
        <?php else: ?>
            <p>未获取到供应商信息，请先登录</p>
            <button class="logout-btn" onclick="location.href='login.php'">前往登录</button>
        <?php endif; ?>
    </div>
</body>
</html>