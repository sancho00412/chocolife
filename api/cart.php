<?php
// GET                          -> my cart
// POST   {productId, quantity} -> add an offer to the cart
// PATCH  {productId, quantity} -> change the quantity
// DELETE {productId}           -> remove an offer from the cart
// Every answer contains the whole cart, so the page can redraw it.

require __DIR__ . '/../includes/api.php';

$user = requireCustomer();
$db = getDb();

function sendCart(PDO $db, int $userId): void
{
    // Prices are always taken from products, so the cart shows the current price
    $stmt = $db->prepare('
        SELECT p.id, p.name, p.price, p.image, p.stock, p.status, ci.quantity,
               p.price * ci.quantity AS subtotal
        FROM cart_items ci
        JOIN products p ON p.id = ci.product_id
        WHERE ci.user_id = ?
        ORDER BY ci.added_at, ci.id
    ');
    $stmt->execute([$userId]);

    $items = array_map(fn(array $row) => [
        'productId' => (int) $row['id'],
        'name'      => $row['name'],
        'price'     => (float) $row['price'],
        'image'     => $row['image'],
        'stock'     => (int) $row['stock'],
        'available' => $row['status'] === 'Available' && (int) $row['stock'] >= (int) $row['quantity'],
        'quantity'  => (int) $row['quantity'],
        'subtotal'  => (float) $row['subtotal'],
    ], $stmt->fetchAll());

    sendJson([
        'success' => true,
        'items'   => $items,
        'count'   => array_sum(array_column($items, 'quantity')),
        'total'   => array_sum(array_column($items, 'subtotal')),
    ]);
}

// Checks the offer and the wanted quantity, returns the product row
function checkQuantity(PDO $db, int $productId, int $quantity): array
{
    $stmt = $db->prepare('SELECT id, stock, status FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        sendError('Offer not found.', 404);
    }
    if ($product['status'] !== 'Available') {
        sendError('This offer is currently unavailable.', 409);
    }

    $max = min(99, (int) $product['stock']);

    if ($quantity < 1 || $quantity > $max) {
        sendError($max > 0
            ? "Quantity must be from 1 to $max."
            : 'This offer is sold out.', 409);
    }

    return $product;
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

        checkQuantity($db, $productId, $inCart + $quantity);

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

        checkQuantity($db, $productId, $quantity);

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
