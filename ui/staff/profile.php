<?php
header("Content-Type: text/html; charset=UTF-8");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db_connect.php';
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    header('Location: ../login/login.php');
    exit();
}

$error_message = '';
$success_message = $_SESSION['profile_success'] ?? '';
unset($_SESSION['profile_success']);
$profile = null;
$staffId = $_SESSION['staff_id'] ?? null;

function fetchStaffProfile(mysqli $conn, int $staffId): ?array {
    $sql = "SELECT s.staff_ID, s.branch_ID, s.position, s.phone, s.hire_date, s.status, s.user_name,
                   b.branch_name,
                   u.first_name, u.last_name, u.user_email, u.user_telephone
            FROM Staff s
            LEFT JOIN Branch b ON s.branch_ID = b.branch_ID
            LEFT JOIN `User` u ON s.user_name = u.user_name
            WHERE s.staff_ID = ?
            LIMIT 1";
    if (!$stmt = $conn->prepare($sql)) {
        throw new Exception('准备查询失败：' . $conn->error);
    }
    $stmt->bind_param('i', $staffId);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('查询员工信息失败：' . $conn->error);
    }
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data ?: null;
}

if ($staffId === null) {
    $error_message = '无法识别当前员工，请重新登录。';
} else {
    $conn = getDBConnection();
    try {
            $profile = fetchStaffProfile($conn, $staffId);
            if (!$profile) {
                $error_message = '未找到员工资料。';
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error_message) {
                $newPhone = trim($_POST['phone'] ?? '');
                $newPassword = trim($_POST['new_password'] ?? '');
                $confirmPassword = trim($_POST['confirm_password'] ?? '');
                $updateErrors = [];

                if ($newPhone === '') {
                    $updateErrors[] = '联系电话不能为空。';
                } elseif (!preg_match('/^[0-9+\-]{6,20}$/', $newPhone)) {
                    $updateErrors[] = '联系电话格式不正确。';
                }

                if (!$profile) {
                    $updateErrors[] = '当前员工信息不可用。';
                }

                if ($newPassword !== '' && $newPassword !== $confirmPassword) {
                    $updateErrors[] = '两次输入的密码不一致。';
                }

                if (empty($updateErrors)) {
                    $conn->begin_transaction();
                    $canCommit = true;

                    if ($stmtPhone = $conn->prepare('UPDATE Staff SET phone = ? WHERE staff_ID = ?')) {
                        $stmtPhone->bind_param('si', $newPhone, $staffId);
                        if (!$stmtPhone->execute()) {
                            $canCommit = false;
                            $error_message = '更新联系电话失败：' . $conn->error;
                        }
                        $stmtPhone->close();
                    } else {
                        $canCommit = false;
                        $error_message = '更新联系电话失败：' . $conn->error;
                    }

                    if ($canCommit && $profile && $profile['user_name']) {
                        if ($stmtUserPhone = $conn->prepare('UPDATE `User` SET user_telephone = ? WHERE user_name = ?')) {
                            $stmtUserPhone->bind_param('ss', $newPhone, $profile['user_name']);
                            if (!$stmtUserPhone->execute()) {
                                $canCommit = false;
                                $error_message = '同步用户联系电话失败：' . $conn->error;
                            }
                            $stmtUserPhone->close();
                        } else {
                            $canCommit = false;
                            $error_message = '同步用户联系电话失败：' . $conn->error;
                        }
                    }

                    if ($canCommit && $newPassword !== '') {
                        $plainPassword = $newPassword;
                        if ($stmtPwd = $conn->prepare('UPDATE `User` SET password_hash = ? WHERE user_name = ?')) {
                            $stmtPwd->bind_param('ss', $plainPassword, $profile['user_name']);
                            if (!$stmtPwd->execute()) {
                                $canCommit = false;
                                $error_message = '更新密码失败：' . $conn->error;
                            }
                            $stmtPwd->close();
                        } else {
                            $canCommit = false;
                            $error_message = '更新密码失败：' . $conn->error;
                        }
                    }

                    if (!empty($updateErrors)) {
                        $conn->rollback();
                        $error_message = implode(' ', $updateErrors);
                    } elseif ($canCommit && $newPassword !== '' && $newPassword === $confirmPassword) {
                        $conn->commit();
                        $_SESSION = [];
                        session_destroy();
                        header('Location: ../login/login.php');
                        exit();
                    } elseif ($canCommit) {
                        $conn->commit();
                        $_SESSION['profile_success'] = '个人信息已更新。';
                        header('Location: profile.php');
                        exit();
                    } else {
                        $conn->rollback();
                    }
                } else {
                    $error_message = implode(' ', $updateErrors);
                }
            }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
    $conn->close();
}

$positionMap = [
    'Manager' => '店长',
    'Sales' => '销售员',
    'Deliveryman' => '配送员'
];
$statusMap = [
    'active' => ['label' => '正常', 'color' => '#43a047'],
    'on_leave' => ['label' => '请假', 'color' => '#fb8c00'],
    'terminated' => ['label' => '已离职', 'color' => '#e53935']
];

$displayName = '未登记';
$displayBranch = '未知门店';
$employeeCode = '--';
$positionLabel = '未分配';
$hireDate = '--';
$statusLabel = '未知';
$statusColor = '#666';
$phoneNumber = '';
$userEmail = '未填写';

if ($profile) {
    $nameParts = array_filter([$profile['last_name'] ?? '', $profile['first_name'] ?? '']);
    $displayName = $nameParts ? implode(' ', $nameParts) : ($profile['user_name'] ?? $displayName);
    $displayBranch = $profile['branch_name'] ?? $displayBranch;
    $employeeCode = 'YG' . str_pad((int)$profile['staff_ID'], 8, '0', STR_PAD_LEFT);
    $positionLabel = $positionMap[$profile['position']] ?? ($profile['position'] ?: $positionLabel);
    $hireDate = $profile['hire_date'] ?? $hireDate;
    $statusKey = strtolower($profile['status'] ?? '');
    if (isset($statusMap[$statusKey])) {
        $statusLabel = $statusMap[$statusKey]['label'];
        $statusColor = $statusMap[$statusKey]['color'];
    } else {
        $statusLabel = $profile['status'] ?? $statusLabel;
    }
    $phoneNumber = $profile['phone'] ?: ($profile['user_telephone'] ?? '');
    $userEmail = $profile['user_email'] ?? $userEmail;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<?php include 'header.php'; ?>
<body>
    <main class="main">
        <section class="section">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h2 class="section-title">个人中心</h2>
                <button type="button" class="btn btn-danger" onclick="location.href='../login/login.php'">退出登录</button>
            </div>
            <?php if ($error_message): ?>
                <div style="background:#fff3e0;border:1px solid #ffc107;color:#b26a00;padding:12px 16px;border-radius:8px;margin-bottom:20px;">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div id="successMessage" style="background:#e8f5e9;border:1px solid #66bb6a;color:#2e7d32;padding:12px 16px;border-radius:8px;margin-bottom:20px;">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            <form method="post" id="profileForm">
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label">员工姓名</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($displayName); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">员工邮箱</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($userEmail); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">员工编号</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($employeeCode); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">所属门店</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($displayBranch); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">岗位</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($positionLabel); ?>" readonly>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label">联系电话</label>
                            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($phoneNumber); ?>" data-editable="true" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">入职日期</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($hireDate); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">账号状态</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($statusLabel); ?>" readonly style="color: <?php echo htmlspecialchars($statusColor); ?>;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">修改密码</label>
                            <input type="password" class="form-control" name="new_password" placeholder="请输入新密码" data-editable="true" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">确认新密码</label>
                            <input type="password" class="form-control" name="confirm_password" placeholder="请再次输入新密码" data-editable="true" disabled>
                        </div>
                    </div>
                </div>
                <div class="form-actions" style="display:flex;justify-content:flex-end;align-items:center;gap:12px;margin-top:20px;">
                    <button type="button" class="btn btn-primary" id="editToggle">修改</button>
                    <div id="editActionGroup" style="display:none;gap:12px;">
                        <button type="submit" class="btn btn-primary">保存修改</button>
                        <button type="button" class="btn btn-warning" id="cancelEdit">退出编辑</button>
                    </div>
                </div>
            </form>
        </section>
    </main>
    <?php include 'footer.php'; ?>
    <script>
    (function() {
        const editBtn = document.getElementById('editToggle');
        const cancelBtn = document.getElementById('cancelEdit');
        const actionGroup = document.getElementById('editActionGroup');
        const form = document.getElementById('profileForm');
        const editableFields = form.querySelectorAll('[data-editable="true"]');
        let editMode = false;

        function setReadOnlyState(enabled) {
            editableFields.forEach(el => {
                if (enabled) {
                    if (el.tagName === 'TEXTAREA') {
                        el.setAttribute('readonly', 'readonly');
                    } else if (el.type === 'password') {
                        el.value = '';
                        el.setAttribute('disabled', 'disabled');
                    } else {
                        el.setAttribute('readonly', 'readonly');
                    }
                } else {
                    el.removeAttribute('readonly');
                    el.removeAttribute('disabled');
                }
            });
        }

        function enterEditMode() {
            editMode = true;
            setReadOnlyState(false);
            editBtn.style.display = 'none';
            actionGroup.style.display = 'flex';
        }

        function exitEditMode() {
            editMode = false;
            form.reset();
            setReadOnlyState(true);
            editBtn.style.display = 'inline-block';
            actionGroup.style.display = 'none';
        }

        editBtn.addEventListener('click', enterEditMode);
        cancelBtn.addEventListener('click', exitEditMode);

        const successBox = document.getElementById('successMessage');
        if (successBox) {
            setTimeout(() => {
                successBox.style.display = 'none';
            }, 4000);
        }
    })();
    </script>
</body>
</html>
