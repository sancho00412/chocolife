<?php
// Shared <head>, sidebar and top bar for admin pages.
// The page sets: $user, $pageTitle, $activePage

$adminLinks = [
    'dashboard' => ['admin.php',          'grid_view',   'Dashboard'],
    'orders'    => ['admin-orders.php',   'receipt_long', 'Orders'],
    'products'  => ['admin-products.php', 'inventory_2', 'Offers'],
    'users'     => ['admin-users.php',    'groups',      'Users'],
    'messages'  => ['admin-messages.php', 'mail',        'Messages'],
    'reports'   => ['admin-reports.php',  'analytics',   'Reports'],
];

// New feedback and partner requests: shown as a red counter next to "Messages"
$newMessages = (int) getDb()->query("SELECT COUNT(*) FROM messages WHERE status = 'New'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($pageTitle) ?> | Chocolife Admin</title>

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">
    <link rel="stylesheet" href="<?= asset('admin.css') ?>">

    <!-- Applies the saved light/dark theme before the page is drawn -->
    <script src="<?= asset('theme.js') ?>"></script>
</head>
<body>

<div class="admin-layout">
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="admin-sidebar-top">
            <a href="admin.php" class="brand-logo" aria-label="Chocolife admin">
                <span class="brand-text">chocolife</span>
                <span class="brand-heart">.me</span>
            </a>

            <button type="button" class="icon-button admin-close" id="admin-close" aria-label="Close menu">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <nav class="admin-nav" aria-label="Admin navigation">
<?php foreach ($adminLinks as $key => [$href, $icon, $label]): ?>
            <a href="<?= $href ?>" class="<?= $activePage === $key ? 'active' : '' ?>">
                <span class="material-symbols-outlined"><?= $icon ?></span>
                <?= $label ?>
<?php if ($key === 'messages' && $newMessages > 0): ?>
                <span class="nav-badge" title="New messages"><?= $newMessages ?></span>
<?php endif; ?>
            </a>
<?php endforeach; ?>

            <a href="admin-product-form.php" class="<?= $activePage === 'product-form' ? 'active' : '' ?>">
                <span class="material-symbols-outlined">add_box</span>
                Add Offer
            </a>

            <a href="user-home.php">
                <span class="material-symbols-outlined">storefront</span>
                View Store
            </a>

            <button type="button" id="logout-button" class="admin-logout">
                <span class="material-symbols-outlined">logout</span>
                Logout
            </button>
        </nav>
    </aside>

    <div class="admin-content">
        <header class="admin-topbar">
            <button type="button" class="icon-button admin-menu" id="admin-menu" aria-label="Open menu">
                <span class="material-symbols-outlined">menu</span>
            </button>

            <h1><?= e($pageTitle) ?></h1>

            <div class="theme-toggler" title="Toggle theme" role="button" tabindex="0" aria-label="Toggle dark theme">
                <span class="material-symbols-outlined active">sunny</span>
                <span class="material-symbols-outlined">moon_stars</span>
            </div>

            <div class="admin-profile">
                <p>Hey, <b><?= e($user['firstName']) ?></b></p>
                <small>Admin</small>
            </div>
        </header>

        <main class="admin-main">
