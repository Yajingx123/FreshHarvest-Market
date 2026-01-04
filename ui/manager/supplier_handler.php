<?php
require_once __DIR__ . '/inc/db_connect.php';
require_once __DIR__ . '/inc/data.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'check_supplier_email_duplicate') {
        $email = $_POST['email'] ?? '';
        $supplierId = intval($_POST['supplier_id'] ?? 0);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['exists' => false, 'message' => 'Invalid email format']);
            exit;
        }
        
        $exists = isSupplierEmailExists($email, $supplierId);
        
        echo json_encode([
            'exists' => $exists,
            'message' => $exists ? 'Email already in use' : 'Email available'
        ]);
        exit;
    }
}