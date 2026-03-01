<?php
$serverName = "DESKTOP-64LHB9P\SQLEXPRESS";
$connectionInfo = array("Database"=>"StudentMonitoringDB");
$conn = sqlsrv_connect($serverName, $connectionInfo);

if ($conn) {
    echo "Connected to SQL Server successfully!";
} else {
    print_r(sqlsrv_errors());
}
?>