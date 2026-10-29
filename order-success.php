<?php
require __DIR__ . '/includes/page.php';

$user = requirePageUser();

// The order must belong to the logged-in user, nobody can open someone else's order
$stmt = getDb()->prepare('
    SELECT o.id, o.order_number, o.total, o.status, o.created_at, SUM(oi.quantity) AS items
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    WHERE o.id = ? AND o.user_id = ?
    GROUP BY o.id, o.order_number, o.total, o.status, o.created_at
');
$stmt->execute([(int) ($_GET['id'] ?? 0), $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
}

$pageTitle   = $order ? 'Order Placed' : 'Order not found';
$activePage  = '';
$pageCss     = ['user-home.css', 'order-success.css'];
$pageScripts = [];

require __DIR__ . '/includes/header.php';
?>

    <main class="success-page">
        <section class="success-card">
<?php if (!$order): ?>
            <h1>Order not found</h1>
            <p class="success-description">This order does not exist or belongs to another account.</p>
<?php else: ?>
            <div class="success-icon">✓</div>

            <p class="eyebrow">CHOCOLIFE</p>
            <h1>Order Placed Successfully!</h1>

            <p class="success-description">
                Thank you for your order. Your coupons will be confirmed by our team.
            </p>

            <div class="order-info">
                <div>
                    <span>Order Number</span>
                    <strong><?= e($order['order_number']) ?></strong>
                </div>

                <div>
                    <span>Order Status</span>
                    <strong class="pending-status"><?= e($order['status']) ?></strong>
                </div>

                <div>
                    <span>Total Amount</span>
                    <strong><?= formatPrice((float) $order['total']) ?></strong>
                </div>

                <div>
                    <span>Items</span>
                    <strong><?= (int) $order['items'] ?></strong>
                </div>
            </div>
<?php endif; ?>

            <div class="success-actions">
                <a href="user-home.php" class="primary-button">Continue Shopping</a>
                <a href="profile.php#orders" class="secondary-button">My Orders</a>
            </div>
        </section>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
