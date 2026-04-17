<?php
/**
 * Login Page
 */
require_once __DIR__ . '/../includes/auth.php';

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
    // Restart session so setFlash can store the message
    session_start();
    setFlash('success', 'You have been logged out.');
    redirect(SITE_URL . '/pages/login.php');
}

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email)) $errors[] = 'Email is required.';
    if (empty($password)) $errors[] = 'Password is required.';

    if (empty($errors)) {
        if (loginUser($email, $password)) {
            setFlash('success', 'Welcome back, ' . $_SESSION['user_name'] . '!');
            
            // Redirect admin to admin panel
            if (isAdmin()) {
                redirect(SITE_URL . '/admin/');
            }
            redirect(SITE_URL . '/index.php');
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card animate-slide-up">
        <h1>Welcome Back</h1>
        <p class="auth-subtitle">Sign in to your account to continue</p>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <span><?php echo implode('<br>', array_map('sanitize', $errors)); ?></span>
            <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" value="<?php echo sanitize($email ?? ''); ?>" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-full">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>

        <p class="auth-footer">
            Don't have an account? <a href="<?php echo SITE_URL; ?>/pages/register.php">Create one</a>
        </p>

        <div class="auth-divider">Demo Credentials</div>
        <div style="font-size: var(--font-size-xs); color: var(--text-muted); text-align: center; line-height: 1.8;">
           
            <strong>User:</strong> john@example.com / user123
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
