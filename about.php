<?php
require __DIR__ . '/includes/page.php';

$user = requirePageLogin();
$db = getDb();

// Real numbers from the database
$stats = $db->query("
    SELECT
        (SELECT COUNT(*) FROM users WHERE role = 'user')            AS customers,
        (SELECT COUNT(*) FROM partners)                             AS partners,
        (SELECT COUNT(*) FROM products WHERE status = 'Available')  AS offers,
        (SELECT COALESCE(SUM(oi.quantity), 0)
           FROM order_items oi
           JOIN orders o ON o.id = oi.order_id
          WHERE o.status <> 'Declined')                             AS coupons,
        (SELECT ROUND(AVG(rating), 1) FROM reviews)                 AS rating,
        (SELECT COALESCE(SUM(oi.quantity * (p.old_price - oi.price)), 0)
           FROM order_items oi
           JOIN orders o   ON o.id = oi.order_id
           JOIN products p ON p.id = oi.product_id
          WHERE o.status <> 'Declined' AND p.old_price IS NOT NULL) AS saved
")->fetch();

// Categories with the number of available offers in each
$categories = $db->query("
    SELECT c.name, COUNT(p.id) AS offers
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id AND p.status = 'Available'
    GROUP BY c.id, c.name
    ORDER BY c.name
")->fetchAll();

$pageTitle   = 'About Us';
$activePage  = 'about';
$pageCss     = ['user-home.css', 'help.css'];
$pageScripts = [];

require __DIR__ . '/includes/header.php';
?>

    <main class="help-page">
        <section class="help-intro">
            <p class="eyebrow">ABOUT US</p>
            <h1>Chocolife — discounts on things you love</h1>
            <p>
                Chocolife is a coupon service in Almaty. We work with restaurants, beauty salons,
                fitness clubs, entertainment centers and travel companies, so you can try new places
                and experiences with a discount of up to 50%.
            </p>
        </section>

        <section class="business-stats about-stats">
            <div>
                <strong><?= (int) $stats['customers'] ?></strong>
                <span>customers</span>
            </div>
            <div>
                <strong><?= (int) $stats['partners'] ?></strong>
                <span>partners</span>
            </div>
            <div>
                <strong><?= (int) $stats['offers'] ?></strong>
                <span>active offers</span>
            </div>
            <div>
                <strong><?= (int) $stats['coupons'] ?></strong>
                <span>coupons sold</span>
            </div>
            <div>
                <strong>★ <?= e($stats['rating'] ?? '—') ?></strong>
                <span>average rating</span>
            </div>
            <div>
                <strong><?= formatPrice((float) $stats['saved']) ?></strong>
                <span>saved by customers</span>
            </div>
        </section>

        <section class="help-card">
            <h2>Our mission</h2>
            <p class="about-text">
                We believe a good evening, a relaxing massage or a weekend trip should not cost a fortune.
                Partners get new customers, and customers get honest discounts, real reviews and
                a simple way to buy.
            </p>
        </section>

        <section class="help-card business-steps">
            <h2>How it works</h2>
            <ol>
                <li><b>Choose an offer</b> in the catalog: use search, categories and sorting.</li>
                <li><b>Buy a coupon</b> online. The price is fixed, the discount is already included.</li>
                <li><b>Use the coupon</b> at the partner before the "Use until" date.</li>
                <li><b>Leave a review</b> to help other customers choose.</li>
            </ol>
        </section>

        <section class="help-card">
            <h2>What you can find</h2>
            <ul class="about-categories">
<?php foreach ($categories as $category): ?>
                <li>
                    <span class="material-symbols-outlined"><?= categoryIcon($category['name']) ?></span>
                    <b><?= e($category['name']) ?></b>
                    <span><?= (int) $category['offers'] ?> offer(s)</span>
                </li>
<?php endforeach; ?>
            </ul>
        </section>

        <section class="help-card">
            <h2>Contact us</h2>
            <p class="about-text">
                Phone: <b><?= SUPPORT_PHONE ?></b><br>
                Questions and comments: <a href="feedback.php">Feedback form</a><br>
                Want to become a partner? <a href="business.php">For business</a>
            </p>
        </section>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
