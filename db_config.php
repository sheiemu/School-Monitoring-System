<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(session_status() == PHP_SESSION_NONE){ session_start(); }

// Use the exact server name from your SQL Server
// Open SSMS and check what server name appears in the login screen
$serverName = "DESKTOP-64LHB9P\\SQLEXPRESS";  
$connectionInfo = array(
    "Database"=>"StudentMonitoringDB",
    "TrustServerCertificate"=>true,
    "CharacterSet"=>"UTF-8"
);

$conn = sqlsrv_connect($serverName, $connectionInfo);

if(!$conn) {
    // Try without instance name
    $serverName = "localhost";
    $conn = sqlsrv_connect($serverName, $connectionInfo);
}

if(!$conn) {
    // Try with (local)
    $serverName = "(local)\\SQLEXPRESS";
    $conn = sqlsrv_connect($serverName, $connectionInfo);
}

if(!$conn) {
    die("Connection failed. Please check:<br>
    1. SQL Server is running<br>
    2. The database 'StudentMonitoringDB' exists<br>
    3. Your connection settings<br>
    Error: " . print_r(sqlsrv_errors(), true));
}

echo "Connected successfully!";
?>