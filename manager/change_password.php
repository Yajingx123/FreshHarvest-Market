<?php
session_start();
require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/db_connect.php';
require_once __DIR__ . '/inc/data.php';

if (!isset($_SESSION['manager_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Not signed in. Please sign in again.'
    ]);
    exit();
}
$pageTitle = "Change Password";
?>
<style>
/* 密码修改页面专属样式 - 现代化设计 */
.password-change-container {
    max-width: 500px;
    margin: 40px auto;
    padding: 0 20px;
}

.password-change-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
    padding: 50px 40px;
    margin-top: 20px;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(25, 118, 210, 0.1);
}

.password-change-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg, #1976d2, #42a5f5, #2ed573);
}

.password-change-title {
    text-align: center;
    color: #1976d2;
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 15px;
    position: relative;
    padding-bottom: 20px;
    letter-spacing: 1px;
}

.password-change-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, #1976d2, #42a5f5);
    border-radius: 2px;
}

.password-change-subtitle {
    text-align: center;
    color: #666;
    font-size: 16px;
    margin-bottom: 40px;
    line-height: 1.6;
    opacity: 0.8;
}

/* 密码表单样式 */
.password-form-container {
    margin-top: 20px;
}

.form-group-password {
    margin-bottom: 30px;
    position: relative;
}

.form-label-password {
    display: block;
    margin-bottom: 12px;
    font-weight: 600;
    color: #333;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-label-password i {
    color: #1976d2;
    font-size: 18px;
    width: 24px;
    text-align: center;
}

.form-input-password {
    width: 100%;
    padding: 16px 20px;
    border: 2px solid #e1e8ed;
    border-radius: 12px;
    font-size: 16px;
    transition: all 0.3s ease;
    background-color: #fff;
    color: #333;
    font-family: inherit;
}

.form-input-password:focus {
    outline: none;
    border-color: #1976d2;
    box-shadow: 0 0 0 4px rgba(25, 118, 210, 0.15);
    transform: translateY(-2px);
}

.form-input-password::placeholder {
    color: #aaa;
    opacity: 0.7;
}

/* 密码提示信息 */
.password-help-text {
    margin-top: 10px;
    font-size: 14px;
    color: #666;
    display: flex;
    align-items: center;
    gap: 8px;
    opacity: 0.8;
}

.password-help-text i {
    color: #1976d2;
    font-size: 16px;
}

/* 密码强度条 */
.password-strength-container {
    margin-top: 15px;
}

.password-strength {
    height: 8px;
    border-radius: 4px;
    background-color: #e1e8ed;
    overflow: hidden;
    position: relative;
    margin-bottom: 8px;
}

