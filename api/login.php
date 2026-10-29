<?php
// POST {email, password} -> logs the user in and returns user data

require __DIR__ . '/../includes/api.php';

requireMethod('POST');
$data = readJsonBody();

$email    = strtolower(field($data, 'email'));
$password = is_string($data['password'] ?? null) ? $data['password'] : '';

$stmt = getDb()->prepare(
    'SELECT id, first_name, last_name, email, password_hash, role FROM users WHERE email = ?'
);
$stmt->execute([$email]);
$user = $stmt->fetch();

// Same message for wrong email and wrong password, so nobody can check which emails exist
if (!$user || !password_verify($password, $user['password_hash'])) {
    sendError('Incorrect email or password.', 401);
}

loginUser($user);

sendJson(['success' => true, 'user' => currentUser()]);
