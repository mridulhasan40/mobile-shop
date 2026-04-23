<?php
/**
 * Admin — Manage Categories
 */

// ── Process actions BEFORE any HTML output ──────────────────────────────────
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$db = getDB();

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if (!empty($name)) {
            $stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            try {
                $stmt->execute([$name, $description]);
                setFlash('success', 'Category added successfully!');
            } catch (PDOException $e) {
                setFlash('error', 'Category name already exists.');
            }
        }
        redirect(SITE_URL . '/admin/categories.php');
    }

    if ($_POST['action'] === 'edit') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if (!empty($name) && $id > 0) {
            $stmt = $db->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            try {
                $stmt->execute([$name, $description, $id]);
                setFlash('success', 'Category updated successfully!');
            } catch (PDOException $e) {
                setFlash('error', 'Category name already exists.');
            }
        }
        redirect(SITE_URL . '/admin/categories.php');
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$deleteId]);
    setFlash('success', 'Category deleted.');
    redirect(SITE_URL . '/admin/categories.php');
}

// ── Now include header (HTML output starts here) ────────────────────────────
$adminPageTitle = 'Categories';
require_once __DIR__ . '/includes/header.php';

// Fetch categories with product counts
$categories = $db->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.name")->fetchAll();

// Edit mode
$editCategory = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch();
}
?>

<!-- Add/Edit Category Form -->
<div class="admin-card" style="margin-bottom: var(--space-6);">
    <div class="admin-card-header">
        <h3><?php echo $editCategory ? 'Edit Category' : 'Add New Category'; ?></h3>
    </div>
    <div class="admin-card-body">
        <form method="POST">
            <input type="hidden" name="action" value="<?php echo $editCategory ? 'edit' : 'add'; ?>">
            <?php if ($editCategory): ?>
            <input type="hidden" name="id" value="<?php echo $editCategory['id']; ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="name">Category Name *</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo sanitize($editCategory['name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <input type="text" id="description" name="description" class="form-control" value="<?php echo sanitize($editCategory['description'] ?? ''); ?>" placeholder="Optional description">
                </div>
            </div>

            <div style="display: flex; gap: var(--space-3);">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-<?php echo $editCategory ? 'save' : 'plus'; ?>"></i>
                    <?php echo $editCategory ? 'Update Category' : 'Add Category'; ?>
                </button>
                <?php if ($editCategory): ?>
                <a href="<?php echo SITE_URL; ?>/admin/categories.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Categories Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3>All Categories (<?php echo count($categories); ?>)</h3>
    </div>
    <?php if (empty($categories)): ?>
    <div class="admin-card-body" style="text-align: center; color: var(--text-muted); padding: var(--space-10);">
        No categories yet. Add one above!
    </div>
    <?php else: ?>
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Products</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?php echo $cat['id']; ?></td>
                    <td style="font-weight: 600;"><?php echo sanitize($cat['name']); ?></td>
                    <td style="color: var(--text-secondary);"><?php echo sanitize($cat['description'] ?? '—'); ?></td>
                    <td><span class="badge badge-processing"><?php echo $cat['product_count']; ?></span></td>
                    <td>
                        <div class="admin-actions">
                            <a href="?edit=<?php echo $cat['id']; ?>" class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i></a>
                            <a href="?delete=<?php echo $cat['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this category? Products in this category will become uncategorized.')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
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
