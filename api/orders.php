<?php
// GET                                          -> my orders with their items
// POST {customerName, email, phone, address}   -> place an order from my cart

require __DIR__ . '/../includes/api.php';

$user = requireCustomer();
$db = getDb();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $stmt = $db->prepare('
            SELECT o.id, o.order_number, o.total, o.status, o.created_at,
                   COUNT(oi.id) AS positions, SUM(oi.quantity) AS items
            FROM orders o
            JOIN order_items oi ON oi.order_id = o.id
            WHERE o.user_id = ?
            GROUP BY o.id, o.order_number, o.total, o.status, o.created_at
            ORDER BY o.created_at DESC
        ');
        $stmt->execute([$user['id']]);
        $orders = $stmt->fetchAll();

        // Items of all my orders in one query
        $itemsByOrder = [];

        if ($orders) {
            $ids = array_column($orders, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $stmt = $db->prepare("
                SELECT order_id, product_id, product_name, price, quantity
                FROM order_items
                WHERE order_id IN ($placeholders)
                ORDER BY id
            ");
            $stmt->execute($ids);

            foreach ($stmt->fetchAll() as $item) {
                $itemsByOrder[$item['order_id']][] = [
                    'productId' => $item['product_id'] === null ? null : (int) $item['product_id'],
                    'name'      => $item['product_name'],
                    'price'     => (float) $item['price'],
                    'quantity'  => (int) $item['quantity'],
                ];
            }
        }

        sendJson([
            'success' => true,
            'orders'  => array_map(fn(array $order) => [
                'id'          => (int) $order['id'],
                'orderNumber' => $order['order_number'],
                'total'       => (float) $order['total'],
                'status'      => $order['status'],
                'date'        => date('d.m.Y H:i', strtotime($order['created_at'])),
                'itemCount'   => (int) $order['items'],
                'items'       => $itemsByOrder[$order['id']] ?? [],
            ], $orders),
        ]);

    case 'POST':
        $data = readJsonBody();

        $customerName = field($data, 'customerName');
        $email        = strtolower(field($data, 'email'));
        $phone        = field($data, 'phone');
        $address      = field($data, 'address');

        $errors = [];

        if (mb_strlen($customerName) < 2 || mb_strlen($customerName) > 100) {
            $errors['customerName'] = 'Enter your full name (2-100 characters).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if (!preg_match('/^\+?[\d\s()-]{10,20}$/', $phone)) {
            $errors['phone'] = 'Enter a valid phone number.';
        }
        if (mb_strlen($address) < 5 || mb_strlen($address) > 200) {
            $errors['address'] = 'Address must contain 5-200 characters.';
        }

        if ($errors) {
            sendJson(['success' => false, 'error' => 'Please fix the errors in the form.', 'fields' => $errors], 400);
        }

        // Transaction: the order, its items, the stock and the cart change together or not at all
        $db->beginTransaction();

        try {
            // FOR UPDATE locks these products until COMMIT,
            // so two customers cannot buy the last coupon at the same time
            $stmt = $db->prepare('
                SELECT p.id, p.name, p.price, p.stock, p.status, ci.quantity
                FROM cart_items ci
                JOIN products p ON p.id = ci.product_id
                WHERE ci.user_id = ?
                ORDER BY p.id
                FOR UPDATE
            ');
            $stmt->execute([$user['id']]);
            $items = $stmt->fetchAll();

            if (!$items) {
                throw new RuntimeException('Your cart is empty.');
            }

            $total = 0;

            foreach ($items as $item) {
                if ($item['status'] !== 'Available') {
                    throw new RuntimeException("\"{$item['name']}\" is no longer available. Please remove it from the cart.");
                }
                if ((int) $item['stock'] < (int) $item['quantity']) {
                    throw new RuntimeException("Only {$item['stock']} coupon(s) of \"{$item['name']}\" left.");
                }

                $total += $item['price'] * $item['quantity'];
            }

            $orderNumber = 'ORD-' . date('ymdHis') . '-' . random_int(100, 999);

            $stmt = $db->prepare('
                INSERT INTO orders (order_number, user_id, customer_name, email, phone, address, total)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$orderNumber, $user['id'], $customerName, $email, $phone, $address, $total]);
            $orderId = (int) $db->lastInsertId();

            $itemStmt = $db->prepare('
                INSERT INTO order_items (order_id, product_id, product_name, price, quantity)
                VALUES (?, ?, ?, ?, ?)
            ');
            $stockStmt = $db->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');

            foreach ($items as $item) {
                $itemStmt->execute([$orderId, $item['id'], $item['name'], $item['price'], $item['quantity']]);
                $stockStmt->execute([$item['quantity'], $item['id']]);
            }

            $db->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$user['id']]);

            $db->commit();
        } catch (RuntimeException $e) {
            // Expected problem (empty cart, not enough stock): nothing is saved
            $db->rollBack();
            sendError($e->getMessage(), 409);
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        sendJson(['success' => true, 'orderId' => $orderId, 'orderNumber' => $orderNumber], 201);

    default:
        sendError('Method not allowed.', 405);
}
