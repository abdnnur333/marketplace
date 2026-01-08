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

// FETCH USER'S BIDS WITH PRODUCT AND SELLER INFORMATION
// Get all bids placed by user with seller details and product status
$sql = "SELECT b.*, p.Name as ProductName, p.Status as ProductStatus, 
        u.Name as SellerName, u.Phone as SellerPhone, u.Email as SellerEmail,
        (SELECT MAX(Amount) FROM Bid WHERE Product_ID = b.Product_ID) as HighestBid
        FROM Bid b 
        JOIN Product p ON b.Product_ID = p.Product_ID 
        JOIN Product_Owner po ON p.Product_ID = po.Product_ID
        JOIN User u ON po.Owner_UID = u.User_ID 
        WHERE b.User_ID = '$user_id' 
        ORDER BY b.TimeStamp DESC";
$bids = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Bids - University Marketplace</title>
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
        .pending { color: orange; font-weight: bold; }
        .accepted { color: green; font-weight: bold; }
        .rejected { color: red; font-weight: bold; }
        .contact-info { background: #e7f3ff; padding: 12px; border-radius: 5px; font-size: 14px; }
        .contact-info strong { display: block; margin-bottom: 8px; color: #004085; }
        .winning-bid { background: #d4edda; }
        .btn { padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border: none; cursor: pointer; border-radius: 3px; display: inline-block; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
    </style>
</head>
<body>
    <div class="header">
        <h1>My Bids</h1>
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
        <!-- TABLE: USER'S BIDS -->
        <h2>Your Bids</h2>
        <table>
            <tr>
                <th>Product</th>
                <th>Your Bid</th>
                <th>Highest Bid</th>
                <th>Time</th>
                <th>Status</th>
                <th>Seller Contact</th>
            </tr>
            <!-- LOOP THROUGH BIDS AND DISPLAY EACH ONE -->
            <?php 
            if (mysqli_num_rows($bids) > 0):
                while ($bid = mysqli_fetch_assoc($bids)): 
                    // CHECK IF THIS BID IS WINNING
                    $is_winning = ($bid['Amount'] == $bid['HighestBid'] && $bid['Status'] == 'pending' && $bid['ProductStatus'] == 'available');
                    $row_class = $is_winning ? 'winning-bid' : '';
            ?>
                <tr class="<?php echo $row_class; ?>">
                    <!-- PRODUCT NAME -->
                    <td><?php echo htmlspecialchars($bid['ProductName']); ?></td>
                    
                    <!-- USER'S BID AMOUNT WITH WINNING INDICATOR -->
                    <td>
                        ৳<?php echo number_format($bid['Amount'], 2); ?>
                        <?php if ($is_winning): ?>
                            <span style="color: green; font-size: 12px;"> ★ WINNING</span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- HIGHEST BID ON THE PRODUCT -->
                    <td>
                        <?php if ($bid['HighestBid']): ?>
                            ৳<?php echo number_format($bid['HighestBid'], 2); ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    
                    <!-- TIME WHEN BID WAS PLACED -->
                    <td><?php echo date('M d, Y H:i', strtotime($bid['TimeStamp'])); ?></td>
                    
                    <!-- BID STATUS -->
                    <td class="<?php echo $bid['Status']; ?>"><?php echo ucfirst($bid['Status']); ?></td>
                    
                    <!-- SELLER CONTACT INFO OR STATUS MESSAGE -->
                    <td>
                        <?php if ($bid['Status'] == 'accepted'): ?>
                            <!-- DISPLAY SELLER CONTACT INFO IF BID ACCEPTED -->
                            <div class="contact-info">
                                <strong>🎉 Your bid was accepted!</strong>
                                <strong>Seller Contact Information:</strong>
                                Name: <?php echo htmlspecialchars($bid['SellerName']); ?><br>
                                Phone: <?php echo htmlspecialchars($bid['SellerPhone']); ?><br>
                                Email: <?php echo htmlspecialchars($bid['SellerEmail']); ?>
                            </div>
                        <?php elseif ($bid['Status'] == 'pending'): ?>
                            <!-- MESSAGE FOR PENDING BIDS -->
                            <em style="color: #666;">Waiting for seller's response...</em>
                        <?php else: ?>
                            <!-- MESSAGE FOR REJECTED BIDS -->
                            <em style="color: #999;">Bid not accepted</em>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php 
                endwhile;
            else:
            ?>
                <!-- MESSAGE IF NO BIDS PLACED -->
                <tr>
                    <td colspan="6" style="text-align: center; color: #666; padding: 50px;">
                        You haven't placed any bids yet. 
                        <a href="dashboard.php" style="color: #007bff;">Browse products</a>
                    </td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>