<?php
require_once 'db_config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Search by username OR email
    $sql = "SELECT * FROM Users WHERE username = ? OR email = ?";
    $stmt = sqlsrv_query($conn, $sql, array($username, $username));
    
    if ($stmt === false) {
        $error = "Database error";
    } else {
        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
        if ($user) {
            // Simple password comparison (no hash for simplicity)
            if ($password == $user['password']) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'] ?: $user['username'];
                
                // Redirect based on role
                if ($user['role'] == 'admin') {
                    header("Location: admin_dashboard.php");
                } elseif ($user['role'] == 'teacher') {
                    header("Location: teacher_dashboard.php");
                } elseif ($user['role'] == 'parent') {
                    header("Location: parent_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "Username or Email not found!";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - School Monitoring System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background: linear-gradient(135deg, #1c0b14 0%, #2c001a 100%); 
            min-height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
        }
        .login-container { 
            background: rgba(44, 0, 26, 0.95); 
            padding: 40px; 
            border-radius: 20px; 
            width: 450px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.5); 
            border: 1px solid #ff4b6e; 
        }
        h1 { color: #ff4b6e; text-align: center; margin-bottom: 10px; font-size: 28px; }
        .subtitle { text-align: center; color: #888; margin-bottom: 30px; font-size: 14px; }
        .input-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #f4f4f4; font-weight: bold; }
        input { 
            width: 100%; 
            padding: 14px; 
            border: 1px solid #660026; 
            border-radius: 8px; 
            background: #3d0023; 
            color: #f4f4f4; 
            font-size: 16px; 
        }
        input:focus { outline: none; border-color: #ff4b6e; }
        button { 
            width: 100%; 
            padding: 14px; 
            background: #ff4b6e; 
            color: white; 
            border: none; 
            border-radius: 8px; 
            font-size: 16px; 
            font-weight: bold; 
            cursor: pointer; 
        }
        button:hover { background: #99001a; }
        .error { 
            background: #b71c1c; 
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            text-align: center; 
        }
        .demo-box { 
            background: #3d0023; 
            padding: 15px; 
            border-radius: 8px; 
            margin-top: 20px; 
            font-size: 12px; 
        }
        .demo-box h4 { color: #ff4b6e; margin-bottom: 10px; }
        .role-badge { 
            display: inline-block; 
            padding: 3px 8px; 
            border-radius: 4px; 
            font-size: 10px; 
            margin-right: 5px;
        }
        .admin-badge { background: #f44336; }
        .teacher-badge { background: #2196f3; }
        .parent-badge { background: #4caf50; }
        .note { text-align: center; margin-top: 15px; font-size: 11px; color: #666; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>🏫 School Monitoring System</h1>
        <div class="subtitle">Login with Username or Email</div>
        
        <?php if($error): ?>
            <div class="error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <label>👤 Username or Email</label>
                <input type="text" name="username" placeholder="Enter username or email" required autocomplete="off">
            </div>
            <div class="input-group">
                <label>🔒 Password</label>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>
            <button type="submit">Login →</button>
        </form>
        
        <div class="demo-box">
            <h4>🔐 Login Credentials</h4>
            <p><span class="role-badge admin-badge">Admin</span> <strong>admin</strong> or admin@school.com / admin123</p>
            <p><span class="role-badge teacher-badge">Teacher</span> <strong>teacher1</strong> or teacher@school.com / password</p>
            <p><span class="role-badge parent-badge">Parent</span> <strong>parent1</strong> or parent@school.com / parent123</p>
        </div>
        <div class="note">
            <p>Don't have an account? <a href="signup.php" style="color:#ff4b6e;">Sign up here</a></p>
        </div>
    </div>
</body>
</html>