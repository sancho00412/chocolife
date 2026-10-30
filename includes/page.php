<?php
// Helpers for PHP pages: access checks and safe output

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/site.php';

// Sends guests to the login page, returns the logged-in user
function requirePageLogin(): array
{
    $user = currentUser();

    if ($user === null) {
        header('Location: login.html');
        exit;
    }

    return $user;
}

// Pages for customers only (cart, checkout): admins go to the admin panel
function requirePageUser(): array
{
    $user = requirePageLogin();

    if ($user['role'] !== 'user') {
        header('Location: admin.php');
        exit;
    }

    return $user;
}

// Only admins can open the page, other users go to the catalog
function requirePageAdmin(): array
{
    $user = requirePageLogin();

    if ($user['role'] !== 'admin') {
        header('Location: user-home.php');
        exit;
    }

    return $user;
}

// Link to a local CSS/JS file with its change time, e.g. offer.css?v=1761834914.
// When the file changes, the link changes too, so the browser does not use an old cached copy
function asset(string $path): string
{
    $file = __DIR__ . '/../' . $path;

    return is_file($file) ? $path . '?v=' . filemtime($file) : $path;
}

// Escapes text before printing it into HTML (protection against XSS)
function e(?string $text): string
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function formatPrice(float $price): string
{
    return number_format($price, 0, '.', ',') . ' ₸';
}

function formatDate(?string $date, string $format = 'd.m.Y'): string
{
    return $date ? date($format, strtotime($date)) : '—';
}

// Material icon for a category, used on cards without a photo
function categoryIcon(string $category): string
{
    return [
        'Beauty'        => 'spa',
        'Food'          => 'restaurant',
        'Entertainment' => 'movie',
        'Travel'        => 'flight',
        'Fitness'       => 'fitness_center',
    ][$category] ?? 'local_offer';
}

function discountPercent(float $price, ?float $oldPrice): int
{
    return $oldPrice ? (int) round((1 - $price / $oldPrice) * 100) : 0;
}
