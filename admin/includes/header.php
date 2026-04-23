<?php
/**
 * Admin Header/Layout Start
 *
 * Pages that need to redirect (POST handlers) should include auth.php
 * and call requireAdmin() / getDB() themselves BEFORE including this file.
 * This file will skip re-initialising if already done.
 */
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/../../includes/auth.php';
}
if (!function_exists('requireAdmin') || !isset($db)) {
    requireAdmin();
    $db = getDB();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($adminPageTitle) ? sanitize($adminPageTitle) . ' — Admin' : 'Admin Panel'; ?> | <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
        <!-- Topbar -->
        <div class="admin-topbar">
            <div class="admin-topbar-left">
                <button class="admin-sidebar-toggle" id="sidebar-toggle" type="button">
                    <i class="fas fa-bars"></i>
                </button>
                <h1><?php echo sanitize($adminPageTitle ?? 'Dashboard'); ?></h1>
            </div>
            <div class="admin-topbar-right">
                <a href="<?php echo SITE_URL; ?>" class="btn btn-secondary btn-sm" target="_blank">
                    <i class="fas fa-external-link-alt"></i> View Site
                </a>
                <div class="user-menu">
                    <button class="user-menu-btn" id="user-menu-btn" type="button">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo sanitize($_SESSION['user_name']); ?></span>
                    </button>
                    <div class="user-dropdown" id="user-dropdown">
                        <a href="<?php echo SITE_URL; ?>/pages/profile.php"><i class="fas fa-user"></i> Profile</a>
                        <hr>
                        <a href="<?php echo SITE_URL; ?>/pages/login.php?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="admin-content">
            <?php displayFlash(); ?>
