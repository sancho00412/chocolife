<?php
require __DIR__ . '/includes/page.php';

$user = requirePageAdmin();

$categories = getDb()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

// Message after saving an offer on the form page
$saved = $_GET['saved'] ?? '';

$pageTitle   = 'Offers';
$activePage  = 'products';
$pageScripts = ['admin-products.js'];

require __DIR__ . '/includes/admin-header.php';
?>

<?php if ($saved === 'created' || $saved === 'updated'): ?>
<p class="alert alert-success" role="status">The offer was <?= $saved ?> successfully.</p>
<?php endif; ?>

<section class="admin-card">
    <div class="admin-card-heading">
        <h2>All Offers</h2>
        <a href="admin-product-form.php" class="button">+ Add Offer</a>
    </div>

    <form id="products-filter" class="filter-bar" role="search">
        <label>
            <span>Search</span>
            <input type="search" id="products-search" placeholder="Name or description">
        </label>

        <label>
            <span>Category</span>
            <select id="products-category">
                <option value="">All categories</option>
<?php foreach ($categories as $category): ?>
                <option value="<?= $category['id'] ?>"><?= e($category['name']) ?></option>
<?php endforeach; ?>
            </select>
        </label>

        <label>
            <span>Status</span>
            <select id="products-status">
                <option value="">All</option>
                <option value="Available">Available</option>
                <option value="Unavailable">Unavailable</option>
            </select>
        </label>

        <label>
            <span>Sort</span>
            <select id="products-sort">
                <option value="newest">Newest first</option>
                <option value="name">Name A-Z</option>
                <option value="price_asc">Price: low to high</option>
                <option value="price_desc">Price: high to low</option>
                <option value="stock">Stock: lowest first</option>
                <option value="popular">Most sold</option>
                <option value="rating">Best rated</option>
            </select>
        </label>
    </form>

    <p id="products-summary" class="text-muted" role="status">Loading offers...</p>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Offer</th>
                    <th>Number</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Sold</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="products-body"></tbody>
        </table>
    </div>

    <nav id="products-pagination" class="pagination" aria-label="Offers pages"></nav>
</section>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
