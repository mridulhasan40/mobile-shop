<?php
/**
 * Admin — Add Product
 */

// ── Process form BEFORE any HTML output ─────────────────────────────────────
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$db = getDB();

$categories = getCategories();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    }

    $name = trim($_POST['name'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $discountPrice = trim($_POST['discount_price'] ?? '');
    $discountPrice = $discountPrice !== '' ? (float)$discountPrice : null;
    $description = trim($_POST['description'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $featured = isset($_POST['featured']) ? 1 : 0;
    $image = 'default.png';

    if (empty($name)) $errors[] = 'Product name is required.';
    if (empty($brand)) $errors[] = 'Brand is required.';
    if ($price <= 0) $errors[] = 'Price must be greater than 0.';
    if ($discountPrice !== null && $discountPrice >= $price) $errors[] = 'Discount price must be less than the regular price.';
    if ($discountPrice !== null && $discountPrice < 0) $errors[] = 'Discount price cannot be negative.';

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadImage($_FILES['image']);
        if ($upload['success']) {
            $image = $upload['filename'];
        } else {
            $errors[] = $upload['message'];
        }
    }

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO products (name, brand, price, discount_price, description, category_id, stock, featured, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $brand, $price, $discountPrice, $description, $categoryId ?: null, $stock, $featured, $image]);

        setFlash('success', 'Product added successfully!');
        redirect(SITE_URL . '/admin/products.php');
    }
}

// ── Now include header (HTML output starts here) ────────────────────────────
$adminPageTitle = 'Add Product';
require_once __DIR__ . '/includes/header.php';
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
    <span><?php echo implode('<br>', array_map('sanitize', $errors)); ?></span>
    <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
</div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Product Details</h3>
        <a href="<?php echo SITE_URL; ?>/admin/products.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
    <div class="admin-card-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="name">Product Name *</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo sanitize($name ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="brand">Brand *</label>
                    <input type="text" id="brand" name="brand" class="form-control" value="<?php echo sanitize($brand ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="price">Price ($) *</label>
                    <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" value="<?php echo $price ?? ''; ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="discount_price">Discount Price ($) <small style="color:var(--text-muted);font-weight:400;">— leave empty for no discount</small></label>
                    <input type="number" id="discount_price" name="discount_price" class="form-control" step="0.01" min="0" value="<?php echo $discountPrice ?? ''; ?>" placeholder="e.g. 899.99">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="category_id">Category</label>
                    <select id="category_id" name="category_id" class="form-control">
                        <option value="0">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($categoryId ?? 0) == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo sanitize($cat['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="stock">Stock Quantity</label>
                    <input type="number" id="stock" name="stock" class="form-control" min="0" value="<?php echo $stock ?? 0; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Options</label>
                    <label class="filter-option" style="padding-top: var(--space-3);">
                        <input type="checkbox" name="featured" <?php echo ($featured ?? 0) ? 'checked' : ''; ?>>
                        Mark as Featured Product
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="5"><?php echo sanitize($description ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="image">Product Image</label>
                <div class="image-preview" id="image-preview">
                    <div class="placeholder">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Choose Image</span>
                    </div>
                </div>
                <input type="file" id="image" name="image" class="form-control" accept="image/*" onchange="previewImage(this)">
                <span class="form-text">Max 5MB. Accepted: JPG, PNG, WebP, GIF</span>
            </div>

            <div style="margin-top: var(--space-6); display: flex; gap: var(--space-4);">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus"></i> Add Product
                </button>
                <a href="<?php echo SITE_URL; ?>/admin/products.php" class="btn btn-secondary btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('image-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

        </div>
    </main>
</div>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
</body>
</html>
