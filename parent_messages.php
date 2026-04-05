<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit();
}

$msg = "";
$reply_msg = "";

// Get all parent messages (where parent_id is not null)
$messages_sql = "SELECT n.notification_id, n.student_id, n.message, n.created_at, n.is_read, 
                        s.name as student_name, u.full_name as parent_name, u.email as parent_email
                 FROM Notifications n
                 JOIN Student s ON n.student_id = s.student_id
                 JOIN Users u ON n.parent_id = u.user_id
                 WHERE n.parent_id IS NOT NULL
                 ORDER BY n.created_at DESC";
$messages_result = sqlsrv_query($conn, $messages_sql);

// Handle reply to parent
if (isset($_POST['send_reply'])) {
    $student_id = $_POST['student_id'];
    $parent_id = $_POST['parent_id'];
    $reply_subject = $_POST['reply_subject'];
    $reply_message = $_POST['reply_message'];
    $original_message_id = $_POST['message_id'];
    
    $full_reply = "[TEACHER REPLY to: $reply_subject] " . $reply_message;
    
    // Store reply in Notifications (with parent_id to identify as teacher reply)
    $sql = "INSERT INTO Notifications (student_id, message, is_read, parent_id, created_at) VALUES (?, ?, 0, ?, GETDATE())";
    $stmt = sqlsrv_query($conn, $sql, array($student_id, $full_reply, $parent_id));
    
    if ($stmt) {
        $reply_msg = "<p style='color:green;'>✓ Reply sent to parent successfully!</p>";
        
        // Mark original message as read
        sqlsrv_query($conn, "UPDATE Notifications SET is_read = 1 WHERE notification_id = ?", array($original_message_id));
    } else {
        $reply_msg = "<p style='color:red;'>✗ Error sending reply!</p>";
    }
}

// Handle mark as read
if (isset($_GET['mark_read'])) {
    $msg_id = $_GET['mark_read'];
    sqlsrv_query($conn, "UPDATE Notifications SET is_read = 1 WHERE notification_id = ?", array($msg_id));
    header("Location: parent_messages.php");
    exit();
}

// Handle delete message
if (isset($_GET['delete'])) {
    $msg_id = $_GET['delete'];
    sqlsrv_query($conn, "DELETE FROM Notifications WHERE notification_id = ?", array($msg_id));
    header("Location: parent_messages.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Parent Messages</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #1c0b14; color: #f4f4f4; }
        .header { background: #4b001f; padding: 20px; text-align: center; }
        .header h1 { color: #ff4b6e; margin: 0; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .card { background: #2c001a; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        .message-card { background: #2c001a; border-radius: 12px; padding: 25px; margin-bottom: 25px; border-left: 4px solid #ff4b6e; }
        h2 { color: #ff4b6e; margin-bottom: 20px; border-left: 4px solid #ff4b6e; padding-left: 15px; }
        h3 { color: #ff4b6e; margin: 15px 0 10px 0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input, textarea { width: 100%; padding: 12px; border: 1px solid #660026; border-radius: 6px; background: #3d0023; color: #f4f4f4; font-size: 14px; }
        textarea { resize: vertical; min-height: 80px; }
        button { padding: 12px 25px; background: #ff4b6e; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; margin-top: 10px; }
        button:hover { background: #99001a; }
        .btn-back { display: inline-block; padding: 10px 20px; background: #4caf50; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; }
        .btn-back:hover { background: #45a049; }
        .message-meta { font-size: 12px; color: #888; margin: 10px 0; }
        .message-content { margin: 15px 0; padding: 15px; background: #3d0023; border-radius: 8px; }
        .reply-form { margin-top: 20px; padding-top: 20px; border-top: 1px solid #660026; display: none; }
        .reply-btn { background: #2196f3; margin-right: 10px; }
        .reply-btn:hover { background: #0b7dda; }
        .delete-btn { background: #f44336; }
        .delete-btn:hover { background: #b71c1c; }
        .read-badge { background: #4caf50; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
        .unread-badge { background: #ff4b6e; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
        .action-buttons { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px; }
        .no-messages { text-align: center; color: #888; padding: 40px; }
    </style>
    <script>
        function toggleReplyForm(messageId) {
            var form = document.getElementById('reply_form_' + messageId);
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }
    </script>
</head>
<body>
    <div class="header">
        <h1>📨 Messages from Parents</h1>
        <p>View and respond to parent feedback</p>
    </div>
    
    <div class="container">
        <?= $msg ?>
        <?= $reply_msg ?>
        
        <?php if(sqlsrv_has_rows($messages_result)): ?>
            <?php while($row = sqlsrv_fetch_array($messages_result, SQLSRV_FETCH_ASSOC)): ?>
                <div class="message-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                        <h3>
                            From: <?= htmlspecialchars($row['parent_name']) ?>
                            <span class="<?= $row['is_read'] ? 'read-badge' : 'unread-badge' ?>">
                                <?= $row['is_read'] ? '✓ Read' : '● New' ?>
                            </span>
                        </h3>
                        <div class="message-meta">
                            Student: <?= htmlspecialchars($row['student_name']) ?> (ID: <?= $row['student_id'] ?>)<br>
                            Parent Email: <?= htmlspecialchars($row['parent_email']) ?><br>
                            Received: <?= $row['created_at']->format('Y-m-d H:i:s') ?>
                        </div>
                    </div>
                    
                    <div class="message-content">
                        <strong>Message:</strong><br>
                        <?= nl2br(htmlspecialchars($row['message'])) ?>
                    </div>
                    
                    <div class="action-buttons">
                        <button class="reply-btn" onclick="toggleReplyForm(<?= $row['notification_id'] ?>)">💬 Reply to Parent</button>
                        <a href="?delete=<?= $row['notification_id'] ?>" class="delete-btn" style="padding: 12px 25px; text-decoration: none; border-radius: 6px;" onclick="return confirm('Delete this message?')">🗑 Delete</a>
                        <?php if(!$row['is_read']): ?>
                            <a href="?mark_read=<?= $row['notification_id'] ?>" style="padding: 12px 25px; background: #ff9800; text-decoration: none; border-radius: 6px; color: white;">✓ Mark as Read</a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Reply Form -->
                    <div id="reply_form_<?= $row['notification_id'] ?>" class="reply-form">
                        <h4>Send Reply to <?= htmlspecialchars($row['parent_name']) ?></h4>
                        <form method="POST">
                            <input type="hidden" name="student_id" value="<?= $row['student_id'] ?>">
                            <input type="hidden" name="parent_id" value="<?= $row['parent_id'] ?>">
                            <input type="hidden" name="message_id" value="<?= $row['notification_id'] ?>">
                            
                            <div class="form-group">
                                <label>Subject:</label>
                                <input type="text" name="reply_subject" placeholder="e.g., Regarding your message about..." required>
                            </div>
                            
                            <div class="form-group">
                                <label>Your Reply:</label>
                                <textarea name="reply_message" placeholder="Write your response here..." required></textarea>
                            </div>
                            
                            <button type="submit" name="send_reply">📤 Send Reply</button>
                            <button type="button" onclick="toggleReplyForm(<?= $row['notification_id'] ?>)" style="background: #666;">Cancel</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="card">
                <div class="no-messages">
                    <p>📭 No messages from parents yet.</p>
                    <p style="margin-top: 10px;">When parents send feedback, it will appear here.</p>
                </div>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center;">
            <a href="teacher_dashboard.php" class="btn-back">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>