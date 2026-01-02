<?php
// supplier_ajax.php - 处理供应商的AJAX请求
require_once __DIR__ . '/inc/db_connect.php';
require_once __DIR__ . '/inc/data.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'save_supplier') {
            // 保存供应商信息
            $supplierId = intval($_POST['supplier_id'] ?? 0);
            $companyName = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $contactPerson = trim($_POST['contact_person'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $taxNumber = trim($_POST['tax_number'] ?? '');
            $status = trim($_POST['status'] ?? 'active');
            
            try {
                if ($supplierId > 0) {
                    // 更新供应商
                    $sql = "UPDATE Supplier SET 
                            company_name = ?, 
                            supplier_category = ?, 
                            contact_person = ?, 
                            phone = ?, 
                            email = ?, 
                            address = ?, 
                            tax_number = ?, 
                            status = ?,
                            updated_at = NOW()
                            WHERE supplier_ID = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssssssi", 
                        $companyName, $category, $contactPerson, 
                        $phone, $email, $address, $taxNumber, 
                        $status, $supplierId
                    );
                } else {
                    // 新增供应商
                    $sql = "INSERT INTO Supplier 
                            (company_name, supplier_category, contact_person, 
                             phone, email, address, tax_number, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssssss", 
                        $companyName, $category, $contactPerson, 
                        $phone, $email, $address, $taxNumber, $status
                    );
                }
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => '供应商保存成功']);
                } else {
                    echo json_encode(['success' => false, 'error' => '保存失败']);
                }
                $stmt->close();
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            
        } elseif ($action === 'delete_supplier') {
            // 删除供应商
            $supplierId = intval($_POST['supplier_id'] ?? 0);
            
            try {
                $sql = "DELETE FROM Supplier WHERE supplier_ID = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $supplierId);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => '供应商删除成功']);
                } else {
                    echo json_encode(['success' => false, 'error' => '删除失败']);
                }
                $stmt->close();
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => '无效的操作']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => '缺少操作参数']);
    }
} else {
    echo json_encode(['success' => false, 'error' => '非法请求']);
}
?>