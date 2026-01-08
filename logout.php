<?php
// SESSION TERMINATION
// Start the current session to access session variables
session_start();
// Destroy all session data to log the user out
session_destroy();
// REDIRECT TO LOGIN PAGE
// Send user back to login page after logout
header('Location: login.php');
// Terminate script execution
exit();
?>