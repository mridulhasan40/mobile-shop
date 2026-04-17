<?php
/**
 * User Profile Page
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db = getDB();
$user = getCurrentUser();
$errors = [];
$success = false;

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    }

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    if (empty($name)) $errors[] = 'Name is required.';

    // Password change
    if (!empty($newPassword)) {
        if (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        }
        if (!password_verify($currentPassword, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        }
    }

    if (empty($errors)) {
        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, address = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $address, $hashedPassword, $_SESSION['user_id']]);
        } else {
            $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $address, $_SESSION['user_id']]);
        }

        $_SESSION['user_name'] = $name;
        setFlash('success', 'Profile updated successfully!');
        redirect(SITE_URL . '/pages/profile.php');
    }
}

// Get order stats
$orderStats = $db->prepare("SELECT COUNT(*) as total_orders, COALESCE(SUM(total_price), 0) as total_spent FROM orders WHERE user_id = ?");
$orderStats->execute([$_SESSION['user_id']]);
$stats = $orderStats->fetch();

$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-content">
    <div class="container">
        <div class="page-header">
            <div class="breadcrumb">
                <a href="<?php echo SITE_URL; ?>">Home</a>
                <span class="separator">/</span>
                <span>My Profile</span>
            </div>
            <h1 class="page-title">My Profile</h1>
        </div>

        <!-- Stats -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-card-icon cyan"><i class="fas fa-box"></i></div>
                <div class="stat-card-value"><?php echo $stats['total_orders']; ?></div>
                <div class="stat-card-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon green"><i class="fas fa-dollar-sign"></i></div>
                <div class="stat-card-value"><?php echo formatPrice($stats['total_spent']); ?></div>
                <div class="stat-card-label">Total Spent</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon purple"><i class="fas fa-calendar"></i></div>
                <div class="stat-card-value"><?php echo date('M Y', strtotime($user['created_at'])); ?></div>
                <div class="stat-card-label">Member Since</div>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <span><?php echo implode('<br>', array_map('sanitize', $errors)); ?></span>
            <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php endif; ?>

        <!-- Profile Form -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                <div>
                    <h3><?php echo sanitize($user['name']); ?></h3>
                    <p style="color: var(--text-muted); font-size: var(--font-size-sm);">
                        <?php echo sanitize($user['email']); ?> · 
                        <span class="badge badge-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span>
                    </p>
                </div>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <h4 style="margin-bottom: var(--space-5);">Personal Information</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="name">Full Name *</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo sanitize($user['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" class="form-control" value="<?php echo sanitize($user['email']); ?>" disabled>
                        <span class="form-text">Email cannot be changed</span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo sanitize($user['phone'] ?? ''); ?>" placeholder="Your phone number">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="address">Address</label>
                        <input type="text" id="address" name="address" class="form-control" value="<?php echo sanitize($user['address'] ?? ''); ?>" placeholder="Your delivery address">
                    </div>
                </div>

                <h4 style="margin: var(--space-8) 0 var(--space-5); padding-top: var(--space-6); border-top: 1px solid var(--border-color);">Change Password</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Enter current password">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Enter new password">
                        <span class="form-text">Leave blank to keep current password</span>
                    </div>
                </div>

                <div style="margin-top: var(--space-6);">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
