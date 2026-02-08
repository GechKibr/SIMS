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

// Check for too many failed login attempts
$config = get_config();
$maxAttempts = $config['max_login_attempts'] ?? 5;
$lockoutDuration = $config['lockout_duration'] ?? 900;
$failedCount = get_failed_login_count($username, (int)($lockoutDuration / 60));

if ($failedCount >= $maxAttempts) {
    log_login_attempt($username, false);
    http_response_code(429);
    echo json_encode([
        'ok' => false, 
        'message' => 'Too many failed login attempts. Please try again later.'
    ]);
    exit;
}

$stmt = $pdo->prepare('SELECT id, username, password_hash, role, full_name, is_active FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    log_login_attempt($username, false);
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Invalid username or password.']);
    exit;
}

// Check if user is active
if (!$user['is_active']) {
    log_login_attempt($username, false);
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Your account has been deactivated.']);
    exit;
}

// Successful login
log_login_attempt($username, true);

session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['last_activity'] = time();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Update last login
$update = $pdo->prepare("UPDATE users SET last_login = datetime('now') WHERE id = ?");
$update->execute([(int) $user['id']]);

// Log the successful login
log_audit((int) $user['id'], 'login', 'session', null, 'User logged in successfully');

echo json_encode(['ok' => true, 'redirect' => '/dashboard.php']);
