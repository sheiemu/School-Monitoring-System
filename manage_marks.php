<?php
session_start();
require_once 'db_config.php';

// Security check: Login chara keu dhukte parbe na
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$msg = "";
// Form submit hole data SQL Server-e jabe
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sid = $_POST['student_id'];
    $subid = $_POST['subject_id'];
    $marks = $_POST['marks'];
    $term = $_POST['exam_term'];

    // Marks table-e data insert kora (PDF Requirement 3.3)
    $sql = "INSERT INTO Marks (student_id, subject_id, marks_obtained, exam_type) VALUES (?, ?, ?, ?)";
    $params = array($sid, $subid, $marks, $term);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) { 
        $msg = "<p style='color:green; font-weight:bold;'>Success: Marks recorded for Student ID $sid!</p>"; 
    } else { 
        $msg = "<p style='color:red; font-weight:bold;'>Error! Please check if Student ID exists.</p>";
    }
}

// Subject dropdown-er jonno data fetch kora
$subjects = sqlsrv_query($conn, "SELECT * FROM Subject");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Academic Marks Entry</title>
    <style>
        body { font-family: 'Segoe UI', Arial; background: #f4f7f6; text-align: center; margin: 0; }
        .box { background: white; width: 400px; margin: 50px auto; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); border-top: 5px solid #3498db; }
        select, input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-size: 15px; }
        button { background: #3498db; color: white; border: none; padding: 12px; width: 100%; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold; transition: 0.3s; }
        button:hover { background: #2980b9; }
        .back { display: block; margin-top: 20px; color: #7f8c8d; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>
    <div class="box">
        <h2 style="color: #2c3e50;">Academic Marks Entry</h2>
        <?php echo $msg; ?>
        <form method="POST">
            <input type="number" name="student_id" placeholder="Enter Student ID (e.g. 1)" required>
            
            <select name="subject_id" required>
                <option value="">-- Select Subject --</option>
                <?php while($row = sqlsrv_fetch_array($subjects, SQLSRV_FETCH_ASSOC)): ?>
                    <option value="<?php echo $row['subject_id']; ?>"><?php echo $row['subject_name']; ?></option>
                <?php endwhile; ?>
            </select>

            <input type="number" name="marks" placeholder="Marks Obtained (0-100)" min="0" max="100" step="0.01" required>
            
            <select name="exam_term" required>
                <option value="Midterm">Midterm</option>
                <option value="Final">Final</option>
            </select>

            <button type="submit">SAVE TO DATABASE</button>
        </form>
        <a href="dashboard.php" class="back">← Back to Dashboard</a>
    </div>
</body>
</html>