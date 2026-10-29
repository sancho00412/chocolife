<?php
// Helpers shared by all files in api/

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/session.php';

header('Content-Type: application/json; charset=utf-8');

// Any unexpected error is returned as JSON, details go to the PHP error log
set_exception_handler(function (Throwable $e) {
    error_log((string) $e);
    sendJson(['success' => false, 'error' => 'Server error. Please try again later.'], 500);
});

function sendJson(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError(string $message, int $status = 400): void
{
    sendJson(['success' => false, 'error' => $message], $status);
}

function requireMethod(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        sendError('Method not allowed.', 405);
    }
}

// Reads JSON sent by fetch()
function readJsonBody(): array
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (!is_array($data)) {
        sendError('Invalid request data.');
    }

    return $data;
}

// Returns a trimmed string field or '' if it is missing or not a string
function field(array $data, string $key): string
{
    return is_string($data[$key] ?? null) ? trim($data[$key]) : '';
}

function requireLogin(): array
{
    $user = currentUser();

    if ($user === null) {
        sendError('Please log in first.', 401);
    }

    return $user;
}

function requireAdmin(): array
{
    $user = requireLogin();

    if ($user['role'] !== 'admin') {
        sendError('Access denied.', 403);
    }

    return $user;
}
