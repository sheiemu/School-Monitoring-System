<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_marks'])) {
    $sid = $_POST['student_id'];
    $cid = $_POST['class_id'];
    $subject = $_POST['subject_name'];
    $marks = $_POST['marks_obtained'];
    $exam = $_POST['exam_type'];

    // Data insert query
    $sql = "INSERT INTO Marks (student_id, class_id, subject_name, marks_obtained, exam_type) VALUES (?, ?, ?, ?, ?)";
    $params = array($sid, $cid, $subject, $marks, $exam);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        $message = "<div style='color: #27ae60; margin-bottom: 15px;'>✔ Marks saved successfully!</div>";
    } else {
        $message = "<div style='color: #e74c3c; margin-bottom: 15px;'>✖ Error saving marks. Check Student ID.</div>";
    }
}
//
// Subject list fetch kora
$subjects_query = "SELECT subject_name FROM Subject";
$subjects_stmt = sqlsrv_query($conn, $subjects_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Academic Marks Entry</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 30px; border-radius: 12px; shadow: 0 4px 15px rgba(0,0,0,0.1); width: 400px; text-align: center; border-top: 5px solid #3498db; }
        input, select { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .btn-save { background: #3498db; color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 16px; }
        .btn-save:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="card">
        <h2 style="color: #2c3e50;">Academic Marks Entry</h2>
        <?php echo $message; ?>
        <form method="POST">
            <input type="number" name="student_id" placeholder="Enter Student ID (e.g. 1)" required>
            
            <select name="class_id" required>
                <option value="">-- Select Class --</option>
                <?php for($i=6; $i<=10; $i++): ?>
                    <option value="<?php echo $i; ?>">Class <?php echo $i; ?></option>
                <?php endfor; ?>
            </select>

            <select name="subject_name" required>
                <option value="">-- Select Subject --</option>
                <?php while($row = sqlsrv_fetch_array($subjects_stmt, SQLSRV_FETCH_ASSOC)): ?>
                    <option value="<?php echo $row['subject_name']; ?>"><?php echo $row['subject_name']; ?></option>
                <?php endwhile; ?>
            </select>

            <input type="number" step="0.01" name="marks_obtained" placeholder="Marks Obtained (0-100)" required>

            <select name="exam_type" required>
                <option value="Midterm">Midterm</option>
                <option value="Final">Final</option>
            </select>

            <button type="submit" name="save_marks" class="btn-save">SAVE TO DATABASE</button>
        </form>
        <p><a href="dashboard.php" style="color: #7f8c8d; text-decoration: none; font-size: 14px;">← Back to Dashboard</a></p>
    </div>
</body>
</html>