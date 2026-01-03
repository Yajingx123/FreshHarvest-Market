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

$branchId = $_SESSION['staff_branch_id'] ?? null;
$staffId = $_SESSION['staff_id'] ?? null;
$batchId = isset($_GET['batch_id']) ? trim($_GET['batch_id']) : '';

$error_message = '';
$success_message = '';
$batch = null;

if (!$branchId || !$staffId) {
    $error_message = '无法识别当前员工或门店，请重新登录。';
} elseif ($batchId === '') {
    $error_message = '缺少批次信息。';
}

if ($error_message === '') {
    $conn = getDBConnection();
    if ($conn) {

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_action'])) {
            $newQuantity = isset($_POST['new_quantity']) ? (int)$_POST['new_quantity'] : -1;
            $originalQuantity = isset($_POST['original_quantity']) ? (int)$_POST['original_quantity'] : null;
            $note = trim($_POST['adjust_note'] ?? '');

            if ($newQuantity < 0 || $originalQuantity === null) {
                $error_message = '请填写正确的退回数量。';
            } elseif ($newQuantity >= $originalQuantity) {
                $error_message = '退回必须减少库存数量。';
            } elseif ($note === '') {
                $error_message = '请填写退回原因。';
            } else {
                try {
                    $stmtAdjust = $conn->prepare('CALL staff_adjust_inventory(?, ?, ?, ?, ?, ?, ?)');
                    if (!$stmtAdjust) {
                        throw new Exception('准备退回存储过程失败：' . $conn->error);
                    }
                    $reason = 'return';
                    $stmtAdjust->bind_param('siisiis', $batchId, $branchId, $newQuantity, $reason, $staffId, $originalQuantity, $note);
                    if (!$stmtAdjust->execute()) {
                        throw new Exception('执行退回存储过程失败：' . $stmtAdjust->error);
                    }
                    $stmtAdjust->close();
                    while ($conn->more_results() && $conn->next_result()) {
                        $extra = $conn->store_result();
                        if ($extra) {
                            $extra->free();
                        }
                    }
                    $_SESSION['inventory_success'] = '退回已提交，库存已更新。';
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
                $error_message = '未找到对应批次信息。';
            }
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<?php include 'header.php'; ?>
<body>
    <main class="main">
        <section class="section">
            <h2 class="section-title">库存退回</h2>
            <?php if ($error_message): ?>
                <div class="alert-box alert-error"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($batch): ?>
                <form method="post" style="max-width:520px;">
                    <input type="hidden" name="return_action" value="1">
                    <input type="hidden" name="original_quantity" value="<?php echo (int)$batch['quantity_on_hand']; ?>">
                    <div class="info"><b>商品：</b><?php echo htmlspecialchars($batch['product_name'] . ' (' . $batch['sku'] . ')', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="info"><b>批次：</b><?php echo htmlspecialchars($batch['batch_ID'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="info"><b>当前库存：</b><?php echo (int)$batch['quantity_on_hand']; ?></div>
                    <div class="info"><b>退回供应商：</b><?php echo htmlspecialchars($batch['supplier_name'] ?? '未知供应商', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="info">
                        <b>调整后库存：</b>
                        <input type="number" name="new_quantity" min="0" max="<?php echo (int)$batch['quantity_on_hand']; ?>" value="<?php echo (int)$batch['quantity_on_hand']; ?>" required style="width:120px;margin-left:8px;">
                    </div>
                    <div class="info" style="align-items:flex-start;">
                        <b>退回原因：</b>
                        <textarea name="adjust_note" rows="3" required style="margin-left:8px;flex:1;min-width:220px;"></textarea>
                    </div>
                    <div class="actions" style="margin-top:18px;">
                        <button class="btn btn-success" type="submit">提交退回</button>
                        <a class="btn btn-warning" href="inventory.php" style="margin-left:10px;display:inline-block;text-decoration:none;">取消</a>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>
