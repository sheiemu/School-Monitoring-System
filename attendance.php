<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$msg = "";
$today = date('Y-m-d');

// FIRST: Make sure Class table has data
$check_classes = sqlsrv_query($conn, "SELECT COUNT(*) as cnt FROM Class");
$class_count = sqlsrv_fetch_array($check_classes, SQLSRV_FETCH_ASSOC);

if ($class_count['cnt'] == 0) {
    // Insert default classes
    $classes_to_insert = [
        'Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5',
        'Class 6', 'Class 7', 'Class 8', 'Class 9', 'Class 10'
    ];
    foreach ($classes_to_insert as $class) {
        sqlsrv_query($conn, "INSERT INTO Class (class_name) VALUES ('$class')");
    }
    $msg = "<p style='color:green;'>✓ Default classes have been added to the system!</p>";
}

// Get the working date from URL or POST, default to today
$working_date = isset($_GET['attendance_date']) ? $_GET['attendance_date'] : (isset($_POST['attendance_date']) ? $_POST['attendance_date'] : $today);

// Handle Single Student Attendance by ID
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_attendance'])) {
    $class_id = $_POST['class_id'];
    $student_id = $_POST['student_id'];
    $status = $_POST['status'];
    $attendance_date = $_POST['attendance_date'];
    
    // First check if student exists in the selected class
    $check_student = sqlsrv_query($conn, "SELECT s.*, c.class_name FROM Student s JOIN Class c ON s.class_id = c.class_id WHERE s.student_id = ? AND s.class_id = ?", 
        array($student_id, $class_id));
    
    if (!sqlsrv_has_rows($check_student)) {
        $msg = "<p style='color:red;'>✗ Student ID $student_id not found in the selected class!</p>";
    } else {
        $student = sqlsrv_fetch_array($check_student, SQLSRV_FETCH_ASSOC);
        
        // Check if attendance already marked for the selected date
        $check = sqlsrv_query($conn, "SELECT * FROM Attendance WHERE student_id = ? AND attendance_date = ?", 
            array($student_id, $attendance_date));
        
        if (sqlsrv_has_rows($check)) {
            // Update existing
            $sql = "UPDATE Attendance SET status = ? WHERE student_id = ? AND attendance_date = ?";
            $stmt = sqlsrv_query($conn, $sql, array($status, $student_id, $attendance_date));
            $msg = "<p style='color:#ff9800;'>⚠ Attendance UPDATED for {$student['name']} (ID: $student_id) on $attendance_date (was changed to $status)</p>";
        } else {
            // Insert new
            $sql = "INSERT INTO Attendance (student_id, attendance_date, status) VALUES (?, ?, ?)";
            $stmt = sqlsrv_query($conn, $sql, array($student_id, $attendance_date, $status));
            $msg = "<p style='color:green;'>✓ NEW Attendance recorded for {$student['name']} (ID: $student_id) on $attendance_date as $status</p>";
        }
        
        if (!$stmt) {
            $msg = "<p style='color:red;'>✗ Error recording attendance!</p>";
        }
    }
}

