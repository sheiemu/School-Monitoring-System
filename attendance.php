<?php
session_start();
require_once 'db_config.php';
$msg="";

$students=sqlsrv_query($conn,"SELECT * FROM Student");

if($_SERVER['REQUEST_METHOD']=="POST"){
    $date=$_POST['date'];
    foreach($_POST['status'] as $sid=>$status){
        $sql="INSERT INTO Attendance(student_id,class_id,attendance_date,status) VALUES(?,?,?,?)";
        sqlsrv_query($conn,$sql,[$sid,$_POST['class_id'][$sid],$date,$status]);
    }
    $msg="<p style='color:green;'>Attendance recorded!</p>";
}
?>
<form method="POST">
<input type="date" name="date" required>
<table border="1">
<tr><th>Student</th><th>Status</th></tr>
<?php while($s=sqlsrv_fetch_array($students,SQLSRV_FETCH_ASSOC)):?>
<tr>
<td><?php echo $s['name'];?></td>
<td>
<input type="hidden" name="class_id[<?php echo $s['student_id'];?>]" value="<?php echo $s['class_id'];?>">
<select name="status[<?php echo $s['student_id'];?>]">
<option value="Present">Present</option>
<option value="Absent">Absent</option>
</select>
</td>
</tr>
<?php endwhile;?>
</table>
<button type="submit">Save</button>
</form>
<?php echo $msg;?>