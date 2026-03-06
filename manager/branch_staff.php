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
    die("Branch not found.");
}

// 处理添加员工
if (isset($_POST['add_staff'])) {
    $staffId = $_POST['staff_id'] ?? 0;
    if ($staffId) {
        try {
            global $conn;
            // 第一步：查询该员工的职位和原门店
            $checkInfoSql = "SELECT position, branch_ID FROM Staff WHERE staff_ID = ?";
            $checkInfoStmt = $conn->prepare($checkInfoSql);
            $checkInfoStmt->bind_param("i", $staffId);
            $checkInfoStmt->execute();
            $infoResult = $checkInfoStmt->get_result();
            $staffInfo = $infoResult->fetch_assoc();
            $checkInfoStmt->close();

            $position = $staffInfo['position'] ?? '';
            $oldBranchId = $staffInfo['branch_ID'] ?? null;

            // 第二步：如果是经理，处理原门店的经理信息
            if ($position === 'Manager' && $oldBranchId) {
                $checkOtherManagerSql = "SELECT COUNT(*) as manager_count FROM Staff 
                                       WHERE branch_ID = ? 
                                       AND position = 'Manager' 
                                       AND staff_ID != ?";
                $checkOtherStmt = $conn->prepare($checkOtherManagerSql);
                $checkOtherStmt->bind_param("ii", $oldBranchId, $staffId);
                $checkOtherStmt->execute();
                $countResult = $checkOtherStmt->get_result();
                $countData = $countResult->fetch_assoc();
                $checkOtherStmt->close();
    
                $otherManagerCount = $countData['manager_count'] ?? 0;
                if ($otherManagerCount == 0) {
                    $clearManagerSql = "UPDATE Branch SET manager_name = NULL, manager_phone = NULL, manager_ID = NULL WHERE branch_ID = ?";
                    $clearStmt = $conn->prepare($clearManagerSql);
                    $clearStmt->bind_param("i", $oldBranchId);
                    $clearStmt->execute();
                    $clearStmt->close();
                }
            }
            
            if ($position === 'Manager') {
                $updateNewBranchSql = "UPDATE Branch SET manager_ID = ? WHERE branch_ID = ?";
                $updateNewStmt = $conn->prepare($updateNewBranchSql);
                $updateNewStmt->bind_param("ii", $staffId, $branchId);
                $updateNewStmt->execute();
                $updateNewStmt->close();
        
                if ($oldBranchId) {
                    $findRemainingManagerSql = "SELECT staff_ID FROM Staff 
                                           WHERE branch_ID = ? 
                                           AND position = 'Manager' 
                                           AND staff_ID != ?
                                           LIMIT 1";
                    $findStmt = $conn->prepare($findRemainingManagerSql);
                    $findStmt->bind_param("ii", $oldBranchId, $staffId);
                    $findStmt->execute();
                    $findResult = $findStmt->get_result();
            
                    if ($findResult->num_rows > 0) {
                        $newManagerData = $findResult->fetch_assoc();
                        $newManagerId = $newManagerData['staff_ID'];
                
                        $updateOldBranchSql = "UPDATE Branch SET manager_ID = ? WHERE branch_ID = ?";
                        $updateOldStmt = $conn->prepare($updateOldBranchSql);
                        $updateOldStmt->bind_param("ii", $newManagerId, $oldBranchId);
                        $updateOldStmt->execute();
                        $updateOldStmt->close();
                    } else {
                        // 没有其他经理了，清空原门店的经理信息
                        $clearManagerSql = "UPDATE Branch SET manager_name = NULL, manager_phone = NULL, manager_ID = NULL WHERE branch_ID = ?";
                        $clearStmt = $conn->prepare($clearManagerSql);
                        $clearStmt->bind_param("i", $oldBranchId);
                        $clearStmt->execute();
                        $clearStmt->close();
                    }
                    $findStmt->close();
                }
            }
    
            // 3. 更新Staff表的branch_ID
            $sql = "UPDATE Staff SET branch_ID = ? WHERE staff_ID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $branchId, $staffId);
            $stmt->execute();
    
            if ($stmt->affected_rows > 0) {
                $success = "Staff assigned successfully.";
            } else {
                $error = "Assignment failed.";
            }
        } catch (Exception $e) {
            $error = "Add failed: " . $e->getMessage();
        }
    }
}

