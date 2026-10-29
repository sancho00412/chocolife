<?php
// Helpers for PHP pages: access checks and safe output

require_once __DIR__ . '/session.php';

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

// Escapes text before printing it into HTML (protection against XSS)
function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
