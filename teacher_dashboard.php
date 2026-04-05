<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Teacher Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #1c0b14; color: #f4f4f4; }
        .header { background: #4b001f; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .header h1 { color: #ff4b6e; }
        .container { max-width: 1300px; margin: 30px auto; padding: 0 20px; }
        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 30px; }
        .card { background: #2c001a; border-radius: 12px; padding: 25px; text-decoration: none; color: #f4f4f4; transition: 0.3s; border-top: 4px solid #ff4b6e; display: block; }
        .card:hover { transform: translateY(-5px); background: #3d0023; }
        .card h3 { color: #ff4b6e; margin-bottom: 10px; font-size: 20px; }
        .card p { color: #888; font-size: 14px; }
        .logout-btn { background: #99001a; color: white; padding: 8px 20px; border-radius: 6px; text-decoration: none; }
        .logout-btn:hover { background: #ff4b6e; }
        .section-title { margin: 30px 0 20px; color: #ff4b6e; border-left: 4px solid #ff4b6e; padding-left: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>👨‍🏫 Teacher Dashboard</h1>
        <div>
            <span style="margin-right: 15px;">Welcome, <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <h2 class="section-title">📋 Student Management</h2>
        <div class="card-grid">
            <a href="add_student.php" class="card"><h3>➕ Add Student</h3><p>Register new students to the system</p></a>
            <a href="view_all_students.php" class="card"><h3>📊 View All Students</h3><p>See complete student profiles</p></a>
            <a href="edit_student.php" class="card"><h3>✏️ Edit Student Info</h3><p>Update student details</p></a>
        </div>
        
        <h2 class="section-title">📝 Academic Records</h2>
        <div class="card-grid">
            <a href="manage_marks.php" class="card"><h3>📝 Enter/Edit Marks</h3><p>Add or modify student exam marks</p></a>
            <a href="view_results.php" class="card"><h3>📖 View Results</h3><p>See academic performance</p></a>
        </div>
        
        <h2 class="section-title">📅 Attendance & Behaviour</h2>
        <div class="card-grid">
            <a href="attendance.php" class="card"><h3>✅ Mark Attendance</h3><p>Record daily student attendance</p></a>
            <a href="behaviour.php" class="card"><h3>⭐ Behaviour Records</h3><p>Track student behaviour (1-5 scale)</p></a>
        </div>
        <h2 class="section-title">💬 Communication</h2>
<div class="card-grid">
    <a href="feedback.php" class="card"><h3>💬 Send Feedback to Parents</h3><p>Message parents about student progress</p></a>
    <a href="parent_messages.php" class="card"><h3>📨 Parent Messages</h3><p>View and reply to messages from parents</p></a>
    <a href="analysis.php" class="card"><h3>📈 View Analysis</h3><p>Academic and behaviour analytics</p></a>
</div>
        
    </div>
</body>
</html>