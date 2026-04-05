<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$student_info = null;
$subject_results = [];
$error_msg = "";
$search_performed = false;
$selected_exam = isset($_GET['exam']) ? $_GET['exam'] : 'all';

// Exam configurations
$exam_configs = [
    'all' => ['name' => 'All Exams (Combined)', 'max' => 100],
    'quiz' => ['name' => 'Quiz', 'max' => 10],
    'assignment' => ['name' => 'Assignment', 'max' => 10],
    'midterm' => ['name' => 'Midterm', 'max' => 20],
    'final' => ['name' => 'Final', 'max' => 70]
];

// Get classes for dropdown
$classes = sqlsrv_query($conn, "SELECT * FROM Class ORDER BY class_id");

// Handle search
if (isset($_POST['search']) || isset($_GET['student_id'])) {
    $class_id = isset($_POST['class_id']) ? $_POST['class_id'] : (isset($_GET['class_id']) ? $_GET['class_id'] : '');
    $student_id = isset($_POST['student_id']) ? $_POST['student_id'] : (isset($_GET['student_id']) ? $_GET['student_id'] : '');
    $search_performed = true;
    
    if (!empty($class_id) && !empty($student_id)) {
        // Verify student exists
        $check_sql = "SELECT s.*, c.class_name 
                      FROM Student s 
                      JOIN Class c ON s.class_id = c.class_id 
                      WHERE s.student_id = ? AND s.class_id = ?";
        $check_stmt = sqlsrv_query($conn, $check_sql, array($student_id, $class_id));
        
        if (!$check_stmt || !sqlsrv_has_rows($check_stmt)) {
            $error_msg = "Student ID $student_id not found in the selected class!";
        } else {
            $student_info = sqlsrv_fetch_array($check_stmt, SQLSRV_FETCH_ASSOC);
            
            // Get all marks for this student
            $marks_sql = "SELECT sub.subject_name, m.marks_obtained, m.exam_type
                          FROM Marks m 
                          JOIN Subject sub ON m.subject_id = sub.subject_id 
                          WHERE m.student_id = ?
                          ORDER BY sub.subject_name";
            $marks_stmt = sqlsrv_query($conn, $marks_sql, array($student_id));
            
            // Organize marks by subject
            $marks_by_subject = [];
            if ($marks_stmt) {
                while($row = sqlsrv_fetch_array($marks_stmt, SQLSRV_FETCH_ASSOC)) {
                    $subject = $row['subject_name'];
                    $exam_type = $row['exam_type'];
                    $marks = $row['marks_obtained'];
                    
                    if (!isset($marks_by_subject[$subject])) {
                        $marks_by_subject[$subject] = [
                            'Quiz' => 0,
                            'Assignment' => 0,
                            'Midterm' => 0,
                            'Final' => 0
                        ];
                    }
                    $marks_by_subject[$subject][$exam_type] = $marks;
                }
            }
            
            // Calculate results based on selected exam
            foreach ($marks_by_subject as $subject => $marks) {
                if ($selected_exam == 'all') {
                    $obtained = $marks['Quiz'] + $marks['Assignment'] + $marks['Midterm'] + $marks['Final'];
                    $max = 100;
                } elseif ($selected_exam == 'quiz') {
                    $obtained = $marks['Quiz'];
                    $max = 10;
                } elseif ($selected_exam == 'assignment') {
                    $obtained = $marks['Assignment'];
                    $max = 10;
                } elseif ($selected_exam == 'midterm') {
                    $obtained = $marks['Midterm'];
                    $max = 20;
                } else { // final
                    $obtained = $marks['Final'];
                    $max = 70;
                }
                
                $percentage = $max > 0 ? round(($obtained / $max) * 100, 2) : 0;
                
                // Determine grade
                if ($percentage >= 80) {
                    $grade = 'A+';
                } elseif ($percentage >= 70) {
                    $grade = 'A';
                } elseif ($percentage >= 60) {
                    $grade = 'B';
                } elseif ($percentage >= 50) {
                    $grade = 'C';
                } elseif ($percentage >= 40) {
                    $grade = 'D';
                } else {
                    $grade = 'F';
                }
                
                $subject_results[] = [
                    'subject' => $subject,
                    'obtained' => $obtained,
                    'max' => $max,
                    'percentage' => $percentage,
                    'grade' => $grade
                ];
            }
            
            // Sort subjects alphabetically
            usort($subject_results, function($a, $b) {
                return strcmp($a['subject'], $b['subject']);
            });
        }
    }
}

