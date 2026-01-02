<?php
// file path: login/logout.php

session_start();

// 根据用户角色显示不同的退出消息
$role = $_SESSION['user_role'] ?? '';
$message = '';

switch ($role) {
    case 'customer':
        $message = '顾客账户已安全退出';
        break;
    case 'staff':
        $message = '员工账户已安全退出';
        break;
    case 'CEO':
        $message = '经理账户已安全退出';
        break;
    case 'supplier':
        $message = '供应商账户已安全退出';
        break;
    default:
        $message = '已安全退出登录';
}

// 清除所有会话变量
$_SESSION = array();

// 如果需要彻底销毁会话，同时删除会话cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 销毁会话
session_destroy();

// 开启新会话，仅存储退出消息
session_start();
$_SESSION['logout_success'] = $message;

// 重定向到登录页面
header('Location: login.php');
exit();
?>