<?php
require_once 'db_config.php';

echo "<h2>Current Users in Database</h2>";

$sql = "SELECT user_id, username, password, role, email, full_name FROM Users";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Username</th><th>Password Hash</th><th>Role</th><th>Email</th></tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . $row['user_id'] . "</td>";
    echo "<td>" . $row['username'] . "</td>";
    echo "<td style='font-family: monospace; font-size: 11px;'>" . substr($row['password'], 0, 50) . "...</td>";
    echo "<td>" . $row['role'] . "</td>";
    echo "<td>" . ($row['email'] ?? 'N/A') . "</td>";
    echo "</tr>";
}
echo "</table>";
?>