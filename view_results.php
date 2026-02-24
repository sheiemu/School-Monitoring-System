<?php
error_reporting(E_ALL);
ini_set('display_errors', 1); // Blank page fix korar jonno error dekhabe

require_once 'db_config.php';

$student_info = null;
$midterm_marks = [];
$final_marks = [];
$error_msg = "";

if (isset($_GET['search'])) {
    $sid = $_GET['student_id'];

    // Step 1: Fetch Student Basic Info
    $s_sql = "SELECT * FROM Student WHERE student_id = ?";
    $params = array($sid);
    $s_stmt = sqlsrv_query($conn, $s_sql, $params);

    if ($s_stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $student_info = sqlsrv_fetch_array($s_stmt, SQLSRV_FETCH_ASSOC);

    if ($student_info) {
        // Step 2: Fetch Midterm Marks
        $mid_sql = "SELECT sub.subject_name, m.marks_obtained 
                    FROM Marks m 
                    JOIN Subject sub ON m.subject_id = sub.subject_id 
                    WHERE m.student_id = ? AND m.exam_type = 'Midterm'";
        $mid_stmt = sqlsrv_query($conn, $mid_sql, array($sid));
        while ($row = sqlsrv_fetch_array($mid_stmt, SQLSRV_FETCH_ASSOC)) { 
            $midterm_marks[] = $row; 
        }

        // Step 3: Fetch Final Marks
        $fin_sql = "SELECT sub.subject_name, m.marks_obtained 
                    FROM Marks m 
                    JOIN Subject sub ON m.subject_id = sub.subject_id 
                    WHERE m.student_id = ? AND m.exam_type = 'Final'";
        $fin_stmt = sqlsrv_query($conn, $fin_sql, array($sid));
        while ($row = sqlsrv_fetch_array($fin_stmt, SQLSRV_FETCH_ASSOC)) { 
            $final_marks[] = $row; 
        }
    } else {
        $error_msg = "Student ID $sid not found!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Academic Report</title>
    <style>
        body { font-family: Arial; background: #f4f7f6; padding: 20px; text-align: center; }
        .container { background: white; max-width: 600px; margin: auto; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; }
        th { background: #3498db; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Search Student Result</h2>
        <form method="GET">
            <input type="number" name="student_id" placeholder="Enter Student ID" required>
            <button type="submit" name="search">View Report</button>
        </form>

        <?php if ($error_msg) echo "<p style='color:red;'>$error_msg</p>"; ?>

        <?php if ($student_info): ?>
            <h3>Report for: <?php echo htmlspecialchars($student_info['name']); ?></h3>
            <table>
                <tr><th>Subject</th><th>Marks</th></tr>
                <?php foreach ($midterm_marks as $m): ?>
                    <tr><td><?php echo $m['subject_name']; ?></td><td><?php echo $m['marks_obtained']; ?></td></tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
        <br><a href="dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>