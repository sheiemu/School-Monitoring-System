<?php
session_start();
require_once 'db_config.php';

// Security check
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$student_info = null;
$marks_data = [];
$search_performed = false;

if (isset($_GET['search'])) {
    $search_performed = true;
    $sid = $_GET['student_id'];
    $cid = $_GET['class_id'];
    $exam = $_GET['exam_type'];

    // 1. Student Info check kora
    $sql_student = "SELECT * FROM Student WHERE student_id = ? AND class_id = ?";
    $params_student = array($sid, $cid);
    $stmt_student = sqlsrv_query($conn, $sql_student, $params_student);
    $student_info = sqlsrv_fetch_array($stmt_student, SQLSRV_FETCH_ASSOC);

    if ($student_info) {
        // 2. Marks fetch kora - SERIAL: Student ID, Exam Type, Class ID
        // RTRIM use kora hoyeche jate string matching-e space error na hoy
        $sql_marks = "SELECT s.subject_name, m.marks_obtained 
                      FROM Subject s
                      LEFT JOIN Marks m ON RTRIM(s.subject_name) = RTRIM(m.subject_name) 
                      AND m.student_id = ? 
                      AND m.exam_type = ? 
                      AND m.class_id = ?";
        
        $params_marks = array($sid, $exam, $cid); 
        $stmt_marks = sqlsrv_query($conn, $sql_marks, $params_marks);

        if ($stmt_marks) {
            while ($row = sqlsrv_fetch_array($stmt_marks, SQLSRV_FETCH_ASSOC)) {
                $marks_data[] = $row;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Academic Progress Report</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; margin: 0; padding: 20px; }
        .search-container { background: white; max-width: 650px; margin: 0 auto 20px; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .report-card { background: white; max-width: 800px; margin: 0 auto; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border-top: 10px solid #3498db; }
        select, input { padding: 10px; margin: 5px; border: 1px solid #ccc; border-radius: 5px; width: 140px; }
        .btn-search { background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .btn-search:hover { background: #2980b9; }
        table { width: 100%; border-collapse: collapse; margin-top: 25px; }
        th, td { border: 1px solid #eee; padding: 15px; text-align: left; }
        th { background: #f9f9f9; color: #333; font-weight: 600; }
        .not-inserted { color: #e74c3c; font-style: italic; font-weight: bold; }
        .pass { color: #27ae60; font-weight: bold; }
        .fail { color: #c0392b; font-weight: bold; }
        .info-header { display: flex; justify-content: space-between; margin-bottom: 20px; line-height: 1.8; }
        hr { border: 0; border-top: 1px solid #eee; margin: 20px 0; }
        .back-link { color: #7f8c8d; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>

<div class="search-container">
    <h2 style="color: #2c3e50;">Search Student Result</h2>
    <form method="GET">
        <input type="number" name="student_id" placeholder="ID (e.g. 9)" required value="<?php echo isset($_GET['student_id']) ? htmlspecialchars($_GET['student_id']) : ''; ?>">
        
        <select name="class_id" required>
            <option value="">-- Class --</option>
            <?php for($i=1; $i<=10; $i++): ?>
                <option value="<?php echo $i; ?>" <?php if(isset($_GET['class_id']) && $_GET['class_id'] == $i) echo 'selected'; ?>>Class <?php echo $i; ?></option>
            <?php endfor; ?>
        </select>

        <select name="exam_type" required>
            <option value="Midterm" <?php if(isset($_GET['exam_type']) && $_GET['exam_type'] == 'Midterm') echo 'selected'; ?>>Midterm</option>
            <option value="Final" <?php if(isset($_GET['exam_type']) && $_GET['exam_type'] == 'Final') echo 'selected'; ?>>Final</option>
        </select>

        <button type="submit" name="search" class="btn-search">View Report</button>
    </form>
    <p><a href="dashboard.php" class="back-link">← Back to Dashboard</a></p>
</div>

<?php if ($search_performed): ?>
    <?php if ($student_info): ?>
        <div class="report-card">
            <center>
                <h1 style="margin:0; color: #2c3e50;">ACADEMIC PROGRESS REPORT</h1>
                <p style="text-transform: uppercase; letter-spacing: 2px; color: #7f8c8d; margin-top: 5px;">
                    <?php echo htmlspecialchars($_GET['exam_type']); ?> Examination
                </p>
            </center>
            <hr>
            
            <div class="info-header">
                <div>
                    <strong>Name:</strong> <?php echo strtoupper(htmlspecialchars($student_info['name'])); ?><br>
                    <strong>Student ID:</strong> <?php echo htmlspecialchars($student_info['student_id']); ?>
                </div>
                <div style="text-align:right;">
                    <strong>Class:</strong> <?php echo htmlspecialchars($_GET['class_id']); ?><br>
                    <strong>Gender:</strong> <?php echo htmlspecialchars($student_info['gender']); ?>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Subject Name</th>
                        <th>Marks Obtained</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($marks_data as $data): ?>
                        <tr>
                            <td style="font-weight: 500;"><?php echo htmlspecialchars($data['subject_name']); ?></td>
                            <td>
                                <?php 
                                if ($data['marks_obtained'] !== null) {
                                    echo number_format($data['marks_obtained'], 2);
                                } else {
                                    echo "<span class='not-inserted'>Not Inserted</span>";
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                    if ($data['marks_obtained'] === null) {
                                        echo "<span style='color:#95a5a6;'>Pending</span>";
                                    } elseif ($data['marks_obtained'] >= 40) {
                                        echo "<span class='pass'>Pass</span>";
                                    } else {
                                        echo "<span class='fail'>Fail</span>";
                                    }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p style="margin-top:40px; text-align:center; font-size:12px; color:#bdc3c7;">
                Report Generated on: <?php echo date("F j, Y, g:i a"); ?>
            </p>
        </div>
    <?php else: ?>
        <div style="text-align:center; color: #e74c3c; background: #fdeaea; padding: 20px; max-width: 650px; margin: 20px auto; border-radius: 10px; border: 1px solid #fab1a0;">
            <strong>Error!</strong> Student with ID <?php echo htmlspecialchars($_GET['student_id']); ?> not found in Class <?php echo htmlspecialchars($_GET['class_id']); ?>.
        </div>
    <?php endif; ?>
<?php endif; ?>

</body>
</html>