<?php
// Shared <head> and header for user pages.
// The page sets: $user, $pageTitle, $activePage, $pageCss
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($pageTitle) ?> | Chocolife</title>

<?php foreach ($pageCss as $css): ?>
    <link rel="stylesheet" href="<?= e($css) ?>">
<?php endforeach; ?>

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">

    <link rel="stylesheet" href="auth.css">
    <link rel="stylesheet" href="brand-theme.css">
</head>
<body>

    <header class="user-header">
        <a href="user-home.php" class="brand">
            <span class="brand-text">chocolife</span><span class="brand-heart">.me♥</span>
        </a>

        <nav class="user-nav">
            <a href="user-home.php" class="<?= $activePage === 'home' ? 'active' : '' ?>">Home</a>
            <a href="user-home.php#offers">Offers</a>
            <a href="user-home.php#categories">Categories</a>
        </nav>

        <div class="user-actions">
            <a href="cart.html" class="cart-link">
                <span class="material-symbols-outlined">shopping_cart</span>
                Cart
                <span id="cart-badge">0</span>
            </a>

            <a href="profile.html" class="profile-nav-link <?= $activePage === 'profile' ? 'active' : '' ?>">
                My Profile
            </a>

            <span class="material-symbols-outlined">person</span>
            <span id="user-name"><?= e($user['firstName'] . ' ' . $user['lastName']) ?></span>

            <button id="logout-button" type="button">Logout</button>
        </div>
    </header>
