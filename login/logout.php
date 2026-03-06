
<?php
session_start();

// 只清除当前用户的session变量，而不是全部
$user_role = $_SESSION['user_role'] ?? '';

if ($user_role === 'customer') {
    unset($_SESSION['customer_logged_in'], $_SESSION['customer_id'], 
          $_SESSION['customer_username'], $_SESSION['user_role']);
} elseif ($user_role === 'staff') {
    unset($_SESSION['staff_logged_in'], $_SESSION['staff_id'], 
          $_SESSION['staff_branch_id'], $_SESSION['staff_username'], $_SESSION['user_role']);
} elseif ($user_role === 'CEO') {
    unset($_SESSION['manager_logged_in'], $_SESSION['manager_id'], 
          $_SESSION['manager_username'], $_SESSION['user_role']);
} elseif ($user_role === 'supplier') {
    unset($_SESSION['supplier_logged_in'], $_SESSION['supplier_id'], 
          $_SESSION['supplier_username'], $_SESSION['user_role']);
}

// Set logout message
$roleLabels = [
    'customer' => 'Customer',
    'staff' => 'Staff',
    'CEO' => 'Manager',
    'supplier' => 'Supplier'
];
$roleLabel = $roleLabels[$user_role] ?? 'User';
$_SESSION['logout_success'] = $roleLabel . ' account signed out successfully.';

// 重定向到登录页面
header('Location: login.php');
exit();
?>
