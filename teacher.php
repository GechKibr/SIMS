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
if (!$user || $user['role'] !== 'teacher') {
    http_response_code(403);
    echo 'Access denied.';
    exit;
}

$pdo = get_pdo();
$message = '';

$teacherStmt = $pdo->prepare('SELECT * FROM teachers WHERE user_id = ? LIMIT 1');
$teacherStmt->execute([(int) $user['id']]);
$teacher = $teacherStmt->fetch();

if (!$teacher) {
    echo 'Teacher profile not linked. Please contact the system admin.';
    exit;
}

$action = $_POST['action'] ?? '';
if ($action === 'save_grade') {
    $gradeId = (int) ($_POST['grade_id'] ?? 0);
    $enrollmentId = (int) ($_POST['enrollment_id'] ?? 0);
    $score = (float) ($_POST['score'] ?? 0);
    $letter = trim($_POST['letter'] ?? '');

    if ($enrollmentId <= 0 || $score <= 0 || $letter === '') {
        $message = 'Enrollment, score, and letter are required.';
    } elseif ($gradeId > 0) {
        $stmt = $pdo->prepare('UPDATE grades SET score = ?, letter = ?, recorded_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$score, $letter, $gradeId]);
        $message = 'Grade updated.';
    } else {
        $stmt = $pdo->prepare('INSERT OR REPLACE INTO grades (enrollment_id, score, letter) VALUES (?, ?, ?)');
        $stmt->execute([$enrollmentId, $score, $letter]);
        $message = 'Grade saved.';
    }
}

if ($action === 'delete_grade') {
    $gradeId = (int) ($_POST['grade_id'] ?? 0);
    if ($gradeId > 0) {
        $stmt = $pdo->prepare('DELETE FROM grades WHERE id = ?');
        $stmt->execute([$gradeId]);
        $message = 'Grade deleted.';
    }
}

$editGradeId = (int) ($_GET['edit_grade'] ?? 0);
$editGrade = null;
if ($editGradeId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM grades WHERE id = ?');
    $stmt->execute([$editGradeId]);
    $editGrade = $stmt->fetch() ?: null;
}

$classes = $pdo->prepare(
    'SELECT classes.id, courses.code, courses.name, classes.term, classes.year '
    . 'FROM classes '
    . 'JOIN courses ON classes.course_id = courses.id '
    . 'WHERE classes.teacher_id = ? '
    . 'ORDER BY classes.year DESC'
);
$classes->execute([(int) $teacher['id']]);
$classList = $classes->fetchAll();

$enrollmentsStmt = $pdo->prepare(
    'SELECT enrollments.id, students.student_no, students.first_name, students.last_name, '
    . 'courses.code, courses.name, classes.term, classes.year '
    . 'FROM enrollments '
    . 'JOIN students ON enrollments.student_id = students.id '
    . 'JOIN classes ON enrollments.class_id = classes.id '
    . 'JOIN courses ON classes.course_id = courses.id '
    . 'WHERE classes.teacher_id = ? '
    . 'ORDER BY students.last_name'
);
$enrollmentsStmt->execute([(int) $teacher['id']]);
$enrollmentList = $enrollmentsStmt->fetchAll();

$grades = $pdo->prepare(
    'SELECT grades.id, grades.score, grades.letter, '
    . 'students.student_no, students.first_name, students.last_name, '
    . 'courses.code, courses.name, classes.term, classes.year '
    . 'FROM grades '
    . 'JOIN enrollments ON grades.enrollment_id = enrollments.id '
    . 'JOIN students ON enrollments.student_id = students.id '
    . 'JOIN classes ON enrollments.class_id = classes.id '
    . 'JOIN courses ON classes.course_id = courses.id '
    . 'WHERE classes.teacher_id = ? '
    . 'ORDER BY grades.recorded_at DESC'
);
$grades->execute([(int) $teacher['id']]);
$gradeList = $grades->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Teacher Portal</title>
    <link rel="stylesheet" href="/assets/styles.css" />
</head>
<body>
<header>
    <div class="logo">A<SUP>+</SUP>SIS</div>
    <nav>
        <a href="/dashboard.php">Dashboard</a>
        <a href="/teacher.php">Teacher</a>
    </nav>
</header>

<main class="page-shell">
    <div class="page-header">
        <div>
            <h1>Teacher Portal</h1>
            <div class="badge">Role: Teacher</div>
        </div>
        <button type="button" class="secondary" id="logoutBtn">Sign Out</button>
    </div>

    <?php if ($message !== '') : ?>
        <div class="card"><strong><?php echo htmlspecialchars($message); ?></strong></div>
    <?php endif; ?>

    <section class="card">
        <h2>My Classes</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Term</th>
                    <th>Year</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classList as $row) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['code'] . ' - ' . $row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['term']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['year']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="card">
        <h2>Gradebook Entry</h2>
        <form method="post">
            <input type="hidden" name="action" value="save_grade" />
            <input type="hidden" name="grade_id" value="<?php echo htmlspecialchars((string) ($editGrade['id'] ?? '')); ?>" />
            <div class="form-grid">
                <div>
                    <label>Enrollment</label>
                    <select name="enrollment_id" required>
                        <option value="">Select student enrollment</option>
                        <?php foreach ($enrollmentList as $enrollment) : ?>
                            <option value="<?php echo (int) $enrollment['id']; ?>" <?php echo ($editGrade && (int) $editGrade['enrollment_id'] === (int) $enrollment['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($enrollment['student_no'] . ' - ' . $enrollment['first_name'] . ' ' . $enrollment['last_name'] . ' (' . $enrollment['code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Score</label>
                    <input type="number" name="score" min="0" max="100" step="0.1" value="<?php echo htmlspecialchars((string) ($editGrade['score'] ?? '')); ?>" required />
                </div>
                <div>
                    <label>Letter</label>
                    <input type="text" name="letter" value="<?php echo htmlspecialchars($editGrade['letter'] ?? ''); ?>" required />
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="primary">Save Grade</button>
            </div>
        </form>
        <table class="table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Term</th>
                    <th>Score</th>
                    <th>Letter</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gradeList as $row) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['student_no'] . ' - ' . $row['first_name'] . ' ' . $row['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['code'] . ' - ' . $row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['term'] . ' ' . $row['year']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['score']); ?></td>
                        <td><?php echo htmlspecialchars($row['letter']); ?></td>
                        <td>
                            <div class="table-actions">
                                <a class="secondary" href="/teacher.php?edit_grade=<?php echo (int) $row['id']; ?>">Edit</a>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete_grade" />
                                    <input type="hidden" name="grade_id" value="<?php echo (int) $row['id']; ?>" />
                                    <button type="submit" class="secondary">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>

<footer>
    Student Information Management System | A+SIS
</footer>

<script src="/assets/dashboard.js"></script>
</body>
</html>
