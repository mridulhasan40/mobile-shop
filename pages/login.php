<?php
/**
 * Login Page
 */
require_once __DIR__ . '/../includes/auth.php';

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
    session_start();
    setFlash('success', 'You have been logged out.');
    redirect(SITE_URL . '/pages/login.php');
}

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email)) $errors[] = 'Email is required.';
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (empty($password)) $errors[] = 'Password is required.';

    if (empty($errors)) {
        // Rate limit check
        if (!checkLoginRateLimit($email)) {
            $errors[] = 'Too many failed attempts. Please wait 15 minutes before trying again.';
        } else {
            $result = loginUser($email, $password);

            if ($result === 'success') {
                setFlash('success', 'Welcome back, ' . $_SESSION['user_name'] . '!');
                if (isAdmin()) redirect(SITE_URL . '/admin/');
                redirect(SITE_URL . '/index.php');

            } elseif ($result === 'banned') {
                $errors[] = 'Your account has been suspended. Please contact support.';

            } else {
                recordLoginAttempt($email);
                $remaining = getRemainingAttempts($email);
                if ($remaining > 0) {
                    $errors[] = 'Invalid email or password. ' . $remaining . ' attempt(s) remaining.';
                } else {
                    $errors[] = 'Too many failed attempts. Please wait 15 minutes before trying again.';
                }
            }
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
                <input type="email" id="email" name="email" class="form-control"
                    placeholder="Enter your email"
                    value="<?php echo sanitize($email); ?>"
                    required autofocus>
            </div>

            <div class="form-group">
                <div class="flex justify-between items-center" style="margin-bottom: 6px;">
                    <label class="form-label" for="password" style="margin:0;">Password</label>
                    <a href="<?php echo SITE_URL; ?>/pages/forgot-password.php"
                       style="font-size:var(--font-size-xs); color:var(--accent-cyan);">
                        Forgot password?
                    </a>
                </div>
                <input type="password" id="password" name="password" class="form-control"
                    placeholder="Enter your password" required>
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
