<?php
require __DIR__ . '/includes/page.php';

$user = requirePageAdmin();
$db = getDb();

// Report period from the form, by default the last 12 months
function readDate(string $key, string $default): string
{
    $value = is_string($_GET[$key] ?? null) ? $_GET[$key] : '';
    $date  = DateTime::createFromFormat('Y-m-d', $value);

    return $date && $date->format('Y-m-d') === $value ? $value : $default;
}

$from = readDate('from', date('Y-m-01', strtotime('-11 months')));
$to   = readDate('to', date('Y-m-d'));
$periodError = '';

if ($from > $to) {
    $periodError = '"From" must be before "To". The dates were swapped.';
    [$from, $to] = [$to, $from];
}

function runReport(PDO $db, string $sql, array $params = []): array
{
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

$period = [$from, $to];

// 1. Summary of the period: aggregate functions
$summarySql = "
SELECT COUNT(*)              AS orders,
       COALESCE(SUM(total), 0) AS revenue,
       COALESCE(AVG(total), 0) AS average,
       COALESCE(MAX(total), 0) AS biggest
FROM orders
WHERE status <> 'Declined'
  AND created_at >= ? AND created_at < ? + INTERVAL 1 DAY";
$summary = runReport($db, $summarySql, $period)[0];

// 2. Revenue by month: date-based statistics with GROUP BY
$monthlySql = "
SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
       COUNT(*)   AS orders,
       SUM(total) AS revenue
FROM orders
WHERE status <> 'Declined'
  AND created_at >= ? AND created_at < ? + INTERVAL 1 DAY
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY month";
$monthly = runReport($db, $monthlySql, $period);

// 3. Revenue by category: 4 tables + SUM + COUNT(DISTINCT)
$categorySql = "
SELECT COALESCE(c.name, 'Deleted offers') AS category,
       COUNT(DISTINCT o.id)               AS orders,
       SUM(oi.quantity)                   AS coupons,
       SUM(oi.price * oi.quantity)        AS revenue
FROM orders o
JOIN order_items oi    ON oi.order_id = o.id
LEFT JOIN products p   ON p.id = oi.product_id
LEFT JOIN categories c ON c.id = p.category_id
WHERE o.status = 'Completed'
  AND o.created_at >= ? AND o.created_at < ? + INTERVAL 1 DAY
GROUP BY c.name
ORDER BY revenue DESC";
$byCategory = runReport($db, $categorySql, $period);

// 4. Top 5 offers by sold coupons: JOIN + SUM + ORDER BY + LIMIT
$topSql = "
SELECT p.name, c.name AS category,
       SUM(oi.quantity)            AS sold,
       SUM(oi.price * oi.quantity) AS revenue
FROM order_items oi
JOIN orders o     ON o.id = oi.order_id
JOIN products p   ON p.id = oi.product_id
JOIN categories c ON c.id = p.category_id
WHERE o.status <> 'Declined'
  AND o.created_at >= ? AND o.created_at < ? + INTERVAL 1 DAY
GROUP BY p.id, p.name, c.name
ORDER BY sold DESC, revenue DESC
LIMIT 5";
$topOffers = runReport($db, $topSql, $period);

// 5. Loyal customers: GROUP BY + HAVING
$loyalSql = "
SELECT CONCAT(u.first_name, ' ', u.last_name) AS customer,
       u.email,
       COUNT(o.id)       AS orders,
       SUM(o.total)      AS spent,
       MAX(o.created_at) AS last_order
FROM users u
JOIN orders o ON o.user_id = u.id
WHERE o.status <> 'Declined'
GROUP BY u.id, u.first_name, u.last_name, u.email
HAVING COUNT(o.id) >= 2
ORDER BY spent DESC";
$loyal = runReport($db, $loyalSql);

// 6. Order statuses in the period: COUNT and SUM per status
$statusSql = "
SELECT status, COUNT(*) AS orders, SUM(total) AS amount
FROM orders
WHERE created_at >= ? AND created_at < ? + INTERVAL 1 DAY
GROUP BY status
ORDER BY orders DESC";
$statuses = runReport($db, $statusSql, $period);

// 7. Partner ratings: LEFT JOIN keeps partners without reviews
$partnerSql = "
SELECT pr.name AS partner,
       COUNT(DISTINCT p.id) AS offers,
       COUNT(r.id)          AS reviews,
       ROUND(AVG(r.rating), 1) AS rating
FROM partners pr
LEFT JOIN products p ON p.partner_id = pr.id
LEFT JOIN reviews r  ON r.product_id = p.id
GROUP BY pr.id, pr.name
ORDER BY rating IS NULL, rating DESC, reviews DESC";
$partners = runReport($db, $partnerSql);

// 8. Offers that were never bought: subquery with NOT EXISTS
$neverSql = "
SELECT p.name, c.name AS category, p.price, p.stock
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE NOT EXISTS (
    SELECT 1
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE oi.product_id = p.id AND o.status <> 'Declined'
)
ORDER BY p.name";
$neverBought = runReport($db, $neverSql);

// 9. Offers more expensive than the average of their category: correlated subquery
$aboveSql = "
SELECT p.name, c.name AS category, p.price,
       ROUND((SELECT AVG(p2.price) FROM products p2 WHERE p2.category_id = p.category_id)) AS category_avg
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE p.price > (SELECT AVG(p2.price) FROM products p2 WHERE p2.category_id = p.category_id)
ORDER BY c.name, p.price DESC";
$aboveAverage = runReport($db, $aboveSql);

// 10. Offers that end in the next 60 days: date filter with several conditions
$expiringSql = "
SELECT name, valid_until, stock, DATEDIFF(valid_until, CURDATE()) AS days_left
FROM products
WHERE status = 'Available'
  AND valid_until IS NOT NULL
  AND valid_until BETWEEN CURDATE() AND CURDATE() + INTERVAL 60 DAY
ORDER BY valid_until";
$expiring = runReport($db, $expiringSql);

$maxMonthly = max(array_merge([1], array_map(fn($row) => (float) $row['revenue'], $monthly)));
$maxCategory = max(array_merge([1], array_map(fn($row) => (float) $row['revenue'], $byCategory)));

// Shows the SQL of a report under its table
function sqlDetails(string $sql): string
{
    return '<details class="sql-details"><summary>Show SQL</summary><pre>' . e(trim($sql)) . '</pre></details>';
}

$pageTitle   = 'Reports';
$activePage  = 'reports';
$pageScripts = [];

require __DIR__ . '/includes/admin-header.php';
?>

<section class="admin-card">
    <form class="filter-bar" method="get">
        <label>
            <span>From</span>
            <input type="date" name="from" value="<?= e($from) ?>">
        </label>
        <label>
            <span>To</span>
            <input type="date" name="to" value="<?= e($to) ?>">
        </label>
        <button type="submit" class="button">Show</button>
    </form>
<?php if ($periodError): ?>
    <p class="alert alert-error"><?= e($periodError) ?></p>
<?php endif; ?>
    <p class="text-muted">
        Period: <?= formatDate($from) ?> – <?= formatDate($to) ?>.
        Reports 5, 7, 8, 9 and 10 do not depend on the period.
    </p>
</section>

<section class="stat-grid" aria-label="Period summary">
    <article class="stat-card">
        <h2>Orders</h2>
        <p class="stat-value"><?= (int) $summary['orders'] ?></p>
        <small>Not declined</small>
    </article>
    <article class="stat-card">
        <h2>Revenue</h2>
        <p class="stat-value"><?= formatPrice((float) $summary['revenue']) ?></p>
        <small>SUM(total)</small>
    </article>
    <article class="stat-card">
        <h2>Average order</h2>
        <p class="stat-value"><?= formatPrice(round((float) $summary['average'])) ?></p>
        <small>AVG(total)</small>
    </article>
    <article class="stat-card">
        <h2>Biggest order</h2>
        <p class="stat-value"><?= formatPrice((float) $summary['biggest']) ?></p>
        <small>MAX(total)</small>
    </article>
</section>
<?= sqlDetails($summarySql) ?>

<div class="admin-columns">
    <section class="admin-card">
        <h2>1. Revenue by Month</h2>
<?php if (!$monthly): ?>
        <p class="text-muted">No orders in this period.</p>
<?php else: ?>
        <ul class="bar-chart">
<?php foreach ($monthly as $row): ?>
            <li>
                <span class="bar-label"><?= date('M Y', strtotime($row['month'] . '-01')) ?></span>
                <span class="bar-track">
                    <span class="bar" style="width: <?= round((float) $row['revenue'] / $maxMonthly * 100) ?>%"></span>
                </span>
                <span class="bar-value"><?= formatPrice((float) $row['revenue']) ?> · <?= (int) $row['orders'] ?></span>
            </li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
        <?= sqlDetails($monthlySql) ?>
    </section>

    <section class="admin-card">
        <h2>2. Revenue by Category</h2>
<?php if (!$byCategory): ?>
        <p class="text-muted">No completed orders in this period.</p>
<?php else: ?>
        <ul class="bar-chart">
<?php foreach ($byCategory as $row): ?>
            <li>
                <span class="bar-label"><?= e($row['category']) ?></span>
                <span class="bar-track">
                    <span class="bar" style="width: <?= round((float) $row['revenue'] / $maxCategory * 100) ?>%"></span>
                </span>
                <span class="bar-value"><?= formatPrice((float) $row['revenue']) ?> · <?= (int) $row['coupons'] ?> pcs</span>
            </li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
        <?= sqlDetails($categorySql) ?>
    </section>
</div>

<div class="admin-columns">
    <section class="admin-card">
        <h2>3. Top 5 Offers</h2>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Offer</th><th>Category</th><th>Sold</th><th>Revenue</th></tr></thead>
                <tbody>
<?php foreach ($topOffers as $row): ?>
                    <tr>
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['category']) ?></td>
                        <td><?= (int) $row['sold'] ?></td>
                        <td><?= formatPrice((float) $row['revenue']) ?></td>
                    </tr>
