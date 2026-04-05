<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$msg = "";
$student_info = null;
$subjects = [];
$existing_marks = [];
$class_id = '';
$student_id = '';
$group = '';
$selected_exam = 'Midterm';

// Exam limits
$exam_limits = [
    'Quiz' => 10,
    'Assignment' => 10,
    'Midterm' => 20,
    'Final' => 70
];

$classes = sqlsrv_query($conn, "SELECT * FROM Class ORDER BY class_id");

// Function to get subjects for a class
function getSubjects($conn, $class_id, $group) {
    $subj = [];
    if ($class_id >= 9 && !empty($group)) {
        $group_subs = [];
        if ($group == 'Science') {
            $group_subs = ['Physics', 'Chemistry', 'Higher Math', 'Biology'];
        } elseif ($group == 'Commerce') {
            $group_subs = ['Accounting', 'Finance & Banking', 'Business Entrepreneurship'];
        } elseif ($group == 'Arts') {
            $group_subs = ['History of Bangladesh', 'Geography & Environment', 'Civics & Citizenship'];
        }
        $all_subs = array_merge($group_subs, ['Bangla', 'English', 'Math', 'ICT', 'Religion', 'Bangladesh & Global Studies']);
        $placeholders = implode(',', array_fill(0, count($all_subs), '?'));
        $sql = "SELECT subject_id, subject_name FROM Subject WHERE subject_name IN ($placeholders) ORDER BY subject_name";
        $stmt = sqlsrv_query($conn, $sql, $all_subs);
    } else {
        $sql = "SELECT s.subject_id, s.subject_name 
                FROM Class_Subject cs
                JOIN Subject s ON cs.subject_id = s.subject_id
                WHERE cs.class_id = ?
                ORDER BY s.subject_name";
        $stmt = sqlsrv_query($conn, $sql, array($class_id));
    }
    
    if ($stmt) {
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $subj[] = $row;
        }
    }
    return $subj;
}

// Handle exam selection (AJAX style via POST)
if (isset($_POST['change_exam'])) {
    $class_id = $_POST['class_id'];
    $student_id = $_POST['student_id'];
    $group = isset($_POST['group']) ? $_POST['group'] : '';
    $selected_exam = $_POST['selected_exam'];
    
    // Get student info
    $check = sqlsrv_query($conn, "SELECT s.*, c.class_name FROM Student s JOIN Class c ON s.class_id = c.class_id WHERE s.student_id = ? AND s.class_id = ?", 
        array($student_id, $class_id));
    
    if ($check && sqlsrv_has_rows($check)) {
        $student_info = sqlsrv_fetch_array($check, SQLSRV_FETCH_ASSOC);
        $subjects = getSubjects($conn, $class_id, $group);
        
        // Get existing marks
        $marks_stmt = sqlsrv_query($conn, "SELECT subject_id, marks_obtained, exam_type FROM Marks WHERE student_id = ?", array($student_id));
        if ($marks_stmt) {
            while($mark = sqlsrv_fetch_array($marks_stmt, SQLSRV_FETCH_ASSOC)) {
                $existing_marks[$mark['subject_id']][$mark['exam_type']] = $mark['marks_obtained'];
            }
        }
    }
}

// Load Student (first time)
if (isset($_POST['load_student'])) {
    $class_id = $_POST['class_id'];
    $student_id = $_POST['student_id'];
    $group = isset($_POST['group']) ? $_POST['group'] : '';
    $selected_exam = isset($_POST['selected_exam']) ? $_POST['selected_exam'] : 'Midterm';
    
    $check = sqlsrv_query($conn, "SELECT s.*, c.class_name FROM Student s JOIN Class c ON s.class_id = c.class_id WHERE s.student_id = ? AND s.class_id = ?", 
        array($student_id, $class_id));
    
    if ($check && sqlsrv_has_rows($check)) {
        $student_info = sqlsrv_fetch_array($check, SQLSRV_FETCH_ASSOC);
        $subjects = getSubjects($conn, $class_id, $group);
        
        // Get existing marks
        $marks_stmt = sqlsrv_query($conn, "SELECT subject_id, marks_obtained, exam_type FROM Marks WHERE student_id = ?", array($student_id));
        if ($marks_stmt) {
            while($mark = sqlsrv_fetch_array($marks_stmt, SQLSRV_FETCH_ASSOC)) {
                $existing_marks[$mark['subject_id']][$mark['exam_type']] = $mark['marks_obtained'];
            }
        }
    } else {
        $msg = "<p style='color:red;'>✗ Student ID $student_id not found in selected class!</p>";
    }
}

