<?php
// Auto-detect server name
$serverName = ".\\SQLEXPRESS"; 

// SSMS-e database-er je naam dekhben sheta ekhane boshaben
$connectionInfo = array(
    "Database" => "StudentMonitoringDB", // Jemon: 'StudentMonitoringDB' (v2 bad diye)
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true
);

$conn = sqlsrv_connect($serverName, $connectionInfo);

if(!$conn) { 
    echo "Connection failed! Check if database name matches SSMS.<br>";
    die(print_r(sqlsrv_errors(), true)); 
}
?>