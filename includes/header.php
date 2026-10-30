<?php
// Shared <head> and header for user pages.
// The page sets: $user, $pageTitle, $activePage, $pageCss

// Number of items in the cart for the header badge
$stmt = getDb()->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?');
$stmt->execute([$user['id']]);
$cartCount = (int) $stmt->fetchColumn();

// Number of saved offers for the heart badge
$stmt = getDb()->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = ?');
$stmt->execute([$user['id']]);
$favoritesCount = (int) $stmt->fetchColumn();

// Items of the "Help" menu: [link, title, subtitle]
$helpLinks = [
    'faq'      => ['faq.php',      'Frequently asked questions', 'Questions and answers from our users'],
    'feedback' => ['feedback.php', 'Feedback',                   'For questions and comments'],
    'business' => ['business.php', 'For business',               'If you want to place your offer on Chocolife'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($pageTitle) ?> | Chocolife</title>

<?php foreach ($pageCss as $css): ?>
    <link rel="stylesheet" href="<?= e(asset($css)) ?>">
<?php endforeach; ?>

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">

    <link rel="stylesheet" href="<?= asset('auth.css') ?>">
    <link rel="stylesheet" href="<?= asset('brand-theme.css') ?>">
    <link rel="stylesheet" href="<?= asset('layout.css') ?>">
</head>
<body>

    <header class="user-header">
        <a href="user-home.php" class="brand">
            <span class="brand-text">chocolife</span><span class="brand-heart">.me♥</span>
        </a>

        <nav class="user-nav" aria-label="Main navigation">
            <a href="user-home.php" class="<?= $activePage === 'home' ? 'active' : '' ?>">Home</a>
            <a href="user-home.php#offers">Offers</a>
            <a href="user-home.php#categories">Categories</a>
        </nav>

        <div class="user-actions">
            <div class="help-dropdown">
                <button type="button" class="help-button" id="help-button"
                    aria-haspopup="true" aria-expanded="false" aria-controls="help-menu">
                    <span class="material-symbols-outlined">help</span>
                    Help
                </button>

                <div class="help-menu" id="help-menu" hidden>
<?php foreach ($helpLinks as $key => [$href, $title, $subtitle]): ?>
                    <a href="<?= $href ?>" class="help-item <?= $activePage === $key ? 'current' : '' ?>"
                        <?= $activePage === $key ? 'aria-current="page"' : '' ?>>
                        <span class="help-item-title"><?= $title ?></span>
                        <span class="help-item-subtitle"><?= $subtitle ?></span>
                    </a>
<?php endforeach; ?>
                </div>
            </div>

            <span class="phone-link" title="Support phone">
                <span class="material-symbols-outlined">call</span>
                <?= SUPPORT_PHONE ?>
            </span>

<?php if ($user['role'] === 'admin'): ?>
            <a href="admin.php" class="cart-link">
                <span class="material-symbols-outlined">dashboard</span>
                Admin panel
            </a>
<?php else: ?>
            <a href="favorites.php" class="favorites-link <?= $activePage === 'favorites' ? 'active' : '' ?>"
                title="Saved offers" aria-label="Saved offers">
                <span class="material-symbols-outlined">favorite</span>
                Favorites
                <span id="favorites-badge"><?= $favoritesCount ?></span>
            </a>

            <a href="cart.php" class="cart-link <?= $activePage === 'cart' ? 'active' : '' ?>">
                <span class="material-symbols-outlined">shopping_cart</span>
                Cart
                <span id="cart-badge"><?= $cartCount ?></span>
            </a>
<?php endif; ?>

            <a href="profile.php" class="profile-nav-link <?= $activePage === 'profile' ? 'active' : '' ?>">
                <span class="material-symbols-outlined">person</span>
                <span id="user-name"><?= e($user['firstName'] . ' ' . $user['lastName']) ?></span>
            </a>

            <button id="logout-button" type="button">Logout</button>
        </div>
    </header>
