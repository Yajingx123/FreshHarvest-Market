<?php $pageTitle = "个人账户"; ?>
<?php include 'header.php'; ?>

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
        flex: 0 0 500px; /* 固定表单容器宽度，保持原有大小 */
    }
    .account-form {
        max-width: 500px;
        padding: 20px;
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
        .account-form-container {
            width: 100% !important;
            flex: none;
        }
        .account-image-container {
            min-width: auto;
            width: 100%;
        }
    }
</style>

<!-- 个人账户 -->
<section id="account" class="module">
    <div class="product-section">
        <!-- 个人信息编辑部分 -->
        <div class="account-form-container">
            <h2 class="section-title">个人账户</h2>
            <form class="account-form">
                <div class="form-group">
                    <label class="form-label" for="username">用户名</label>
                    <input type="text" id="username" class="form-input" value="张三" disabled>
                </div>

                <div class="form-group">
                    <label class="form-label" for="gender">性别</label>
                    <input type="text" id="gender" class="form-input" value="男" disabled>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="phone">手机号码</label>
                    <input type="tel" id="phone" class="form-input" value="13800138000" disabled>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="email">电子邮箱</label>
                    <input type="email" id="email" class="form-input" value="zhangsan@example.com" disabled>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="address">默认地址</label>
                    <input type="text" id="address" class="form-input" value="北京市朝阳区建国路88号" disabled>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="edit-btn">编辑信息</button>
                    <button type="button" class="save-btn">保存修改</button>
                    <a href="../login/login.php" class="logout-btn">退出登录</a>
                </div>
            </form>
        </div>
        
        <!-- 右侧图片区域 -->
        <div class="account-image-container">
            <img src="account.jpg" alt="生鲜产品展示" class="account-image">
        </div>
    </div>
</section>

<script>
    // 编辑账户信息
    document.querySelector('.edit-btn').addEventListener('click', function() {
        document.querySelectorAll('.form-input').forEach(input => {
            input.disabled = false;
        });
        this.style.display = 'none';
        document.querySelector('.save-btn').style.display = 'inline-block';
    });

    // 保存账户信息
    document.querySelector('.save-btn').addEventListener('click', function() {
        document.querySelectorAll('.form-input').forEach(input => {
            input.disabled = true;
        });
        this.style.display = 'none';
        document.querySelector('.edit-btn').style.display = 'inline-block';
        alert('信息保存成功！');
    });
</script>

</main>
</body>
</html>