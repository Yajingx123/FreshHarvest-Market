<?php
require_once 'inc/data.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'get_store_stock') {
        $productId = $_POST['product_id'] ?? 0;
        $storeId = $_POST['store_id'] ?? 0;
        
        if ($productId > 0 && $storeId > 0) {
            $stockInfo = getProductStockInStore($productId, $storeId);
            
            echo json_encode([
                'success' => true,
                'stock_info' => $stockInfo
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid parameters.'
            ]);
        }
        exit;
    }
}

echo json_encode([
    'success' => false,
    'message' => 'Invalid request.'
]);
?>
