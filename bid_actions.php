<?php
// accept_bid.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'db_connect.php';

$bid_id = $_GET['id'];

// Update bid status to accepted
$sql = "UPDATE Bid SET Status = 'accepted' WHERE Bid_ID = '$bid_id'";

if (mysqli_query($conn, $sql)) {
    // Mark product as sold
    $product_sql = "UPDATE Product p 
                    JOIN Bid b ON p.Product_ID = b.Product_ID 
                    SET p.Status = 'sold' 
                    WHERE b.Bid_ID = '$bid_id'";
    mysqli_query($conn, $product_sql);
    
    header('Location: my_products.php?msg=accepted');
} else {
    header('Location: my_products.php?msg=error');
}
exit();
?>

<?php
// reject_bid.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'db_connect.php';

$bid_id = $_GET['id'];

// Update bid status to rejected
$sql = "UPDATE Bid SET Status = 'rejected' WHERE Bid_ID = '$bid_id'";

if (mysqli_query($conn, $sql)) {
    header('Location: my_products.php?msg=rejected');
} else {
    header('Location: my_products.php?msg=error');
}
exit();
?>