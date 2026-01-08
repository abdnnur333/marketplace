<?php
// SESSION VERIFICATION
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// DATABASE CONNECTION
include 'db_connect.php';

// GET CURRENT USER ID
$user_id = $_SESSION['user_id'];
$message = '';

// DISPLAY MESSAGE FROM URL PARAMETER IF PROVIDED
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

// HANDLE PRODUCT DELETION
if (isset($_GET['delete'])) {
    // GET PRODUCT ID TO DELETE
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // VERIFY USER OWNS THE PRODUCT BEFORE DELETION
    $verify = "SELECT * FROM Product_Owner WHERE Product_ID = '$delete_id' AND Owner_UID = '$user_id'";
    if (mysqli_num_rows(mysqli_query($conn, $verify)) > 0) {
        // DELETE THE PRODUCT IF OWNERSHIP VERIFIED
        $delete_sql = "DELETE FROM Product WHERE Product_ID = '$delete_id'";
        if (mysqli_query($conn, $delete_sql)) {
            $message = "Product deleted successfully!";
        } else {
            $message = "Error deleting product: " . mysqli_error($conn);
        }
    }
}

// FETCH USER'S PRODUCTS WITH BID STATISTICS
// Get all products owned by user with highest bid and total bid count
$sql = "SELECT p.*, 
        (SELECT MAX(Amount) FROM Bid WHERE Product_ID = p.Product_ID) as HighestBid,
        (SELECT COUNT(*) FROM Bid WHERE Product_ID = p.Product_ID) as BidCount
        FROM Product p 
        JOIN Product_Owner po ON p.Product_ID = po.Product_ID 
        WHERE po.Owner_UID = '$user_id' 
        ORDER BY p.Product_ID DESC";
$products = mysqli_query($conn, $sql);

// FETCH ALL BIDS ON USER'S PRODUCTS
// Get detailed bid information with bidder details and product information
$bids_sql = "SELECT b.*, p.Name as ProductName, p.Product_ID, u.Name as BidderName, u.Phone as BidderPhone, u.Email as BidderEmail,
             (SELECT MAX(Amount) FROM Bid WHERE Product_ID = b.Product_ID) as HighestBid
             FROM Bid b 
             JOIN Product p ON b.Product_ID = p.Product_ID 
             JOIN Product_Owner po ON p.Product_ID = po.Product_ID
             JOIN User u ON b.User_ID = u.User_ID 
             WHERE po.Owner_UID = '$user_id' 
             ORDER BY p.Product_ID, b.Amount DESC, b.TimeStamp DESC";
