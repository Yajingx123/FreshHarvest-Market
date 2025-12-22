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
    die("门店不存在");
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
        $errors[] = "门店名称不能为空";
    }
    if (empty($address)) {
        $errors[] = "门店地址不能为空";
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
            $errors[] = "更新失败: " . $e->getMessage();
        }
    }
}

// 获取经理列表
$managers = getAvailableManagers(); // 复用add_branch.php中的函数
?>

<!DOCTYPE html>
<html>
<head>
    <title>编辑门店</title>
    <style>
        .error { color: red; }
        .success { color: green; }
        .form-group { margin-bottom: 15px; }
    </style>
</head>
<body>
    <h1>编辑门店</h1>
    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $error): ?>
            <div class="error"><?= $error ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success">更新成功！</div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label>门店名称 *</label>
            <input type="text" name="branch_name" value="<?= htmlspecialchars($branch['branch_name']) ?>" required>
        </div>
        <div class="form-group">
            <label>地址 *</label>
            <textarea name="address" required><?= htmlspecialchars($branch['address']) ?></textarea>
        </div>
        <div class="form-group">
            <label>联系电话</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($branch['phone'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>邮箱</label>
            <input type="email" name="email" value="<?= htmlspecialchars($branch['email'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>门店经理</label>
            <select name="manager_id">
                <option value="0">无</option>
                <?php foreach ($managers as $m): ?>
                    <option value="<?= $m['staff_ID'] ?>" <?= $branch['manager_ID'] == $m['staff_ID'] ? 'selected' : '' ?>>
                        <?= $m['name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>状态</label>
            <select name="status">
                <option value="active" <?= $branch['status'] == 'active' ? 'selected' : '' ?>>正常</option>
                <option value="inactive" <?= $branch['status'] == 'inactive' ? 'selected' : '' ?>>关闭</option>
            </select>
        </div>
        <button type="submit">保存</button>
        <a href="stores.php">取消</a>
    </form>
</body>
</html>