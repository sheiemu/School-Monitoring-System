<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$student_info = null;
$midterm_marks = [];
$final_marks = [];
$quiz_marks = [];
$assignment_marks = [];
$error_msg = "";

// Get classes for dropdown
$classes = sqlsrv_query($conn, "SELECT * FROM Class ORDER BY class_id");

if (isset($_POST['search'])) {
    $class_id = $_POST['class_id'];
    $student_id = $_POST['student_id'];
    
    // First verify student exists in selected class
    $check_sql = "SELECT s.*, c.class_name 
                  FROM Student s 
                  JOIN Class c ON s.class_id = c.class_id 
                  WHERE s.student_id = ? AND s.class_id = ?";
    $check_stmt = sqlsrv_query($conn, $check_sql, array($student_id, $class_id));
    
    if (!$check_stmt || !sqlsrv_has_rows($check_stmt)) {
        $error_msg = "Student ID $student_id not found in the selected class!";
    } else {
        $student_info = sqlsrv_fetch_array($check_stmt, SQLSRV_FETCH_ASSOC);
        
        // Fetch ALL marks for this student
        $all_marks_sql = "SELECT sub.subject_name, m.marks_obtained, m.exam_type, sub.subject_id
                          FROM Marks m 
                          JOIN Subject sub ON m.subject_id = sub.subject_id 
                          WHERE m.student_id = ?
                          ORDER BY m.exam_type, sub.subject_name";
        $all_marks_stmt = sqlsrv_query($conn, $all_marks_sql, array($student_id));
        
        if ($all_marks_stmt === false) {
            $error_msg = "Error fetching marks: " . print_r(sqlsrv_errors(), true);
        } else {
            while ($row = sqlsrv_fetch_array($all_marks_stmt, SQLSRV_FETCH_ASSOC)) {
                if ($row['exam_type'] == 'Midterm') {
                    $midterm_marks[] = $row;
                } elseif ($row['exam_type'] == 'Final') {
                    $final_marks[] = $row;
                } elseif ($row['exam_type'] == 'Quiz') {
                    $quiz_marks[] = $row;
                } elseif ($row['exam_type'] == 'Assignment') {
                    $assignment_marks[] = $row;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Results</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #1c0b14; color: #f4f4f4; }
        .header { background: #4b001f; padding: 20px; text-align: center; }
        .header h1 { color: #ff4b6e; margin: 0; }
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .card { background: #2c001a; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        h2 { color: #ff4b6e; margin-bottom: 20px; border-left: 4px solid #ff4b6e; padding-left: 15px; }
        h3 { color: #ff4b6e; margin: 20px 0 15px 0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input { width: 100%; padding: 12px; border: 1px solid #660026; border-radius: 6px; background: #3d0023; color: #f4f4f4; font-size: 14px; }
        button { padding: 12px 25px; background: #ff4b6e; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; margin-top: 10px; }
        button:hover { background: #99001a; }
        .btn-back { display: inline-block; padding: 10px 20px; background: #4caf50; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; }
        .btn-back:hover { background: #45a049; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #3d0023; }
        th { background: #3d0023; color: #ff4b6e; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .info-item { background: #3d0023; padding: 15px; border-radius: 8px; }
        .error-box { background: #b71c1c; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .row-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; align-items: end; }
        @media (max-width: 768px) { .row-2col { grid-template-columns: 1fr; } }
        .id-input { font-size: 18px; font-weight: bold; }
        .note { background: #3d0023; padding: 10px; border-radius: 6px; margin-top: 15px; font-size: 12px; color: #888; }
        .score-high { color: #4caf50; font-weight: bold; }
        .score-medium { color: #ff9800; font-weight: bold; }
        .score-low { color: #f44336; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📖 Student Academic Results</h1>
        <p>View student performance by Class and ID</p>
    </div>
    
    <div class="container">
        <!-- Search Form -->
        <div class="card">
            <h2>🔍 Search Student Results</h2>
            <form method="POST">
                <div class="row-2col">
                    <div class="form-group">
                        <label>Select Class:</label>
                        <select name="class_id" required>
                            <option value="">-- Select Class --</option>
                            <?php while($c = sqlsrv_fetch_array($classes, SQLSRV_FETCH_ASSOC)): ?>
                                <option value="<?= $c['class_id'] ?>" <?= (isset($_POST['class_id']) && $_POST['class_id'] == $c['class_id']) ? 'selected' : '' ?>>
                                    <?= $c['class_name'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Enter Student ID:</label>
                        <input type="number" name="student_id" placeholder="Type Student ID (e.g., 101)" class="id-input" required value="<?= isset($_POST['student_id']) ? $_POST['student_id'] : '' ?>">
                    </div>
                </div>
                <button type="submit" name="search">🔍 View Report</button>
            </form>
            <div class="note">
                💡 How to use: First select the Class, then enter the Student ID number (e.g., 101, 102, 201) and click View Report.
            </div>
        </div>
        
        <?php if($error_msg): ?>
            <div class="error-box">
                ❌ <?= $error_msg ?>
            </div>
        <?php endif; ?>
        
        <?php if($student_info): ?>
            <!-- Student Information -->
            <div class="card">
                <h2>👤 Student Information</h2>
                <div class="info-grid">
                    <div class="info-item"><strong>Student ID:</strong> <?= $student_info['student_id'] ?></div>
                    <div class="info-item"><strong>Name:</strong> <?= htmlspecialchars($student_info['name']) ?></div>
                    <div class="info-item"><strong>Class:</strong> <?= $student_info['class_name'] ?></div>
                    <div class="info-item"><strong>Gender:</strong> <?= $student_info['gender'] ?? 'N/A' ?></div>
                    <div class="info-item"><strong>Date of Birth:</strong> <?= $student_info['date_of_birth'] ? $student_info['date_of_birth']->format('Y-m-d') : 'N/A' ?></div>
                </div>
            </div>
            
            <!-- Midterm Results -->
            <div class="card">
                <h2>📝 Midterm Examination</h2>
                <?php if(count($midterm_marks) > 0): ?>
                    <table>
                        <thead>
                            <tr><th>Subject</th><th>Marks Obtained</th><th>Grade</th> </thead>
                        </thead>
                        <tbody>
                            <?php 
                            $mid_total = 0;
                            foreach($midterm_marks as $m): 
                                $mid_total += $m['marks_obtained'];
                                $grade = '';
                                $grade_class = '';
                                if($m['marks_obtained'] >= 80) { $grade = 'A+'; $grade_class = 'score-high'; }
                                elseif($m['marks_obtained'] >= 70) { $grade = 'A'; $grade_class = 'score-high'; }
                                elseif($m['marks_obtained'] >= 60) { $grade = 'B'; $grade_class = 'score-medium'; }
                                elseif($m['marks_obtained'] >= 50) { $grade = 'C'; $grade_class = 'score-medium'; }
                                elseif($m['marks_obtained'] >= 40) { $grade = 'D'; $grade_class = 'score-low'; }
                                else { $grade = 'F'; $grade_class = 'score-low'; }
                            ?>
                            <tr>
                                <td><?= $m['subject_name'] ?></td>
                                <td><?= $m['marks_obtained'] ?></td>
                                <td class="<?= $grade_class ?>"><?= $grade ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(count($midterm_marks) > 0): ?>
                            <tr style="background:#3d0023; font-weight:bold;">
                                <td>Total / Average</td>
                                <td><?= $mid_total ?> / <?= count($midterm_marks) ?> subjects</td>
                                <td><?= round($mid_total / count($midterm_marks), 2) ?>%</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color:#888; text-align:center;">No midterm marks recorded yet. Please add marks in "Manage Marks" section.</p>
                <?php endif; ?>
            </div>
            
            <!-- Final Results -->
            <div class="card">
                <h2>📝 Final Examination</h2>
                <?php if(count($final_marks) > 0): ?>
                    <table>
                        <thead>
                            <tr><th>Subject</th><th>Marks Obtained</th><th>Grade</th> </thead>
                        </thead>
                        <tbody>
                            <?php 
                            $final_total = 0;
                            foreach($final_marks as $f): 
                                $final_total += $f['marks_obtained'];
                                $grade = '';
                                $grade_class = '';
                                if($f['marks_obtained'] >= 80) { $grade = 'A+'; $grade_class = 'score-high'; }
                                elseif($f['marks_obtained'] >= 70) { $grade = 'A'; $grade_class = 'score-high'; }
                                elseif($f['marks_obtained'] >= 60) { $grade = 'B'; $grade_class = 'score-medium'; }
                                elseif($f['marks_obtained'] >= 50) { $grade = 'C'; $grade_class = 'score-medium'; }
                                elseif($f['marks_obtained'] >= 40) { $grade = 'D'; $grade_class = 'score-low'; }
                                else { $grade = 'F'; $grade_class = 'score-low'; }
                            ?>
                            <tr>
                                <td><?= $f['subject_name'] ?></td>
                                <td><?= $f['marks_obtained'] ?></td>
                                <td class="<?= $grade_class ?>"><?= $grade ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(count($final_marks) > 0): ?>
                            <tr style="background:#3d0023; font-weight:bold;">
                                <td>Total / Average</td>
                                <td><?= $final_total ?> / <?= count($final_marks) ?> subjects</td>
                                <td><?= round($final_total / count($final_marks), 2) ?>%</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color:#888; text-align:center;">No final marks recorded yet. Please add marks in "Manage Marks" section.</p>
                <?php endif; ?>
            </div>
            
            <!-- Quiz Results -->
            <?php if(count($quiz_marks) > 0): ?>
            <div class="card">
                <h2>📝 Quiz Results</h2>
                <table>
                    <thead><tr><th>Subject</th><th>Marks Obtained</th></tr></thead>
                    <tbody>
                        <?php foreach($quiz_marks as $q): ?>
                        <tr><td><?= $q['subject_name'] ?></td><td><?= $q['marks_obtained'] ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Assignment Results -->
            <?php if(count($assignment_marks) > 0): ?>
            <div class="card">
                <h2>📝 Assignment Results</h2>
                <table>
                    <thead><tr><th>Subject</th><th>Marks Obtained</th></tr></thead>
                    <tbody>
                        <?php foreach($assignment_marks as $a): ?>
                        <tr><td><?= $a['subject_name'] ?></td><td><?= $a['marks_obtained'] ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Overall Performance Summary -->
            <div class="card">
                <h2>📊 Overall Performance Summary</h2>
                <?php
                $mid_avg = count($midterm_marks) > 0 ? array_sum(array_column($midterm_marks, 'marks_obtained')) / count($midterm_marks) : 0;
                $final_avg = count($final_marks) > 0 ? array_sum(array_column($final_marks, 'marks_obtained')) / count($final_marks) : 0;
                
                $weighted_total = ($mid_avg * 0.4) + ($final_avg * 0.6);
                if(count($midterm_marks) == 0 && count($final_marks) > 0) {
                    $weighted_total = $final_avg;
                } elseif(count($midterm_marks) > 0 && count($final_marks) == 0) {
                    $weighted_total = $mid_avg;
                }
                ?>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Midterm Average (40%):</strong><br>
                        <span style="font-size:24px;"><?= round($mid_avg, 2) ?>%</span>
                    </div>
                    <div class="info-item">
                        <strong>Final Average (60%):</strong><br>
                        <span style="font-size:24px;"><?= round($final_avg, 2) ?>%</span>
                    </div>
                    <div class="info-item">
                        <strong>Final Grade:</strong><br>
                        <span style="font-size:24px;" class="<?= $weighted_total >= 60 ? 'score-high' : ($weighted_total >= 40 ? 'score-medium' : 'score-low') ?>">
                            <?= round($weighted_total, 2) ?>%
                        </span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center;">
            <a href="teacher_dashboard.php" class="btn-back">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>