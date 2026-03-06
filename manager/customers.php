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
    <h2 class="section-title">Customers</h2>
    <div class="filter-bar" style="align-items:center;">
        <input id="custSearch" class="filter-input" placeholder="Search by name / phone / ID">
        <button id="custSearchBtn" class="btn btn-primary">Search</button>
    </div>

    <div style="overflow:auto;">
        <table class="data-table" id="customersTable">
            <thead>
                <tr>
                    <th>Customer ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Registered</th>
                    <th>Orders</th>
                    <th>Total Spent (¥)</th>
                    <th>Tier</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;padding:20px;color:#666;">
                        No customer data
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
                                $loyaltyText = 'Elite VIP';
                                $loyaltyClass = 'vip-vvip';
                                break;
                            case 'VIP':
                                $loyaltyText = 'VIP';
                                $loyaltyClass = 'vip-normal';
                                break;
                            case 'Regular':
                            default:
                                $loyaltyText = 'Regular';
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
