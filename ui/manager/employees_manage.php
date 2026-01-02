<?php
// employees_manage.php
require_once __DIR__ . '/inc/db_connect.php';
require_once __DIR__ . '/inc/data.php';
require_once __DIR__ . '/inc/header.php';

// 处理保存请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 生成全名
    $fullName = trim(($_POST['first_name'] ?? '') . ' ' . ($_POST['last_name'] ?? ''));
    
    $data = [
        'is_new' => $_POST['is_new'] === 'true',
        'id' => $_POST['id'] ?? '',
        'username' => $_POST['username'] ?? '', // 添加用户名
        'first_name' => $_POST['first_name'] ?? '', // 添加姓氏
        'last_name' => $_POST['last_name'] ?? '', // 添加名字
        'name' => $fullName, // 使用生成的全名
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
        showSuccessModal("保存成功", "员工信息已保存", function() {
            window.location.href = "employees.php";
        });
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
    echo "<script>showErrorModal('保存失败', '" . addslashes($error) . "');</script>";
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
                          <label class="form-label">用户名 <span class="required">*</span></label>
                          <input type="text" name="username" id="mf_username" class="form-control" 
                          value="<?= $isEdit ? htmlspecialchars($employee['username'] ?? '') : '' ?>" 
                          required>
                        <small class="form-text">用于登录的用户名，必须唯一</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">姓氏 <span class="required">*</span></label>
                            <input type="text" name="first_name" id="mf_first_name" class="form-control" 
                           value="<?= $isEdit ? htmlspecialchars($employee['first_name'] ?? '') : '' ?>" 
                           required>
                        </div>

                        <div class="form-group">
                          <label class="form-label">名字 <span class="required">*</span></label>
                          <input type="text" name="last_name" id="mf_last_name" class="form-control" 
                          value="<?= $isEdit ? htmlspecialchars($employee['last_name'] ?? '') : '' ?>" 
                          required>
                        </div>

                        <div class="form-group">
                              <label class="form-label">全名</label>
                              <input type="text" id="mf_fullname" class="form-control" readonly 
                                  value="<?= $isEdit ? htmlspecialchars($employee['name']) : '' ?>">
                              <small class="form-text">自动显示：姓氏 + 名字</small>
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
// 首先添加showAppModal函数
function showAppModal(title, content, options = {}) {
    const defaultOptions = {
        showCancel: true,
        okText: '确定',
        cancelText: '取消',
        okClass: 'btn-primary',
        cancelClass: 'btn-default',
        width: '500px',
        onOk: null,
        onCancel: null
    };
    
    const config = { ...defaultOptions, ...options };
    
    const modalId = 'modal-' + Date.now();
    const modalHtml = `
        <div id="${modalId}" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;">
            <div style="background:white;border-radius:8px;width:${config.width};max-width:90%;box-shadow:0 5px 20px rgba(0,0,0,0.2);">
                <div style="padding:20px 24px 15px 24px;border-bottom:1px solid #eee;text-align:center;">
                    <h3 style="margin:0;color:#333;font-size:18px;">${title}</h3>
                </div>
                <div style="padding:24px;color:#555;">
                    ${content}
                </div>
                <div style="padding:16px 24px 20px 24px;border-top:1px solid #eee;text-align:right;">
                    ${config.showCancel ? `
                    <button id="${modalId}-cancel" style="background:#6c757d;color:white;border:none;padding:8px 20px;border-radius:4px;cursor:pointer;margin-right:10px;font-size:14px;">
                        ${config.cancelText}
                    </button>
                    ` : ''}
                    <button id="${modalId}-ok" style="background:#007bff;color:white;border:none;padding:8px 20px;border-radius:4px;cursor:pointer;font-size:14px;">
                        ${config.okText}
                    </button>
                </div>
            </div>
        </div>
    `;
    
    const modalDiv = document.createElement('div');
    modalDiv.innerHTML = modalHtml;
    document.body.appendChild(modalDiv);
    
    const modal = document.getElementById(modalId);
    const okBtn = document.getElementById(`${modalId}-ok`);
    const cancelBtn = config.showCancel ? document.getElementById(`${modalId}-cancel`) : null;
    
    // 应用按钮样式
    if (config.okClass) {
        okBtn.className = config.okClass;
        okBtn.removeAttribute('style');
    }
    
    if (cancelBtn && config.cancelClass) {
        cancelBtn.className = config.cancelClass;
        cancelBtn.removeAttribute('style');
    }
    
    return new Promise((resolve) => {
        okBtn.addEventListener('click', () => {
            document.body.removeChild(modalDiv);
            if (typeof config.onOk === 'function') config.onOk();
            resolve(true);
        });
        
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                document.body.removeChild(modalDiv);
                if (typeof config.onCancel === 'function') config.onCancel();
                resolve(false);
            });
        }
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                document.body.removeChild(modalDiv);
                if (typeof config.onCancel === 'function') config.onCancel();
                resolve(false);
            }
        });
    });
}
// 自动更新全名
function updateFullName() {
    const firstName = document.getElementById('mf_first_name').value;
    const lastName = document.getElementById('mf_last_name').value;
    const fullName = (firstName + lastName).trim();
    document.getElementById('mf_fullname').value = fullName || '未填写';
}

