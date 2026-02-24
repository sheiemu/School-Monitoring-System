<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Login check
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Back button security (Cache control)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html>
<head>
    <title>School Dashboard</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f7f6; text-align: center; margin: 0; }
        .header { background-color: #2c3e50; color: white; padding: 20px; }
        .container { display: flex; justify-content: center; gap: 20px; padding: 50px; flex-wrap: wrap; }
        .card { background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); padding: 25px; width: 220px; text-decoration: none; color: #333; transition: 0.3s; border-top: 5px solid #3498db; }
        .card:hover { transform: translateY(-5px); background-color: #3498db; color: white; }
        .logout { display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #e74c3c; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>School Monitoring System (Class 1-10)</h1>
        <p>Welcome, <b><?php echo htmlspecialchars($_SESSION['user']); ?></b></p>
    </div>

    <div class="container">
        <a href="subject_allocation.php" class="card"><h3>Subject Allocation</h3></a>
        <a href="attendance.php" class="card"><h3>Daily Attendance</h3></a>
        <a href="manage_marks.php" class="card"><h3>Academic Marks</h3></a>
        <a href="behaviour_assessment.php" class="card"><h3>Behaviour Records</h3></a>
    </div>

    <a href="logout.php" class="logout">Logout</a>
</body>
</html>