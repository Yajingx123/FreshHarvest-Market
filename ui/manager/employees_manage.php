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
        'status' => $_POST['status'] ?? '在职',
        'branch_id' => $_POST['branch_id'] ?? ''
    ];
    
    $result = saveEmployee($data);
    
    if ($result['success']) {
        echo '<script>
            alert("保存成功！");
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

// 设置标题
$pageTitle = $isEdit ? '编辑员工' : '新增员工';

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
                员工照片
            </div>
            <div style="text-align:center;color:#666;">
                <?php if ($isEdit): ?>
                <small>员工ID: <?= htmlspecialchars($employee['id']) ?></small><br>
                <?php endif; ?>
                <small>建议尺寸: 300x300px</small>
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
                            <label class="form-label">员工ID</label>
                            <input type="text" name="id" class="form-control" value="<?= htmlspecialchars($employee['id']) ?>" readonly>
                            <small class="form-text">ID不可修改</small>
                        </div>
                        <?php else: ?>
                        <div class="form-group">
                            <label class="form-label">员工ID</label>
                            <input type="text" name="id" class="form-control" value="自动生成" readonly>
                            <small class="form-text">新增员工ID将自动生成</small>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label class="form-label">姓名 <span class="required">*</span></label>
                            <input type="text" name="name" id="mf_name" class="form-control" 
                                   value="<?= $isEdit ? htmlspecialchars($employee['name']) : '' ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">邮箱 <span class="required">*</span></label>
                            <input type="email" name="email" id="mf_email" class="form-control" 
                                   value="<?= $isEdit ? htmlspecialchars($employee['email']) : '' ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">电话 <span class="required">*</span></label>
                            <input type="tel" name="phone" id="mf_phone" class="form-control" 
                                   value="<?= $isEdit ? htmlspecialchars($employee['phone']) : '' ?>" 
                                   required>
                        </div>
                    </div>
                    
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label">所属门店 <span class="required">*</span></label>
                            <select name="branch_id" id="mf_store" class="form-control" required>
                                <option value="">请选择门店</option>
                                <?php foreach ($branches as $branch): ?>
                                <option value="<?= htmlspecialchars($branch['id']) ?>" 
                                    <?= ($isEdit && $employee['branch_id'] == str_replace('BR-', '', $branch['id'])) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($branch['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">岗位 <span class="required">*</span></label>
                            <select name="role" id="mf_role" class="form-control" required>
                                <option value="Manager" <?= ($isEdit && $employee['role'] == 'Manager') ? 'selected' : '' ?>>经理</option>
                                <option value="Sales" <?= ($isEdit && $employee['role'] == 'Sales') ? 'selected' : '' ?>>销售</option>
                                <option value="Deliveryman" <?= ($isEdit && $employee['role'] == 'Deliveryman') ? 'selected' : '' ?>>配送员</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">薪资 (¥) <span class="required">*</span></label>
                            <input type="number" name="salary" id="mf_salary" class="form-control" 
                                   value="<?= $isEdit ? htmlspecialchars($employee['salary']) : '' ?>" 
                                   step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">入职时间 <span class="required">*</span></label>
                               <input type="date" name="start_date" id="mf_start" class="form-control" 
                                  value="<?= $isEdit ? date('Y-m-d', strtotime($employee['start_date'])) : '' ?>" 
                                  required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">状态 <span class="required">*</span></label>
                            <select name="status" id="mf_status" class="form-control" required>
                                <option value="在职" <?= ($isEdit && $employee['status'] == '在职') ? 'selected' : '' ?>>在职</option>
                                <option value="休假" <?= ($isEdit && $employee['status'] == '休假') ? 'selected' : '' ?>>休假</option>
                                <option value="离职" <?= ($isEdit && $employee['status'] == '离职') ? 'selected' : '' ?>>离职</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div style="margin-top:20px;padding-top:20px;border-top:1px solid #eee;">
                    <div class="form-group">
                        <label class="form-label">备注</label>
                        <textarea name="remark" class="form-control" rows="3" placeholder="可填写员工相关备注信息"></textarea>
                    </div>
                    
                    <div style="margin-top:20px;">
                        <button type="submit" class="btn btn-primary">保存</button>
                        <button type="button" id="mf_cancel" class="btn btn-default" style="margin-left:8px;">取消</button>
                        
                        <?php if ($isEdit): ?>
                        <button type="button" id="mf_reset_pwd" class="btn btn-secondary" style="margin-left:8px;">重置密码</button>
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
    if (confirm('确定要取消吗？未保存的更改将会丢失。')) {
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
        alert('薪资不能为负数');
        if (!firstInvalidField) firstInvalidField = salaryField;
    }
    
    // 验证邮箱格式
    const emailField = document.getElementById('mf_email');
    if (emailField.value && !isValidEmail(emailField.value)) {
        isValid = false;
        emailField.style.borderColor = '#dc3545';
        alert('请输入有效的邮箱地址');
        if (!firstInvalidField) firstInvalidField = emailField;
    }
    const dateField = document.getElementById('mf_start');
    const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
    if (dateField.value && !dateRegex.test(dateField.value)) {
        isValid = false;
        dateField.style.borderColor = '#dc3545';
        alert('请选择完整的日期（格式：年-月-日）');
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
    if (!confirm('确定要保存员工信息吗？')) {
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