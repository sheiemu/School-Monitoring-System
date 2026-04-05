<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$students = sqlsrv_query($conn, "SELECT * FROM Student ORDER BY student_id");
$selected_student = null;
$attendance_data = [];
$marks_data = [];
$behaviour_data = [];

if (isset($_GET['student_id'])) {
    $sid = $_GET['student_id'];
    
    // Get student info
    $stmt = sqlsrv_query($conn, "SELECT * FROM Student WHERE student_id = ?", array($sid));
    $selected_student = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    
    // Get attendance summary
    $stmt = sqlsrv_query($conn, "
        SELECT 
            COUNT(*) as total_days,
            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days
        FROM Attendance 
        WHERE student_id = ?", array($sid));
    $att_summary = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $attendance_pct = $att_summary['total_days'] > 0 ? 
        round(($att_summary['present_days'] / $att_summary['total_days']) * 100, 2) : 0;
    
    // Get marks by subject and exam
    $stmt = sqlsrv_query($conn, "
        SELECT sub.subject_name, m.exam_type, m.marks_obtained
        FROM Marks m
        JOIN Subject sub ON m.subject_id = sub.subject_id
        WHERE m.student_id = ?
        ORDER BY sub.subject_name, m.exam_type", array($sid));
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $marks_data[] = $row;
    }
    
    // Calculate average marks
    $stmt = sqlsrv_query($conn, "
        SELECT AVG(marks_obtained) as avg_marks
        FROM Marks
        WHERE student_id = ?", array($sid));
    $avg_row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $avg_marks = $avg_row['avg_marks'] ? round($avg_row['avg_marks'], 2) : 0;
    
    // Get behaviour records
    $stmt = sqlsrv_query($conn, "
        SELECT description, behaviour_date, score
        FROM Behaviour
        WHERE student_id = ?
        ORDER BY behaviour_date DESC", array($sid));
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $behaviour_data[] = $row;
    }
    
    // Calculate average behaviour score
    $stmt = sqlsrv_query($conn, "
        SELECT AVG(score) as avg_score
        FROM Behaviour
        WHERE student_id = ? AND score IS NOT NULL", array($sid));
    $score_row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $avg_behaviour = $score_row['avg_score'] ? round($score_row['avg_score'], 2) : 0;
    
    // Calculate overall performance score (60% marks + 20% attendance + 20% behaviour)
    $marks_score = min(5, $avg_marks / 20); // Convert 0-100 to 0-5
    $att_score = min(5, $attendance_pct / 20); // Convert 0-100% to 0-5
    $behave_score = $avg_behaviour;
    $overall_score = round(($marks_score * 0.6) + ($att_score * 0.2) + ($behave_score * 0.2), 2);
    
    // Determine status
    if ($overall_score >= 4) $status = "Excellent";
    elseif ($overall_score >= 3) $status = "Good";
    elseif ($overall_score >= 2) $status = "Needs Improvement";
    else $status = "Critical - Needs Immediate Attention";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Performance Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #1c0b14; color: #f4f4f4; }
        .header { background: #4b001f; padding: 20px; text-align: center; }
        .header h1 { color: #ff4b6e; margin-bottom: 10px; }
        .container { display: flex; max-width: 1400px; margin: 30px auto; gap: 30px; padding: 0 20px; }
        .sidebar { width: 280px; background: #2c001a; border-radius: 12px; padding: 20px; height: fit-content; }
        .sidebar h3 { color: #ff4b6e; margin-bottom: 15px; border-bottom: 2px solid #ff4b6e; padding-bottom: 8px; }
        .student-list { list-style: none; }
        .student-list li { margin-bottom: 8px; }
        .student-list a { display: block; padding: 10px; background: #3d0023; color: #f4f4f4; text-decoration: none; border-radius: 6px; transition: 0.3s; }
        .student-list a:hover, .student-list a.active { background: #ff4b6e; color: white; }
        .main { flex: 1; }
        .card { background: #2c001a; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        .card h2 { color: #ff4b6e; margin-bottom: 20px; border-left: 4px solid #ff4b6e; padding-left: 15px; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .info-item { background: #3d0023; padding: 15px; border-radius: 8px; }
        .info-label { font-size: 12px; color: #ff4b6e; text-transform: uppercase; }
        .info-value { font-size: 24px; font-weight: bold; margin-top: 5px; }
        .score-card { text-align: center; background: linear-gradient(135deg, #ff4b6e, #99001a); padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        .score-value { font-size: 48px; font-weight: bold; }
        .score-label { font-size: 14px; opacity: 0.9; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #3d0023; }
        th { background: #3d0023; color: #ff4b6e; font-weight: bold; }
        .status-excellent { color: #4caf50; }
        .status-good { color: #8bc34a; }
        .status-needs { color: #ff9800; }
        .status-critical { color: #f44336; }
        .btn-back { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #ff4b6e; color: white; text-decoration: none; border-radius: 6px; }
        .btn-back:hover { background: #99001a; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏫 Student Performance Dashboard</h1>
        <p>Welcome, Teacher <?php echo htmlspecialchars($_SESSION['user']); ?></p>
    </div>
    
    <div class="container">
        <div class="sidebar">
            <h3>📋 Select Student</h3>
            <ul class="student-list">
                <?php while($s = sqlsrv_fetch_array($students, SQLSRV_FETCH_ASSOC)): ?>
                    <li>
                        <a href="?student_id=<?= $s['student_id'] ?>" 
                           class="<?= (isset($_GET['student_id']) && $_GET['student_id'] == $s['student_id']) ? 'active' : '' ?>">
                            <?= htmlspecialchars($s['name']) ?> - <?= $s['class_id'] ? 'Class ' . $s['class_id'] : 'No Class' ?>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
        
        <div class="main">
            <?php if ($selected_student): ?>
                <div class="score-card">
                    <div class="score-value"><?= $overall_score ?> / 5</div>
                    <div class="score-label">Overall Performance Score</div>
                    <div style="margin-top: 10px; font-size: 18px; font-weight: bold;" 
                         class="status-<?= strtolower(str_replace(' ', '-', explode(' - ', $status)[0])) ?>">
                        <?= $status ?>
                    </div>
                </div>
                
                <div class="card">
                    <h2>👤 Student Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Student ID</div>
                            <div class="info-value"><?= $selected_student['student_id'] ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Name</div>
                            <div class="info-value"><?= htmlspecialchars($selected_student['name']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Class</div>
                            <div class="info-value">Class <?= $selected_student['class_id'] ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Gender</div>
                            <div class="info-value"><?= $selected_student['gender'] ?: 'Not specified' ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Date of Birth</div>
                            <div class="info-value"><?= $selected_student['date_of_birth'] ? $selected_student['date_of_birth']->format('Y-m-d') : 'Not specified' ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <h2>📊 Performance Metrics</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Attendance Rate</div>
                            <div class="info-value"><?= $attendance_pct ?>%</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Average Marks</div>
                            <div class="info-value"><?= $avg_marks ?> / 100</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Behaviour Score</div>
                            <div class="info-value"><?= $avg_behaviour ?> / 5</div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <h2>📝 Academic Marks</h2>
                    <?php if(count($marks_data) > 0): ?>
                        <table>
                            <thead>
                                30<th>Subject</th><th>Exam Type</th><th>Marks</th> </tr>
                            </thead>
                            <tbody>
                                <?php foreach($marks_data as $m): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($m['subject_name']) ?></td>
                                        <td><?= $m['exam_type'] ?></td>
                                        <td><?= $m['marks_obtained'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="color: #888;">No marks recorded yet.</p>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <h2>📋 Behaviour Records</h2>
                    <?php if(count($behaviour_data) > 0): ?>
                        <table>
                            <thead>
                                32<th>Date</th><th>Description</th><th>Score (1-5)</th> </tr>
                            </thead>
                            <tbody>
                                <?php foreach($behaviour_data as $b): ?>
                                    <tr>
                                        <td><?= $b['behaviour_date'] ? $b['behaviour_date']->format('Y-m-d') : 'N/A' ?></td>
                                        <td><?= htmlspecialchars($b['description']) ?></td>
                                        <td><?= $b['score'] ?: 'Not rated' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="color: #888;">No behaviour records yet.</p>
                    <?php endif; ?>
                </div>
                
            <?php else: ?>
                <div class="card" style="text-align: center;">
                    <h2>👈 Select a student from the left sidebar</h2>
                    <p style="margin-top: 20px;">View comprehensive reports including academic performance, attendance, and behaviour records.</p>
                </div>
            <?php endif; ?>
            
            <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>