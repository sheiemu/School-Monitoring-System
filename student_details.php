<?php
session_start();
include 'db.php'; // your DB connection

if(!isset($_GET['id'])){
    echo "Student ID not provided.";
    exit;
}

$student_id = intval($_GET['id']);

// Fetch student info
$stmt = $conn->prepare("SELECT * FROM students WHERE id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Attendance %
$stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(status='Present') as present FROM attendance WHERE student_id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$att_res = $stmt->get_result()->fetch_assoc();
$attendance_pct = ($att_res['total']>0)? round(($att_res['present']/$att_res['total'])*100,2) : 0;

// Behaviour
$stmt = $conn->prepare("SELECT * FROM behaviour WHERE student_id=? ORDER BY date DESC");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$behaviour_res = $stmt->get_result();

// Marks
$stmt = $conn->prepare("SELECT m.marks, s.name as subject_name FROM marks m JOIN subjects s ON m.subject_id=s.id WHERE m.student_id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$marks_res = $stmt->get_result();
$total_marks = $marks_count = 0;
while($row = $marks_res->fetch_assoc()){
    $total_marks += $row['marks'];
    $marks_count++;
}
$marks_avg = ($marks_count>0)? round($total_marks/$marks_count,2) : 0;

// Scores conversion
$behaviour_score = 0;
$behaviour_count = 0;
while($row = $behaviour_res->fetch_assoc()){
    $behaviour_score += $row['score'];
    $behaviour_count++;
}
$behaviour_score = ($behaviour_count>0)? round($behaviour_score/$behaviour_count,2) : 0;

// Attendance numeric
$attendance_score = 0;
if($attendance_pct>=95) $attendance_score = 5;
elseif($attendance_pct>=90) $attendance_score = 4;
elseif($attendance_pct>=80) $attendance_score = 3;
elseif($attendance_pct>=70) $attendance_score = 2;
else $attendance_score = 1;

// Marks numeric
$marks_score = 0;
if($marks_avg>=90) $marks_score = 5;
elseif($marks_avg>=80) $marks_score = 4;
elseif($marks_avg>=70) $marks_score = 3;
elseif($marks_avg>=60) $marks_score = 2;
else $marks_score = 1;

// Overall
$overall = round(($behaviour_score + $attendance_score + $marks_score)/3,2);
?>

<h2>Student Dashboard: <?= $student['name'] ?></h2>
<p><b>Class:</b> <?= $student['class'] ?> | <b>Gender:</b> <?= $student['gender'] ?> | <b>DOB:</b> <?= $student['dob'] ?></p>

<h3>Attendance: <?= $attendance_pct ?>%</h3>

<h3>Marks:</h3>
<table border="1">
<tr><th>Subject</th><th>Marks</th></tr>
<?php
$stmt = $conn->prepare("SELECT m.marks, s.name as subject_name FROM marks m JOIN subjects s ON m.subject_id=s.id WHERE m.student_id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
while($row=$res->fetch_assoc()){
    echo "<tr><td>{$row['subject_name']}</td><td>{$row['marks']}</td></tr>";
}
?>
</table>
<p>Average Marks: <?= $marks_avg ?></p>

<h3>Behaviour Notes:</h3>
<table border="1">
<tr><th>Date</th><th>Note</th><th>Score</th></tr>
<?php
$stmt = $conn->prepare("SELECT * FROM behaviour WHERE student_id=? ORDER BY date DESC");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
while($row=$res->fetch_assoc()){
    echo "<tr><td>{$row['date']}</td><td>{$row['note']}</td><td>{$row['score']}</td></tr>";
}
?>
</table>

<h3>Overall Performance Score: <?= $overall ?> / 5</h3>