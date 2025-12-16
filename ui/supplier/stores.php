<?php
// 供应商端 - 合作门店管理（带订单红点提示）
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>鲜选生鲜 - 供应商端-合作门店</title>
    <style>
        /* 原有样式 + 交互相关样式 */
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
        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
        }
        .filter-input {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            width: 250px;
        }
        .filter-select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background-color: white;
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
        .store-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }
        .store-table th, .store-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .store-table th {
            background-color: #f5f9ff;
            color: #1976d2;
            font-weight: 600;
        }
        .store-table tr:hover {
            background-color: #f5f9ff;
        }
        .store-stat-card {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-item {
            background-color: #f5f9ff;
            padding: 15px;
            border-radius: 8px;
        }
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #1976d2;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
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
        /* 弹窗样式 */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal.show {
            display: flex;
        }
        .modal-content {
            background-color: white;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        .modal-close {
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        .modal-close:hover {
            color: #333;
        }
        .modal-body {
            padding: 20px;
        }
        .detail-info {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .detail-info:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 500;
            color: #666;
            margin-bottom: 5px;
            display: block;
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
            .filter-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            .filter-input, .filter-select {
                width: 100%;
            }
            .store-table th, .store-table td {
                padding: 8px 10px;
                font-size: 14px;
            }
            .store-stat-card {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="main">
        <section class="section">
            <h2 class="section-title">合作门店管理</h2>
            <div class="filter-bar">
                <input type="text" class="filter-input" id="storeSearch" placeholder="搜索门店名称/编号">
                <select class="filter-select" id="areaFilter">
                    <option value="">全部区域</option>
                    <option value="高新区">高新区</option>
                    <option value="曲江新区">曲江新区</option>
                    <option value="未央区">未央区</option>
                    <option value="碑林区">碑林区</option>
                    <option value="雁塔区">雁塔区</option>
                </select>
                <button class="btn btn-primary">导出数据</button>
            </div>

            <div class="store-stat-card">
                <div class="stat-item">
                    <div class="stat-value">12</div>
                    <div class="stat-label">总合作门店数</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">5</div>
                    <div class="stat-label">今日有订单门店</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">86%</div>
                    <div class="stat-label">订单完成率</div>
                </div>
            </div>

            <table class="store-table">
                <thead>
                    <tr>
                        <th>门店编号</th>
                        <th>门店名称</th>
                        <th>所在区域</th>
                        <th>联系人</th>
                        <th>联系电话</th>
                        <th>合作状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>S001</td>
                        <td>鲜选生鲜（高新店）</td>
                        <td>高新区</td>
                        <td>张三</td>
                        <td>13800138000</td>
                        <td><span style="color: #43a047;">正常合作</span></td>
                        <td>
                            <button class="btn btn-primary" onclick="showStoreDetail('S001')">查看详情</button>
                        </td>
                    </tr>
                    <!-- 更多门店数据行... -->
                </tbody>
            </table>
        </section>
    </main>

    <!-- 门店详情弹窗 -->
    <div class="modal" id="storeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">门店详情</h3>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body" id="storeDetailContent">
                <!-- 门店详情内容将通过JavaScript动态填充 -->
            </div>
        </div>
    </div>

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
        document.addEventListener('DOMContentLoaded', function() {
            // 计算并更新未处理订单数量（待确认状态）
            function updateUnhandledOrderCount() {
                // 实际应用中应通过AJAX从服务器获取数据
                // 此处模拟数据，与订单页保持一致
                const pendingCount = 1; // 待确认订单数量
                const badge = document.getElementById('unhandledOrderBadge');
                badge.textContent = pendingCount > 0 ? pendingCount : '';
            }

            // 初始化红点
            updateUnhandledOrderCount();
            
            // 点击红点跳转到订单管理页并筛选待确认订单
            const badge = document.getElementById('unhandledOrderBadge');
            if (badge) {
                badge.parentElement.addEventListener('click', function(e) {
                    window.location.href = 'orders.php?status=pending';
                });
            }
        });

        // 显示门店详情
        function showStoreDetail(storeId) {
            // 实际应用中应通过AJAX从服务器获取门店详情
            const detailContent = `
                <div class="detail-info">
                    <span class="detail-label">门店编号</span>
                    <div>S001</div>
                </div>
                <div class="detail-info">
                    <span class="detail-label">门店名称</span>
                    <div>鲜选生鲜（高新店）</div>
                </div>
                <div class="detail-info">
                    <span class="detail-label">所在区域</span>
                    <div>高新区</div>
                </div>
                <div class="detail-info">
                    <span class="detail-label">详细地址</span>
                    <div>西安市高新区科技路88号</div>
                </div>
                <div class="detail-info">
                    <span class="detail-label">联系人</span>
                    <div>张三</div>
                </div>
                <div class="detail-info">
                    <span class="detail-label">联系电话</span>
                    <div>13800138000</div>
                </div>
                <div class="detail-info">
                    <span class="detail-label">合作开始时间</span>
                    <div>2023-05-10</div>
                </div>
                <div class="detail-info">
                    <span class="detail-label">合作状态</span>
                    <div style="color: #43a047;">正常合作</div>
                </div>
                <div class="detail-info">
                    <span class="detail-label">本月订单数</span>
                    <div>28单</div>
                </div>
                <div class="detail-info">
                    <span class="detail-label">本月交易额</span>
                    <div>¥15,680</div>
                </div>
            `;
            
            document.getElementById('storeDetailContent').innerHTML = detailContent;
            document.getElementById('storeModal').classList.add('show');
        }

        // 关闭弹窗
        function closeModal() {
            document.getElementById('storeModal').classList.remove('show');
        }
    </script>
</body>
</html>