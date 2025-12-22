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
$branch = getBranch($branchId); // 复用branch_edit.php中的函数
if (!$branch) {
    die("门店不存在");
}

// 处理添加员工
if (isset($_POST['add_staff'])) {
    $staffId = $_POST['staff_id'] ?? 0;
    if ($staffId) {
        try {
            global $conn;
            $sql = "UPDATE Staff SET branch_ID = ? WHERE staff_ID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $branchId, $staffId);
            $stmt->execute();
        } catch (Exception $e) {
            $error = "添加失败: " . $e->getMessage();
        }
    }
}

// 处理移除员工
if (isset($_GET['remove_staff'])) {
    $staffId = $_GET['remove_staff'];
    try {
        global $conn;
        $sql = "UPDATE Staff SET branch_ID = NULL WHERE staff_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $staffId);
        $stmt->execute();
    } catch (Exception $e) {
        $error = "移除失败: " . $e->getMessage();
    }
}

$staffList = getBranchStaff($branchId);
$availableStaff = getAvailableStaff($branchId);
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= $branch['branch_name'] ?> - 员工管理</title>
</head>
<body>
    <h1><?= $branch['branch_name'] ?> - 员工管理</h1>
    <a href="stores.php">返回门店列表</a>
    
    <?php if (isset($error)): ?>
        <div style="color:red;"><?= $error ?></div>
    <?php endif; ?>

    <h3>当前员工</h3>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>姓名</th>
            <th>职位</th>
            <th>状态</th>
            <th>邮箱</th>
            <th>电话</th>
            <th>操作</th>
        </tr>
        <?php foreach ($staffList as $s): ?>
        <tr>
            <td><?= $s['staff_ID'] ?></td>
            <td><?= $s['name'] ?></td>
            <td><?= $s['position'] ?></td>
            <td><?= $s['status'] ?></td>
            <td><?= $s['user_email'] ?></td>
            <td><?= $s['phone'] ?></td>
            <td>
                <a href="?id=<?= $branchId ?>&remove_staff=<?= $s['staff_ID'] ?>" 
                   onclick="return confirm('确定移除?')">移除</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h3>添加员工到门店</h3>
    <form method="post">
        <select name="staff_id">
            <?php foreach ($availableStaff as $s): ?>
                <option value="<?= $s['staff_ID'] ?>"><?= $s['name'] ?> (<?= $s['position'] ?>)</option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="add_staff">添加</button>
    </form>
</body>
</html>