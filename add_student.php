<?php
session_start();
require_once 'db_config.php';

$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sid = $_POST['student_id'];
    $name = $_POST['name'];
    $class = $_POST['class_id'];
    $gender = $_POST['gender']; // Notun field
    $dob = $_POST['dob'];       // Notun field

    // Sob data SQL Server-e pathano hochhe
    $sql = "INSERT INTO Student (student_id, name, class_id, gender, date_of_birth) VALUES (?, ?, ?, ?, ?)";
    $params = array($sid, $name, $class, $gender, $dob);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        $msg = "<p style='color:green;'>Student $name added successfully!</p>";
    } else {
        $msg = "<p style='color:red;'>Error! Check if ID already exists or Data format is wrong.</p>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add New Student</title>
    <style>
        body { font-family: Arial; background: #f4f7f6; text-align: center; }
        .box { width: 350px; margin: 50px auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-top: 5px solid #2ecc71; }
        input, select { width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #2ecc71; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Register Student</h2>
        <?php echo $msg; ?>
        <form method="POST">
            <input type="number" name="student_id" placeholder="Student ID" required>
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="number" name="class_id" placeholder="Class (1-10)" required>
            
            <select name="gender" required>
                <option value="">-- Select Gender --</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>

            <label style="display:block; text-align:left; font-size:12px; color:#666;">Date of Birth:</label>
            <input type="date" name="dob" required>

            <button type="submit">SAVE STUDENT</button>
        </form>
        <br><a href="dashboard.php" style="color:#7f8c8d; text-decoration:none; font-size:14px;">← Back</a>
    </div>
</body>
</html>