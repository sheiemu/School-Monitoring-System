<?php
session_start();
require_once 'db_config.php';
if($_SERVER['REQUEST_METHOD']=='POST'){
    $user=$_POST['username']; $pass=$_POST['password'];
    $stmt=sqlsrv_query($conn,"SELECT * FROM Users WHERE username=?",array($user));
    if($stmt===false){ die(print_r(sqlsrv_errors(),true)); }
    $row=sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC);
    if($row && password_verify($pass,$row['password'])){
        $_SESSION['user']=$row['username']; $_SESSION['role']=$row['role'];
        header("Location: dashboard.php"); exit();
    }else{
        echo "<h1 style='color:red;text-align:center;'>Login Failed!</h1><p style='text-align:center;'><a href='login.php'>Try Again</a></p>";
    }
}
?>