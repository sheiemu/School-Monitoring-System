<?php
session_start();
require_once 'db_config.php';

echo "<h2>Checking Marks in Database</h2>";

$sql = "SELECT m.*, s.name as student_name, sub.subject_name 
        FROM Marks m
        JOIN Student s ON m.student_id = s.student_id
        JOIN Subject sub ON m.subject_id = sub.subject_id
        ORDER BY m.mark_id DESC";

$result = sqlsrv_query($conn, $sql);

if($result === false) {
    die(print_r(sqlsrv_errors(), true));
}

echo "<table border='1' cellpadding='8'>";
echo "<tr><th>ID</th><th>Student</th><th>Subject</th><th>Marks</th><th>Exam Type</th><th>Created</th></tr>";

while($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . $row['mark_id'] . "</td>";
    echo "<td>" . $row['student_name'] . " (ID: " . $row['student_id'] . ")</td>";
    echo "<td>" . $row['subject_name'] . "</td>";
    echo "<td>" . $row['marks_obtained'] . "</td>";
    echo "<td>" . $row['exam_type'] . "</td>";
    echo "<td>" . ($row['created_at'] ? $row['created_at']->format('Y-m-d H:i') : 'N/A') . "</td>";
    echo "</tr>";
}
echo "</table>";
?>