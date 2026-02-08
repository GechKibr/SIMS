<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/db.php';

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

// Check if user is still active
if (!$user['is_active']) {
    session_destroy();
    header('Location: /index.html?deactivated=1');
    exit;
}

$role = $user['role'];
$roleLabels = [
    'student' => 'Student',
    'teacher' => 'Teacher',
    'system_admin' => 'System Admin',
    'registrar_officer' => 'Registrar Officer',
    'transcript_officer' => 'Transcript Officer',
];

$roleLabel = $roleLabels[$role] ?? 'User';
$fullName = $user['full_name'] ?: $user['username'];
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="A+SIS Dashboard - Access your personalized student information management tools" />
    <title>A+SIS Dashboard - <?php echo htmlspecialchars($roleLabel); ?></title>
    <link rel="stylesheet" href="/assets/styles.css" />
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>

<header role="banner">
    <div class="logo" aria-label="A+SIS - Student Information Management System">A<SUP>+</SUP>SIS</div>
    <nav role="navigation" aria-label="Main navigation">
        <a href="/dashboard.php" aria-current="page">Dashboard</a>
        <a href="/index.html">Login</a>
    </nav>
</header>

<section class="dashboard-layout">
    <aside class="dashboard-nav" role="navigation" aria-label="Dashboard navigation">
        <div>
            <strong><?php echo htmlspecialchars($fullName); ?></strong><br />
            <small><?php echo htmlspecialchars($roleLabel); ?></small>
        </div>
        <button class="active" type="button" aria-current="page">Overview</button>
        <button type="button">My Profile</button>
        <button type="button">Notifications</button>
        <button type="button" id="logoutBtn">Sign Out</button>
    </aside>

    <div class="dashboard-content" id="main-content" role="main">
        <div class="dashboard-card">
            <h3>Quick Actions</h3>
            <p>Shortcuts for your daily tasks.</p>
            <button class="secondary" type="button">Open Role Panel</button>
        </div>

        <?php if ($role === 'student') : ?>
            <div class="dashboard-card">
                <h3>My Courses</h3>
                <p>Review enrolled classes and schedules.</p>
                <button class="secondary" type="button">View Courses</button>
            </div>
            <div class="dashboard-card">
                <h3>Results</h3>
                <p>Access term results and GPA summary.</p>
                <button class="secondary" type="button">Check Results</button>
            </div>
        <?php endif; ?>

        <?php if ($role === 'teacher') : ?>
            <div class="dashboard-card">
                <h3>Class Roster</h3>
                <p>Manage roster and attendance for your classes.</p>
                <button class="secondary" type="button">Open Roster</button>
            </div>
            <div class="dashboard-card">
                <h3>Gradebook</h3>
                <p>Enter and review assessments.</p>
                <button class="secondary" type="button">Update Grades</button>
            </div>
        <?php endif; ?>

        <?php if ($role === 'registrar_officer') : ?>
            <div class="dashboard-card">
                <h3>Admissions Queue</h3>
                <p>Approve new student registrations.</p>
                <button class="secondary" type="button">Review Requests</button>
            </div>
            <div class="dashboard-card">
                <h3>Student Records</h3>
                <p>Update demographics and enrollment status.</p>
                <button class="secondary" type="button">Manage Records</button>
            </div>
        <?php endif; ?>

        <?php if ($role === 'transcript_officer') : ?>
            <div class="dashboard-card">
                <h3>Transcript Requests</h3>
                <p>Generate transcripts and verify records.</p>
                <button class="secondary" type="button">Open Requests</button>
            </div>
            <div class="dashboard-card">
                <h3>Academic History</h3>
                <p>Audit and archive academic records.</p>
                <button class="secondary" type="button">View Archive</button>
            </div>
        <?php endif; ?>

        <?php if ($role === 'system_admin') : ?>
            <div class="dashboard-card">
                <h3>User Management</h3>
                <p>Create accounts and assign roles.</p>
                <button class="secondary" type="button">Manage Users</button>
            </div>
            <div class="dashboard-card">
                <h3>System Reports</h3>
                <p>Monitor system usage and audits.</p>
                <button class="secondary" type="button">View Reports</button>
            </div>
            <div class="dashboard-card">
                <h3>Data Integrity</h3>
                <p>Backup schedules and validation checks.</p>
                <button class="secondary" type="button">Run Checks</button>
            </div>
        <?php endif; ?>
    </div>
</section>

<footer role="contentinfo">
    Student Information Management System | A+SIS &copy; 2024
</footer>

<script>
    // Pass CSRF token to JavaScript
    window.CSRF_TOKEN = '<?php echo $csrfToken; ?>';
</script>
<script src="/assets/dashboard.js"></script>
</body>
</html>
