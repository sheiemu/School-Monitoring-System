<?php
// Error reporting 
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection file call
require_once 'db_config.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Connection variable ensure
    global $conn; 

    if (!$conn) {
        die("Fatal Error: Connection object not found. Check db_config.php settings.");
    }

    $user = $_POST['username'];
    $pass = $_POST['password'];

    // SQL query
    $sql = "SELECT * FROM Users WHERE username = ? AND password = ?";
    $params = array($user, $pass);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    if (sqlsrv_has_rows($stmt)) {
        echo "<h1 style='color:green; text-align:center;'>Login Successful! Welcome, " . htmlspecialchars($user) . ".</h1>";
        echo "<p style='text-align:center;'><a href='index.php'>Go to Home</a></p>";
    } else {
        echo "<h1 style='color:red; text-align:center;'>Login Failed! Invalid Username or Password.</h1>";
        echo "<p style='text-align:center;'><a href='login.php'>Try Again</a></p>";
    }
}
?>