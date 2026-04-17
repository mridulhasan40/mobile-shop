<?php
/**
 * Admin — Manage Users
 */
$adminPageTitle = 'Users';
require_once __DIR__ . '/includes/header.php';

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['role'])) {
    $userId = (int)$_POST['user_id'];
    $role = $_POST['role'];

    if (in_array($role, ['user', 'admin']) && $userId !== $_SESSION['user_id']) {
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$role, $userId]);
        setFlash('success', 'User role updated.');
    }
    redirect(SITE_URL . '/admin/users.php');
}

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    if ($deleteId !== $_SESSION['user_id']) {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$deleteId]);
        setFlash('success', 'User deleted.');
    } else {
        setFlash('error', 'You cannot delete your own account.');
    }
    redirect(SITE_URL . '/admin/users.php');
}

// Fetch users
$search = trim($_GET['search'] ?? '');
$where = '';
$params = [];
if ($search) {
    $where = "WHERE name LIKE ? OR email LIKE ?";
    $searchTerm = "%{$search}%";
    $params = [$searchTerm, $searchTerm];
}

$users = $db->prepare("SELECT u.*, (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count, (SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE user_id = u.id AND status != 'cancelled') as total_spent FROM users u $where ORDER BY u.created_at DESC");
$users->execute($params);
$users = $users->fetchAll();
?>

<div class="flex justify-between items-center" style="margin-bottom: var(--space-6);">
    <form method="GET" class="header-search" style="max-width: 300px; display: flex;">
        <i class="fas fa-search"></i>
        <input type="text" name="search" placeholder="Search users..." value="<?php echo sanitize($search); ?>">
    </form>
    <span style="color: var(--text-muted); font-size: var(--font-size-sm);"><?php echo count($users); ?> users</span>
</div>

<div class="admin-card">
    <?php if (empty($users)): ?>
    <div class="admin-card-body" style="text-align: center; color: var(--text-muted); padding: var(--space-10);">
        No users found.
    </div>
    <?php else: ?>
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Orders</th>
                    <th>Total Spent</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div style="width:36px;height:36px;border-radius:50%;background:var(--gradient-primary);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:var(--font-size-sm);color:white;">
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                            <span style="font-weight: 600;"><?php echo sanitize($user['name']); ?></span>
                        </div>
                    </td>
                    <td style="color: var(--text-secondary);"><?php echo sanitize($user['email']); ?></td>
                    <td>
                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <select name="role" class="status-select" onchange="this.form.submit()">
                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </form>
                        <?php else: ?>
                        <span class="badge badge-admin"><?php echo ucfirst($user['role']); ?> (You)</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-processing"><?php echo $user['order_count']; ?></span></td>
                    <td style="font-weight: 600;"><?php echo formatPrice($user['total_spent']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                        <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user and all their data?')">
                            <i class="fas fa-trash"></i>
                        </a>
                        <?php else: ?>
                        <span style="color: var(--text-muted); font-size: var(--font-size-xs);">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

        </div>
    </main>
</div>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
</body>
</html>
