<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user'])) {
    exit();
}

// Fetch latest unread notifications
$user = $_SESSION['user'];

// First get parent user_id
$getUser = sqlsrv_query($conn, "SELECT user_id FROM Users WHERE username = ?", array($user));
$u = sqlsrv_fetch_array($getUser, SQLSRV_FETCH_ASSOC);

$parent_id = $u['user_id'];

// Get only this parent's student notifications
$sql = "SELECT n.* 
        FROM Notifications n
        JOIN Parent_Student ps ON n.student_id = ps.student_id
        WHERE ps.parent_id = ? AND n.is_read = 0
        ORDER BY n.created_at DESC";

$stmt = sqlsrv_query($conn, $sql, array($parent_id));

$stmt = sqlsrv_query($conn, $sql);

$data = [];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $data[] = $row;
}

echo json_encode($data);
?>