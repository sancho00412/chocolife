<?php
// POST {firstName, lastName, email, password} -> creates a new user

require __DIR__ . '/../includes/api.php';

requireMethod('POST');
$data = readJsonBody();

$firstName = field($data, 'firstName');
$lastName  = field($data, 'lastName');
$email     = strtolower(field($data, 'email'));
$password  = is_string($data['password'] ?? null) ? $data['password'] : '';

// Server-side validation: never trust only the checks in the browser
if (mb_strlen($firstName) < 2 || mb_strlen($firstName) > 50) {
    sendError('First name must be 2-50 characters.');
}
if (mb_strlen($lastName) < 2 || mb_strlen($lastName) > 50) {
    sendError('Last name must be 2-50 characters.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
    sendError('Please enter a valid email address.');
}
if (strlen($password) < 8) {
    sendError('Password must be at least 8 characters.');
}

$db = getDb();

$stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    sendError('This email is already registered.', 409);
}

$stmt = $db->prepare(
    'INSERT INTO users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)'
);
$stmt->execute([$firstName, $lastName, $email, password_hash($password, PASSWORD_DEFAULT)]);

sendJson(['success' => true], 201);