// Save Marks
if (isset($_POST['save_marks'])) {
    $class_id = $_POST['class_id'];
    $student_id = $_POST['student_id'];
    $group = isset($_POST['group']) ? $_POST['group'] : '';
    $selected_exam = $_POST['selected_exam'];
    $max_marks = $exam_limits[$selected_exam];
    
    $subjects = getSubjects($conn, $class_id, $group);
    $saved = 0;
    
    foreach ($subjects as $subject) {
        $field = 'mark_' . $subject['subject_id'];
        if (isset($_POST[$field]) && $_POST[$field] !== '') {
            $marks = intval($_POST[$field]);
            if ($marks >= 0 && $marks <= $max_marks) {
                // Check if exists
                $check = sqlsrv_query($conn, "SELECT * FROM Marks WHERE student_id = ? AND subject_id = ? AND exam_type = ?", 
                    array($student_id, $subject['subject_id'], $selected_exam));
                
                if ($check && sqlsrv_has_rows($check)) {
                    sqlsrv_query($conn, "UPDATE Marks SET marks_obtained = ? WHERE student_id = ? AND subject_id = ? AND exam_type = ?", 
                        array($marks, $student_id, $subject['subject_id'], $selected_exam));
                } else {
                    sqlsrv_query($conn, "INSERT INTO Marks (student_id, subject_id, marks_obtained, exam_type) VALUES (?, ?, ?, ?)", 
                        array($student_id, $subject['subject_id'], $marks, $selected_exam));
                }
                $saved++;
            }
        }
    }
    
    if ($saved > 0) {
        $msg = "<p style='color:green;'>✓ $saved marks saved for $selected_exam exam!</p>";
    }
    
    // Reload data
    $check = sqlsrv_query($conn, "SELECT s.*, c.class_name FROM Student s JOIN Class c ON s.class_id = c.class_id WHERE s.student_id = ?", array($student_id));
    if ($check) {
        $student_info = sqlsrv_fetch_array($check, SQLSRV_FETCH_ASSOC);
    }
    $subjects = getSubjects($conn, $class_id, $group);
    $marks_stmt = sqlsrv_query($conn, "SELECT subject_id, marks_obtained, exam_type FROM Marks WHERE student_id = ?", array($student_id));
    if ($marks_stmt) {
        while($mark = sqlsrv_fetch_array($marks_stmt, SQLSRV_FETCH_ASSOC)) {
            $existing_marks[$mark['subject_id']][$mark['exam_type']] = $mark['marks_obtained'];
        }
    }
}

