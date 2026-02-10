<?php
$serverName = ".\SQLEXPRESS"; 
$connectionInfo = array("Database"=>"StudentMonitoringDB");
$conn = sqlsrv_connect($serverName, $connectionInfo);

if($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>