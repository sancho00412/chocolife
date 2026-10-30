<?php
// Fills the database with test data.
// Run from the project folder: C:\xampp\php\php.exe database\seed.php

if (PHP_SAPI !== 'cli') {
    exit('Run this script from the terminal.');
}

require __DIR__ . '/../config/db.php';

$db = getDb();

// The same "random" data on every run
mt_srand(2026);

// Clear old data so the script can be run again
$db->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach (['messages', 'favorites', 'reviews', 'order_items', 'orders', 'cart_items',
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

    // Partners: [name, city, address, phone, latitude, longitude]
    // Coordinates are approximate points on these streets, for the 2GIS map
    $partners = [
        ['Lotus SPA',          'Almaty',  'Abay Ave 52',            '+7 701 111 2233', 43.240300, 76.933000],
        ['Bella Cucina',       'Almaty',  'Dostyk Ave 89',          '+7 701 222 3344', 43.235300, 76.957000],
        ['Star Cinema',        'Almaty',  'Satpayev St 30',         '+7 701 333 4455', 43.236600, 76.926800],
        ['Glow Beauty Studio', 'Almaty',  'Tole Bi St 101',         '+7 701 444 5566', 43.253400, 76.918000],
        ['Burabay Resort',     'Burabay', 'Lake Rd 1',              '+7 701 555 6677', 53.083200, 70.311000],
        ['Iron Fitness',       'Almaty',  'Al-Farabi Ave 77',       '+7 701 666 7788', 43.218500, 76.928500],
        ['Strike Bowling',     'Almaty',  'Rozybakiev St 247',      '+7 701 777 8899', 43.219000, 76.898000],
        ['Aqua World',         'Almaty',  'Kabanbay Batyr 15',      '+7 701 888 9900', 43.260000, 76.960000],
        ['Sakura Sushi',       'Almaty',  'Zheltoksan St 59',       '+7 701 999 0011', 43.258000, 76.944000],
        ['Escape Lab',         'Almaty',  'Gogol St 86',            '+7 702 111 2233', 43.262000, 76.933000],
        ['Snow Peak Resort',   'Almaty',  'Mountain Rd 12',         '+7 702 222 3344', 43.128000, 77.080000],
        ['Silk Touch Spa',     'Almaty',  'Nauryzbay Batyr St 99',  '+7 702 333 4455', 43.255000, 76.942000],
        ['Thai Harmony',       'Almaty',  'Furmanov St 140',        '+7 702 444 5566', 43.247000, 76.950000],
        ['Nail Art Studio',    'Almaty',  'Bogenbay Batyr St 132',  '+7 702 555 6677', 43.250000, 76.936000],
        ['Lash Lab',           'Almaty',  'Kazybek Bi St 40',       '+7 702 666 7788', 43.257000, 76.948000],
        ['Derma Clinic',       'Almaty',  'Timiryazev St 42',       '+7 702 777 8899', 43.226000, 76.904000],
        ['Steak House One',    'Almaty',  'Kunaev St 77',           '+7 705 111 2233', 43.253000, 76.955000],
        ['Pizza Napoli',       'Almaty',  'Abylai Khan Ave 62',     '+7 705 222 3344', 43.256000, 76.941000],
        ['Coffee Point',       'Almaty',  'Panfilov St 98',         '+7 705 333 4455', 43.255500, 76.946000],
        ['Plov Center',        'Almaty',  'Raiymbek Ave 155',       '+7 705 444 5566', 43.271000, 76.925000],
        ['Burger Garage',      'Almaty',  'Seifullin Ave 500',      '+7 705 555 6677', 43.247000, 76.927000],
        ['Karting Arena',      'Almaty',  'Sain St 16',             '+7 707 111 2233', 43.233000, 76.860000],
        ['VR Zone',            'Almaty',  'Rozybakiev St 125',      '+7 707 222 3344', 43.232000, 76.896000],
        ['Rope Park',          'Almaty',  'Kok-Tobe Hill',          '+7 707 333 4455', 43.232500, 76.975000],
        ['Laser Tag Club',     'Almaty',  'Zhandosov St 58',        '+7 707 444 5566', 43.226000, 76.890000],
        ['Karaoke Star',       'Almaty',  'Makatayev St 117',       '+7 707 555 6677', 43.262000, 76.946000],
        ['Kolsai Tours',       'Almaty',  'Tole Bi St 59',          '+7 708 111 2233', 43.254000, 76.928000],
        ['Charyn Trips',       'Almaty',  'Kurmangazy St 61',       '+7 708 222 3344', 43.245000, 76.937000],
        ['Tamgaly Glamping',   'Almaty',  'Kapchagay Rd 25',        '+7 708 333 4455', 43.870000, 77.080000],
        ['Sky Balloon',        'Almaty',  'Talgar Rd 8',            '+7 708 444 5566', 43.300000, 77.230000],
        ['Aqua Swim Center',   'Almaty',  'Abay Ave 48',            '+7 747 111 2233', 43.240000, 76.936000],
        ['Box Club',           'Almaty',  'Zhambyl St 114',         '+7 747 222 3344', 43.248000, 76.931000],
        ['Dance Studio Move',  'Almaty',  'Shevchenko St 165',      '+7 747 333 4455', 43.245500, 76.913000],
        ['CrossFit Base',      'Almaty',  'Utepov St 19',           '+7 747 444 5566', 43.218000, 76.913000],
    ];
    $partnerIds = [];
    $stmt = $db->prepare(
        'INSERT INTO partners (name, city, address, phone, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach ($partners as $partner) {
        $stmt->execute($partner);
        $partnerIds[$partner[0]] = (int) $db->lastInsertId();
    }

    // Products: [number, name, description, price, old price, stock, max per customer, category, partner, buy until, status]
    // Max per customer: 2 for things you do together (cinema, dinner), 1 for personal ones (salon, gym)
    $products = [
        // Beauty
        ['58241', 'SPA & Massage', 'Full body massage and sauna for 90 minutes.', 12000, 20000, 40, 1, 'Beauty', 'Lotus SPA', '2026-12-31', 'Available'],
        ['91524', 'Beauty Salon', 'Haircut, styling and manicure.', 15000, 25000, 25, 1, 'Beauty', 'Glow Beauty Studio', '2027-01-31', 'Available'],
        ['10231', 'Thai Massage 60 Minutes', 'Traditional Thai massage by a certified master.', 9900, 14000, 80, 1, 'Beauty', 'Thai Harmony', '2026-12-15', 'Available'],
        ['10232', 'Romantic SPA for Two', 'Two-hour program for a couple: massage, hammam and tea ceremony.', 29000, 42000, 30, 1, 'Beauty', 'Silk Touch Spa', '2026-12-20', 'Available'],
        ['10233', 'Manicure and Pedicure', 'Hardware manicure and pedicure with gel polish.', 6500, 10000, 100, 1, 'Beauty', 'Nail Art Studio', '2027-01-15', 'Available'],
        ['10234', 'Lash Lamination', 'Lamination, tinting and care for your eyelashes.', 7500, 12000, 60, 1, 'Beauty', 'Lash Lab', '2026-12-31', 'Available'],
        ['10235', 'Face Cleansing', 'Combined face cleansing with a calming mask.', 8900, 18000, 50, 1, 'Beauty', 'Derma Clinic', '2027-02-15', 'Available'],
        ['10236', 'Hair Coloring', 'Coloring of any complexity, care and styling included.', 18000, 30000, 20, 1, 'Beauty', 'Glow Beauty Studio', '2027-01-31', 'Available'],
        ['10237', 'Stone Therapy Massage', 'Relaxing massage with hot volcanic stones, 75 minutes.', 14000, 21000, 40, 1, 'Beauty', 'Thai Harmony', '2026-12-31', 'Available'],
        ['10238', 'Brow Shaping', 'Brow correction, tinting and styling.', 4000, 7000, 90, 1, 'Beauty', 'Lash Lab', '2026-11-30', 'Available'],
        // Food
        ['73185', 'Restaurant Dinner', 'Dinner set for two with dessert.', 8500, 14000, 60, 2, 'Food', 'Bella Cucina', '2026-12-31', 'Available'],
        ['27419', 'Sushi Set', 'Set of 48 rolls with delivery.', 9000, 15000, 70, 2, 'Food', 'Sakura Sushi', '2026-11-15', 'Available'],
        ['20111', 'Steak for Two', 'Two ribeye steaks, garnish and sauces.', 19000, 28000, 40, 2, 'Food', 'Steak House One', '2026-12-31', 'Available'],
        ['20112', 'Large Pizza and Drinks', 'Any large pizza and two lemonades.', 5500, 8500, 150, 2, 'Food', 'Pizza Napoli', '2027-01-31', 'Available'],
        ['20113', 'Coffee and Dessert', 'Any coffee and a dessert of the day.', 2500, 4000, 200, 2, 'Food', 'Coffee Point', '2026-12-31', 'Available'],
        ['20114', 'Plov Feast', 'Big plate of Almaty plov, salad and tea for two.', 7000, 11000, 80, 2, 'Food', 'Plov Center', '2026-12-31', 'Available'],
        ['20115', 'Burger Combo for Two', 'Two burgers, fries and drinks.', 6000, 9000, 120, 2, 'Food', 'Burger Garage', '2026-12-15', 'Available'],
        ['20116', 'Italian Lunch', 'Soup, pasta and drink on weekdays from 12:00 to 16:00.', 4500, 7000, 100, 2, 'Food', 'Bella Cucina', '2026-12-31', 'Available'],
        ['20117', 'Sushi Master Class', 'Learn to make rolls with a chef, 2 hours.', 12000, 18000, 15, 2, 'Food', 'Sakura Sushi', '2027-01-31', 'Available'],
        ['20118', 'Breakfast Buffet', 'All-you-can-eat breakfast on weekends.', 3500, 5500, 90, 2, 'Food', 'Coffee Point', '2026-12-31', 'Available'],
        // Entertainment
        ['42673', 'Cinema Tickets for Two', 'Two tickets to any 2D session.', 5000, 7000, 120, 2, 'Entertainment', 'Star Cinema', '2026-11-20', 'Available'],
        ['82416', 'Bowling Night', 'Two hours of bowling for up to 6 people.', 7000, 12000, 80, 2, 'Entertainment', 'Strike Bowling', '2026-12-31', 'Available'],
        ['39175', 'Water Park Family', 'Day pass for 2 adults and 2 kids.', 18000, 28000, 30, 2, 'Entertainment', 'Aqua World', '2027-05-31', 'Available'],
        ['64093', 'Quest Room', 'One hour escape room for up to 4 players.', 10000, 16000, 1, 2, 'Entertainment', 'Escape Lab', '2026-12-31', 'Available'],
        ['30121', 'Karting 15 Minutes', 'Race on a professional indoor track.', 6000, 9000, 70, 2, 'Entertainment', 'Karting Arena', '2026-12-31', 'Available'],
        ['30122', 'VR Game Hour', 'One hour in virtual reality, more than 50 games.', 5000, 8000, 60, 2, 'Entertainment', 'VR Zone', '2027-01-31', 'Available'],
        ['30123', 'Rope Park Adventure', 'Rope park with three difficulty levels on Kok-Tobe.', 4500, 7000, 80, 2, 'Entertainment', 'Rope Park', '2026-11-30', 'Available'],
        ['30124', 'Laser Tag Battle', 'One hour of laser tag for a team.', 5500, 9000, 50, 2, 'Entertainment', 'Laser Tag Club', '2026-12-31', 'Available'],
        ['30125', 'Karaoke Room for 2 Hours', 'VIP karaoke room for up to 8 guests.', 9000, 15000, 40, 2, 'Entertainment', 'Karaoke Star', '2026-12-31', 'Available'],
        ['30126', 'IMAX Tickets for Two', 'Two tickets to any IMAX session.', 8000, 11000, 90, 2, 'Entertainment', 'Star Cinema', '2026-12-15', 'Available'],
        // Travel
        ['34862', 'Hotel Weekend', 'Two nights for two with breakfast.', 35000, 60000, 3, 1, 'Travel', 'Burabay Resort', '2027-03-31', 'Available'],
        ['78352', 'Ski Day Pass', 'Full day ski lift pass with equipment rental.', 22000, 32000, 0, 2, 'Travel', 'Snow Peak Resort', '2027-03-15', 'Unavailable'],
        ['40131', 'Kolsay Lakes Tour', 'One-day trip to Kolsay lakes with a guide and lunch.', 15000, 22000, 40, 2, 'Travel', 'Kolsai Tours', '2026-11-30', 'Available'],
        ['40132', 'Charyn Canyon Trip', 'Bus tour to the Valley of Castles, 12 hours.', 13000, 19000, 45, 2, 'Travel', 'Charyn Trips', '2026-11-30', 'Available'],
        ['40133', 'Glamping Night', 'Night in a dome tent with dinner by the fire.', 28000, 40000, 15, 1, 'Travel', 'Tamgaly Glamping', '2027-04-30', 'Available'],
        ['40134', 'Hot Air Balloon Flight', 'Morning flight over the mountains, about 1 hour.', 65000, 90000, 10, 2, 'Travel', 'Sky Balloon', '2027-05-31', 'Available'],
        ['40135', 'Mountain Hiking Day', 'Guided hike in the mountains, 6-8 hours.', 9000, 14000, 60, 2, 'Travel', 'Snow Peak Resort', '2026-11-30', 'Available'],
        ['40136', 'Lakeside Cottage Weekend', 'Cottage for up to 4 guests for two nights.', 48000, 70000, 8, 1, 'Travel', 'Burabay Resort', '2027-03-31', 'Available'],
        ['40137', 'Horse Riding Tour', 'Two hours of horse riding in the foothills.', 11000, 16000, 30, 2, 'Travel', 'Charyn Trips', '2026-12-31', 'Available'],
        ['40138', 'Big Almaty Lake Jeep Tour', 'Jeep tour to the lake with photo stops.', 18000, 26000, 25, 2, 'Travel', 'Kolsai Tours', '2026-11-30', 'Available'],
        // Fitness
        ['65731', 'Fitness Club Membership', 'One month of unlimited gym access.', 20000, 30000, 50, 1, 'Fitness', 'Iron Fitness', '2027-02-28', 'Available'],
        ['51836', 'Yoga Course', 'Eight group yoga classes.', 16000, 24000, 20, 1, 'Fitness', 'Iron Fitness', '2027-01-31', 'Available'],
        ['50141', 'Swimming Pool 8 Visits', 'Eight visits to a 25-meter pool.', 14000, 20000, 60, 1, 'Fitness', 'Aqua Swim Center', '2027-01-31', 'Available'],
        ['50142', 'Boxing Classes for a Month', 'Twelve group boxing classes with a coach.', 18000, 26000, 30, 1, 'Fitness', 'Box Club', '2027-01-31', 'Available'],
        ['50143', 'Dance Lessons', 'Eight classes: hip-hop, latina or contemporary.', 15000, 22000, 35, 1, 'Fitness', 'Dance Studio Move', '2026-12-31', 'Available'],
        ['50144', 'CrossFit Trial Month', 'One month of CrossFit classes for beginners.', 17000, 25000, 25, 1, 'Fitness', 'CrossFit Base', '2027-02-28', 'Available'],
        ['50145', 'Personal Training', 'Five sessions with a personal trainer.', 25000, 35000, 15, 1, 'Fitness', 'Iron Fitness', '2027-01-31', 'Available'],
        ['50146', 'Pilates Course', 'Eight pilates classes in small groups.', 13000, 19000, 30, 1, 'Fitness', 'Dance Studio Move', '2026-12-31', 'Available'],
        ['50147', 'Kids Swimming Course', 'Eight lessons for kids from 5 to 12 years.', 12000, 18000, 40, 1, 'Fitness', 'Aqua Swim Center', '2027-01-31', 'Available'],
        ['50148', 'Stretching Classes', 'Eight stretching classes for any level.', 9000, 14000, 50, 1, 'Fitness', 'Dance Studio Move', '2026-12-31', 'Available'],
    ];
    // Photos are in images/offers/<number>.jpg, their authors and licenses in photo-credits.php
    $photoCredits = require __DIR__ . '/photo-credits.php';

    $productData = [];
    $stmt = $db->prepare(
        'INSERT INTO products (product_number, name, description, price, old_price, stock, max_per_customer,
                               category_id, partner_id, valid_until, use_until, status, created_at,
                               image, image_credit, image_source)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($products as $i => [$number, $name, $description, $price, $oldPrice, $stock, $maxPerCustomer, $category, $partner, $buyUntil, $status]) {
        // Products appear one day after another, so "Newest first" has a stable order
        $createdAt = date('Y-m-d H:i:s', strtotime('2026-04-01 10:00:00') + $i * 86400);

        // A bought coupon can be used 30 days longer than it can be bought
        $useUntil = date('Y-m-d', strtotime("$buyUntil +30 days"));

        $hasPhoto = isset($photoCredits[$number]) && is_file(__DIR__ . "/../images/offers/$number.jpg");

        $stmt->execute([$number, $name, $description, $price, $oldPrice, $stock, $maxPerCustomer,
                        $categoryIds[$category], $partnerIds[$partner], $buyUntil, $useUntil, $status, $createdAt,
                        $hasPhoto ? "images/offers/$number.jpg" : null,
                        $hasPhoto ? $photoCredits[$number][0] : null,
                        $hasPhoto ? $photoCredits[$number][1] : null]);
        $productData[$number] = [
            'id'       => (int) $db->lastInsertId(),
            'name'     => $name,
            'price'    => $price,
            'category' => $category,
        ];
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
    $userStmt = $db->prepare(
        'INSERT INTO users (first_name, last_name, email, password_hash, phone, role, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($users as [$first, $last, $email, $password, $phone, $role, $createdAt]) {
        $userStmt->execute([$first, $last, $email, password_hash($password, PASSWORD_DEFAULT), $phone, $role, $createdAt]);
        $userData[$email] = ['id' => (int) $db->lastInsertId(), 'name' => "$first $last", 'phone' => $phone];
    }

    // 70 more customers who write the reviews. They all have the password user123
    $femaleNames = ['Aliya', 'Asel', 'Dinara', 'Gulnara', 'Kamila', 'Laura', 'Saule', 'Togzhan', 'Zarina', 'Ayana', 'Dilnaz', 'Inkar'];
    $maleNames   = ['Arman', 'Azamat', 'Bauyrzhan', 'Dauren', 'Erlan', 'Marat', 'Nurlan', 'Ruslan', 'Sanzhar', 'Yerzhan', 'Alibek', 'Miras'];
    $lastNames   = ['Abenov', 'Bekmukhanov', 'Dzhaksybekov', 'Ermekov', 'Ibraev', 'Kairatov', 'Mukanov', 'Nurpeisov', 'Sadykov', 'Zhumabaev'];

    $reviewers = [];
    $sharedHash = password_hash('user123', PASSWORD_DEFAULT);

    for ($i = 0; $i < 70; $i++) {
        // 35 women and 35 men; the last name shifts every 12 people, so every pair is unique
        $k = intdiv($i, 2);
        $isFemale = $i % 2 === 0;
        $first = $isFemale ? $femaleNames[$k % 12] : $maleNames[$k % 12];
        $last  = $lastNames[($k + intdiv($k, 12)) % 10] . ($isFemale ? 'a' : '');
        $email = strtolower("$first.$last") . '@mail.kz';

        $phone = sprintf('+7 70%d %03d %02d %02d', mt_rand(0, 8), mt_rand(100, 999), mt_rand(10, 99), mt_rand(10, 99));
        $createdAt = date('Y-m-d', strtotime('2026-03-01') + mt_rand(0, 60) * 86400);

        $userStmt->execute([$first, $last, $email, $sharedHash, $phone, 'user', $createdAt]);
        $userData[$email] = ['id' => (int) $db->lastInsertId(), 'name' => "$first $last", 'phone' => $phone];
        $reviewers[] = $email;
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
        ['ORD-1014', 'madina@mail.kz',    'Almaty, Satpayev St 18',   'Declined',  '2026-09-26 09:30:00', [['91524', 1]]],
        ['ORD-1015', 'nursultan@mail.kz', 'Almaty, Al-Farabi Ave 3',  'Completed', '2026-10-05 17:15:00', [['82416', 2]]],
        ['ORD-1016', 'aigerim@mail.kz',   'Almaty, Gogol St 7',       'Pending',   '2026-10-19 14:05:00', [['58241', 1]]],
        ['ORD-1017', 'aruzhan@mail.kz',   'Almaty, Dostyk Ave 15',    'Pending',   '2026-10-27 11:40:00', [['73185', 2], ['82416', 1]]],
        ['ORD-1018', 'timur@mail.kz',     'Almaty, Tole Bi St 44',    'Pending',   '2026-10-28 18:20:00', [['42673', 1]]],
    ];

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

    // Generated reviews: every offer gets 50-70 reviews.
    // Only buyers can write a review, so every reviewer first gets a completed order
    $comments = [
        'Beauty' => [
            'The master was very attentive, I felt completely relaxed.',
            'Clean salon and friendly staff. Recommend!',
            'Great result, I will come again with a friend.',
            'Everything was on time and very professional.',
            'Nice atmosphere, but I had to wait 15 minutes.',
            'Excellent value for this price.',
            'Very gentle and careful work, thank you!',
            'Booking by phone was easy, the coupon was accepted without questions.',
        ],
        'Food' => [
            'Delicious food and big portions.',
            'Cozy place, perfect for a date.',
            'Everything was fresh and tasty, the waiter was very polite.',
            'Good value, we will come back.',
            'The food was great, but it was noisy in the evening.',
            'Fast service and nice music.',
            'My friends loved it, thank you!',
            'Tasty, but we had to wait a bit for a table.',
        ],
        'Entertainment' => [
            'We had a lot of fun, time flew by!',
            'Great way to spend the weekend with friends.',
            'Modern equipment and friendly staff.',
            'The kids were delighted.',
            'Fun, but book in advance on weekends.',
            'Perfect for a birthday, everyone was happy.',
            'Good organization and a nice place.',
            'Exciting experience, would do it again.',
        ],
        'Travel' => [
            'Breathtaking views, the guide was great.',
            'Well organized trip, everything was on time.',
            'Beautiful nature and comfortable transport.',
            'Unforgettable weekend, highly recommend.',
            'The road is long, but it is worth it.',
            'Clean rooms and tasty food.',
            'A great present for my parents.',
            'Nice trip, take warm clothes!',
        ],
        'Fitness' => [
            'Professional coaches and modern equipment.',
            'I see results after the first weeks.',
            'Friendly atmosphere, even for beginners.',
            'Convenient schedule and clean changing rooms.',
            'Good classes, but groups are sometimes big.',
            'The coach explains everything very clearly.',
            'Great motivation, I bought a second course.',
            'Clean place and a nice community.',
        ],
    ];
    $addresses = ['Almaty, Abay Ave 10', 'Almaty, Dostyk Ave 25', 'Almaty, Tole Bi St 80', 'Almaty, Satpayev St 5',
                  'Almaty, Zhandosov St 12', 'Almaty, Raiymbek Ave 300', 'Almaty, Al-Farabi Ave 140'];
    // Mostly good ratings, like on a real service
    $ratingPool = [5, 5, 5, 5, 5, 5, 5, 5, 5, 5, 4, 4, 4, 4, 4, 4, 3, 3, 2, 1];

    $periodStart = strtotime('2026-05-01');
    $periodDays  = (int) ((strtotime('2026-10-25') - $periodStart) / 86400);
    $orderNumber = 20001;

    foreach ($productData as $productNumber => $product) {
        $count = mt_rand(50, 70);
        $picked = $reviewers;
        shuffle($picked);

        foreach (array_slice($picked, 0, $count) as $email) {
            $orderDate  = $periodStart + mt_rand(0, $periodDays) * 86400 + mt_rand(9 * 3600, 21 * 3600);
            $reviewDate = min($orderDate + mt_rand(1, 10) * 86400, strtotime('2026-10-29 20:00:00'));

            $orders[] = ['ORD-' . $orderNumber++, $email, $addresses[mt_rand(0, count($addresses) - 1)], 'Completed',
                         date('Y-m-d H:i:s', $orderDate), [[$productNumber, 1]]];

            // Some customers leave only a rating without text
            $pool = $comments[$product['category']];
            $comment = mt_rand(1, 100) <= 15 ? null : $pool[mt_rand(0, count($pool) - 1)];

            $reviews[] = [$email, $productNumber, $ratingPool[mt_rand(0, count($ratingPool) - 1)], $comment,
                          date('Y-m-d H:i:s', $reviewDate)];
        }
    }

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

    // Messages from the Help pages: [type, user email or null, name, email, phone, company, subject, message, status, date]
    $messages = [
        ['feedback', 'aruzhan@mail.kz', 'Aruzhan Serikova', 'aruzhan@mail.kz', null, null, 'Question about an order',
         'Hello! How long does it take to confirm an order? My order ORD-1017 is still pending.', 'New', '2026-10-28 10:15:00'],
        ['feedback', 'daniyar@mail.kz', 'Daniyar Akhmetov', 'daniyar@mail.kz', null, null, 'Suggestion',
         'It would be great to have gift certificates for any amount.', 'Answered', '2026-10-12 18:40:00'],
        ['business', null, 'Saule Ibraeva', 'saule@lotos-yoga.kz', '+7 701 765 4321', 'Lotos Yoga Studio', null,
         'We are a yoga studio in Almaty and would like to place our courses on Chocolife.', 'New', '2026-10-27 13:05:00'],
        ['business', null, 'Marat Sadykov', 'marat@burger-pro.kz', '+7 702 123 9876', 'Burger Pro', null,
         'We have 3 cafes in Almaty. What are the conditions for partners?', 'Answered', '2026-10-03 11:20:00'],
    ];
    $stmt = $db->prepare('
        INSERT INTO messages (type, user_id, name, email, phone, company, subject, message, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    foreach ($messages as [$type, $userEmail, $name, $email, $phone, $company, $subject, $message, $status, $date]) {
        $stmt->execute([$type, $userEmail ? $userData[$userEmail]['id'] : null, $name, $email, $phone,
                        $company, $subject, $message, $status, $date]);
    }

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    exit('Seeding failed: ' . $e->getMessage() . PHP_EOL);
}

echo 'Done: ' . count($products) . ' products, ' . count($userData) . ' users, '
    . count($orders) . ' orders, ' . count($reviews) . ' reviews.' . PHP_EOL;
