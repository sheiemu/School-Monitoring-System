<?php
session_start();
require_once 'db_config.php';
$msg="";

$students=sqlsrv_query($conn,"SELECT * FROM Student");

if($_SERVER['REQUEST_METHOD']=="POST"){
    $date=$_POST['date'];
    foreach($_POST['notes'] as $sid=>$note){
        $sql="INSERT INTO Behaviour(student_id,teacher_id,record_date,behaviour_notes) VALUES(?,?,?,?)";
        sqlsrv_query($conn, $sql, [$sid,$_SESSION['user_id'],$date,$note]);
    }
    $msg="<p style='color:green;'>Behaviour saved!</p>";
}
?>
<form method="POST">
<input type="date" name="date" required>
<table border="1">
<tr><th>Student</th><th>Notes</th></tr>
<?php while($s=sqlsrv_fetch_array($students,SQLSRV_FETCH_ASSOC)):?>
<tr>
<td><?php echo $s['name'];?></td>
<td><input type="text" name="notes[<?php echo $s['student_id'];?>]" placeholder="Enter notes"></td>
</tr>
<?php endwhile;?>
</table>
<button type="submit">Save</button>
</form>
<?php echo $msg;?>