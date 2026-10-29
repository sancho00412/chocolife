<?php
// Admin only.
// GET    ?search=&role=&sort=   -> users with their order statistics
// DELETE {id}                   -> delete a customer account

require __DIR__ . '/../includes/api.php';

$admin = requireAdmin();
$db = getDb();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $search = is_string($_GET['search'] ?? null) ? trim($_GET['search']) : '';
        $role   = $_GET['role'] ?? '';
        $sort   = is_string($_GET['sort'] ?? null) ? $_GET['sort'] : 'newest';

        $sortOptions = [
            'newest' => 'u.created_at DESC',
            'name'   => 'u.first_name, u.last_name',
            'spent'  => 'spent DESC',
            'orders' => 'orders_count DESC',
        ];

        if (!isset($sortOptions[$sort])) {
            sendError('Unknown sort option.');
        }

        $where  = ['1 = 1'];
        $params = [];

        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $where[] = "(CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR u.email LIKE ?)";
            array_push($params, $like, $like);
        }
        if (in_array($role, ['user', 'admin'], true)) {
            $where[]  = 'u.role = ?';
            $params[] = $role;
        }

        $whereSql = implode(' AND ', $where);

        // LEFT JOIN keeps users without orders; declined orders are not counted as spent money
        $stmt = $db->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.role, u.created_at,
                   COUNT(o.id) AS orders_count,
                   COALESCE(SUM(CASE WHEN o.status <> 'Declined' THEN o.total END), 0) AS spent,
                   MAX(o.created_at) AS last_order
            FROM users u
            LEFT JOIN orders o ON o.user_id = u.id
            WHERE $whereSql
            GROUP BY u.id, u.first_name, u.last_name, u.email, u.phone, u.role, u.created_at
            ORDER BY {$sortOptions[$sort]}
        ");
        $stmt->execute($params);

        $users = array_map(fn(array $row) => [
            'id'          => (int) $row['id'],
            'name'        => $row['first_name'] . ' ' . $row['last_name'],
            'email'       => $row['email'],
            'phone'       => $row['phone'],
            'role'        => $row['role'],
            'registered'  => date('d.m.Y', strtotime($row['created_at'])),
            'ordersCount' => (int) $row['orders_count'],
            'spent'       => (float) $row['spent'],
            'lastOrder'   => $row['last_order'] ? date('d.m.Y', strtotime($row['last_order'])) : null,
            'canDelete'   => $row['role'] === 'user',
        ], $stmt->fetchAll());

        sendJson(['success' => true, 'users' => $users]);

    case 'DELETE':
        $id = (int) (readJsonBody()['id'] ?? 0);

        $stmt = $db->prepare('SELECT role FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $role = $stmt->fetchColumn();

        if ($role === false) {
            sendError('User not found.', 404);
        }

        // Admin accounts (including your own) cannot be deleted here
        if ($role !== 'user') {
            sendError('Admin accounts cannot be deleted.', 403);
        }

        // Cart, reviews and favorites are deleted by ON DELETE CASCADE,
        // orders stay for the reports (user_id becomes NULL)
        $db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);

        sendJson(['success' => true]);

    default:
        sendError('Method not allowed.', 405);
}
