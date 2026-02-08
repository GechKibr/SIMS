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
if (!$user || $user['role'] !== 'student') {
    http_response_code(403);
    echo 'Access denied.';
    exit;
}

$pdo = get_pdo();

$studentStmt = $pdo->prepare('SELECT * FROM students WHERE user_id = ? LIMIT 1');
$studentStmt->execute([(int) $user['id']]);
$student = $studentStmt->fetch();

$grades = [];
if ($student) {
    $gradesStmt = $pdo->prepare(
        'SELECT courses.code, courses.name, classes.term, classes.year, grades.score, grades.letter '
        . 'FROM grades '
        . 'JOIN enrollments ON grades.enrollment_id = enrollments.id '
        . 'JOIN classes ON enrollments.class_id = classes.id '
        . 'JOIN courses ON classes.course_id = courses.id '
        . 'WHERE enrollments.student_id = ? '
        . 'ORDER BY classes.year DESC'
    );
    $gradesStmt->execute([(int) $student['id']]);
    $grades = $gradesStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Student Portal</title>
    <link rel="stylesheet" href="/assets/styles.css" />
</head>
<body>
<header>
    <div class="logo">A<SUP>+</SUP>SIS</div>
    <nav>
        <a href="/dashboard.php">Dashboard</a>
        <a href="/student.php">Student</a>
    </nav>
</header>

<main class="page-shell">
    <div class="page-header">
        <div>
            <h1>Student Portal</h1>
            <div class="badge">Role: Student</div>
        </div>
        <button type="button" class="secondary" id="logoutBtn">Sign Out</button>
    </div>

    <?php if (!$student) : ?>
        <section class="card">
            <p>Your student profile is not linked yet. Please contact the registrar.</p>
        </section>
    <?php else : ?>
        <section class="card">
            <h2>My Profile</h2>
            <div class="form-grid">
                <div>
                    <label>Student No</label>
                    <div><?php echo htmlspecialchars($student['student_no']); ?></div>
                </div>
                <div>
                    <label>Name</label>
                    <div><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                </div>
                <div>
                    <label>Gender</label>
                    <div><?php echo htmlspecialchars($student['gender']); ?></div>
                </div>
                <div>
                    <label>Grade</label>
                    <div><?php echo htmlspecialchars((string) $student['grade_level']); ?></div>
                </div>
                <div>
                    <label>Section</label>
                    <div><?php echo htmlspecialchars($student['section']); ?></div>
                </div>
            </div>
        </section>

        <section class="card">
            <h2>My Grades</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Term</th>
                        <th>Year</th>
                        <th>Score</th>
                        <th>Letter</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grades as $row) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['code'] . ' - ' . $row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['term']); ?></td>
                            <td><?php echo htmlspecialchars((string) $row['year']); ?></td>
                            <td><?php echo htmlspecialchars((string) $row['score']); ?></td>
                            <td><?php echo htmlspecialchars($row['letter']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
</main>

<footer>
    Student Information Management System | A+SIS
</footer>

<script src="/assets/dashboard.js"></script>
</body>
</html>
