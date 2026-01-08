<?php
// SESSION AND DATABASE SETUP
session_start();
include 'db_connect.php';

$error = '';

// HANDLE LOGIN FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // VALIDATE AND PREPARE USER INPUT
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = md5($_POST['password']);
    
    // QUERY DATABASE FOR USER CREDENTIALS
    $sql = "SELECT * FROM User WHERE Email='$email' AND Password='$password'";
    $result = mysqli_query($conn, $sql);
    
    // AUTHENTICATE USER AND CREATE SESSION
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        // STORE USER INFORMATION IN SESSION FOR LATER USE
        $_SESSION['user_id'] = $user['User_ID'];
        $_SESSION['name'] = $user['Name'];
        $_SESSION['role'] = $user['Role'];
        // REDIRECT AUTHENTICATED USER TO DASHBOARD
        header('Location: dashboard.php');
        exit();
    } else {
        // SET ERROR MESSAGE FOR INVALID CREDENTIALS
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - University Marketplace</title>
    <style>
        body { font-family: Arial; max-width: 500px; margin: 50px auto; padding: 20px; }
        input { width: 100%; padding: 10px; margin: 10px 0; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
        .error { padding: 10px; background: #f8d7da; color: #721c24; margin-bottom: 20px; }
        a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <h2>Login</h2>
    
    <!-- DISPLAY ERROR MESSAGE IF LOGIN FAILED -->
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- LOGIN FORM WITH EMAIL AND PASSWORD FIELDS -->
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    
    <!-- PROVIDE REGISTRATION LINK FOR NEW USERS -->
    <p>Don't have an account? <a href="register.php">Register here</a></p>
</body>
</html>