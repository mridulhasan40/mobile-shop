<?php
/**
 * Admin Sidebar Navigation
 */
$currentAdminPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar" id="admin-sidebar">
    <div class="admin-sidebar-header">
        <a href="<?php echo SITE_URL; ?>/admin/" class="logo">
            <i class="fas fa-mobile-screen-button"></i>
            <span>Mobile<strong>Shop</strong></span>
        </a>
        <div class="admin-label">Admin Panel</div>
    </div>

    <nav class="admin-nav">
        <div class="admin-nav-section">
            <div class="admin-nav-section-title">Main</div>
            <a href="<?php echo SITE_URL; ?>/admin/" class="<?php echo $currentAdminPage === 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
        </div>

        <div class="admin-nav-section">
            <div class="admin-nav-section-title">Management</div>
            <a href="<?php echo SITE_URL; ?>/admin/products.php" class="<?php echo in_array($currentAdminPage, ['products.php','add-product.php','edit-product.php']) ? 'active' : ''; ?>">
                <i class="fas fa-box"></i> Products
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/categories.php" class="<?php echo $currentAdminPage === 'categories.php' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="<?php echo $currentAdminPage === 'orders.php' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i> Orders
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/users.php" class="<?php echo $currentAdminPage === 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Users
            </a>
        </div>

        <div class="admin-nav-section">
            <div class="admin-nav-section-title">Other</div>
            <a href="<?php echo SITE_URL; ?>/" target="_blank">
                <i class="fas fa-external-link-alt"></i> View Site
            </a>
            <a href="<?php echo SITE_URL; ?>/pages/login.php?logout=1">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>
</aside>
