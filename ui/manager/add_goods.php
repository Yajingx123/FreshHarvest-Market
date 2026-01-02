<?php
// add_goods.php - 只处理AJAX请求
require_once __DIR__ . '/inc/db_connect.php';
require_once __DIR__ . '/inc/data.php';

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

$sale_quantity = intval($_POST['sale_quantity'] ?? 0); 

$description = trim($_POST['description'] ?? '');
$category_id = intval($_POST['category_id'] ?? 0);

// 基础验证
$errors = [];
if (empty($name)) $errors[] = '产品名称不能为空';
// 验证售卖量
if ($sale_quantity <= 0) {
    $errors[] = '售卖量必须大于0';
}
// 验证单位是否在允许的范围内
$allowed_units = ['g', 'kg', '只', '个', '枚', '斤', '公斤', '盒', '包', '袋'];
if (!in_array($unit, $allowed_units)) {
    $errors[] = '请选择有效的单位';
}
if ($price < 0) $errors[] = '价格不能为负数';
if ($category_id <= 0) $errors[] = '请选择产品类型';

// 验证分类和供应商匹配
if ($category_id > 0 && !empty($supplier)) {
    // 1. 先获取供应商能提供的品类（第二级父类）
    $supplierCategorySql = "SELECT supplier_category FROM Supplier WHERE supplier_ID = ?";
    $supplierStmt = $conn->prepare($supplierCategorySql);
    $supplierStmt->bind_param("i", $supplier);
    $supplierStmt->execute();
    $supplierResult = $supplierStmt->get_result();
    
    if ($supplierRow = $supplierResult->fetch_assoc()) {
        $supplierCategory = $supplierRow['supplier_category'];
        
        // 2. 根据选中的第三级分类ID找到其第二级父类
        $categorySql = "SELECT 
                            c3.category_id as third_id,
                            c3.category_name as third_name,
                            c2.category_id as second_id,
                            c2.category_name as second_name
                        FROM Categories c3
                        LEFT JOIN Categories c2 ON c3.parent_category_id = c2.category_id
                        WHERE c3.category_id = ?";
        $categoryStmt = $conn->prepare($categorySql);
        $categoryStmt->bind_param("i", $category_id);
        $categoryStmt->execute();
        $categoryResult = $categoryStmt->get_result();
        
        if ($categoryRow = $categoryResult->fetch_assoc()) {
            $productParentCategory = $categoryRow['second_name'];
            
            // 3. 比较供应商能提供的品类（第二级）与产品的父类（第二级）
            if ($supplierCategory !== $productParentCategory) {
                $errors[] = "该供应商（提供：{$supplierCategory}）不能提供此类型的产品（属于：{$productParentCategory}）";
            }
        } else {
            $errors[] = '无效的产品分类';
        }
        $categoryStmt->close();
    }
    $supplierStmt->close();
}

// 验证分类范围
$thirdLevelCategoriesSql = "SELECT category_id FROM Categories WHERE parent_category_id IN (
    SELECT category_id FROM Categories WHERE parent_category_id IS NOT NULL
)";
$thirdLevelResult = $conn->query($thirdLevelCategoriesSql);
$allowed_categories = [];
while ($row = $thirdLevelResult->fetch_assoc()) {
    $allowed_categories[] = $row['category_id'];
}

if (!in_array($category_id, $allowed_categories)) {
    $errors[] = '请选择正确的产品类型（果蔬、肉禽蛋、水产）';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
    exit;
}

// 调用新增产品函数
$result = addProductToDB($name, $unit, $price, $spec, $supplier, $description, $category_id,$sale_quantity);

// 返回JSON结果
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