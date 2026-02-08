<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/db.php';

session_start();
ensure_schema();

if (!isset($_SESSION['user_id'])) {
    header('Location: /index.html');
    exit;
}

$user = get_user_by_id((int) $_SESSION['user_id']);
if (!$user) {
    header('Location: /index.html');
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
$rolePages = [
    'student' => '/student.php',
    'teacher' => '/teacher.php',
    'system_admin' => '/admin.php',
    'registrar_officer' => '/registrar.php',
    'transcript_officer' => '/transcript.php',
];
$rolePage = $rolePages[$role] ?? '/dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>A+SIS Dashboard</title>
    <link rel="stylesheet" href="/assets/styles.css" />
</head>
<body>
<header>
    <div class="logo">A<SUP>+</SUP>SIS</div>
    <nav>
        <a href="/dashboard.php">Dashboard</a>
        <a href="/index.html">Login</a>
    </nav>
</header>

<section class="dashboard-layout">
    <aside class="dashboard-nav">
        <div>
            <strong><?php echo htmlspecialchars($fullName); ?></strong><br />
            <small><?php echo htmlspecialchars($roleLabel); ?></small>
        </div>
        <button class="active" type="button">Overview</button>
        <a class="nav-link" href="<?php echo htmlspecialchars($rolePage); ?>">Open Role Page</a>
        <button type="button">My Profile</button>
        <button type="button">Notifications</button>
        <button type="button" id="logoutBtn">Sign Out</button>
    </aside>

    <div class="dashboard-content">
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

<footer>
    Student Information Management System | A+SIS
</footer>

<script src="/assets/dashboard.js"></script>
</body>
</html>
