<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$msg = "";

// SAFE student fetch (no negative IDs)
$students = sqlsrv_query($conn, "SELECT * FROM Student WHERE student_id > 0");

// SAVE behaviour
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['description'])) {
    $student_id = $_POST['student_id'];
    $desc = $_POST['description'];

    $sql = "INSERT INTO Behaviour (student_id, description) VALUES (?, ?)";
    $stmt = sqlsrv_query($conn, $sql, array($student_id, $desc));

    if ($stmt) {
        // Notification
        sqlsrv_query($conn,
        "INSERT INTO Notifications (student_id, message) VALUES (?, ?)",
        array($student_id, "Behaviour record added"));

        $msg = "Behaviour saved!";
    } else {
        $msg = "Error!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Behaviour</title>
<style>
body { background:#1c0b14; color:#fff; text-align:center; font-family:Arial; }
select, textarea, button { padding:10px; margin:10px; border-radius:5px; }
.box { background:#2c001a; margin:10px auto; padding:10px; width:60%; border-radius:6px; }
a { color:#ff4b6e; }
</style>
</head>
<body>

<h2>Add Behaviour Record</h2>
<p><?php echo $msg; ?></p>

<form method="POST">
<select name="student_id" required>
<option value="">Select Student</option>
<?php while($s = sqlsrv_fetch_array($students, SQLSRV_FETCH_ASSOC)): ?>
<option value="<?= $s['student_id'] ?>"><?= $s['name'] ?></option>
<?php endwhile; ?>
</select>

<textarea name="description" placeholder="Behaviour description" required></textarea>
<br>
<button type="submit">Save</button>
</form>

<hr>

<h2>View Behaviour History</h2>

<form method="POST">
<select name="student_id_history" required>
<option value="">Select Student</option>
<?php
$students2 = sqlsrv_query($conn, "SELECT * FROM Student WHERE student_id > 0");
while($s2 = sqlsrv_fetch_array($students2, SQLSRV_FETCH_ASSOC)){
    echo "<option value='".$s2['student_id']."'>".$s2['name']."</option>";
}
?>
</select>
<button type="submit">Show</button>
</form>

<?php
if (isset($_POST['student_id_history'])) {
    $sid = $_POST['student_id_history'];

    $result = sqlsrv_query($conn, 
        "SELECT * FROM Behaviour WHERE student_id = ? ORDER BY behaviour_date DESC", 
        array($sid));

    if($result === false){
        echo "⚠ Table 'Behaviour' missing!";
    } else {
        echo "<h3>Behaviour History</h3>";

        while($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
            echo "<div class='box'>";
            echo $row['description'];

            if(isset($row['behaviour_date'])){
                echo " (" . $row['behaviour_date']->format('Y-m-d') . ")";
            }

            echo "</div>";
        }
    }
}
?>

<br><a href="dashboard.php">← Back</a>

</body>
</html>