<?php endforeach; ?>
<?php if (!$topOffers): ?>
                    <tr><td colspan="4">No sales in this period.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= sqlDetails($topSql) ?>
    </section>

    <section class="admin-card">
        <h2>4. Orders by Status</h2>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Status</th><th>Orders</th><th>Amount</th></tr></thead>
                <tbody>
<?php foreach ($statuses as $row): ?>
                    <tr>
                        <td><span class="badge badge-<?= strtolower($row['status']) ?>"><?= e($row['status']) ?></span></td>
                        <td><?= (int) $row['orders'] ?></td>
                        <td><?= formatPrice((float) $row['amount']) ?></td>
                    </tr>
<?php endforeach; ?>
<?php if (!$statuses): ?>
                    <tr><td colspan="3">No orders in this period.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= sqlDetails($statusSql) ?>
    </section>
</div>

<section class="admin-card">
    <h2>5. Loyal Customers (2+ orders)</h2>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Customer</th><th>Email</th><th>Orders</th><th>Spent</th><th>Last order</th></tr></thead>
            <tbody>
<?php foreach ($loyal as $row): ?>
                <tr>
                    <td><?= e($row['customer']) ?></td>
                    <td><?= e($row['email']) ?></td>
                    <td><?= (int) $row['orders'] ?></td>
                    <td><?= formatPrice((float) $row['spent']) ?></td>
                    <td><?= formatDate($row['last_order']) ?></td>
                </tr>
