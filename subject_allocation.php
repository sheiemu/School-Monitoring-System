<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$msg = "";
$classes = sqlsrv_query($conn, "SELECT * FROM Class");
if (!$classes) { die(print_r(sqlsrv_errors(), true)); }

$subjects = sqlsrv_query($conn, "SELECT * FROM Subject");
if (!$subjects) { die(print_r(sqlsrv_errors(), true)); }
$subjects = sqlsrv_query($conn, "SELECT * FROM Subject");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_id = $_POST['class_id'];
    $subject_id = $_POST['subject_id'];
    
    // Save to a new table (you can create Class_Subject table)
    $sql = "INSERT INTO Class_Subject (class_id, subject_id) VALUES (?, ?)";
    $stmt = sqlsrv_query($conn, $sql, array($class_id, $subject_id));
    $msg = $stmt ? "Subject assigned successfully!" : "Error assigning subject.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Subject Allocation</title>
    <style>
        body { background-color: #1c0b14; color: #f4f4f4; font-family: Arial; text-align:center; padding-top:50px; }
        select, button { padding:10px; margin:10px; border-radius:5px; border:none; }
        button { background-color:#4b001f; color:white; cursor:pointer; }
        button:hover { background-color:#ff4b6e; }
    </style>
</head>
<body>
    <h2>Subject Allocation</h2>
    <?php if($msg) echo "<p style='color:lime;'>$msg</p>"; ?>
    <form method="POST">
        <select name="class_id" required>
            <option value="">-- Select Class --</option>
            <?php while($c = sqlsrv_fetch_array($classes, SQLSRV_FETCH_ASSOC)): ?>
                <option value="<?= $c['class_id'] ?>"><?= $c['class_name'] ?></option>
            <?php endwhile; ?>
        </select>

        <select name="subject_id" required>
            <option value="">-- Select Subject --</option>
            <?php while($s = sqlsrv_fetch_array($subjects, SQLSRV_FETCH_ASSOC)): ?>
                <option value="<?= $s['subject_id'] ?>"><?= $s['subject_name'] ?></option>
            <?php endwhile; ?>
        </select>

        <button type="submit">Assign</button>
    </form>
    <br><a href="dashboard.php" style="color:#ff4b6e;">← Back to Dashboard</a>
</body>
</html>