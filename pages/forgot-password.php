<?php
/**
 * Forgot Password Page
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

if (isLoggedIn()) redirect(SITE_URL . '/index.php');

$pageTitle = 'Forgot Password';
$errors    = [];
$success   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    }

    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        $errors[] = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        $token = createPasswordReset($email);
        // Always show success to prevent user enumeration
        if ($token) {
            sendPasswordResetEmail($email, $token);
        }
        $success = true;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card animate-slide-up">
        <div style="text-align:center; margin-bottom: var(--space-6);">
            <div style="width:64px;height:64px;background:linear-gradient(135deg,var(--accent-cyan),var(--accent-purple));border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto var(--space-4);">
                <i class="fas fa-lock" style="color:#fff;font-size:1.5rem;"></i>
            </div>
            <h1>Forgot Password?</h1>
            <p class="auth-subtitle">Enter your email and we'll send you a reset link</p>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <span><i class="fas fa-check-circle"></i> If that email is registered, a reset link has been sent. Check your inbox (and spam folder).</span>
        </div>
        <p style="text-align:center; margin-top: var(--space-6);">
            <a href="<?php echo SITE_URL; ?>/pages/login.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </p>
        <?php else: ?>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <span><?php echo implode('<br>', array_map('sanitize', $errors)); ?></span>
            <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control"
                    placeholder="Enter your registered email"
                    value="<?php echo sanitize($_POST['email'] ?? ''); ?>"
                    required autofocus>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-full">
                <i class="fas fa-paper-plane"></i> Send Reset Link
            </button>
        </form>

        <p class="auth-footer">
            Remember your password? <a href="<?php echo SITE_URL; ?>/pages/login.php">Sign in</a>
        </p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
