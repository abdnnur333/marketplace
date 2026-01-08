<?php
// SESSION VERIFICATION
// Start session and check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// DATABASE CONNECTION
// Include database connection file
include 'db_connect.php';

// GET BID ID FROM URL
// Retrieve the bid ID from the URL parameter
$bid_id = $_GET['id'];

// UPDATE BID STATUS TO REJECTED
// Query to mark the bid as rejected
$sql = "UPDATE Bid SET Status = 'rejected' WHERE Bid_ID = '$bid_id'";

// EXECUTE QUERY AND REDIRECT
// Update the bid and redirect back to my_products page
if (mysqli_query($conn, $sql)) {
    header('Location: my_products.php');
} else {
    header('Location: my_products.php');
}
exit();
?>