<?php
session_start();
require_once 'db_config.php';

$user = $_SESSION['user'];

// get user id
$getUser = sqlsrv_query($conn, "SELECT user_id FROM Users WHERE username = ?", array($user));
$u = sqlsrv_fetch_array($getUser, SQLSRV_FETCH_ASSOC);

$user_id = $u['user_id'];

// mark only this user's notifications
sqlsrv_query($conn, "
UPDATE n
SET is_read = 1
FROM Notifications n
JOIN Parent_Student ps ON n.student_id = ps.student_id
WHERE ps.parent_id = ?", array($user_id));
?>