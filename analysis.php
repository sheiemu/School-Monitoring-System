<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get statistics
// Total students
$total_students_sql = "SELECT COUNT(*) as total FROM Student";
$total_result = sqlsrv_query($conn, $total_students_sql);
$total_students = sqlsrv_fetch_array($total_result, SQLSRV_FETCH_ASSOC)['total'];

// Students by class
$class_stats = sqlsrv_query($conn, "
    SELECT c.class_name, COUNT(s.student_id) as student_count
    FROM Class c
    LEFT JOIN Student s ON c.class_id = s.class_id
    GROUP BY c.class_name, c.class_id
    ORDER BY c.class_id
");

// Weak students (average marks below 50)
$weak_students = sqlsrv_query($conn, "
    SELECT s.student_id, s.name, AVG(m.marks_obtained) as avg_marks
    FROM Student s
    JOIN Marks m ON s.student_id = m.student_id
    GROUP BY s.student_id, s.name
    HAVING AVG(m.marks_obtained) < 50
");

// Attendance summary
$attendance_summary = sqlsrv_query($conn, "
    SELECT 
        COUNT(CASE WHEN status = 'Present' THEN 1 END) as present_count,
        COUNT(CASE WHEN status = 'Absent' THEN 1 END) as absent_count,
        COUNT(*) as total_records
    FROM Attendance
");

$att_row = sqlsrv_fetch_array($attendance_summary, SQLSRV_FETCH_ASSOC);
$total_records = $att_row['total_records'] ?? 0;
$present_count = $att_row['present_count'] ?? 0;
$att_pct = $total_records > 0 ? round(($present_count / $total_records) * 100, 2) : 0;

// Behaviour summary
$behaviour_summary = sqlsrv_query($conn, "
    SELECT 
        AVG(score) as avg_score,
        COUNT(*) as total_records
    FROM Behaviour
    WHERE score IS NOT NULL
");

$beh_row = sqlsrv_fetch_array($behaviour_summary, SQLSRV_FETCH_ASSOC);
$avg_behaviour = $beh_row['avg_score'] ? round($beh_row['avg_score'], 2) : 0;
$total_behaviour = $beh_row['total_records'] ?? 0;

// Top performing students (average marks above 80)
$top_students = sqlsrv_query($conn, "
    SELECT TOP 5 s.student_id, s.name, AVG(m.marks_obtained) as avg_marks
    FROM Student s
    JOIN Marks m ON s.student_id = m.student_id
    GROUP BY s.student_id, s.name
    HAVING AVG(m.marks_obtained) >= 80
    ORDER BY avg_marks DESC
");

// Monthly attendance trend (last 6 months)
$monthly_attendance = sqlsrv_query($conn, "
    SELECT 
        FORMAT(attendance_date, 'yyyy-MM') as month,
        COUNT(CASE WHEN status = 'Present' THEN 1 END) * 100.0 / COUNT(*) as attendance_rate
    FROM Attendance
    WHERE attendance_date >= DATEADD(MONTH, -6, GETDATE())
    GROUP BY FORMAT(attendance_date, 'yyyy-MM')
    ORDER BY month DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Analysis</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #1c0b14; margin: 0; color: #f4f4f4; }
        .header { background: #4b001f; padding: 20px; text-align: center; }
        .header h1 { color: #ff4b6e; margin-bottom: 10px; }
        .container { max-width: 1300px; margin: 30px auto; padding: 0 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #2c001a; border-radius: 12px; padding: 25px; text-align: center; border-top: 4px solid #ff4b6e; }
        .stat-number { font-size: 48px; font-weight: bold; color: #ff4b6e; margin-bottom: 10px; }
        .stat-label { font-size: 14px; text-transform: uppercase; letter-spacing: 1px; color: #888; }
        .section { background: #2c001a; border-radius: 12px; padding: 25px; margin-bottom: 30px; }
        .section h2 { color: #ff4b6e; margin-bottom: 20px; border-left: 4px solid #ff4b6e; padding-left: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #3d0023; }
        th { background: #3d0023; color: #ff4b6e; font-weight: bold; }
        .badge-warning { color: #ff9800; font-weight: bold; }
        .badge-success { color: #4caf50; font-weight: bold; }
        .btn-back { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #4caf50; color: white; text-decoration: none; border-radius: 6px; }
        .btn-back:hover { background: #45a049; }
        .progress-bar { background: #3d0023; border-radius: 10px; height: 20px; overflow: hidden; margin-top: 5px; }
        .progress-fill { background: #ff4b6e; height: 100%; border-radius: 10px; transition: width 0.5s; }
        .no-data { text-align: center; color: #888; padding: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📈 Data Analysis & Insights</h1>
        <p>Academic performance, attendance trends, and behaviour analytics</p>
    </div>

    <div class="container">
        <!-- Key Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $total_students ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $att_pct ?>%</div>
                <div class="stat-label">Overall Attendance Rate</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $avg_behaviour ?>/5</div>
                <div class="stat-label">Average Behaviour Score</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_behaviour ?></div>
                <div class="stat-label">Total Behaviour Records</div>
            </div>
        </div>

        <!-- Students by Class -->
        <div class="section">
            <h2>📊 Students by Class</h2>
            <table>
                <thead>
                    <tr><th>Class Name</th><th>Number of Students</th><th>Distribution</th></tr>
                </thead>
                <tbody>
                    <?php 
                    $has_classes = false;
                    while($row = sqlsrv_fetch_array($class_stats, SQLSRV_FETCH_ASSOC)): 
                        $has_classes = true;
                        $percentage = $total_students > 0 ? round(($row['student_count'] / $total_students) * 100, 2) : 0;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($row['class_name']) ?></td>
                            <td><?= $row['student_count'] ?></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $percentage ?>%"></div>
                                </div>
                                <span style="font-size: 12px;"><?= $percentage ?>%</span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if(!$has_classes): ?>
                        <tr><td colspan="3" class="no-data">No class data available</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Weak Students (Need Attention) -->
        <div class="section">
            <h2>⚠️ Students Needing Academic Support (Avg &lt; 50%)</h2>
            <?php 
            $has_weak = false;
            while($row = sqlsrv_fetch_array($weak_students, SQLSRV_FETCH_ASSOC)): 
                $has_weak = true;
            ?>
                <div style="background: #3d0023; border-radius: 8px; padding: 15px; margin-bottom: 10px;">
                    <strong><?= htmlspecialchars($row['name']) ?></strong> (ID: <?= $row['student_id'] ?>)
                    <span class="badge-warning">Average: <?= round($row['avg_marks'], 2) ?>%</span>
                </div>
            <?php endwhile; ?>
            <?php if(!$has_weak): ?>
                <div class="no-data">✓ No weak students! All students are performing well.</div>
            <?php endif; ?>
        </div>

        <!-- Top Performing Students -->
        <div class="section">
            <h2>🏆 Top Performing Students (Avg ≥ 80%)</h2>
            <?php 
            $has_top = false;
            while($row = sqlsrv_fetch_array($top_students, SQLSRV_FETCH_ASSOC)): 
                $has_top = true;
            ?>
                <div style="background: #3d0023; border-radius: 8px; padding: 15px; margin-bottom: 10px;">
                    <strong><?= htmlspecialchars($row['name']) ?></strong> (ID: <?= $row['student_id'] ?>)
                    <span class="badge-success">Average: <?= round($row['avg_marks'], 2) ?>%</span>
                </div>
            <?php endwhile; ?>
            <?php if(!$has_top): ?>
                <div class="no-data">No students with average marks above 80% yet.</div>
            <?php endif; ?>
        </div>

        <!-- Monthly Attendance Trend -->
        <div class="section">
            <h2>📅 Monthly Attendance Trend (Last 6 Months)</h2>
            <table>
                <thead>
                    <tr><th>Month</th><th>Attendance Rate</th><th>Trend</th></tr>
                </thead>
                <tbody>
                    <?php 
                    $has_monthly = false;
                    while($row = sqlsrv_fetch_array($monthly_attendance, SQLSRV_FETCH_ASSOC)): 
                        $has_monthly = true;
                        $rate = round($row['attendance_rate'], 2);
                    ?>
                        <tr>
                            <td><?= $row['month'] ?></td>
                            <td><?= $rate ?>%</td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= min(100, $rate) ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if(!$has_monthly): ?>
                        <tr><td colspan="3" class="no-data">No attendance data available</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Quick Actions -->
        <div style="text-align: center;">
            <a href="teacher_dashboard.php" class="btn-back">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>