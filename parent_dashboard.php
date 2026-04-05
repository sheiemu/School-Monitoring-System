<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parent') {
    header("Location: login.php");
    exit();
}

$student_info = null;
$midterm_marks = [];
$final_marks = [];
$quiz_marks = [];
$assignment_marks = [];
$attendance_records = [];
$behaviour_records = [];
$notifications = [];
$parent_feedback = [];
$teacher_replies = [];
$error_msg = "";
$search_performed = false;
$feedback_msg = "";

// Get classes for dropdown
$classes = sqlsrv_query($conn, "SELECT * FROM Class ORDER BY class_id");

// Handle search
if (isset($_POST['search_student'])) {
    $class_id = $_POST['class_id'];
    $student_id = $_POST['student_id'];
    $group = isset($_POST['group']) ? $_POST['group'] : '';
    $search_performed = true;
    
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
        
        // Store in session for feedback
        $_SESSION['viewing_student_id'] = $student_id;
        $_SESSION['viewing_class_id'] = $class_id;
        
        // Fetch Quiz Marks
        $quiz_sql = "SELECT sub.subject_name, m.marks_obtained
                     FROM Marks m 
                     JOIN Subject sub ON m.subject_id = sub.subject_id 
                     WHERE m.student_id = ? AND m.exam_type = 'Quiz'
                     ORDER BY sub.subject_name";
        $quiz_stmt = sqlsrv_query($conn, $quiz_sql, array($student_id));
        while($row = sqlsrv_fetch_array($quiz_stmt, SQLSRV_FETCH_ASSOC)) {
            $quiz_marks[] = $row;
        }
        
        // Fetch Assignment Marks
        $assign_sql = "SELECT sub.subject_name, m.marks_obtained
                       FROM Marks m 
                       JOIN Subject sub ON m.subject_id = sub.subject_id 
                       WHERE m.student_id = ? AND m.exam_type = 'Assignment'
                       ORDER BY sub.subject_name";
        $assign_stmt = sqlsrv_query($conn, $assign_sql, array($student_id));
        while($row = sqlsrv_fetch_array($assign_stmt, SQLSRV_FETCH_ASSOC)) {
            $assignment_marks[] = $row;
        }
        
        // Fetch Midterm Marks
        $mid_sql = "SELECT sub.subject_name, m.marks_obtained
                    FROM Marks m 
                    JOIN Subject sub ON m.subject_id = sub.subject_id 
                    WHERE m.student_id = ? AND m.exam_type = 'Midterm'
                    ORDER BY sub.subject_name";
        $mid_stmt = sqlsrv_query($conn, $mid_sql, array($student_id));
        while($row = sqlsrv_fetch_array($mid_stmt, SQLSRV_FETCH_ASSOC)) {
            $midterm_marks[] = $row;
        }
        
        // Fetch Final Marks
        $final_sql = "SELECT sub.subject_name, m.marks_obtained
                      FROM Marks m 
                      JOIN Subject sub ON m.subject_id = sub.subject_id 
                      WHERE m.student_id = ? AND m.exam_type = 'Final'
                      ORDER BY sub.subject_name";
        $final_stmt = sqlsrv_query($conn, $final_sql, array($student_id));
        while($row = sqlsrv_fetch_array($final_stmt, SQLSRV_FETCH_ASSOC)) {
            $final_marks[] = $row;
        }
        
        // Fetch Attendance
        $att_sql = "SELECT attendance_date, status 
                    FROM Attendance 
                    WHERE student_id = ?
                    ORDER BY attendance_date DESC";
        $att_stmt = sqlsrv_query($conn, $att_sql, array($student_id));
        if ($att_stmt) {
            while($row = sqlsrv_fetch_array($att_stmt, SQLSRV_FETCH_ASSOC)) {
                $attendance_records[] = $row;
            }
        }
        
        // Fetch Behaviour
        $beh_sql = "SELECT description, score, behaviour_date 
                    FROM Behaviour 
                    WHERE student_id = ?
                    ORDER BY behaviour_date DESC";
        $beh_stmt = sqlsrv_query($conn, $beh_sql, array($student_id));
        if ($beh_stmt) {
            while($row = sqlsrv_fetch_array($beh_stmt, SQLSRV_FETCH_ASSOC)) {
                $behaviour_records[] = $row;
            }
        }
        
        // Fetch Notifications (Teacher Feedback)
        $notif_sql = "SELECT message, created_at, is_read 
                      FROM Notifications 
                      WHERE student_id = ? AND (parent_id IS NULL OR parent_id = 0)
                      ORDER BY created_at DESC";
        $notif_stmt = sqlsrv_query($conn, $notif_sql, array($student_id));
        if ($notif_stmt) {
            while($row = sqlsrv_fetch_array($notif_stmt, SQLSRV_FETCH_ASSOC)) {
                $notifications[] = $row;
            }
        }
        
        // Fetch Parent Feedback sent to teachers
        $feedback_sql = "SELECT message, created_at 
                         FROM Notifications 
                         WHERE student_id = ? AND parent_id = ?
                         ORDER BY created_at DESC";
        $feedback_stmt = sqlsrv_query($conn, $feedback_sql, array($student_id, $_SESSION['user_id']));
        if ($feedback_stmt) {
            while($row = sqlsrv_fetch_array($feedback_stmt, SQLSRV_FETCH_ASSOC)) {
                $parent_feedback[] = $row;
            }
        }
        
        // Fetch Teacher Replies
        $replies_sql = "SELECT message, created_at 
                        FROM Notifications 
                        WHERE parent_id = ? AND message LIKE '[TEACHER REPLY%'
                        ORDER BY created_at DESC";
        $replies_stmt = sqlsrv_query($conn, $replies_sql, array($_SESSION['user_id']));
        if ($replies_stmt) {
            while($reply = sqlsrv_fetch_array($replies_stmt, SQLSRV_FETCH_ASSOC)) {
                $teacher_replies[] = $reply;
            }
        }
    }
}