.password-strength-bar {
    height: 100%;
    width: 0%;
    border-radius: 4px;
    transition: all 0.4s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.password-strength-weak {
    background: linear-gradient(90deg, #ff6b6b, #ff8e8e);
    width: 33%;
}

.password-strength-medium {
    background: linear-gradient(90deg, #ff9f43, #ffcc29);
    width: 66%;
}

.password-strength-strong {
    background: linear-gradient(90deg, #2ed573, #00d2d3);
    width: 100%;
}

.password-strength-labels {
    display: flex;
    justify-content: space-between;
    margin-top: 8px;
}

.password-strength-label {
    font-size: 12px;
    color: #aaa;
    font-weight: 500;
}

.password-strength-label.active {
    color: #333;
    font-weight: 600;
}

/* 密码要求列表 */
.password-requirements {
    margin-top: 35px;
    padding: 25px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 15px;
    border-left: 5px solid #1976d2;
    position: relative;
    overflow: hidden;
}

.password-requirements::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 100%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(25,118,210,0.03)"/></svg>');
    background-size: cover;
}

.password-requirements-title {
    font-weight: 700;
    color: #1976d2;
    margin-bottom: 15px;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
}

.password-requirements-title i {
    font-size: 18px;
}

.password-requirements-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.password-requirement-item {
    font-size: 14px;
    color: #555;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
    transition: all 0.3s ease;
    position: relative;
}

.password-requirement-item i {
    font-size: 14px;
    width: 20px;
    text-align: center;
}

.requirement-met {
    color: #2ed573;
    transform: translateX(5px);
}

.requirement-met i {
    color: #2ed573;
    animation: popIn 0.3s ease;
}

.requirement-unmet {
    color: #aaa;
}

@keyframes popIn {
    0% { transform: scale(0.8); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* 按钮样式 */
.password-form-actions {
    display: flex;
    gap: 20px;
    margin-top: 40px;
    justify-content: center;
}

.btn-password {
    padding: 18px 35px;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 160px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    letter-spacing: 0.5px;
    position: relative;
    overflow: hidden;
}

.btn-password::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.btn-password:hover::before {
    left: 100%;
}

.btn-password-primary {
    background: linear-gradient(135deg, #1976d2 0%, #42a5f5 100%);
    color: white;
    box-shadow: 0 8px 25px rgba(25, 118, 210, 0.3);
}

.btn-password-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(25, 118, 210, 0.4);
}

.btn-password-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #adb5bd 100%);
    color: white;
    box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
}

.btn-password-secondary:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(108, 117, 125, 0.4);
}

/* 响应式设计 */
@media (max-width: 768px) {
    .password-change-card {
        padding: 30px 25px;
        margin-top: 10px;
    }
    
    .password-change-title {
        font-size: 26px;
        padding-bottom: 15px;
    }
    
    .form-input-password {
        padding: 14px 16px;
        font-size: 15px;
    }
    
    .password-form-actions {
        flex-direction: column;
        gap: 15px;
    }
    
    .btn-password {
        width: 100%;
        max-width: 300px;
        margin: 0 auto;
        padding: 16px 30px;
    }
    
    .password-requirements {
        padding: 20px;
    }
}

@media (max-width: 480px) {
    .password-change-container {
        padding: 0 15px;
    }
    
    .password-change-card {
        padding: 25px 20px;
    }
    
    .password-change-title {
        font-size: 22px;
    }
    
    .password-change-subtitle {
        font-size: 14px;
        margin-bottom: 30px;
    }
}

/* 消息提示样式 */
.password-message {
    margin-top: 25px;
    padding: 18px 20px;
    border-radius: 12px;
    font-size: 15px;
    text-align: center;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    opacity: 0;
    transform: translateY(-10px);
    animation: slideIn 0.3s ease forwards;
}

@keyframes slideIn {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.password-message.success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
    border: 2px solid #b1dfbb;
}

.password-message.error {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
    border: 2px solid #f1b0b7;
}

.password-message i {
    font-size: 18px;
}

/* 输入框图标容器 */
.input-icon-container {
    position: relative;
}

.input-icon {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: #aaa;
    cursor: pointer;
    transition: color 0.3s;
    font-size: 18px;
}

.input-icon:hover {
    color: #1976d2;
}

/* 装饰元素 */
.password-decoration {
    position: absolute;
    z-index: -1;
    opacity: 0.1;
}

.decoration-1 {
    top: -50px;
    right: -50px;
    width: 150px;
    height: 150px;
    background: radial-gradient(circle, #1976d2 0%, transparent 70%);
}

.decoration-2 {
    bottom: -30px;
    left: -30px;
    width: 100px;
    height: 100px;
    background: radial-gradient(circle, #42a5f5 0%, transparent 70%);
}

/* 加载动画 */
.loading-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* 密码可见性切换 */
.password-toggle {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    font-size: 16px;
    padding: 5px;
    transition: color 0.3s;
}

.password-toggle:hover {
    color: #1976d2;
}
</style>

    <main>
        <div class="password-change-container">
            <!-- 装饰元素 -->
            <div class="password-decoration decoration-1"></div>
            <div class="password-decoration decoration-2"></div>
            
            <div class="password-change-card">
                <h1 class="password-change-title">🔐 Change Password</h1>
                <p class="password-change-subtitle">Keep your account secure with a strong, regularly updated password.</p>
                
                <div class="password-form-container">
                    <form id="passwordForm">
                        <!-- 当前密码 -->
                        <div class="form-group-password">
                            <label class="form-label-password" for="current_password">
                                <i class="fas fa-lock"></i> Current Password
                            </label>
                            <div class="input-icon-container">
                                <input type="password" 
                                       id="current_password" 
                                       class="form-input-password" 
                                       placeholder="Enter your current password"
                                       required>
                                <button type="button" class="password-toggle" onclick="togglePasswordVisibility('current_password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- 新密码 -->
                        <div class="form-group-password">
                            <label class="form-label-password" for="new_password">
                                <i class="fas fa-key"></i> New Password
                            </label>
                            <div class="input-icon-container">
                                <input type="password" 
                                       id="new_password" 
                                       class="form-input-password" 
                                       placeholder="Set a new password (min 6 characters)"
                                       required
                                       minlength="6">
                                <button type="button" class="password-toggle" onclick="togglePasswordVisibility('new_password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-help-text">
                                <i class="fas fa-info-circle"></i>
                                Use at least 6 characters, ideally a mix of letters, numbers, and symbols.
                            </div>
                            
                            <div class="password-strength-container">
                                <div class="password-strength">
                                    <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                </div>
                                <div class="password-strength-labels">
                                    <span class="password-strength-label" id="weakLabel">Weak</span>
                                    <span class="password-strength-label" id="mediumLabel">Medium</span>
                                    <span class="password-strength-label" id="strongLabel">Strong</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 确认新密码 -->
                        <div class="form-group-password">
                            <label class="form-label-password" for="confirm_password">
                                <i class="fas fa-check-double"></i> Confirm New Password
                            </label>
                            <div class="input-icon-container">
                                <input type="password" 
                                       id="confirm_password" 
                                       class="form-input-password" 
                                       placeholder="Re-enter the new password"
                                       required>
                                <button type="button" class="password-toggle" onclick="togglePasswordVisibility('confirm_password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-help-text" id="passwordMatchText">
                                <i class="fas fa-check-circle"></i>
                                Make sure both passwords match.
                            </div>
                        </div>
                        
                        <!-- 密码要求 -->
                        <div class="password-requirements">
                            <div class="password-requirements-title">
                                <i class="fas fa-clipboard-check"></i>
                                Password Requirements
                            </div>
                            <ul class="password-requirements-list">
                                <li class="password-requirement-item" id="reqLength">
                                    <i class="fas fa-circle"></i>
                                    <span>At least 6 characters</span>
                                </li>
                                <li class="password-requirement-item" id="reqMatch">
                                    <i class="fas fa-circle"></i>
                                    <span>Passwords must match</span>
                                </li>
                                <li class="password-requirement-item" id="reqDifferent">
                                    <i class="fas fa-circle"></i>
                                    <span>New password must differ from current</span>
                                </li>
                            </ul>
                        </div>
                        
                        <!-- 消息提示区域 -->
                        <div id="passwordMessage" class="password-message" style="display: none;">
                            <i class="fas fa-info-circle"></i>
                            <span>Message goes here.</span>
                        </div>
                        
                        <!-- 操作按钮 -->
                        <div class="password-form-actions">
                            <button type="submit" class="btn-password btn-password-primary" id="submitPasswordChange">
                                <i class="fas fa-save"></i>
                                Update Password
                            </button>
                            <button type="button" class="btn-password btn-password-secondary" onclick="goBack()">
                                <i class="fas fa-arrow-left"></i>
                                Back
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
    // DOM加载完成后初始化
    document.addEventListener('DOMContentLoaded', function() {
        setupPasswordValidation();
        
        // 表单提交事件
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            submitPasswordChange();
        });
        
        // 初始化密码强度标签
        updateStrengthLabels('weakLabel');
    });
    
    // 切换密码可见性
    function togglePasswordVisibility(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fas fa-eye-slash';
            button.setAttribute('aria-label', 'Hide password');
        } else {
            input.type = 'password';
            icon.className = 'fas fa-eye';
            button.setAttribute('aria-label', 'Show password');
        }
    }
    
    // 返回上一页
    function goBack() {
        if (document.referrer && document.referrer.includes(window.location.hostname)) {
            window.history.back();
        } else {
            window.location.href = 'overview.php';
        }
    }
    
    // 设置密码验证
    function setupPasswordValidation() {
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const currentPasswordInput = document.getElementById('current_password');
        const strengthBar = document.getElementById('passwordStrengthBar');
        const passwordMatchText = document.getElementById('passwordMatchText');
        const reqLength = document.getElementById('reqLength');
        const reqMatch = document.getElementById('reqMatch');
        const reqDifferent = document.getElementById('reqDifferent');
        
        if (!newPasswordInput) return;
        
        // 新密码输入事件
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            
            // 检查长度
            const isLengthValid = password.length >= 6;
            updateRequirement(reqLength, isLengthValid);
            
            // 检查密码强度并更新UI
            checkPasswordStrength(password);
            
            // 检查新密码是否与当前密码不同
            checkPasswordDifferent();
            // 检查密码匹配
            checkPasswordMatch();
        });
        
        // 确认密码输入事件
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        
        // 当前密码输入事件
        currentPasswordInput.addEventListener('input', checkPasswordDifferent);
        
        // 检查密码强度
        function checkPasswordStrength(password) {
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // 更新强度条和标签
            strengthBar.className = 'password-strength-bar';
            let strengthLevel = '';
            
            if (strength <= 2) {
                strengthBar.classList.add('password-strength-weak');
                strengthLevel = 'weakLabel';
            } else if (strength <= 4) {
                strengthBar.classList.add('password-strength-medium');
                strengthLevel = 'mediumLabel';
            } else {
                strengthBar.classList.add('password-strength-strong');
                strengthLevel = 'strongLabel';
            }
            
            updateStrengthLabels(strengthLevel);
        }
        
        // 更新强度标签
        function updateStrengthLabels(activeLabelId) {
            const labels = ['weakLabel', 'mediumLabel', 'strongLabel'];
            labels.forEach(id => {
                const label = document.getElementById(id);
                label.classList.remove('active');
            });
            document.getElementById(activeLabelId).classList.add('active');
        }
        
        // 检查密码是否匹配
        function checkPasswordMatch() {
            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const isMatch = newPassword === confirmPassword && newPassword.length > 0;
            
            updateRequirement(reqMatch, isMatch);
            
            const icon = passwordMatchText.querySelector('i');
            const text = passwordMatchText.querySelector('span') || passwordMatchText;
            
            if (confirmPassword.length === 0) {
                icon.className = 'fas fa-check-circle';
                text.textContent = 'Make sure both passwords match.';
                passwordMatchText.style.color = '#666';
            } else if (isMatch) {
                icon.className = 'fas fa-check-circle';
                text.textContent = 'Passwords match.';
                passwordMatchText.style.color = '#2ed573';
            } else {
                icon.className = 'fas fa-times-circle';
                text.textContent = 'Passwords do not match.';
                passwordMatchText.style.color = '#ff4757';
            }
        }
        
        // 检查新密码是否与当前密码不同
        function checkPasswordDifferent() {
            const newPassword = newPasswordInput.value;
            const currentPassword = currentPasswordInput.value;
            const isDifferent = newPassword !== currentPassword && newPassword.length > 0 && currentPassword.length > 0;
            
            updateRequirement(reqDifferent, isDifferent);
        }
        
        // 更新要求状态
        function updateRequirement(element, isValid) {
            const icon = element.querySelector('i');
            if (isValid) {
                element.classList.add('requirement-met');
                element.classList.remove('requirement-unmet');
                icon.className = 'fas fa-check-circle';
                icon.style.color = '#2ed573';
            } else {
                element.classList.add('requirement-unmet');
                element.classList.remove('requirement-met');
                icon.className = 'fas fa-circle';
                icon.style.color = '#aaa';
            }
        }
    }
    
    // 提交密码修改
    async function submitPasswordChange() {
    const currentPassword = document.getElementById('current_password').value;
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const messageDiv = document.getElementById('passwordMessage');
    const submitBtn = document.getElementById('submitPasswordChange');
    
    // Validate input
    if (!currentPassword || !newPassword || !confirmPassword) {
        showMessage('Please fill in all required fields.', 'error');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showMessage('New passwords do not match.', 'error');
        return;
    }
    
    if (newPassword.length < 6) {
        showMessage('Password must be at least 6 characters.', 'error');
        return;
    }
    
    if (newPassword === currentPassword) {
        showMessage('New password must differ from current.', 'error');
        return;
    }
    
    // 显示加载状态
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner loading-spinner"></i> Updating...';
    submitBtn.disabled = true;
    
    try {
        // 发送请求到专门的 API 接口
        const response = await fetch('update_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword,
                confirm_password: confirmPassword
            })
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            showMessage(result.message, 'success');
            
            // 清空表单
            document.getElementById('passwordForm').reset();
            
            // 询问是否重新登录
            setTimeout(() => {
                showAppModal('Password Updated', 
                    '🎉 Your password has been updated.<br><br>' +
                    'For security, we recommend signing in again.<br>' +
                    'Confirm to sign out now and sign back in.', {
                    okText: 'Sign out now',
                    cancelText: 'Later',
                    enterConfirm: true
                }).then(confirmed => {
                    if (confirmed) {
                        window.location.href = '../../login/logout.php';
                    }
                });
            }, 1500);
        } else {
            showMessage(result.message, 'error');
        }
    } catch (error) {
        console.error('Password change failed:', error);
        showMessage('Network error. Please try again.', 'error');
    } finally {
        // 恢复按钮状态
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}
    
    // 显示消息
    function showMessage(message, type) {
        const messageDiv = document.getElementById('passwordMessage');
        const icon = messageDiv.querySelector('i');
        const textSpan = messageDiv.querySelector('span');
        
        // 设置图标
        if (type === 'success') {
            icon.className = 'fas fa-check-circle';
        } else {
            icon.className = 'fas fa-exclamation-circle';
        }
        
        // 设置文本
        textSpan.textContent = message;
        
        // 设置样式
        messageDiv.className = `password-message ${type}`;
        messageDiv.style.display = 'flex';
        
        // 5秒后自动隐藏
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 5000);
    }
    </script>
</body>
</html>
