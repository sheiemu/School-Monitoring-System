<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$msg = "";

// Get classes for dropdown
$classes = sqlsrv_query($conn, "SELECT * FROM Class ORDER BY class_id");

// Send feedback message
if (isset($_POST['send_feedback'])) {
    $class_id = $_POST['class_id'];
    $student_id = $_POST['student_id'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    
    // First verify student exists in the selected class
    $check_student = sqlsrv_query($conn, "SELECT s.*, c.class_name FROM Student s JOIN Class c ON s.class_id = c.class_id WHERE s.student_id = ? AND s.class_id = ?", 
        array($student_id, $class_id));
    
    if (!sqlsrv_has_rows($check_student)) {
        $msg = "<p style='color:red;'>✗ Student ID $student_id not found in the selected class!</p>";
    } else {
        $student = sqlsrv_fetch_array($check_student, SQLSRV_FETCH_ASSOC);
        
        $full_message = "[$subject] " . $message;
        $sql = "INSERT INTO Notifications (student_id, message, created_at) VALUES (?, ?, GETDATE())";
        $stmt = sqlsrv_query($conn, $sql, array($student_id, $full_message));
        
        if ($stmt) {
            $msg = "<p style='color:green;'>✓ Feedback sent successfully to {$student['name']} (ID: $student_id) in {$student['class_name']}!</p>";
        } else {
            $msg = "<p style='color:red;'>✗ Error sending feedback!</p>";
        }
    }
}

// Get all feedback messages
$feedback_sql = "SELECT n.*, s.name as student_name, c.class_name
                 FROM Notifications n
                 JOIN Student s ON n.student_id = s.student_id
                 JOIN Class c ON s.class_id = c.class_id
                 ORDER BY n.created_at DESC";
$feedback_result = sqlsrv_query($conn, $feedback_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Feedback System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #1c0b14; color: #f4f4f4; }
        .header { background: #4b001f; padding: 20px; text-align: center; }
        .header h1 { color: #ff4b6e; margin: 0; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; display: flex; gap: 30px; flex-wrap: wrap; }
        .form-card { flex: 1; min-width: 380px; background: #2c001a; border-radius: 12px; padding: 25px; }
        .list-card { flex: 2; min-width: 500px; background: #2c001a; border-radius: 12px; padding: 25px; }
        h2 { color: #ff4b6e; margin-bottom: 20px; border-left: 4px solid #ff4b6e; padding-left: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, textarea, input { width: 100%; padding: 12px; border: 1px solid #660026; border-radius: 6px; background: #3d0023; color: #f4f4f4; font-size: 14px; }
        textarea { resize: vertical; min-height: 100px; }
        button { width: 100%; padding: 12px; background: #ff4b6e; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        button:hover { background: #99001a; }
        .message-item { background: #3d0023; border-radius: 8px; padding: 15px; margin-bottom: 15px; border-left: 4px solid #ff4b6e; }
        .message-subject { font-weight: bold; color: #ff4b6e; margin-bottom: 8px; }
        .message-text { margin-bottom: 8px; line-height: 1.5; }
        .message-meta { font-size: 12px; color: #888; margin-top: 8px; }
        .btn-back { display: inline-block; padding: 10px 20px; background: #4caf50; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; text-align: center; }
        .btn-back:hover { background: #45a049; }
        .unread { color: #ff4b6e; }
        .read { color: #4caf50; }
        .no-data { text-align: center; color: #888; padding: 40px; }
        .row-feedback { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        @media (max-width: 768px) { .row-feedback { grid-template-columns: 1fr; } }
        .id-input { font-size: 18px; font-weight: bold; }
        .note { background: #3d0023; padding: 10px; border-radius: 6px; margin-top: 15px; font-size: 12px; color: #888; }
    </style>
</head>
<body>
    <div class="header">
        <h1>💬 Teacher-Parent Feedback System</h1>
        <p>Send messages and notifications to parents/students</p>
    </div>

    <div class="container">
        <div class="form-card">
            <h2>📝 Send New Feedback</h2>
            <?php echo $msg; ?>
            <form method="POST">
                <div class="row-feedback">
                    <div class="form-group">
                        <label>Select Class:</label>
                        <select name="class_id" required>
                            <option value="">-- Select Class --</option>
                            <?php while($c = sqlsrv_fetch_array($classes, SQLSRV_FETCH_ASSOC)): ?>
                                <option value="<?= $c['class_id'] ?>"><?= $c['class_name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Enter Student ID:</label>
                        <input type="number" name="student_id" placeholder="Type Student ID (e.g., 39)" class="id-input" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Subject/Topic:</label>
                    <input type="text" name="subject" placeholder="e.g., Academic Progress, Behaviour, Attendance" required>
                </div>

                <div class="form-group">
                    <label>Message:</label>
                    <textarea name="message" placeholder="Write your feedback message here..." required></textarea>
                </div>

                <button type="submit" name="send_feedback">📤 Send Feedback</button>
            </form>
            <div class="note">
                💡 How to use: First select the Class, then type the Student ID number (e.g., 39), then add Subject and Message.
            </div>
        </div>

        <div class="list-card">
            <h2>📨 Recent Feedback Messages</h2>
            <?php 
            $has_messages = false;
            while($row = sqlsrv_fetch_array($feedback_result, SQLSRV_FETCH_ASSOC)): 
                $has_messages = true;
            ?>
                <div class="message-item">
                    <div class="message-subject">
                        To: <?= htmlspecialchars($row['student_name']) ?> 
                        (ID: <?= $row['student_id'] ?>, Class: <?= $row['class_name'] ?>)
                    </div>
                    <div class="message-text">
                        <?= htmlspecialchars($row['message']) ?>
                    </div>
                    <div class="message-meta">
                        Sent: <?= $row['created_at'] ? $row['created_at']->format('Y-m-d H:i:s') : 'Just now' ?>
                        <?php if($row['is_read'] == 0): ?>
                            <span class="unread">● Unread</span>
                        <?php else: ?>
                            <span class="read">✓ Read</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
            
            <?php if(!$has_messages): ?>
                <div class="no-data">No feedback messages sent yet.</div>
            <?php endif; ?>
        </div>
    </div>

    <div style="text-align: center; margin-bottom: 30px;">
        <a href="teacher_dashboard.php" class="btn-back">← Back to Dashboard</a>
    </div>
</body>
</html>