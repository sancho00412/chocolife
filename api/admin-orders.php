<?php
// Admin only.
// GET   ?search=&status=&from=&to=&sort=&page=   -> one page of orders with their items
// PATCH {orderId, status}                   -> change the order status

require __DIR__ . '/../includes/api.php';

requireAdmin();
$db = getDb();

const STATUSES = ['Pending', 'Completed', 'Declined'];
const ORDERS_PER_PAGE = 20;

function validDate(string $date): bool
{
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    return $parsed && $parsed->format('Y-m-d') === $date;
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $search = is_string($_GET['search'] ?? null) ? trim($_GET['search']) : '';
        $status = $_GET['status'] ?? '';
        $from   = is_string($_GET['from'] ?? null) ? $_GET['from'] : '';
        $to     = is_string($_GET['to'] ?? null) ? $_GET['to'] : '';
        $sort   = is_string($_GET['sort'] ?? null) ? $_GET['sort'] : 'newest';
        $page   = max(1, (int) ($_GET['page'] ?? 1));

        $sortOptions = [
            'newest'     => 'o.created_at DESC',
            'oldest'     => 'o.created_at ASC',
            'total_desc' => 'o.total DESC',
            'total_asc'  => 'o.total ASC',
        ];

        if (!isset($sortOptions[$sort])) {
            sendError('Unknown sort option.');
        }

        // Filtering with several conditions at once
        $where  = ['1 = 1'];
        $params = [];

        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $where[] = '(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.email LIKE ?)';
            array_push($params, $like, $like, $like);
        }
        if (in_array($status, STATUSES, true)) {
            $where[]  = 'o.status = ?';
            $params[] = $status;
        }
        if ($from !== '') {
            if (!validDate($from)) {
                sendError('Invalid "from" date.');
            }
            $where[]  = 'o.created_at >= ?';
            $params[] = $from;
        }
        if ($to !== '') {
            if (!validDate($to)) {
                sendError('Invalid "to" date.');
            }
            // Up to the end of the chosen day
            $where[]  = 'o.created_at < ? + INTERVAL 1 DAY';
            $params[] = $to;
        }

        $whereSql = implode(' AND ', $where);

        // Number and sum of all matching orders, needed for the summary and pagination
        $stmt = $db->prepare("SELECT COUNT(*) AS count, COALESCE(SUM(o.total), 0) AS sum FROM orders o WHERE $whereSql");
        $stmt->execute($params);
        $totals = $stmt->fetch();

        $totalPages = max(1, (int) ceil($totals['count'] / ORDERS_PER_PAGE));
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * ORDERS_PER_PAGE;

        $stmt = $db->prepare("
            SELECT o.id, o.order_number, o.customer_name, o.email, o.phone, o.address,
                   o.total, o.status, o.created_at,
                   SUM(oi.quantity) AS items
            FROM orders o
            JOIN order_items oi ON oi.order_id = o.id
            WHERE $whereSql
            GROUP BY o.id
            ORDER BY {$sortOptions[$sort]}, o.id DESC
            LIMIT " . ORDERS_PER_PAGE . " OFFSET $offset
        ");
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        $itemsByOrder = [];

        if ($orders) {
            $ids = array_column($orders, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $stmt = $db->prepare("
                SELECT order_id, product_name, price, quantity
                FROM order_items
                WHERE order_id IN ($placeholders)
                ORDER BY id
            ");
            $stmt->execute($ids);

            foreach ($stmt->fetchAll() as $item) {
                $itemsByOrder[$item['order_id']][] = [
                    'name'     => $item['product_name'],
                    'price'    => (float) $item['price'],
                    'quantity' => (int) $item['quantity'],
                ];
            }
        }

        sendJson([
            'success' => true,
            'orders'  => array_map(fn(array $order) => [
                'id'           => (int) $order['id'],
                'orderNumber'  => $order['order_number'],
                'customerName' => $order['customer_name'],
                'email'        => $order['email'],
                'phone'        => $order['phone'],
                'address'      => $order['address'],
                'total'        => (float) $order['total'],
                'status'       => $order['status'],
                'date'         => date('d.m.Y H:i', strtotime($order['created_at'])),
                'itemCount'    => (int) $order['items'],
                'items'        => $itemsByOrder[$order['id']] ?? [],
            ], $orders),
            'count'      => (int) $totals['count'],
            'sum'        => (float) $totals['sum'],
            'page'       => $page,
            'totalPages' => $totalPages,
        ]);

    case 'PATCH':
        $data      = readJsonBody();
        $orderId   = (int) ($data['orderId'] ?? 0);
        $newStatus = field($data, 'status');

        if (!in_array($newStatus, STATUSES, true)) {
            sendError('Unknown order status.');
        }

        // Transaction: a declined order gives its coupons back to the stock,
        // a declined order that is opened again takes them back
        $db->beginTransaction();

        try {
            $stmt = $db->prepare('SELECT status FROM orders WHERE id = ? FOR UPDATE');
            $stmt->execute([$orderId]);
            $oldStatus = $stmt->fetchColumn();

            if ($oldStatus === false) {
                throw new RuntimeException('Order not found.');
            }

            $stmt = $db->prepare('
                SELECT oi.product_id, oi.quantity, p.stock, p.name
                FROM order_items oi
                JOIN products p ON p.id = oi.product_id
                WHERE oi.order_id = ?
                FOR UPDATE
            ');
            $stmt->execute([$orderId]);
            $items = $stmt->fetchAll();

            $stockStmt = $db->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');

            if ($newStatus === 'Declined' && $oldStatus !== 'Declined') {
                foreach ($items as $item) {
                    $stockStmt->execute([$item['quantity'], $item['product_id']]);
                }
            }

            if ($oldStatus === 'Declined' && $newStatus !== 'Declined') {
                foreach ($items as $item) {
                    if ((int) $item['stock'] < (int) $item['quantity']) {
                        throw new RuntimeException("Not enough coupons of \"{$item['name']}\" to restore this order.");
                    }
                    $stockStmt->execute([-$item['quantity'], $item['product_id']]);
                }
            }

            $db->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$newStatus, $orderId]);

            $db->commit();
        } catch (RuntimeException $e) {
            $db->rollBack();
            sendError($e->getMessage(), 409);
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        sendJson(['success' => true, 'status' => $newStatus]);

    default:
        sendError('Method not allowed.', 405);
}
