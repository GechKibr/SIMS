<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';

session_start();
header('Content-Type: application/json');

// Log logout if user is authenticated
if (isset($_SESSION['user_id'])) {
    log_audit((int) $_SESSION['user_id'], 'logout', 'session', null, 'User logged out');
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

session_destroy();

echo json_encode(['ok' => true]);
