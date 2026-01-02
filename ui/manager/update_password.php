<?php
session_start();
require_once __DIR__ . '/inc/db_connect.php';
require_once __DIR__ . '/inc/data.php';

header('Content-Type: application/json');
// 获取 POST 数据
$data = json_decode(file_get_contents('php://input'), true);

// 验证输入
if (!isset($data['current_password']) || !isset($data['new_password']) || !isset($data['confirm_password'])) {
    echo json_encode([
        'status' => 'error',
        'message' => '缺少必要参数'
    ]);
    exit();
}

$currentPassword = trim($data['current_password']);
$newPassword = trim($data['new_password']);
$confirmPassword = trim($data['confirm_password']);
$userId = $_SESSION['manager_id'];


// 基本验证
if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    echo json_encode([
        'status' => 'error',
        'message' => '所有字段都必须填写'
    ]);
    exit();
}

if ($newPassword !== $confirmPassword) {
    echo json_encode([
        'status' => 'error',
        'message' => '两次输入的密码不一致'
    ]);
    exit();
}

if (strlen($newPassword) < 6) {
    echo json_encode([
        'status' => 'error',
        'message' => '密码长度不能少于6位'
    ]);
    exit();
}

if ($newPassword === $currentPassword) {
    echo json_encode([
        'status' => 'error',
        'message' => '新密码不能与当前密码相同'
    ]);
    exit();
}
try {
    // 查询数据库验证当前密码
    $sql = "SELECT password_hash FROM User WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => '用户不存在'
        ]);
        exit();
    }
    
    $user = $result->fetch_assoc();
        
    // 更新密码
    $hashedNewPassword = md5($newPassword);
    
    $updateSql = "UPDATE User SET password_hash = ? WHERE user_ID = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("si", $hashedNewPassword, $userId);
    
    if ($updateStmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => '密码修改成功'
        ]);
        
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => '密码修改失败，请稍后重试'
        ]);
    }
    
    $updateStmt->close();
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => '服务器错误：' . $e->getMessage()
    ]);
}

$conn->close();
?>