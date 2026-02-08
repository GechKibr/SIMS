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
    $dbPath = $config['sqlite_path'];
    $dbDir = dirname($dbPath);
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0775, true);
    }

    $dsn = 'sqlite:' . $dbPath;

    $pdo = new PDO($dsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');

    return $pdo;
}

function ensure_schema(): void
{
    $pdo = get_pdo();

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL,
            full_name TEXT NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME DEFAULT NULL
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS students (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER UNIQUE,
            student_no TEXT NOT NULL UNIQUE,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            gender TEXT NOT NULL,
            grade_level INTEGER NOT NULL,
            section TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS teachers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER UNIQUE,
            staff_no TEXT NOT NULL UNIQUE,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            department TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS courses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT NOT NULL UNIQUE,
            name TEXT NOT NULL,
            credit INTEGER NOT NULL,
            grade_level INTEGER NOT NULL
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS classes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            course_id INTEGER NOT NULL,
            teacher_id INTEGER NOT NULL,
            term TEXT NOT NULL,
            year INTEGER NOT NULL,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS enrollments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER NOT NULL,
            class_id INTEGER NOT NULL,
            enrolled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(student_id, class_id),
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS grades (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            enrollment_id INTEGER NOT NULL UNIQUE,
            score REAL NOT NULL,
            letter TEXT NOT NULL,
            recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS transcripts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER NOT NULL,
            term TEXT NOT NULL,
            year INTEGER NOT NULL,
            gpa REAL NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(student_id, term, year),
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        )"
    );

    $seedUsers = [
        ['admin', 'admin123', 'system_admin', 'System Administrator'],
        ['student1', 'student123', 'student', 'Student One'],
        ['teacher1', 'teacher123', 'teacher', 'Teacher One'],
        ['registrar', 'registrar123', 'registrar_officer', 'Registrar Officer'],
        ['transcript', 'transcript123', 'transcript_officer', 'Transcript Officer'],
    ];

    $userLookup = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $insertUser = $pdo->prepare(
        'INSERT INTO users (username, password_hash, role, full_name) VALUES (?, ?, ?, ?)'
    );

    foreach ($seedUsers as $seed) {
        [$username, $password, $role, $fullName] = $seed;
        $userLookup->execute([$username]);
        $exists = $userLookup->fetchColumn();
        if (!$exists) {
            $insertUser->execute([
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                $role,
                $fullName,
            ]);
        }
    }

    $userLookup->execute(['student1']);
    $studentUserId = (int) $userLookup->fetchColumn();
    if ($studentUserId > 0) {
        $stmt = $pdo->prepare('SELECT id FROM students WHERE user_id = ? LIMIT 1');
        $stmt->execute([$studentUserId]);
        if (!$stmt->fetchColumn()) {
            $insertStudent = $pdo->prepare(
                'INSERT INTO students (user_id, student_no, first_name, last_name, gender, grade_level, section) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $insertStudent->execute([
                $studentUserId,
                'STU-001',
                'Student',
                'One',
                'Male',
                11,
                'A',
            ]);
        }
    }

    $userLookup->execute(['teacher1']);
    $teacherUserId = (int) $userLookup->fetchColumn();
    if ($teacherUserId > 0) {
        $stmt = $pdo->prepare('SELECT id FROM teachers WHERE user_id = ? LIMIT 1');
        $stmt->execute([$teacherUserId]);
        if (!$stmt->fetchColumn()) {
            $insertTeacher = $pdo->prepare(
                'INSERT INTO teachers (user_id, staff_no, first_name, last_name, department) VALUES (?, ?, ?, ?, ?)'
            );
            $insertTeacher->execute([
                $teacherUserId,
                'TCH-001',
                'Teacher',
                'One',
                'Mathematics',
            ]);
        }
    }

    $courseLookup = $pdo->prepare('SELECT id FROM courses WHERE code = ? LIMIT 1');
    $courseLookup->execute(['MATH101']);
    $courseId = $courseLookup->fetchColumn();
    if (!$courseId) {
        $insertCourse = $pdo->prepare('INSERT INTO courses (code, name, credit, grade_level) VALUES (?, ?, ?, ?)');
        $insertCourse->execute(['MATH101', 'Mathematics I', 3, 11]);
        $courseId = $pdo->lastInsertId();
    }

    $teacherId = $pdo->prepare('SELECT id FROM teachers WHERE staff_no = ? LIMIT 1');
    $teacherId->execute(['TCH-001']);
    $teacherIdValue = $teacherId->fetchColumn();

    if ($courseId && $teacherIdValue) {
        $classLookup = $pdo->prepare(
            'SELECT id FROM classes WHERE course_id = ? AND teacher_id = ? AND term = ? AND year = ? LIMIT 1'
        );
        $classLookup->execute([$courseId, $teacherIdValue, 'Term 1', 2026]);
        $classId = $classLookup->fetchColumn();
        if (!$classId) {
            $insertClass = $pdo->prepare(
                'INSERT INTO classes (course_id, teacher_id, term, year) VALUES (?, ?, ?, ?)'
            );
            $insertClass->execute([$courseId, $teacherIdValue, 'Term 1', 2026]);
            $classId = $pdo->lastInsertId();
        }

        $studentId = $pdo->prepare('SELECT id FROM students WHERE student_no = ? LIMIT 1');
        $studentId->execute(['STU-001']);
        $studentIdValue = $studentId->fetchColumn();

        if ($studentIdValue) {
            $enrollmentLookup = $pdo->prepare(
                'SELECT id FROM enrollments WHERE student_id = ? AND class_id = ? LIMIT 1'
            );
            $enrollmentLookup->execute([$studentIdValue, $classId]);
            $enrollmentId = $enrollmentLookup->fetchColumn();
            if (!$enrollmentId) {
                $insertEnrollment = $pdo->prepare(
                    'INSERT INTO enrollments (student_id, class_id) VALUES (?, ?)'
                );
                $insertEnrollment->execute([$studentIdValue, $classId]);
                $enrollmentId = $pdo->lastInsertId();
            }

            if ($enrollmentId) {
                $gradeLookup = $pdo->prepare('SELECT id FROM grades WHERE enrollment_id = ? LIMIT 1');
                $gradeLookup->execute([$enrollmentId]);
                if (!$gradeLookup->fetchColumn()) {
                    $insertGrade = $pdo->prepare(
                        'INSERT INTO grades (enrollment_id, score, letter) VALUES (?, ?, ?)'
                    );
                    $insertGrade->execute([$enrollmentId, 88, 'B+']);
                }
            }
        }
    }
}

function get_user_by_id(int $userId): ?array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT id, username, role, full_name FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    return $user ?: null;
}
