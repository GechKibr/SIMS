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
if (!$user || $user['role'] !== 'transcript_officer') {
    http_response_code(403);
    echo 'Access denied.';
    exit;
}

$pdo = get_pdo();
$message = '';
$action = $_POST['action'] ?? '';

if ($action === 'create_transcript') {
    $studentId = (int) ($_POST['student_id'] ?? 0);
    $term = trim($_POST['term'] ?? '');
    $year = (int) ($_POST['year'] ?? 0);

    if ($studentId <= 0 || $term === '' || $year <= 0) {
        $message = 'Student, term, and year are required.';
    } else {
        $avgStmt = $pdo->prepare(
            'SELECT AVG(grades.score) AS avg_score '
            . 'FROM grades '
            . 'JOIN enrollments ON grades.enrollment_id = enrollments.id '
            . 'JOIN classes ON enrollments.class_id = classes.id '
            . 'WHERE enrollments.student_id = ? AND classes.term = ? AND classes.year = ?'
        );
        $avgStmt->execute([$studentId, $term, $year]);
        $avgScore = $avgStmt->fetchColumn();

        if ($avgScore === null) {
            $message = 'No grades found for this student in the selected term.';
        } else {
            $gpa = round(((float) $avgScore) / 25, 2);
            $stmt = $pdo->prepare(
                'INSERT INTO transcripts (student_id, term, year, gpa) VALUES (?, ?, ?, ?) '
                . 'ON CONFLICT(student_id, term, year) DO UPDATE SET gpa = excluded.gpa, created_at = CURRENT_TIMESTAMP'
            );
            $stmt->execute([$studentId, $term, $year, $gpa]);
            $message = 'Transcript generated.';
        }
    }
}

$studentOptions = $pdo->query('SELECT id, student_no, first_name, last_name FROM students ORDER BY last_name')->fetchAll();
$transcripts = $pdo->query(
    'SELECT transcripts.id, transcripts.term, transcripts.year, transcripts.gpa, '
    . 'students.student_no, students.first_name, students.last_name '
    . 'FROM transcripts '
    . 'JOIN students ON transcripts.student_id = students.id '
    . 'ORDER BY transcripts.created_at DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Transcript Office</title>
    <link rel="stylesheet" href="/assets/styles.css" />
</head>
<body>
<header>
    <div class="logo">A<SUP>+</SUP>SIS</div>
    <nav>
        <a href="/dashboard.php">Dashboard</a>
        <a href="/transcript.php">Transcript</a>
    </nav>
</header>

<main class="page-shell">
    <div class="page-header">
        <div>
            <h1>Transcript Office</h1>
            <div class="badge">Role: Transcript Officer</div>
        </div>
        <button type="button" class="secondary" id="logoutBtn">Sign Out</button>
    </div>

    <?php if ($message !== '') : ?>
        <div class="card"><strong><?php echo htmlspecialchars($message); ?></strong></div>
    <?php endif; ?>

    <section class="card">
        <h2>Generate Transcript</h2>
        <form method="post">
            <input type="hidden" name="action" value="create_transcript" />
            <div class="form-grid">
                <div>
                    <label>Student</label>
                    <select name="student_id" required>
                        <option value="">Select student</option>
                        <?php foreach ($studentOptions as $student) : ?>
                            <option value="<?php echo (int) $student['id']; ?>">
                                <?php echo htmlspecialchars($student['student_no'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Term</label>
                    <input type="text" name="term" value="Term 1" required />
                </div>
                <div>
                    <label>Year</label>
                    <input type="number" name="year" value="2026" required />
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="primary">Generate</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>Transcript Records</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Term</th>
                    <th>Year</th>
                    <th>GPA</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transcripts as $row) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['student_no'] . ' - ' . $row['first_name'] . ' ' . $row['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['term']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['year']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['gpa']); ?></td>
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
