<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'message' => 'Not authenticated']);
    exit;
}

$user = get_user_by_id((int) $_SESSION['user_id']);
if (!$user) {
    echo json_encode(['ok' => false, 'message' => 'User not found']);
    exit;
}

echo json_encode([
    'ok' => true,
    'user' => $user,
]);
