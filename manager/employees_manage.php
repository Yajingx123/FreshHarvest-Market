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
    echo "<script>showError('" . addslashes($error) . "');</script>";
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
    customConfirm({
        title: '确认取消',
        message: '确定要取消吗？未保存的更改将会丢失。',
        type: 'warning',
        confirmText: '确定',
        cancelText: '取消',
        onConfirm: () => {
            window.location.href = 'employees.php';
        }
    });
});


// 表单验证
// 表单验证 - 修改这部分
document.getElementById('manageForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // 重置所有字段的边框颜色
    const requiredFields = this.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        field.style.borderColor = '';
    });
    
    // 清空之前的错误提示队列
    toastQueue = [];
    
    // 收集错误信息
    const errors = [];
    let firstInvalidField = null;
    
    // 检查必填字段
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#dc3545';
            const fieldName = field.previousElementSibling ? 
                field.previousElementSibling.textContent.replace('*', '').trim() : 
                '此项';
            errors.push(`${fieldName}不能为空`);
            if (!firstInvalidField) firstInvalidField = field;
        }
    });
    
    // 验证薪资
    const salaryField = document.getElementById('mf_salary');
    if (salaryField.value && parseFloat(salaryField.value) < 0) {
        salaryField.style.borderColor = '#dc3545';
        errors.push('薪资不能为负数');
        if (!firstInvalidField) firstInvalidField = salaryField;
    }
    
    // 验证邮箱
    const emailField = document.getElementById('mf_email');
    if (emailField.value.trim()) {
        if (!isValidEmail(emailField.value)) {
            emailField.style.borderColor = '#dc3545';
            errors.push('请输入有效的邮箱地址');
            if (!firstInvalidField) firstInvalidField = emailField;
        } else {
            try {
                const isDuplicate = await checkEmailDuplicate(emailField.value);
                if (isDuplicate) {
                    emailField.style.borderColor = '#dc3545';
                    errors.push('该邮箱已被其他员工使用，请使用其他邮箱');
                    if (!firstInvalidField) firstInvalidField = emailField;
                }
            } catch (error) {
                console.error('邮箱验证失败:', error);
                showError('邮箱验证失败，请稍后重试');
                return false;
            }
        }
    }
    
    // 验证电话
    const phoneField = document.getElementById('mf_phone');
    if (phoneField.value.trim()) {
        const phoneValidation = isValidPhone(phoneField.value);
        if (!phoneValidation.valid) {
            phoneField.style.borderColor = '#dc3545';
            errors.push(phoneValidation.message);
            if (!firstInvalidField) firstInvalidField = phoneField;
        }
    }
    
    // 验证日期格式
    const dateField = document.getElementById('mf_start');
    const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
    if (dateField.value && !dateRegex.test(dateField.value)) {
        dateField.style.borderColor = '#dc3545';
        errors.push('请选择完整的日期（格式：年-月-日）');
        if (!firstInvalidField) firstInvalidField = dateField;
    }
    
    // 如果有错误，显示所有错误
    if (errors.length > 0) {
        // 先显示第一条错误
        showError(errors[0], 'error');
        
        // 如果有更多错误，稍后显示
        if (errors.length > 1) {
            setTimeout(() => {
                errors.slice(1).forEach((error, index) => {
                    setTimeout(() => {
                        showError(error, 'error');
                    }, index * 300); // 每条错误间隔300ms显示
                });
            }, 500);
        }
        
        // 聚焦到第一个错误字段
        if (firstInvalidField) {
            firstInvalidField.focus();
        }
        return false;
    }
    
    // 所有验证通过，显示确认弹窗
    customConfirm({
        title: '确认保存',
        message: '确定要保存员工信息吗？',
        type: 'info',
        confirmText: '保存',
        cancelText: '取消',
        onConfirm: () => {
            const form = document.getElementById('manageForm');
            form.removeEventListener('submit', arguments.callee);
            form.submit();
        }
    });
});

// 邮箱验证函数
function isValidEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}
let toastQueue = [];
let isToastShowing = false;

// === 美观的提示窗口函数（带队列管理） ===
function showError(message, type = 'error', duration = 5000) {
    // 类型映射：error, success, warning
    const typeConfig = {
        'error': { title: '错误', icon: '❌', className: 'error-toast' },
        'success': { title: '成功', icon: '✅', className: 'success-toast error-toast' },
        'warning': { title: '警告', icon: '⚠️', className: 'warning-toast error-toast' }
    };
    
    const config = typeConfig[type] || typeConfig.error;
    
    // 创建弹窗配置对象
    const toastConfig = {
        message: message,
        type: type,
        config: config,
        duration: duration
    };
    
    // 添加到队列
    toastQueue.push(toastConfig);
    
    // 如果没有正在显示的弹窗，开始显示
    if (!isToastShowing) {
        showNextToast();
    }
}

