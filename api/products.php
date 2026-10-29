<?php
// GET -> offers with search, category filter, sorting and pagination
// Example: api/products.php?search=spa&category=1&sort=price_asc&page=2

require __DIR__ . '/../includes/api.php';

requireMethod('GET');

const PER_PAGE = 8;

$search     = is_string($_GET['search'] ?? null) ? trim($_GET['search']) : '';
$categoryId = (int) ($_GET['category'] ?? 0);
$sort       = is_string($_GET['sort'] ?? null) ? $_GET['sort'] : 'newest';
$page       = max(1, (int) ($_GET['page'] ?? 1));

// Only these ORDER BY values are allowed, user input never goes into SQL directly
$sortOptions = [
    'newest'     => 'p.created_at DESC',
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'discount'   => '(p.old_price - p.price) / p.old_price DESC',
    'rating'     => 'avg_rating DESC, review_count DESC',
    'popular'    => 'sold DESC',
];

if (!isset($sortOptions[$sort])) {
    sendError('Unknown sort option.');
}

// WHERE conditions with placeholders
$where  = ['1 = 1'];
$params = [];

if ($search !== '') {
    // % and _ typed by the user are searched as normal characters
    $like = '%' . addcslashes($search, '%_\\') . '%';
    $where[]  = '(p.name LIKE ? OR p.description LIKE ?)';
    $params[] = $like;
    $params[] = $like;
}

if ($categoryId > 0) {
    $where[]  = 'p.category_id = ?';
    $params[] = $categoryId;
}

$whereSql = implode(' AND ', $where);
$db = getDb();

// Total number of matching offers, needed for pagination
$stmt = $db->prepare("SELECT COUNT(*) FROM products p WHERE $whereSql");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

$totalPages = max(1, (int) ceil($total / PER_PAGE));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * PER_PAGE;

// JOIN: every product has a category
// LEFT JOIN: partner, reviews and sales may be missing
// Subqueries with GROUP BY count the average rating and the number of sold coupons
$stmt = $db->prepare("
    SELECT p.id, p.product_number, p.name, p.description, p.price, p.old_price,
           p.status, p.image, p.valid_until,
           c.name AS category,
           pr.name AS partner,
           COALESCE(r.avg_rating, 0)   AS avg_rating,
           COALESCE(r.review_count, 0) AS review_count,
           COALESCE(s.sold, 0)         AS sold
    FROM products p
    JOIN categories c ON c.id = p.category_id
    LEFT JOIN partners pr ON pr.id = p.partner_id
    LEFT JOIN (
        SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
        FROM reviews
        GROUP BY product_id
    ) r ON r.product_id = p.id
    LEFT JOIN (
        SELECT oi.product_id, SUM(oi.quantity) AS sold
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        WHERE o.status <> 'Declined'
        GROUP BY oi.product_id
    ) s ON s.product_id = p.id
    WHERE $whereSql
    ORDER BY {$sortOptions[$sort]}, p.id DESC
    LIMIT " . PER_PAGE . " OFFSET $offset
");
$stmt->execute($params);

$products = array_map(fn(array $row) => [
    'id'            => (int) $row['id'],
    'productNumber' => $row['product_number'],
    'name'          => $row['name'],
    'description'   => $row['description'],
    'price'         => (float) $row['price'],
    'oldPrice'      => $row['old_price'] === null ? null : (float) $row['old_price'],
    'status'        => $row['status'],
    'image'         => $row['image'],
    'validUntil'    => $row['valid_until'],
    'category'      => $row['category'],
    'partner'       => $row['partner'],
    'rating'        => round((float) $row['avg_rating'], 1),
    'reviewCount'   => (int) $row['review_count'],
    'sold'          => (int) $row['sold'],
], $stmt->fetchAll());

sendJson([
    'success'    => true,
    'products'   => $products,
    'page'       => $page,
    'totalPages' => $totalPages,
    'total'      => $total,
]);
