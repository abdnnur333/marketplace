<?php
// SESSION AND DATABASE SETUP
session_start();
include 'db_connect.php';

// Initialize message variable for feedback
$message = '';

// HANDLE REGISTRATION FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // SANITIZE AND PREPARE USER INPUT
    // Escape all input to prevent SQL injection
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = md5($_POST['password']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $university_id = mysqli_real_escape_string($conn, $_POST['university_id']);
    
    // INSERT NEW USER INTO DATABASE
    $sql = "INSERT INTO User (Name, Email, Password, Phone, Role, University_ID) 
            VALUES ('$name', '$email', '$password', '$phone', '$role', '$university_id')";
    
    // VALIDATE REGISTRATION AND SET MESSAGE
    if (mysqli_query($conn, $sql)) {
        $message = "Registration successful! Please login.";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - University Marketplace</title>
    <style>
        body { font-family: Arial; max-width: 500px; margin: 50px auto; padding: 20px; }
        input, select { width: 100%; padding: 10px; margin: 10px 0; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
        .message { padding: 10px; background: #d4edda; color: #155724; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; }
        a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <h2>Register</h2>
    
    <!-- DISPLAY REGISTRATION MESSAGE OR ERROR -->
    <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <!-- REGISTRATION FORM WITH ALL REQUIRED FIELDS -->
    <form method="POST">
        <!-- User's full name field -->
        <input type="text" name="name" placeholder="Full Name" required>
        <!-- User's email address for login -->
        <input type="email" name="email" placeholder="Email" required>
        <!-- Password field (will be hashed with MD5) -->
        <input type="password" name="password" placeholder="Password" required>
        <!-- User's contact phone number -->
        <input type="text" name="phone" placeholder="Phone Number" required>
        
        <!-- User role selection (Student or Faculty) -->
        <select name="role" required>
            <option value="">Select Role</option>
            <option value="student">Student</option>
            <option value="faculty">Faculty</option>
        </select>
        
        <!-- University ID for identification -->
        <input type="text" name="university_id" placeholder="University ID" required>
        
        <!-- Submit button to register -->
        <button type="submit">Register</button>
    </form>
    
    <!-- LINK TO LOGIN PAGE FOR EXISTING USERS -->
    <p>Already have an account? <a href="login.php">Login here</a></p>
</body>
</html>