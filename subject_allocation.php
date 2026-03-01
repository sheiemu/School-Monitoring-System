<?php
session_start();
require_once 'db_config.php';
$msg="";

$classes = sqlsrv_query($conn,"SELECT * FROM Class");
$subjects = sqlsrv_query($conn,"SELECT * FROM Subject");
$teachers = sqlsrv_query($conn,"SELECT * FROM Teacher");

if($_SERVER['REQUEST_METHOD']=="POST"){
    $class_id=$_POST['class_id'];
    $subject_id=$_POST['subject_id'];
    $teacher_id=$_POST['teacher_id'];

    $sql="INSERT INTO SubjectAllocation (class_id,subject_id,teacher_id) VALUES (?,?,?)";
    $stmt=sqlsrv_query($conn,$sql,[$class_id,$subject_id,$teacher_id]);
    $msg = $stmt ? "<p style='color:green;'>Allocated successfully!</p>" : "<p style='color:red;'>Error!</p>";
}
?>
<form method="POST">
<select name="class_id" required>
<option value="">Select Class</option>
<?php while($c=sqlsrv_fetch_array($classes,SQLSRV_FETCH_ASSOC)){ echo "<option value='{$c['class_id']}'>{$c['name']}</option>";}?>
</select>
<select name="subject_id" required>
<option value="">Select Subject</option>
<?php while($s=sqlsrv_fetch_array($subjects,SQLSRV_FETCH_ASSOC)){ echo "<option value='{$s['subject_id']}'>{$s['subject_name']}</option>";}?>
</select>
<select name="teacher_id" required>
<option value="">Select Teacher</option>
<?php while($t=sqlsrv_fetch_array($teachers,SQLSRV_FETCH_ASSOC)){ echo "<option value='{$t['teacher_id']}'>{$t['name']}</option>";}?>
</select>
<button type="submit">Allocate</button>
</form>
<?php echo $msg;?>