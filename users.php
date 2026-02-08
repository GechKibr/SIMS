<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/utils.php';

session_start();
ensure_schema();

// Check session timeout
if (check_session_timeout()) {
    session_destroy();
    header('Location: /index.html?timeout=1');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /index.html');
    exit;
}

$user = get_user_by_id((int) $_SESSION['user_id']);
if (!$user) {
    header('Location: /index.html');
    exit;
}

// Only system admin can access this page
if ($user['role'] !== 'system_admin') {
    header('Location: /dashboard.php');
    exit;
}

$csrfToken = generate_csrf_token();

// Handle export request
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $pdo = get_pdo();
    $stmt = $pdo->query('SELECT id, username, role, full_name, email, is_active, created_at, last_login FROM users ORDER BY created_at DESC');
    $users = $stmt->fetchAll();
    
    $headers = ['ID', 'Username', 'Role', 'Full Name', 'Email', 'Status', 'Created At', 'Last Login'];
    $rows = array_map(function($u) {
        return [
            $u['id'],
            $u['username'],
            $u['role'],
            $u['full_name'],
            $u['email'] ?: 'N/A',
            $u['is_active'] ? 'Active' : 'Inactive',
            $u['created_at'],
            $u['last_login'] ?: 'Never'
        ];
    }, $users);
    
    download_csv('users_' . date('Y-m-d') . '.csv', $headers, $rows);
    exit;
}

// Get all users
$pdo = get_pdo();
$stmt = $pdo->query('SELECT id, username, role, full_name, email, is_active, created_at, last_login FROM users ORDER BY created_at DESC');
$users = $stmt->fetchAll();

// Get audit log count
$auditStmt = $pdo->query('SELECT COUNT(*) FROM audit_logs');
$auditCount = $auditStmt->fetchColumn();

$roleLabels = [
    'student' => 'Student',
    'teacher' => 'Teacher',
    'system_admin' => 'System Admin',
    'registrar_officer' => 'Registrar Officer',
    'transcript_officer' => 'Transcript Officer',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="User Management - A+SIS" />
    <title>User Management - A+SIS</title>
    <link rel="stylesheet" href="/assets/styles.css" />
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>

<header role="banner">
    <div class="logo" aria-label="A+SIS - Student Information Management System">A<SUP>+</SUP>SIS</div>
    <nav role="navigation" aria-label="Main navigation">
        <a href="/dashboard.php">Dashboard</a>
        <a href="/users.php" aria-current="page">Users</a>
        <a href="/index.html">Login</a>
    </nav>
</header>

<main id="main-content" role="main" style="max-width: 1200px; margin: 0 auto; padding: 30px 20px;">
    <div style="margin-bottom: 30px;">
        <h1 style="margin: 0 0 10px 0;">User Management</h1>
        <p style="color: var(--ink-700); margin: 0;">Manage system users, roles, and permissions. Total audit logs: <?php echo $auditCount; ?></p>
    </div>

    <div class="table-container">
        <div class="table-header">
            <input 
                type="search" 
                id="userSearch" 
                class="table-search" 
                placeholder="Search users..."
                aria-label="Search users"
            />
            <div class="table-actions">
                <button type="button" class="secondary" onclick="location.href='?export=csv'">
                    Export CSV
                </button>
                <button type="button" class="primary" style="width: auto; padding: 10px 20px;" onclick="openAddUserModal()">
                    Add User
                </button>
            </div>
        </div>

        <table id="usersTable">
            <thead>
                <tr>
                    <th data-sortable>ID</th>
                    <th data-sortable>Username</th>
                    <th data-sortable>Full Name</th>
                    <th data-sortable>Role</th>
                    <th data-sortable>Email</th>
                    <th data-sortable>Status</th>
                    <th data-sortable>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo $u['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                    <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                    <td><span class="badge info"><?php echo $roleLabels[$u['role']] ?? $u['role']; ?></span></td>
                    <td><?php echo htmlspecialchars($u['email'] ?: 'N/A'); ?></td>
                    <td>
                        <?php if ($u['is_active']): ?>
                            <span class="badge success">Active</span>
                        <?php else: ?>
                            <span class="badge error">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo format_date($u['created_at'], 'Y-m-d'); ?></td>
                    <td>
                        <button type="button" class="secondary" style="padding: 6px 12px; font-size: 13px;">
                            Edit
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 40px;">
        <button type="button" class="secondary" onclick="location.href='/dashboard.php'">
            ← Back to Dashboard
        </button>
    </div>
</main>

<!-- Add User Modal (placeholder) -->
<div id="addUserModal" class="modal-overlay" style="display: none;" role="dialog" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal">
        <div class="modal-header">
            <h2 id="modalTitle">Add New User</h2>
            <button class="modal-close" onclick="closeAddUserModal()" aria-label="Close modal">×</button>
        </div>
        
        <form id="addUserForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>" />
            
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username <span style="color: var(--error);">*</span></label>
                    <input type="text" id="username" name="username" required minlength="3" maxlength="50" />
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" />
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="full_name">Full Name <span style="color: var(--error);">*</span></label>
                    <input type="text" id="full_name" name="full_name" required maxlength="100" />
                </div>
                
                <div class="form-group">
                    <label for="role">Role <span style="color: var(--error);">*</span></label>
                    <select id="role" name="role" required>
                        <option value="">Select a role...</option>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="registrar_officer">Registrar Officer</option>
                        <option value="transcript_officer">Transcript Officer</option>
                        <option value="system_admin">System Admin</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password <span style="color: var(--error);">*</span></label>
                <input type="password" id="password" name="password" required minlength="6" />
                <small style="color: var(--ink-700);">Minimum 6 characters</small>
            </div>
            
            <div class="form-actions">
                <button type="button" class="secondary" onclick="closeAddUserModal()">Cancel</button>
                <button type="submit" class="primary" style="width: auto; padding: 12px 24px;">Create User</button>
            </div>
        </form>
    </div>
</div>

<footer role="contentinfo">
    Student Information Management System | A+SIS &copy; 2024
</footer>

<script src="/assets/utils.js"></script>
<script>
// Initialize table search
ASISUtils.initTableSearch('usersTable', 'userSearch');

// Initialize table sorting
ASISUtils.initTableSort('usersTable');

// Modal functions
function openAddUserModal() {
    ASISUtils.openModal('addUserModal');
}

function closeAddUserModal() {
    ASISUtils.closeModal('addUserModal');
    document.getElementById('addUserForm').reset();
}

// Form submission (placeholder)
document.getElementById('addUserForm').addEventListener('submit', (e) => {
    e.preventDefault();
    ASISUtils.showToast('This is a demo. User creation API endpoint would be implemented here.', 'info');
    closeAddUserModal();
});
</script>
</body>
</html>
