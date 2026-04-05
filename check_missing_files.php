<?php
$required_files = [
    'db_config.php',
    'login.php', 
    'logout.php',
    'teacher_dashboard.php',
    'add_student.php',
    'view_all_students.php',
    'edit_student.php',
    'manage_marks.php',
    'view_results.php',
    'attendance.php',
    'behaviour.php',
    'feedback.php',
    'analysis.php'
];

echo "<h2>File Check Results</h2>";
echo "<ul>";
foreach($required_files as $file) {
    if(file_exists($file)) {
        echo "<li style='color:green'>✓ $file - EXISTS</li>";
    } else {
        echo "<li style='color:red'>✗ $file - MISSING (This is why you get 404 error)</li>";
    }
}
echo "</ul>";
?>