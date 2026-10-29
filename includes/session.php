<?php
// Starts the session and keeps the logged-in user in it

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,          // cookie lives until the browser is closed
        'path'     => '/',
        'httponly' => true,       // JavaScript cannot read the session cookie
        'samesite' => 'Strict',   // cookie is not sent with requests from other sites
    ]);
    session_start();
}

// Returns the logged-in user or null.
// The user is read from the database once per request, so a deleted user
// is logged out at once and a changed name or role is used immediately.
function currentUser(): ?array
{
    static $user = false;

    if ($user !== false) {
        return $user;
    }

    $user = null;
    $userId = $_SESSION['userId'] ?? null;

    if ($userId !== null) {
        $stmt = getDb()->prepare('SELECT id, first_name, last_name, email, role FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if ($row) {
            $user = [
                'id'        => (int) $row['id'],
                'firstName' => $row['first_name'],
                'lastName'  => $row['last_name'],
                'email'     => $row['email'],
                'role'      => $row['role'],
            ];
        } else {
            unset($_SESSION['userId']);
        }
    }

    return $user;
}

// Saves the user in the session after a successful login
function loginUser(array $user): void
{
    // New session id after login protects against session fixation
    session_regenerate_id(true);

    $_SESSION['userId'] = (int) $user['id'];
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
