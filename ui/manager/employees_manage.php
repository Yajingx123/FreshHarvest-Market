<?php
// employees_manage.php
require_once __DIR__ . '/inc/db_connect.php';
require_once __DIR__ . '/inc/data.php';
require_once __DIR__ . '/inc/header.php';

// 处理保存请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'is_new' => $_POST['is_new'] === 'true',
        'id' => $_POST['id'] ?? '',
        'name' => $_POST['name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'role' => $_POST['role'] ?? 'staff',
        'salary' => floatval($_POST['salary'] ?? 0),
        'start_date' => $_POST['start_date'] ?? '',
        'status' => $_POST['status'] ?? 'Active',
        'branch_id' => $_POST['branch_id'] ?? ''
    ];
    
    $result = saveEmployee($data);
    
    if ($result['success']) {
        echo '<script>
            alert("Saved successfully.");
            window.location.href = "employees.php";
        </script>';
        exit;
    } else {
        $error = $result['message'];
    }
}

// 获取员工ID
$empId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$isEdit = $empId > 0;
$employee = $isEdit ? getEmployeeById($empId) : null;

// 获取门店列表
$branches = getBranchesForSelect();

// Normalize status for UI selection
$currentStatus = $employee['status'] ?? '';
$statusMap = [
    'active' => 'Active',
    'on_leave' => 'On leave',
    'terminated' => 'Terminated',
    '在职' => 'Active',
    '休假' => 'On leave',
    '离职' => 'Terminated'
];
$currentStatus = $statusMap[$currentStatus] ?? $currentStatus;

// 设置标题
$pageTitle = $isEdit ? 'Edit Employee' : 'Add Employee';

