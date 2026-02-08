<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';

session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

ensure_schema();
$pdo = get_pdo();

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Username and password are required.']);
    exit;
}

$stmt = $pdo->prepare('SELECT id, username, password_hash, role, full_name FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Invalid username or password.']);
    exit;
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
$_SESSION['full_name'] = $user['full_name'];

$update = $pdo->prepare('UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?');
$update->execute([(int) $user['id']]);

$roleRedirects = [
    'student' => '/student.php',
    'teacher' => '/teacher.php',
    'system_admin' => '/admin.php',
    'registrar_officer' => '/registrar.php',
    'transcript_officer' => '/transcript.php',
];

$redirect = $roleRedirects[$user['role']] ?? '/dashboard.php';

echo json_encode(['ok' => true, 'redirect' => $redirect]);
