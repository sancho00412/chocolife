<?php
// GET -> all categories with the number of offers in each

require __DIR__ . '/../includes/api.php';

requireMethod('GET');

// LEFT JOIN keeps categories that have no offers yet (count = 0)
$rows = getDb()->query('
    SELECT c.id, c.name, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id, c.name
    ORDER BY c.name
')->fetchAll();

$categories = array_map(fn(array $row) => [
    'id'           => (int) $row['id'],
    'name'         => $row['name'],
    'productCount' => (int) $row['product_count'],
], $rows);

sendJson(['success' => true, 'categories' => $categories]);
