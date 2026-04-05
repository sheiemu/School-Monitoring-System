<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$table = isset($_GET['table']) ? $_GET['table'] : 'Student';
$allowed_tables = ['Student', 'Class', 'Subject', 'Marks', 'Attendance', 'Behaviour', 'Users', 'Notifications'];

if (!in_array($table, $allowed_tables)) {
    $table = 'Student';
}

$sql = "SELECT * FROM $table";
$result = sqlsrv_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Viewer</title>
    <style>
        body { font-family: Arial; background: #1c0b14; color: #f4f4f4; }
        .container { max-width: 1200px; margin: 30px auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; background: #2c001a; }
        th, td { padding: 10px; border: 1px solid #660026; text-align: left; }
        th { background: #ff4b6e; }
        .btn { display: inline-block; padding: 10px; margin: 5px; background: #ff4b6e; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Viewer</h1>
        <div>
            <?php foreach($allowed_tables as $t): ?>
                <a href="?table=<?= $t ?>" class="btn"><?= $t ?></a>
            <?php endforeach; ?>
        </div>
        <h2>Table: <?= $table ?></h2>
        <table>
            <thead>
                <tr>
                    <?php 
                    $cols = sqlsrv_field_metadata($result);
                    foreach($cols as $col): ?>
                        <th><?= $col['Name'] ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php while($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <?php foreach($row as $value): ?>
                            <td><?= htmlspecialchars($value ?? 'NULL') ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="teacher_dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>