<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Get student info
$stmt = sqlsrv_query($conn, "
    SELECT s.*, c.class_name 
    FROM Student s
    JOIN Class c ON s.class_id = c.class_id
    WHERE s.student_id = ?
", array($student_id));
$student = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$student) {
    header("Location: view_all_students.php");
    exit();
}

// Get marks
$marks = sqlsrv_query($conn, "
    SELECT sub.subject_name, m.marks_obtained, m.exam_type
    FROM Marks m
    JOIN Subject sub ON m.subject_id = sub.subject_id
    WHERE m.student_id = ?
    ORDER BY m.exam_type, sub.subject_name
", array($student_id));

// Get attendance
$attendance = sqlsrv_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present
    FROM Attendance
    WHERE student_id = ?
", array($student_id));
$att_row = sqlsrv_fetch_array($attendance, SQLSRV_FETCH_ASSOC);
$att_pct = $att_row['total'] > 0 ? round(($att_row['present'] / $att_row['total']) * 100, 2) : 0;

// Get behaviour
$behaviour = sqlsrv_query($conn, "
    SELECT description, score, behaviour_date
    FROM Behaviour
    WHERE student_id = ?
    ORDER BY behaviour_date DESC
", array($student_id));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Details</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #1c0b14; color: #f4f4f4; margin: 0; }
        .header { background: #4b001f; padding: 20px; text-align: center; }
        .header h1 { color: #ff4b6e; margin: 0; }
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .card { background: #2c001a; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        .card h2 { color: #ff4b6e; margin-bottom: 20px; border-left: 4px solid #ff4b6e; padding-left: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #3d0023; }
        th { background: #3d0023; color: #ff4b6e; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .info-item { background: #3d0023; padding: 15px; border-radius: 8px; }
        .btn { display: inline-block; padding: 10px 20px; background: #ff4b6e; color: white; text-decoration: none; border-radius: 6px; margin-top: 10px; }
        .btn:hover { background: #99001a; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Student Details</h1>
    </div>
    <div class="container">
        <div class="card">
            <h2>👤 Personal Information</h2>
            <div class="info-grid">
                <div class="info-item"><strong>Name:</strong> <?= htmlspecialchars($student['name']) ?></div>
                <div class="info-item"><strong>Class:</strong> <?= $student['class_name'] ?></div>
                <div class="info-item"><strong>Gender:</strong> <?= $student['gender'] ?? 'N/A' ?></div>
                <div class="info-item"><strong>DOB:</strong> <?= $student['date_of_birth'] ? $student['date_of_birth']->format('Y-m-d') : 'N/A' ?></div>
                <div class="info-item"><strong>Attendance:</strong> <?= $att_pct ?>%</div>
            </div>
        </div>
        
        <div class="card">
            <h2>📖 Academic Results</h2>
            <table>
                <thead><tr><th>Subject</th><th>Exam Type</th><th>Marks</th></tr></thead>
                <tbody>
                    <?php while($m = sqlsrv_fetch_array($marks, SQLSRV_FETCH_ASSOC)): ?>
                    <tr><td><?= $m['subject_name'] ?></td><td><?= $m['exam_type'] ?></td><td><?= $m['marks_obtained'] ?></td></tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div class="card">
            <h2>⭐ Behaviour Records</h2>
            <table>
                <thead><tr><th>Date</th><th>Description</th><th>Score</th></tr></thead>
                <tbody>
                    <?php while($b = sqlsrv_fetch_array($behaviour, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td><?= $b['behaviour_date']->format('Y-m-d') ?></td>
                        <td><?= htmlspecialchars($b['description']) ?></td>
                        <td><?= $b['score'] ?: 'N/A' ?>/5</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <a href="view_all_students.php" class="btn">← Back to All Students</a>
        <a href="edit_student.php?id=<?= $student_id ?>" class="btn" style="background:#2196f3;">✏️ Edit Student</a>
        <a href="teacher_dashboard.php" class="btn" style="background:#4caf50;">📊 Dashboard</a>
    </div>
</body>
</html>