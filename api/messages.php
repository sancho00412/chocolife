<?php
// POST  {type, name, email, phone, company, subject, message} -> send feedback or a partner request
// GET   ?type=&status=                                        -> all messages (admin)
// PATCH {id, status}                                          -> mark a message as answered (admin)

require __DIR__ . '/../includes/api.php';

$user = requireLogin();
$db = getDb();

const SUBJECTS = ['Question about an order', 'Problem with a coupon', 'Suggestion', 'Other'];

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $data    = readJsonBody();
        $type    = field($data, 'type');
        $name    = field($data, 'name');
        $email   = strtolower(field($data, 'email'));
        $phone   = field($data, 'phone');
        $company = field($data, 'company');
        $subject = field($data, 'subject');
        $message = field($data, 'message');

        if (!in_array($type, ['feedback', 'business'], true)) {
            sendError('Unknown message type.');
        }

        $errors = [];

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors['name'] = 'Enter your name (2-100 characters).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if (mb_strlen($message) < 10 || mb_strlen($message) > 2000) {
            $errors['message'] = 'The message must be 10-2000 characters.';
        }

        if ($type === 'feedback' && !in_array($subject, SUBJECTS, true)) {
            $errors['subject'] = 'Choose a subject.';
        }

        // A partner request needs a company and a phone to call back
        if ($type === 'business') {
            if (mb_strlen($company) < 2 || mb_strlen($company) > 150) {
                $errors['company'] = 'Enter the company name.';
            }
            if (!preg_match('/^\+?[\d\s()-]{10,20}$/', $phone)) {
                $errors['phone'] = 'Enter a valid phone number.';
            }
        }

        if ($errors) {
            sendJson(['success' => false, 'error' => 'Please fix the errors in the form.', 'fields' => $errors], 400);
        }

        $stmt = $db->prepare('
            INSERT INTO messages (type, user_id, name, email, phone, company, subject, message)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $type,
            $user['id'],
            $name,
            $email,
            $phone === '' ? null : $phone,
            $type === 'business' ? $company : null,
            $type === 'feedback' ? $subject : null,
            $message,
        ]);

        sendJson(['success' => true], 201);

    case 'GET':
        requireAdmin();

        $type   = $_GET['type'] ?? '';
        $status = $_GET['status'] ?? '';

        $where  = ['1 = 1'];
        $params = [];

        if (in_array($type, ['feedback', 'business'], true)) {
            $where[]  = 'type = ?';
            $params[] = $type;
        }
        if (in_array($status, ['New', 'Answered'], true)) {
            $where[]  = 'status = ?';
            $params[] = $status;
        }

        $whereSql = implode(' AND ', $where);

        // New messages first, then the newest
        $stmt = $db->prepare("
            SELECT id, type, name, email, phone, company, subject, message, status, created_at
            FROM messages
            WHERE $whereSql
            ORDER BY FIELD(status, 'New', 'Answered'), created_at DESC
        ");
        $stmt->execute($params);

        $messages = array_map(fn(array $row) => [
            'id'      => (int) $row['id'],
            'type'    => $row['type'],
            'name'    => $row['name'],
            'email'   => $row['email'],
            'phone'   => $row['phone'],
            'company' => $row['company'],
            'subject' => $row['subject'],
            'message' => $row['message'],
            'status'  => $row['status'],
            'date'    => date('d.m.Y H:i', strtotime($row['created_at'])),
        ], $stmt->fetchAll());

        sendJson(['success' => true, 'messages' => $messages]);

    case 'PATCH':
        requireAdmin();

        $data   = readJsonBody();
        $id     = (int) ($data['id'] ?? 0);
        $status = field($data, 'status');

        if (!in_array($status, ['New', 'Answered'], true)) {
            sendError('Unknown status.');
        }

        $stmt = $db->prepare('UPDATE messages SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);

        $check = $db->prepare('SELECT 1 FROM messages WHERE id = ?');
        $check->execute([$id]);
        if (!$check->fetch()) {
            sendError('Message not found.', 404);
        }

        sendJson(['success' => true]);

    default:
        sendError('Method not allowed.', 405);
}
