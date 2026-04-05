<?php
require_once 'db_config.php';

echo "<h2>Fixing Passwords</h2>";

// Generate correct password hashes
$admin_hash = password_hash('admin123', PASSWORD_DEFAULT);
$teacher_hash = password_hash('password', PASSWORD_DEFAULT);
$parent_hash = password_hash('parent123', PASSWORD_DEFAULT);

echo "<pre>";
echo "Admin hash: " . $admin_hash . "\n";
echo "Teacher hash: " . $teacher_hash . "\n";
echo "Parent hash: " . $parent_hash . "\n";
echo "</pre>";

// Update admin password
$sql1 = "UPDATE Users SET password = ? WHERE username = 'admin'";
$stmt1 = sqlsrv_query($conn, $sql1, array($admin_hash));
if ($stmt1) {
    echo "<p style='color:green'>✓ Admin password updated (admin123)</p>";
} else {
    echo "<p style='color:red'>✗ Failed to update admin</p>";
}

// Update teacher password
$sql2 = "UPDATE Users SET password = ? WHERE username = 'teacher1'";
$stmt2 = sqlsrv_query($conn, $sql2, array($teacher_hash));
if ($stmt2) {
    echo "<p style='color:green'>✓ Teacher password updated (password)</p>";
} else {
    echo "<p style='color:red'>✗ Failed to update teacher</p>";
}

// Update parent password
$sql3 = "UPDATE Users SET password = ? WHERE username = 'parent1'";
$stmt3 = sqlsrv_query($conn, $sql3, array($parent_hash));
if ($stmt3) {
    echo "<p style='color:green'>✓ Parent password updated (parent123)</p>";
} else {
    echo "<p style='color:red'>✗ Failed to update parent</p>";
}

echo "<hr>";
echo "<h3>Now try logging in with:</h3>";
echo "<ul>";
echo "<li><strong>Admin:</strong> username: admin, password: admin123</li>";
echo "<li><strong>Teacher:</strong> username: teacher1, password: password</li>";
echo "<li><strong>Parent:</strong> username: parent1, password: parent123</li>";
echo "</ul>";
?>