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
if (!$user || $user['role'] !== 'registrar_officer') {
    http_response_code(403);
    echo 'Access denied.';
    exit;
}

$pdo = get_pdo();
$message = '';
$action = $_POST['action'] ?? '';

if ($action === 'save_student') {
    $studentId = (int) ($_POST['student_id'] ?? 0);
    $studentNo = trim($_POST['student_no'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $gradeLevel = (int) ($_POST['grade_level'] ?? 0);
    $section = trim($_POST['section'] ?? '');
    $userId = (int) ($_POST['link_user_id'] ?? 0);
    $userId = $userId > 0 ? $userId : null;

    if ($studentNo === '' || $firstName === '' || $lastName === '' || $gender === '' || $gradeLevel <= 0 || $section === '') {
        $message = 'All student fields are required.';
    } elseif ($studentId > 0) {
        $stmt = $pdo->prepare(
            'UPDATE students SET user_id = ?, student_no = ?, first_name = ?, last_name = ?, gender = ?, grade_level = ?, section = ? WHERE id = ?'
        );
        $stmt->execute([$userId, $studentNo, $firstName, $lastName, $gender, $gradeLevel, $section, $studentId]);
        $message = 'Student updated.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO students (user_id, student_no, first_name, last_name, gender, grade_level, section) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $studentNo, $firstName, $lastName, $gender, $gradeLevel, $section]);
        $message = 'Student created.';
    }
}

if ($action === 'delete_student') {
    $studentId = (int) ($_POST['student_id'] ?? 0);
    if ($studentId > 0) {
        $stmt = $pdo->prepare('DELETE FROM students WHERE id = ?');
        $stmt->execute([$studentId]);
        $message = 'Student deleted.';
    }
}

if ($action === 'add_enrollment') {
    $studentId = (int) ($_POST['enroll_student_id'] ?? 0);
    $classId = (int) ($_POST['enroll_class_id'] ?? 0);
    if ($studentId > 0 && $classId > 0) {
        $stmt = $pdo->prepare('INSERT OR IGNORE INTO enrollments (student_id, class_id) VALUES (?, ?)');
        $stmt->execute([$studentId, $classId]);
        $message = 'Enrollment saved.';
    }
}

$editStudentId = (int) ($_GET['edit_student'] ?? 0);
$editStudent = null;
if ($editStudentId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$editStudentId]);
    $editStudent = $stmt->fetch() ?: null;
}

$students = $pdo->query('SELECT * FROM students ORDER BY id DESC')->fetchAll();
$studentOptions = $pdo->query('SELECT id, student_no, first_name, last_name FROM students ORDER BY last_name')->fetchAll();
$classOptions = $pdo->query(
    'SELECT classes.id, courses.code, courses.name, classes.term, classes.year, '
    . 'teachers.first_name || " " || teachers.last_name AS teacher_name '
    . 'FROM classes '
    . 'JOIN courses ON classes.course_id = courses.id '
    . 'JOIN teachers ON classes.teacher_id = teachers.id '
    . 'ORDER BY classes.year DESC'
)->fetchAll();
$enrollments = $pdo->query(
    'SELECT enrollments.id, students.student_no, students.first_name, students.last_name, '
    . 'courses.code, courses.name, classes.term, classes.year '
    . 'FROM enrollments '
    . 'JOIN students ON enrollments.student_id = students.id '
    . 'JOIN classes ON enrollments.class_id = classes.id '
    . 'JOIN courses ON classes.course_id = courses.id '
    . 'ORDER BY enrollments.enrolled_at DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Registrar Officer</title>
    <link rel="stylesheet" href="/assets/styles.css" />
</head>
<body>
<header>
    <div class="logo">A<SUP>+</SUP>SIS</div>
    <nav>
        <a href="/dashboard.php">Dashboard</a>
        <a href="/registrar.php">Registrar</a>
    </nav>
</header>

<main class="page-shell">
    <div class="page-header">
        <div>
            <h1>Registrar Office</h1>
            <div class="badge">Role: Registrar Officer</div>
        </div>
        <button type="button" class="secondary" id="logoutBtn">Sign Out</button>
    </div>

    <?php if ($message !== '') : ?>
        <div class="card"><strong><?php echo htmlspecialchars($message); ?></strong></div>
    <?php endif; ?>

    <section class="card">
        <h2>Student Records</h2>
        <form method="post">
            <input type="hidden" name="action" value="save_student" />
            <input type="hidden" name="student_id" value="<?php echo htmlspecialchars((string) ($editStudent['id'] ?? '')); ?>" />
            <div class="form-grid">
                <div>
                    <label>Student No</label>
                    <input type="text" name="student_no" value="<?php echo htmlspecialchars($editStudent['student_no'] ?? ''); ?>" required />
                </div>
                <div>
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($editStudent['first_name'] ?? ''); ?>" required />
                </div>
                <div>
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($editStudent['last_name'] ?? ''); ?>" required />
                </div>
                <div>
                    <label>Gender</label>
                    <input type="text" name="gender" value="<?php echo htmlspecialchars($editStudent['gender'] ?? ''); ?>" required />
                </div>
                <div>
                    <label>Grade Level</label>
                    <input type="number" name="grade_level" min="1" value="<?php echo htmlspecialchars((string) ($editStudent['grade_level'] ?? '')); ?>" required />
                </div>
                <div>
                    <label>Section</label>
                    <input type="text" name="section" value="<?php echo htmlspecialchars($editStudent['section'] ?? ''); ?>" required />
                </div>
                <div>
                    <label>Link User ID (optional)</label>
                    <input type="number" name="link_user_id" value="<?php echo htmlspecialchars((string) ($editStudent['user_id'] ?? '')); ?>" />
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="primary">Save Student</button>
            </div>
        </form>
        <table class="table">
            <thead>
                <tr>
                    <th>Student No</th>
                    <th>Name</th>
                    <th>Grade</th>
                    <th>Section</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $row) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['student_no']); ?></td>
                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['grade_level']); ?></td>
                        <td><?php echo htmlspecialchars($row['section']); ?></td>
                        <td>
                            <div class="table-actions">
                                <a class="secondary" href="/registrar.php?edit_student=<?php echo (int) $row['id']; ?>">Edit</a>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete_student" />
                                    <input type="hidden" name="student_id" value="<?php echo (int) $row['id']; ?>" />
                                    <button type="submit" class="secondary">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="card">
        <h2>Enrollments</h2>
        <form method="post">
            <input type="hidden" name="action" value="add_enrollment" />
            <div class="form-grid">
                <div>
                    <label>Student</label>
                    <select name="enroll_student_id" required>
                        <option value="">Select student</option>
                        <?php foreach ($studentOptions as $student) : ?>
                            <option value="<?php echo (int) $student['id']; ?>">
                                <?php echo htmlspecialchars($student['student_no'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Class</label>
                    <select name="enroll_class_id" required>
                        <option value="">Select class</option>
                        <?php foreach ($classOptions as $class) : ?>
                            <option value="<?php echo (int) $class['id']; ?>">
                                <?php echo htmlspecialchars($class['code'] . ' - ' . $class['name'] . ' (' . $class['term'] . ' ' . $class['year'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="primary">Assign Student</button>
            </div>
        </form>
        <table class="table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Term</th>
                    <th>Year</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($enrollments as $row) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['student_no'] . ' - ' . $row['first_name'] . ' ' . $row['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['code'] . ' - ' . $row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['term']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['year']); ?></td>
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
