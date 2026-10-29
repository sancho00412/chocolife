<?php
// Starts the session and keeps the logged-in user in it

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,          // cookie lives until the browser is closed
        'path'     => '/',
        'httponly' => true,       // JavaScript cannot read the session cookie
        'samesite' => 'Strict',   // cookie is not sent with requests from other sites
    ]);
    session_start();
}

// Returns the logged-in user or null
function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

// Saves the user in the session after a successful login
function loginUser(array $user): void
{
    // New session id after login protects against session fixation
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'        => (int) $user['id'],
        'firstName' => $user['first_name'],
        'lastName'  => $user['last_name'],
        'email'     => $user['email'],
        'role'      => $user['role'],
    ];
}

// Removes the session completely
function logoutUser(): void
{
    $_SESSION = [];

    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $params['path'],
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    session_destroy();
}
