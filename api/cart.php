<?php
// GET                          -> my cart
// POST   {productId, quantity} -> add an offer to the cart
// PATCH  {productId, quantity} -> change the quantity
// DELETE {productId}           -> remove an offer from the cart
// Every answer contains the whole cart, so the page can redraw it.

require __DIR__ . '/../includes/api.php';
require __DIR__ . '/../includes/shop.php';

$user = requireCustomer();
$db = getDb();

function sendCart(PDO $db, int $userId): void
{
    // Prices are always taken from products, so the cart shows the current price.
    // The subquery counts coupons of each offer that the user bought before
    $stmt = $db->prepare("
        SELECT p.id, p.name, p.price, p.image, p.stock, p.status, p.max_per_customer, ci.quantity,
               p.price * ci.quantity AS subtotal,
               COALESCE(b.bought, 0) AS bought
        FROM cart_items ci
        JOIN products p ON p.id = ci.product_id
        LEFT JOIN (
            SELECT oi.product_id, SUM(oi.quantity) AS bought
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            WHERE o.user_id = ? AND o.status <> 'Declined'
            GROUP BY oi.product_id
        ) b ON b.product_id = p.id
        WHERE ci.user_id = ?
        ORDER BY ci.added_at, ci.id
    ");
    $stmt->execute([$userId, $userId]);

    $items = array_map(function (array $row) {
        $max = maxQuantity((int) $row['stock'], (int) $row['max_per_customer'], (int) $row['bought']);
        $available = $row['status'] === 'Available' && (int) $row['quantity'] <= $max;

        return [
            'productId'      => (int) $row['id'],
            'name'           => $row['name'],
            'price'          => (float) $row['price'],
            'image'          => $row['image'],
            'maxQuantity'    => $max,
            'maxPerCustomer' => (int) $row['max_per_customer'],
            'available'      => $available,
            // Why this item blocks the checkout
            'problem'        => $available ? null : ($row['status'] !== 'Available'
                ? 'This offer is no longer available. Please remove it.'
                : limitMessage($row['name'], (int) $row['stock'], (int) $row['max_per_customer'], (int) $row['bought'])),
            'quantity'       => (int) $row['quantity'],
            'subtotal'       => (float) $row['subtotal'],
        ];
    }, $stmt->fetchAll());

    sendJson([
        'success' => true,
        'items'   => $items,
        'count'   => array_sum(array_column($items, 'quantity')),
        'total'   => array_sum(array_column($items, 'subtotal')),
    ]);
}

// Checks the offer and the wanted quantity in the cart
function checkQuantity(PDO $db, int $userId, int $productId, int $quantity): void
{
    $stmt = $db->prepare('SELECT name, stock, status, max_per_customer FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        sendError('Offer not found.', 404);
    }
    if ($product['status'] !== 'Available') {
        sendError('This offer is currently unavailable.', 409);
    }

    $bought = boughtQuantity($db, $userId, $productId);
    $max    = maxQuantity((int) $product['stock'], (int) $product['max_per_customer'], $bought);

    if ($quantity > $max) {
        sendError(limitMessage($product['name'], (int) $product['stock'], (int) $product['max_per_customer'], $bought), 409);
    }
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        sendCart($db, $user['id']);

    case 'POST':
        $data      = readJsonBody();
        $productId = (int) ($data['productId'] ?? 0);
        $quantity  = (int) ($data['quantity'] ?? 1);

        if ($quantity < 1) {
            sendError('Quantity must be at least 1.');
        }

        // Quantity already in the cart plus the new quantity
        $stmt = $db->prepare('SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$user['id'], $productId]);
        $inCart = (int) $stmt->fetchColumn();

        checkQuantity($db, $user['id'], $productId, $inCart + $quantity);

        $stmt = $db->prepare('
            INSERT INTO cart_items (user_id, product_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ');
        $stmt->execute([$user['id'], $productId, $quantity]);

        sendCart($db, $user['id']);

    case 'PATCH':
        $data      = readJsonBody();
        $productId = (int) ($data['productId'] ?? 0);
        $quantity  = (int) ($data['quantity'] ?? 0);

        if ($quantity < 1) {
            sendError('Quantity must be at least 1.');
        }

        checkQuantity($db, $user['id'], $productId, $quantity);

        $stmt = $db->prepare('UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$quantity, $user['id'], $productId]);

        if ($stmt->rowCount() === 0) {
            // Either the item is not in the cart or the quantity did not change
            $check = $db->prepare('SELECT 1 FROM cart_items WHERE user_id = ? AND product_id = ?');
            $check->execute([$user['id'], $productId]);

            if (!$check->fetch()) {
                sendError('This offer is not in your cart.', 404);
            }
        }

        sendCart($db, $user['id']);

    case 'DELETE':
        $productId = (int) (readJsonBody()['productId'] ?? 0);

        $stmt = $db->prepare('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$user['id'], $productId]);

        sendCart($db, $user['id']);

    default:
        sendError('Method not allowed.', 405);
}
