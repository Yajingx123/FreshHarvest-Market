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
        'message' => 'Missing required parameters.'
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
        'message' => 'All fields are required.'
    ]);
    exit();
}

if ($newPassword !== $confirmPassword) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Passwords do not match.'
    ]);
    exit();
}

if (strlen($newPassword) < 6) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Password must be at least 6 characters.'
    ]);
    exit();
}

if ($newPassword === $currentPassword) {
    echo json_encode([
        'status' => 'error',
        'message' => 'New password must differ from current.'
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
            'message' => 'User not found.'
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
            'message' => 'Password updated successfully.'
        ]);
        
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Password update failed. Please try again.'
        ]);
    }
    
    $updateStmt->close();
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
