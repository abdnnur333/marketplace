<?php
// SESSION VERIFICATION
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// DATABASE CONNECTION
include 'db_connect.php';

// GET BID ID FROM URL PARAMETER
$bid_id = mysqli_real_escape_string($conn, $_GET['id']);

// VERIFY BID BELONGS TO SELLER'S PRODUCT
// Check that the bid is on a product owned by the current user
$verify_sql = "SELECT b.*, po.Owner_UID 
               FROM Bid b
               JOIN Product_Owner po ON b.Product_ID = po.Product_ID
               WHERE b.Bid_ID = '$bid_id' AND po.Owner_UID = '{$_SESSION['user_id']}'";
$verify_result = mysqli_query($conn, $verify_sql);

// PROCESS BID ACCEPTANCE IF AUTHORIZED
if (mysqli_num_rows($verify_result) > 0) {
    // UPDATE BID STATUS TO ACCEPTED
    $sql = "UPDATE Bid SET Status = 'accepted' WHERE Bid_ID = '$bid_id'";
    
    if (mysqli_query($conn, $sql)) {
        // MARK PRODUCT AS SOLD
        // Update the product status to sold after bid acceptance
        $product_sql = "UPDATE Product p 
                        JOIN Bid b ON p.Product_ID = b.Product_ID 
                        SET p.Status = 'sold' 
                        WHERE b.Bid_ID = '$bid_id'";
        mysqli_query($conn, $product_sql);
        
        // REJECT ALL OTHER PENDING BIDS ON SAME PRODUCT
        // Automatically reject other bids when one is accepted
        $reject_sql = "UPDATE Bid b1
                       JOIN Bid b2 ON b1.Product_ID = b2.Product_ID
                       SET b1.Status = 'rejected'
                       WHERE b2.Bid_ID = '$bid_id' 
                       AND b1.Bid_ID != '$bid_id'
                       AND b1.Status = 'pending'";
        mysqli_query($conn, $reject_sql);
        
        // SET SUCCESS MESSAGE
        $message = "Bid accepted successfully! You can now contact the buyer.";
    } else {
        // SET ERROR MESSAGE
        $message = "Error accepting bid.";
    }
} else {
    // SET UNAUTHORIZED ACCESS MESSAGE
    $message = "Invalid bid or unauthorized access.";
}

// REDIRECT TO MY PRODUCTS PAGE WITH MESSAGE
header('Location: my_products.php?msg=' . urlencode($message));
exit();
?>