// 显示下一个弹窗
function showNextToast() {
    if (toastQueue.length === 0) {
        isToastShowing = false;
        return;
    }
    
    isToastShowing = true;
    const toastConfig = toastQueue.shift();
    
    // 创建弹窗元素
    const toast = document.createElement('div');
    toast.className = toastConfig.config.className;
    toast.innerHTML = `
        <div class="error-icon">${toastConfig.config.icon}</div>
        <div class="error-content">
            <div class="error-title">${toastConfig.config.title}</div>
            <div class="error-message">${toastConfig.message}</div>
        </div>
        <button class="error-close" onclick="removeToast(this.parentElement)">×</button>
    `;
    
    // 设置位置（根据已有弹窗数量计算位置）
    const existingToasts = document.querySelectorAll('.error-toast:not(.removing)');
    const topPosition = 20 + (existingToasts.length * (toast.offsetHeight + 10));
    toast.style.top = `${topPosition}px`;
    
    // 添加到容器
    const container = document.getElementById('toast-container');
    if (!container) {
        // 如果容器不存在，创建并添加到body
        const newContainer = document.createElement('div');
        newContainer.id = 'toast-container';
        newContainer.style.cssText = 'position:fixed;top:20px;right:20px;z-index:10000;';
        document.body.appendChild(newContainer);
        newContainer.appendChild(toast);
    } else {
        container.appendChild(toast);
    }
    
    // 显示动画
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    // 自动消失（如果设置了duration）
    if (toastConfig.duration > 0) {
        setTimeout(() => {
            removeToast(toast);
        }, toastConfig.duration);
    }
    
    // 点击弹窗任何地方关闭（除了关闭按钮）
    toast.addEventListener('click', function(e) {
        if (!e.target.classList.contains('error-close')) {
            removeToast(toast);
        }
    });
    
    return toast;
}

// 移除弹窗
function removeToast(toast) {
    if (!toast) return;
    
    toast.classList.remove('show');
    toast.classList.add('removing');
    
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
        // 显示下一个弹窗
        setTimeout(showNextToast, 100);
    }, 300);
}

// 隐藏并移除弹窗
function hideToast(toast) {
    toast.classList.remove('show');
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 300);
}
// 电话验证函数
function isValidPhone(phone) {
    // 1. 检查是否全数字
    if (!/^\d+$/.test(phone)) {
        return { valid: false, message: '电话号码必须全为数字' };
    }
    
    // 2. 检查是否为12位
    if (phone.length !== 11) {
        return { valid: false, message: '电话号码必须是11位数字' };
    }
    
    return { valid: true, message: '' };
}
// 快捷函数
function showSuccess(message, duration = 3000) {
    return showError(message, 'success', duration);
}

function showWarning(message, duration = 4000) {
    return showError(message, 'warning', duration);
}
// === 自定义确认弹窗函数 ===
function customConfirm(options) {
    const {
        title = '确认',
        message = '确定要执行此操作吗？',
        type = 'info', // warning, danger, info, success
        confirmText = '确定',
        cancelText = '取消',
        showCancel = true,
        onConfirm = () => {},
        onCancel = () => {}
    } = options;
    
    // 类型映射
    const typeConfig = {
        'warning': { icon: '⚠️', className: 'warning' },
        'danger': { icon: '❌', className: 'danger' },
        'info': { icon: 'ℹ️', className: 'info' },
        'success': { icon: '✅', className: 'success' }
    };
    
    const config = typeConfig[type] || typeConfig.info;
    
    // 创建弹窗元素
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'custom-modal-overlay';
    modalOverlay.id = 'custom-modal-' + Date.now();
    
    modalOverlay.innerHTML = `
        <div class="custom-modal">
            <div class="custom-modal-header">
                <div class="custom-modal-icon ${config.className}">${config.icon}</div>
                <h3 class="custom-modal-title">${title}</h3>
                <button class="custom-modal-close" onclick="closeCustomModal('${modalOverlay.id}')">×</button>
            </div>
            <div class="custom-modal-content">
                ${message}
            </div>
            <div class="custom-modal-footer">
                ${showCancel ? `
                <button class="custom-modal-btn custom-modal-btn-secondary" onclick="handleCancel('${modalOverlay.id}')">
                    ${cancelText}
                </button>
                ` : ''}
                <button class="custom-modal-btn custom-modal-btn-primary" onclick="handleConfirm('${modalOverlay.id}')">
                    ${confirmText}
                </button>
            </div>
        </div>
    `;
    
    // 添加到容器
    const container = document.getElementById('custom-modal-container');
    if (!container) {
        // 如果容器不存在，创建并添加到body
        const newContainer = document.createElement('div');
        newContainer.id = 'custom-modal-container';
        document.body.appendChild(newContainer);
        container = newContainer;
    }
    container.appendChild(modalOverlay);
    
    // 存储回调函数
    modalOverlay._onConfirm = onConfirm;
    modalOverlay._onCancel = onCancel;
    
    // 显示动画
    setTimeout(() => {
        modalOverlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }, 10);
    
    return modalOverlay;
}

// 关闭自定义弹窗
function closeCustomModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            if (modal.parentElement) {
                modal.remove();
            }
        }, 300);
        document.body.style.overflow = '';
    }
}

// 处理确认
function handleConfirm(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        if (modal._onConfirm) {
            modal._onConfirm();
        }
        closeCustomModal(modalId);
    }
}

