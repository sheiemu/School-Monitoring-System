<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get all students with their class names
$students = sqlsrv_query($conn, "
    SELECT s.*, c.class_name 
    FROM Student s
    JOIN Class c ON s.class_id = c.class_id
    ORDER BY s.class_id, s.name
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>All Students</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #1c0b14; color: #f4f4f4; margin: 0; }
        .header { background: #4b001f; padding: 20px; text-align: center; }
        .header h1 { color: #ff4b6e; margin: 0; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        table { width: 100%; border-collapse: collapse; background: #2c001a; border-radius: 12px; overflow: hidden; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #3d0023; }
        th { background: #3d0023; color: #ff4b6e; }
        .btn { display: inline-block; padding: 8px 15px; background: #ff4b6e; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .btn:hover { background: #99001a; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 All Students</h1>
    </div>
    <div class="container">
        <a href="teacher_dashboard.php" class="btn">← Back to Dashboard</a>
        
        <table>
            <thead>
                <tr><th>ID</th><th>Name</th><th>Class</th><th>Gender</th><th>DOB</th><th>Action</th></thead>
            </thead>
            <tbody>
                <?php while($s = sqlsrv_fetch_array($students, SQLSRV_FETCH_ASSOC)): ?>
                <tr>
                    <td><?= $s['student_id'] ?></td>
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= $s['class_name'] ?></td>
                    <td><?= $s['gender'] ?? 'N/A' ?></td>
                    <td><?= $s['date_of_birth'] ? $s['date_of_birth']->format('Y-m-d') : 'N/A' ?></td>
                    <td><a href="student_detail.php?id=<?= $s['student_id'] ?>" style="color:#ff4b6e;">View Details</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>