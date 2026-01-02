<?php 
$pageTitle = "个人账户"; 

session_start(); // 确保启动会话以获取登录状态
include 'header.php'; 

// 引入数据库操作函数
require_once __DIR__ . '/inc/data.php';

// 获取当前登录用户的信息
$customer_info = getCustomerFullInfo();
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
    .edit-btn, .save-btn, .logout-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    .edit-btn {
        background-color: #2d884d;
        color: white;
    }
    .edit-btn:hover {
        background-color: #236b3c;
    }
    .save-btn {
        background-color: #1890ff;
        color: white;
        display: none;
    }
    .save-btn:hover {
        background-color: #096dd9;
    }
    /* 为退出登录按钮添加悬停效果 */
.logout-btn {
    background-color: #ff4d4f;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    min-width: 100px;
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
    /* 成功提示弹窗样式 */
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

<!-- 个人账户 -->
<section id="account" class="module">
    <?php if (!$customer_info): ?>
        <div class="error-message">
            未检测到登录状态，请先<a href="../login/login.php">登录</a>
        </div>
    <?php else: ?>
        <div class="product-section">
            <!-- 个人信息编辑部分 -->
            <div class="account-form-container">
                <h2 class="section-title">个人账户</h2>
                <form class="account-form" id="accountForm">
                    <div class="form-column">
                        <div class="form-group">
                            <label class="form-label" for="full-name">姓名</label>
                            <input type="text" id="full-name" class="form-input" 
                                   value="<?php echo htmlspecialchars($customer_info['full_name'] ?? ''); ?>" disabled>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="username">用户名</label>
                            <input type="text" id="username" class="form-input" 
                                   value="<?php echo htmlspecialchars($customer_info['user_name'] ?? ''); ?>" disabled>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="loyalty_level">VIP等级</label>
                            <input type="text" id="loyalty_level" class="form-input" 
                                   value="<?php echo htmlspecialchars($customer_info['loyalty_level'] ?? ''); ?>" disabled>
                        </div>

                        <div class="form-group">
                             <label class="form-label" for="gender">性别</label>
                             <select id="gender" class="form-input" disabled>
                              <option value="Male" <?php echo isset($customer_info['gender']) && $customer_info['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                             <option value="Female" <?php echo isset($customer_info['gender']) && $customer_info['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                             </select>
                        </div>
                    </div>
                    
                    <div class="form-column">
                        <div class="form-group">
                            <label class="form-label" for="phone">手机号码</label>
                            <input type="tel" id="phone" class="form-input" 
                                   value="<?php echo htmlspecialchars($customer_info['customer_phone'] ?? ''); ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="email">电子邮箱</label>
                            <input type="email" id="email" class="form-input" 
                                   value="<?php echo htmlspecialchars($customer_info['email'] ?? ''); ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="address">默认地址</label>
                            <input type="text" id="address" class="form-input" 
                                   value="<?php echo htmlspecialchars($customer_info['address'] ?? ''); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="new_password">新密码</label>
                            <input type="password" id="new_password" class="form-input" disabled>
                        </div>

                        <div class="form-group">
                           <label class="form-label" for="confirm_password">确认密码</label>
                           <input type="password" id="confirm_password" class="form-input" disabled>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="edit-btn">编辑信息</button>
                            <button type="button" class="save-btn">保存修改</button>
                            <!-- 将原来的退出登录按钮改为： -->
                            <button type="button" class="logout-btn" onclick="showLogoutModal()">退出登录</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</section>

<!-- 成功提示弹窗 -->
<div class="success-modal-overlay" id="successModal">
    <div class="success-modal">
        <span class="close-success-modal" id="closeSuccessModal">×</span>
        <div class="success-icon">✓</div>
        <div class="success-message">信息修改成功！</div>
    </div>
</div>
<div class="logout-modal-overlay" id="logoutModal">
    <div class="logout-modal">
        <div class="logout-modal-icon">⚠️</div>
        <h3 class="logout-modal-title">确认退出</h3>
        <p class="logout-modal-message">确定要退出登录吗？<br>退出后需要重新登录才能访问账户。</p>
        <div class="logout-modal-actions">
            <button type="button" class="logout-modal-btn logout-modal-cancel" id="cancelLogout">取消</button>
            <button type="button" class="logout-modal-btn logout-modal-confirm" id="confirmLogout">确认退出</button>
        </div>
    </div>
</div>

<script>
    // 退出登录确认
function confirmLogout() {
    if (confirm('确定要退出登录吗？')) {
        // 添加一个加载状态
        const logoutBtn = document.querySelector('.logout-btn');
        const originalText = logoutBtn.textContent;
        logoutBtn.textContent = '退出中...';
        logoutBtn.disabled = true;
        
        // 跳转到logout.php
        setTimeout(() => {
            window.location.href = '../login/logout.php';
        }, 500);
    }
}
    // 编辑账户信息
    const successModal = document.getElementById('successModal');
    const closeSuccessModal = document.getElementById('closeSuccessModal');

     // 退出登录弹窗变量
    const logoutModal = document.getElementById('logoutModal');
    const cancelLogoutBtn = document.getElementById('cancelLogout');
    const confirmLogoutBtn = document.getElementById('confirmLogout');
    let originalLogoutBtnText = '';

    document.querySelector('.edit-btn')?.addEventListener('click', function() {
       document.querySelectorAll('.form-input:not(#loyalty_level):not(#full-name)').forEach(input => {
          input.disabled = false;
       });
       // 新增：启用密码输入框
       document.getElementById('new_password').disabled = false;
       document.getElementById('confirm_password').disabled = false;
   
       document.getElementById('new_password').value = '';
       document.getElementById('confirm_password').value = '';
       this.style.display = 'none';
       document.querySelector('.save-btn').style.display = 'inline-block';
    });
    // 保存账户信息
    document.querySelector('.save-btn')?.addEventListener('click', async function() {
    // 1. 手动收集所有输入框的数值（即使是disabled/readonly的输入框，也能获取value）
    const formData = {
        customerId: <?php echo $customer_info['customer_ID'] ?? 0; ?>,
        username: document.getElementById('username').value.trim(),
        gender: document.getElementById('gender').value.trim(),
        phone: document.getElementById('phone').value.trim(),
        email: document.getElementById('email').value.trim(),
        address: document.getElementById('address').value.trim(),
        loyalty_level: document.getElementById('loyalty_level').value.trim(),
        new_password: document.getElementById('new_password').value.trim(),
        confirm_password: document.getElementById('confirm_password').value.trim()
    };

    if (formData.new_password && formData.new_password !== formData.confirm_password) {
        alert('两次输入的密码不一致！');
        return; // 阻止提交
    }
    
    // 如果只填了一个密码框
    if ((formData.new_password && !formData.confirm_password) || 
        (!formData.new_password && formData.confirm_password)) {
          alert('请完整填写密码和确认密码！');
          return;
    }

    try {
        // 2. 发送AJAX请求到后端update_account.php
        const response = await fetch('update_account.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json', // 告诉后端是JSON格式
            },
            body: JSON.stringify(formData) // 把数据转成JSON字符串传递
        });

        const result = await response.json();
        if (result.status === 'success') {
            successModal.classList.add('active');
            document.body.style.overflow = 'hidden'; // 禁止背景滚动
            document.querySelectorAll('.form-input').forEach(input => {
                if (input.id !== 'full-name' && input.id !== 'loyalty_level') {
                    input.disabled = true;
                }
            });
            document.getElementById('new_password').disabled = true;
            document.getElementById('confirm_password').disabled = true;
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
            this.style.display = 'none';
            document.querySelector('.edit-btn').style.display = 'inline-block';
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('请求失败：', error);
        alert('保存失败，请重试！');
    }
});
// 关闭成功弹窗
    closeSuccessModal.addEventListener('click', function() {
        successModal.classList.remove('active');
        document.body.style.overflow = ''; // 恢复背景滚动
    });

    // 点击弹窗外部关闭
    successModal.addEventListener('click', function(e) {
        if (e.target === successModal) {
            successModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    // ========== 退出登录弹窗功能 ==========
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
        originalLogoutBtnText = logoutBtn.textContent;
        logoutBtn.textContent = '退出中...';
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