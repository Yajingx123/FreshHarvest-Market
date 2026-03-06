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

$branchId = $_SESSION['staff_branch_id'] ?? null;
$staffId = $_SESSION['staff_id'] ?? null;
$batchId = isset($_GET['batch_id']) ? trim($_GET['batch_id']) : '';

$error_message = '';
$success_message = '';
$batch = null;

if (!$branchId || !$staffId) {
    $error_message = 'Unable to identify the staff member or branch. Please sign in again.';
} elseif ($batchId === '') {
    $error_message = 'Missing batch information.';
}

if ($error_message === '') {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        $error_message = 'Database connection failed: ' . $conn->connect_error;
    } else {
        $conn->set_charset('utf8mb4');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_action'])) {
            $newQuantity = isset($_POST['new_quantity']) ? (int)$_POST['new_quantity'] : -1;
            $originalQuantity = isset($_POST['original_quantity']) ? (int)$_POST['original_quantity'] : null;
            $note = trim($_POST['adjust_note'] ?? '');

            if ($newQuantity < 0 || $originalQuantity === null) {
                $error_message = 'Please provide a valid return quantity.';
            } elseif ($newQuantity >= $originalQuantity) {
                $error_message = 'Returns must reduce stock.';
            } elseif ($note === '') {
                $error_message = 'Please provide a return reason.';
            } else {
                try {
                    $stmtAdjust = $conn->prepare('CALL staff_adjust_inventory(?, ?, ?, ?, ?, ?, ?)');
                    if (!$stmtAdjust) {
                        throw new Exception('Failed to prepare return procedure: ' . $conn->error);
                    }
                    $reason = 'return';
                    $stmtAdjust->bind_param('siisiis', $batchId, $branchId, $newQuantity, $reason, $staffId, $originalQuantity, $note);
                    if (!$stmtAdjust->execute()) {
                        throw new Exception('Failed to execute return procedure: ' . $stmtAdjust->error);
                    }
                    $stmtAdjust->close();
                    while ($conn->more_results() && $conn->next_result()) {
                        $extra = $conn->store_result();
                        if ($extra) {
                            $extra->free();
                        }
                    }
                    $_SESSION['inventory_success'] = 'Return submitted. Inventory updated.';
                    header('Location: inventory.php');
                    exit();
                } catch (Exception $e) {
                    $error_message = $e->getMessage();
                }
            }
        }

        if ($error_message === '') {
            $stmt = $conn->prepare("
                SELECT batch_ID, quantity_on_hand, product_name, sku, unit_cost, supplier_name
                FROM v_staff_inventory_batches
                WHERE batch_ID = ? AND branch_ID = ?
                LIMIT 1
            ");
            if ($stmt) {
                $stmt->bind_param('si', $batchId, $branchId);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    $batch = $result->fetch_assoc();
                }
                $stmt->close();
            }
            if (!$batch) {
                $error_message = 'Batch information not found.';
            }
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>
<body>
    <main class="main">
        <section class="section">
            <h2 class="section-title">Inventory Return</h2>
            <?php if ($error_message): ?>
                <div class="alert-box alert-error"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($batch): ?>
                <form method="post" style="max-width:520px;">
                    <input type="hidden" name="return_action" value="1">
                    <input type="hidden" name="original_quantity" value="<?php echo (int)$batch['quantity_on_hand']; ?>">
                    <div class="info"><b>Product:</b> <?php echo htmlspecialchars($batch['product_name'] . ' (' . $batch['sku'] . ')', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="info"><b>Batch:</b> <?php echo htmlspecialchars($batch['batch_ID'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="info"><b>Current stock:</b> <?php echo (int)$batch['quantity_on_hand']; ?></div>
                    <div class="info"><b>Return to supplier:</b> <?php echo htmlspecialchars($batch['supplier_name'] ?? 'Unknown Supplier', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="info">
                        <b>Stock after return:</b>
                        <input type="number" name="new_quantity" min="0" max="<?php echo (int)$batch['quantity_on_hand']; ?>" value="<?php echo (int)$batch['quantity_on_hand']; ?>" required style="width:120px;margin-left:8px;">
                    </div>
                    <div class="info" style="align-items:flex-start;">
                        <b>Return reason:</b>
                        <textarea name="adjust_note" rows="3" required style="margin-left:8px;flex:1;min-width:220px;"></textarea>
                    </div>
                    <div class="actions" style="margin-top:18px;">
                        <button class="btn btn-success" type="submit">Submit return</button>
                        <a class="btn btn-warning" href="inventory.php" style="margin-left:10px;display:inline-block;text-decoration:none;">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>
