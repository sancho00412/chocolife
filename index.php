<?php
// Entry point: sends every visitor to the right start page

require __DIR__ . '/includes/session.php';

$user = currentUser();

if ($user === null) {
    header('Location: welcome.html');
} elseif ($user['role'] === 'admin') {
    header('Location: admin.php');
} else {
    header('Location: user-home.php');
}
