<?php
ob_start();
header("Content-Type: text/html; charset=UTF-8");
session_start();
// Ensure PHP uses the same timezone as your team (adjust if needed)
date_default_timezone_set('Asia/Shanghai');
// 检查staff是否已登录
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    header('Location: ../login/login.php');
    exit();
}

// 数据库配置
$servername = "localhost";
$username = "staff_user";
$password = "YourPassword123!";
$dbname = "mydb";

// 连接数据库
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// 获取staff的branch_ID
$staff_branch_id = $_SESSION['staff_branch_id'];

// 查询待补货商品数（与库存管理页面一致：总库存 < 动态阈值则预警）
$sql_restock = "
    SELECT COUNT(*) AS restock_count
    FROM v_staff_inventory_restock_stats
    WHERE branch_ID = ?
      AND total_stock < GREATEST(10, CEIL(GREATEST(total_stock, total_received) * 0.3))
";
$stmt_restock = $conn->prepare($sql_restock);
$stmt_restock->bind_param("i", $staff_branch_id);
$stmt_restock->execute();
$result_restock = $stmt_restock->get_result();
$restock_count = (int)($result_restock->fetch_assoc()['restock_count'] ?? 0);

// Use PHP server date as "today" (matches what users expect in the browser)
// If your MySQL server timezone differs, using CURDATE() can shift the day and cause mismatches.
$today = date('Y-m-d'); // YYYY-MM-DD

// Optional debug: add ?debug=1 to the URL to check what "today" is on PHP vs MySQL
if (isset($_GET['debug']) && $_GET['debug'] == '1') {
    $dbNow = $conn->query("SELECT NOW() AS now, CURDATE() AS curdate, DATABASE() AS db")->fetch_assoc();

    // List today's orders for this branch to debug count mismatches
    $sql_debug_orders = "SELECT order_ID, customer_ID, order_date, branch_ID, total_amount, status
                         FROM CustomerOrder
                         WHERE branch_ID = ? AND DATE(order_date) = ?
                         ORDER BY order_date ASC";
    $stmt_debug_orders = $conn->prepare($sql_debug_orders);
    $stmt_debug_orders->bind_param("is", $staff_branch_id, $today);
    $stmt_debug_orders->execute();
    $rs_debug_orders = $stmt_debug_orders->get_result();

    $debug_rows = [];
    while ($r = $rs_debug_orders->fetch_assoc()) {
        $debug_rows[] = $r;
    }

    echo "<pre>";
    echo "PHP today: {$today}\n";
    echo "DB now: {$dbNow['now']}\n";
    echo "DB curdate: {$dbNow['curdate']}\n";
    echo "DB schema: {$dbNow['db']}\n";
    echo "Branch: {$staff_branch_id}\n";
    echo "Today's orders in DB (raw): " . count($debug_rows) . "\n";
    print_r($debug_rows);
    echo "</pre>";
}

// 查询今日成交订单数
$sql_orders_today = "SELECT COALESCE(orders_count, 0) AS orders_today
                    FROM v_staff_order_daily_summary
                    WHERE branch_ID = ?
                      AND order_day = ?
                      AND status = 'Completed'";
$stmt_orders_today = $conn->prepare($sql_orders_today);
$stmt_orders_today->bind_param("is", $staff_branch_id, $today);
$stmt_orders_today->execute();
$result_orders_today = $stmt_orders_today->get_result();
$row_orders_today = $result_orders_today->fetch_assoc();
$orders_today = (int)($row_orders_today['orders_today'] ?? 0);

// 查询在职员工数
$sql_staff_count = "SELECT COALESCE(staff_count, 0) as staff_count
                    FROM v_staff_branch_active_staff_count
                    WHERE branch_ID = ?";
$stmt_staff_count = $conn->prepare($sql_staff_count);
$stmt_staff_count->bind_param("i", $staff_branch_id);
$stmt_staff_count->execute();
$result_staff_count = $stmt_staff_count->get_result();
$row_staff_count = $result_staff_count->fetch_assoc();
$staff_count = (int)($row_staff_count['staff_count'] ?? 0);

// 查询门店收益（今日总金额）
$sql_revenue_today = "SELECT COALESCE(revenue_total, 0) AS revenue_today
                      FROM v_staff_order_daily_summary
                      WHERE branch_ID = ?
                        AND order_day = ?
                        AND status = 'Completed'";
$stmt_revenue_today = $conn->prepare($sql_revenue_today);
$stmt_revenue_today->bind_param("is", $staff_branch_id, $today);
$stmt_revenue_today->execute();
$result_revenue_today = $stmt_revenue_today->get_result();
$row_revenue_today = $result_revenue_today->fetch_assoc();
$revenue_today = (float)($row_revenue_today['revenue_today'] ?? 0);

