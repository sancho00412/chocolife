<?php
require __DIR__ . '/includes/page.php';

$user = requirePageUser();

// Saved offers with their partner and sales, the last saved first
$stmt = getDb()->prepare("
    SELECT p.id, p.name, p.price, p.old_price, p.image, p.status, p.stock,
           c.name AS category, pr.name AS partner,
           (SELECT COALESCE(SUM(oi.quantity), 0)
              FROM order_items oi
              JOIN orders o ON o.id = oi.order_id
             WHERE oi.product_id = p.id AND o.status <> 'Declined') AS sold
    FROM favorites f
    JOIN products p       ON p.id = f.product_id
    JOIN categories c     ON c.id = p.category_id
    LEFT JOIN partners pr ON pr.id = p.partner_id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");
$stmt->execute([$user['id']]);
$favorites = $stmt->fetchAll();

$pageTitle   = 'Saved Offers';
$activePage  = 'favorites';
$pageCss     = ['user-home.css', 'offer.css'];
$pageScripts = ['favorites.js'];

require __DIR__ . '/includes/header.php';
?>

    <main class="offer-page">
        <a href="user-home.php#offers" class="offer-back-link">← Back to offers</a>

        <section class="similar">
            <div class="similar-heading">
                <h1>Saved Offers</h1>
                <span class="text-muted" id="favorites-count"><?= count($favorites) ?> saved</span>
            </div>

            <div class="review-notice" id="favorites-empty" <?= $favorites ? 'hidden' : '' ?>>
                <span class="material-symbols-outlined">favorite</span>
                <div>
                    <strong>No saved offers yet</strong>
                    <p>Press <b>Save</b> on any offer page, and it will appear here.
                        <a href="user-home.php#offers">Browse offers</a></p>
                </div>
            </div>

            <div class="similar-grid">
<?php foreach ($favorites as $item): ?>
<?php $discount = discountPercent((float) $item['price'], $item['old_price'] ? (float) $item['old_price'] : null); ?>
                <article class="similar-card favorite-card">
                    <a href="offer.php?id=<?= $item['id'] ?>">
                        <div class="similar-image category-<?= strtolower(e($item['category'])) ?>">
<?php if ($item['image']): ?>
                            <img src="<?= e($item['image']) ?>" alt="">
<?php else: ?>
                            <span class="material-symbols-outlined"><?= categoryIcon($item['category']) ?></span>
<?php endif; ?>
<?php if ($discount > 0): ?>
                            <span class="similar-discount">-<?= $discount ?>%</span>
<?php endif; ?>
                        </div>
                        <h3><?= e($item['name']) ?></h3>
                        <p><?= e($item['partner'] ?? $item['category']) ?> · <?= (int) $item['sold'] ?> bought</p>
                    </a>

                    <div class="favorite-card-bottom">
                        <span class="chip chip-price"><?= formatPrice((float) $item['price']) ?></span>
<?php if ($item['status'] !== 'Available' || (int) $item['stock'] === 0): ?>
                        <span class="offer-unavailable">Not available</span>
<?php endif; ?>
                        <button type="button" class="favorite-remove" data-product-id="<?= $item['id'] ?>"
                            data-name="<?= e($item['name']) ?>" aria-label="Remove <?= e($item['name']) ?> from saved">
                            <span class="material-symbols-outlined">heart_minus</span>
                        </button>
                    </div>
                </article>
<?php endforeach; ?>
            </div>
        </section>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
