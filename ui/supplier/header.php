<?php
// 在header.php的顶部添加
require_once __DIR__ . '/inc/data.php';
$statusSummary = getOrderStatusSummary();
// 确定当前页面以设置active类
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<script>
    (function() {
        const userKey = <?php echo json_encode('supplier:' . ($_SESSION['supplier_username'] ?? '')); ?>;
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
            window.location.href = '../login/login.php';
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
                navigator.sendBeacon('../login/logout.php?beacon=1');
            } else {
                fetch('../login/logout.php?beacon=1', { method: 'GET', keepalive: true });
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
<header class="header">
    <div class="nav-container">
        <a href="dashboard.php" class="logo">鲜选生鲜 - 供应商管理平台</a>
        <ul class="nav-menu">
            <li class="nav-item"><a href="dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">数据概览</a></li>
            <li class="nav-item"><a href="stores.php" class="nav-link <?php echo $currentPage === 'stores.php' ? 'active' : ''; ?>">合作门店</a></li>
            <li class="nav-item">
             <a href="orders.php" class="nav-link <?php echo $currentPage === 'orders.php' ? 'active' : ''; ?>">
             订单管理 
             <span class="badge" id="unhandledOrderBadge">
               <?php 
               echo isset($statusSummary['pending']) && $statusSummary['pending'] > 0 
                ? $statusSummary['pending'] 
                : '0'; 
              ?>
              </span>
            </a>
         </li>
            <li class="nav-item"><a href="products_manage.php" class="nav-link <?php echo $currentPage === 'products_manage.php' ? 'active' : ''; ?>">产品管理</a></li>
            <li class="nav-item"><a href="profile.php" class="nav-link <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">供货商信息</a></li>
        </ul>
    </div>
</header>
