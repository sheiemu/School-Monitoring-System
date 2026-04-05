<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
<title>Sign Up</title>
<style>
body { font-family: Arial; display:flex; justify-content:center; align-items:center; height:100vh; background:#1c0b14; color:#f4f4f4; margin:0;}
.signup-box { background:#2c001a; padding:30px; border-radius:10px; width:400px; text-align:center;}
h2{color:#ff4b6e;margin-bottom:20px;}
input,select{width:100%;padding:12px;margin:10px 0;border:1px solid #660026;border-radius:5px;background:#33000d;color:#f4f4f4;}
input::placeholder{color:#ccc;}
button{width:100%;padding:12px;background:#ff4b6e;border:none;color:white;border-radius:5px;font-weight:bold;cursor:pointer;}
button:hover{background:#99001a;}
a{color:#ff4b6e;text-decoration:none;}
a:hover{text-decoration:underline;}
</style>
</head>
<body>
<div class="signup-box">
<h2>Create Account</h2>
<form action="signup_process.php" method="POST">
<input type="text" name="username" placeholder="Username" required>
<input type="email" name="email" placeholder="Email address" required>
<input type="password" name="password" placeholder="Password" required>
<select name="role" required>
<option value="">Select Role</option>
<option value="teacher">Teacher</option>
<option value="parent">Parent</option>
</select>
<button type="submit">Sign Up</button>
<p>Already have account? <a href="login.php">Login</a></p>
</form>
</div>
</body>
</html>