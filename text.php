<?php
require 'db_config.php'; // includes your database connection

if ($conn) {
    echo "Connected successfully!";
} else {
    die(print_r(sqlsrv_errors(), true));
}
?>