<?php
// SESSION VERIFICATION
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// DATABASE CONNECTION
include 'db_connect.php';

// SEARCH FUNCTIONALITY
// Get search query if provided and build search condition
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_condition = '';
if ($search) {
    $search_condition = "AND (p.Name LIKE '%$search%' OR p.Category LIKE '%$search%')";
}

// FETCH AVAILABLE PRODUCTS WITH BID INFORMATION
// Get all available products with owner details and bid statistics
$sql = "SELECT p.*, u.Name as OwnerName, u.Phone as OwnerPhone,
        (SELECT MAX(Amount) FROM Bid WHERE Product_ID = p.Product_ID) as HighestBid,
        (SELECT COUNT(*) FROM Bid WHERE Product_ID = p.Product_ID) as BidCount
        FROM Product p 
        JOIN Product_Owner po ON p.Product_ID = po.Product_ID
        JOIN User u ON po.Owner_UID = u.User_ID 
        WHERE p.Status = 'available' $search_condition
        ORDER BY p.Product_ID DESC";
$products = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - University Marketplace</title>
    <style>
        body { font-family: Arial; margin: 0; padding: 0; }
        .header { background: #007bff; color: white; padding: 20px; }
        .nav { background: #333; padding: 10px; }
        .nav a { color: white; text-decoration: none; padding: 10px 20px; display: inline-block; }
        .nav a:hover { background: #555; }
        .search-bar { background: #f8f9fa; padding: 20px; text-align: center; }
        .search-bar input { width: 60%; padding: 12px; font-size: 16px; border: 1px solid #ddd; border-radius: 5px; }
        .search-bar button { padding: 12px 30px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 5px; font-size: 16px; }
        .search-bar button:hover { background: #0056b3; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .product-card { border: 1px solid #ddd; padding: 15px; border-radius: 5px; transition: box-shadow 0.3s; }
        .product-card:hover { box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .product-card img { width: 100%; height: 200px; object-fit: cover; border-radius: 5px; }
        .product-card h3 { margin: 10px 0; }
        .bid-info { background: #e7f3ff; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .no-bids { color: #666; font-style: italic; }
        .btn { padding: 8px 15px; background: #007bff; color: white; text-decoration: none; display: inline-block; border: none; cursor: pointer; border-radius: 3px; }
        .btn:hover { background: #0056b3; }
        .your-product { color: green; font-weight: bold; }
    </style>
</head>
<body>
    <!-- PAGE HEADER WITH WELCOME MESSAGE -->
    <div class="header">
        <h1>University Marketplace</h1>
        <p>Welcome, <?php echo $_SESSION['name']; ?>!</p>
    </div>
    
    <!-- NAVIGATION MENU -->
    <div class="nav">
        <a href="dashboard.php">Home</a>
        <a href="add_product.php">Add Product</a>
        <a href="my_products.php">My Products</a>
        <a href="my_bids.php">My Bids</a>
        <a href="logout.php">Logout</a>
    </div>
    
    <!-- SEARCH BAR -->
    <div class="search-bar">
        <form method="GET" action="dashboard.php">
            <!-- SEARCH INPUT BY PRODUCT NAME OR CATEGORY -->
            <input type="text" name="search" placeholder="Search products by name or category..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            <!-- SEARCH BUTTON -->
            <button type="submit">Search</button>
            <!-- CLEAR SEARCH LINK -->
            <?php if ($search): ?>
                <a href="dashboard.php" style="margin-left: 10px; color: #007bff;">Clear Search</a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="container">
        <!-- PAGE TITLE WITH SEARCH INDICATOR -->
        <h2>Available Products <?php echo $search ? '(Search Results)' : ''; ?></h2>
        
        <!-- PRODUCT GRID LAYOUT -->
        <div class="product-grid">
            <!-- LOOP THROUGH PRODUCTS AND DISPLAY EACH ONE -->
            <?php while ($product = mysqli_fetch_assoc($products)): ?>
                <?php
                // CHECK IF CURRENT USER IS THE PRODUCT OWNER
                $check_owner = "SELECT * FROM Product_Owner 
                               WHERE Product_ID = {$product['Product_ID']} 
                               AND Owner_UID = {$_SESSION['user_id']}";
                $is_owner = mysqli_num_rows(mysqli_query($conn, $check_owner)) > 0;
                ?>
                
                <!-- INDIVIDUAL PRODUCT CARD -->
                <div class="product-card">
                    <!-- PRODUCT IMAGE -->
                    <img src="<?php echo $product['ImageURL'] ?: 'https://via.placeholder.com/250'; ?>" 
                         alt="<?php echo htmlspecialchars($product['Name']); ?>">
                    
                    <!-- PRODUCT NAME -->
                    <h3><?php echo htmlspecialchars($product['Name']); ?></h3>
                    
                    <!-- PRODUCT DETAILS -->
                    <p><strong>Category:</strong> <?php echo htmlspecialchars($product['Category']); ?></p>
                    <p><strong>Seller:</strong> <?php echo htmlspecialchars($product['OwnerName']); ?></p>
                    
                    <!-- BID INFORMATION SECTION -->
                    <div class="bid-info">
                        <?php if ($product['HighestBid']): ?>
                            <!-- DISPLAY CURRENT HIGHEST BID AND COUNT -->
                            <strong>Current Highest Bid:</strong> ৳<?php echo number_format($product['HighestBid'], 2); ?>
                            <br>
                            <small>(<?php echo $product['BidCount']; ?> bid<?php echo $product['BidCount'] > 1 ? 's' : ''; ?>)</small>
                        <?php else: ?>
                            <!-- MESSAGE IF NO BIDS YET -->
                            <span class="no-bids">No bids yet</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- PLACE BID OR OWNERSHIP INDICATOR -->
                    <?php if (!$is_owner): ?>
                        <!-- PLACE BID BUTTON FOR OTHER USERS' PRODUCTS -->
                        <a href="place_bid.php?id=<?php echo $product['Product_ID']; ?>" class="btn">Place Bid</a>
                    <?php else: ?>
                        <!-- INDICATOR THAT THIS IS USER'S PRODUCT -->
                        <span class="your-product">Your Product</span>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- NO PRODUCTS MESSAGE -->
        <?php if (mysqli_num_rows($products) == 0): ?>
            <p style="text-align: center; color: #666; padding: 50px;">
                <?php echo $search ? 'No products found matching your search.' : 'No products available at the moment.'; ?>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>