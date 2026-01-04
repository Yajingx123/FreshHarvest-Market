<?php 
$pageTitle = "Account";

session_start(); // 确保启动会话以获取登录状态
include 'header.php'; 

// 引入数据库操作函数
require_once __DIR__ . '/inc/data.php';

// 获取当前登录用户的信息
$customer_info = getCustomerFullInfo();

// 处理表单提交逻辑
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_password') {
        // 密码更新逻辑
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');
        
        if ($newPassword === '' && $confirmPassword === '') {
            // 如果密码为空，跳过密码更新
        } elseif ($newPassword !== $confirmPassword) {
            $error_message = 'The passwords entered twice are inconsistent.';
        } elseif (strlen($newPassword) < 6) {
            $error_message = 'The password length should be at least 6 characters.';
        } else {
            // 更新密码
            if (updateCustomerPassword($customer_info['customer_ID'] ?? 0, $newPassword)) {
                $success_message = 'Password updated successfully.';
            } else {
                $error_message = 'Password update failed. Please try again.';
            }
        }
    } elseif ($action === 'update_info') {
        // 新增：更新用户信息逻辑
        $update_data = [
            'gender' => $_POST['gender'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'email' => $_POST['email'] ?? '',
            'address' => $_POST['address'] ?? ''
        ];
        
        if (updateCustomerInfo($customer_info['customer_ID'] ?? 0, $update_data)) {
            $success_message = 'Profile updated successfully.';
            // 刷新用户信息
            $customer_info = getCustomerFullInfo();
        } else {
            $error_message = 'Profile update failed. Please try again.';
        }
    }
}

// 如果有成功消息，显示后清除
if ($success_message) {
    echo '<script>document.addEventListener("DOMContentLoaded", function() { showSuccessModal("' . $success_message . '"); });</script>';
}
?>

