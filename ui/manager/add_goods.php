<?php
// 这个文件只处理AJAX，不包含任何页面HTML内容
require_once __DIR__ . '/inc/db_connect.php';
require_once __DIR__ . '/inc/data.php';  // 确保data.php已经修正

header('Content-Type: application/json');

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => '非法请求']);
    exit;
}

// 验证action参数
if (!isset($_POST['action']) || $_POST['action'] !== 'add_product') {
    echo json_encode(['success' => false, 'error' => '无效的操作']);
    exit;
}

// 获取表单数据
$name = trim($_POST['name'] ?? '');
$unit = trim($_POST['unit'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$spec = trim($_POST['spec'] ?? '');
$supplier = trim($_POST['supplier'] ?? '');
$description = trim($_POST['description'] ?? '');
$category_id = intval($_POST['category_id'] ?? 0);

// 基础验证
$errors = [];
if (empty($name)) $errors[] = '产品名称不能为空';
if (empty($unit)) $errors[] = '产品单位不能为空';
if ($price < 0) $errors[] = '价格不能为负数';
if ($category_id <= 0) $errors[] = '请选择产品类型';

if ($category_id > 0 && !empty($supplier)) {
    // 获取供应商的类别
    $supplierCategorySql = "SELECT supplier_category FROM Supplier WHERE supplier_ID = ?";
    $supplierStmt = $conn->prepare($supplierCategorySql);
    $supplierStmt->bind_param("i", $supplier);
    $supplierStmt->execute();
    $supplierResult = $supplierStmt->get_result();
    
    if ($supplierRow = $supplierResult->fetch_assoc()) {
        $supplierCategory = $supplierRow['supplier_category'];
        
        // 映射产品类型ID到名称
        $categoryMap = [
            2 => '果蔬',
            3 => '肉禽蛋',
            4 => '水产'
        ];
        
        $productCategory = $categoryMap[$category_id] ?? '';
        
        if ($supplierCategory !== $productCategory) {
            $errors[] = '该供应商不能提供此类型的产品';
        }
    }
    $supplierStmt->close();
}

// 验证分类是否为果蔬(1)、肉禽蛋(2)、水产(3)
$allowed_categories = [2, 3, 4];
if (!in_array($category_id, $allowed_categories)) {
    $errors[] = '请选择正确的产品类型（果蔬、肉禽蛋、水产）';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'error' => implode(',', $errors)]);
    exit;
}

// 调用新增产品函数
$result = addProductToDB($name, $unit, $price, $spec, $supplier, $description, $category_id);

if ($result['success']) {
    echo json_encode([
        'success' => true, 
        'message' => $result['message'],
        'product_id' => $result['product_id'],
        'sku' => $result['sku']
    ]);
} else {
    echo json_encode(['success' => false, 'error' => $result['error']]);
}
exit;
?>