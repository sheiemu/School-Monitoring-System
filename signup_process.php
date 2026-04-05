<?php
require_once 'db_config.php';

if($_SERVER['REQUEST_METHOD']=='POST'){
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Check if username or email exists
    $check = sqlsrv_query($conn, "SELECT * FROM Users WHERE username = ? OR email = ?", array($username, $email));
    if(sqlsrv_has_rows($check)) {
        echo "<div style='text-align:center;margin-top:50px;'><h1 style='color:#ff4b6e;'>Error!</h1><p>Username or Email already exists!</p><a href='signup.php' style='padding:10px 20px;background:#500c3c;color:white;text-decoration:none;border-radius:5px;'>Try Again</a></div>";
    } else {
        $stmt = sqlsrv_query($conn, "INSERT INTO Users (username, email, password, role, full_name) VALUES (?, ?, ?, ?, ?)", 
            array($username, $email, $password, $role, $username));
        
        if($stmt === false){ 
            die(print_r(sqlsrv_errors(), true)); 
        }
        
        echo "<div style='text-align:center;margin-top:50px;'><h1 style='color:#4caf50;'>Registration Successful!</h1><p>User <b>".htmlspecialchars($username)."</b> registered.</p><a href='login.php' style='padding:10px 20px;background:#500c3c;color:white;text-decoration:none;border-radius:5px;'>Go to Login</a></div>";
    }
}
?>