<?php
declare(strict_types=1);

function get_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config.php';
    }
    return $config;
}

function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = get_config();
    $dbPath = $config['db_path'];
    
    // Ensure the data directory exists
    $dataDir = dirname($dbPath);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Enable foreign keys
    $pdo->exec('PRAGMA foreign_keys = ON');

    return $pdo;
}

function ensure_schema(): void
{
    $pdo = get_pdo();

    // Users table with enhanced constraints
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE COLLATE NOCASE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL CHECK(role IN ('student', 'teacher', 'system_admin', 'registrar_officer', 'transcript_officer')),
            full_name TEXT NOT NULL DEFAULT '',
            email TEXT UNIQUE,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            last_login TEXT,
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        )"
    );

    // Audit log table for tracking user activities
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT NOT NULL,
            entity_type TEXT,
            entity_id INTEGER,
            details TEXT,
            ip_address TEXT,
            user_agent TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )"
    );

    // Login attempts table for security
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL,
            ip_address TEXT NOT NULL,
            success INTEGER NOT NULL DEFAULT 0,
            attempted_at TEXT NOT NULL DEFAULT (datetime('now'))
        )"
    );

    // Session tracking table
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS user_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            session_id TEXT NOT NULL UNIQUE,
            ip_address TEXT,
            user_agent TEXT,
            last_activity TEXT NOT NULL DEFAULT (datetime('now')),
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )"
    );

    // Create indexes for performance
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_audit_user_id ON audit_logs(user_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_audit_created ON audit_logs(created_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_login_username ON login_attempts(username)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_login_attempted ON login_attempts(attempted_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_session_user ON user_sessions(user_id)");

    // Check if admin user exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $stmt->execute(['admin']);
    $adminExists = $stmt->fetchColumn();

    if (!$adminExists) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $insert = $pdo->prepare(
            'INSERT INTO users (username, password_hash, role, full_name, email) VALUES (?, ?, ?, ?, ?)'
        );
        $insert->execute(['admin', $hash, 'system_admin', 'System Administrator', 'admin@asis.edu']);
    }
}

function get_user_by_id(int $userId): ?array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT id, username, role, full_name, email, is_active FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function log_audit(int $userId, string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address, user_agent) 
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $userId,
        $action,
        $entityType,
        $entityId,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

function log_login_attempt(string $username, bool $success): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, ?)'
    );
    $stmt->execute([
        $username,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $success ? 1 : 0
    ]);
}

function get_failed_login_count(string $username, int $minutes = 15): int
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM login_attempts 
         WHERE username = ? AND success = 0 
         AND attempted_at > datetime('now', '-' || ? || ' minutes')"
    );
    $stmt->execute([$username, $minutes]);
    return (int) $stmt->fetchColumn();
}

function check_session_timeout(): bool
{
    $config = get_config();
    $timeout = $config['session_timeout'] ?? 1800;
    
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        if ($elapsed > $timeout) {
            return true; // Session has timed out
        }
    }
    
    $_SESSION['last_activity'] = time();
    return false;
}

function generate_csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

function has_permission(string $userRole, string $requiredRole): bool
{
    $roleHierarchy = [
        'system_admin' => 5,
        'registrar_officer' => 4,
        'transcript_officer' => 3,
        'teacher' => 2,
        'student' => 1,
    ];
    
    $userLevel = $roleHierarchy[$userRole] ?? 0;
    $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;
    
    return $userLevel >= $requiredLevel;
}
