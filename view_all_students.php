<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$selected_class = isset($_GET['class_id']) ? $_GET['class_id'] : '';
$msg = "";

// Get all classes for dropdown
$classes = sqlsrv_query($conn, "SELECT * FROM Class ORDER BY class_id");

// Get students based on selected class
if ($selected_class) {
    $students = sqlsrv_query($conn, "
        SELECT s.*, c.class_name 
        FROM Student s
        JOIN Class c ON s.class_id = c.class_id
        WHERE s.class_id = ?
        ORDER BY s.student_id
    ", array($selected_class));
} else {
    $students = sqlsrv_query($conn, "
        SELECT s.*, c.class_name 
        FROM Student s
        JOIN Class c ON s.class_id = c.class_id
        ORDER BY s.class_id, s.student_id
    ");
}

// Delete student if requested
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_sql = "DELETE FROM Student WHERE student_id = ?";
    $delete_stmt = sqlsrv_query($conn, $delete_sql, array($delete_id));
    if ($delete_stmt) {
        $msg = "<p style='color:green;'>✓ Student deleted successfully!</p>";
        // Refresh the page to update the list
        header("Location: view_all_students.php?class_id=" . $selected_class);
        exit();
    } else {
        $msg = "<p style='color:red;'>✗ Error deleting student!</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>All Students</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #1c0b14; color: #f4f4f4; }
        .header { background: #4b001f; padding: 20px; text-align: center; }
        .header h1 { color: #ff4b6e; margin: 0; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .filter-card { background: #2c001a; border-radius: 12px; padding: 20px; margin-bottom: 25px; }
        .filter-form { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
        .form-group { margin-bottom: 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, button { padding: 10px 15px; border-radius: 6px; border: none; font-size: 14px; }
        select { background: #3d0023; color: #f4f4f4; border: 1px solid #660026; min-width: 150px; }
        button { background: #ff4b6e; color: white; cursor: pointer; }
        button:hover { background: #99001a; }
        .btn-reset { background: #4caf50; }
        .btn-reset:hover { background: #45a049; }
        .table-card { background: #2c001a; border-radius: 12px; padding: 25px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #3d0023; }
        th { background: #3d0023; color: #ff4b6e; }
        .btn { display: inline-block; padding: 8px 15px; background: #ff4b6e; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .btn-small { padding: 5px 10px; font-size: 12px; margin: 0 2px; }
        .btn-view { background: #2196f3; }
        .btn-edit { background: #ff9800; }
        .btn-delete { background: #f44336; }
        .btn-small:hover { opacity: 0.8; }
        a { text-decoration: none; }
        .class-badge { background: #ff4b6e; color: white; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
        .no-data { text-align: center; color: #888; padding: 40px; }
        .stats { margin-top: 15px; padding: 10px; background: #3d0023; border-radius: 6px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 All Students</h1>
    </div>
    
    <div class="container">
        <?= $msg ?>
        
        <!-- Class Filter -->
        <div class="filter-card">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label>📚 Select Class:</label>
                    <select name="class_id">
                        <option value="">-- All Classes --</option>
                        <?php while($c = sqlsrv_fetch_array($classes, SQLSRV_FETCH_ASSOC)): ?>
                            <option value="<?= $c['class_id'] ?>" <?= $selected_class == $c['class_id'] ? 'selected' : '' ?>>
                                <?= $c['class_name'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit">🔍 Filter</button>
                <?php if($selected_class): ?>
                    <a href="view_all_students.php" class="btn-reset" style="padding: 10px 15px; background: #4caf50; color: white; border-radius: 6px;">⟳ Show All</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Students Table -->
        <div class="table-card">
            <h2 style="margin-bottom: 20px;">
                <?php if($selected_class): ?>
                    Students in <?php 
                        $class_name_sql = sqlsrv_query($conn, "SELECT class_name FROM Class WHERE class_id = ?", array($selected_class));
                        $class_row = sqlsrv_fetch_array($class_name_sql, SQLSRV_FETCH_ASSOC);
                        echo $class_row['class_name'];
                    ?>
                <?php else: ?>
                    All Students
                <?php endif; ?>
            </h2>
            
            <?php if(sqlsrv_has_rows($students)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Gender</th>
                            <th>DOB</th>
                            <th>Actions</th>
                        </thead>
                    </thead>
                    <tbody>
                        <?php while($s = sqlsrv_fetch_array($students, SQLSRV_FETCH_ASSOC)): ?>
                        <tr>
                            <td><?= $s['student_id'] ?></td>
                            <td><?= htmlspecialchars($s['name']) ?></td>
                            <td><span class="class-badge"><?= $s['class_name'] ?></span></td>
                            <td><?= $s['gender'] ?? 'N/A' ?></td>
                            <td><?= $s['date_of_birth'] ? $s['date_of_birth']->format('Y-m-d') : 'N/A' ?></td>
                            <td>
                                <a href="student_detail.php?id=<?= $s['student_id'] ?>" class="btn-small btn-view">View</a>
                                <a href="edit_student.php?id=<?= $s['student_id'] ?>" class="btn-small btn-edit">Edit</a>
                                <a href="?delete_id=<?= $s['student_id'] ?>&class_id=<?= $selected_class ?>" class="btn-small btn-delete" onclick="return confirm('Delete <?= htmlspecialchars($s['name']) ?>?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <?php 
                // Get total count for selected class
                $count_sql = "SELECT COUNT(*) as total FROM Student";
                if($selected_class) {
                    $count_sql .= " WHERE class_id = $selected_class";
                }
                $count_result = sqlsrv_query($conn, $count_sql);
                $count_row = sqlsrv_fetch_array($count_result, SQLSRV_FETCH_ASSOC);
                ?>
                <div class="stats">
                    📊 Total Students: <strong><?= $count_row['total'] ?></strong>
                </div>
                
            <?php else: ?>
                <div class="no-data">
                    <?php if($selected_class): ?>
                        No students found in this class. 
                        <a href="add_student.php" style="color:#ff4b6e;">Add a student</a>
                    <?php else: ?>
                        No students found. 
                        <a href="add_student.php" style="color:#ff4b6e;">Add your first student</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="teacher_dashboard.php" class="btn">← Back to Dashboard</a>
            <a href="add_student.php" class="btn" style="background: #4caf50; margin-left: 10px;">➕ Add New Student</a>
        </div>
    </div>
</body>
</html>