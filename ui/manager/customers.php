<?php
// customers.php
require_once __DIR__ . '/inc/db_connect.php';
require_once __DIR__ . '/inc/data.php';
require_once __DIR__ . '/inc/header.php';

// 检查数据是否加载
if (!isset($customers) || !is_array($customers)) {
    $customers = [];
}
?>
<section class="section">
    <h2 class="section-title">顾客信息</h2>
    <div class="filter-bar" style="align-items:center;">
        <input id="custSearch" class="filter-input" placeholder="按姓名/手机号/ID搜索">
        <button id="custSearchBtn" class="btn btn-primary">搜索</button>
    </div>

    <div style="overflow:auto;">
        <table class="data-table" id="customersTable">
            <thead>
                <tr>
                    <th>顾客ID</th>
                    <th>姓名</th>
                    <th>手机</th>
                    <th>注册日期</th>
                    <th>购买次数</th>
                    <th>总消费 (¥)</th>
                    <th>VIP等级</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;padding:20px;color:#666;">
                        暂无顾客数据
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($customers as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['id']) ?></td>
                    <td><?= htmlspecialchars($c['name']) ?></td>
                    <td><?= htmlspecialchars($c['phone']) ?></td>
                    <td><?= htmlspecialchars($c['registered']) ?></td>
                    <td><?= $c['orders'] ?></td>
                    <td><?= number_format($c['total_spent'], 2) ?></td>
                    <td>
                        <?php
                        // 根据loyalty_level显示不同的VIP等级
                        $loyaltyText = $c['loyalty_level'];
                        $loyaltyClass = '';
                        
                        switch ($loyaltyText) {
                            case 'VVIP':
                                $loyaltyText = '至尊VIP';
                                $loyaltyClass = 'vip-vvip';
                                break;
                            case 'VIP':
                                $loyaltyText = 'VIP';
                                $loyaltyClass = 'vip-normal';
                                break;
                            case 'Regular':
                            default:
                                $loyaltyText = '普通';
                                $loyaltyClass = 'vip-regular';
                                break;
                        }
                        ?>
                        <span class="vip-tag <?= $loyaltyClass ?>"><?= htmlspecialchars($loyaltyText) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

<script>
// 搜索功能（前端过滤）
document.getElementById('custSearchBtn').addEventListener('click', function() {
    const query = (document.getElementById('custSearch').value || '').trim().toLowerCase();
    const rows = document.querySelectorAll('#customersTable tbody tr');
    
    rows.forEach(row => {
        // 跳过空行提示
        if (row.cells.length < 2) return;
        
        const rowText = (row.textContent || '').toLowerCase();
        row.style.display = query === '' || rowText.includes(query) ? '' : 'none';
    });
});

// 支持按Enter键搜索
document.getElementById('custSearch').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        document.getElementById('custSearchBtn').click();
    }
});
</script>

<style>
.vip-tag {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.vip-vvip {
    background-color: #ffeb3b;
    color: #333;
    border: 1px solid #ffc107;
}

.vip-normal {
    background-color: #e8f5e8;
    color: #2e7d32;
    border: 1px solid #4caf50;
}

.vip-regular {
    background-color: #f5f5f5;
    color: #666;
    border: 1px solid #ddd;
}
</style>