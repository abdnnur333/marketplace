<?php
// SESSION VERIFICATION
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// DATABASE CONNECTION
include 'db_connect.php';

// INITIALIZE MESSAGE AND ERROR VARIABLES
$message = '';
$error = '';

// HANDLE PRODUCT FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // GET AND SANITIZE PRODUCT INFORMATION
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $owner_id = $_SESSION['user_id'];
    
    // INITIALIZE IMAGE URL VARIABLE
    $image_url = '';
    
    // HANDLE IMAGE UPLOAD
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        // DEFINE ALLOWED IMAGE TYPES
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $file_type = $_FILES['product_image']['type'];
        $file_size = $_FILES['product_image']['size'];
        
        // VALIDATE FILE TYPE AND SIZE (MAX 5MB)
        if (in_array($file_type, $allowed_types) && $file_size <= 5242880) {
            // CREATE UPLOADS DIRECTORY IF IT DOESN'T EXIST
            $upload_dir = 'uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // GENERATE UNIQUE FILENAME FOR THE UPLOADED IMAGE
            $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('product_') . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            // MOVE UPLOADED FILE TO UPLOADS FOLDER
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                $image_url = $upload_path;
            } else {
                $error = "Error uploading image.";
            }
        } else {
            // SET ERROR IF FILE TYPE OR SIZE INVALID
            $error = "Invalid file type or file too large (max 5MB). Allowed: JPG, PNG, GIF";
        }
    } elseif (!empty($_POST['image_url'])) {
        // USE MANUAL URL IF PROVIDED INSTEAD OF UPLOAD
        $image_url = mysqli_real_escape_string($conn, $_POST['image_url']);
    }
    
    // INSERT PRODUCT INTO DATABASE IF NO ERRORS
    if (!$error) {
        // INSERT PRODUCT WITH INITIAL PRICE OF 0 (BIDDING STARTS AT ANY AMOUNT)
        $sql = "INSERT INTO Product (Name, Category, CurrentPrice, ImageURL, Status) 
                VALUES ('$name', '$category', 0, '$image_url', 'available')";
        
        if (mysqli_query($conn, $sql)) {
            // GET THE NEWLY INSERTED PRODUCT ID
            $product_id = mysqli_insert_id($conn);
            
            // LINK PRODUCT TO OWNER IN PRODUCT_OWNER TABLE
            $owner_sql = "INSERT INTO Product_Owner (Product_ID, Owner_UID) 
                          VALUES ('$product_id', '$owner_id')";
            
            if (mysqli_query($conn, $owner_sql)) {
                $message = "Product added successfully!";
            } else {
                $error = "Error linking owner: " . mysqli_error($conn);
            }
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product - University Marketplace</title>
    <style>
        body { font-family: Arial; margin: 0; }
        .header { background: #007bff; color: white; padding: 20px; }
        .nav { background: #333; padding: 10px; }
        .nav a { color: white; text-decoration: none; padding: 10px 20px; display: inline-block; }
        .nav a:hover { background: #555; }
        .container { max-width: 600px; margin: 50px auto; padding: 20px; }
        input, select { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
        input[type="file"] { padding: 8px; border: 1px solid #ddd; }
        button { width: 100%; padding: 12px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 3px; font-size: 16px; }
        button:hover { background: #0056b3; }
        .message { padding: 10px; background: #d4edda; color: #155724; margin-bottom: 20px; border-radius: 5px; }
        .error { padding: 10px; background: #f8d7da; color: #721c24; margin-bottom: 20px; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .divider { text-align: center; margin: 20px 0; color: #666; position: relative; }
        .divider:before, .divider:after { content: ''; position: absolute; top: 50%; width: 45%; height: 1px; background: #ddd; }
        .divider:before { left: 0; }
        .divider:after { right: 0; }
        .note { color: #666; font-size: 13px; margin-top: 5px; }
        .preview-container { margin: 10px 0; display: none; }
        .preview-container img { max-width: 100%; max-height: 200px; border-radius: 5px; border: 1px solid #ddd; }
    </style>
    <script>
        // JAVASCRIPT FUNCTION TO PREVIEW IMAGE BEFORE UPLOAD
        function previewImage(input) {
            // GET PREVIEW ELEMENTS
            const preview = document.getElementById('imagePreview');
            const previewContainer = document.getElementById('previewContainer');
            
            // CHECK IF FILE IS SELECTED
            if (input.files && input.files[0]) {
                // CREATE FILE READER OBJECT
                const reader = new FileReader();
                // SET PREVIEW IMAGE WHEN FILE IS LOADED
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.style.display = 'block';
                }
                // READ THE SELECTED FILE
                reader.readAsDataURL(input.files[0]);
            } else {
                // HIDE PREVIEW IF NO FILE SELECTED
                previewContainer.style.display = 'none';
            }
        }
    </script>
</head>
<body>
    <div class="header">
        <h1>Add New Product</h1>
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
        
        <!-- PRODUCT ADDITION FORM -->
        <form method="POST" enctype="multipart/form-data">
            <!-- PRODUCT NAME FIELD -->
            <div class="form-group">
                <label for="name">Product Name *</label>
                <input type="text" id="name" name="name" placeholder="e.g., iPhone 12 Pro" required>
            </div>
            
            <!-- PRODUCT CATEGORY DROPDOWN -->
            <div class="form-group">
                <label for="category">Category *</label>
                <select id="category" name="category" required>
                    <option value="">Select Category</option>
                    <option value="Electronics">Electronics</option>
                    <option value="Books">Books</option>
                    <option value="Furniture">Furniture</option>
                    <option value="Clothing">Clothing</option>
                    <option value="Sports">Sports & Fitness</option>
                    <option value="Stationery">Stationery</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <!-- PRODUCT IMAGE UPLOAD FIELD -->
            <div class="form-group">
                <label for="product_image">Upload Product Image</label>
                <input type="file" 
                       id="product_image" 
                       name="product_image" 
                       accept="image/jpeg,image/png,image/jpg,image/gif"
                       onchange="previewImage(this)">
                <p class="note">Max file size: 5MB. Allowed formats: JPG, PNG, GIF</p>
                
                <!-- IMAGE PREVIEW SECTION -->
                <div id="previewContainer" class="preview-container">
                    <img id="imagePreview" src="" alt="Preview">
                </div>
            </div>
            
            <!-- DIVIDER BETWEEN UPLOAD AND URL OPTIONS -->
            <div class="divider">OR</div>
            
            <!-- ALTERNATIVE: PROVIDE IMAGE URL -->
            <div class="form-group">
                <label for="image_url">Image URL (optional)</label>
                <input type="url" id="image_url" name="image_url" placeholder="https://example.com/image.jpg">
                <p class="note">Provide a direct link to an image if you prefer not to upload</p>
            </div>
            
            <!-- SUBMIT BUTTON -->
            <button type="submit">Add Product</button>
        </form>
        
        <!-- INFORMATION NOTE ABOUT BIDDING -->
        <p style="color: #666; font-size: 14px; margin-top: 20px; background: #f8f9fa; padding: 15px; border-radius: 5px;">
            <strong>📝 Note:</strong> Your product will be listed for bidding. Buyers will place their bids and you can accept the highest bid. No initial price needed - let the market decide!
        </p>
    </div>
</body>
</html>