// 检查邮箱是否重复的函数
async function checkEmailDuplicate(email) {
    try {
        const response = await fetch(`check_email.php?email=${encodeURIComponent(email)}<?= $isEdit ? '&exclude_id=' . $empId : '' ?>`);
        const result = await response.json();
        
        if (result.success) {
            return result.exists;
        } else {
            console.error('邮箱检查失败:', result.message);
            return false;
        }
    } catch (error) {
        console.error('网络错误:', error);
        throw error;
    }
}

// 处理取消
function handleCancel(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        if (modal._onCancel) {
            modal._onCancel();
        }
        closeCustomModal(modalId);
    }
}

// 快捷函数
function confirmWarning(message, onConfirm, onCancel) {
    return customConfirm({
        title: '警告',
        message: message,
        type: 'warning',
        onConfirm: onConfirm,
        onCancel: onCancel
    });
}

function confirmDanger(message, onConfirm, onCancel) {
    return customConfirm({
        title: '危险操作',
        message: message,
        type: 'danger',
        onConfirm: onConfirm,
        onCancel: onCancel
    });
}

function confirmSuccess(message, onConfirm, onCancel) {
    return customConfirm({
        title: '操作确认',
        message: message,
        type: 'success',
        onConfirm: onConfirm,
        onCancel: onCancel
    });
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
/* 错误提示弹窗样式 */
.error-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background-color: #ff4444;
    color: white;
    padding: 16px 24px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(255, 68, 68, 0.3);
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 10000;
    transform: translateX(120%);
    transition: transform 0.3s ease;
    max-width: 400px;
    min-width: 300px;
}

.error-toast.show {
    transform: translateX(0);
}

.error-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.error-content {
    flex: 1;
}

.error-title {
    font-weight: 600;
    margin-bottom: 4px;
    font-size: 16px;
}

.error-message {
    font-size: 14px;
    opacity: 0.9;
    line-height: 1.4;
}

.error-close {
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.2s;
    padding: 0;
    margin-left: 8px;
}

.error-close:hover {
    opacity: 1;
}

/* 成功提示样式 */
.success-toast {
    background-color: #2d884d !important;
    box-shadow: 0 4px 12px rgba(45, 136, 77, 0.3) !important;
}

.success-toast .error-icon {
    color: #a8e6c1;
}

/* 警告提示样式 */
.warning-toast {
    background-color: #ff9900 !important;
    box-shadow: 0 4px 12px rgba(255, 153, 0, 0.3) !important;
}

.warning-toast .error-icon {
    color: #ffe0b3;
}
/* 自定义确认弹窗样式 */
.custom-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10001;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.custom-modal-overlay.show {
    opacity: 1;
    visibility: visible;
}

.custom-modal {
    background-color: white;
    border-radius: 12px;
    width: 90%;
    max-width: 420px;
    padding: 30px;
    position: relative;
    transform: translateY(-20px);
    transition: transform 0.3s ease;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

.custom-modal-overlay.show .custom-modal {
    transform: translateY(0);
}

.custom-modal-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.custom-modal-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.custom-modal-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0;
    flex: 1;
}

.custom-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #999;
    cursor: pointer;
    padding: 0;
    line-height: 1;
    transition: color 0.2s;
}

.custom-modal-close:hover {
    color: #ff4d4f;
}

.custom-modal-content {
    font-size: 16px;
    line-height: 1.5;
    color: #555;
    margin-bottom: 30px;
    padding: 0 5px;
}

.custom-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.custom-modal-btn {
    padding: 10px 24px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    min-width: 80px;
}

.custom-modal-btn-primary {
    background-color: #2d884d;
    color: white;
}

.custom-modal-btn-primary:hover {
    background-color: #236b3d;
    transform: translateY(-1px);
}

.custom-modal-btn-secondary {
    background-color: #f0f0f0;
    color: #666;
}

.custom-modal-btn-secondary:hover {
    background-color: #e0e0e0;
    color: #333;
}

.custom-modal-btn-danger {
    background-color: #ff4d4f;
    color: white;
}

.custom-modal-btn-danger:hover {
    background-color: #e03e40;
    transform: translateY(-1px);
}

/* 不同类型的图标颜色 */
.custom-modal-icon.warning {
    color: #ff9900;
}

.custom-modal-icon.danger {
    color: #ff4444;
}

.custom-modal-icon.info {
    color: #1890ff;
}

.custom-modal-icon.success {
    color: #2d884d;
}
/* 错误提示弹窗样式 - 支持多个排列 */
.error-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background-color: #ff4444;
    color: white;
    padding: 16px 24px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(255, 68, 68, 0.3);
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 10000;
    transform: translateX(120%);
    transition: transform 0.3s ease, opacity 0.3s ease;
    max-width: 400px;
    min-width: 300px;
    opacity: 0;
    margin-bottom: 10px;
}

.error-toast.show {
    transform: translateX(0);
    opacity: 1;
}

.error-toast.removing {
    opacity: 0;
    transform: translateX(120%);
}
</style>
