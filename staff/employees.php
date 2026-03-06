<?php
header("Content-Type: text/html; charset=UTF-8");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    header('Location: ../login/login.php');
    exit();
}

$servername = "localhost";
$username = "staff_user";
$password = "YourPassword123!";
$dbname = "mydb";

$staffMembers = [];
$error_message = '';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    $error_message = "Database connection failed: " . $conn->connect_error;
} else {
    $conn->set_charset("utf8mb4");
    $branchId = $_SESSION['staff_branch_id'] ?? null;

    if ($branchId === null) {
        $error_message = "Unable to determine the current branch. Please sign in again.";
    } else {
        $sql = "SELECT staff_ID, position, staff_phone, status,
                       first_name, last_name, user_email, user_telephone
                FROM v_staff_branch_employees
                WHERE branch_ID = ?
                ORDER BY FIELD(position,'Manager','Sales','Deliveryman'), staff_ID";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $branchId);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $staffMembers[] = $row;
                }
            } else {
                $error_message = "Failed to load staff data: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error_message = "Failed to prepare staff query: " . $conn->error;
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
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
            <h2 class="section-title">Branch Staff</h2>
            <div style="overflow-x:auto;">
                <table class="employee-table">
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($staffMembers) > 0): ?>
                            <?php foreach ($staffMembers as $staff): ?>
                                <?php
                                    $displayId = 'YG' . str_pad($staff['staff_ID'], 8, '0', STR_PAD_LEFT);
                                    $nameParts = array_filter([$staff['last_name'] ?? '', $staff['first_name'] ?? '']);
                                    $displayName = $nameParts ? implode(' ', $nameParts) : 'Not set';
                                    $positionMap = [
                                        'Manager' => 'Manager',
                                        'Sales' => 'Sales',
                                        'Deliveryman' => 'Delivery'
                                    ];
                                    $positionLabel = $positionMap[$staff['position']] ?? $staff['position'];
                                    $phone = $staff['staff_phone'] ?: ($staff['user_telephone'] ?? 'Not provided');
                                    $email = $staff['user_email'] ?? 'Not provided';
                                    $status = $staff['status'] ?? 'active';
                                    $statusClass = 'status-' . str_replace(' ', '_', $status);
                                    $statusLabelMap = [
                                        'active' => 'Active',
                                        'on_leave' => 'On leave',
                                        'terminated' => 'Terminated'
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
                                    <?php echo $error_message ? htmlspecialchars($error_message) : 'No staff records for this branch'; ?>
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