// Calculate overall totals
$overall = null;
if (!empty($subject_results)) {
    $total_obtained = 0;
    $total_max = 0;
    foreach ($subject_results as $subject) {
        $total_obtained += $subject['obtained'];
        $total_max += $subject['max'];
    }
    $overall_percentage = $total_max > 0 ? round(($total_obtained / $total_max) * 100, 2) : 0;
    
    if ($overall_percentage >= 80) {
        $overall_grade = 'A+';
    } elseif ($overall_percentage >= 70) {
        $overall_grade = 'A';
    } elseif ($overall_percentage >= 60) {
        $overall_grade = 'B';
    } elseif ($overall_percentage >= 50) {
        $overall_grade = 'C';
    } elseif ($overall_percentage >= 40) {
        $overall_grade = 'D';
    } else {
        $overall_grade = 'F';
    }
    
    $overall = [
        'total_obtained' => $total_obtained,
        'total_max' => $total_max,
        'overall_percentage' => $overall_percentage,
        'overall_grade' => $overall_grade
    ];
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
        .search-card { background: #2c001a; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        h2 { color: #ff4b6e; margin-bottom: 20px; border-left: 4px solid #ff4b6e; padding-left: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input { width: 100%; padding: 12px; border: 1px solid #660026; border-radius: 6px; background: #3d0023; color: #f4f4f4; font-size: 14px; }
        button { padding: 12px 25px; background: #ff4b6e; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; margin-top: 10px; }
        button:hover { background: #99001a; }
        .btn-back { display: inline-block; padding: 10px 20px; background: #4caf50; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; }
        .btn-back:hover { background: #45a049; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #3d0023;
        }
        th {
            background: #3d0023;
            color: #ff4b6e;
            font-weight: bold;
        }
        tr:hover {
            background: rgba(255, 75, 110, 0.1);
        }
        .total-row {
            background: #3d0023;
            font-weight: bold;
        }
        .total-row td {
            border-top: 2px solid #ff4b6e;
        }
        .score-high { color: #4caf50; font-weight: bold; }
        .score-medium { color: #ff9800; font-weight: bold; }
        .score-low { color: #f44336; font-weight: bold; }
        
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .info-item { background: #3d0023; padding: 15px; border-radius: 8px; }
        .error-box { background: #b71c1c; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .row-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; align-items: end; }
        @media (max-width: 768px) { .row-2col { grid-template-columns: 1fr; } }
        
        .exam-filters { display: flex; gap: 10px; margin: 20px 0; flex-wrap: wrap; justify-content: center; }
        .filter-btn { padding: 10px 20px; background: #3d0023; border: 1px solid #ff4b6e; border-radius: 6px; text-decoration: none; color: #f4f4f4; transition: 0.3s; }
        .filter-btn:hover, .filter-btn.active { background: #ff4b6e; color: white; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📖 Student Academic Results</h1>
    </div>
    
    <div class="container">
        <!-- Search Form -->
        <div class="search-card">
            <h2>🔍 Search Student Results</h2>
            <form method="POST">
                <div class="row-2col">
                    <div class="form-group">
                        <label>Select Class:</label>
                        <select name="class_id" required>
                            <option value="">-- Select Class --</option>
                            <?php 
                            $c_reset = sqlsrv_query($conn, "SELECT * FROM Class ORDER BY class_id");
                            while($c = sqlsrv_fetch_array($c_reset, SQLSRV_FETCH_ASSOC)): 
                            ?>
                                <option value="<?= $c['class_id'] ?>" <?= (isset($_POST['class_id']) && $_POST['class_id'] == $c['class_id']) ? 'selected' : '' ?>>
                                    <?= $c['class_name'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Enter Student ID:</label>
                        <input type="number" name="student_id" placeholder="Type Student ID (e.g., 101)" required value="<?= isset($_POST['student_id']) ? $_POST['student_id'] : (isset($_GET['student_id']) ? $_GET['student_id'] : '') ?>">
                    </div>
                </div>
                <button type="submit" name="search">🔍 View Report</button>
            </form>
        </div>
        
        <?php if($error_msg): ?>
            <div class="error-box">❌ <?= $error_msg ?></div>
        <?php endif; ?>
        
        <?php if($student_info && !empty($subject_results)): ?>
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
            
            <!-- Exam Type Filters -->
            <div class="card">
                <div class="exam-filters">
                    <a href="?exam=all&student_id=<?= $student_info['student_id'] ?>&class_id=<?= $class_id ?>" class="filter-btn <?= ($selected_exam == 'all') ? 'active' : '' ?>">📊 All Exams (100 marks)</a>
                    <a href="?exam=quiz&student_id=<?= $student_info['student_id'] ?>&class_id=<?= $class_id ?>" class="filter-btn <?= ($selected_exam == 'quiz') ? 'active' : '' ?>">📝 Quiz (10 marks)</a>
                    <a href="?exam=assignment&student_id=<?= $student_info['student_id'] ?>&class_id=<?= $class_id ?>" class="filter-btn <?= ($selected_exam == 'assignment') ? 'active' : '' ?>">📋 Assignment (10 marks)</a>
                    <a href="?exam=midterm&student_id=<?= $student_info['student_id'] ?>&class_id=<?= $class_id ?>" class="filter-btn <?= ($selected_exam == 'midterm') ? 'active' : '' ?>">📖 Midterm (20 marks)</a>
                    <a href="?exam=final&student_id=<?= $student_info['student_id'] ?>&class_id=<?= $class_id ?>" class="filter-btn <?= ($selected_exam == 'final') ? 'active' : '' ?>">🎓 Final (70 marks)</a>
                </div>
            </div>
            
            <!-- Results Table -->
            <div class="card">
                <h2>📝 <?= $exam_configs[$selected_exam]['name'] ?> Results</h2>
                
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Marks Obtained</th>
                            <th>Max Marks</th>
                            <th>%</th>
                            <th>Grade</th>
                        </thead>
                    </thead>
                    <tbody>
                        <?php 
                        $total_obtained = 0;
                        $total_max = 0;
                        foreach($subject_results as $subject): 
                            $total_obtained += $subject['obtained'];
                            $total_max += $subject['max'];
                            $grade_class = 'score-high';
                            if ($subject['percentage'] < 40) $grade_class = 'score-low';
                            elseif ($subject['percentage'] < 60) $grade_class = 'score-medium';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($subject['subject']) ?></td>
                            <td><?= $subject['obtained'] ?></td>
                            <td><?= $subject['max'] ?></td>
                            <td class="<?= $grade_class ?>"><?= $subject['percentage'] ?>%</td>
                            <td class="<?= $grade_class ?>"><?= $subject['grade'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if($overall): ?>
                    <tfoot>
                        <tr class="total-row">
                            <td><strong>TOTAL</strong></td>
                            <td><strong><?= $overall['total_obtained'] ?></strong></td>
                            <td><strong><?= $overall['total_max'] ?></strong></td>
                            <td><strong><?= $overall['overall_percentage'] ?>%</strong></td>
                            <td><strong><?= $overall['overall_grade'] ?></strong></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
            
            <!-- Grade Legend -->
            <div class="card">
                <div style="padding: 15px; background: #3d0023; border-radius: 8px; text-align: center;">
                    <strong>Grade Legend:</strong><br>
                    <span class="score-high">A+ (80-100%)</span> | 
                    <span class="score-high">A (70-79%)</span> | 
                    <span class="score-medium">B (60-69%)</span> | 
                    <span class="score-medium">C (50-59%)</span> | 
                    <span class="score-low">D (40-49%)</span> | 
                    <span class="score-low">F (Below 40%)</span>
                </div>
            </div>
            
        <?php elseif($search_performed && !$student_info): ?>
            <div class="error-box">❌ No student found. Please check the Class and Student ID and try again.</div>
        <?php elseif($search_performed && empty($subject_results)): ?>
            <div class="error-box">⚠ No marks found for this student. Please add marks in the Manage Marks section.</div>
        <?php endif; ?>
        
        <div style="text-align: center;">
            <a href="teacher_dashboard.php" class="btn-back">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>