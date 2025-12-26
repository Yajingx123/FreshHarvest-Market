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
        margin-bottom: 20px; /* 与其他表单项相同的底部间距，实现与性别栏对齐 */
        display: flex;
        gap: 10px;
        justify-content: flex-end; /* 右对齐 */
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
    .logout-btn {
        background-color: #ff4d4f;
        color: white;
    }
    .logout-btn:hover {
        background-color: #d9363e;
    }
    @media (max-width: 768px) {
        .product-section {
            flex-direction: column; /* 移动端垂直排列 */
        }
        .account-form {
            flex-direction: column;
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
                              <option value="Men" <?php echo isset($customer_info['gender']) && $customer_info['gender'] == 'Men' ? 'selected' : ''; ?>>Men</option>
                             <option value="Woman" <?php echo isset($customer_info['gender']) && $customer_info['gender'] == 'Woman' ? 'selected' : ''; ?>>Woman</option>
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
                        
                        <div class="form-actions">
                            <button type="button" class="edit-btn">编辑信息</button>
                            <button type="button" class="save-btn">保存修改</button>
                            <a href="../login/login.php" class="logout-btn">退出登录</a>
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

<script>
    // 获取弹窗元素
    const successModal = document.getElementById('successModal');
    const closeSuccessModal = document.getElementById('closeSuccessModal');

    // 编辑账户信息
    document.querySelector('.edit-btn')?.addEventListener('click', function() {
       document.querySelectorAll('.form-input:not(#loyalty_level):not(#full-name)').forEach(input => {
          input.disabled = false;
       });
       this.style.display = 'none';
       document.querySelector('.save-btn').style.display = 'inline-block';
    });

    // 保存账户信息
    document.querySelector('.save-btn')?.addEventListener('click', async function() {
    // 1. 手动收集所有输入框的数值
    const formData = {
        // 必传：用户ID（从后端获取，用于定位要修改的用户）
        customerId: <?php echo $customer_info['customer_ID'] ?? 0; ?>,
        // 收集所有输入框的值
        username: document.getElementById('username').value.trim(),
        gender: document.getElementById('gender').value.trim(),
        phone: document.getElementById('phone').value.trim(),
        email: document.getElementById('email').value.trim(),
        address: document.getElementById('address').value.trim(),
        loyalty_level: document.getElementById('loyalty_level').value.trim()
    };

    try {
        // 2. 发送AJAX请求到后端update_account.php
        const response = await fetch('update_account.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();
        if (result.status === 'success') {
            // 显示成功弹窗
            successModal.classList.add('active');
            document.body.style.overflow = 'hidden'; // 禁止背景滚动
            
            // 保存后恢复禁用状态
            document.querySelectorAll('.form-input').forEach(input => {
                if (input.id !== 'full-name' && input.id !== 'loyalty_level') {
                    input.disabled = true;
                }
            });
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
</script>

</main>
</body>
</html>