$bids = mysqli_query($conn, $bids_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Products - University Marketplace</title>
    <style>
        body { font-family: Arial; margin: 0; }
        .header { background: #007bff; color: white; padding: 20px; }
        .nav { background: #333; padding: 10px; }
        .nav a { color: white; text-decoration: none; padding: 10px 20px; display: inline-block; }
        .nav a:hover { background: #555; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #007bff; color: white; }
        .btn { padding: 8px 15px; background: #28a745; color: white; text-decoration: none; border: none; cursor: pointer; border-radius: 3px; display: inline-block; }
        .btn-danger { background: #dc3545; }
        .btn:hover { opacity: 0.8; }
        .pending { color: orange; font-weight: bold; }
        .accepted { color: green; font-weight: bold; }
        .rejected { color: red; font-weight: bold; }
        .highest-bid { background: #fff3cd; font-weight: bold; }
        .message { padding: 15px; background: #d4edda; color: #155724; margin-bottom: 20px; border-radius: 5px; }
        .error { background: #f8d7da; color: #721c24; }
        .contact-info { background: #e7f3ff; padding: 10px; border-radius: 5px; margin-top: 5px; font-size: 14px; }
        .contact-info strong { display: block; margin-bottom: 5px; color: #004085; }
    </style>
    <script>
        // CONFIRMATION DIALOG FOR PRODUCT DELETION
        function confirmDelete(productName) {
            return confirm('Are you sure you want to delete "' + productName + '"? This action cannot be undone.');
        }
    </script>
</head>
<body>
    <div class="header">
        <h1>My Products</h1>
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
            <div class="message <?php echo (strpos(strtolower($message), 'error') !== false) ? 'error' : ''; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- TABLE: USER'S PRODUCTS -->
        <h2>Your Products</h2>
        <table>
            <tr>
                <th>Name</th>
                <th>Category</th>
                <th>Highest Bid</th>
                <th>Total Bids</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <!-- LOOP THROUGH PRODUCTS AND DISPLAY EACH ONE -->
            <?php 
            mysqli_data_seek($products, 0);
            while ($product = mysqli_fetch_assoc($products)): 
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['Name']); ?></td>
                    <td><?php echo htmlspecialchars($product['Category']); ?></td>
                    <!-- DISPLAY HIGHEST BID OR "NO BIDS YET" -->
                    <td>
                        <?php if ($product['HighestBid']): ?>
                            ৳<?php echo number_format($product['HighestBid'], 2); ?>
                        <?php else: ?>
                            <em>No bids yet</em>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $product['BidCount']; ?></td>
                    <td><?php echo ucfirst($product['Status']); ?></td>
                    <!-- DELETE BUTTON - ONLY SHOW IF PRODUCT NOT SOLD -->
                    <td>
                        <?php if ($product['Status'] != 'sold'): ?>
                            <a href="my_products.php?delete=<?php echo $product['Product_ID']; ?>" 
                               class="btn btn-danger" 
                               onclick="return confirmDelete('<?php echo htmlspecialchars($product['Name']); ?>')">
                                Delete
                            </a>
                        <?php else: ?>
                            <span style="color: green;">✓ Sold</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
        
        <!-- TABLE: BIDS RECEIVED ON USER'S PRODUCTS -->
        <h2>Bids on Your Products</h2>
        <table>
            <tr>
                <th>Product</th>
                <th>Bidder</th>
                <th>Amount</th>
                <th>Time</th>
                <th>Status</th>
                <th>Action / Contact</th>
            </tr>
            <!-- LOOP THROUGH BIDS AND DISPLAY EACH ONE -->
            <?php 
            if (mysqli_num_rows($bids) > 0):
                while ($bid = mysqli_fetch_assoc($bids)): 
                    // CHECK IF THIS IS THE HIGHEST BID
                    $is_highest = ($bid['Amount'] == $bid['HighestBid']);
                    $row_class = ($is_highest && $bid['Status'] == 'pending') ? 'highest-bid' : '';
            ?>
                <tr class="<?php echo $row_class; ?>">
                    <td><?php echo htmlspecialchars($bid['ProductName']); ?></td>
                    <td><?php echo htmlspecialchars($bid['BidderName']); ?></td>
                    <!-- DISPLAY BID AMOUNT WITH HIGHEST BID INDICATOR -->
                    <td>
                        ৳<?php echo number_format($bid['Amount'], 2); ?>
                        <?php if ($is_highest && $bid['Status'] == 'pending'): ?>
                            <span style="color: green; font-size: 12px;"> ★ HIGHEST</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('M d, Y H:i', strtotime($bid['TimeStamp'])); ?></td>
                    <td class="<?php echo $bid['Status']; ?>"><?php echo ucfirst($bid['Status']); ?></td>
                    <!-- ACTION BUTTONS - VARY BASED ON BID STATUS -->
                    <td>
                        <?php if ($bid['Status'] == 'pending'): ?>
                            <!-- ACCEPT BID BUTTON FOR PENDING BIDS -->
                            <a href="accept_bid.php?id=<?php echo $bid['Bid_ID']; ?>" 
                               class="btn"
                               onclick="return confirm('Accept this bid from <?php echo htmlspecialchars($bid['BidderName']); ?> for ৳<?php echo number_format($bid['Amount'], 2); ?>?')">
                                Accept Bid
                            </a>
                        <?php elseif ($bid['Status'] == 'accepted'): ?>
                            <!-- SHOW BUYER CONTACT INFO FOR ACCEPTED BIDS -->
                            <div class="contact-info">
                                <strong>✓ Accepted - Buyer Contact:</strong>
                                Name: <?php echo htmlspecialchars($bid['BidderName']); ?><br>
                                Phone: <?php echo htmlspecialchars($bid['BidderPhone']); ?><br>
                                Email: <?php echo htmlspecialchars($bid['BidderEmail']); ?>
                            </div>
                        <?php else: ?>
                            <span style="color: #999;">Rejected</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php 
                endwhile;
            else:
            ?>
                <!-- MESSAGE IF NO BIDS RECEIVED -->
                <tr>
                    <td colspan="6" style="text-align: center; color: #666; padding: 30px;">
                        No bids received yet on your products.
                    </td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>