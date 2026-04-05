<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$msg = "";
$student = null;

// Get student if ID provided
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = sqlsrv_query($conn, "SELECT * FROM Student WHERE student_id = ?", array($id));
    $student = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
}

// Update student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $id = $_POST['student_id'];
    $name = $_POST['name'];
    $class_id = $_POST['class_id'];
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    
    $sql = "UPDATE Student SET name=?, class_id=?, gender=?, date_of_birth=? WHERE student_id=?";
    $params = array($name, $class_id, $gender, $dob, $id);
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt) {
        $msg = "<p style='color:green;'>Student updated successfully!</p>";
        // Refresh student data
        $stmt = sqlsrv_query($conn, "SELECT * FROM Student WHERE student_id = ?", array($id));
        $student = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    } else {
        $msg = "<p style='color:red;'>Error updating student!</p>";
    }
}

// Get all classes for dropdown
$classes = sqlsrv_query($conn, "SELECT * FROM Class ORDER BY class_id");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Student</title>
    <style>
        body { font-family: Arial; background: #1c0b14; text-align: center; margin: 0; color: #f4f4f4; }
        .box { width: 400px; margin: 50px auto; background: #2c001a; padding: 25px; border-radius: 12px; }
        input, select { width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #660026; border-radius: 6px; background: #3d0023; color: #f4f4f4; }
        button { width: 100%; padding: 12px; background: #ff4b6e; color: white; border: none; border-radius: 6px; cursor: pointer; }
        button:hover { background: #99001a; }
        .btn { display: inline-block; padding: 10px 20px; background: #ff4b6e; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>✏️ Edit Student</h2>
        <?= $msg ?>
        
        <?php if(!$student && !isset($_GET['id'])): ?>
            <form method="GET">
                <input type="number" name="id" placeholder="Enter Student ID" required>
                <button type="submit">Search Student</button>
            </form>
        <?php elseif($student): ?>
            <form method="POST">
                <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                <input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" required>
                <select name="class_id" required>
                    <?php while($c = sqlsrv_fetch_array($classes, SQLSRV_FETCH_ASSOC)): ?>
                        <option value="<?= $c['class_id'] ?>" <?= $c['class_id'] == $student['class_id'] ? 'selected' : '' ?>>
                            <?= $c['class_name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <select name="gender">
                    <option value="Male" <?= $student['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= $student['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                    <option value="Other" <?= $student['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
                <input type="date" name="dob" value="<?= $student['date_of_birth'] ? $student['date_of_birth']->format('Y-m-d') : '' ?>">
                <button type="submit" name="update">Update Student</button>
            </form>
        <?php else: ?>
            <p style="color:red;">Student not found!</p>
        <?php endif; ?>
        
        <a href="teacher_dashboard.php" class="btn">← Back to Dashboard</a>
    </div>
</body>
</html>