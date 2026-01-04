<?php
session_start();
require_once __DIR__ .'/inc/db_connect.php';
require_once __DIR__ . '/inc/data.php';
require_once __DIR__ . '/inc/header.php';

// 获取筛选参数
$timeFilter = isset($_GET['time_filter']) ? $_GET['time_filter'] : '';
$timeValue = isset($_GET['time_value']) ? $_GET['time_value'] : '';
$branchFilter = isset($_GET['branch']) ? $_GET['branch'] : '';
$sortField = isset($_GET['sort']) ? $_GET['sort'] : 'total_sales';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'desc';

// 获取分店销售数据
$salesData = getBranchSalesData($timeFilter, $timeValue, $branchFilter, $sortField, $sortOrder);

// 获取分店列表用于筛选
$allBranches = getAllBranchNames();

// 获取最近年份列表
$recentYears = getRecentYears();

// 计算统计数据
$totalSales = 0;
$totalOrders = 0;
foreach ($salesData as $data) {
    $totalSales += $data['total_sales'];
    $totalOrders += $data['order_count'];
}

// 生成排序链接
function getSortLink($field, $currentField, $currentOrder) {
    $params = $_GET;
    $params['sort'] = $field;
    
    if ($currentField == $field) {
        $params['order'] = ($currentOrder == 'desc') ? 'asc' : 'desc';
    } else {
        $params['order'] = 'desc'; // 默认降序
    }
    
    return '?' . http_build_query($params);
}