<style>
    .product-section {
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        padding: 25px;
        margin-bottom: 30px;
        display: flex;
        gap: 30px;
        align-items: flex-start;
    }
    .section-title {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #2d884d;
        border-left: 4px solid #2d884d;
        padding-left: 10px;
    }
    .account-form-container {
        flex: 1; /* 改为1使左右两部分均匀分布 */
    }
    .account-form {
        display: flex;
        flex-wrap: wrap;
        gap: 30px; /* 两列间距 */
    }
    .form-column {
        flex: 1;
        min-width: 300px;
    }
    .form-group {
        margin-bottom: 20px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .form-label {
        font-weight: 500;
        color: #333;
    }
    .form-input {
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 16px;
        width: 100%;
        background-color: #f9f9f9;
        transition: border-color 0.3s;
    }
    .form-input:disabled {
        background-color: #f9f9f9;
        cursor: not-allowed;
        color: #666;
    }
    .form-input:enabled {
        background-color: white;
        border-color: #2d884d;
    }
    .form-actions {
        margin-top: 30px;
        text-align: right;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-bottom: 20px; /* 与左边文件保持一致的底部间距 */
    }
    .edit-btn, .save-btn, .logout-btn, .cancel-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .edit-btn {
        background-color: #2d884d;
        color: white;
    }
    .edit-btn:hover {
        background-color: #236b3c;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(45, 136, 77, 0.3);
    }
    .save-btn {
        background-color: #1890ff;
        color: white;
        display: none;
    }
    .save-btn:hover {
        background-color: #096dd9;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(24, 144, 255, 0.3);
    }
    .cancel-btn {
        background-color: #f0f0f0;
        color: #666;
        display: none;
    }
    .cancel-btn:hover {
        background-color: #e0e0e0;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    /* 为退出登录按钮添加悬停效果 */
    .logout-btn {
        background-color: #ff4d4f;
        color: white;
    }
    .logout-btn:hover {
        background-color: #d9363e;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(217, 54, 62, 0.3);
    }
    .logout-btn:active {
        transform: translateY(0);
    }
    .logout-btn:disabled {
        background-color: #ffcccc;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    /* 错误和成功消息样式 */
    .error-message {
        background-color: #fff2f0;
        border: 1px solid #ffccc7;
        color: #f5222d;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .success-message {
        background-color: #f6ffed;
        border: 1px solid #b7eb8f;
        color: #52c41a;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    /* 新增图片区域样式 */
    .account-image-container {
        flex: 1; /* 占据剩余空间 */
        min-width: 300px; /* 最小宽度，避免过窄 */
        padding: 20px;
    }
    .account-image {
        width: 100%;
        height: 100%;
        border-radius: 8px;
        object-fit: cover; /* 保持图片比例并填充容器 */
        max-height: 450px; /* 限制最大高度，与表单高度匹配 */
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    @media (max-width: 768px) {
        .product-section {
            flex-direction: column; /* 移动端垂直排列 */
        }
        .account-form {
            flex-direction: column;
        }
        .account-form-container {
            width: 100% !important;
            flex: none;
        }
        .account-image-container {
            min-width: auto;
            width: 100%;
            order: -1; /* 移动端将图片放在顶部 */
        }
        .form-actions {
            justify-content: center; /* 移动端居中显示按钮 */
        }
    }
    
    /* ========== Sign out confirmation modal ========== */
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
    
    /* Success modal styles */
    .success-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    .success-modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }
    .success-modal {
        background-color: white;
        border-radius: 10px;
        width: 90%;
        max-width: 500px;
        padding: 30px;
        position: relative;
        transform: translateY(-20px);
        transition: transform 0.3s ease;
    }
    .success-modal-overlay.active .success-modal {
        transform: translateY(0);
    }
    .close-success-modal {
        position: absolute;
        top: 20px;
        right: 20px;
        font-size: 24px;
        cursor: pointer;
        color: #999;
        transition: color 0.3s;
    }
    .close-success-modal:hover {
        color: #ff4d4f;
    }
    .success-icon {
        font-size: 60px;
        color: #2d884d;
        text-align: center;
        margin-bottom: 20px;
    }
    .success-message {
        font-size: 20px;
        color: #333;
        text-align: center;
        margin-bottom: 20px;
        font-weight: 500;
    }
</style>

<!-- Account -->
<section id="account" class="module">
    <?php if (!$customer_info): ?>
        <div class="error-message">
        Session not found. Please <a href="../login/login.php">sign in</a>.
        </div>
    <?php else: ?>
        <div class="product-section">
            <!-- Profile form -->
            <div class="account-form-container">
                <h2 class="section-title">Personal Account</h2>
                
                <?php if ($error_message): ?>
                    <div class="error-message" id="errorMessage">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="success-message" id="successMessage">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <form class="account-form" id="accountForm" method="post" action="">
    <input type="hidden" name="action" id="formAction" value="update_info">
    
    <div class="form-column">
        <div class="form-group">
            <label class="form-label" for="full-name">Full name</label>
            <input type="text" id="full-name" class="form-input" 
                   value="<?php echo htmlspecialchars($customer_info['full_name'] ?? ''); ?>" disabled>
        </div>

        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input type="text" id="username" class="form-input" 
                   value="<?php echo htmlspecialchars($customer_info['user_name'] ?? ''); ?>" disabled>
        </div>

        <div class="form-group">
            <label class="form-label" for="loyalty_level">Loyalty level</label>
            <input type="text" id="loyalty_level" class="form-input" 
                   value="<?php echo htmlspecialchars($customer_info['loyalty_level'] ?? ''); ?>" disabled>
        </div>

        <div class="form-group">
            <label class="form-label" for="gender">Gender</label>
            <select id="gender" name="gender" class="form-input" disabled>
                <option value="Male" <?php echo isset($customer_info['gender']) && $customer_info['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo isset($customer_info['gender']) && $customer_info['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
            </select>
        </div>
    </div>
    
    <div class="form-column">
        <div class="form-group">
            <label class="form-label" for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" class="form-input" 
                   value="<?php echo htmlspecialchars($customer_info['customer_phone'] ?? ''); ?>" disabled>
        </div>
        
        <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <input type="email" id="email" name="email" class="form-input" 
                   value="<?php echo htmlspecialchars($customer_info['email'] ?? ''); ?>" disabled>
        </div>
        
        <div class="form-group">
            <label class="form-label" for="address">Address</label>
            <input type="text" id="address" name="address" class="form-input" 
                   value="<?php echo htmlspecialchars($customer_info['address'] ?? ''); ?>" disabled>
        </div>
        
        <!-- Password update -->
        <div class="form-group">
            <label class="form-label" for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" class="form-input" 
                   placeholder="Enter new password" disabled>
        </div>
        
        <div class="form-group">
            <label class="form-label" for="confirm_password">Confirm new password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                   placeholder="Re-enter new password" disabled>
        </div>
        
        <div class="form-actions">
            <button type="button" class="edit-btn" id="editToggle">Edit information</button>
            <button type="submit" class="save-btn" id="saveBtn">Save changes</button>
            <button type="button" class="cancel-btn" id="cancelEdit">Cancel</button>
            <button type="button" class="logout-btn" onclick="showLogoutModal()">Sign out</button>
        </div>
    </div>
</form>
            </div>
        </div>
    <?php endif; ?>
</section>

<!-- Success modal -->
<div class="success-modal-overlay" id="successModal">
    <div class="success-modal">
        <span class="close-success-modal" id="closeSuccessModal">×</span>
        <div class="success-icon">✓</div>
        <div class="success-message" id="successModalMessage">Your updates were saved.</div>
    </div>
</div>

<!-- Sign out confirmation modal -->
<div class="logout-modal-overlay" id="logoutModal">
    <div class="logout-modal">
        <div class="logout-modal-icon">⚠️</div>
        <h3 class="logout-modal-title">Confirm sign out</h3>
        <p class="logout-modal-message">Sign out now? <br> You will need to sign in again to access your account.</p>
        <div class="logout-modal-actions">
            <button type="button" class="logout-modal-btn logout-modal-cancel" id="cancelLogout">Cancel</button>
            <button type="button" class="logout-modal-btn logout-modal-confirm" id="confirmLogout">Sign out</button>
        </div>
    </div>
</div>

<script>
    // 编辑/保存功能 - 学习profile.php的实现方式
    document.addEventListener('DOMContentLoaded', function() {
        const editBtn = document.getElementById('editToggle');
        const saveBtn = document.getElementById('saveBtn');
        const cancelBtn = document.getElementById('cancelEdit');
        const form = document.getElementById('accountForm');
        
        // 可编辑的字段（不包括姓名和VIP等级）
        const editableFields = [
            'phone', 'email', 'address', 'gender',
            'new_password', 'confirm_password'
        ];
        
        // 进入编辑模式
        function enterEditMode() {
            editableFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.disabled = false;
                    if (field.type === 'password') {
                        field.value = ''; // 清空密码字段
                    }
                }
            });
            
            editBtn.style.display = 'none';
            saveBtn.style.display = 'inline-block';
            cancelBtn.style.display = 'inline-block';
        }
        
        // 退出编辑模式
        function exitEditMode() {
            editableFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.disabled = true;
                    if (field.type === 'password') {
                        field.value = ''; // 清空密码字段
                    }
                }
            });
            
            editBtn.style.display = 'inline-block';
            saveBtn.style.display = 'none';
            cancelBtn.style.display = 'none';
            
            // 重置表单（除了密码字段，其他字段保持原值）
            const originalValues = {
                'phone': '<?php echo htmlspecialchars($customer_info['customer_phone'] ?? ''); ?>',
                'email': '<?php echo htmlspecialchars($customer_info['email'] ?? ''); ?>',
                'address': '<?php echo htmlspecialchars($customer_info['address'] ?? ''); ?>',
                'gender': '<?php echo htmlspecialchars($customer_info['gender'] ?? 'Male'); ?>'
            };
            
            for (const [fieldId, value] of Object.entries(originalValues)) {
                const field = document.getElementById(fieldId);
                if (field && field.type !== 'password') {
                    if (field.tagName === 'SELECT') {
                        field.value = value;
                    } else {
                        field.value = value;
                    }
                }
            }
        }
        
        // 事件监听
        editBtn.addEventListener('click', enterEditMode);
        cancelBtn.addEventListener('click', exitEditMode);
        
const actionInput = document.getElementById('formAction'); // 获取隐藏字段

form.addEventListener('submit', function(event) {
    const newPassword = document.getElementById('new_password').value.trim();
    const confirmPassword = document.getElementById('confirm_password').value.trim();
    
    const hasPasswordChange = newPassword || confirmPassword;
    
    // 设置action
    if (hasPasswordChange) {
        actionInput.value = 'update_password';
        
        // 密码验证
        if (newPassword !== confirmPassword) {
            event.preventDefault();
            alert('The passwords entered twice are inconsistent. Please re-enter.');
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
            document.getElementById('new_password').focus();
            return false;
        }
        
        if (newPassword && newPassword.length < 6) {
            event.preventDefault();
            alert('The password length should be at least 6 characters.');
            document.getElementById('new_password').focus();
            return false;
        }
    } else {
        actionInput.value = 'update_info';
    }
    
    return true;
});
        
        // 自动隐藏错误/成功消息
        const errorMessage = document.getElementById('errorMessage');
        const successMessage = document.getElementById('successMessage');
        
        if (errorMessage) {
            setTimeout(() => {
                errorMessage.style.display = 'none';
            }, 5000);
        }
        
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 5000);
        }
    });
    
    // 成功弹窗功能
    const successModal = document.getElementById('successModal');
    const closeSuccessModal = document.getElementById('closeSuccessModal');
    
    function showSuccessModal(message) {
        if (message) {
            document.getElementById('successModalMessage').textContent = message;
        }
        successModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    // 关闭成功弹窗
    closeSuccessModal.addEventListener('click', function() {
        successModal.classList.remove('active');
        document.body.style.overflow = '';
    });
    
    // 点击弹窗外部关闭
    successModal.addEventListener('click', function(e) {
        if (e.target === successModal) {
            successModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    // ========== 退出登录弹窗功能 ==========
    const logoutModal = document.getElementById('logoutModal');
    const cancelLogoutBtn = document.getElementById('cancelLogout');
    const confirmLogoutBtn = document.getElementById('confirmLogout');
    
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
        const logoutBtn = document.querySelector('.logout-btn');
        const originalLogoutBtnText = logoutBtn.textContent;
        logoutBtn.textContent = 'Signing out...';
        logoutBtn.disabled = true;
        
        logoutModal.style.opacity = '0.5';
        
        setTimeout(() => {
            window.location.href = '../login/logout.php';
        }, 800);
    });
    
    // 键盘快捷键支持
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && logoutModal.classList.contains('active')) {
            hideLogoutModal();
        }
        if (e.key === 'Enter' && logoutModal.classList.contains('active')) {
            confirmLogoutBtn.click();
        }
    });
</script>

</main>
</body>
</html>
