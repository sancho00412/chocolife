<?php
require __DIR__ . '/includes/page.php';
require __DIR__ . '/includes/shop.php';
require __DIR__ . '/config/maps.php';

$user = requirePageLogin();
$db = getDb();

// offer.php?id=5 -> product with id 5
$productId = (int) ($_GET['id'] ?? 0);

// Offer with its partner, sales and rating
$stmt = $db->prepare("
    SELECT p.id, p.product_number, p.name, p.description, p.price, p.old_price,
           p.stock, p.max_per_customer, p.status, p.image, p.image_credit, p.image_source,
           p.valid_until, p.use_until,
           p.category_id, c.name AS category,
           pr.name AS partner, pr.city AS partner_city, pr.address AS partner_address,
           pr.latitude, pr.longitude,
           (SELECT COALESCE(SUM(oi.quantity), 0)
              FROM order_items oi
              JOIN orders o ON o.id = oi.order_id
             WHERE oi.product_id = p.id AND o.status <> 'Declined') AS sold,
           (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id) AS review_count,
           (SELECT ROUND(AVG(r.rating), 1) FROM reviews r WHERE r.product_id = p.id) AS rating,
           EXISTS (
               SELECT 1 FROM favorites f WHERE f.product_id = p.id AND f.user_id = ?
           ) AS is_favorite
    FROM products p
    JOIN categories c ON c.id = p.category_id
    LEFT JOIN partners pr ON pr.id = p.partner_id
    WHERE p.id = ?
");
$stmt->execute([$user['id'], $productId]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
}

// How many coupons this customer can still buy (stock, limit per customer, earlier orders)
$maxQuantity = 0;
$limitNote = '';

if ($product && $user['role'] === 'user' && $product['status'] === 'Available') {
    $bought      = boughtQuantity($db, $user['id'], (int) $product['id']);
    $maxQuantity = maxQuantity((int) $product['stock'], (int) $product['max_per_customer'], $bought);

    if ($maxQuantity === 0) {
        $limitNote = limitMessage($product['name'], (int) $product['stock'], (int) $product['max_per_customer'], $bought);
    }
}

$canBuy = $maxQuantity > 0;

// Similar offers: the same category, the most popular first (two rows of four)
$similar = [];

if ($product) {
    $stmt = $db->prepare("
        SELECT p.id, p.name, p.price, p.old_price, p.image, c.name AS category, pr.name AS partner,
               COALESCE(SUM(CASE WHEN o.status <> 'Declined' THEN oi.quantity END), 0) AS sold
        FROM products p
        JOIN categories c      ON c.id = p.category_id
        LEFT JOIN partners pr  ON pr.id = p.partner_id
        LEFT JOIN order_items oi ON oi.product_id = p.id
        LEFT JOIN orders o     ON o.id = oi.order_id
        WHERE p.category_id = ? AND p.id <> ? AND p.status = 'Available'
        GROUP BY p.id, p.name, p.price, p.old_price, p.image, c.name, pr.name
        ORDER BY sold DESC
        LIMIT 8
    ");
    $stmt->execute([$product['category_id'], $product['id']]);
    $similar = $stmt->fetchAll();
}

// 2GIS: a search link always, a map only when there is a key and coordinates
$twoGisLink = $product && $product['partner']
    ? 'https://2gis.kz/search/' . rawurlencode("{$product['partner']}, {$product['partner_address']}, {$product['partner_city']}")
    : null;
$showMap = $twoGisLink && TWOGIS_KEY !== '' && $product['latitude'] !== null;

$pageTitle   = $product ? $product['name'] : 'Offer not found';
$activePage  = '';
$pageCss     = ['user-home.css', 'offer.css'];
$pageScripts = $showMap
    ? ['https://mapgl.2gis.com/api/js/v1', 'offer.js']
    : ['offer.js'];

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
<?php $discount = discountPercent((float) $product['price'], $product['old_price'] ? (float) $product['old_price'] : null); ?>
        <article class="offer-card deal" id="offer" data-product-id="<?= $product['id'] ?>">
            <figure class="deal-photo">
                <div class="deal-image category-<?= strtolower(e($product['category'])) ?>">
<?php if ($product['image']): ?>
                    <img src="<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>">
<?php else: ?>
                    <span class="material-symbols-outlined"><?= categoryIcon($product['category']) ?></span>
<?php endif; ?>
                </div>
<?php if ($product['image_credit']): ?>
                <figcaption class="photo-credit">
<?php if ($product['image_source']): ?>
                    <a href="<?= e($product['image_source']) ?>" target="_blank" rel="noopener"><?= e($product['image_credit']) ?></a>
<?php else: ?>
                    <?= e($product['image_credit']) ?>
<?php endif; ?>
                </figcaption>
<?php endif; ?>
            </figure>

            <div class="deal-info">
                <h1><?= e($product['name']) ?></h1>
                <p class="deal-partner"><?= e($product['partner'] ?? $product['category']) ?></p>

                <div class="deal-chips">
<?php if ($discount > 0): ?>
                    <span class="chip chip-discount">-<?= $discount ?>%</span>
<?php endif; ?>
                    <span class="chip chip-price">
                        <?= formatPrice((float) $product['price']) ?>
<?php if ($product['old_price']): ?>
                        <s><?= formatPrice((float) $product['old_price']) ?></s>
<?php endif; ?>
                    </span>
                </div>

<?php if ($product['partner']): ?>
                <a href="<?= e($twoGisLink) ?>" class="chip chip-line" target="_blank" rel="noopener"
                    title="Open in 2GIS">
                    <span class="material-symbols-outlined">location_on</span>
                    <?= e($product['partner_city'] . ', ' . $product['partner_address']) ?>
                    <span class="chip-note">· 2GIS</span>
                </a>
<?php endif; ?>

<?php if ($product['valid_until']): ?>
                <span class="chip chip-line">
                    <span class="material-symbols-outlined">schedule</span>
                    Can be bought until <?= formatDate($product['valid_until'], 'j F Y') ?>
                </span>
<?php endif; ?>

<?php if ($product['use_until']): ?>
                <span class="chip chip-line">
                    <span class="material-symbols-outlined">timelapse</span>
                    Use until <?= formatDate($product['use_until'], 'j F Y') ?> (inclusive)
                </span>
<?php endif; ?>

<?php if ($user['role'] === 'user'): ?>
                <div class="offer-actions">
<?php if ($canBuy): ?>
                    <label for="offer-quantity" class="visually-hidden">Quantity</label>
                    <input type="number" id="offer-quantity" min="1" max="<?= $maxQuantity ?>" value="1">

                    <button id="add-to-cart" type="button">Add to Cart</button>
<?php elseif ($limitNote): ?>
                    <p class="offer-limit-note"><?= e($limitNote) ?></p>
<?php else: ?>
                    <p class="offer-limit-note">This offer is currently unavailable.</p>
<?php endif; ?>

                    <button
                        id="favorite-button"
                        type="button"
                        class="favorite-button <?= $product['is_favorite'] ? 'active' : '' ?>"
                        aria-pressed="<?= $product['is_favorite'] ? 'true' : 'false' ?>"
                        title="Save to favorites"
                    >
                        <span class="material-symbols-outlined">favorite</span>
                        <span class="favorite-label"><?= $product['is_favorite'] ? 'Saved' : 'Save' ?></span>
                    </button>
                </div>
<?php endif; ?>
                <p id="cart-message" class="offer-message" role="status"></p>

                <div class="deal-stats">
                    <span>Bought: <b><?= (int) $product['sold'] ?></b></span>
                    <span>
                        <span class="review-stars"><?= str_repeat('★', (int) round((float) $product['rating'])) . str_repeat('☆', 5 - (int) round((float) $product['rating'])) ?></span>
                        <b id="stat-rating"><?= $product['rating'] !== null ? e($product['rating']) : '—' ?></b>
                    </span>
                    <a href="#reviews">Reviews: <b id="stat-reviews"><?= (int) $product['review_count'] ?></b></a>
                </div>
            </div>
        </article>

        <section class="offer-card deal-details">
            <h2>About the offer</h2>
            <p><?= e($product['description']) ?></p>

            <dl class="offer-facts">
                <dt>Category</dt>
                <dd><?= e($product['category']) ?></dd>

                <dt>Limit</dt>
                <dd><?= (int) $product['max_per_customer'] ?> coupon(s) per customer</dd>

                <dt>Coupons left</dt>
                <dd class="<?= $product['status'] === 'Available' && (int) $product['stock'] > 0 ? 'offer-available' : 'offer-unavailable' ?>">
                    <?= $product['status'] !== 'Available' ? 'Unavailable' : ((int) $product['stock'] > 0 ? (int) $product['stock'] : 'Sold out') ?>
                </dd>

                <dt>Offer number</dt>
                <dd><?= e($product['product_number']) ?></dd>
            </dl>
        </section>

<?php if ($showMap): ?>
        <section class="offer-card">
            <h2>How to get there</h2>
            <div
                id="partner-map"
                class="partner-map"
                data-key="<?= e(TWOGIS_KEY) ?>"
                data-lat="<?= e($product['latitude']) ?>"
                data-lng="<?= e($product['longitude']) ?>"
                aria-label="Map with the partner location"
            ></div>
        </section>
<?php endif; ?>

<?php if ($similar): ?>
        <section class="similar" aria-labelledby="similar-title">
            <div class="similar-heading">
                <h2 id="similar-title">Similar offers</h2>
                <a href="user-home.php#offers">Show all</a>
            </div>

            <div class="similar-grid">
<?php foreach ($similar as $item): ?>
<?php $itemDiscount = discountPercent((float) $item['price'], $item['old_price'] ? (float) $item['old_price'] : null); ?>
                    <a href="offer.php?id=<?= $item['id'] ?>" class="similar-card">
                        <div class="similar-image category-<?= strtolower(e($item['category'])) ?>">
<?php if ($item['image']): ?>
                            <img src="<?= e($item['image']) ?>" alt="">
<?php else: ?>
                            <span class="material-symbols-outlined"><?= categoryIcon($item['category']) ?></span>
<?php endif; ?>
<?php if ($itemDiscount > 0): ?>
                            <span class="similar-discount">-<?= $itemDiscount ?>%</span>
<?php endif; ?>
                        </div>
                        <h3><?= e($item['name']) ?></h3>
                        <p><?= e($item['partner'] ?? $item['category']) ?> · <?= (int) $item['sold'] ?> bought</p>
                        <span class="chip chip-price"><?= formatPrice((float) $item['price']) ?></span>
                    </a>
<?php endforeach; ?>
            </div>
        </section>
<?php endif; ?>

        <section class="offer-card offer-reviews" id="reviews">
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

            <div id="review-hint" class="review-notice" hidden>
                <span class="material-symbols-outlined">rate_review</span>
                <div>
                    <strong>Want to leave a review?</strong>
                    <p>
                        Only customers who bought this offer can write a review.
                        Buy a coupon, and after the order the review form will appear here.
                    </p>
                </div>
            </div>

            <p id="reviews-status" class="review-hint" role="status">Loading reviews...</p>
            <div id="reviews-list"></div>
            <button type="button" id="reviews-more" class="reviews-more" hidden>Show more reviews</button>
        </section>
<?php endif; ?>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
