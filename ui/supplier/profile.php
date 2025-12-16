<?php
// 供应商端 - 供货商信息（含退出按钮版，带订单红点提示）
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>鲜选生鲜 - 供应商端-供货商信息</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Microsoft YaHei', Arial, sans-serif;
        }
        body {
            background-color: #f8f9fa;
            color: #333;
        }
        .header {
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .nav-container {
            width: 90%;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #1976d2;
            text-decoration: none;
        }
        .nav-menu {
            display: flex;
            list-style: none;
        }
        .nav-item {
            margin-left: 30px;
        }
        .nav-link {
            text-decoration: none;
            color: #333;
            font-size: 16px;
            font-weight: 500;
            transition: color 0.3s;
            display: inline-flex;
            align-items: center;
        }
        .nav-link:hover {
            color: #1976d2;
        }
        .nav-link.active {
            color: #1976d2;
            font-weight: bold;
        }
        .main {
            width: 90%;
            margin: 30px auto;
            min-height: calc(100vh - 200px);
        }
        .section-title {
            font-size: 22px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .profile-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }
        .profile-group {
            margin-bottom: 25px;
        }
        .profile-label {
            display: block;
            font-weight: 500;
            color: #666;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .profile-value {
            font-size: 16px;
            padding: 10px 15px;
            background-color: #f5f9ff;
            border-radius: 6px;
            border: 1px solid #e3f2fd;
        }
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-primary {
            background-color: #1976d2;
            color: white;
        }
        .btn-primary:hover {
            background-color: #1565c0;
        }
        .btn-danger {
            background-color: #e53935;
            color: white;
        }
        .btn-danger:hover {
            background-color: #d32f2f;
        }
        .footer {
            background-color: #333;
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }
        .footer-container {
            width: 90%;
            margin: 0 auto;
            text-align: center;
        }
        .copyright {
            margin-top: 20px;
            font-size: 14px;
            color: #999;
        }
        /* 红点提示样式 */
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background-color: #e53935;
            color: white;
            font-size: 12px;
            font-weight: bold;
            margin-left: 5px;
            padding: 0;
            line-height: 1;
        }
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            .profile-card {
                padding: 20px;
            }
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="main">
        <section class="section">
            <h2 class="section-title">供货商信息</h2>
            
            <div class="profile-card">
                <div class="profile-group">
                    <span class="profile-label">供应商编号</span>
                    <div class="profile-value">SUPP20240015</div>
                </div>
                
                <div class="profile-group">
                    <span class="profile-label">供应商名称</span>
                    <div class="profile-value">绿源农产品有限公司</div>
                </div>
                
                <div class="profile-group">
                    <span class="profile-label">联系人</span>
                    <div class="profile-value">李明</div>
                </div>
                
                <div class="profile-group">
                    <span class="profile-label">联系电话</span>
                    <div class="profile-value">13900139000</div>
                </div>
                
                <div class="profile-group">
                    <span class="profile-label">电子邮箱</span>
                    <div class="profile-value">liming@lvyuan.com</div>
                </div>
                
                <div class="profile-group">
                    <span class="profile-label">地址</span>
                    <div class="profile-value">陕西省西安市未央区农产品物流中心B区12号</div>
                </div>
                
                <div class="profile-group">
                    <span class="profile-label">营业执照编号</span>
                    <div class="profile-value">91610112XXXXXXXXXX</div>
                </div>
                
                <div class="profile-group">
                    <span class="profile-label">合作开始日期</span>
                    <div class="profile-value">2023-03-15</div>
                </div>
                
                <div class="profile-group">
                    <span class="profile-label">合作状态</span>
                    <div class="profile-value" style="color: #43a047; font-weight: 500;">正常合作中</div>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-primary">编辑信息</button>
                    <button class="btn btn-danger" onclick="confirmLogout()">退出登录</button>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <h3 class="logo" style="color: white; margin-bottom: 20px;">鲜选生鲜 - 供应商管理平台</h3>
            <div style="display: flex; justify-content: center; gap: 30px; margin-bottom: 20px;">
                <a href="#" style="color: #ccc; text-decoration: none;">供应商帮助中心</a>
                <a href="#" style="color: #ccc; text-decoration: none;">合作协议</a>
                <a href="#" style="color: #ccc; text-decoration: none;">结算规则</a>
                <a href="#" style="color: #ccc; text-decoration: none;">投诉反馈</a>
                <a href="#" style="color: #ccc; text-decoration: none;">联系平台</a>
            </div>
            <div class="copyright">© 2024 鲜选生鲜 版权所有 | 平台客服电话：400-888-XXXX</div>
        </div>
    </footer>

    <script>
        // 计算并更新未处理订单数量（待确认状态）
        function updateUnhandledOrderCount() {
            // 实际应用中应通过AJAX从服务器获取数据
            // 此处模拟数据，与订单页保持一致
            const pendingCount = 2; // 待确认订单数量
            const badge = document.getElementById('unhandledOrderBadge');
            badge.textContent = pendingCount > 0 ? pendingCount : '';
        }

        // 退出登录确认
        function confirmLogout() {
            if (confirm('确定要退出登录吗？')) {
                // 实际应用中这里会处理退出登录逻辑
                alert('已退出登录');
                window.location.href = 'login.html';
            }
        }

        // 页面加载完成后初始化红点
        document.addEventListener('DOMContentLoaded', function() {
            updateUnhandledOrderCount();
            
            // 点击红点跳转到订单管理页并筛选待确认订单
            const badge = document.getElementById('unhandledOrderBadge');
            if (badge) {
                badge.parentElement.addEventListener('click', function(e) {
                    // 跳转到订单页并自动筛选待确认订单
                    window.location.href = 'orders.php?status=pending';
                });
            }
        });
    </script>
</body>
</html>