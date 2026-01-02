<?php
session_start();
require_once __DIR__.'/inc/data.php';
require_once __DIR__.'/header.php';
// 验证登录状态
if (!isset($_SESSION['supplier_id'])) {
    header("Location: login.php");
    exit;
}

// 获取筛选参数
$search = isset($_GET['search']) ? $_GET['search'] : '';
$area = isset($_GET['area']) ? $_GET['area'] : '';

// 获取门店数据和统计信息
$branches = getSupplierBranches($search, $area);
$stats = getBranchStatistics();
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
    <main class="main">
    <section class="section">
        <h2 class="section-title">合作门店管理</h2>
        <div class="filter-bar">
            <input type="text" class="filter-input" id="storeSearch" 
                   placeholder="搜索门店名称/编号" value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn btn-primary">导出数据</button>
        </div>

        <div class="store-stat-card">
            <div class="stat-item">
                <div class="stat-value"><?php echo $stats['total_branches']; ?></div>
                <div class="stat-label">总合作门店数</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $stats['today_order_branches']; ?></div>
                <div class="stat-label">今日有订单门店</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $stats['completion_rate']; ?>%</div>
                <div class="stat-label">订单完成率</div>
            </div>
        </div>

        <table class="store-table">
            <thead>
                <tr>
                    <th>门店编号</th>
                    <th>门店名称</th>
                    <th>联系人</th>
                    <th>联系电话</th>
                    <th>合作状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($branches as $branch): ?>
                <tr>
                    <td><?php echo htmlspecialchars($branch['branch_ID']); ?></td>
                    <td><?php echo htmlspecialchars($branch['branch_name']); ?></td>
                    <td><?php echo htmlspecialchars($branch['contact_person'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($branch['phone']); ?></td>
                    <td>
                        <span style="color: <?php echo $branch['status'] === 'active' ? '#43a047' : '#f44336'; ?>">
                            <?php echo $branch['status'] === 'active' ? '正常合作' : '已终止'; ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-primary" 
                                onclick="showStoreDetail('<?php echo htmlspecialchars($branch['branch_ID']); ?>')">
                            查看详情
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($branches)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">
                        暂无合作门店数据
                    </td>
                </tr>
                <?php endif; ?>
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
    // 搜索提交
    const searchInput = document.getElementById('storeSearch');
    
    function handleFilter() {
        const search = encodeURIComponent(searchInput.value.trim());
        window.location.href = `stores.php?search=${search}`;
    }
    
    // 回车键触发搜索
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            handleFilter();
        }
        });
    });

        // 显示门店详情（修改为AJAX获取真实数据）
        function showStoreDetail(storeId) {
            // 发送AJAX请求获取门店详情
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `get_branch_detail.php?branch_id=${encodeURIComponent(storeId)}`, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const branch = JSON.parse(xhr.responseText);
                    if (branch) {
                        const detailContent = `
                            <div class="detail-info">
                                <span class="detail-label">门店编号</span>
                                <div>${htmlspecialchars(branch.branch_ID)}</div>
                            </div>
                            <div class="detail-info">
                                <span class="detail-label">门店名称</span>
                                <div>${htmlspecialchars(branch.branch_name)}</div>
                            </div>
                            <div class="detail-info">
                                <span class="detail-label">详细地址</span>
                                <div>${htmlspecialchars(branch.address)}</div>
                            </div>
                            <div class="detail-info">
                                <span class="detail-label">联系人</span>
                                <div>${htmlspecialchars(branch.contact_person)}</div>
                            </div>
                            <div class="detail-info">
                                <span class="detail-label">联系电话</span>
                                <div>${htmlspecialchars(branch.phone)}</div>
                            </div>
                            <div class="detail-info">
                                <span class="detail-label">合作开始时间</span>
                                <div>${htmlspecialchars(branch.first_cooperation_date ? new Date(branch.first_cooperation_date).toLocaleDateString() : '未知')}</div>
                            </div>
                            <div class="detail-info">
                                <span class="detail-label">合作状态</span>
                                <div style="color: ${branch.status === 'active' ? '#43a047' : '#f44336'}">
                                    ${branch.status === 'active' ? '正常合作' : '已终止'}
                                </div>
                            </div>
                            <div class="detail-info">
                                <span class="detail-label">本月订单数</span>
                                <div>${branch.monthly_orders || 0}单</div>
                            </div>
                            <div class="detail-info">
                                <span class="detail-label">本月交易额</span>
                                <div>¥${branch.monthly_amount ? parseFloat(branch.monthly_amount).toFixed(2) : '0.00'}</div>
                            </div>
                        `;
                        document.getElementById('storeDetailContent').innerHTML = detailContent;
                        document.getElementById('storeModal').classList.add('show');
                    }
                }
            };
            xhr.send();
        }

        // 辅助函数：防止XSS攻击
        function htmlspecialchars(str) {
            if (!str) return '';
            return str.toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // 关闭弹窗函数保持不变
        function closeModal() {
            document.getElementById('storeModal').classList.remove('show');
        }
    </script>
</body>
</html>