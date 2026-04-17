<?php
/**
 * Registration Page
 */
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($name)) $errors[] = 'Name is required.';
    if (empty($email)) $errors[] = 'Email is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $result = registerUser($name, $email, $password);
        if ($result['success']) {
            setFlash('success', 'Account created successfully! Please log in.');
            redirect(SITE_URL . '/pages/login.php');
        } else {
            $errors[] = $result['message'];
        }
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card animate-slide-up">
        <h1>Create Account</h1>
        <p class="auth-subtitle">Join MobileShop for the best mobile deals</p>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <span><?php echo implode('<br>', array_map('sanitize', $errors)); ?></span>
            <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Enter your full name" value="<?php echo sanitize($name ?? ''); ?>" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" value="<?php echo sanitize($email ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="At least 6 characters" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Repeat your password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-full">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <p class="auth-footer">
            Already have an account? <a href="<?php echo SITE_URL; ?>/pages/login.php">Sign in</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
