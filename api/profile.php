<?php
// PATCH {firstName, lastName, phone}             -> update my personal data
// POST  {currentPassword, newPassword}           -> change my password

require __DIR__ . '/../includes/api.php';

$user = requireLogin();
$db = getDb();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'PATCH':
        $data      = readJsonBody();
        $firstName = field($data, 'firstName');
        $lastName  = field($data, 'lastName');
        $phone     = field($data, 'phone');

        if (mb_strlen($firstName) < 2 || mb_strlen($firstName) > 50) {
            sendError('First name must be 2-50 characters.');
        }
        if (mb_strlen($lastName) < 2 || mb_strlen($lastName) > 50) {
            sendError('Last name must be 2-50 characters.');
        }
        if ($phone !== '' && !preg_match('/^\+?[\d\s()-]{10,20}$/', $phone)) {
            sendError('Enter a valid phone number or leave it empty.');
        }

        // The id comes from the session, so a user can change only his own profile
        $stmt = $db->prepare('UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?');
        $stmt->execute([$firstName, $lastName, $phone === '' ? null : $phone, $user['id']]);

        sendJson([
            'success'   => true,
            'firstName' => $firstName,
            'lastName'  => $lastName,
            'phone'     => $phone,
        ]);

    case 'POST':
        $data            = readJsonBody();
        $currentPassword = is_string($data['currentPassword'] ?? null) ? $data['currentPassword'] : '';
        $newPassword     = is_string($data['newPassword'] ?? null) ? $data['newPassword'] : '';

        $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);

        if (!password_verify($currentPassword, $stmt->fetchColumn())) {
            sendError('Current password is incorrect.', 403);
        }
        if (strlen($newPassword) < 8) {
            sendError('New password must be at least 8 characters.');
        }
        if ($newPassword === $currentPassword) {
            sendError('New password must be different from the current one.');
        }

        $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);

        sendJson(['success' => true]);

    default:
        sendError('Method not allowed.', 405);
}