// 如果有错误消息
if (isset($error)) {
    echo "<script>alert('" . addslashes($error) . "');</script>";
}
?>
<section class="section">
    <h2 class="section-title"><?= htmlspecialchars($pageTitle) ?></h2>

    <div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap;">
        <!-- 左侧：照片 -->
        <div style="width:260px;">
            <div style="width:220px;height:220px;background:#f5f5f5;border:1px dashed #ddd;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#999;margin-bottom:12px;">
                Employee photo
            </div>
            <div style="text-align:center;color:#666;">
                <?php if ($isEdit): ?>
                <small>Employee ID: <?= htmlspecialchars($employee['id']) ?></small><br>
                <?php endif; ?>
                <small>Recommended size: 300x300px</small>
            </div>
        </div>

        <!-- 右侧：表单 -->
        <div style="flex:1;min-width:320px;">
            <form id="manageForm" method="POST">
                <input type="hidden" name="is_new" value="<?= $isEdit ? 'false' : 'true' ?>">
                
                <div class="form-row">
                    <div class="form-col">
                        <?php if ($isEdit): ?>
                        <div class="form-group">
                            <label class="form-label">Employee ID</label>
                            <input type="text" name="id" class="form-control" value="<?= htmlspecialchars($employee['id']) ?>" readonly>
                            <small class="form-text">ID cannot be edited</small>
                        </div>
                        <?php else: ?>
                        <div class="form-group">
                            <label class="form-label">Employee ID</label>
                            <input type="text" name="id" class="form-control" value="Auto-generated" readonly>
                            <small class="form-text">New employee IDs are auto-generated</small>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label class="form-label">Name <span class="required">*</span></label>
                            <input type="text" name="name" id="mf_name" class="form-control" 
                                   value="<?= $isEdit ? htmlspecialchars($employee['name']) : '' ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email <span class="required">*</span></label>
                            <input type="email" name="email" id="mf_email" class="form-control" 
                                   value="<?= $isEdit ? htmlspecialchars($employee['email']) : '' ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Phone <span class="required">*</span></label>
                            <input type="tel" name="phone" id="mf_phone" class="form-control" 
                                   value="<?= $isEdit ? htmlspecialchars($employee['phone']) : '' ?>" 
                                   required>
                        </div>
                    </div>
                    
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label">Branch <span class="required">*</span></label>
                            <select name="branch_id" id="mf_store" class="form-control" required>
                                <option value="">Select a branch</option>
                                <?php foreach ($branches as $branch): ?>
                                <option value="<?= htmlspecialchars($branch['id']) ?>" 
                                    <?= ($isEdit && $employee['branch_id'] == str_replace('BR-', '', $branch['id'])) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($branch['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Role <span class="required">*</span></label>
                            <select name="role" id="mf_role" class="form-control" required>
                                <option value="Manager" <?= ($isEdit && $employee['role'] == 'Manager') ? 'selected' : '' ?>>Manager</option>
                                <option value="Sales" <?= ($isEdit && $employee['role'] == 'Sales') ? 'selected' : '' ?>>Sales</option>
                                <option value="Deliveryman" <?= ($isEdit && $employee['role'] == 'Deliveryman') ? 'selected' : '' ?>>Delivery</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Salary (¥) <span class="required">*</span></label>
                            <input type="number" name="salary" id="mf_salary" class="form-control" 
                                   value="<?= $isEdit ? htmlspecialchars($employee['salary']) : '' ?>" 
                                   step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Hire Date <span class="required">*</span></label>
                               <input type="date" name="start_date" id="mf_start" class="form-control" 
                                  value="<?= $isEdit ? date('Y-m-d', strtotime($employee['start_date'])) : '' ?>" 
                                  required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Status <span class="required">*</span></label>
                            <select name="status" id="mf_status" class="form-control" required>
                                <option value="Active" <?= ($isEdit && $currentStatus === 'Active') ? 'selected' : '' ?>>Active</option>
                                <option value="On leave" <?= ($isEdit && $currentStatus === 'On leave') ? 'selected' : '' ?>>On leave</option>
                                <option value="Terminated" <?= ($isEdit && $currentStatus === 'Terminated') ? 'selected' : '' ?>>Terminated</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div style="margin-top:20px;padding-top:20px;border-top:1px solid #eee;">
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="remark" class="form-control" rows="3" placeholder="Add notes about this employee"></textarea>
                    </div>
                    
                    <div style="margin-top:20px;">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <button type="button" id="mf_cancel" class="btn btn-default" style="margin-left:8px;">Cancel</button>
                        
                        <?php if ($isEdit): ?>
                        <button type="button" id="mf_reset_pwd" class="btn btn-secondary" style="margin-left:8px;">Reset Password</button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

<script>
// 取消按钮
document.getElementById('mf_cancel').addEventListener('click', function() {
    if (confirm('Cancel changes? Unsaved edits will be lost.')) {
        window.location.href = 'employees.php';
    }
});


// 表单验证
document.getElementById('manageForm').addEventListener('submit', function(e) {
    // 基本验证
    const requiredFields = this.querySelectorAll('[required]');
    let isValid = true;
    let firstInvalidField = null;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.style.borderColor = '#dc3545';
            if (!firstInvalidField) firstInvalidField = field;
        } else {
            field.style.borderColor = '';
        }
    });
    
    // 验证薪资
    const salaryField = document.getElementById('mf_salary');
    if (salaryField.value && parseFloat(salaryField.value) < 0) {
        isValid = false;
        salaryField.style.borderColor = '#dc3545';
        alert('Salary cannot be negative.');
        if (!firstInvalidField) firstInvalidField = salaryField;
    }
    
    // 验证邮箱格式
    const emailField = document.getElementById('mf_email');
    if (emailField.value && !isValidEmail(emailField.value)) {
        isValid = false;
        emailField.style.borderColor = '#dc3545';
        alert('Please enter a valid email address.');
        if (!firstInvalidField) firstInvalidField = emailField;
    }
    const dateField = document.getElementById('mf_start');
    const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
    if (dateField.value && !dateRegex.test(dateField.value)) {
        isValid = false;
        dateField.style.borderColor = '#dc3545';
        alert('Please select a valid date (YYYY-MM-DD).');
        if (!firstInvalidField) firstInvalidField = dateField;
    }
    if (!isValid) {
        e.preventDefault();
        if (firstInvalidField) {
            firstInvalidField.focus();
        }
        return false;
    }
    
    // 确认保存
    if (!confirm('Save employee details?')) {
        e.preventDefault();
        return false;
    }
    
    return true;
});

// 邮箱验证函数
function isValidEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

// 自动填充今天的日期（如果是新增）
<?php if (!$isEdit): ?>
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('mf_start').value = today;
});
<?php endif; ?>
</script>

<style>
.required {
    color: #dc3545;
    margin-left: 2px;
}
.form-text {
    font-size: 12px;
    color: #6c757d;
    margin-top: 4px;
}
</style>