// Handle Percentage-based Bulk Attendance
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bulk_attendance'])) {
    $class_id = $_POST['class_id'];
    $attendance_percentage = floatval($_POST['attendance_percentage']);
    $attendance_date = $_POST['attendance_date'];
    
    if ($class_id == "") {
        $msg = "<p style='color:red;'>✗ Please select a class!</p>";
    } elseif ($attendance_percentage < 0 || $attendance_percentage > 100) {
        $msg = "<p style='color:red;'>✗ Percentage must be between 0 and 100!</p>";
    } else {
        // Get all students in the class
        $class_students = sqlsrv_query($conn, "SELECT student_id FROM Student WHERE class_id = ?", array($class_id));
        
        $students_list = [];
        while($s = sqlsrv_fetch_array($class_students, SQLSRV_FETCH_ASSOC)) {
            $students_list[] = $s['student_id'];
        }
        
        $total_students = count($students_list);
        if ($total_students > 0) {
            $present_count = round(($attendance_percentage / 100) * $total_students);
            $absent_count = $total_students - $present_count;
            
            // Shuffle students to randomly select which ones are present
            shuffle($students_list);
            $present_students = array_slice($students_list, 0, $present_count);
            $absent_students = array_slice($students_list, $present_count);
            
            $marked_present = 0;
            $marked_absent = 0;
            
            // Mark present students
            foreach ($present_students as $sid) {
                $check = sqlsrv_query($conn, "SELECT * FROM Attendance WHERE student_id = ? AND attendance_date = ?", 
                    array($sid, $attendance_date));
                
                if (sqlsrv_has_rows($check)) {
                    sqlsrv_query($conn, "UPDATE Attendance SET status = 'Present' WHERE student_id = ? AND attendance_date = ?", 
                        array($sid, $attendance_date));
                } else {
                    sqlsrv_query($conn, "INSERT INTO Attendance (student_id, attendance_date, status) VALUES (?, ?, 'Present')", 
                        array($sid, $attendance_date));
                }
                $marked_present++;
            }
            
            // Mark absent students
            foreach ($absent_students as $sid) {
                $check = sqlsrv_query($conn, "SELECT * FROM Attendance WHERE student_id = ? AND attendance_date = ?", 
                    array($sid, $attendance_date));
                
                if (sqlsrv_has_rows($check)) {
                    sqlsrv_query($conn, "UPDATE Attendance SET status = 'Absent' WHERE student_id = ? AND attendance_date = ?", 
                        array($sid, $attendance_date));
                } else {
                    sqlsrv_query($conn, "INSERT INTO Attendance (student_id, attendance_date, status) VALUES (?, ?, 'Absent')", 
                        array($sid, $attendance_date));
                }
                $marked_absent++;
            }
            
            $class_name_sql = sqlsrv_query($conn, "SELECT class_name FROM Class WHERE class_id = ?", array($class_id));
            $class_row = sqlsrv_fetch_array($class_name_sql, SQLSRV_FETCH_ASSOC);
            
            $msg = "<p style='color:green;'>✓ Bulk attendance for {$class_row['class_name']}: $marked_present Present, $marked_absent Absent ($attendance_percentage%) on $attendance_date</p>";
        } else {
            $msg = "<p style='color:red;'>✗ No students found in this class! Please add students first.</p>";
        }
    }
}

