<?php
$serverName = "DESKTOP-64LHB9P\SQLEXPRESS";
$connectionInfo = array("Database"=>"StudentMonitoringDB_v2", "CharacterSet"=>"UTF-8");
$conn = sqlsrv_connect($serverName, $connectionInfo);

if(!$conn) { die(print_r(sqlsrv_errors(), true)); }
?>