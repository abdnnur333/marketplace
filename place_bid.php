<?php
// SESSION AND AUTHENTICATION CHECK
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// DATABASE CONNECTION
include 'db_connect.php';

// GET PRODUCT ID AND INITIALIZE VARIABLES
$product_id = mysqli_real_escape_string($conn, $_GET['id']);
$message = '';
$error = '';

// FETCH PRODUCT DETAILS WITH BID INFORMATION
// Get product info, owner name, highest bid, and total bid count
$sql = "SELECT p.*, u.Name as OwnerName, po.Owner_UID,
        (SELECT MAX(Amount) FROM Bid WHERE Product_ID = p.Product_ID) as HighestBid,
        (SELECT COUNT(*) FROM Bid WHERE Product_ID = p.Product_ID) as BidCount
        FROM Product p 
        JOIN Product_Owner po ON p.Product_ID = po.Product_ID
        JOIN User u ON po.Owner_UID = u.User_ID 
        WHERE p.Product_ID = '$product_id'";
$result = mysqli_query($conn, $sql);
$product = mysqli_fetch_assoc($result);

// VALIDATE PRODUCT EXISTS
if (!$product) {
    header('Location: dashboard.php');
    exit();
}

// PREVENT PRODUCT OWNER FROM BIDDING ON OWN PRODUCT
if ($product['Owner_UID'] == $_SESSION['user_id']) {
    header('Location: dashboard.php');
    exit();
}

// CHECK IF PRODUCT IS STILL AVAILABLE FOR BIDDING
if ($product['Status'] != 'available') {
    $error = "This product is no longer available for bidding.";
}

// HANDLE BID SUBMISSION
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$error) {
    // GET BID AMOUNT FROM FORM
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $user_id = $_SESSION['user_id'];
    
    // VALIDATE BID AMOUNT - MUST BE HIGHER THAN CURRENT HIGHEST
    $min_bid = $product['HighestBid'] ? $product['HighestBid'] + 1 : 1;
    
    if ($amount < $min_bid) {
        $error = "Your bid must be at least ৳" . number_format($min_bid, 2);
    } else {
        // CHECK IF USER ALREADY HAS A PENDING BID FOR THIS PRODUCT
        $check_existing = "SELECT * FROM Bid WHERE Product_ID = '$product_id' AND User_ID = '$user_id' AND Status = 'pending'";
        $existing_bid = mysqli_query($conn, $check_existing);
        
        if (mysqli_num_rows($existing_bid) > 0) {
            // UPDATE EXISTING BID WITH NEW AMOUNT
            $update_sql = "UPDATE Bid SET Amount = '$amount', TimeStamp = CURRENT_TIMESTAMP 
                          WHERE Product_ID = '$product_id' AND User_ID = '$user_id' AND Status = 'pending'";
            
            if (mysqli_query($conn, $update_sql)) {
                $message = "Your bid has been updated successfully!";
            } else {
                $error = "Error updating bid: " . mysqli_error($conn);
            }
        } else {
            // INSERT NEW BID INTO DATABASE
            $insert_sql = "INSERT INTO Bid (Product_ID, User_ID, Amount, Status) 
                          VALUES ('$product_id', '$user_id', '$amount', 'pending')";
            
            if (mysqli_query($conn, $insert_sql)) {
                $message = "Bid placed successfully!";
            } else {
                $error = "Error placing bid: " . mysqli_error($conn);
            }
        }
        
        // REFRESH PRODUCT DATA TO SHOW UPDATED HIGHEST BID
        $result = mysqli_query($conn, $sql);
        $product = mysqli_fetch_assoc($result);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Place Bid - University Marketplace</title>
    <style>
        body { font-family: Arial; margin: 0; }
        .header { background: #007bff; color: white; padding: 20px; }
        .nav { background: #333; padding: 10px; }
        .nav a { color: white; text-decoration: none; padding: 10px 20px; display: inline-block; }
        .nav a:hover { background: #555; }
        .container { max-width: 600px; margin: 50px auto; padding: 20px; }
        .product-info { background: #f8f9fa; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        .product-info img { width: 100%; max-height: 300px; object-fit: cover; border-radius: 5px; margin-bottom: 15px; }
        input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 3px; font-size: 16px; }
        button:hover { background: #0056b3; }
        .message { padding: 10px; background: #d4edda; color: #155724; margin-bottom: 20px; border-radius: 5px; }
        .error { padding: 10px; background: #f8d7da; color: #721c24; margin-bottom: 20px; border-radius: 5px; }
        .bid-history { background: #e7f3ff; padding: 15px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Place Your Bid</h1>
    </div>
    
    <!-- NAVIGATION MENU -->
    <div class="nav">
        <a href="dashboard.php">Home</a>
        <a href="add_product.php">Add Product</a>
        <a href="my_products.php">My Products</a>
        <a href="my_bids.php">My Bids</a>
        <a href="logout.php">Logout</a>
    </div>
    
    <div class="container">
        <!-- DISPLAY SUCCESS OR ERROR MESSAGES -->
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- DISPLAY PRODUCT INFORMATION -->
        <div class="product-info">
            <!-- PRODUCT IMAGE -->
            <?php if ($product['ImageURL']): ?>
                <img src="<?php echo htmlspecialchars($product['ImageURL']); ?>" 
                     alt="<?php echo htmlspecialchars($product['Name']); ?>">
            <?php endif; ?>
            
            <!-- PRODUCT DETAILS -->
            <h2><?php echo htmlspecialchars($product['Name']); ?></h2>
            <p><strong>Category:</strong> <?php echo htmlspecialchars($product['Category']); ?></p>
            <p><strong>Seller:</strong> <?php echo htmlspecialchars($product['OwnerName']); ?></p>
            
            <!-- CURRENT BIDDING STATUS -->
            <div class="bid-history">
                <?php if ($product['HighestBid']): ?>
                    <strong>Current Highest Bid:</strong> ৳<?php echo number_format($product['HighestBid'], 2); ?><br>
                    <small>Total Bids: <?php echo $product['BidCount']; ?></small>
                <?php else: ?>
                    <strong>No bids yet - Be the first to bid!</strong>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- BID FORM - ONLY SHOW IF PRODUCT IS AVAILABLE -->
        <?php if ($product['Status'] == 'available'): ?>
            <form method="POST">
                <label for="amount" style="display: block; margin-bottom: 5px; font-weight: bold;">
                    Enter Your Bid Amount:
                </label>
                <!-- BID INPUT WITH MINIMUM AMOUNT VALIDATION -->
                <input type="number" 
                       id="amount"
                       name="amount" 
                       step="0.01" 
                       placeholder="Enter amount in BDT" 
                       min="<?php echo $product['HighestBid'] ? $product['HighestBid'] + 1 : 1; ?>" 
                       required>
                <small style="color: #666; display: block; margin-bottom: 15px;">
                    Minimum bid: ৳<?php echo number_format($product['HighestBid'] ? $product['HighestBid'] + 1 : 1, 2); ?>
                </small>
                <!-- SUBMIT BID BUTTON -->
                <button type="submit">Submit Bid</button>
            </form>
        <?php endif; ?>
        
        <!-- BACK TO PRODUCTS LINK -->
        <p style="text-align: center; margin-top: 20px;">
            <a href="dashboard.php" style="color: #007bff;">← Back to Products</a>
        </p>
    </div>
</body>
</html>