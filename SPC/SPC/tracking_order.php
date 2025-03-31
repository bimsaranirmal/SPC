<?php
include 'db.php';
$tracking_result = null;
$tracking_error = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tracking_id = mysqli_real_escape_string($conn, $_POST['tracking_id']);
    $query = "SELECT po.id AS order_id, u.email, d.name, po.quantity, po.total_price, ot.status, 
              ot.updated_at
              FROM order_tracking ot
              JOIN pharmacy_orders po ON ot.order_id = po.id
              JOIN users u ON ot.user_id = u.id
              JOIN drugs d ON ot.drug_id = d.id
              WHERE ot.tracking_id=?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $tracking_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $tracking_result = mysqli_fetch_assoc($result);
    } else {
        $tracking_error = "No order found with tracking ID: " . htmlspecialchars($tracking_id);
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracking - SPC Pharmacy</title>
    <link rel="shortcut icon" href="images/OIP-removebg-preview.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #4285f4;
            --secondary-color: #34a853;
            --accent-color: #fbbc05;
            --error-color: #ea4335;
            --text-color: #202124;
            --text-secondary: #5f6368;
            --background-color: #f8f9fa;
            --card-color: #ffffff;
            --border-color: #dadce0;
            --shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        header {
            background-color: var(--card-color);
            box-shadow: var(--shadow);
            padding: 20px 0;
            margin-bottom: 30px;
        }
        
        .header-content {
            display: flex;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .logo img {
            height: 40px;
        }
        
        .card {
            background-color: var(--card-color);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .card-title {
            font-size: 1.5rem;
            color: var(--text-color);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .search-form {
            max-width: 600px;
            margin: 0 auto 40px;
        }
        
        .form-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 20px 15px 50px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .form-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn:hover {
            background-color: #3b78e7;
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .result-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .tracking-id {
            font-size: 1.2rem;
            font-weight: 500;
            color: var(--text-secondary);
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .status-processing {
            background-color: #e8f0fe;
            color: var(--primary-color);
        }
        
        .status-shipped {
            background-color: #e6f4ea;
            color: var(--secondary-color);
        }
        
        .status-delivered {
            background-color: #fef7e0;
            color: #f9ab00;
        }
        
        .status-cancelled {
            background-color: #fce8e6;
            color: var(--error-color);
        }
        
        .order-details {
            margin-bottom: 30px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 500;
            color: var(--text-secondary);
        }
        
        .detail-value {
            font-weight: 500;
            text-align: right;
        }
        
        .tracking-steps {
            margin-top: 40px;
        }
        
        .step {
            display: flex;
            position: relative;
            padding-bottom: 30px;
        }
        
        .step:last-child {
            padding-bottom: 0;
        }
        
        .step:before {
            content: "";
            position: absolute;
            left: 15px;
            top: 30px;
            bottom: 0;
            width: 2px;
            background-color: var(--border-color);
        }
        
        .step:last-child:before {
            display: none;
        }
        
        .step-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--card-color);
            border: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            position: relative;
            z-index: 2;
        }
        
        .step.active .step-icon {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .step-content {
            flex: 1;
        }
        
        .step-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .step-time {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background-color: #fce8e6;
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }
        
        .placeholder-content {
            text-align: center;
            padding: 40px 0;
        }
        
        .placeholder-icon {
            font-size: 5rem;
            color: var(--border-color);
            margin-bottom: 20px;
        }
        
        .placeholder-text {
            color: var(--text-secondary);
            font-size: 1.2rem;
            margin-bottom: 20px;
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

        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }
            
            .card {
                padding: 20px;
            }
            
            .form-control {
                padding: 12px 15px 12px 45px;
            }
            
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
            
            .detail-value {
                text-align: left;
            }
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
                        <a href="view_cart.php" class="nav-link px-3 py-2 text-sm font-medium">
                            <i class="fas fa-shopping-cart mr-1"></i> Cart
                        </a>
                        <a href="view_pharmacy_orders.php" class="nav-link px-3 py-2 text-sm font-medium">
                            <i class="fas fa-clipboard-list mr-1"></i> Orders
                        </a>
                        <a href="tracking_order.php" class="nav-link active px-3 py-2 text-sm font-medium">
                            <i class="fas fa-truck mr-1"></i> Track Order
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                   
                <a href="javascript:void(0);" onclick="confirmLogout()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition flex items-center text-sm font-medium">
    <i class="fas fa-sign-out-alt mr-2"></i> Logout
</a>

                    <script>
                        function confirmLogout() {
                            Swal.fire({
                                title: 'Are you sure?',
                                text: "You will be logged out.",
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'Yes, logout!'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'logout.php';
                                }
                            });
                        }
                    </script>
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
                <a href="tracking_order.php" class="flex flex-col items-center text-gray-600">
                    <i class="fas fa-truck text-lg"></i>
                    <span class="text-xs mt-1">Track Order</span>
                </a>
                <a href="logout.php" class="flex flex-col items-center text-gray-600">
                    <i class="fas fa-sign-out-alt text-lg"></i>
                    <span class="text-xs mt-1">Logout</span>
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="card">
            <h1 class="card-title">Track Your Order</h1>
            
            <form method="post" class="search-form">
                <div class="form-group">
                    <i class="fas fa-search form-icon"></i>
                    <input type="text" name="tracking_id" class="form-control" placeholder="Enter your tracking ID" value="<?php echo isset($_POST['tracking_id']) ? htmlspecialchars($_POST['tracking_id']) : ''; ?>" required>
                </div>
                <button type="submit" class="btn btn-block">
                    <i class="fas fa-truck-moving"></i> Track Order
                </button>
            </form>
            
            <?php if ($tracking_error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $tracking_error; ?>
                </div>
            <?php elseif ($tracking_result): ?>
                <div class="result-container">
                    <div class="result-header">
                        <div class="tracking-id">
                            Tracking ID: <strong><?php echo htmlspecialchars($_POST['tracking_id']); ?></strong>
                        </div>
                        <?php
                        $status_class = '';
                        switch(strtolower($tracking_result['status'])) {
                            case 'processing':
                                $status_class = 'status-processing';
                                break;
                            case 'shipped':
                                $status_class = 'status-shipped';
                                break;
                            case 'delivered':
                                $status_class = 'status-delivered';
                                break;
                            case 'cancelled':
                                $status_class = 'status-cancelled';
                                break;
                            default:
                                $status_class = 'status-processing';
                        }
                        ?>
                        <div class="status-badge <?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($tracking_result['status']); ?>
                        </div>
                    </div>
                    
                    <div class="order-details">
                        <div class="detail-row">
                            <div class="detail-label">Order ID</div>
                            <div class="detail-value">#<?php echo htmlspecialchars($tracking_result['order_id']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Customer</div>
                            <div class="detail-value"><?php echo htmlspecialchars($tracking_result['email']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Product</div>
                            <div class="detail-value"><?php echo htmlspecialchars($tracking_result['name']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Quantity</div>
                            <div class="detail-value"><?php echo htmlspecialchars($tracking_result['quantity']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Total Price</div>
                            <div class="detail-value">$<?php echo number_format($tracking_result['total_price'], 2); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Order Date</div>
                            <div class="detail-value"><?php echo date('F j, Y', strtotime($tracking_result['updated_at'])); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Last Updated</div>
                            <div class="detail-value"><?php echo date('F j, Y g:i A', strtotime($tracking_result['updated_at'])); ?></div>
                        </div>
                    </div>
                    
                    <div class="tracking-steps">
                        <h3>Tracking History</h3>
                        
                        <?php
                        $current_status = strtolower($tracking_result['status']);
                        $steps = [
                            'Pending' => [
                                'title' => 'Pending',
                                'active' => in_array($current_status, ['Pending', 'Shipped', 'Delivered']),
                                'time' => $current_status === 'Pending' ? date('F j, Y', strtotime($tracking_result['updated_at'])) : date('F j, Y', strtotime('+1 day', strtotime($tracking_result['updated_at']))),
                                'icon' => 'fa-box'
                            ],
                            'Shipped' => [
                                'title' => 'Shipped',
                                'active' => in_array($current_status, ['Shipped', 'delivered']),
                                'time' => $current_status === 'Shipped' ? date('F j, Y', strtotime($tracking_result['updated_at'])) : ($current_status === 'delivered' ? date('F j, Y', strtotime('-2 days', strtotime($tracking_result['updated_at']))) : ''),
                                'icon' => 'fa-shipping-fast'
                            ],
                            'Delivered' => [
                                'title' => 'Delivered',
                                'active' => $current_status === 'Delivered',
                                'time' => $current_status === 'Delivered' ? date('F j, Y', strtotime($tracking_result['updated_at'])) : '',
                                'icon' => 'fa-check-circle'
                            ]
                        ];
                        
                        // Handle cancelled orders
                        if ($current_status === 'cancelled') {
                            $steps = [
                                'order_placed' => $steps['order_placed'],
                                'cancelled' => [
                                    'title' => 'Order Cancelled',
                                    'active' => true,
                                    'time' => date('F j, Y', strtotime($tracking_result['updated_at'])),
                                    'icon' => 'fa-times-circle'
                                ]
                            ];
                        }
                        
                        foreach ($steps as $step) {
                            if (!$step['active'] && empty($step['time'])) continue;
                        ?>
                            <div class="step <?php echo $step['active'] ? 'active' : ''; ?>">
                                <div class="step-icon">
                                    <i class="fas <?php echo $step['icon']; ?>"></i>
                                </div>
                                <div class="step-content">
                                    <div class="step-title"><?php echo $step['title']; ?></div>
                                    <?php if (!empty($step['time'])): ?>
                                        <div class="step-time"><?php echo $step['time']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php
                        }
                        ?>
                    </div>
                </div>
            <?php elseif (!isset($_POST['tracking_id'])): ?>
                <div class="placeholder-content">
                    <div class="placeholder-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="placeholder-text">
                        Enter your tracking ID to see order status
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Focus the tracking input field on page load
            const trackingInput = document.querySelector('input[name="tracking_id"]');
            if (trackingInput) {
                trackingInput.focus();
            }
            
            // Simple form validation
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(event) {
                    const trackingId = trackingInput.value.trim();
                    if (!trackingId) {
                        event.preventDefault();
                        trackingInput.classList.add('error');
                        setTimeout(() => {
                            trackingInput.classList.remove('error');
                        }, 1500);
                    }
                });
            }
        });
    </script>
</body>
</html>