// Get attendance summary for selected date
$date_attendance = sqlsrv_query($conn, "
    SELECT 
        COUNT(CASE WHEN status = 'Present' THEN 1 END) as present,
        COUNT(CASE WHEN status = 'Absent' THEN 1 END) as absent,
        COUNT(*) as total
    FROM Attendance WHERE attendance_date = ?
", array($working_date));
$date_row = sqlsrv_fetch_array($date_attendance, SQLSRV_FETCH_ASSOC);

// Get attendance records for selected date
$daily_records = sqlsrv_query($conn, "
    SELECT s.student_id, s.name, c.class_name, a.status
    FROM Attendance a
    JOIN Student s ON a.student_id = s.student_id
    JOIN Class c ON s.class_id = c.class_id
    WHERE a.attendance_date = ?
    ORDER BY s.student_id
", array($working_date));

// Get classes for dropdown
$classes = sqlsrv_query($conn, "SELECT * FROM Class ORDER BY class_id");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Attendance Management</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #1c0b14; color: #f4f4f4; }
        .header { background: #4b001f; padding: 20px; text-align: center; }
        .header h1 { color: #ff4b6e; margin: 0; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .card { background: #2c001a; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        h2 { color: #ff4b6e; margin-bottom: 20px; border-left: 4px solid #ff4b6e; padding-left: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input { width: 100%; padding: 12px; border: 1px solid #660026; border-radius: 6px; background: #3d0023; color: #f4f4f4; font-size: 14px; }
        button { padding: 12px 25px; background: #ff4b6e; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        button:hover { background: #99001a; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-box { background: #3d0023; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-number { font-size: 32px; font-weight: bold; color: #ff4b6e; }
        .stat-label { font-size: 12px; color: #888; margin-top: 5px; }
        .present { color: #4caf50; }
        .absent { color: #f44336; }
        .btn-back { display: inline-block; padding: 10px 20px; background: #4caf50; color: white; text-decoration: none; border-radius: 6px; margin-top: 10px; }
        .btn-back:hover { background: #45a049; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #3d0023; }
        th { background: #3d0023; color: #ff4b6e; }
        .bulk-form, .single-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end; }
        .note { background: #3d0023; padding: 10px; border-radius: 6px; margin-top: 15px; font-size: 12px; color: #888; }
        .id-input { font-size: 18px; font-weight: bold; }
        .date-header { background: #3d0023; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .date-header h3 { color: #ff4b6e; }
        .current-date { font-size: 20px; font-weight: bold; color: #ff4b6e; }
        .error-box { background: #b71c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>✅ Attendance Management System</h1>
    </div>
    
    <div class="container">
        <?= $msg ?>
        
        <!-- Date Selector at TOP -->
        <div class="card">
            <div class="date-header">
                <h3>📅 Select Working Date</h3>
                <form method="GET" style="display: flex; gap: 10px; justify-content: center; align-items: center; flex-wrap: wrap; margin-top: 10px;">
                    <input type="date" name="attendance_date" value="<?= $working_date ?>" style="width: auto;">
                    <button type="submit">Change Date</button>
                </form>
                <p class="current-date" style="margin-top: 10px;">Currently Working on: <?= $working_date ?></p>
            </div>
        </div>
        
        <!-- Statistics for selected date -->
        <div class="card">
            <h2>📊 Attendance Summary for <?= $working_date ?></h2>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-number present"><?= $date_row['present'] ?? 0 ?></div>
                    <div class="stat-label">Present</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number absent"><?= $date_row['absent'] ?? 0 ?></div>
                    <div class="stat-label">Absent</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= $date_row['total'] ?? 0 ?></div>
                    <div class="stat-label">Total Marked</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= ($date_row['total'] > 0) ? round(($date_row['present'] / $date_row['total']) * 100, 1) : 0 ?>%</div>
                    <div class="stat-label">Attendance Rate</div>
                </div>
            </div>
        </div>
        
        <!-- Single Student Attendance -->
        <div class="card">
            <h2>👤 Mark Single Student Attendance for <?= $working_date ?></h2>
            <form method="POST" class="single-form">
                <input type="hidden" name="attendance_date" value="<?= $working_date ?>">
                
                <div class="form-group">
                    <label>Select Class:</label>
                    <select name="class_id" required>
                        <option value="">-- Select Class --</option>
                        <?php 
                        $has_classes = false;
                        while($c = sqlsrv_fetch_array($classes, SQLSRV_FETCH_ASSOC)): 
                            $has_classes = true;
                        ?>
                            <option value="<?= $c['class_id'] ?>"><?= $c['class_name'] ?></option>
                        <?php endwhile; 
                        if(!$has_classes):
                        ?>
                            <option value="" disabled>No classes found! Please run SQL to insert classes</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Enter Student ID:</label>
                    <input type="number" name="student_id" placeholder="Type Student ID (e.g., 39)" class="id-input" required>
                </div>
                
                <div class="form-group">
                    <label>Attendance Status:</label>
                    <select name="status" required>
                        <option value="Present">✅ Present</option>
                        <option value="Absent">❌ Absent</option>
                    </select>
                </div>
                
                <button type="submit" name="mark_attendance">Mark Attendance for <?= $working_date ?></button>
            </form>
        </div>
        
        <!-- Bulk Attendance -->
        <div class="card">
            <h2>👥 Bulk Attendance for <?= $working_date ?></h2>
            <form method="POST" class="bulk-form">
                <input type="hidden" name="attendance_date" value="<?= $working_date ?>">
                
                <div class="form-group">
                    <label>Select Class:</label>
                    <select name="class_id" required>
                        <option value="">-- Choose Class --</option>
                        <?php 
                        $classes2 = sqlsrv_query($conn, "SELECT * FROM Class ORDER BY class_id");
                        while($c = sqlsrv_fetch_array($classes2, SQLSRV_FETCH_ASSOC)): 
                        ?>
                            <option value="<?= $c['class_id'] ?>"><?= $c['class_name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Attendance Percentage (%):</label>
                    <input type="number" name="attendance_percentage" min="0" max="100" step="1" placeholder="e.g., 86" required>
                </div>
                
                <button type="submit" name="bulk_attendance">Apply Bulk Attendance for <?= $working_date ?></button>
            </form>
            <div class="note">
                💡 Example: Class 1 with 86% means 86% of students will be marked Present, 14% Absent (randomly distributed)
            </div>
        </div>
        
        <!-- Today's Attendance Records -->
        <div class="card">
            <h2>📋 Attendance Records for <?= $working_date ?></h2>
            <?php if(sqlsrv_has_rows($daily_records)): ?>
                <table>
                    <thead>
                        <tr><th>Student ID</th><th>Student Name</th><th>Class</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php while($row = sqlsrv_fetch_array($daily_records, SQLSRV_FETCH_ASSOC)): ?>
                        <tr>
                            <td><?= $row['student_id'] ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= $row['class_name'] ?></td>
                            <td class="<?= strtolower($row['status']) ?>"><?= $row['status'] ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align:center; color:#888;">No attendance records found for <?= $working_date ?>. Mark attendance above!</p>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center;">
            <a href="teacher_dashboard.php" class="btn-back">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>