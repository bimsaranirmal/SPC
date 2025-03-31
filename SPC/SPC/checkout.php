<?php
session_start();
include 'db.php';

//check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=Please login to continue");
    exit();
}
$sql_user = "SELECT name FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);

$sql = "SELECT id, name, drug_id FROM drugs WHERE id = ?";
$stmt = $conn->prepare($sql);
$total = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['cart'])) {
    $user_id = $_SESSION['user_id'];

    // Updated SQL to use the correct column name 'id' instead of 'drug_id'
    $sql = "INSERT INTO pharmacy_orders (user_id, drug_id, quantity, total_price) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    foreach ($_SESSION['cart'] as $item) {
        $drug_id = $item['id'];
        $quantity = $item['quantity'];
        $total_price = $item['total_price'];
    
        // Bind parameters and execute
        $stmt->bind_param("iiid", $user_id, $drug_id, $quantity, $total_price);
        if ($stmt->execute()) { // Execute only once
            $order_id = $conn->insert_id; // Get the last inserted order ID
            $tracking_id = strtoupper(substr(md5(uniqid()), 0, 8)); // Generate unique tracking ID
    
            // Prepare tracking statement
            $track_sql = "INSERT INTO order_tracking (order_id, user_id, drug_id, tracking_id) VALUES (?, ?, ?, ?)";
            $track_stmt = $conn->prepare($track_sql);
            $track_stmt->bind_param("iiis", $order_id, $user_id, $drug_id, $tracking_id);
    
            if ($track_stmt->execute()) {
                echo "Order placed! Your Tracking ID: <strong>$tracking_id</strong>";
            }
            $track_stmt->close();
        } else {
            echo "Order failed: " . $stmt->error;
        }
    }

    $_SESSION['cart'] = [];
    $_SESSION['alert'] = "Order Placed Successfully! Your Tracking ID: <strong>$tracking_id</strong>";
    header("Location: checkout.php");
    exit();
}

