<?php
// file path: login/logout.php

session_start();
require_once __DIR__ . '/../config/db_connect.php';

// 保存当前用户的角色信息用于显示消息
$current_role = $_SESSION['user_role'] ?? '';
$current_user_id = $_SESSION['user_id'] ?? null;
$current_session_id = session_id();
$message = '';

switch ($current_role) {
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

if (!empty($current_user_id)) {
    $conn = getDBConnection();
    if ($conn) {
        $updateSql = "UPDATE User SET login_session_id = NULL WHERE user_ID = ? AND login_session_id = ?";
        $stmt = $conn->prepare($updateSql);
        if ($stmt) {
            $stmt->bind_param("is", $current_user_id, $current_session_id);
            $stmt->execute();
            $stmt->close();
        }
        $conn->close();
    }
}

$_SESSION = array();

session_destroy();

if (isset($_GET['beacon']) && $_GET['beacon'] === '1') {
    http_response_code(204);
    exit();
}

header('Location: login.php?logout=' . urlencode($message));
exit();
?>
