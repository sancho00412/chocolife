<?php
require __DIR__ . '/includes/page.php';

$user = requirePageAdmin();
$db = getDb();

// Key numbers. Declined orders are not counted as sales
$stats = $db->query("
    SELECT
        (SELECT COALESCE(SUM(total), 0) FROM orders WHERE status = 'Completed') AS revenue,
        (SELECT COUNT(*) FROM orders)                                          AS orders_total,
        (SELECT COUNT(*) FROM orders WHERE status = 'Pending')                 AS orders_pending,
        (SELECT COUNT(*) FROM users WHERE role = 'user')                       AS customers,
        (SELECT COALESCE(AVG(total), 0) FROM orders WHERE status = 'Completed') AS avg_order,
        (SELECT COUNT(*) FROM products WHERE status = 'Available')             AS offers_active
")->fetch();

// Revenue of this month compared with the previous month (date-based query)
$months = $db->query("
    SELECT
        COALESCE(SUM(CASE WHEN created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN total END), 0) AS this_month,
        COALESCE(SUM(CASE WHEN created_at >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01')
                           AND created_at <  DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN total END), 0) AS last_month
    FROM orders
    WHERE status <> 'Declined'
")->fetch();

$recentOrders = $db->query('
    SELECT id, order_number, customer_name, total, status, created_at
    FROM orders
    ORDER BY created_at DESC
    LIMIT 5
')->fetchAll();

// Offers that need attention: few coupons left or the offer ends soon
$attention = $db->query("
    SELECT id, name, stock, valid_until
    FROM products
    WHERE status = 'Available'
      AND (stock < 5 OR valid_until BETWEEN CURDATE() AND CURDATE() + INTERVAL 30 DAY)
    ORDER BY stock, valid_until
")->fetchAll();

$pageTitle   = 'Dashboard';
$activePage  = 'dashboard';
$pageScripts = [];

require __DIR__ . '/includes/admin-header.php';
?>

<section class="stat-grid" aria-label="Key numbers">
    <article class="stat-card">
        <span class="material-symbols-outlined">payments</span>
        <h2>Revenue</h2>
        <p class="stat-value"><?= formatPrice((float) $stats['revenue']) ?></p>
        <small>Completed orders</small>
    </article>

    <article class="stat-card">
        <span class="material-symbols-outlined">calendar_month</span>
        <h2>This month</h2>
        <p class="stat-value"><?= formatPrice((float) $months['this_month']) ?></p>
        <small>Last month: <?= formatPrice((float) $months['last_month']) ?></small>
    </article>

    <article class="stat-card">
        <span class="material-symbols-outlined">receipt_long</span>
        <h2>Orders</h2>
        <p class="stat-value"><?= (int) $stats['orders_total'] ?></p>
        <small><a href="admin-orders.php?status=Pending"><?= (int) $stats['orders_pending'] ?> pending</a></small>
    </article>

    <article class="stat-card">
        <span class="material-symbols-outlined">shopping_bag</span>
        <h2>Average order</h2>
        <p class="stat-value"><?= formatPrice(round((float) $stats['avg_order'])) ?></p>
        <small>Completed orders</small>
    </article>

    <article class="stat-card">
        <span class="material-symbols-outlined">groups</span>
        <h2>Customers</h2>
        <p class="stat-value"><?= (int) $stats['customers'] ?></p>
        <small><a href="admin-users.php">View users</a></small>
    </article>

    <article class="stat-card">
        <span class="material-symbols-outlined">local_offer</span>
        <h2>Active offers</h2>
        <p class="stat-value"><?= (int) $stats['offers_active'] ?></p>
        <small><a href="admin-products.php">Manage offers</a></small>
    </article>
</section>

<div class="admin-columns">
    <section class="admin-card">
        <div class="admin-card-heading">
            <h2>Recent Orders</h2>
            <a href="admin-orders.php">Show all</a>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
<?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td><?= e($order['order_number']) ?></td>
                        <td><?= e($order['customer_name']) ?></td>
                        <td><?= formatDate($order['created_at'], 'd.m.Y H:i') ?></td>
                        <td><?= formatPrice((float) $order['total']) ?></td>
                        <td><span class="badge badge-<?= strtolower($order['status']) ?>"><?= e($order['status']) ?></span></td>
                    </tr>
<?php endforeach; ?>
<?php if (!$recentOrders): ?>
                    <tr><td colspan="5">No orders yet.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card-heading">
            <h2>Needs Attention</h2>
            <a href="admin-reports.php">Reports</a>
        </div>

<?php if (!$attention): ?>
        <p class="text-muted">All offers have enough coupons and are valid for more than 30 days.</p>
<?php else: ?>
        <ul class="attention-list">
<?php foreach ($attention as $product): ?>
            <li>
                <a href="admin-product-form.php?id=<?= $product['id'] ?>"><?= e($product['name']) ?></a>
                <span>
<?php if ((int) $product['stock'] < 5): ?>
                    <span class="badge badge-declined"><?= (int) $product['stock'] ?> left</span>
<?php endif; ?>
<?php if ($product['valid_until'] && strtotime($product['valid_until']) <= strtotime('+30 days')): ?>
                    <span class="badge badge-pending">ends <?= formatDate($product['valid_until']) ?></span>
<?php endif; ?>
                </span>
            </li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