// Get user name
if (isset($_SESSION['user_id'])) {
    $stmt_user->bind_param("i", $_SESSION['user_id']);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user = $result_user->fetch_assoc();
    $user_name = $user ? $user['name'] : 'User';
}
else {
    $user_name = 'Guest';
}   

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - SPC Pharmacy</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
        }
        
        /* Header */
        .header {
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #4285f4;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .logo img {
            height: 40px;
        }
        
        .nav-link {
            position: relative;
            transition: all 0.3s ease;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: #3b82f6;
            transition: width 0.3s ease;
        }
        .nav-link:hover::after {
            width: 100%;
        }
        .nav-link.active {
            color: #3b82f6;
            font-weight: 500;
        }
        .nav-link.active::after {
            width: 100%;
        }
        
        .user-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #4285f4;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .logout-btn {
            background-color: #4285f4;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .logout-btn:hover {
            background-color: #3b78e7;
        }
        
        /* Container */
        .main-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .card-title {
            font-size: 1.5rem;
            color: #333;
            font-weight: 600;
        }
        
        .cart-count {
            background-color: #f1f3f4;
            padding: 5px 12px;
            border-radius: 20px;
            color: #5f6368;
            font-size: 0.9rem;
        }
        
        /* Cart Items */
        .cart-items {
            margin-bottom: 20px;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-name {
            font-weight: 500;
            color: #202124;
        }
        
        .item-quantity {
            color: #5f6368;
        }
        
        .item-price {
            font-weight: 500;
            color: #202124;
        }
        
        /* Empty Cart */
        .empty-cart {
            text-align: center;
            padding: 40px 0;
            color: #5f6368;
        }
        
        .empty-cart i {
            font-size: 4rem;
            color: #dadce0;
            margin-bottom: 15px;
        }
        
        .empty-cart p {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
        
        /* Total Section */
        .order-summary {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .total-section {
            display: flex;
            justify-content: space-between;
            font-size: 1.7rem;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        /* Buttons */
        .btn-container {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 4px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn-browse {
            background-color: #34a853;
            color: white;
        }
        
        .btn-browse:hover {
            background-color: #2d9249;
        }
        
        .btn-browse i{
            font-size: 1rem;
            margin-right: 5px;
            color: #dadce0;
        }
        .btn-primary {
            background-color: #4285f4;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #3b78e7;
        }
        
        .btn-secondary {
            background-color: #f1f3f4;
            color: #5f6368;
        }
        
        .btn-secondary:hover {
            background-color: #e8eaed;
        }
    </style>
</head>
<body>
     <!-- Navigation -->
     <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <img class="h-10 w-auto" src="images/OIP-removebg-preview.png" alt="SPC Pharmacy Logo">
                        <span class="ml-2 text-lg font-semibold text-blue-800">SPC Pharmacy</span>
                    </div>
                    <div class="hidden sm:ml-8 sm:flex sm:space-x-8">
                        <a href="pharmacy_home.php" class="nav-link px-3 py-2 text-sm font-medium">
                            <i class="fas fa-home mr-1"></i> Home
                        </a>
                        <a href="view_cart.php" class="nav-link px-3 active py-2 text-sm font-medium">
                            <i class="fas fa-shopping-cart mr-1"></i> Cart
                        </a>
                        <a href="view_pharmacy_orders.php" class="nav-link px-3 py-2 text-sm font-medium">
                            <i class="fas fa-clipboard-list mr-1"></i> Orders
                        </a>
                        <a href="tracking_order.php" class="nav-link px-3 py-2 text-sm font-medium">
                            <i class="fas fa-truck mr-1"></i> Track Order
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="hidden md:flex items-center px-3 py-1 bg-gray-100 text-gray-700 rounded-full">
                        <i class="fas fa-user-circle mr-2"></i>
                        <span class="text-sm"><?= htmlspecialchars($user_name) ?></span>
                    </div>
                    <a href="javascript:void(0);" onclick="confirmLogout()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition flex items-center text-sm font-medium">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>
        <!-- Mobile Navigation -->
        <div class="sm:hidden border-t">
            <div class="flex justify-between py-2 px-4">
                <a href="pharmacy_home.php" class="flex flex-col items-center text-blue-600">
                    <i class="fas fa-home text-lg"></i>
                    <span class="text-xs mt-1">Home</span>
                </a>
                <a href="view_cart.php" class="flex flex-col items-center text-gray-600">
                    <i class="fas fa-shopping-cart text-lg"></i>
                    <span class="text-xs mt-1">Cart</span>
                </a>
                <a href="view_pharmacy_orders.php" class="flex flex-col items-center text-gray-600">
                    <i class="fas fa-clipboard-list text-lg"></i>
                    <span class="text-xs mt-1">Orders</span>
                </a>
                <a href="logout.php" class="flex flex-col items-center text-gray-600">
                    <i class="fas fa-sign-out-alt text-lg"></i>
                    <span class="text-xs mt-1">Logout</span>
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Alert Message (if exists) -->
    <?php if(isset($_SESSION['alert'])): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="alert bg-green-100 text-green-800 flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2 text-green-500"></i>
                <?= $_SESSION['alert'] ?>
            </div>
            <button onclick="this.parentElement.style.display='none'" class="text-green-500 hover:text-green-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <?php unset($_SESSION['alert']); endif; ?>

    <!-- Main Container -->
    <div class="main-container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Your Shopping Cart</h1>
                <span class="cart-count">
                    <?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?> items
                </span>
            </div>
            
            <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                <div class="cart-items">
                    <?php
                    foreach ($_SESSION['cart'] as $item) {
                        // Prepare a new SQL query for each drug
                        $select_sql = "SELECT drug_id, name FROM drugs WHERE id = ?";
                        $select_stmt = $conn->prepare($select_sql);
                        $select_stmt->bind_param("i", $item['id']); // Bind as string

                        // Execute the query
                        if (!$select_stmt->execute()) {
                            error_log("Query failed: " . $select_stmt->error);
                            continue; // Skip this iteration if the query fails
                        }

                        // Fetch the result
                        $result = $select_stmt->get_result();
                        $drug = $result->fetch_assoc();

                        // Debugging: Check if the drug was found
                        if (!$drug) {
                            error_log("No drug found with ID: " . $item['id']);
                            $drug_name = 'Unknown Drug';
                        } else {
                            $drug_name = $drug['name'];
                        }

                        // Add the total price
                        $total += $item['total_price'];
                    ?>
                        <div class="cart-item">
                            <div class="item-name"><?php echo htmlspecialchars($drug_name); ?></div>
                            <div class="item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                            <div class="item-price">$<?php echo number_format($item['total_price'], 2); ?></div>
                        </div>
                    <?php
                    }
                    ?>
                </div>
                
                <div class="order-summary">
                    <div class="total-section">
                        <span>Order Total:</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
                
                <div class="btn-container">
                    <a href="view_cart.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Cart
                    </a>
                    <form method="POST">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check-circle"></i> Confirm Order
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Your cart is empty</p>
                    <a href="pharmacy_home.php" class="btn btn-browse">
                        <i class="fas fa-search"></i> Browse Products
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
    function confirmLogout() {
        Swal.fire({
            title: 'Are you sure?',
            text: "You will be logged out of your session.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, Logout',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect to logout.php
                window.location.href = 'logout.php';
            }
        });
    }
</script>
</body>
</html>