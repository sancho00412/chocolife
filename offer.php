<?php
require __DIR__ . '/includes/page.php';

$user = requirePageLogin();

// offer.php?id=5 -> product with id 5
$productId = (int) ($_GET['id'] ?? 0);

$stmt = getDb()->prepare('
    SELECT p.id, p.product_number, p.name, p.description, p.price, p.old_price,
           p.stock, p.status, p.image, p.valid_until,
           c.name AS category,
           pr.name AS partner, pr.city AS partner_city, pr.address AS partner_address,
           EXISTS (
               SELECT 1 FROM favorites f WHERE f.product_id = p.id AND f.user_id = ?
           ) AS is_favorite
    FROM products p
    JOIN categories c ON c.id = p.category_id
    LEFT JOIN partners pr ON pr.id = p.partner_id
    WHERE p.id = ?
');
$stmt->execute([$user['id'], $productId]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
}

$canBuy = $product
    && $user['role'] === 'user'
    && $product['status'] === 'Available'
    && (int) $product['stock'] > 0;

$pageTitle   = $product ? $product['name'] : 'Offer not found';
$activePage  = '';
$pageCss     = ['user-home.css', 'offer.css'];
$pageScripts = ['offer.js'];

require __DIR__ . '/includes/header.php';
?>

    <main class="offer-page">
        <a href="user-home.php#offers" class="offer-back-link">← Back to offers</a>

<?php if (!$product): ?>
        <section class="offer-card">
            <h1>Offer not found</h1>
            <p>This offer does not exist or was removed.</p>
        </section>
<?php else: ?>
        <article class="offer-card" id="offer" data-product-id="<?= $product['id'] ?>">
            <div class="offer-image">
<?php if ($product['image']): ?>
                <img src="<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>">
<?php else: ?>
                <span class="material-symbols-outlined">local_offer</span>
<?php endif; ?>
            </div>

            <div class="offer-info">
                <p class="eyebrow"><?= e($product['category']) ?></p>
                <h1><?= e($product['name']) ?></h1>

                <p class="offer-price">
                    <?= formatPrice((float) $product['price']) ?>
<?php if ($product['old_price']): ?>
                    <s><?= formatPrice((float) $product['old_price']) ?></s>
                    <span class="offer-discount">
                        -<?= round((1 - $product['price'] / $product['old_price']) * 100) ?>%
                    </span>
<?php endif; ?>
                </p>

                <p><?= e($product['description']) ?></p>

                <dl class="offer-facts">
                    <dt>Partner</dt>
                    <dd>
                        <?= e($product['partner'] ?? '—') ?>
                        <?= $product['partner'] ? e(', ' . $product['partner_city'] . ', ' . $product['partner_address']) : '' ?>
                    </dd>

                    <dt>Valid until</dt>
                    <dd><?= formatDate($product['valid_until']) ?></dd>

                    <dt>Offer number</dt>
                    <dd><?= e($product['product_number']) ?></dd>

                    <dt>Status</dt>
                    <dd class="<?= $product['status'] === 'Available' ? 'offer-available' : 'offer-unavailable' ?>">
                        <?= e($product['status']) ?>
<?php if ($product['status'] === 'Available'): ?>
                        · <?= (int) $product['stock'] > 0 ? (int) $product['stock'] . ' left' : 'sold out' ?>
<?php endif; ?>
                    </dd>
                </dl>

<?php if ($user['role'] === 'user'): ?>
                <div class="offer-actions">
<?php if ($canBuy): ?>
                    <label for="offer-quantity" class="visually-hidden">Quantity</label>
                    <input type="number" id="offer-quantity" min="1"
                        max="<?= min(99, (int) $product['stock']) ?>" value="1">

                    <button id="add-to-cart" type="button">Add to Cart</button>
<?php endif; ?>

                    <button
                        id="favorite-button"
                        type="button"
                        class="favorite-button <?= $product['is_favorite'] ? 'active' : '' ?>"
                        aria-pressed="<?= $product['is_favorite'] ? 'true' : 'false' ?>"
                    >
                        <span class="material-symbols-outlined">favorite</span>
                        <span class="favorite-label"><?= $product['is_favorite'] ? 'Saved' : 'Save' ?></span>
                    </button>
                </div>
<?php endif; ?>
                <p id="cart-message" class="offer-message" role="status"></p>
            </div>
        </article>

        <section class="offer-card offer-reviews">
            <h2>Reviews <span id="reviews-summary"></span></h2>

            <form id="review-form" class="review-form" hidden novalidate>
                <h3 id="review-form-title">Leave a review</h3>

                <label for="review-rating">Rating</label>
                <select id="review-rating" required>
                    <option value="">Choose a rating</option>
                    <option value="5">★★★★★ Excellent</option>
                    <option value="4">★★★★☆ Good</option>
                    <option value="3">★★★☆☆ Average</option>
                    <option value="2">★★☆☆☆ Poor</option>
                    <option value="1">★☆☆☆☆ Terrible</option>
                </select>

                <label for="review-comment">Comment (optional)</label>
                <textarea id="review-comment" rows="4" maxlength="1000"
                    placeholder="What did you like or dislike?"></textarea>

                <p id="review-message" class="offer-message" role="status"></p>

                <button type="submit">Save review</button>
            </form>

            <p id="review-hint" class="review-hint" hidden>
                Only customers who bought this offer can leave a review.
            </p>

            <p id="reviews-status" class="review-hint" role="status">Loading reviews...</p>
            <div id="reviews-list"></div>
        </section>
<?php endif; ?>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
