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
    $sql = "SELECT staff_ID, branch_ID, position, phone, hire_date, status, user_name,
                   branch_name,
                   first_name, last_name, user_email, user_telephone
            FROM v_staff_profile_detail
            WHERE staff_ID = ?
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
    if ($conn) {
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
                        $plainPassword = md5($newPassword);
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
                        header('Location: ../login/logout.php');
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
<!-- 添加退出确认弹窗的CSS -->
<style>
/* ========== 退出确认弹窗样式 ========== */
.logout-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.logout-modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.logout-modal {
    background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
    border-radius: 16px;
    width: 90%;
    max-width: 400px;
    padding: 40px 30px 30px;
    position: relative;
    transform: translateY(-30px) scale(0.95);
    transition: all 0.4s ease;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.logout-modal-overlay.active .logout-modal {
    transform: translateY(0) scale(1);
}

.logout-modal-icon {
    font-size: 64px;
    color: #ff6b6b;
    text-align: center;
    margin-bottom: 20px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.logout-modal-title {
    font-size: 24px;
    font-weight: 600;
    color: #333;
    text-align: center;
    margin-bottom: 10px;
}

.logout-modal-message {
    font-size: 16px;
    color: #666;
    text-align: center;
    line-height: 1.6;
    margin-bottom: 30px;
}

.logout-modal-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.logout-modal-btn {
    flex: 1;
    padding: 14px 0;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 120px;
}

.logout-modal-cancel {
    background-color: #f0f0f0;
    color: #666;
}

.logout-modal-cancel:hover {
    background-color: #e0e0e0;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.logout-modal-confirm {
    background: linear-gradient(135deg, #ff6b6b 0%, #ff4757 100%);
    color: white;
}

.logout-modal-confirm:hover {
    background: linear-gradient(135deg, #ff4757 0%, #ff3838 100%);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
}

.logout-modal-btn:active {
    transform: translateY(0);
}
</style>
<body>
    <main class="main">
        <section class="section">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h2 class="section-title">个人中心</h2>
                <button type="button" class="btn btn-danger" onclick="showLogoutModal()">退出登录</button>
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
    <!-- ========== 退出确认弹窗 ========== -->
<div class="logout-modal-overlay" id="logoutModal">
    <div class="logout-modal">
        <div class="logout-modal-icon">⚠️</div>
        <h3 class="logout-modal-title">确认退出</h3>
        <p class="logout-modal-message">确定要退出登录吗？<br>退出后需要重新登录才能访问系统。</p>
        <div class="logout-modal-actions">
            <button type="button" class="logout-modal-btn logout-modal-cancel" id="cancelLogout">取消</button>
            <button type="button" class="logout-modal-btn logout-modal-confirm" id="confirmLogout">确认退出</button>
        </div>
    </div>
</div>

<script>
    // 退出登录弹窗功能
    const logoutModal = document.getElementById('logoutModal');
    const cancelLogoutBtn = document.getElementById('cancelLogout');
    const confirmLogoutBtn = document.getElementById('confirmLogout');
    let originalLogoutBtnText = '';

    // 显示退出确认弹窗
    function showLogoutModal() {
        logoutModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // 隐藏退出确认弹窗
    function hideLogoutModal() {
        logoutModal.classList.remove('active');
        document.body.style.overflow = '';
    }

    // 点击弹窗外部关闭
    logoutModal.addEventListener('click', function(e) {
        if (e.target === logoutModal) {
            hideLogoutModal();
        }
    });

    // 点击取消按钮
    cancelLogoutBtn.addEventListener('click', hideLogoutModal);

    // 点击确认退出按钮
    confirmLogoutBtn.addEventListener('click', function() {
        // 显示加载状态
        const logoutBtn = document.querySelector('.btn-danger');
        originalLogoutBtnText = logoutBtn.innerHTML;
        logoutBtn.innerHTML = '退出中...';
        logoutBtn.disabled = true;
        
        // 添加退出动画
        logoutModal.style.opacity = '0.5';
        
        // 延迟跳转，让用户看到加载状态
        setTimeout(() => {
            window.location.href = '../login/logout.php';
        }, 800);
    });

    // 键盘快捷键支持：ESC关闭弹窗
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && logoutModal.classList.contains('active')) {
            hideLogoutModal();
        }
        // 回车键确认退出
        if (e.key === 'Enter' && logoutModal.classList.contains('active')) {
            confirmLogoutBtn.click();
        }
    });
    </script>

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
