<?php
require __DIR__ . '/includes/page.php';

$user = requirePageAdmin();

// The dashboard can open this page with a filter, e.g. admin-orders.php?status=Pending
$status = in_array($_GET['status'] ?? '', ['Pending', 'Completed', 'Declined'], true) ? $_GET['status'] : '';

$pageTitle   = 'Orders';
$activePage  = 'orders';
$pageScripts = ['admin-orders.js'];

require __DIR__ . '/includes/admin-header.php';
?>

<section class="admin-card">
    <form id="orders-filter" class="filter-bar" role="search">
        <label>
            <span>Search</span>
            <input type="search" id="orders-search" placeholder="Order number, customer or email">
        </label>

        <label>
            <span>Status</span>
            <select id="orders-status">
                <option value="">All statuses</option>
<?php foreach (['Pending', 'Completed', 'Declined'] as $option): ?>
                <option value="<?= $option ?>" <?= $status === $option ? 'selected' : '' ?>><?= $option ?></option>
<?php endforeach; ?>
            </select>
        </label>

        <label>
            <span>From</span>
            <input type="date" id="orders-from">
        </label>

        <label>
            <span>To</span>
            <input type="date" id="orders-to">
        </label>

        <label>
            <span>Sort</span>
            <select id="orders-sort">
                <option value="newest">Newest first</option>
                <option value="oldest">Oldest first</option>
                <option value="total_desc">Total: high to low</option>
                <option value="total_asc">Total: low to high</option>
            </select>
        </label>
    </form>

    <p id="orders-summary" class="text-muted" role="status">Loading orders...</p>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="orders-body"></tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
