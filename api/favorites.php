<?php
// GET                -> my saved offers
// POST {productId}   -> save or remove an offer (toggle)

require __DIR__ . '/../includes/api.php';

$user = requireCustomer();
$db = getDb();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $stmt = $db->prepare('
            SELECT p.id, p.name, p.price, p.status, c.name AS category
            FROM favorites f
            JOIN products p ON p.id = f.product_id
            JOIN categories c ON c.id = p.category_id
            WHERE f.user_id = ?
            ORDER BY f.created_at DESC
        ');
        $stmt->execute([$user['id']]);

        $favorites = array_map(fn(array $row) => [
            'id'       => (int) $row['id'],
            'name'     => $row['name'],
            'price'    => (float) $row['price'],
            'status'   => $row['status'],
            'category' => $row['category'],
        ], $stmt->fetchAll());

        sendJson(['success' => true, 'favorites' => $favorites]);

    case 'POST':
        $productId = (int) (readJsonBody()['productId'] ?? 0);

        $stmt = $db->prepare('SELECT id FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        if (!$stmt->fetch()) {
            sendError('Offer not found.', 404);
        }

        $stmt = $db->prepare('DELETE FROM favorites WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$user['id'], $productId]);
        $isFavorite = false;

        // Nothing was deleted, so the offer was not saved yet
        if ($stmt->rowCount() === 0) {
            $db->prepare('INSERT INTO favorites (user_id, product_id) VALUES (?, ?)')
               ->execute([$user['id'], $productId]);
            $isFavorite = true;
        }

        $stmt = $db->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = ?');
        $stmt->execute([$user['id']]);

        sendJson(['success' => true, 'isFavorite' => $isFavorite, 'count' => (int) $stmt->fetchColumn()]);

    default:
        sendError('Method not allowed.', 405);
}
