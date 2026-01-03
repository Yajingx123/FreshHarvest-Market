<?php
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>鲜选生鲜 - CEO</title>
    <style>
        /* 全局核心样式（保留所有必要功能，无冗余） */
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Microsoft YaHei',Arial,sans-serif;}
        body{background:#f8f9fa;color:#333;}
        
        /* 顶部导航（按新架构简化） */
        .header{background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.1);position:sticky;top:0;z-index:999;}
        .nav-container{width:90%;margin:0 auto;display:flex;justify-content:space-between;align-items:center;padding:15px 0;}
        .logo{font-size:20px;font-weight:700;color:#1976d2;text-decoration:none;}
        .nav-menu{display:flex;list-style:none;gap:18px;}
        .nav-link{color:#333;text-decoration:none;font-weight:500;transition:color 0.3s;}
        .nav-link:hover, .nav-link.active{color:#1976d2;}
        .nav-link .badge{background:#ff4d4f;color:white;font-size:12px;padding:2px 6px;border-radius:10px;margin-left:5px;}
        
        /* 主内容区（按新架构统一容器） */
        main{width:90%;margin:30px auto;min-height:calc(100vh - 200px);}
        
        /* 通用模块样式（兼容所有页面） */
        .section{background:#fff;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.05);padding:25px;margin-bottom:30px;}
        .section-title{font-size:20px;font-weight:600;margin-bottom:20px;color:#1976d2;border-left:4px solid #1976d2;padding-left:10px;}
        
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        
        /* 仪表盘卡片（数据概览用） */
        .dashboard-cards{display:flex;gap:20px;flex-wrap:wrap;}
        .card{flex:1;min-width:200px;background:#e3f2fd;border-radius:8px;padding:20px;text-align:center;}
        .card-icon{font-size:32px;color:#1976d2;margin-bottom:10px;}
        .card-title{font-size:16px;margin-bottom:5px;color:#666;}
        .card-value{font-size:24px;font-weight:bold;color:#333;}
        
        /* 表格样式（门店/产品/交易用） */
        .data-table{width:100%;border-collapse:collapse;margin-top:15px;}
        .data-table th, .data-table td{padding:12px 15px;text-align:left;border-bottom:1px solid #eee;}
        .data-table th{background:#f5f9ff;color:#1976d2;font-weight:600;}
        .data-table tr:hover{background:#fafafa;}
        
        /* 按钮样式（所有操作按钮兼容） */
        .btn{padding:8px 16px;border-radius:6px;border:none;font-size:14px;cursor:pointer;transition:background-color 0.3s;}
        .btn-primary{background:#1976d2;color:white;}
        .btn-primary:hover{background:#1565c0;}
        .btn-success{background:#43a047;color:white;}
        .btn-success:hover{background:#388e3c;}
        .btn-warning{background:#fbc02d;color:#333;}
        .btn-warning:hover{background:#f9a825;}
        .btn-danger{background:#e53935;color:white;}
        .btn-danger:hover{background:#d32f2f;}
        
        /* 筛选栏（门店/产品搜索用） */
        .filter-bar{display:flex;gap:15px;margin-bottom:20px;flex-wrap:wrap;align-items:center;}
        .filter-input{padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;width:200px;}
        .filter-select{padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;width:180px;}
        .partners-status-btn.active{background:#1976d2;color:#fff;}
        
        /* 表单样式（供应商资料/新增编辑用） */
        .form-group{margin-bottom:20px;}
        .form-label{display:block;margin-bottom:8px;font-weight:500;color:#666;}
        .form-control{width:100%;padding:10px 15px;border:1px solid #ddd;border-radius:6px;font-size:16px;}
        .form-row{display:flex;gap:20px;flex-wrap:wrap;}
        .form-col{flex:1;min-width:250px;}
        
        /* 状态标签（所有状态展示兼容） */
        .status-tag{padding:4px 8px;border-radius:12px;font-size:12px;font-weight:500;}
        .status-pending{background:#fff3e0;color:#ff9800;}
        .status-accepted{background:#e8f5e9;color:#43a047;}
        .status-shipped{background:#e3f2fd;color:#1976d2;}
        .status-completed{background:#f3e5f5;color:#8e24aa;}
        .status-rejected{background:#ffebee;color:#e53935;}
        
        /* 模态弹窗（所有页面共用） */
        .modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:2000;}
        .modal.show{display:flex;}
        .modal-backdrop{position:absolute;inset:0;background:rgba(0,0,0,0.45);}
        .modal-content{position:relative;z-index:2;background:white;border-radius:10px;padding:18px;min-width:320px;max-width:720px;box-shadow:0 12px 30px rgba(0,0,0,0.25);}
        .modal-header{display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #eee;padding-bottom:10px;margin-bottom:12px;}
        .modal-body{max-height:60vh;overflow:auto;padding-right:6px;}
        .modal-close{cursor:pointer;border:none;background:transparent;font-size:16px;color:#999;}
        
        /* 响应式调整（保持原页面适配） */
        @media (max-width:768px){
            .nav-menu{display:none;}
            .dashboard-cards{flex-direction:column;}
            .form-row{flex-direction:column;gap:10px;}
            .filter-bar{flex-direction:column;align-items:flex-start;}
            .filter-input, .filter-select{width:100%;}
            .data-table th, .data-table td{padding:8px 10px;font-size:14px;}
            .btn{padding:6px 12px;font-size:13px;}
        }
        .loading-text {
           display: inline-block;
           animation: pulse 1.5s infinite;
           color: #ff4757;
        }
        @keyframes pulse {
           0% { opacity: 1; }
           50% { opacity: 0.5; }
           100% { opacity: 1; }
        }
    </style>
    <script>
        (function() {
            const userKey = <?php echo json_encode('manager:' . ($_SESSION['manager_username'] ?? '')); ?>;
            if (!userKey) {
                return;
            }
            if (!window.name) {
                window.name = 'fhwin_' + Math.random().toString(36).slice(2) + Date.now();
            }
            const token = window.name;
            const activeKey = 'fh_active_window_' + userKey;
            const now = Date.now();
            let state = null;
            try {
                state = JSON.parse(localStorage.getItem(activeKey) || 'null');
            } catch (e) {
                state = null;
            }
            if (state && state.token && state.token !== token && now - state.last < 10000) {
                alert('该账号已在其他窗口登录，请关闭当前窗口或使用原窗口。');
                window.location.href = '../../login/login.php';
                return;
            }
            localStorage.setItem(activeKey, JSON.stringify({ token, last: now }));
            setInterval(function() {
                localStorage.setItem(activeKey, JSON.stringify({ token, last: Date.now() }));
            }, 5000);

            function clearActiveKey() {
                try {
                    const current = JSON.parse(localStorage.getItem(activeKey) || 'null');
                    if (current && current.token === token) {
                        localStorage.removeItem(activeKey);
                    }
                } catch (e) {
                }
            }

            function sendLogoutBeacon() {
                if (window.__fhInternalNav) {
                    return;
                }
                clearActiveKey();
                if (navigator.sendBeacon) {
                    navigator.sendBeacon('../../login/logout.php?beacon=1');
                } else {
                    fetch('../../login/logout.php?beacon=1', { method: 'GET', keepalive: true });
                }
            }

            document.addEventListener('click', function(e) {
                const link = e.target.closest('a');
                if (link && link.href && link.origin === window.location.origin) {
                    window.__fhInternalNav = true;
                }
            }, true);

            document.addEventListener('submit', function() {
                window.__fhInternalNav = true;
            }, true);

            window.addEventListener('beforeunload', sendLogoutBeacon);
            window.addEventListener('pagehide', sendLogoutBeacon);
        })();
    </script>
    <!-- 引入Chart.js（数据概览折线图用） -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<!-- 顶部导航（按新架构渲染，动态激活当前页面） -->
<header class="header">
    <div class="nav-container">
        <a href="overview.php" class="logo">鲜选生鲜 - CEO端</a>
        <?php
        $curPage = basename($_SERVER['PHP_SELF']);
        // 导航链接配置（按新架构简化）
        $navLinks = [
            '概况' => 'overview.php',
            '门店列表' => 'stores.php',
            '货品信息' => 'goods.php',
            '员工信息' => 'employees.php',     // 新增
            '顾客信息' => 'customers.php',     // 新增
            '销售情况' => 'saletrend.php',
            '供应商信息' => 'sup_Info.php',
            '退出登录' => 'logout',
            '修改密码' => 'change_password.php'
        ];
        echo '<ul class="nav-menu">';
        foreach ($navLinks as $label => $url) {
          if ($label === '退出登录') {
             echo "<li><a class='nav-link logout-btn' href='javascript:void(0)'>{$label}</a></li>";
          } else {
            $activeClass = ($curPage === basename($url)) ? 'active' : '';
            echo "<li><a class='nav-link {$activeClass}' href='{$url}'>{$label}</a></li>";
          }
        }
        echo '</ul>';
?>
    </div>
</header>

<!-- 主内容区（所有页面内容写在这个标签内） -->
<main>
    <!-- 全局公共模态框（所有页面复用，无需重复渲染） -->
    <div id="appModal" class="modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="modal-backdrop"></div>
        <div class="modal-content" role="document">
            <div class="modal-header">
                <h3 id="appModalTitle" style="margin:0;font-size:18px;color:#1976d2;">消息</h3>
                <button class="modal-close" aria-label="关闭">✕</button>
            </div>
            <div class="modal-body" id="appModalBody">
                <p style="color:#666;margin:0;">这里显示信息</p>
            </div>
            <div style="text-align:right;margin-top:12px;">
                <button class="btn btn-primary" id="appModalConfirm">确定</button>
                <button class="btn" id="appModalCancel" style="margin-left:8px;">取消</button>
            </div>
        </div>
    </div>

    <!-- 全局公共模态框函数（所有页面直接调用，无需重复编写） -->
    <script>
// 退出登录事件监听
document.addEventListener('DOMContentLoaded', function() {
    // 监听所有退出登录按钮
    document.querySelectorAll('.logout-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            confirmLogout(e);
        });
    });
    
    // 如果用户直接访问logout.php相关页面，也触发退出
    if (window.location.pathname.includes('logout')) {
        confirmLogout();
    }
});

async function confirmLogout(e) {
    if (e) e.preventDefault();
    
    const confirmed = await showAppModal('确认退出', '确定要退出登录吗？<br>退出后需要重新登录才能访问系统。', {
        okText: '确认退出',
        cancelText: '取消',
        enterConfirm: true
    });
    
    if (confirmed) {
        // 显示加载状态
        const logoutLinks = document.querySelectorAll('.logout-btn, .nav-link[href*="logout"]');
        logoutLinks.forEach(link => {
            link.innerHTML = '<span class="loading-text">退出中...</span>';
            link.style.pointerEvents = 'none';
        });
        
        // 跳转到logout.php
        setTimeout(() => {
            window.location.href = '../../login/logout.php';
        }, 800);
    }
}
        function showAppModal(title, html, opts = {}) {
            const modal = document.getElementById('appModal');
            document.getElementById('appModalTitle').textContent = title || '消息';
            document.getElementById('appModalBody').innerHTML = html || '';
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            const okBtn = document.getElementById('appModalConfirm');
            const cancelBtn = document.getElementById('appModalCancel');
            okBtn.textContent = opts.okText || '确定';
            cancelBtn.textContent = opts.cancelText || '取消';
            cancelBtn.style.display = opts.showCancel === false ? 'none' : 'inline-block';

            return new Promise(resolve => {
                function cleanup() {
                    okBtn.removeEventListener('click', onOk);
                    cancelBtn.removeEventListener('click', onCancel);
                    document.removeEventListener('keydown', onKey);
                    modal.querySelector('.modal-backdrop').removeEventListener('click', onCancel);
                    modal.querySelector('.modal-close').removeEventListener('click', onCancel);
                }
                function onOk() { cleanup(); closeAppModal(); resolve(true); }
                function onCancel() { cleanup(); closeAppModal(); resolve(false); }
                function onKey(e) {
                    if (e.key === 'Escape') onCancel();
                    if (e.key === 'Enter' && opts.enterConfirm) onOk();
                }

                okBtn.addEventListener('click', onOk);
                cancelBtn.addEventListener('click', onCancel);
                document.addEventListener('keydown', onKey);
                modal.querySelector('.modal-backdrop').addEventListener('click', onCancel);
                modal.querySelector('.modal-close').addEventListener('click', onCancel);
            });
        }

        function closeAppModal() {
            const modal = document.getElementById('appModal');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    </script>
<?php ?>
