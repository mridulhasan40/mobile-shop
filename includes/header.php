<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';

$cartCount     = getCartCount();
$wishlistCount = getWishlistCount();
$currentPage   = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo isset($pageDesc) ? sanitize($pageDesc) : 'MobileShop — Premium mobile phones &amp; accessories. Browse the latest smartphones from Samsung, Apple, Xiaomi &amp; more.'; ?>">
    <meta name="site-url" content="<?php echo SITE_URL; ?>">
    <title><?php echo isset($pageTitle) ? sanitize($pageTitle) . ' — ' . SITE_NAME : SITE_NAME . ' — Premium Mobile Store'; ?></title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>

<!-- Header -->
<header class="header" id="main-header">
    <div class="container header-inner">
        <!-- Logo -->
        <a href="<?php echo SITE_URL; ?>" class="logo">
            <i class="fas fa-mobile-screen-button"></i>
            <span>Mobile<strong>Shop</strong></span>
        </a>

        <!-- Navigation -->
        <nav class="nav-links" id="nav-links">
            <a href="<?php echo SITE_URL; ?>" class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="<?php echo SITE_URL; ?>/pages/products.php" class="<?php echo $currentPage === 'products.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Products
            </a>
            <?php if (isLoggedIn()): ?>
            <a href="<?php echo SITE_URL; ?>/pages/orders.php" class="<?php echo $currentPage === 'orders.php' ? 'active' : ''; ?>">
                <i class="fas fa-box"></i> My Orders
            </a>
            <?php endif; ?>
        </nav>

        <!-- Search -->
        <div class="header-search" id="header-search">
            <i class="fas fa-search"></i>
            <input type="text" id="search-input" placeholder="Search phones, accessories..." autocomplete="off">
            <div class="search-results" id="search-results"></div>
        </div>

        <!-- Actions -->
        <div class="header-actions">
            <!-- Wishlist -->
            <?php if (isLoggedIn()): ?>
            <a href="<?php echo SITE_URL; ?>/pages/wishlist.php" class="cart-btn" id="wishlist-btn"
               title="My Wishlist"
               style="<?php echo $currentPage === 'wishlist.php' ? 'color:var(--accent-cyan);' : ''; ?>">
                <i class="fas fa-heart"></i>
                <?php if ($wishlistCount > 0): ?>
                <span class="cart-badge" id="wishlist-badge"><?php echo $wishlistCount; ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>

            <!-- Cart -->
            <a href="<?php echo SITE_URL; ?>/pages/cart.php" class="cart-btn" id="cart-btn">
                <i class="fas fa-shopping-bag"></i>
                <span>Cart</span>
                <?php if ($cartCount > 0): ?>
                <span class="cart-badge" id="cart-badge"><?php echo $cartCount; ?></span>
                <?php endif; ?>
            </a>

            <?php if (isLoggedIn()): ?>
            <!-- User Menu -->
            <div class="user-menu">
                <button class="user-menu-btn" id="user-menu-btn" type="button">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo sanitize($_SESSION['user_name']); ?></span>
                    <i class="fas fa-chevron-down" style="font-size: 10px;"></i>
                </button>
                <div class="user-dropdown" id="user-dropdown">
                    <a href="<?php echo SITE_URL; ?>/pages/profile.php">
                        <i class="fas fa-user"></i> My Profile
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/orders.php">
                        <i class="fas fa-box"></i> My Orders
                    </a>
                    <?php if (isAdmin()): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/">
                        <i class="fas fa-shield-halved"></i> Admin Panel
                    </a>
                    <?php endif; ?>
                    <hr>
                    <a href="<?php echo SITE_URL; ?>/pages/login.php?logout=1">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
            <?php else: ?>
            <a href="<?php echo SITE_URL; ?>/pages/login.php" class="btn btn-primary btn-sm">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
            <?php endif; ?>

            <!-- Mobile Menu Toggle -->
            <button class="menu-toggle" id="menu-toggle" type="button">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</header>

<!-- Flash Messages -->
<div class="container" style="position:relative; z-index:1;">
    <?php displayFlash(); ?>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>
