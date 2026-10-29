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

    // Products: [number, name, description, price, old price, stock, category, partner, valid until, status]
    $products = [
        ['58241', 'SPA & Massage', 'Full body massage and sauna for 90 minutes.', 12000, 20000, 40, 'Beauty', 'Lotus SPA', '2026-12-31', 'Available'],
        ['73185', 'Restaurant Dinner', 'Dinner set for two with dessert.', 8500, 14000, 60, 'Food', 'Bella Cucina', '2026-12-31', 'Available'],
        ['42673', 'Cinema Tickets for Two', 'Two tickets to any 2D session.', 5000, 7000, 120, 'Entertainment', 'Star Cinema', '2026-11-20', 'Available'],
        ['91524', 'Beauty Salon', 'Haircut, styling and manicure.', 15000, 25000, 25, 'Beauty', 'Glow Beauty Studio', '2027-01-31', 'Available'],
        ['34862', 'Hotel Weekend', 'Two nights for two with breakfast.', 35000, 60000, 3, 'Travel', 'Burabay Resort', '2027-03-31', 'Available'],
        ['65731', 'Fitness Club Membership', 'One month of unlimited gym access.', 20000, 30000, 50, 'Fitness', 'Iron Fitness', '2027-02-28', 'Available'],
        ['82416', 'Bowling Night', 'Two hours of bowling for up to 6 people.', 7000, 12000, 80, 'Entertainment', 'Strike Bowling', '2026-12-31', 'Available'],
        ['39175', 'Water Park Family', 'Day pass for 2 adults and 2 kids.', 18000, 28000, 30, 'Entertainment', 'Aqua World', '2027-05-31', 'Available'],
        ['27419', 'Sushi Set', 'Set of 48 rolls with delivery.', 9000, 15000, 70, 'Food', 'Sakura Sushi', '2026-11-15', 'Available'],
        ['51836', 'Yoga Course', 'Eight group yoga classes.', 16000, 24000, 20, 'Fitness', 'Iron Fitness', '2027-01-31', 'Available'],
        ['64093', 'Quest Room', 'One hour escape room for up to 4 players.', 10000, 16000, 45, 'Entertainment', 'Escape Lab', '2026-12-31', 'Available'],
        ['78352', 'Ski Day Pass', 'Full day ski lift pass with equipment rental.', 22000, 32000, 0, 'Travel', 'Snow Peak Resort', '2027-03-15', 'Unavailable'],
    ];
    $productData = [];
    $stmt = $db->prepare(
        'INSERT INTO products (product_number, name, description, price, old_price, stock,
                               category_id, partner_id, valid_until, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($products as $i => [$number, $name, $description, $price, $oldPrice, $stock, $category, $partner, $validUntil, $status]) {
        // Products appear one day after another, so "Newest first" has a stable order
        $createdAt = date('Y-m-d H:i:s', strtotime('2026-04-01 10:00:00') + $i * 86400);

        $stmt->execute([$number, $name, $description, $price, $oldPrice, $stock,
                        $categoryIds[$category], $partnerIds[$partner], $validUntil, $status, $createdAt]);
        $productData[$number] = ['id' => (int) $db->lastInsertId(), 'name' => $name, 'price' => $price];
    }

    // Users: passwords are stored only as hashes
    $users = [
        ['Admin',   'Chocolife',  'admin@chocolife.kz', 'admin123', '+7 700 000 0000', 'admin', '2026-03-01'],
        ['Aruzhan', 'Serikova',   'aruzhan@mail.kz',    'user123',  '+7 701 234 5678', 'user',  '2026-04-10'],
        ['Daniyar', 'Akhmetov',   'daniyar@mail.kz',    'user123',  '+7 702 345 6789', 'user',  '2026-04-22'],
        ['Madina',  'Nurlanova',  'madina@mail.kz',     'user123',  '+7 705 111 2233', 'user',  '2026-05-03'],
        ['Timur',   'Bekov',      'timur@mail.kz',      'user123',  '+7 707 222 3344', 'user',  '2026-05-18'],
        ['Aigerim', 'Tulegenova', 'aigerim@mail.kz',    'user123',  '+7 708 333 4455', 'user',  '2026-06-07'],
        ['Nursultan', 'Omarov',   'nursultan@mail.kz',  'user123',  '+7 747 444 5566', 'user',  '2026-08-14'],
        ['Dana',    'Kassymova',  'dana@mail.kz',       'user123',  '+7 771 555 6677', 'user',  '2026-10-02'],
    ];
    $userData = [];
    $stmt = $db->prepare(
        'INSERT INTO users (first_name, last_name, email, password_hash, phone, role, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($users as [$first, $last, $email, $password, $phone, $role, $createdAt]) {
        $stmt->execute([$first, $last, $email, password_hash($password, PASSWORD_DEFAULT), $phone, $role, $createdAt]);
        $userData[$email] = ['id' => (int) $db->lastInsertId(), 'name' => "$first $last", 'phone' => $phone];
    }

    // Orders: [number, user email, address, status, date, [[product number, quantity], ...]]
    $orders = [
        ['ORD-1001', 'aruzhan@mail.kz',   'Almaty, Dostyk Ave 15',    'Completed', '2026-05-05 14:20:00', [['58241', 1], ['42673', 2]]],
        ['ORD-1002', 'daniyar@mail.kz',   'Almaty, Abay Ave 120',     'Completed', '2026-05-12 19:05:00', [['34862', 1]]],
        ['ORD-1003', 'madina@mail.kz',    'Almaty, Satpayev St 18',   'Completed', '2026-05-28 12:10:00', [['91524', 1], ['58241', 1]]],
        ['ORD-1004', 'timur@mail.kz',     'Almaty, Tole Bi St 44',    'Completed', '2026-06-03 18:45:00', [['65731', 1]]],
        ['ORD-1005', 'aruzhan@mail.kz',   'Almaty, Dostyk Ave 15',    'Completed', '2026-06-15 20:30:00', [['27419', 2]]],
        ['ORD-1006', 'aigerim@mail.kz',   'Almaty, Gogol St 7',       'Declined',  '2026-06-21 10:15:00', [['39175', 1]]],
        ['ORD-1007', 'daniyar@mail.kz',   'Almaty, Abay Ave 120',     'Completed', '2026-07-02 16:00:00', [['82416', 1], ['64093', 1]]],
        ['ORD-1008', 'madina@mail.kz',    'Almaty, Satpayev St 18',   'Completed', '2026-07-19 13:25:00', [['51836', 1]]],
        ['ORD-1009', 'aigerim@mail.kz',   'Almaty, Gogol St 7',       'Completed', '2026-08-01 11:40:00', [['39175', 1], ['42673', 2]]],
        ['ORD-1010', 'timur@mail.kz',     'Almaty, Tole Bi St 44',    'Completed', '2026-08-09 21:10:00', [['73185', 1]]],
        ['ORD-1011', 'nursultan@mail.kz', 'Almaty, Al-Farabi Ave 3',  'Completed', '2026-08-23 15:35:00', [['34862', 1], ['58241', 1]]],
        ['ORD-1012', 'aruzhan@mail.kz',   'Almaty, Dostyk Ave 15',    'Completed', '2026-09-04 19:50:00', [['64093', 1]]],
        ['ORD-1013', 'daniyar@mail.kz',   'Almaty, Abay Ave 120',     'Completed', '2026-09-17 12:00:00', [['65731', 1], ['27419', 1]]],
        ['ORD-1014', 'madina@mail.kz',    'Almaty, Satpayev St 18',   'Declined',  '2026-09-26 09:30:00', [['91524', 2]]],
        ['ORD-1015', 'nursultan@mail.kz', 'Almaty, Al-Farabi Ave 3',  'Completed', '2026-10-05 17:15:00', [['82416', 2]]],
        ['ORD-1016', 'aigerim@mail.kz',   'Almaty, Gogol St 7',       'Pending',   '2026-10-19 14:05:00', [['58241', 2]]],
        ['ORD-1017', 'aruzhan@mail.kz',   'Almaty, Dostyk Ave 15',    'Pending',   '2026-10-27 11:40:00', [['73185', 2], ['82416', 1]]],
        ['ORD-1018', 'timur@mail.kz',     'Almaty, Tole Bi St 44',    'Pending',   '2026-10-28 18:20:00', [['42673', 1]]],
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

    // Reviews: [user email, product number, rating, comment, date]
    $reviews = [
        ['aruzhan@mail.kz',   '58241', 5, 'Very relaxing, will come again!',            '2026-05-10'],
        ['aruzhan@mail.kz',   '42673', 4, 'Good seats, but popcorn was not included.',  '2026-05-11'],
        ['daniyar@mail.kz',   '34862', 5, 'Beautiful place and great breakfast.',       '2026-05-20'],
        ['madina@mail.kz',    '91524', 4, 'Nice stylist, the manicure was perfect.',     '2026-06-02'],
        ['madina@mail.kz',    '58241', 4, 'Great massage, the sauna was a bit small.',   '2026-06-02'],
        ['timur@mail.kz',     '65731', 3, 'Good equipment, but crowded in the evening.', '2026-06-20'],
        ['aruzhan@mail.kz',   '27419', 5, 'Fresh and fast delivery.',                    '2026-06-16'],
        ['daniyar@mail.kz',   '82416', 4, 'Fun evening with friends.',                   '2026-07-05'],
        ['daniyar@mail.kz',   '64093', 5, 'Hard but very interesting quest!',            '2026-07-06'],
        ['aigerim@mail.kz',   '39175', 5, 'Kids were happy all day.',                    '2026-08-03'],
        ['nursultan@mail.kz', '34862', 4, 'Quiet place, a bit far from the city.',       '2026-08-30'],
        ['timur@mail.kz',     '73185', 2, 'The dessert was not fresh.',                  '2026-08-12'],
    ];
    $stmt = $db->prepare('INSERT INTO reviews (user_id, product_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?)');
    foreach ($reviews as [$email, $productNumber, $rating, $comment, $date]) {
        $stmt->execute([$userData[$email]['id'], $productData[$productNumber]['id'], $rating, $comment, $date]);
    }

    // Favorites: [user email, product number]
    $favorites = [
        ['aruzhan@mail.kz', '91524'],
        ['aruzhan@mail.kz', '39175'],
        ['daniyar@mail.kz', '65731'],
        ['daniyar@mail.kz', '64093'],
        ['madina@mail.kz',  '58241'],
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
