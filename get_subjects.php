<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    exit();
}

$class_id = isset($_GET['class_id']) ? $_GET['class_id'] : 0;
$group = isset($_GET['group']) ? $_GET['group'] : null;

$subjects = [];

if ($class_id >= 9 && $group) {
    // For Class 9-10 with group
    $group_subjects = [];
    if ($group == 'Science') {
        $group_subjects = ['Physics', 'Chemistry', 'Higher Math', 'Biology'];
    } elseif ($group == 'Commerce') {
        $group_subjects = ['Accounting', 'Finance & Banking', 'Business Entrepreneurship'];
    } elseif ($group == 'Arts') {
        $group_subjects = ['History of Bangladesh', 'Geography & Environment', 'Civics & Citizenship'];
    }
    
    // Get group subjects
    $sql = "SELECT subject_id, subject_name FROM Subject WHERE subject_name IN ('" . implode("','", $group_subjects) . "') 
            UNION 
            SELECT subject_id, subject_name FROM Subject 
            WHERE subject_name IN ('Bangla','English','Math','ICT','Religion','Bangladesh & Global Studies')";
    $stmt = sqlsrv_query($conn, $sql);
} else {
    // For other classes
    $sql = "SELECT s.subject_id, s.subject_name 
            FROM Class_Subject cs
            JOIN Subject s ON cs.subject_id = s.subject_id
            WHERE cs.class_id = ?
            ORDER BY s.subject_name";
    $stmt = sqlsrv_query($conn, $sql, array($class_id));
}

while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $subjects[] = $row;
}

echo '<option value="">-- Select Subject --</option>';
foreach($subjects as $subject) {
    echo '<option value="' . $subject['subject_id'] . '">' . htmlspecialchars($subject['subject_name']) . '</option>';
}
?>