<?php
session_start(); // Session start kora joruri
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'db_config.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    global $conn; 

    if (!$conn) {
        die("Fatal Error: Connection object not found.");
    }

    $user = $_POST['username'];
    $pass = $_POST['password'];

    $sql = "SELECT * FROM Users WHERE username = ? AND password = ?";
    $params = array($user, $pass);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    if (sqlsrv_has_rows($stmt)) {
        // User data session-e save kora
        $_SESSION['user'] = $user;
        // Dashboard-e redirect kora
        header("Location: dashboard.php");
        exit();
    } else {
        echo "<h1 style='color:red; text-align:center;'>Login Failed!</h1>";
        echo "<p style='text-align:center;'><a href='login.php'>Try Again</a></p>";
    }
}
?>