// 处理移除员工
// 处理移除员工
if (isset($_GET['remove_staff'])) {
    $staffId = $_GET['remove_staff'];
    try {
        global $conn;
        
        // 1. 先检查要移除的员工是否是经理
        $checkManagerSql = "SELECT position, branch_ID FROM Staff WHERE staff_ID = ?";
        $checkStmt = $conn->prepare($checkManagerSql);
        $checkStmt->bind_param("i", $staffId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $staffInfo = $result->fetch_assoc();
        $checkStmt->close();
        
        $isManager = ($staffInfo['position'] === 'Manager');
        $branchId = $staffInfo['branch_ID'];
        
        // 2. 移除员工（将其 branch_ID 设为 NULL）
        $sql = "UPDATE Staff SET branch_ID = NULL WHERE staff_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $staffId);
        $stmt->execute();
        $stmt->close();
        
        // 3. 如果是经理，需要处理门店的经理信息
        if ($isManager && $branchId) {
            // 检查门店是否还有其他经理
            $checkOtherManagerSql = "SELECT staff_ID FROM Staff 
                                   WHERE branch_ID = ? 
                                   AND position = 'Manager' 
                                   AND staff_ID != ? 
                                   LIMIT 1";
            $checkOtherStmt = $conn->prepare($checkOtherManagerSql);
            $checkOtherStmt->bind_param("ii", $branchId, $staffId);
            $checkOtherStmt->execute();
            $otherResult = $checkOtherStmt->get_result();
            
            if ($otherResult->num_rows > 0) {
                // 还有其他经理，更新为新经理
                $newManagerData = $otherResult->fetch_assoc();
                $newManagerId = $newManagerData['staff_ID'];
                
                $updateManagerSql = "UPDATE Branch SET manager_ID = ? WHERE branch_ID = ?";
                $updateStmt = $conn->prepare($updateManagerSql);
                $updateStmt->bind_param("ii", $newManagerId, $branchId);
                $updateStmt->execute();
                $updateStmt->close();
            } else {
                // 没有其他经理了，清空经理信息
                $clearManagerSql = "UPDATE Branch SET manager_ID = NULL WHERE branch_ID = ?";
                $clearStmt = $conn->prepare($clearManagerSql);
                $clearStmt->bind_param("i", $branchId);
                $clearStmt->execute();
                $clearStmt->close();
            }
            $checkOtherStmt->close();
        }
        
        $success = "Staff removed successfully.";
        
    } catch (Exception $e) {
        $error = "Remove failed: " . $e->getMessage();
    }
}

$staffList = getBranchStaff($branchId);
$availableStaff = getAvailableStaff($branchId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= $branch['branch_name'] ?> - Staff</title>
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
        a[href="stores.php"] {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 10px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            margin: 20px 0;
            transition: all 0.3s ease;
        }
        a[href="stores.php"]:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        div[style*="color:red;"] {
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            color: #d32f2f;
            padding: 15px 20px;
            border-radius: 8px;
            border-left: 5px solid #d32f2f;
            margin: 20px auto;
            max-width: 800px;
            box-shadow: 0 4px 12px rgba(211, 47, 47, 0.15);
        }
        h3 {
            color: #333;
            margin: 25px 0 15px 0;
            font-size: 20px;
            font-weight: 600;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        table th {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            color: white;
            padding: 14px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        table td {
            padding: 12px 14px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }
        table tr:hover {
            background-color: #f5f9ff;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        table tr:nth-child(even):hover {
            background-color: #f5f9ff;
        }
        a[href*="remove_staff"] {
            color: #d32f2f;
            text-decoration: none;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 4px;
            border: 1px solid #ffcdd2;
            transition: all 0.3s ease;
        }
        a[href*="remove_staff"]:hover {
            background-color: #ffebee;
        }
        form {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 20px auto;
            border: 1px solid #e0e0e0;
        }
        select {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            width: 300px;
            margin-right: 15px;
        }
        select:focus {
            outline: none;
            border-color: #1976d2;
            box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
        }
        button[name="add_staff"] {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(25, 118, 210, 0.2);
        }
        button[name="add_staff"]:hover {
            background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(25, 118, 210, 0.3);
        }
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            table {
                font-size: 12px;
            }
            table th, table td {
                padding: 10px;
            }
            form {
                padding: 20px;
            }
            select {
                width: 100%;
                margin-bottom: 15px;
            }
            button[name="add_staff"] {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <h1><?= $branch['branch_name'] ?> - Staff</h1>
    <a href="stores.php">Back to stores</a>
    
    <?php if (isset($error)): ?>
        <div style="color:red;"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div style="color:green; background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); padding: 15px 20px; border-radius: 8px; margin: 20px auto; max-width: 800px; border-left: 5px solid #2e7d32;">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <h3>Current Staff</h3>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Role</th>
            <th>Status</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($staffList as $s): ?>
        <tr>
            <td><?= $s['staff_ID'] ?></td>
            <td><?= $s['name'] ?></td>
            <td><?= $s['position'] ?></td>
            <td>
                <?php
                $statusMap = [
                    'active' => 'Active',
                    'on_leave' => 'On leave',
                    'terminated' => 'Terminated',
                    '在职' => 'Active',
                    '休假' => 'On leave',
                    '离职' => 'Terminated'
                ];
                echo htmlspecialchars($statusMap[$s['status']] ?? $s['status']);
                ?>
            </td>
            <td><?= $s['user_email'] ?></td>
            <td><?= $s['phone'] ?></td>
            <td>
                <a href="?id=<?= $branchId ?>&remove_staff=<?= $s['staff_ID'] ?>" 
                    onclick="return confirm('Remove this staff member?')">Remove</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h3>Add Staff to Branch</h3>
    <form method="post">
        <select name="staff_id">
            <?php foreach ($availableStaff as $s): ?>
                <option value="<?= $s['staff_ID'] ?>"><?= $s['name'] ?> (<?= $s['position'] ?>)</option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="add_staff">Add</button>
    </form>
    <script>
</script>
</body>
</html>
