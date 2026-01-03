<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fresh Select Fresh Produce - <?php echo $pageTitle ?? 'Customer'; ?></title>
    <script>
        (function() {
            const userKey = <?php echo json_encode('customer:' . ($_SESSION['customer_id'] ?? '')); ?>;
            if (!userKey || userKey.endsWith(':')) {
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Microsoft YaHei', Arial, sans-serif;
        }
        body {
            background-color: #f8f9fa;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        /* 顶部导航栏 */
        .header {
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .nav-container {
            width: 90%;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2d884d; /* 生鲜绿色系 */
            text-decoration: none;
        }
        .nav-menu {
            display: flex;
            list-style: none;
        }
        .nav-item {
            margin-left: 30px;
        }
        .nav-link {
            text-decoration: none;
            color: #333;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
        }
        .nav-link:hover {
            color: #2d884d;
        }
        .nav-link.active {
            color: #2d884d;
        }
        .nav-link .badge {
            background-color: #ff4d4f;
            color: white;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
        }
        .main {
            width: 90%;
            margin: 30px auto;
            flex: 1;
        }
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
        }

    </style>
</head>
<body>
    <!-- 顶部导航栏 -->
    <header class="header">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">Fresh Select Fresh Produce.</a>
            <ul class="nav-menu">
                <li class="nav-item"><a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : ''; ?>" href="products.php">Products Select</a></li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'cart.php' ? 'active' : ''; ?>" href="cart.php">
                        Shopping cart <span class="badge">3</span>
                    </a>
                </li>
                <li class="nav-item"><a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : ''; ?>" href="orders.php">My orders</a></li>
                <li class="nav-item"><a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'account.php' ? 'active' : ''; ?>" href="account.php">Accounts</a></li>
            </ul>
        </div>
    </header>
    <main class="main">