// 最近7天（含今天）的日期标签与序列（按“日期”而不是“周几”，避免显示与数据对不上）
$end_date = $today; // 今日
$start_date = date('Y-m-d', strtotime($today . ' -6 days'));

$date_labels = [];
$week_orders = [];
$week_revenue = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime($today . " -{$i} days"));
    $date_labels[] = date('m-d', strtotime($d));
    $week_orders[] = 0;
    $week_revenue[] = 0.0;
}

// 订单量（最近7天按日期汇总）
$sql_week_orders = "SELECT order_day, orders_count AS cnt
                    FROM v_staff_order_daily_summary
                    WHERE branch_ID = ?
                      AND order_day BETWEEN ? AND ?
                      AND status = 'Completed'";
$stmt_week_orders = $conn->prepare($sql_week_orders);
$stmt_week_orders->bind_param("iss", $staff_branch_id, $start_date, $end_date);
$stmt_week_orders->execute();
$result_week_orders = $stmt_week_orders->get_result();
$orders_map = [];
while ($row = $result_week_orders->fetch_assoc()) {
    $orders_map[$row['order_day']] = (int)$row['cnt'];
}

// 收益（最近7天按日期汇总）
$sql_week_revenue = "SELECT order_day, COALESCE(revenue_total, 0) AS amt
                     FROM v_staff_order_daily_summary
                     WHERE branch_ID = ?
                       AND order_day BETWEEN ? AND ?
                       AND status = 'Completed'";
$stmt_week_revenue = $conn->prepare($sql_week_revenue);
$stmt_week_revenue->bind_param("iss", $staff_branch_id, $start_date, $end_date);
$stmt_week_revenue->execute();
$result_week_revenue = $stmt_week_revenue->get_result();
$revenue_map = [];
while ($row = $result_week_revenue->fetch_assoc()) {
    $revenue_map[$row['order_day']] = (float)$row['amt'];
}

// 按我们生成的7天顺序填充数组
for ($i = 0; $i < 7; $i++) {
    $d_full = date('Y-m-d', strtotime($today . " -" . (6 - $i) . " days"));
    $week_orders[$i] = $orders_map[$d_full] ?? 0;
    $week_revenue[$i] = $revenue_map[$d_full] ?? 0.0;
}

// 假设男7女5
$male_count = 7;
$female_count = 5;

// 关闭连接
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>
<body>
    <main class="main">
        <section class="section">
            <h2 class="section-title">Today's Overview</h2>
            <div class="dashboard-cards">
                <div class="card"><div class="card-icon">🔄</div><div class="card-title">Restock Needed</div><div class="card-value"><?php echo $restock_count; ?></div></div>
                <div class="card"><div class="card-icon">💰</div><div class="card-title">Orders Today</div><div class="card-value"><?php echo $orders_today; ?></div></div>
                <div class="card"><div class="card-icon">👥</div><div class="card-title">Active Staff</div><div class="card-value"><?php echo $staff_count; ?></div></div>
            </div>
            <div style="display:flex;gap:32px;margin-top:32px;flex-wrap:wrap;justify-content:center;">
                <div style="flex:1;min-width:320px;background:#e3f2fd;border-radius:8px;padding:24px;text-align:center;display:flex;flex-direction:column;align-items:center;">
                    <h3 style="font-size:16px;color:#1976d2;margin-bottom:16px;">Store Revenue (Line)</h3>
                    <canvas id="lineChart" width="320" height="220" style="margin:0 auto;"></canvas>
                </div>
                <div style="flex:1;min-width:320px;background:#fce4ec;border-radius:8px;padding:24px;text-align:center;display:flex;flex-direction:column;align-items:center;">
                    <h3 style="font-size:16px;color:#ad1457;margin-bottom:16px;">Weekly Orders (Bar)</h3>
                    <canvas id="barChart" width="320" height="220" style="margin:0 auto;"></canvas>
                </div>
            </div>
        </section>
    </main>
    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // 折线图：门店收益
    new Chart(document.getElementById('lineChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($date_labels, JSON_UNESCAPED_UNICODE); ?>,
            datasets: [{
                label: 'Revenue (CNY)',
                data: [<?php echo implode(',', $week_revenue); ?>],
                borderColor: '#1976d2',
                backgroundColor: 'rgba(25,118,210,0.08)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        display: false
                    }
                }
            }
        }
    });
    // 柱状图：一周订单量
    new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($date_labels, JSON_UNESCAPED_UNICODE); ?>,
            datasets: [{
                label: 'Orders',
                data: [<?php echo implode(',', $week_orders); ?>],
                backgroundColor: '#ad1457'
            }]
        },
        options: {
            responsive: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        display: false
                    }
                }
            }
        }
    });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>
