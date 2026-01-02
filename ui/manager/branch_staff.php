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
                $success = "员工分配成功";
            } else {
                $error = "分配失败";
            }
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
        
        $success = "员工移除成功";
        
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
        /* 弹窗按钮悬停效果 */
#confirmCancelBtn:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

#confirmOkBtn:hover {
    background: #c62828;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(211, 47, 47, 0.2);
}

/* 动画效果 */
#customConfirmModal > div {
    animation: modalFadeIn 0.3s ease;
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
/* 移除链接样式 */
a[onclick*="showRemoveConfirm"] {
    color: #d32f2f;
    text-decoration: none;
    font-weight: 500;
    padding: 6px 12px;
    border-radius: 4px;
    border: 1px solid #ffcdd2;
    transition: all 0.3s ease;
    display: inline-block;
}

a[onclick*="showRemoveConfirm"]:hover {
    background-color: #ffebee;
    border-color: #ef9a9a;
    transform: translateY(-1px);
}
</style>
</head>
<body>
    <h1><?= $branch['branch_name'] ?> - 员工管理</h1>
    <a href="stores.php">返回门店列表</a>
    
    <?php if (isset($error)): ?>
        <div style="color:red;"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div style="color:green; background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); padding: 15px 20px; border-radius: 8px; margin: 20px auto; max-width: 800px; border-left: 5px solid #2e7d32;">
            <?= $success ?>
        </div>
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
                <a href="javascript:void(0);" onclick="showRemoveConfirm(<?= $branchId ?>, <?= $s['staff_ID'] ?>, '<?= addslashes($s['name']) ?>')" style="color: #d32f2f; text-decoration: none; font-weight: 500; padding: 6px 12px; border-radius: 4px; border: 1px solid #ffcdd2; transition: all 0.3s ease; display: inline-block;">
                  移除
                </a>
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
    <script>
    // 全局弹窗通知函数
    function showNotification(type, message) {
        // 移除现有的弹窗
        const existing = document.querySelector('.notification-popup');
        if (existing) existing.remove();
        
        // 创建弹窗元素
        const notification = document.createElement('div');
        notification.className = 'notification-popup';
        notification.style.cssText = `
            position: fixed;
            top: 30px;
            right: 30px;
            padding: 18px 25px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            z-index: 9999;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            animation: slideInRight 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 300px;
            max-width: 500px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        `;
        
        // 根据类型设置样式
        if (type === 'success') {
            notification.style.background = 'linear-gradient(135deg, rgba(76, 175, 80, 0.95) 0%, rgba(46, 125, 50, 0.95) 100%)';
        } else if (type === 'error') {
            notification.style.background = 'linear-gradient(135deg, rgba(244, 67, 54, 0.95) 0%, rgba(198, 40, 40, 0.95) 100%)';
        } else if (type === 'warning') {
            notification.style.background = 'linear-gradient(135deg, rgba(255, 193, 7, 0.95) 0%, rgba(245, 124, 0, 0.95) 100%)';
        } else {
            notification.style.background = 'linear-gradient(135deg, rgba(33, 150, 243, 0.95) 0%, rgba(13, 71, 161, 0.95) 100%)';
        }
        
        // 图标
        let icon = 'ℹ️';
        if (type === 'success') icon = '✅';
        else if (type === 'error') icon = '❌';
        else if (type === 'warning') icon = '⚠️';
        
        notification.innerHTML = `
            <div style="font-size: 28px; flex-shrink: 0;">${icon}</div>
            <div style="flex-grow: 1; font-size: 15px; line-height: 1.4;">${message}</div>
            <div style="cursor: pointer; font-size: 24px; opacity: 0.8; flex-shrink: 0; margin-left: 10px;" onclick="this.parentElement.remove()">×</div>
        `;
        
        document.body.appendChild(notification);
        
        // 添加CSS动画
        if (!document.querySelector('#notification-styles')) {
            const style = document.createElement('style');
            style.id = 'notification-styles';
            style.textContent = `
                @keyframes slideInRight {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                @keyframes slideOutRight {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        // 5秒后自动移除
        setTimeout(() => {
            if (notification.parentElement) {
                notification.style.animation = 'slideOutRight 0.4s ease forwards';
                setTimeout(() => notification.remove(), 400);
            }
        }, 5000);
    }
    // 移除员工相关变量
let pendingRemoveBranchId = null;
let pendingRemoveStaffId = null;

// 显示确认弹窗
function showRemoveConfirm(branchId, staffId, staffName) {
    pendingRemoveBranchId = branchId;
    pendingRemoveStaffId = staffId;
    
    // 设置确认消息
    const message = `确定要移除员工 <strong>${escapeHtml(staffName)}</strong> 吗？`;
    document.getElementById('confirmMessage').innerHTML = message;
    
    // 显示弹窗
    document.getElementById('customConfirmModal').style.display = 'flex';
}

// 确认移除
function confirmRemove() {
    if (pendingRemoveBranchId && pendingRemoveStaffId) {
        // 跳转到移除URL
        window.location.href = `?id=${pendingRemoveBranchId}&remove_staff=${pendingRemoveStaffId}`;
    }
    closeConfirmModal();
}

// 关闭弹窗
function closeConfirmModal() {
    document.getElementById('customConfirmModal').style.display = 'none';
    pendingRemoveBranchId = null;
    pendingRemoveStaffId = null;
}

// 绑定弹窗事件
document.addEventListener('DOMContentLoaded', function() {
    // 确认按钮
    document.getElementById('confirmOkBtn').addEventListener('click', confirmRemove);
    
    // 取消按钮
    document.getElementById('confirmCancelBtn').addEventListener('click', closeConfirmModal);
    
    // 点击背景关闭
    document.getElementById('customConfirmModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeConfirmModal();
        }
    });
    
    // ESC键关闭
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('customConfirmModal').style.display === 'flex') {
            closeConfirmModal();
        }
    });
});

// HTML转义函数（如果还没有的话）
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
    
    // 页面加载后显示PHP消息
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($success)): ?>
            setTimeout(() => {
                showNotification('success', '<?= addslashes($success) ?>');
            }, 300);
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            setTimeout(() => {
                showNotification('error', '<?= addslashes($error) ?>');
            }, 300);
        <?php endif; ?>
        
        // 表单提交提示
        const addStaffForm = document.querySelector('form[method="post"]');
        if (addStaffForm) {
            const submitBtn = addStaffForm.querySelector('button[name="add_staff"]');
            if (submitBtn) {
                submitBtn.addEventListener('click', function(e) {
                    const staffSelect = addStaffForm.querySelector('select[name="staff_id"]');
                    if (staffSelect && staffSelect.value) {
                        const selectedOption = staffSelect.options[staffSelect.selectedIndex];
                        const staffName = selectedOption.text.split(' (')[0];
                        
                        // 保存原始文本
                        const originalText = submitBtn.innerHTML;
                        
                        // 显示加载状态
                        submitBtn.innerHTML = '<span style="display:inline-block;animation:spin 1s linear infinite;">↻</span> 添加中...';
                        submitBtn.disabled = true;
                        
                        // 添加旋转动画
                        if (!document.querySelector('#spin-animation')) {
                            const spinStyle = document.createElement('style');
                            spinStyle.id = 'spin-animation';
                            spinStyle.textContent = `
                                @keyframes spin {
                                    0% { transform: rotate(0deg); }
                                    100% { transform: rotate(360deg); }
                                }
                            `;
                            document.head.appendChild(spinStyle);
                        }
                        
                        // 表单提交后恢复按钮状态
                        setTimeout(() => {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }, 3000);
                    }
                });
            }
        }
    });
</script>
<!-- 自定义确认弹窗 -->
<div id="customConfirmModal" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:1001;display:none;">
    <div style="background:#fff;width:400px;border-radius:12px;padding:24px;box-shadow:0 10px 30px rgba(0,0,0,0.2);">
        <div style="text-align:center;margin-bottom:20px;">
            <div style="width:60px;height:60px;margin:0 auto 15px;background:#fff3cd;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <span style="font-size:28px;color:#f0ad4e;">⚠️</span>
            </div>
            <h3 style="margin:0;color:#333;font-size:18px;font-weight:600;">确认移除</h3>
        </div>
        <p id="confirmMessage" style="text-align:center;color:#666;margin-bottom:24px;line-height:1.5;font-size:14px;"></p>
        <div style="display:flex;gap:12px;justify-content:center;">
            <button id="confirmCancelBtn" style="background:#f8f9fa;color:#333;border:1px solid #ddd;padding:10px 24px;border-radius:6px;font-weight:500;cursor:pointer;transition:all 0.3s;flex:1;">取消</button>
            <button id="confirmOkBtn" style="background:#d32f2f;color:white;border:none;padding:10px 24px;border-radius:6px;font-weight:500;cursor:pointer;transition:all 0.3s;flex:1;">确认移除</button>
        </div>
    </div>
</div>
</body>
</html>