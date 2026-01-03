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
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            margin: 0;
            padding: 20px;
            font-family: 'Microsoft YaHei', Arial, sans-serif;
            min-height: 100vh;
        }
        h1 {
            color: #1976d2;
            text-align: center;
            margin: 30px 0;
            font-size: 28px;
            font-weight: 600;
            padding-bottom: 15px;
            border-bottom: 2px solid #1976d2;
        }
        .error {
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            color: #d32f2f;
            padding: 15px 20px;
            border-radius: 8px;
            border-left: 5px solid #d32f2f;
            margin: 20px auto;
            max-width: 600px;
            box-shadow: 0 4px 12px rgba(211, 47, 47, 0.15);
        }
        .success {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            color: #2e7d32;
            padding: 25px;
            border-radius: 10px;
            border: 2px solid #c8e6c9;
            margin: 30px auto;
            max-width: 600px;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.15);
        }
        .success a {
            display: inline-block;
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            margin-top: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(25, 118, 210, 0.2);
        }
        .success a:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(25, 118, 210, 0.3);
        }
        form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }
        .form-group {
            margin-bottom: 25px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #444;
            font-size: 14px;
        }
        input, textarea, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #1976d2;
            box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
        }
        input[required], textarea[required] {
            background-color: #f8fdff;
            border-left: 3px solid #1976d2;
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        button[type="submit"] {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(25, 118, 210, 0.2);
            display: block;
            width: 100%;
            margin-top: 10px;
        }
        button[type="submit"]:hover {
            background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(25, 118, 210, 0.3);
        }
        a[href="stores.php"] {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 14px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            text-align: center;
            margin-top: 15px;
            width: 91%;
            transition: all 0.3s ease;
        }
        a[href="stores.php"]:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            form {
                padding: 20px;
            }
            h1 {
                font-size: 24px;
                margin: 20px 0;
            }
        }
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
        <div class="success">
            门店新增成功！
            <br>
            <a href="stores.php">返回门店列表</a>
        </div>
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