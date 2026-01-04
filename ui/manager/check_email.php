<?php
require_once __DIR__ . '/inc/data.php';

header('Content-Type: application/json');

if (isset($_GET['email'])) {
    $email = trim($_GET['email']);
    $excludeId = isset($_GET['exclude_id']) ? intval($_GET['exclude_id']) : 0;
    
    $exists = isEmailExists($email, $excludeId);
    
    echo json_encode([
        'success' => true,
        'exists' => $exists,
        'email' => $email
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => '邮箱参数缺失'
    ]);
}
?>