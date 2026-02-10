<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db_config.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];
    $role = $_POST['role'];

    $sql = "INSERT INTO Users (username, password, role) VALUES (?, ?, ?)";
    $params = array($user, $pass, $role);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        echo "<h3>Database Error:</h3>";
        die(print_r(sqlsrv_errors(), true));
    }

    echo "<div style='text-align:center; margin-top:50px; font-family: Arial;'>";
    echo "<h1 style='color:green;'>Registration Successful!</h1>";
    echo "<p>User <b>" . htmlspecialchars($user) . "</b> has been registered.</p>";
    echo "<a href='login.php' style='padding:10px 20px; background:#3498db; color:white; text-decoration:none; border-radius:5px;'>Go to Login Page</a>";
    echo "</div>";
}
?>