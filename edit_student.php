<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$msg = "";
$student = null;
$student_id = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['student_id']) ? $_POST['student_id'] : null);

// Get student if ID provided
if ($student_id) {
    $stmt = sqlsrv_query($conn, "SELECT * FROM Student WHERE student_id = ?", array($student_id));
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
        $msg = "<p style='color:green;'>✓ Student updated successfully!</p>";
        // Refresh student data
        $stmt = sqlsrv_query($conn, "SELECT * FROM Student WHERE student_id = ?", array($id));
        $student = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    } else {
        $msg = "<p style='color:red;'>✗ Error updating student!</p>";
    }
}

// Delete student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    $id = $_POST['student_id'];
    $sql = "DELETE FROM Student WHERE student_id = ?";
    $stmt = sqlsrv_query($conn, $sql, array($id));
    
    if ($stmt) {
        header("Location: view_all_students.php?deleted=1");
        exit();
    } else {
        $msg = "<p style='color:red;'>✗ Error deleting student!</p>";
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
        body { font-family: Arial; background: #1c0b14; margin: 0; color: #f4f4f4; }
        .header { background: #4b001f; padding: 20px; text-align: center; }
        .header h1 { color: #ff4b6e; margin: 0; }
        .box { width: 450px; margin: 50px auto; background: #2c001a; padding: 30px; border-radius: 12px; }
        input, select { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #660026; border-radius: 6px; background: #3d0023; color: #f4f4f4; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #ff4b6e; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        button:hover { background: #99001a; }
        .btn { display: inline-block; padding: 10px 20px; background: #ff4b6e; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; }
        .btn-danger { background: #f44336; }
        .btn-danger:hover { background: #b71c1c; }
        .search-box { margin-bottom: 30px; }
        hr { border-color: #3d0023; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>✏️ Edit Student Information</h1>
    </div>
    <div class="box">
        <?= $msg ?>
        
        <?php if(!$student && !$student_id): ?>
            <div class="search-box">
                <h3>Search Student</h3>
                <form method="GET">
                    <input type="number" name="id" placeholder="Enter Student ID" required>
                    <button type="submit">Search</button>
                </form>
            </div>
            <hr>
            <div class="search-box">
                <h3>Or View All Students</h3>
                <a href="view_all_students.php" class="btn">View All Students →</a>
            </div>
        <?php elseif($student): ?>
            <h3>Editing: <?= htmlspecialchars($student['name']) ?></h3>
            <form method="POST">
                <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                
                <label>Student Name:</label>
                <input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" required>
                
                <label>Class:</label>
                <select name="class_id" required>
                    <?php while($c = sqlsrv_fetch_array($classes, SQLSRV_FETCH_ASSOC)): ?>
                        <option value="<?= $c['class_id'] ?>" <?= $c['class_id'] == $student['class_id'] ? 'selected' : '' ?>>
                            <?= $c['class_name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                
                <label>Gender:</label>
                <select name="gender">
                    <option value="Male" <?= $student['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= $student['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                    <option value="Other" <?= $student['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
                
                <label>Date of Birth:</label>
                <input type="date" name="dob" value="<?= $student['date_of_birth'] ? $student['date_of_birth']->format('Y-m-d') : '' ?>">
                
                <button type="submit" name="update">Update Student</button>
                <button type="submit" name="delete" class="btn-danger" onclick="return confirm('Are you sure you want to delete this student?')">Delete Student</button>
            </form>
        <?php else: ?>
            <p style="color:red; text-align:center;">Student not found! Please check the ID.</p>
            <a href="edit_student.php" class="btn">← Try Again</a>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="teacher_dashboard.php" style="color: #ff4b6e;">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>