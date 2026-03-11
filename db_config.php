<?php
// 1. Auto-detecting the server - ekhane fixed kono naam thakbe na
$serverName = ".\SQLEXPRESS"; 

// 2. Database Name: Dui joni SSMS-e database-er naam 'StudentMonitoringDB' rakhun
$connectionInfo = array(
    "Database" => "StudentMonitoringDB", 
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true
);

// 3. Connection attempt
$conn = sqlsrv_connect($serverName, $connectionInfo);

// 4. Jodi karon PC-te '.' kaj na kore, tobe localhost try korbe
if ($conn === false) {
    $serverName = "localhost\SQLEXPRESS";
    $conn = sqlsrv_connect($serverName, $connectionInfo);
}

// 5. Final Error Handling
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>