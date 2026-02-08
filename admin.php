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
if (!$user || $user['role'] !== 'system_admin') {
    http_response_code(403);
    echo 'Access denied.';
    exit;
}

$pdo = get_pdo();
$message = '';
$action = $_POST['action'] ?? '';

if ($action === 'save_user') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');

    if ($username === '' || $role === '') {
        $message = 'Username and role are required.';
    } elseif ($userId > 0) {
        $update = 'UPDATE users SET username = ?, role = ?, full_name = ?';
        $params = [$username, $role, $fullName];
        if ($password !== '') {
            $update .= ', password_hash = ?';
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        $update .= ' WHERE id = ?';
        $params[] = $userId;
        $stmt = $pdo->prepare($update);
        $stmt->execute($params);
        $message = 'User updated.';
    } else {
        if ($password === '') {
            $message = 'Password is required for new users.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO users (username, password_hash, role, full_name) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                $role,
                $fullName,
            ]);
            $message = 'User created.';
        }
    }
}

if ($action === 'delete_user') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    if ($userId === (int) $user['id']) {
        $message = 'You cannot delete your own account.';
    } elseif ($userId > 0) {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $message = 'User deleted.';
    }
}

if ($action === 'save_teacher') {
    $teacherId = (int) ($_POST['teacher_id'] ?? 0);
    $staffNo = trim($_POST['staff_no'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $userId = (int) ($_POST['link_user_id'] ?? 0);
    $userId = $userId > 0 ? $userId : null;

    if ($staffNo === '' || $firstName === '' || $lastName === '' || $department === '') {
        $message = 'All teacher fields are required.';
    } elseif ($teacherId > 0) {
        $stmt = $pdo->prepare(
            'UPDATE teachers SET user_id = ?, staff_no = ?, first_name = ?, last_name = ?, department = ? WHERE id = ?'
        );
        $stmt->execute([$userId, $staffNo, $firstName, $lastName, $department, $teacherId]);
        $message = 'Teacher updated.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO teachers (user_id, staff_no, first_name, last_name, department) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $staffNo, $firstName, $lastName, $department]);
        $message = 'Teacher created.';
    }
}

if ($action === 'delete_teacher') {
    $teacherId = (int) ($_POST['teacher_id'] ?? 0);
    if ($teacherId > 0) {
        $stmt = $pdo->prepare('DELETE FROM teachers WHERE id = ?');
        $stmt->execute([$teacherId]);
        $message = 'Teacher deleted.';
    }
}

if ($action === 'save_course') {
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $credit = (int) ($_POST['credit'] ?? 0);
    $gradeLevel = (int) ($_POST['grade_level'] ?? 0);

    if ($code === '' || $name === '' || $credit <= 0 || $gradeLevel <= 0) {
        $message = 'All course fields are required.';
    } elseif ($courseId > 0) {
        $stmt = $pdo->prepare(
            'UPDATE courses SET code = ?, name = ?, credit = ?, grade_level = ? WHERE id = ?'
        );
        $stmt->execute([$code, $name, $credit, $gradeLevel, $courseId]);
        $message = 'Course updated.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO courses (code, name, credit, grade_level) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$code, $name, $credit, $gradeLevel]);
        $message = 'Course created.';
    }
}

if ($action === 'delete_course') {
    $courseId = (int) ($_POST['course_id'] ?? 0);
    if ($courseId > 0) {
        $stmt = $pdo->prepare('DELETE FROM courses WHERE id = ?');
        $stmt->execute([$courseId]);
        $message = 'Course deleted.';
    }
}

if ($action === 'save_class') {
    $classId = (int) ($_POST['class_id'] ?? 0);
    $courseId = (int) ($_POST['class_course_id'] ?? 0);
    $teacherId = (int) ($_POST['class_teacher_id'] ?? 0);
    $term = trim($_POST['term'] ?? '');
    $year = (int) ($_POST['year'] ?? 0);

    if ($courseId <= 0 || $teacherId <= 0 || $term === '' || $year <= 0) {
        $message = 'All class fields are required.';
    } elseif ($classId > 0) {
        $stmt = $pdo->prepare(
            'UPDATE classes SET course_id = ?, teacher_id = ?, term = ?, year = ? WHERE id = ?'
        );
        $stmt->execute([$courseId, $teacherId, $term, $year, $classId]);
        $message = 'Class updated.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO classes (course_id, teacher_id, term, year) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$courseId, $teacherId, $term, $year]);
        $message = 'Class created.';
    }
}

if ($action === 'delete_class') {
    $classId = (int) ($_POST['class_id'] ?? 0);
    if ($classId > 0) {
        $stmt = $pdo->prepare('DELETE FROM classes WHERE id = ?');
        $stmt->execute([$classId]);
        $message = 'Class deleted.';
    }
}

$editUserId = (int) ($_GET['edit_user'] ?? 0);
$editTeacherId = (int) ($_GET['edit_teacher'] ?? 0);
$editCourseId = (int) ($_GET['edit_course'] ?? 0);
$editClassId = (int) ($_GET['edit_class'] ?? 0);

$editUser = null;
if ($editUserId > 0) {
    $stmt = $pdo->prepare('SELECT id, username, role, full_name FROM users WHERE id = ?');
    $stmt->execute([$editUserId]);
    $editUser = $stmt->fetch() ?: null;
}

$editTeacher = null;
if ($editTeacherId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM teachers WHERE id = ?');
    $stmt->execute([$editTeacherId]);
    $editTeacher = $stmt->fetch() ?: null;
}

$editCourse = null;
if ($editCourseId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM courses WHERE id = ?');
    $stmt->execute([$editCourseId]);
    $editCourse = $stmt->fetch() ?: null;
}

$editClass = null;
if ($editClassId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM classes WHERE id = ?');
    $stmt->execute([$editClassId]);
    $editClass = $stmt->fetch() ?: null;
}

$users = $pdo->query('SELECT id, username, role, full_name, created_at FROM users ORDER BY id DESC')->fetchAll();
$teachers = $pdo->query('SELECT * FROM teachers ORDER BY id DESC')->fetchAll();
$courses = $pdo->query('SELECT * FROM courses ORDER BY id DESC')->fetchAll();
$classes = $pdo->query(
    'SELECT classes.id, courses.code AS course_code, courses.name AS course_name, '
    . 'teachers.first_name || " " || teachers.last_name AS teacher_name, '
    . 'classes.term, classes.year, classes.course_id, classes.teacher_id '
    . 'FROM classes '
    . 'JOIN courses ON classes.course_id = courses.id '
    . 'JOIN teachers ON classes.teacher_id = teachers.id '
    . 'ORDER BY classes.year DESC'
)->fetchAll();
$teacherOptions = $pdo->query('SELECT id, staff_no, first_name, last_name FROM teachers ORDER BY last_name')->fetchAll();
$courseOptions = $pdo->query('SELECT id, code, name FROM courses ORDER BY code')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>System Admin</title>
    <link rel="stylesheet" href="/assets/styles.css" />
</head>
<body>
<header>
    <div class="logo">A<SUP>+</SUP>SIS</div>
    <nav>
        <a href="/dashboard.php">Dashboard</a>
        <a href="/admin.php">Admin</a>
    </nav>
</header>

<main class="page-shell">
    <div class="page-header">
        <div>
            <h1>System Administration</h1>
            <div class="badge">Role: System Admin</div>
        </div>
        <button type="button" class="secondary" id="logoutBtn">Sign Out</button>
    </div>

    <?php if ($message !== '') : ?>
        <div class="card"><strong><?php echo htmlspecialchars($message); ?></strong></div>
    <?php endif; ?>

    <section class="card">
        <h2>User Accounts</h2>
        <form method="post">
            <input type="hidden" name="action" value="save_user" />
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars((string) ($editUser['id'] ?? '')); ?>" />
            <div class="form-grid">
                <div>
                    <label>Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($editUser['username'] ?? ''); ?>" required />
                </div>
                <div>
                    <label>Role</label>
                    <input type="text" name="role" value="<?php echo htmlspecialchars($editUser['role'] ?? ''); ?>" placeholder="system_admin" required />
                </div>
                <div>
                    <label>Full name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($editUser['full_name'] ?? ''); ?>" />
                </div>
                <div>
                    <label>Password <?php echo $editUser ? '(leave blank to keep)' : ''; ?></label>
                    <input type="password" name="password" />
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="primary">Save User</button>
            </div>
        </form>
        <div class="table-scroll">
            <table class="table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Full Name</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $row) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['role']); ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td>
                            <div class="table-actions">
                                <a class="secondary" href="/admin.php?edit_user=<?php echo (int) $row['id']; ?>">Edit</a>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete_user" />
                                    <input type="hidden" name="user_id" value="<?php echo (int) $row['id']; ?>" />
                                    <button type="submit" class="secondary">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2>Teachers</h2>
        <form method="post">
            <input type="hidden" name="action" value="save_teacher" />
            <input type="hidden" name="teacher_id" value="<?php echo htmlspecialchars((string) ($editTeacher['id'] ?? '')); ?>" />
            <div class="form-grid">
                <div>
                    <label>Staff No</label>
                    <input type="text" name="staff_no" value="<?php echo htmlspecialchars($editTeacher['staff_no'] ?? ''); ?>" required />
                </div>
                <div>
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($editTeacher['first_name'] ?? ''); ?>" required />
                </div>
                <div>
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($editTeacher['last_name'] ?? ''); ?>" required />
                </div>
                <div>
                    <label>Department</label>
                    <input type="text" name="department" value="<?php echo htmlspecialchars($editTeacher['department'] ?? ''); ?>" required />
                </div>
                <div>
                    <label>Link User ID (optional)</label>
                    <input type="number" name="link_user_id" value="<?php echo htmlspecialchars((string) ($editTeacher['user_id'] ?? '')); ?>" />
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="primary">Save Teacher</button>
            </div>
        </form>
        <div class="table-scroll">
            <table class="table">
            <thead>
                <tr>
                    <th>Staff No</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teachers as $row) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['staff_no']); ?></td>
                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['department']); ?></td>
                        <td>
                            <div class="table-actions">
                                <a class="secondary" href="/admin.php?edit_teacher=<?php echo (int) $row['id']; ?>">Edit</a>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete_teacher" />
                                    <input type="hidden" name="teacher_id" value="<?php echo (int) $row['id']; ?>" />
                                    <button type="submit" class="secondary">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2>Courses</h2>
        <form method="post">
            <input type="hidden" name="action" value="save_course" />
            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars((string) ($editCourse['id'] ?? '')); ?>" />
            <div class="form-grid">
                <div>
                    <label>Code</label>
                    <input type="text" name="code" value="<?php echo htmlspecialchars($editCourse['code'] ?? ''); ?>" required />
                </div>
                <div>
                    <label>Course Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($editCourse['name'] ?? ''); ?>" required />
                </div>
                <div>
                    <label>Credit</label>
                    <input type="number" name="credit" min="1" value="<?php echo htmlspecialchars((string) ($editCourse['credit'] ?? '')); ?>" required />
                </div>
                <div>
                    <label>Grade Level</label>
                    <input type="number" name="grade_level" min="1" value="<?php echo htmlspecialchars((string) ($editCourse['grade_level'] ?? '')); ?>" required />
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="primary">Save Course</button>
            </div>
        </form>
        <div class="table-scroll">
            <table class="table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Credit</th>
                    <th>Grade</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $row) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['code']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['credit']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['grade_level']); ?></td>
                        <td>
                            <div class="table-actions">
                                <a class="secondary" href="/admin.php?edit_course=<?php echo (int) $row['id']; ?>">Edit</a>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete_course" />
                                    <input type="hidden" name="course_id" value="<?php echo (int) $row['id']; ?>" />
                                    <button type="submit" class="secondary">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2>Classes</h2>
        <form method="post">
            <input type="hidden" name="action" value="save_class" />
            <input type="hidden" name="class_id" value="<?php echo htmlspecialchars((string) ($editClass['id'] ?? '')); ?>" />
            <div class="form-grid">
                <div>
                    <label>Course</label>
                    <select name="class_course_id" required>
                        <option value="">Select course</option>
                        <?php foreach ($courseOptions as $course) : ?>
                            <option value="<?php echo (int) $course['id']; ?>" <?php echo ($editClass && (int) $editClass['course_id'] === (int) $course['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['code'] . ' - ' . $course['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Teacher</label>
                    <select name="class_teacher_id" required>
                        <option value="">Select teacher</option>
                        <?php foreach ($teacherOptions as $teacher) : ?>
                            <option value="<?php echo (int) $teacher['id']; ?>" <?php echo ($editClass && (int) $editClass['teacher_id'] === (int) $teacher['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($teacher['staff_no'] . ' - ' . $teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Term</label>
                    <input type="text" name="term" value="<?php echo htmlspecialchars($editClass['term'] ?? 'Term 1'); ?>" required />
                </div>
                <div>
                    <label>Year</label>
                    <input type="number" name="year" value="<?php echo htmlspecialchars((string) ($editClass['year'] ?? '2026')); ?>" required />
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="primary">Save Class</button>
            </div>
        </form>
        <div class="table-scroll">
            <table class="table">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Teacher</th>
                    <th>Term</th>
                    <th>Year</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classes as $row) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['course_code'] . ' - ' . $row['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['teacher_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['term']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['year']); ?></td>
                        <td>
                            <div class="table-actions">
                                <a class="secondary" href="/admin.php?edit_class=<?php echo (int) $row['id']; ?>">Edit</a>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete_class" />
                                    <input type="hidden" name="class_id" value="<?php echo (int) $row['id']; ?>" />
                                    <button type="submit" class="secondary">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
        </div>
    </section>
</main>

<footer>
    Student Information Management System | A+SIS
</footer>

<script src="/assets/dashboard.js"></script>
</body>
</html>
