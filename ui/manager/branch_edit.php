<?php
session_start();
require_once __DIR__ . '/inc/db_connect.php';
require_once __DIR__ . '/inc/data.php';

$branchId = $_GET['id'] ?? 0;
if (!$branchId) {
    header('Location: stores.php');
    exit;
}

// 获取门店信息
$branch = getBranch($branchId);
if (!$branch) {
    die("Branch not found.");
}

// 处理表单提交
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $branchName = trim($_POST['branch_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $managerId = $_POST['manager_id'] ?? 0;
    $status = $_POST['status'] ?? 'active';

    if (empty($branchName)) {
        $errors[] = "Branch name is required.";
    }
    if (empty($address)) {
        $errors[] = "Address is required.";
    }

    if (empty($errors)) {
        try {
            global $conn;
            $conn->begin_transaction();

            // 更新门店信息
            $sql = "UPDATE Branch SET 
                    branch_name = ?, 
                    address = ?, 
                    phone = ?, 
                    email = ?, 
                    manager_ID = ?,
                    status = ?
                    WHERE branch_ID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssisi", $branchName, $address, $phone, $email, $managerId, $status, $branchId);
            $stmt->execute();

            $conn->commit();
            $success = true;
            $branch = getBranch($branchId); // 刷新数据
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Update failed: " . $e->getMessage();
        }
    }
}

// 获取经理列表
$managers = getAvailableManagers(); // 复用add_branch.php中的函数
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Branch</title>
    <style>
        .error { color: red; }
        .success { color: green; }
        .form-group { margin-bottom: 15px; }
        
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
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #c8e6c9;
            margin: 20px auto;
            max-width: 600px;
            text-align: center;
            font-size: 16px;
            font-weight: 600;
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.15);
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
    <h1>Edit Branch</h1>
    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $error): ?>
            <div class="error"><?= $error ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success">Updated successfully.</div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label>Branch Name *</label>
            <input type="text" name="branch_name" value="<?= htmlspecialchars($branch['branch_name']) ?>" required>
        </div>
        <div class="form-group">
            <label>Address *</label>
            <textarea name="address" required><?= htmlspecialchars($branch['address']) ?></textarea>
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($branch['phone'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($branch['email'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Manager</label>
            <select name="manager_id">
                <option value="0">None</option>
                <?php foreach ($managers as $m): ?>
                    <option value="<?= $m['staff_ID'] ?>" <?= $branch['manager_ID'] == $m['staff_ID'] ? 'selected' : '' ?>>
                        <?= $m['name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="active" <?= $branch['status'] == 'active' ? 'selected' : '' ?>>Open</option>
                <option value="inactive" <?= $branch['status'] == 'inactive' ? 'selected' : '' ?>>Closed</option>
            </select>
        </div>
        <button type="submit">Save</button>
        <a href="stores.php">Cancel</a>
    </form>
</body>
</html>
