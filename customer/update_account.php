<?php
session_start();
require_once __DIR__ . '/inc/data.php';


$data = json_decode(file_get_contents('php://input'), true);

// 2. 必须校验用户ID（核心，用于定位用户）
if (!isset($data['customerId']) || empty($data['customerId'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing customer ID.']);
    exit;
}
$customerId = $data['customerId'];


// 2. 获取各个输入框的数据
$username = $data['username'] ?? null;       // 用户名
$gender = $data['gender'] ?? null;           // 性别
$phone = $data['phone'] ?? null;             // 手机号码
$email = $data['email'] ?? null;             // 电子邮箱
$address = $data['address'] ?? null;         // 地址
$loyaltyLevel = $data['loyalty_level'] ?? null; // 会员等级（不可编辑）

// 数据验证
$errors = [];

// 用户名验证（不重复）
if (checkUsernameExists($data['username'], $customer_id)) {
    $errors[] = 'Username already exists.';
}

// 性别验证
if (!in_array($data['gender'], ['Male', 'Female'])) {
    $errors[] = 'Gender must be Male or Female.';
}

if (empty($phone)) {
    $errors[] = '电话号码不能为空';
} 
// 2. 检查是否全是数字
elseif (!preg_match('/^\d+$/', $phone)) {
    $errors[] = '电话号码只能包含数字，不能包含字母或其他字符';
} 
// 3. 检查是否为11位
elseif (strlen($phone) !== 11) {
    $errors[] = '电话号码必须是11位数字';
}

// 邮箱验证
if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.com$/', $data['email'])) {
    $errors[] = 'Email must include @ and end with .com.';
}

// 地址验证
if (strlen($data['address']) > 200) {
    $errors[] = 'Address cannot exceed 200 characters.';
}

// 如果有错误，返回错误信息
if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => implode(': ', $errors)]);
    exit;
}

// 准备更新数据（排除不能修改的loyalty_level）
$updateData = [];
if ($username !== null && $username !== '') $updateData['username'] = $username;
if ($gender !== null && $gender !== '') $updateData['gender'] = $gender;
if ($phone !== null && $phone !== '') $updateData['phone'] = $phone;
if ($email !== null && $email !== '') $updateData['email'] = $email;
if ($address !== null && $address !== '') $updateData['address'] = $address;

// 执行更新
if (updateCustomerInfo($customer_id, $updateData)) {
    echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Update failed. Please try again.']);
}
?>