// Handle sending feedback to teacher
if (isset($_POST['send_feedback'])) {
    $student_id = $_POST['student_id'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $parent_id = $_SESSION['user_id'];
    
    $full_message = "[PARENT FEEDBACK - $subject] " . $message;
    
    $sql = "INSERT INTO Notifications (student_id, message, is_read, parent_id, created_at) VALUES (?, ?, 0, ?, GETDATE())";
    $stmt = sqlsrv_query($conn, $sql, array($student_id, $full_message, $parent_id));
    
    if ($stmt) {
        $feedback_msg = "<p style='color:green;'>✓ Your feedback has been sent to the teacher successfully!</p>";
        
        // Refresh parent feedback list
        $feedback_stmt = sqlsrv_query($conn, "SELECT message, created_at FROM Notifications WHERE student_id = ? AND parent_id = ? ORDER BY created_at DESC", 
            array($student_id, $parent_id));
        $parent_feedback = [];
        if ($feedback_stmt) {
            while($row = sqlsrv_fetch_array($feedback_stmt, SQLSRV_FETCH_ASSOC)) {
                $parent_feedback[] = $row;
            }
        }
    } else {
        $feedback_msg = "<p style='color:red;'>✗ Error sending feedback. Please try again.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Parent Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #1c0b14; color: #f4f4f4; }
        .header { background: #4b001f; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .header h1 { color: #ff4b6e; }
        .container { max-width: 1400px; margin: 30px auto; padding: 0 20px; }
        .card { background: #2c001a; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        .search-card { background: #2c001a; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        .two-columns { display: flex; gap: 25px; flex-wrap: wrap; }
        .column { flex: 1; min-width: 300px; }
        h2 { color: #ff4b6e; margin-bottom: 20px; border-left: 4px solid #ff4b6e; padding-left: 15px; }
        h3 { color: #ff4b6e; margin: 20px 0 15px 0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input, textarea { width: 100%; padding: 12px; border: 1px solid #660026; border-radius: 6px; background: #3d0023; color: #f4f4f4; font-size: 14px; }
        textarea { resize: vertical; min-height: 100px; }
        button { padding: 12px 25px; background: #ff4b6e; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; margin-top: 10px; }
        button:hover { background: #99001a; }
        .logout-btn { background: #99001a; color: white; padding: 8px 20px; border-radius: 6px; text-decoration: none; }
        .logout-btn:hover { background: #ff4b6e; }
        .btn-back { display: inline-block; padding: 10px 20px; background: #4caf50; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; }
        .btn-back:hover { background: #45a049; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #3d0023; }
        th { background: #3d0023; color: #ff4b6e; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .info-item { background: #3d0023; padding: 15px; border-radius: 8px; }
        .error-box { background: #b71c1c; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .row-3col { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; align-items: end; }
        @media (max-width: 768px) { .row-3col { grid-template-columns: 1fr; } }
        .score-high { color: #4caf50; font-weight: bold; }
        .score-medium { color: #ff9800; font-weight: bold; }
        .score-low { color: #f44336; font-weight: bold; }
        .present { color: #4caf50; }
        .absent { color: #f44336; }
        .notification-item, .feedback-item { background: #3d0023; border-left: 4px solid #ff4b6e; padding: 15px; margin-bottom: 10px; border-radius: 6px; }
        .notification-date, .feedback-date { font-size: 11px; color: #888; margin-top: 5px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-box { background: #3d0023; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-number { font-size: 28px; font-weight: bold; color: #ff4b6e; }
        .stat-label { font-size: 12px; color: #888; margin-top: 5px; }
        .percentage-bar { background: #3d0023; border-radius: 10px; height: 8px; overflow: hidden; width: 100px; }
        .percentage-fill { background: #4caf50; height: 100%; border-radius: 10px; }
        .note { background: #3d0023; padding: 10px; border-radius: 6px; margin-top: 15px; font-size: 12px; color: #888; }
        .feedback-form { background: #3d0023; padding: 20px; border-radius: 8px; margin-top: 20px; }
        .feedback-item-parent { border-left-color: #4caf50; }
        .reply-item { border-left-color: #2196f3; }
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
    </script>
</head>
<body>
    <div class="header">
        <h1>👪 Parent Dashboard</h1>
        <div>
            <span style="margin-right: 15px;">Welcome, <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Search Form -->
        <div class="search-card">
            <h2>🔍 Search Your Child</h2>
            <form method="POST">
                <div class="row-3col">
                    <div class="form-group">
                        <label>Select Class:</label>
                        <select name="class_id" id="class_id" required onchange="showGroupOption()">
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
                    
                    <div class="form-group" id="group_div" style="display: none;">
                        <label>Group (Class 9-10):</label>
                        <select name="group">
                            <option value="">-- Select Group --</option>
                            <option value="Science" <?= (isset($_POST['group']) && $_POST['group'] == 'Science') ? 'selected' : '' ?>>🔬 Science</option>
                            <option value="Commerce" <?= (isset($_POST['group']) && $_POST['group'] == 'Commerce') ? 'selected' : '' ?>>💼 Commerce</option>
                            <option value="Arts" <?= (isset($_POST['group']) && $_POST['group'] == 'Arts') ? 'selected' : '' ?>>🎨 Arts</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Enter Student ID:</label>
                        <input type="number" name="student_id" placeholder="Type Student ID (e.g., 101)" required value="<?= isset($_POST['student_id']) ? $_POST['student_id'] : '' ?>">
                    </div>
                </div>
                <button type="submit" name="search_student">🔍 View Child's Progress</button>
            </form>
            <div class="note">
                💡 Enter your child's Student ID and Class to view their academic progress, attendance, behaviour, and teacher feedback.
            </div>
        </div>
        
        <?php if($error_msg): ?>
            <div class="error-box">❌ <?= $error_msg ?></div>
        <?php endif; ?>
        
        <?php if($student_info): ?>
            <!-- Student Information -->
            <div class="card">
                <h2>👤 Child Information</h2>
                <div class="info-grid">
                    <div class="info-item"><strong>Student ID:</strong> <?= $student_info['student_id'] ?></div>
                    <div class="info-item"><strong>Name:</strong> <?= htmlspecialchars($student_info['name']) ?></div>
                    <div class="info-item"><strong>Class:</strong> <?= $student_info['class_name'] ?></div>
                    <div class="info-item"><strong>Gender:</strong> <?= $student_info['gender'] ?? 'N/A' ?></div>
                    <div class="info-item"><strong>Date of Birth:</strong> <?= $student_info['date_of_birth'] ? $student_info['date_of_birth']->format('Y-m-d') : 'N/A' ?></div>
                </div>
            </div>
            
            <!-- Two Columns for Marks and Feedback -->
            <div class="two-columns">
                <!-- Left Column: Academic Results -->
                <div class="column">
                    <!-- Quiz Results -->
                    <?php if(count($quiz_marks) > 0): ?>
                    <div class="card">
                        <h2>📝 Quiz Results (10 marks each)</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Marks</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($quiz_marks as $q): 
                                    $percent = round(($q['marks_obtained'] / 10) * 100, 2);
                                    $grade_class = ($percent >= 60) ? 'score-high' : (($percent >= 40) ? 'score-medium' : 'score-low');
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($q['subject_name']) ?></td>
                                    <td><?= $q['marks_obtained'] ?> / 10</td>
                                    <td class="<?= $grade_class ?>"><?= $percent ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Assignment Results -->
                    <?php if(count($assignment_marks) > 0): ?>
                    <div class="card">
                        <h2>📋 Assignment Results (10 marks each)</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Marks</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($assignment_marks as $a): 
                                    $percent = round(($a['marks_obtained'] / 10) * 100, 2);
                                    $grade_class = ($percent >= 60) ? 'score-high' : (($percent >= 40) ? 'score-medium' : 'score-low');
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['subject_name']) ?></td>
                                    <td><?= $a['marks_obtained'] ?> / 10</td>
                                    <td class="<?= $grade_class ?>"><?= $percent ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Midterm Results -->
                    <?php if(count($midterm_marks) > 0): ?>
                    <div class="card">
                        <h2>📖 Midterm Results (20 marks each)</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Marks</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($midterm_marks as $m): 
                                    $percent = round(($m['marks_obtained'] / 20) * 100, 2);
                                    $grade_class = ($percent >= 60) ? 'score-high' : (($percent >= 40) ? 'score-medium' : 'score-low');
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($m['subject_name']) ?></td>
                                    <td><?= $m['marks_obtained'] ?> / 20</td>
                                    <td class="<?= $grade_class ?>"><?= $percent ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Final Results -->
                    <?php if(count($final_marks) > 0): ?>
                    <div class="card">
                        <h2>🎓 Final Results (70 marks each)</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Marks</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($final_marks as $f): 
                                    $percent = round(($f['marks_obtained'] / 70) * 100, 2);
                                    $grade_class = ($percent >= 60) ? 'score-high' : (($percent >= 40) ? 'score-medium' : 'score-low');
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($f['subject_name']) ?></td>
                                    <td><?= $f['marks_obtained'] ?> / 70</td>
                                    <td class="<?= $grade_class ?>"><?= $percent ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Attendance Records -->
                    <div class="card">
                        <h2>📅 Attendance Records</h2>
                        <?php 
                        $total_days = count($attendance_records);
                        $present_days = 0;
                        foreach($attendance_records as $att) {
                            if($att['status'] == 'Present') $present_days++;
                        }
                        $att_percent = $total_days > 0 ? round(($present_days / $total_days) * 100, 2) : 0;
                        ?>
                        <div class="stats-grid">
                            <div class="stat-box">
                                <div class="stat-number"><?= $present_days ?> / <?= $total_days ?></div>
                                <div class="stat-label">Present / Total Days</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-number"><?= $att_percent ?>%</div>
                                <div class="stat-label">Attendance Rate</div>
                            </div>
                        </div>
                        <?php if(count($attendance_records) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($attendance_records as $att): ?>
                                <tr>
                                    <td><?= $att['attendance_date']->format('Y-m-d') ?></td>
                                    <td class="<?= strtolower($att['status']) ?>"><?= $att['status'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                            <p style="color:#888;">No attendance records found.</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Behaviour Records -->
                    <div class="card">
                        <h2>⭐ Behaviour Records</h2>
                        <?php if(count($behaviour_records) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($behaviour_records as $b): ?>
                                <tr>
                                    <td><?= $b['behaviour_date']->format('Y-m-d') ?></td>
                                    <td><?= htmlspecialchars($b['description']) ?></td>
                                    <td><?= $b['score'] ?>/5</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                            <p style="color:#888;">No behaviour records found.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right Column: Feedback Section -->
                <div class="column">
                    <!-- Teacher Feedback -->
                    <div class="card">
                        <h2>📬 Messages from Teachers</h2>
                        <?php if(count($notifications) > 0): ?>
                            <?php foreach($notifications as $n): ?>
                                <div class="notification-item">
                                    <?= htmlspecialchars($n['message']) ?>
                                    <div class="notification-date"><?= $n['created_at']->format('Y-m-d H:i') ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color:#888;">No messages from teachers yet.</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Teacher Replies -->
                    <div class="card">
                        <h2>📨 Replies from Teachers</h2>
                        <?php if(count($teacher_replies) > 0): ?>
                            <?php foreach($teacher_replies as $reply): ?>
                                <div class="notification-item reply-item">
                                    <?= htmlspecialchars($reply['message']) ?>
                                    <div class="notification-date"><?= $reply['created_at']->format('Y-m-d H:i') ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color:#888;">No replies from teachers yet.</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Send Feedback to Teacher -->
                    <div class="card">
                        <h2>💬 Send Message to Teacher</h2>
                        <?= $feedback_msg ?>
                        <form method="POST">
                            <input type="hidden" name="student_id" value="<?= $student_info['student_id'] ?>">
                            <div class="form-group">
                                <label>Subject:</label>
                                <input type="text" name="subject" placeholder="e.g., Academic Concern, Behaviour, Attendance, General Query" required>
                            </div>
                            <div class="form-group">
                                <label>Your Message:</label>
                                <textarea name="message" placeholder="Write your message to the teacher here..." required></textarea>
                            </div>
                            <button type="submit" name="send_feedback">📤 Send to Teacher</button>
                        </form>
                        <div class="note">
                            💡 Your message will be sent to the teacher. They will be able to see it and respond.
                        </div>
                    </div>
                    
                    <!-- Your Sent Feedback History -->
                    <div class="card">
                        <h2>📨 Your Sent Messages</h2>
                        <?php if(count($parent_feedback) > 0): ?>
                            <?php foreach($parent_feedback as $pf): ?>
                                <div class="notification-item feedback-item-parent">
                                    <?= htmlspecialchars($pf['message']) ?>
                                    <div class="feedback-date">Sent: <?= $pf['created_at']->format('Y-m-d H:i') ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color:#888;">No messages sent yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
        <?php elseif($search_performed && !$student_info): ?>
            <div class="error-box">❌ No student found. Please check the Class and Student ID and try again.</div>
        <?php endif; ?>
        
        <div style="text-align: center;">
            <a href="logout.php" class="btn-back">← Logout</a>
        </div>
    </div>
    
    <script>
        function initGroupOption() {
            var classId = document.getElementById('class_id').value;
            var groupDiv = document.getElementById('group_div');
            if (classId >= 9) {
                groupDiv.style.display = 'block';
            } else {
                groupDiv.style.display = 'none';
            }
        }
        initGroupOption();
    </script>
</body>
</html>