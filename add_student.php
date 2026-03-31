<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$msg = "";
$classes = sqlsrv_query($conn, "SELECT * FROM Class ORDER BY class_id");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $name = $_POST['name'];
    $class_id = $_POST['class_id'];
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    
    // First check if student ID already exists
    $check_sql = "SELECT * FROM Student WHERE student_id = ?";
    $check_stmt = sqlsrv_query($conn, $check_sql, array($student_id));
    
    if (sqlsrv_has_rows($check_stmt)) {
        $msg = "<p style='color:red;'>✗ Student ID $student_id already exists! Please use a different ID.</p>";
    } else {
        // Enable IDENTITY_INSERT to allow manual ID insertion
        sqlsrv_query($conn, "SET IDENTITY_INSERT Student ON");
        
        // Insert with manual ID
        $sql = "INSERT INTO Student (student_id, name, class_id, gender, date_of_birth) VALUES (?, ?, ?, ?, ?)";
        $params = array($student_id, $name, $class_id, $gender, $dob);
        $stmt = sqlsrv_query($conn, $sql, $params);
        
        // Turn IDENTITY_INSERT off
        sqlsrv_query($conn, "SET IDENTITY_INSERT Student OFF");
        
        if ($stmt) {
            $msg = "<p style='color:green;'>✓ Student $name added successfully with ID: $student_id!</p>";
        } else {
            $msg = "<p style='color:red;'>✗ Error adding student! " . print_r(sqlsrv_errors(), true) . "</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add New Student</title>
    <style>
        body { font-family: Arial; background: #1c0b14; text-align: center; margin: 0; color: #f4f4f4; }
        .box { width: 450px; margin: 50px auto; background: #2c001a; padding: 30px; border-radius: 12px; }
        input, select { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #660026; border-radius: 6px; background: #3d0023; color: #f4f4f4; font-size: 14px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #ff4b6e; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }
        button:hover { background: #99001a; }
        .btn { display: inline-block; padding: 10px 20px; background: #4caf50; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; }
        .btn:hover { background: #45a049; }
        h2 { color: #ff4b6e; margin-bottom: 20px; }
        .note { background: #3d0023; padding: 10px; border-radius: 6px; margin-top: 15px; font-size: 12px; color: #888; }
        .id-input { font-size: 18px; font-weight: bold; }
        .error { color: #f44336; }
        .success { color: #4caf50; }
    </style>
</head>
<body>
    <div class="box">
        <h2>📝 Register New Student</h2>
        <?= $msg ?>
        <form method="POST">
            <div class="form-group">
                <label>Student ID:</label>
                <input type="number" name="student_id" placeholder="Enter Student ID (e.g., 101, 102)" class="id-input" required>
            </div>
            
            <div class="form-group">
                <label>Full Name:</label>
                <input type="text" name="name" placeholder="Enter Full Name" required>
            </div>
            
            <div class="form-group">
                <label>Select Class:</label>
                <select name="class_id" required>
                    <option value="">-- Select Class --</option>
                    <?php while($c = sqlsrv_fetch_array($classes, SQLSRV_FETCH_ASSOC)): ?>
                        <option value="<?= $c['class_id'] ?>"><?= $c['class_name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Gender:</label>
                <select name="gender" required>
                    <option value="">-- Select Gender --</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Date of Birth:</label>
                <input type="date" name="dob" required>
            </div>
            
            <button type="submit">💾 SAVE STUDENT</button>
        </form>
        <div class="note">
            💡 Tip: Student ID should be unique. Suggested format: Class Number + Roll Number<br>
            Example: Class 1 Roll 01 = 101, Class 2 Roll 15 = 215, Class 10 Roll 30 = 1030
        </div>
        <a href="teacher_dashboard.php" class="btn">← Back to Dashboard</a>
    </div>
</body>
</html>