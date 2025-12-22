<?php
session_start();
// 引入数据库连接
require_once __DIR__ . '/inc/db_connect.php';
require_once __DIR__ . '/inc/data.php';

// 处理表单提交
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $branchName = trim($_POST['branch_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $managerId = $_POST['manager_id'] ?? 0;

    // 验证
    if (empty($branchName)) {
        $errors[] = "门店名称不能为空";
    }
    if (empty($address)) {
        $errors[] = "门店地址不能为空";
    }

    if (empty($errors)) {
        try {
            global $conn;
            $conn->begin_transaction();

            // 插入门店数据
            // 插入门店数据前先查询经理电话
            $managerPhone = '';
            if ($managerId > 0) {
               // 查询经理的联系电话
               $phoneSql = "SELECT phone FROM Staff WHERE staff_ID = ?";
               $phoneStmt = $conn->prepare($phoneSql);
               $phoneStmt->bind_param("i", $managerId);
               $phoneStmt->execute();
               $phoneResult = $phoneStmt->get_result();
    
               if ($phoneResult->num_rows > 0) {
                  $managerData = $phoneResult->fetch_assoc();
                  $managerPhone = $managerData['phone'] ?? '';
                }
}
            $sql = "INSERT INTO Branch (branch_name, address, phone, email, manager_ID, manager_phone, status)
                VALUES (?, ?, ?, ?, ?, ?, 'active')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssis", $branchName, $address, $phone, $email, $managerId, $managerPhone);
            $stmt->execute();

            // 如果设置了经理，更新员工的所属门店
            if ($managerId > 0) {
                $newBranchId = $conn->insert_id;
                $updateSql = "UPDATE Staff SET branch_ID = ? WHERE staff_ID = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("ii", $newBranchId, $managerId);
                $updateStmt->execute();
            }

            $conn->commit();
            $success = true;
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "新增失败: " . $e->getMessage();
        }
    }
}



$managers = getAvailableManagers();
?>

<!DOCTYPE html>
<html>
<head>
    <title>新增门店</title>
    <style>
        .error { color: red; }
        .success { color: green; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, textarea, select { width: 300px; padding: 8px; }
    </style>
</head>
<body>
    <h1>新增门店</h1>
    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $error): ?>
            <div class="error"><?= $error ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success">门店新增成功！</div>
        <a href="stores.php">返回门店列表</a>
    <?php else: ?>
        <form method="post">
            <div class="form-group">
                <label>门店名称 *</label>
                <input type="text" name="branch_name" required>
            </div>
            <div class="form-group">
                <label>地址 *</label>
                <textarea name="address" required></textarea>
            </div>
            <div class="form-group">
                <label>联系电话</label>
                <input type="text" name="phone">
            </div>
            <div class="form-group">
                <label>邮箱</label>
                <input type="email" name="email">
            </div>
            <div class="form-group">
                <label>门店经理</label>
                <select name="manager_id">
                    <option value="0">无</option>
                    <?php foreach ($managers as $m): ?>
                        <option value="<?= $m['staff_ID'] ?>"><?= $m['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">保存</button>
            <a href="stores.php">取消</a>
        </form>
    <?php endif; ?>
</body>
</html>