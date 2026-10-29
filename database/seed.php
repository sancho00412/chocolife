<?php
// Fills the database with test data.
// Run from the project folder: C:\xampp\php\php.exe database\seed.php

if (PHP_SAPI !== 'cli') {
    exit('Run this script from the terminal.');
}

require __DIR__ . '/../config/db.php';

$db = getDb();

// Clear old data so the script can be run again
$db->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach (['favorites', 'reviews', 'order_items', 'orders', 'cart_items',
          'products', 'partners', 'categories', 'users'] as $table) {
    $db->exec("TRUNCATE TABLE $table");
}
$db->exec('SET FOREIGN_KEY_CHECKS = 1');

$db->beginTransaction();

try {
    // Categories
    $categoryIds = [];
    $stmt = $db->prepare('INSERT INTO categories (name) VALUES (?)');
    foreach (['Beauty', 'Food', 'Entertainment', 'Travel', 'Fitness'] as $name) {
        $stmt->execute([$name]);
        $categoryIds[$name] = (int) $db->lastInsertId();
    }

    // Partners: [name, address, phone]
    $partners = [
        ['Lotus SPA',          'Abay Ave 52',        '+7 701 111 2233'],
        ['Bella Cucina',       'Dostyk Ave 89',      '+7 701 222 3344'],
        ['Star Cinema',        'Satpayev St 30',     '+7 701 333 4455'],
        ['Glow Beauty Studio', 'Tole Bi St 101',     '+7 701 444 5566'],
        ['Burabay Resort',     'Burabay, Lake Rd 1', '+7 701 555 6677'],
        ['Iron Fitness',       'Al-Farabi Ave 77',   '+7 701 666 7788'],
        ['Strike Bowling',     'Rozybakiev St 247',  '+7 701 777 8899'],
        ['Aqua World',         'Kabanbay Batyr 15',  '+7 701 888 9900'],
        ['Sakura Sushi',       'Zheltoksan St 59',   '+7 701 999 0011'],
        ['Escape Lab',         'Gogol St 86',        '+7 702 111 2233'],
        ['Snow Peak Resort',   'Mountain Rd 12',     '+7 702 222 3344'],
    ];
    $partnerIds = [];
    $stmt = $db->prepare('INSERT INTO partners (name, address, phone) VALUES (?, ?, ?)');
    foreach ($partners as $partner) {
        $stmt->execute($partner);
        $partnerIds[$partner[0]] = (int) $db->lastInsertId();
    }

    // Products: [number, name, description, price, old price, category, partner, valid until, status]
    $products = [
        ['58241', 'SPA & Massage', 'Full body massage and sauna for 90 minutes.', 12000, 20000, 'Beauty', 'Lotus SPA', '2026-12-31', 'Available'],
        ['73185', 'Restaurant Dinner', 'Dinner set for two with dessert.', 8500, 14000, 'Food', 'Bella Cucina', '2026-12-31', 'Available'],
        ['42673', 'Cinema Tickets for Two', 'Two tickets to any 2D session.', 5000, 7000, 'Entertainment', 'Star Cinema', '2026-11-30', 'Available'],
        ['91524', 'Beauty Salon', 'Haircut, styling and manicure.', 15000, 25000, 'Beauty', 'Glow Beauty Studio', '2027-01-31', 'Available'],
        ['34862', 'Hotel Weekend', 'Two nights for two with breakfast.', 35000, 60000, 'Travel', 'Burabay Resort', '2027-03-31', 'Available'],
        ['65731', 'Fitness Club Membership', 'One month of unlimited gym access.', 20000, 30000, 'Fitness', 'Iron Fitness', '2027-02-28', 'Available'],
        ['82416', 'Bowling Night', 'Two hours of bowling for up to 6 people.', 7000, 12000, 'Entertainment', 'Strike Bowling', '2026-12-31', 'Available'],
        ['39175', 'Water Park Family', 'Day pass for 2 adults and 2 kids.', 18000, 28000, 'Entertainment', 'Aqua World', '2027-05-31', 'Available'],
        ['27419', 'Sushi Set', 'Set of 48 rolls with delivery.', 9000, 15000, 'Food', 'Sakura Sushi', '2026-12-15', 'Available'],
        ['51836', 'Yoga Course', 'Eight group yoga classes.', 16000, 24000, 'Fitness', 'Iron Fitness', '2027-01-31', 'Available'],
        ['64093', 'Quest Room', 'One hour escape room for up to 4 players.', 10000, 16000, 'Entertainment', 'Escape Lab', '2026-12-31', 'Available'],
        ['78352', 'Ski Day Pass', 'Full day ski lift pass with equipment rental.', 22000, 32000, 'Travel', 'Snow Peak Resort', '2027-03-15', 'Unavailable'],
    ];
    $productData = [];
    $stmt = $db->prepare(
        'INSERT INTO products (product_number, name, description, price, old_price,
                               category_id, partner_id, valid_until, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($products as [$number, $name, $description, $price, $oldPrice, $category, $partner, $validUntil, $status]) {
        $stmt->execute([$number, $name, $description, $price, $oldPrice,
                        $categoryIds[$category], $partnerIds[$partner], $validUntil, $status]);
        $productData[$number] = ['id' => (int) $db->lastInsertId(), 'name' => $name, 'price' => $price];
    }

    // Users: passwords are stored only as hashes
    $users = [
        ['Admin',   'Chocolife', 'admin@chocolife.kz', 'admin123', '+7 700 000 0000', 'admin'],
        ['Aruzhan', 'Serikova',  'aruzhan@mail.kz',    'user123',  '+7 701 234 5678', 'user'],
        ['Daniyar', 'Akhmetov',  'daniyar@mail.kz',    'user123',  '+7 702 345 6789', 'user'],
    ];
    $userData = [];
    $stmt = $db->prepare(
        'INSERT INTO users (first_name, last_name, email, password_hash, phone, role)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach ($users as [$first, $last, $email, $password, $phone, $role]) {
        $stmt->execute([$first, $last, $email, password_hash($password, PASSWORD_DEFAULT), $phone, $role]);
        $userData[$email] = ['id' => (int) $db->lastInsertId(), 'name' => "$first $last", 'phone' => $phone];
    }

    // Orders: [number, user email, address, status, date, [[product number, quantity], ...]]
    $orders = [
        ['ORD-1001', 'aruzhan@mail.kz', 'Almaty, Dostyk Ave 15', 'Completed', '2026-10-05 14:20:00', [['58241', 1], ['42673', 2]]],
        ['ORD-1002', 'daniyar@mail.kz', 'Almaty, Abay Ave 120',  'Completed', '2026-10-12 19:05:00', [['34862', 1]]],
        ['ORD-1003', 'aruzhan@mail.kz', 'Almaty, Dostyk Ave 15', 'Pending',   '2026-10-27 11:40:00', [['73185', 2], ['82416', 1]]],
    ];
    $orderStmt = $db->prepare(
        'INSERT INTO orders (order_number, user_id, customer_name, email, phone, address, total, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $itemStmt = $db->prepare(
        'INSERT INTO order_items (order_id, product_id, product_name, price, quantity)
         VALUES (?, ?, ?, ?, ?)'
    );
    foreach ($orders as [$number, $email, $address, $status, $date, $items]) {
        $total = 0;
        foreach ($items as [$productNumber, $quantity]) {
            $total += $productData[$productNumber]['price'] * $quantity;
        }

        $user = $userData[$email];
        $orderStmt->execute([$number, $user['id'], $user['name'], $email, $user['phone'], $address, $total, $status, $date]);
        $orderId = (int) $db->lastInsertId();

        foreach ($items as [$productNumber, $quantity]) {
            $product = $productData[$productNumber];
            $itemStmt->execute([$orderId, $product['id'], $product['name'], $product['price'], $quantity]);
        }
    }

    // Reviews: [user email, product number, rating, comment]
    $reviews = [
        ['aruzhan@mail.kz', '58241', 5, 'Very relaxing, will come again!'],
        ['aruzhan@mail.kz', '42673', 4, 'Good seats, but popcorn was not included.'],
        ['daniyar@mail.kz', '34862', 5, 'Beautiful place and great breakfast.'],
    ];
    $stmt = $db->prepare('INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)');
    foreach ($reviews as [$email, $productNumber, $rating, $comment]) {
        $stmt->execute([$userData[$email]['id'], $productData[$productNumber]['id'], $rating, $comment]);
    }

    // Favorites: [user email, product number]
    $favorites = [
        ['aruzhan@mail.kz', '91524'],
        ['daniyar@mail.kz', '65731'],
        ['daniyar@mail.kz', '64093'],
    ];
    $stmt = $db->prepare('INSERT INTO favorites (user_id, product_id) VALUES (?, ?)');
    foreach ($favorites as [$email, $productNumber]) {
        $stmt->execute([$userData[$email]['id'], $productData[$productNumber]['id']]);
    }

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    exit('Seeding failed: ' . $e->getMessage() . PHP_EOL);
}

echo 'Done: ' . count($products) . ' products, ' . count($users) . ' users, '
    . count($orders) . ' orders, ' . count($reviews) . ' reviews.' . PHP_EOL;
