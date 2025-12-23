<?php
header("Content-Type: text/html; charset=UTF-8");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db_connect.php';
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    header('Location: ../login/login.php');
    exit();
}

$staffMembers = [];
$error_message = '';

$conn = getDBConnection();
$branchId = $_SESSION['staff_branch_id'] ?? null;

if ($branchId === null) {
    $error_message = "无法确定当前门店，请重新登录。";
} else {
    $sql = "SELECT s.staff_ID, s.position, s.phone AS staff_phone, s.status,
                   u.first_name, u.last_name, u.user_email, u.user_telephone
            FROM Staff s
            LEFT JOIN User u ON s.user_name = u.user_name
            WHERE s.branch_ID = ?
            ORDER BY FIELD(s.position,'Manager','Sales','Deliveryman'), s.staff_ID";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $branchId);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $staffMembers[] = $row;
            }
        } else {
            $error_message = "查询员工信息失败：" . $conn->error;
        }
        $stmt->close();
    } else {
        $error_message = "准备查询语句失败：" . $conn->error;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<?php include 'header.php'; ?>
<style>
.employee-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 36px;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.employee-table th, .employee-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #f0f0f0;
    text-align: left;
    font-size: 15px;
}
.employee-table th { background: #f8f9fa; color: #1976d2; letter-spacing: 1px; }
.employee-table tr:last-child td { border-bottom: none; }
.section-title {
    font-size: 22px;
    color: #1976d2;
    margin-bottom: 18px;
    letter-spacing: 1px;
    font-weight: 700;
}
.employee-table .status-pill {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 12px;
    color: #fff;
}
.status-active { background: #43a047; }
.status-on_leave { background: #fb8c00; }
.status-terminated { background: #e53935; }
.empty-state {
    text-align: center;
    color: #888;
    font-size: 15px;
    padding: 24px 0;
}
</style>
<body>
    <main class="main">
        <section class="section">
            <h2 class="section-title">门店员工信息</h2>
            <div style="overflow-x:auto;">
                <table class="employee-table">
                    <thead>
                        <tr>
                            <th>员工编号</th>
                            <th>姓名</th>
                            <th>职位</th>
                            <th>联系方式</th>
                            <th>邮箱</th>
                            <th>状态</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($staffMembers) > 0): ?>
                            <?php foreach ($staffMembers as $staff): ?>
                                <?php
                                    $displayId = 'YG' . str_pad($staff['staff_ID'], 8, '0', STR_PAD_LEFT);
                                    $nameParts = array_filter([$staff['last_name'] ?? '', $staff['first_name'] ?? '']);
                                    $displayName = $nameParts ? implode(' ', $nameParts) : '未登记';
                                    $positionMap = [
                                        'Manager' => '店长',
                                        'Sales' => '销售员',
                                        'Deliveryman' => '配送员'
                                    ];
                                    $positionLabel = $positionMap[$staff['position']] ?? $staff['position'];
                                    $phone = $staff['staff_phone'] ?: ($staff['user_telephone'] ?? '未填写');
                                    $email = $staff['user_email'] ?? '未填写';
                                    $status = $staff['status'] ?? 'active';
                                    $statusClass = 'status-' . str_replace(' ', '_', $status);
                                    $statusLabelMap = [
                                        'active' => '在岗',
                                        'on_leave' => '请假',
                                        'terminated' => '离职'
                                    ];
                                    $statusLabel = $statusLabelMap[$status] ?? $status;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($displayId); ?></td>
                                    <td><?php echo htmlspecialchars($displayName); ?></td>
                                    <td><?php echo htmlspecialchars($positionLabel); ?></td>
                                    <td><?php echo htmlspecialchars($phone); ?></td>
                                    <td><?php echo htmlspecialchars($email); ?></td>
                                    <td>
                                        <span class="status-pill <?php echo htmlspecialchars($statusClass); ?>">
                                            <?php echo htmlspecialchars($statusLabel); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <?php echo $error_message ? htmlspecialchars($error_message) : '当前门店暂无员工信息'; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>
