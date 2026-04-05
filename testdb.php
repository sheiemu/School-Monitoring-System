<?php
require_once 'db_config.php';

echo "<h2>Database Connection Test</h2>";

// Test connection
if($conn) {
    echo "<p style='color:green;'>✓ Connected to database successfully!</p>";
} else {
    echo "<p style='color:red;'>✗ Connection failed!</p>";
    die(print_r(sqlsrv_errors(), true));
}

// Check Users table
$result = sqlsrv_query($conn, "SELECT * FROM Users");
if($result === false) {
    echo "<p style='color:red;'>Error reading Users table:</p>";
    print_r(sqlsrv_errors());
} else {
    echo "<h3>Users in database:</h3>";
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Email</th><th>Full Name</th></tr>";
    while($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "<td>" . (isset($row['email']) ? $row['email'] : 'N/A') . "</td>";
        echo "<td>" . (isset($row['full_name']) ? $row['full_name'] : 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check Student table
$result2 = sqlsrv_query($conn, "SELECT COUNT(*) as count FROM Student");
if($result2) {
    $row = sqlsrv_fetch_array($result2, SQLSRV_FETCH_ASSOC);
    echo "<p>Total Students: " . $row['count'] . "</p>";
}
?>