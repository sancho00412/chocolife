<?php
require __DIR__ . '/includes/page.php';

$user = requirePageAdmin();
$db = getDb();

// admin-product-form.php -> new offer, admin-product-form.php?id=5 -> edit offer 5
$productId = (int) ($_GET['id'] ?? 0);
$product = null;

if ($productId > 0) {
    $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $product = $stmt->fetch() ?: null;

    if (!$product) {
        http_response_code(404);
    }
}

$categories = $db->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$partners   = $db->query('SELECT id, name FROM partners ORDER BY name')->fetchAll();

$isEdit = $product !== null;
$value  = fn(string $key, string $default = '') => e((string) ($product[$key] ?? $default));

$pageTitle   = $isEdit ? 'Edit Offer' : ($productId > 0 ? 'Offer not found' : 'Add Offer');
$activePage  = $isEdit ? 'products' : 'product-form';
$pageScripts = ['admin-product-form.js'];

require __DIR__ . '/includes/admin-header.php';
?>

<?php if ($productId > 0 && !$isEdit): ?>
<section class="admin-card">
    <p>This offer does not exist. <a href="admin-products.php">Back to offers</a></p>
</section>
<?php else: ?>
<section class="admin-card form-card">
    <a href="admin-products.php" class="back-link">← Back to offers</a>

    <form id="product-form" class="admin-form" novalidate enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $isEdit ? (int) $product['id'] : 0 ?>">

        <div class="form-row">
            <div class="form-group">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" maxlength="150" required value="<?= $value('name') ?>">
                <small class="field-error" id="name-error"></small>
            </div>

            <div class="form-group">
                <label for="productNumber">Offer number *</label>
                <input type="text" id="productNumber" name="productNumber" maxlength="20" required
                    pattern="[A-Za-z0-9-]{1,20}" value="<?= $value('product_number') ?>">
                <small class="field-error" id="productNumber-error"></small>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" maxlength="2000"><?= $value('description') ?></textarea>
            <small class="field-error" id="description-error"></small>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="price">Price, ₸ *</label>
                <input type="number" id="price" name="price" min="0" step="1" required value="<?= $value('price') ?>">
                <small class="field-error" id="price-error"></small>
            </div>

            <div class="form-group">
                <label for="oldPrice">Old price, ₸</label>
                <input type="number" id="oldPrice" name="oldPrice" min="0" step="1" value="<?= $value('old_price') ?>">
                <small class="field-error" id="oldPrice-error"></small>
            </div>

            <div class="form-group">
                <label for="stock">Coupons in stock *</label>
                <input type="number" id="stock" name="stock" min="0" step="1" required value="<?= $value('stock', '100') ?>">
                <small class="field-error" id="stock-error"></small>
            </div>

            <div class="form-group">
                <label for="maxPerCustomer">Limit per customer *</label>
                <input type="number" id="maxPerCustomer" name="maxPerCustomer" min="1" max="10" step="1" required
                    value="<?= $value('max_per_customer', '1') ?>">
                <small class="text-muted">2 for things people do together (cinema), 1 for personal ones (salon, gym)</small>
                <small class="field-error" id="maxPerCustomer-error"></small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="categoryId">Category *</label>
                <select id="categoryId" name="categoryId" required>
                    <option value="">Choose a category</option>
<?php foreach ($categories as $category): ?>
                    <option value="<?= $category['id'] ?>" <?= (int) ($product['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>>
                        <?= e($category['name']) ?>
                    </option>
<?php endforeach; ?>
                </select>
                <small class="field-error" id="categoryId-error"></small>
            </div>

            <div class="form-group">
                <label for="partnerId">Partner</label>
                <select id="partnerId" name="partnerId">
                    <option value="">No partner</option>
<?php foreach ($partners as $partner): ?>
                    <option value="<?= $partner['id'] ?>" <?= (int) ($product['partner_id'] ?? 0) === (int) $partner['id'] ? 'selected' : '' ?>>
                        <?= e($partner['name']) ?>
                    </option>
<?php endforeach; ?>
                </select>
                <small class="field-error" id="partnerId-error"></small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="validUntil">Can be bought until</label>
                <input type="date" id="validUntil" name="validUntil" value="<?= $value('valid_until') ?>">
                <small class="field-error" id="validUntil-error"></small>
            </div>

            <div class="form-group">
                <label for="useUntil">Use until</label>
                <input type="date" id="useUntil" name="useUntil" value="<?= $value('use_until') ?>">
                <small class="field-error" id="useUntil-error"></small>
            </div>

            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status">
<?php foreach (['Available', 'Unavailable'] as $status): ?>
                    <option value="<?= $status ?>" <?= ($product['status'] ?? 'Available') === $status ? 'selected' : '' ?>><?= $status ?></option>
<?php endforeach; ?>
                </select>
                <small class="field-error" id="status-error"></small>
            </div>
        </div>

        <div class="form-group">
            <label for="image">Image (JPG, PNG or WEBP, up to 2 MB)</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
            <small class="field-error" id="image-error"></small>

            <img id="image-preview" class="image-preview" alt="Image preview"
                src="<?= $value('image') ?>" <?= empty($product['image']) ? 'hidden' : '' ?>>

<?php if (!empty($product['image'])): ?>
            <label class="checkbox-label">
                <input type="checkbox" id="removeImage" name="removeImage" value="1">
                Remove current image
            </label>
<?php endif; ?>
        </div>

        <p id="form-message" class="form-status" role="status"></p>

        <div class="form-actions">
            <button type="submit" class="button" id="save-button">
                <?= $isEdit ? 'Save Changes' : 'Add Offer' ?>
            </button>
            <a href="admin-products.php" class="button button-secondary">Cancel</a>
        </div>
    </form>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
