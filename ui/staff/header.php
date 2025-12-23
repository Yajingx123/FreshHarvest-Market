<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>鲜选生鲜 - 员工端</title>
    <?php
    header("Content-Type: text/html; charset=UTF-8");
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // 检查staff是否已登录
    if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
        header('Location: ../login/login.php');
        exit();
    }

    // 数据库配置
    $servername = "localhost";
    $username = "root";
    $password = "8049023544Aaa?";
    $dbname = "mydb";

    // 连接数据库
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("数据库连接失败: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    // 获取门店名称
    $branch_id = $_SESSION['staff_branch_id'];
    $sql_branch = "SELECT branch_name FROM Branch WHERE branch_ID = ?";
    $stmt_branch = $conn->prepare($sql_branch);
    $stmt_branch->bind_param("i", $branch_id);
    $stmt_branch->execute();
    $result_branch = $stmt_branch->get_result();
    $branch_name = $result_branch->fetch_assoc()['branch_name'] ?? '未知门店';

    // 获取员工编号（staff_ID）
    $staff_id = $_SESSION['staff_id'];
    $employee_code = 'YG' . str_pad($staff_id, 8, '0', STR_PAD_LEFT); // 格式化为YG00000001

    $conn->close();
    ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Microsoft YaHei', Arial, sans-serif; }
        body { background-color: #f8f9fa; color: #333; }
        .header { background-color: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 999; }
        .nav-container { width: 90%; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 15px 0; }
        .logo { font-size: 24px; font-weight: bold; color: #ff7043; text-decoration: none; }
        .store-info { font-size: 14px; color: #666; margin-left: 20px; }
        .nav-menu { display: flex; list-style: none; }
        .nav-item { margin-left: 30px; }
        .nav-link { text-decoration: none; color: #333; font-size: 16px; font-weight: 500; transition: color 0.3s; }
        .nav-link:hover { color: #ff7043; }
        /* 新增高亮样式 */
        .nav-link.active { color: #ff7043; font-weight: 600; position: relative; }
        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #ff7043;
            border-radius: 1px;
        }
        /* 原有样式保持不变 */
        .nav-link .badge { background-color: #ff4d4f; color: white; font-size: 12px; padding: 2px 6px; border-radius: 10px; margin-left: 5px; }
        .main { width: 90%; margin: 30px auto; min-height: calc(100vh - 200px); }
        .section { background-color: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); padding: 25px; margin-bottom: 30px; }
        .section-title { font-size: 20px; font-weight: 600; margin-bottom: 20px; color: #ff7043; border-left: 4px solid #ff7043; padding-left: 10px; }
        .dashboard-cards { display: flex; gap: 20px; flex-wrap: wrap; }
        .card { flex: 1; min-width: 180px; background-color: #fff3e0; border-radius: 8px; padding: 20px; text-align: center; }
        .card-icon { font-size: 32px; color: #ff7043; margin-bottom: 10px; }
        .card-title { font-size: 16px; margin-bottom: 5px; color: #666; }
        .card-value { font-size: 24px; font-weight: bold; color: #333; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        .data-table th { background-color: #fff8e1; color: #ff7043; font-weight: 600; }
        .data-table tr:hover { background-color: #fafafa; }
        .btn { padding: 8px 16px; border-radius: 6px; border: none; font-size: 14px; cursor: pointer; transition: background-color 0.3s; }
        .btn-primary { background-color: #ff7043; color: white; }
        .btn-primary:hover { background-color: #f57c00; }
        .btn-success { background-color: #43a047; color: white; }
        .btn-success:hover { background-color: #388e3c; }
        .btn-warning { background-color: #fbc02d; color: #333; }
        .btn-warning:hover { background-color: #f9a825; }
        .btn-danger { background-color: #e53935; color: white; }
        .btn-danger:hover { background-color: #d32f2f; }
        .filter-bar { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
        .filter-input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; width: 200px; }
        .filter-select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; width: 180px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 500; color: #666; }
        .form-control { width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px; }
        .form-row { display: flex; gap: 20px; flex-wrap: wrap; }
        .form-col { flex: 1; min-width: 250px; }
        .status-tag { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-pending { background-color: #fff3e0; color: #ff9800; }
        .status-processing { background-color: #e3f2fd; color: #1976d2; }
        .status-completed { background-color: #e8f5e9; color: #43a047; }
        .status-cancelled { background-color: #ffebee; color: #e53935; }
        .status-outstock { background-color: #fce4ec; color: #ad1457; }
        .inventory-form { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .inventory-item { display: flex; flex-direction: column; gap: 8px; }
        .footer { background-color: #333; color: white; padding: 30px 0; margin-top: 50px; }
        .footer-container { width: 90%; margin: 0 auto; text-align: center; }
        .copyright { margin-top: 20px; font-size: 14px; color: #999; }
        @media (max-width: 768px) {
            .nav-menu { display: none; }
            .dashboard-cards { flex-direction: column; }
            .form-row { flex-direction: column; gap: 10px; }
            .filter-bar { flex-direction: column; align-items: flex-start; }
            .filter-input, .filter-select { width: 100%; }
            .data-table th, .data-table td { padding: 8px 10px; font-size: 14px; }
            .btn { padding: 6px 12px; font-size: 13px; }
            .inventory-form { grid-template-columns: 1fr; }
        }
    </style>
</head>
<header class="header">
    <div class="nav-container">
        <div style="display: flex; align-items: center;">
            <a href="dashboard.php" class="logo">鲜选生鲜 - staff</a>
            <span class="store-info">当前门店：<?php echo htmlspecialchars($branch_name); ?> | 员工编号：<?php echo htmlspecialchars($employee_code); ?></span>
        </div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="dashboard.php" class="nav-link">工作概览</a></li>
            <li class="nav-item"><a href="orders.php" class="nav-link">订单管理</a></li>
            <li class="nav-item"><a href="inventory.php" class="nav-link">库存管理</a></li>
            <li class="nav-item"><a href="employees.php" class="nav-link">员工信息</a></li>
            <li class="nav-item"><a href="profile.php" class="nav-link">个人中心</a></li>
        </ul>
    </div>
</header>
<!-- 新增导航高亮JS逻辑 -->
<script>
// 获取当前页面的URL路径
const currentPath = window.location.pathname;
// 获取最后一个斜杠后的文件名
const currentPage = currentPath.split('/').pop() || 'dashboard.php';

// 给对应导航项添加active类
document.querySelectorAll('.nav-link').forEach(link => {
    const linkHref = link.getAttribute('href');
    if (linkHref === currentPage) {
        link.classList.add('active');
    }
    
    // 点击导航项时添加高亮
    link.addEventListener('click', function(e) {
        // 移除所有导航项的active类
        document.querySelectorAll('.nav-link').forEach(item => {
            item.classList.remove('active');
        });
        // 给当前点击的导航项添加active类
        this.classList.add('active');
    });
});
</script>