// 生成排序图标
function getSortIcon($field, $currentField, $currentOrder) {
    if ($currentField != $field) {
        return '';
    }
    
    return ($currentOrder == 'desc') ? '▼' : '▲';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshHarvest - Sales Trends</title>
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
            margin: 20px;
        }
        .main {
            width: 95%;
            margin: 20px auto;
            min-height: calc(100vh - 200px);
        }
        .section {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 25px;
            color: #1976d2;
            border-left: 4px solid #1976d2;
            padding-left: 15px;
        }
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #f5f9ff, #e3f2fd);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border: 1px solid #e0e0e0;
        }
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #1976d2;
            margin: 10px 0;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .filter-container {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px solid #e0e0e0;
        }
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-label {
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        .form-select, .form-input {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            width: 100%;
            background-color: white;
            height: 44px;
        }
        .form-select:focus, .form-input:focus {
            border-color: #1976d2;
            outline: none;
            box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.1);
        }
        .btn-group {
            display: flex;
            gap: 10px;
            align-self: end;
        }
        .filter-actions .form-label {
            visibility: hidden;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-primary {
            background-color: #1976d2;
            color: white;
        }
        .btn-primary:hover {
            background-color: #1565c0;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .sales-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }
        .sales-table th {
            background-color: #1976d2;
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            cursor: pointer;
            position: relative;
        }
        .sales-table th:hover {
            background-color: #1565c0;
        }
        .sales-table th a {
            color: white;
            text-decoration: none;
            display: block;
        }
        .sales-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        .sales-table tr:hover {
            background-color: #f5f9ff;
        }
        .sales-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .sales-table tr:nth-child(even):hover {
            background-color: #f5f9ff;
        }
        .sort-icon {
            margin-left: 5px;
            font-size: 12px;
        }
        .branch-name {
            font-weight: 500;
            color: #333;
        }
        .sales-amount {
            font-weight: 600;
            color: #e53935;
        }
        .order-count {
            color: #1976d2;
            font-weight: 500;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 16px;
        }
        .time-period {
            font-size: 18px;
            color: #666;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f0f7ff;
            border-radius: 6px;
            border-left: 4px solid #1976d2;
        }
        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
            .btn-group {
                flex-direction: column;
            }
            .sales-table {
                font-size: 12px;
            }
            .sales-table th, .sales-table td {
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>


    <main class="main">
        <section class="section">
            <h2 class="section-title">Sales Trends - Branch Performance</h2>
            
            <!-- 统计概览 -->
            <div class="stats-summary">
                <div class="stat-card">
                    <div class="stat-label">Branches Included</div>
                    <div class="stat-value"><?php echo count($allBranches); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Sales</div>
                    <div class="stat-value">¥<?php echo number_format($totalSales, 2); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value"><?php echo number_format($totalOrders); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Average Order Value</div>
                    <div class="stat-value">¥<?php echo $totalOrders > 0 ? number_format($totalSales / $totalOrders, 2) : '0.00'; ?></div>
                </div>
            </div>
            
            <!-- 时间筛选提示 -->
            <?php if (!empty($timeFilter) && !empty($timeValue)): ?>
                <div class="time-period">
                    <?php
                    $periodText = '';
                    switch ($timeFilter) {
                        case 'year':
                            $periodText = $timeValue . ' (Year)';
                            break;
                        case 'month':
                            $periodText = $timeValue . ' (Month)';
                            break;
                        case 'day':
                            $periodText = $timeValue;
                            break;
                    }
                    ?>
                    Current filter: sales data for <?php echo $periodText; ?>
                </div>
            <?php endif; ?>
            
            <!-- 筛选表单 -->
            <div class="filter-container">
                <form method="get" class="filter-form">
                    <div class="form-group">
                        <label class="form-label">Time Filter</label>
                        <select name="time_filter" class="form-select">
                            <option value="">Select period</option>
                            <option value="year" <?php echo $timeFilter == 'year' ? 'selected' : ''; ?>>Year</option>
                            <option value="month" <?php echo $timeFilter == 'month' ? 'selected' : ''; ?>>Month</option>
                            <option value="day" <?php echo $timeFilter == 'day' ? 'selected' : ''; ?>>Day</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Time Value</label>
                        <?php if ($timeFilter == 'year'): ?>
                            <select name="time_value" class="form-select">
                                <option value="">Select year</option>
                                <?php foreach ($recentYears as $year): ?>
                                    <option value="<?php echo $year; ?>" <?php echo $timeValue == $year ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($timeFilter == 'month'): ?>
                            <input type="month" name="time_value" class="form-input" 
                                   value="<?php echo $timeValue; ?>" placeholder="e.g. 2024-01">
                        <?php elseif ($timeFilter == 'day'): ?>
                            <input type="date" name="time_value" class="form-input" 
                                   value="<?php echo $timeValue; ?>">
                        <?php else: ?>
                            <input type="text" name="time_value" class="form-input" 
                                   value="<?php echo $timeValue; ?>" placeholder="Select a period first" readonly>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Branch</label>
                        <select name="branch" class="form-select">
                            <option value="">All branches</option>
                            <?php foreach ($allBranches as $branch): ?>
                                <option value="<?php echo $branch['name']; ?>" 
                                    <?php echo $branchFilter == $branch['name'] ? 'selected' : ''; ?>>
                                    <?php echo $branch['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group filter-actions">
                        <label class="form-label">Actions</label>
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">Apply</button>
                            <a href="saletrend.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- 销售数据表格 -->
            <?php if (!empty($salesData)): ?>
                <div style="overflow-x: auto;">
                    <table class="sales-table">
                        <thead>
                            <tr>
                                <th>
                                    <a href="<?php echo getSortLink('branch_name', $sortField, $sortOrder); ?>">
                                        Branch <?php echo getSortIcon('branch_name', $sortField, $sortOrder); ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?php echo getSortLink('total_sales', $sortField, $sortOrder); ?>">
                                        Total Sales <?php echo getSortIcon('total_sales', $sortField, $sortOrder); ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?php echo getSortLink('order_count', $sortField, $sortOrder); ?>">
                                        Orders <?php echo getSortIcon('order_count', $sortField, $sortOrder); ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?php echo getSortLink('avg_order_value', $sortField, $sortOrder); ?>">
                                        Avg Order Value <?php echo getSortIcon('avg_order_value', $sortField, $sortOrder); ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?php echo getSortLink('unique_customers', $sortField, $sortOrder); ?>">
                                        Customers <?php echo getSortIcon('unique_customers', $sortField, $sortOrder); ?>
                                    </a>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($salesData as $data): ?>
                                <tr>
                                    <td class="branch-name"><?php echo htmlspecialchars($data['branch_name']); ?></td>
                                    <td class="sales-amount"><?php echo $data['formatted_sales']; ?></td>
                                    <td class="order-count"><?php echo number_format($data['order_count']); ?></td>
                                    <td><?php echo $data['formatted_avg']; ?></td>
                                    <td><?php echo number_format($data['unique_customers']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    No sales data found. Adjust the filters and try again.
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script>
        // 动态更新时间值输入框
        document.addEventListener('DOMContentLoaded', function() {
            const timeFilterSelect = document.querySelector('select[name="time_filter"]');
            const timeValueContainer = document.querySelector('input[name="time_value"], select[name="time_value"]').parentNode;
            
            timeFilterSelect.addEventListener('change', function() {
                const filterType = this.value;
                const currentValue = '<?php echo $timeValue; ?>';
                
                let html = '';
                
                switch(filterType) {
                    case 'year':
                        html = `
                            <select name="time_value" class="form-select">
                                <option value="">Select year</option>
                                <?php foreach ($recentYears as $year): ?>
                                    <option value="<?php echo $year; ?>" ${currentValue == '<?php echo $year; ?>' ? 'selected' : ''}>
                                        <?php echo $year; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        `;
                        break;
                    case 'month':
                        html = `<input type="month" name="time_value" class="form-input" value="${currentValue}" placeholder="e.g. 2024-01">`;
                        break;
                    case 'day':
                        html = `<input type="date" name="time_value" class="form-input" value="${currentValue}">`;
                        break;
                    default:
                        html = `<input type="text" name="time_value" class="form-input" value="${currentValue}" placeholder="Select a period first" readonly>`;
                }
                
                timeValueContainer.innerHTML = `
                    <label class="form-label">Time Value</label>
                    ${html}
                `;
            });
        });
        
        // 确认重置筛选
        document.querySelector('.btn-secondary').addEventListener('click', function(e) {
            if (!confirm('Reset all filters?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