// Get all marks for display
$all_marks = sqlsrv_query($conn, "
    SELECT m.mark_id, s.student_id, s.name as student_name, c.class_name, sub.subject_name, m.marks_obtained, m.exam_type
    FROM Marks m
    JOIN Student s ON m.student_id = s.student_id
    JOIN Class c ON s.class_id = c.class_id
    JOIN Subject sub ON m.subject_id = sub.subject_id
    ORDER BY s.class_id, s.student_id, 
        CASE m.exam_type 
            WHEN 'Quiz' THEN 1 
            WHEN 'Assignment' THEN 2 
            WHEN 'Midterm' THEN 3 
            WHEN 'Final' THEN 4 
        END
");

// Delete mark
if (isset($_GET['delete'])) {
    sqlsrv_query($conn, "DELETE FROM Marks WHERE mark_id = ?", array($_GET['delete']));
    header("Location: manage_marks.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Marks</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #1c0b14; color: #f4f4f4; }
        .header { background: #4b001f; padding: 20px; text-align: center; }
        .header h1 { color: #ff4b6e; margin: 0; }
        .container { max-width: 1300px; margin: 30px auto; padding: 0 20px; }
        .card { background: #2c001a; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        .table-card { background: #2c001a; border-radius: 12px; padding: 25px; overflow-x: auto; }
        h2 { color: #ff4b6e; margin-bottom: 20px; border-left: 4px solid #ff4b6e; padding-left: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input { width: 100%; padding: 12px; border: 1px solid #660026; border-radius: 6px; background: #3d0023; color: #f4f4f4; font-size: 14px; }
        button { padding: 12px 25px; background: #ff4b6e; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        button:hover { background: #99001a; }
        .btn-back { background: #4caf50; text-decoration: none; display: inline-block; }
        .btn-back:hover { background: #45a049; }
        .save-btn { background: #4caf50; margin-top: 20px; width: auto; padding: 12px 30px; }
        .save-btn:hover { background: #45a049; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #3d0023; }
        th { background: #3d0023; color: #ff4b6e; }
        .delete-btn { color: #f44336; text-decoration: none; }
        .student-info { background: #3d0023; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .exam-buttons { display: flex; gap: 10px; margin: 20px 0; flex-wrap: wrap; }
        .exam-btn { background: #3d0023; border: 1px solid #ff4b6e; cursor: pointer; padding: 10px 20px; border-radius: 6px; }
        .exam-btn.active { background: #ff4b6e; color: white; }
        .row-3col { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; align-items: end; }
        @media (max-width: 768px) { .row-3col { grid-template-columns: 1fr; } }
        .marks-input { width: 100px; text-align: center; padding: 8px; border-radius: 6px; }
        .status-saved { color: #4caf50; font-weight: bold; }
        .status-missing { color: #888; }
        .note { background: #3d0023; padding: 10px; border-radius: 6px; margin-top: 15px; font-size: 12px; color: #888; }
        .percentage-bar { background: #3d0023; border-radius: 10px; height: 6px; overflow: hidden; width: 80px; }
        .percentage-fill { background: #4caf50; height: 100%; border-radius: 10px; }
        .marks-table { width: 100%; margin-top: 20px; }
        .marks-table th, .marks-table td { padding: 12px; text-align: left; }
    </style>
    <script>
        function showGroupOption() {
            var classId = document.getElementById('class_id').value;
            var groupDiv = document.getElementById('group_div');
            if (classId >= 9) {
                groupDiv.style.display = 'block';
            } else {
                groupDiv.style.display = 'none';
            }
        }
        
        function changeExam(examType) {
            document.getElementById('selected_exam').value = examType;
            document.getElementById('exam_change_form').submit();
        }
    </script>
</head>
<body>
    <div class="header">
        <h1>📝 Manage Student Marks</h1>
    </div>
    
    <div class="container">
        <?= $msg ?>
        
        <!-- Student Selection -->
        <div class="card">
            <h2>🔍 Select Student</h2>
            <form method="POST">
                <div class="row-3col">
                    <div class="form-group">
                        <label>Class:</label>
                        <select name="class_id" id="class_id" required onchange="showGroupOption()">
                            <option value="">-- Select --</option>
                            <?php 
                            $c_reset = sqlsrv_query($conn, "SELECT * FROM Class ORDER BY class_id");
                            while($c = sqlsrv_fetch_array($c_reset, SQLSRV_FETCH_ASSOC)): 
                            ?>
                                <option value="<?= $c['class_id'] ?>" <?= ($class_id == $c['class_id']) ? 'selected' : '' ?>>
                                    <?= $c['class_name'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" id="group_div" style="display: <?= ($class_id >= 9) ? 'block' : 'none' ?>;">
                        <label>Group (Class 9-10):</label>
                        <select name="group">
                            <option value="">-- None --</option>
                            <option value="Science" <?= ($group == 'Science') ? 'selected' : '' ?>>🔬 Science</option>
                            <option value="Commerce" <?= ($group == 'Commerce') ? 'selected' : '' ?>>💼 Commerce</option>
                            <option value="Arts" <?= ($group == 'Arts') ? 'selected' : '' ?>>🎨 Arts</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Student ID:</label>
                        <input type="number" name="student_id" placeholder="Enter ID (e.g., 101)" value="<?= $student_id ?>" required>
                    </div>
                </div>
                
                <input type="hidden" name="selected_exam" value="<?= $selected_exam ?>">
                <button type="submit" name="load_student">📋 Load Student</button>
            </form>
        </div>
        
        <?php if($student_info && !empty($subjects)): ?>
        
        <!-- Hidden form for changing exam (does NOT save) -->
        <form method="POST" id="exam_change_form">
            <input type="hidden" name="class_id" value="<?= $class_id ?>">
            <input type="hidden" name="student_id" value="<?= $student_id ?>">
            <input type="hidden" name="group" value="<?= $group ?>">
            <input type="hidden" name="selected_exam" id="selected_exam" value="<?= $selected_exam ?>">
            <input type="hidden" name="change_exam" value="1">
        </form>
        
        <!-- Marks Entry -->
        <div class="card">
            <div class="student-info">
                <strong>👤 Student:</strong> <?= htmlspecialchars($student_info['name']) ?> (ID: <?= $student_info['student_id'] ?>) | 
                <strong>📚 Class:</strong> <?= $student_info['class_name'] ?>
                <?php if($group): ?> | <strong>🎯 Group:</strong> <?= $group ?><?php endif; ?>
            </div>
            
            <label>📝 Select Exam Type:</label>
            <div class="exam-buttons">
                <button type="button" class="exam-btn <?= ($selected_exam == 'Quiz') ? 'active' : '' ?>" onclick="changeExam('Quiz')">📝 Quiz (10 marks)</button>
                <button type="button" class="exam-btn <?= ($selected_exam == 'Assignment') ? 'active' : '' ?>" onclick="changeExam('Assignment')">📋 Assignment (10 marks)</button>
                <button type="button" class="exam-btn <?= ($selected_exam == 'Midterm') ? 'active' : '' ?>" onclick="changeExam('Midterm')">📖 Midterm (20 marks)</button>
                <button type="button" class="exam-btn <?= ($selected_exam == 'Final') ? 'active' : '' ?>" onclick="changeExam('Final')">🎓 Final (70 marks)</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="class_id" value="<?= $class_id ?>">
                <input type="hidden" name="student_id" value="<?= $student_id ?>">
                <input type="hidden" name="group" value="<?= $group ?>">
                <input type="hidden" name="selected_exam" value="<?= $selected_exam ?>">
                
                <div class="note" style="margin-bottom: 15px;">
                    💡 Currently entering marks for: <strong><?= $selected_exam ?></strong> (Maximum <?= $exam_limits[$selected_exam] ?> marks per subject)
                </div>
                
                <table class="marks-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Marks (out of <?= $exam_limits[$selected_exam] ?>)</th>
                            <th>Status</th>
                        </thead>
                    </thead>
                    <tbody>
                        <?php foreach($subjects as $subject): 
                            $mark = isset($existing_marks[$subject['subject_id']][$selected_exam]) ? $existing_marks[$subject['subject_id']][$selected_exam] : '';
                        ?>
                        <tr>
                            <td><?= $subject['subject_name'] ?></td>
                            <td>
                                <input type="number" name="mark_<?= $subject['subject_id'] ?>" 
                                       value="<?= $mark ?>" 
                                       min="0" 
                                       max="<?= $exam_limits[$selected_exam] ?>" 
                                       class="marks-input">
                             </td
                            <td>
                                <?php if($mark !== ''): ?>
                                    <span class="status-saved">✓ Saved (<?= $mark ?>/<?= $exam_limits[$selected_exam] ?>)</span>
                                <?php else: ?>
                                    <span class="status-missing">Not entered</span>
                                <?php endif; ?>
                             </td
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <button type="submit" name="save_marks" class="save-btn">💾 Save <?= $selected_exam ?> Marks</button>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- All Marks Records -->
        <div class="table-card">
            <h2>📊 All Marks Records</h2>
            <?php if($all_marks && sqlsrv_has_rows($all_marks)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th><th>Class</th><th>Subject</th><th>Exam</th><th>Marks</th><th>Max</th><th>%</th><th>Action</th>
                        </thead>
                    </thead>
                    <tbody>
                        <?php while($row = sqlsrv_fetch_array($all_marks, SQLSRV_FETCH_ASSOC)): 
                            $max_mark = $exam_limits[$row['exam_type']];
                            $percent = ($max_mark > 0) ? round(($row['marks_obtained'] / $max_mark) * 100, 2) : 0;
                        ?>
                        <tr>
                            <td><?= $row['student_name'] ?> (ID: <?= $row['student_id'] ?>)</td
                            <td><?= $row['class_name'] ?></td
                            <td><?= $row['subject_name'] ?></td
                            <td><?= $row['exam_type'] ?></td
                            <td><?= $row['marks_obtained'] ?> </td
                            <td><?= $max_mark ?> </td
                            <td>
                                <div class="percentage-bar">
                                    <div class="percentage-fill" style="width: <?= min(100, $percent) ?>%"></div>
                                </div>
                                <?= $percent ?>%
                             </td
                            <td><a href="?delete=<?= $row['mark_id'] ?>" class="delete-btn" onclick="return confirm('Delete this mark?')">Delete</a></td
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align:center; color:#888;">No marks records found.</p>
            <?php endif; ?>
        </div>
        
        <div style="text-align:center; margin-top:20px;">
            <a href="teacher_dashboard.php" class="btn-back" style="padding:12px 25px;">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>