<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$msg = "";

// Handle Add Behaviour by Student ID
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_behaviour'])) {
    $student_id = $_POST['student_id'];
    $description = $_POST['description'];
    $score = $_POST['score'];
    
    // Check if student exists
    $check_student = sqlsrv_query($conn, "SELECT name FROM Student WHERE student_id = ?", array($student_id));
    if (!sqlsrv_has_rows($check_student)) {
        $msg = "<p style='color:red;'>✗ Student ID $student_id not found!</p>";
    } else {
        $student = sqlsrv_fetch_array($check_student, SQLSRV_FETCH_ASSOC);
        
        $sql = "INSERT INTO Behaviour (student_id, description, score, behaviour_date) VALUES (?, ?, ?, GETDATE())";
        $stmt = sqlsrv_query($conn, $sql, array($student_id, $description, $score));
        
        if ($stmt) {
            $msg = "<p style='color:green;'>✓ Behaviour record saved for {$student['name']} (ID: $student_id)!</p>";
            // Add notification for parent
            $notify_msg = "Behaviour record added: " . substr($description, 0, 50);
            sqlsrv_query($conn, "INSERT INTO Notifications (student_id, message) VALUES (?, ?)", 
                array($student_id, $notify_msg));
        } else {
            $msg = "<p style='color:red;'>✗ Error saving behaviour record!</p>";
        }
    }
}

// Handle Delete Behaviour
if (isset($_GET['delete'])) {
    $behaviour_id = $_GET['delete'];
    $stmt = sqlsrv_query($conn, "DELETE FROM Behaviour WHERE behaviour_id = ?", array($behaviour_id));
    if ($stmt) {
        $msg = "<p style='color:green;'>✓ Behaviour record deleted!</p>";
    }
}

// Get behaviour history for display
$behaviour_history = sqlsrv_query($conn, "
    SELECT b.behaviour_id, s.student_id, s.name as student_name, b.description, b.score, b.behaviour_date
    FROM Behaviour b
    JOIN Student s ON b.student_id = s.student_id
    ORDER BY b.behaviour_date DESC
");

// Get behaviour statistics
$stats = sqlsrv_query($conn, "
    SELECT 
        AVG(score) as avg_score,
        COUNT(*) as total_records,
        MAX(score) as max_score,
        MIN(score) as min_score
    FROM Behaviour
    WHERE score IS NOT NULL
");
$stats_row = sqlsrv_fetch_array($stats, SQLSRV_FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Behaviour Management</title>
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
        select, textarea, input { width: 100%; padding: 12px; border: 1px solid #660026; border-radius: 6px; background: #3d0023; color: #f4f4f4; font-size: 14px; }
        textarea { resize: vertical; min-height: 80px; }
        button { padding: 12px 25px; background: #ff4b6e; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        button:hover { background: #99001a; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-box { background: #3d0023; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-number { font-size: 32px; font-weight: bold; color: #ff4b6e; }
        .stat-label { font-size: 12px; color: #888; margin-top: 5px; }
        .score-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .score-5 { background: #4caf50; color: white; }
        .score-4 { background: #8bc34a; color: white; }
        .score-3 { background: #ff9800; color: white; }
        .score-2 { background: #f44336; color: white; }
        .score-1 { background: #b71c1c; color: white; }
        .btn-back { display: inline-block; padding: 10px 20px; background: #4caf50; color: white; text-decoration: none; border-radius: 6px; margin-top: 10px; }
        .btn-back:hover { background: #45a049; }
        .delete-btn { color: #f44336; text-decoration: none; margin-left: 10px; }
        .delete-btn:hover { text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #3d0023; }
        th { background: #3d0023; color: #ff4b6e; }
        .note { background: #3d0023; padding: 10px; border-radius: 6px; margin-top: 15px; font-size: 12px; color: #888; }
        .id-input { font-size: 18px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>⭐ Behaviour Management System</h1>
    </div>
    
    <div class="container">
        <?= $msg ?>
        
        <!-- Statistics -->
        <div class="card">
            <h2>📊 Behaviour Statistics</h2>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-number"><?= number_format($stats_row['avg_score'] ?? 0, 1) ?></div>
                    <div class="stat-label">Average Score (1-5)</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= $stats_row['total_records'] ?? 0 ?></div>
                    <div class="stat-label">Total Records</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= $stats_row['max_score'] ?? 0 ?></div>
                    <div class="stat-label">Highest Score</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= $stats_row['min_score'] ?? 0 ?></div>
                    <div class="stat-label">Lowest Score</div>
                </div>
            </div>
        </div>
        
        <!-- Add Behaviour Record by Student ID -->
        <div class="card">
            <h2>➕ Add Behaviour Record</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Enter Student ID:</label>
                    <input type="number" name="student_id" placeholder="Enter Student ID (e.g., 1, 2, 3...)" class="id-input" required>
                </div>
                
                <div class="form-group">
                    <label>Behaviour Description:</label>
                    <textarea name="description" placeholder="Describe the student's behaviour..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Behaviour Score (1-5):</label>
                    <select name="score" required>
                        <option value="">-- Select Score --</option>
                        <option value="5">5 - Excellent - Outstanding behaviour</option>
                        <option value="4">4 - Good - Very good behaviour</option>
                        <option value="3">3 - Average - Satisfactory behaviour</option>
                        <option value="2">2 - Needs Improvement - Behaviour needs work</option>
                        <option value="1">1 - Poor - Concerning behaviour</option>
                    </select>
                </div>
                
                <button type="submit" name="add_behaviour">Save Behaviour Record</button>
            </form>
            <div class="note">
                💡 Tip: Just enter the Student ID number. Example: 1, 2, 3, etc.<br>
                Score Guide: 5=Excellent, 4=Good, 3=Average, 2=Needs Improvement, 1=Poor
            </div>
        </div>
        
        <!-- Behaviour History -->
        <div class="card">
            <h2>📋 Behaviour History</h2>
            <?php if(sqlsrv_has_rows($behaviour_history)): ?>
                <table>
                    <thead>
                        <tr><th>Student ID</th><th>Student Name</th><th>Description</th><th>Score</th><th>Date</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php while($row = sqlsrv_fetch_array($behaviour_history, SQLSRV_FETCH_ASSOC)): ?>
                        <tr>
                            <td><?= $row['student_id'] ?></td>
                            <td><?= htmlspecialchars($row['student_name']) ?></td>
                            <td><?= htmlspecialchars($row['description']) ?></td>
                            <td>
                                <span class="score-badge score-<?= $row['score'] ?>">
                                    <?= $row['score'] ?>/5
                                </span>
                            </td>
                            <td><?= $row['behaviour_date']->format('Y-m-d H:i') ?></td>
                            <td>
                                <a href="?delete=<?= $row['behaviour_id'] ?>" class="delete-btn" onclick="return confirm('Delete this record?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align:center; color:#888;">No behaviour records found. Add some records above!</p>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center;">
            <a href="teacher_dashboard.php" class="btn-back">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>