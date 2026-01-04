<?php
require_once __DIR__ . '/inc/data.php';

// Only check cart count for signed-in users.
$cart_count = 0;
if (isset($_SESSION['customer_id'])) {
    $customer_id = $_SESSION['customer_id'];
    $cart_count = getCartItemCount($customer_id);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshHarvest - <?php echo $pageTitle ?? 'Customer'; ?></title>
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
        /* Top navigation */
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
            color: #2d884d; /* Fresh green */
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
    <!-- Top navigation -->
    <header class="header">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">FreshHarvest</a>
            <ul class="nav-menu">
                <li class="nav-item"><a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : ''; ?>" href="products.php">Products</a></li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'cart.php' ? 'active' : ''; ?>" href="cart.php">
                        Cart
                        <?php if ($cart_count > 0): ?>
                            <span class="badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item"><a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : ''; ?>" href="orders.php">My Orders</a></li>
                <li class="nav-item"><a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'account.php' ? 'active' : ''; ?>" href="account.php">Account</a></li>
            </ul>
        </div>
    </header>
    <main class="main">