<?php endforeach; ?>
<?php if (!$loyal): ?>
                <tr><td colspan="5">No customers with two or more orders yet.</td></tr>
<?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= sqlDetails($loyalSql) ?>
</section>

<div class="admin-columns">
    <section class="admin-card">
        <h2>6. Partner Ratings</h2>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Partner</th><th>Offers</th><th>Reviews</th><th>Rating</th></tr></thead>
                <tbody>
<?php foreach ($partners as $row): ?>
                    <tr>
                        <td><?= e($row['partner']) ?></td>
                        <td><?= (int) $row['offers'] ?></td>
                        <td><?= (int) $row['reviews'] ?></td>
                        <td><?= $row['rating'] !== null ? '★ ' . e($row['rating']) : '—' ?></td>
                    </tr>
<?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= sqlDetails($partnerSql) ?>
    </section>

    <section class="admin-card">
        <h2>7. Never Bought Offers</h2>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Offer</th><th>Category</th><th>Price</th><th>Stock</th></tr></thead>
                <tbody>
<?php foreach ($neverBought as $row): ?>
                    <tr>
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['category']) ?></td>
                        <td><?= formatPrice((float) $row['price']) ?></td>
                        <td><?= (int) $row['stock'] ?></td>
                    </tr>
<?php endforeach; ?>
<?php if (!$neverBought): ?>
                    <tr><td colspan="4">Every offer was bought at least once.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= sqlDetails($neverSql) ?>
    </section>
</div>

<div class="admin-columns">
    <section class="admin-card">
        <h2>8. Above Category Average Price</h2>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Offer</th><th>Category</th><th>Price</th><th>Category avg</th></tr></thead>
                <tbody>
<?php foreach ($aboveAverage as $row): ?>
                    <tr>
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['category']) ?></td>
                        <td><?= formatPrice((float) $row['price']) ?></td>
                        <td><?= formatPrice((float) $row['category_avg']) ?></td>
                    </tr>
<?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= sqlDetails($aboveSql) ?>
    </section>

    <section class="admin-card">
        <h2>9. Ending in 60 Days</h2>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Offer</th><th>Valid until</th><th>Days left</th><th>Stock</th></tr></thead>
                <tbody>
<?php foreach ($expiring as $row): ?>
                    <tr>
                        <td><?= e($row['name']) ?></td>
                        <td><?= formatDate($row['valid_until']) ?></td>
                        <td><?= (int) $row['days_left'] ?></td>
                        <td><?= (int) $row['stock'] ?></td>
                    </tr>
<?php endforeach; ?>
<?php if (!$expiring): ?>
                    <tr><td colspan="4">No offers end in the next 60 days.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= sqlDetails($expiringSql) ?>
    </section>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