// 绑定事件
document.getElementById('mf_first_name').addEventListener('input', updateFullName);
document.getElementById('mf_last_name').addEventListener('input', updateFullName);

// 表单验证增加用户名唯一性检查（前端提示）
function checkUsernameAvailability() {
    const username = document.getElementById('mf_username').value.trim();
    if (!username) return;
    

    fetch('check_username.php?username=' + encodeURIComponent(username))
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                alert('用户名已存在，请选择其他用户名');
                document.getElementById('mf_username').style.borderColor = '#dc3545';
            }
        })
        .catch(error => console.error('检查用户名失败:', error));
}
// 添加成功弹窗函数
function showSuccessModal(title, message, callback) {
    const html = `
        <div style="text-align:center;padding:20px;">
            <div style="font-size:60px;color:#28a745;margin-bottom:10px;">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 style="margin:10px 0;color:#333;">${title}</h3>
            <p style="color:#666;margin-bottom:20px;">${message}</p>
        </div>
    `;
    
    showAppModal(title, html, {
        showCancel: false,
        okText: '确定',
        okClass: 'btn-success',
        onOk: callback,
        width: '400px'
    });
}
// 添加确认弹窗函数
function confirmWithModal(title, html) {
    return new Promise((resolve) => {
        const modalHtml = html || `
            <div style="text-align:center;padding:20px;">
                <p style="color:#666;">确定要继续此操作吗？</p>
            </div>
        `;
        
        showAppModal(title, modalHtml, {
            showCancel: true,
            okText: '确定',
            cancelText: '取消',
            okClass: 'btn-primary',
            cancelClass: 'btn-default',
            onOk: () => resolve(true),
            onCancel: () => resolve(false),
            width: '400px'
        });
    });
}
// 添加错误弹窗函数
function showErrorModal(title, message) {
    const html = `
        <div style="text-align:center;padding:20px;">
            <div style="font-size:60px;color:#dc3545;margin-bottom:10px;">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h3 style="margin:10px 0;color:#333;">${title}</h3>
            <p style="color:#666;margin-bottom:20px;">${message}</p>
        </div>
    `;
    
    showAppModal(title, html, {
        showCancel: false,
        okText: '关闭',
        okClass: 'btn-danger',
        width: '400px'
    });
}
// 取消按钮 - 返回员工信息展示界面
document.getElementById('mf_cancel').addEventListener('click', function() {
    // 检查是否有未保存的更改（可选）
    let hasUnsavedChanges = false;
    
    // 可以检查表单字段是否有修改
    const form = document.getElementById('manageForm');
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        if (input.type !== 'hidden' && input.defaultValue !== input.value) {
            hasUnsavedChanges = true;
        }
    });
    
    if (!hasUnsavedChanges) {
        // 如果没有修改，直接返回
        window.location.href = 'employees.php';
        return;
    }
    
    const cancelHtml = `
        <div style="text-align:center;padding:15px;">
            <div style="font-size:48px;color:#ffc107;margin-bottom:15px;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h4 style="margin:0 0 10px 0;color:#333;">确认取消</h4>
            <p style="color:#666;margin:0;font-size:14px;">未保存的更改将会丢失，确定要取消吗？</p>
        </div>
    `;
    
    showAppModal('确认取消', cancelHtml, {
        showCancel: true,
        okText: '确定',
        cancelText: '取消',
        okClass: 'btn-danger',
        cancelClass: 'btn-default',
        width: '400px'
    }).then(confirmed => {
        if (confirmed) {
            window.location.href = 'employees.php';
        }
    });
});
// 表单验证
document.getElementById('manageForm').addEventListener('submit', function(e) {
    // 基本验证
    e.preventDefault();

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

    const usernameField = document.getElementById('mf_username');
    if (!usernameField.value.trim()) {
        isValid = false;
        usernameField.style.borderColor = '#dc3545';
        alert('用户名不能为空');
        if (!firstInvalidField) firstInvalidField = usernameField;
    } else if (usernameField.value.length < 3) {
        isValid = false;
        usernameField.style.borderColor = '#dc3545';
        alert('用户名至少3个字符');
        if (!firstInvalidField) firstInvalidField = usernameField;
    } else {
        usernameField.style.borderColor = '';
    }
    
    // 姓氏验证
    const firstNameField = document.getElementById('mf_first_name');
    if (!firstNameField.value.trim()) {
        isValid = false;
        firstNameField.style.borderColor = '#dc3545';
        alert('姓氏不能为空');
        if (!firstInvalidField) firstInvalidField = firstNameField;
    } else {
        firstNameField.style.borderColor = '';
    }
    
    // 名字验证
    const lastNameField = document.getElementById('mf_last_name');
    if (!lastNameField.value.trim()) {
        isValid = false;
        lastNameField.style.borderColor = '#dc3545';
        alert('名字不能为空');
        if (!firstInvalidField) firstInvalidField = lastNameField;
    } else {
        lastNameField.style.borderColor = '';
    }
    const phoneField = document.getElementById('mf_phone');
    if (phoneField.value.length != 11) {
        isValid = false;
        usernameField.style.borderColor = '#dc3545';
        alert('电话号码必须11位');
        if (!firstInvalidField) firstInvalidField = phoneField;
    }
    
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
    
    if (!isValid) {
        // 使用自定义弹窗显示错误
        const errorHtml = `
            <div style="text-align:center;padding:15px;">
                <div style="font-size:48px;color:#dc3545;margin-bottom:15px;">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h4 style="margin:0 0 10px 0;color:#333;">验证失败</h4>
                <p style="color:#666;margin:0;font-size:14px;">${errorMessage}</p>
            </div>
        `;
        
        showAppModal('验证失败', errorHtml, {
            showCancel: false,
            okText: '我知道了',
            okClass: 'btn-danger',
            width: '380px'
        });
        
        if (firstInvalidField) {
            firstInvalidField.focus();
        }
        return false;
    }
    
    // 显示保存确认弹窗
    const confirmHtml = `
        <div style="text-align:center;padding:15px;">
            <div style="font-size:48px;color:#28a745;margin-bottom:15px;">
                <i class="fas fa-question-circle"></i>
            </div>
            <h4 style="margin:0 0 10px 0;color:#333;">确认保存</h4>
            <p style="color:#666;margin:0;font-size:14px;">确定要保存员工信息吗？</p>
        </div>
    `;
    
    showAppModal('确认保存', confirmHtml, {
        showCancel: true,
        okText: '保存',
        cancelText: '取消',
        okClass: 'btn-success',
        cancelClass: 'btn-default',
        width: '400px'
    }).then(confirmed => {
        if (confirmed) {
            this.submit();
        }
    });
    
    return false;
});
// 使用showAppModal的确认函数
function showSaveConfirm() {
    return new Promise((resolve) => {
        const html = `
            <div style="text-align:center;padding:20px;">
                <div style="font-size:55px;color:#28a745;margin-bottom:15px;">
                    <i class="fas fa-save"></i>
                </div>
                <h3 style="margin:0 0 15px 0;color:#333;font-size:18px;">确认保存</h3>
                <p style="color:#666;margin:0;font-size:15px;">确定要保存员工信息吗？保存后信息将更新到系统中。</p>
            </div>
        `;
        
        showAppModal('确认保存', html, {
            showCancel: true,
            okText: '保存',
            cancelText: '取消',
            okClass: 'btn-success',
            cancelClass: 'btn-default',
            onOk: () => resolve(true),
            onCancel: () => resolve(false),
            width: '420px'
        });
    });
}

// 使用现有的showAppModal函数
function showConfirmModal(title, message) {
    return new Promise((resolve) => {
        const html = `
            <div style="text-align:center;padding:20px;">
                <div style="font-size:50px;color:#ffc107;margin-bottom:15px;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 style="margin:0 0 15px 0;color:#333;">${title}</h3>
                <p style="color:#666;margin:0;">${message}</p>
            </div>
        `;
        
        // 假设showAppModal函数存在
        showAppModal(title, html, {
            showCancel: true,
            okText: '确定',
            cancelText: '取消',
            okClass: 'btn-danger',
            cancelClass: 'btn-default',
            onOk: () => resolve(true),
            onCancel: () => resolve(false),
            width: '400px'
        });
    });
}


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