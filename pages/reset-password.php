<?php
/**
 * Reset Password Page
 */
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) redirect(SITE_URL . '/index.php');

$pageTitle = 'Reset Password';
$errors    = [];
$success   = false;

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$reset = $token ? validatePasswordResetToken($token) : null;

if (!$reset && !empty($token)) {
    $errors[] = 'This reset link is invalid or has expired. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    }

    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $password2) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        if (resetPassword($token, $password)) {
            $success = true;
        } else {
            $errors[] = 'Reset failed. The link may have expired. Please request a new one.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card animate-slide-up">
        <div style="text-align:center; margin-bottom: var(--space-6);">
            <div style="width:64px;height:64px;background:linear-gradient(135deg,var(--accent-green),var(--accent-cyan));border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto var(--space-4);">
                <i class="fas fa-key" style="color:#fff;font-size:1.5rem;"></i>
            </div>
            <h1>Reset Password</h1>
            <p class="auth-subtitle">Create a new secure password for your account</p>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <span><i class="fas fa-check-circle"></i> Password reset successfully! You can now log in with your new password.</span>
        </div>
        <div style="text-align:center; margin-top: var(--space-6);">
            <a href="<?php echo SITE_URL; ?>/pages/login.php" class="btn btn-primary btn-lg">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </a>
        </div>

        <?php elseif (!$reset): ?>
        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <span><?php echo implode('<br>', array_map('sanitize', $errors)); ?></span>
        </div>
        <?php endif; ?>
        <p style="text-align:center; margin-top: var(--space-4);">
            <a href="<?php echo SITE_URL; ?>/pages/forgot-password.php" class="btn btn-secondary">
                <i class="fas fa-redo"></i> Request New Link
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
            <input type="hidden" name="token" value="<?php echo sanitize($token); ?>">

            <div class="form-group">
                <label class="form-label" for="password">New Password</label>
                <input type="password" id="password" name="password" class="form-control"
                    placeholder="Min. 8 characters" required autofocus minlength="8">
                <div id="strength-bar" style="height:4px;border-radius:2px;margin-top:6px;background:#e2e8f0;transition:all .3s;">
                    <div id="strength-fill" style="height:100%;border-radius:2px;width:0%;transition:all .3s;"></div>
                </div>
                <div id="strength-label" style="font-size:11px;color:var(--text-muted);margin-top:4px;"></div>
            </div>
            <div class="form-group">
                <label class="form-label" for="password2">Confirm New Password</label>
                <input type="password" id="password2" name="password2" class="form-control"
                    placeholder="Repeat your new password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-full">
                <i class="fas fa-save"></i> Reset Password
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
// Password strength meter
const pwdInput = document.getElementById('password');
const fill = document.getElementById('strength-fill');
const label = document.getElementById('strength-label');
if (pwdInput) {
    pwdInput.addEventListener('input', function() {
        const val = this.value;
        let score = 0;
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;
        const map = [
            { w: '25%', c: '#ef4444', t: 'Weak' },
            { w: '50%', c: '#f59e0b', t: 'Fair' },
            { w: '75%', c: '#3b82f6', t: 'Good' },
            { w: '100%', c: '#22c55e', t: 'Strong' },
        ];
        const s = map[Math.max(0, score - 1)];
        fill.style.width = s.w;
        fill.style.background = s.c;
        label.textContent = val.length > 0 ? 'Strength: ' + s.t : '';
        label.style.color = s.c;
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
