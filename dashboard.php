<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Login check
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Back button security
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html>
<head>
    <title>School Dashboard</title>
    <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #1c0b14; text-align: center; margin: 0; color: #f4f4f4; }
    .header { background-color: #4b001f; color: #f4f4f4; padding: 20px; }
    .container { display: flex; justify-content: center; gap: 20px; padding: 50px; flex-wrap: wrap; }
    .card { background: #2c001a; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); padding: 25px; width: 220px; text-decoration: none; color: #f4f4f4; transition: 0.3s; border-top: 5px solid #ff4b6e; }
    .card:hover { transform: translateY(-5px); background-color: #ff4b6e; color: white; }
    .logout { display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #99001a; color: white; text-decoration: none; border-radius: 5px; }
    .logout:hover { background-color: #ff4b6e; color: white; }
</style>
</head>
<body>
    <div id="notifications" style="position:fixed; top:20px; right:20px; z-index:999;"></div>
    <div class="header">
        <h1>
School Monitoring System (Class 1-10)
<span id="notifCount" style="background:red; padding:5px 10px; border-radius:50%; font-size:14px;">0</span>
</h1>
        <p>Welcome, <b><?php echo htmlspecialchars($_SESSION['user']); ?></b></p>
    </div>

    <div class="container">
    <a href="add_student.php" class="card">
        <h3>➕ Add Student</h3>
        <p>Register new students to the system</p>
    </a>

    <a href="subject_allocation.php" class="card">
        <h3>📚 Subject Allocation</h3>
        <p>Assign subjects to classes</p>
    </a>

    <a href="attendance.php" class="card">
        <h3>📅 Daily Attendance</h3>
        <p>Mark student attendance</p>
    </a>

    <a href="manage_marks.php" class="card">
        <h3>📝 Manage Marks</h3>
        <p>Add and edit student marks</p>
    </a>

    <a href="view_results.php" class="card">
        <h3>📊 Academic Marks</h3>
        <p>View student results</p>
    </a>

    <a href="behaviour_assessment.php" class="card">
        <h3>⭐ Behaviour Records</h3>
        <p>Track student behaviour</p>
    </a>

    <a href="teacher_view.php" class="card">
        <h3>🎯 Student Performance</h3>
        <p>Complete view: marks + attendance + behaviour</p>
    </a>

    <a href="feedback.php" class="card">
        <h3>💬 Feedback System</h3>
        <p>Send messages to parents and students</p>
    </a>

    <a href="analysis.php" class="card">
        <h3>📈 View Analysis</h3>
        <p>Analytics and reports</p>
    </a>
</div>

    <a href="logout.php" class="logout">Logout</a>
    <script>
function loadNotifications() {
    fetch('get_notifications.php')
    .then(response => response.json())
    .then(data => {
        let box = document.getElementById("notifications");
        box.innerHTML = "";

        // Update badge number
        document.getElementById("notifCount").innerText = data.length;

        data.forEach(n => {
            let div = document.createElement("div");
            div.style.background = "#ff4b6e";
            div.style.color = "white";
            div.style.padding = "10px";
            div.style.margin = "5px";
            div.style.borderRadius = "5px";
            div.innerText = n.message;

            box.appendChild(div);
        });

        //  MARK AS READ (THIS IS WHERE YOU ADD IT)
        fetch('mark_read.php');
    });
}

// run every 3 seconds
setInterval(loadNotifications, 3000);
</script>
</body>
</html>