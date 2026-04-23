<?php
/**
 * Admin — Coupon Management
 */

// ── Process form BEFORE any HTML output ─────────────────────────────────────
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$db = getDB();

$errors  = [];
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $code     = strtoupper(trim($_POST['code'] ?? ''));
        $type     = $_POST['type'] ?? 'percentage';
        $value    = (float)($_POST['value'] ?? 0);
        $minOrder = (float)($_POST['min_order'] ?? 0);
        $maxUses  = $_POST['max_uses'] !== '' ? (int)$_POST['max_uses'] : null;
        $expires  = !empty($_POST['expires_at']) ? $_POST['expires_at'] . ' 23:59:59' : null;

        if (empty($code))   $errors[] = 'Coupon code is required.';
        if ($value <= 0)    $errors[] = 'Value must be greater than 0.';
        if (!in_array($type, ['percentage','fixed'])) $errors[] = 'Invalid type.';
        if ($type === 'percentage' && $value > 100) $errors[] = 'Percentage cannot exceed 100%.';

        if (empty($errors)) {
            try {
                $db->prepare("INSERT INTO coupons (code, type, value, min_order, max_uses, expires_at) VALUES (?, ?, ?, ?, ?, ?)")
                   ->execute([$code, $type, $value, $minOrder, $maxUses, $expires]);
                setFlash('success', "Coupon <strong>{$code}</strong> created successfully.");
                redirect(SITE_URL . '/admin/coupons.php');
            } catch (Exception $e) {
                $errors[] = 'Coupon code already exists or an error occurred.';
            }
        }
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['coupon_id'] ?? 0);
        $db->prepare("UPDATE coupons SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
        setFlash('success', 'Coupon status updated.');
        redirect(SITE_URL . '/admin/coupons.php');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['coupon_id'] ?? 0);
        $db->prepare("DELETE FROM coupons WHERE id = ?")->execute([$id]);
        setFlash('success', 'Coupon deleted.');
        redirect(SITE_URL . '/admin/coupons.php');
    }
}

// ── Now include header (HTML output starts here) ────────────────────────────
$adminPageTitle = 'Coupons';
require_once __DIR__ . '/includes/header.php';

$coupons = $db->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-error"><?php echo implode('<br>', array_map('sanitize', $errors)); ?><button class="alert-close" onclick="this.parentElement.remove()">&times;</button></div>
<?php endif; ?>

<!-- Create Coupon -->
<div class="admin-card" style="margin-bottom:var(--space-8);">
    <div class="admin-card-header">
        <h3><i class="fas fa-plus-circle" style="color:var(--accent-green);margin-right:8px;"></i>Create New Coupon</h3>
    </div>
    <div class="admin-card-body">
        <form method="POST" action="">
            <input type="hidden" name="action" value="create">
            <div class="grid grid-2" style="gap:var(--space-4);">
                <div class="form-group">
                    <label class="form-label">Code *</label>
                    <input type="text" name="code" class="form-control" placeholder="e.g. SAVE20"
                           style="text-transform:uppercase;" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Type *</label>
                    <select name="type" class="form-control" id="coupon-type" onchange="updateTypeHint()">
                        <option value="percentage">Percentage (%)</option>
                        <option value="fixed">Fixed Amount ($)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Value * <span id="type-hint">(e.g. 10 = 10% off)</span></label>
                    <input type="number" name="value" class="form-control" step="0.01" min="0.01" placeholder="10" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Minimum Order ($)</label>
                    <input type="number" name="min_order" class="form-control" step="0.01" min="0" value="0" placeholder="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Max Uses (blank = unlimited)</label>
                    <input type="number" name="max_uses" class="form-control" min="1" placeholder="e.g. 100">
                </div>
                <div class="form-group">
                    <label class="form-label">Expiry Date (blank = no expiry)</label>
                    <input type="date" name="expires_at" class="form-control" min="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Coupon
            </button>
        </form>
    </div>
</div>

<!-- Coupon List -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3>All Coupons (<?php echo count($coupons); ?>)</h3>
    </div>
    <?php if (empty($coupons)): ?>
    <div class="admin-card-body" style="text-align:center;color:var(--text-muted);padding:var(--space-10);">
        <i class="fas fa-ticket-alt" style="font-size:2rem;display:block;margin-bottom:var(--space-4);"></i>
        No coupons yet
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Code</th><th>Type</th><th>Value</th><th>Min Order</th>
                    <th>Uses</th><th>Expires</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $c): ?>
                <tr>
                    <td><code style="font-size:.85rem;font-weight:700;"><?php echo sanitize($c['code']); ?></code></td>
                    <td><?php echo ucfirst($c['type']); ?></td>
                    <td style="font-weight:600;">
                        <?php echo $c['type']==='percentage' ? $c['value'].'%' : formatPrice($c['value']); ?>
                    </td>
                    <td><?php echo formatPrice($c['min_order']); ?></td>
                    <td>
                        <?php echo $c['used_count']; ?>
                        <?php echo $c['max_uses'] ? '/ '.$c['max_uses'] : '/ ∞'; ?>
                    </td>
                    <td><?php echo $c['expires_at'] ? date('M d, Y', strtotime($c['expires_at'])) : '—'; ?></td>
                    <td>
                        <span class="badge <?php echo $c['is_active'] ? 'badge-processing' : 'badge-cancelled'; ?>">
                            <?php echo $c['is_active'] ? 'Active' : 'Disabled'; ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="coupon_id" value="<?php echo $c['id']; ?>">
                            <button class="btn btn-secondary btn-sm" type="submit" title="Toggle status">
                                <i class="fas <?php echo $c['is_active'] ? 'fa-pause' : 'fa-play'; ?>"></i>
                            </button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="coupon_id" value="<?php echo $c['id']; ?>">
                            <button class="btn btn-danger btn-sm" type="submit"
                                onclick="return confirm('Delete coupon <?php echo sanitize($c['code']); ?>?')" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

    </div></main>
</div>

<script>
function updateTypeHint() {
    const type = document.getElementById('coupon-type').value;
    document.getElementById('type-hint').textContent =
        type === 'percentage' ? '(e.g. 10 = 10% off)' : '(e.g. 20 = $20 off)';
}
</script>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
</body>
</html>
