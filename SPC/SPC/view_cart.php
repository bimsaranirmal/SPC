<?php
session_start();
include 'db.php';
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if user is logged in and is a pharmacy user
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'pharmacy') {
    header("Location: login.php");
    exit();
}

$total_cost = array_sum(array_column($_SESSION['cart'], 'total_price'));
$item_count = count($_SESSION['cart']);

// Get user information
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT name FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_name = ($user_result->num_rows > 0) ? $user_result->fetch_assoc()['name'] : 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Shopping Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #f72585;
            --light-gray: #f8f9fa;
            --medium-gray: #e9ecef;
            --dark-gray: #495057;
            --danger-color: #e63946;
            --success-color: #2a9d8f;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--light-gray);
            color: var(--dark-gray);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 15px auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 1px solid var(--medium-gray);
            padding-bottom: 15px;
        }
        
        h1 {
            color: var(--primary-color);
            font-size: 28px;
        }
        
        .cart-summary {
            background-color: var(--light-gray);
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .cart-summary i {
            color: var(--primary-color);
            margin-right: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            border-radius: 8px;
            overflow: hidden;
        }
        
        th {
            background-color: var(--primary-color);
            color: white;
            text-align: left;
            padding: 15px;
            font-weight: 600;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid var(--medium-gray);
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:nth-child(even) {
            background-color: var(--light-gray);
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
        }
        
        .quantity-btn {
            background-color: var(--medium-gray);
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        
        .quantity-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .quantity-input {
            width: 78px;
            text-align: center;
            margin: 0 10px;
            border: 1px solid var(--medium-gray);
            border-radius: 4px;
            padding: 5px;
        }
        
        .remove-item {
            color: var(--danger-color);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.2s;
        }
        
        .remove-item:hover {
            transform: scale(1.2);
        }
        
        .price {
            font-weight: bold;
        }
        
        .cart-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--medium-gray);
        }
        
        .continue-shopping {
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .continue-shopping i {
            margin-right: 5px;
        }
        
        .continue-shopping:hover {
            text-decoration: underline;
        }
        
        .cart-total {
            font-size: 18px;
            font-weight: bold;
        }
        
        .cart-total span {
            color: var(--primary-color);
            font-size: 24px;
            margin-left: 10px;
        }
        
        .checkout-btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: var(--success-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s, transform 0.2s;
            margin-left: 20px;
        }
        
        .checkout-btn:hover {
            background-color: #218878;
            transform: translateY(-2px);
        }
        
        .checkout-btn i {
            margin-left: 8px;
        }
        
        .empty-cart {
            text-align: center;
            padding: 50px 0;
        }
        
        .empty-cart i {
            font-size: 60px;
            color: var(--medium-gray);
            margin-bottom: 20px;
        }
        
        .empty-cart p {
            font-size: 18px;
            margin-bottom: 20px;
            color: var(--dark-gray);
        }
        .footer-link {
            transition: all 0.2s ease;
        }
        .footer-link:hover {
            color: #60a5fa;
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
                padding: 15px;
            }
            
            header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .cart-summary {
                margin-top: 10px;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .cart-footer {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .cart-actions {
                display: flex;
                flex-direction: column;
                margin-top: 20px;
                width: 100%;
            }
            
            .checkout-btn {
                margin-left: 0;
                margin-top: 15px;
                text-align: center;
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
                        <a href="view_cart.php" class="nav-link active px-3 py-2 text-sm font-medium">
                            <i class="fas fa-shopping-cart mr-1"></i> Cart
                        </a>
                        <a href="view_pharmacy_orders.php" class="nav-link px-3 py-2 text-sm font-medium">
                            <i class="fas fa-clipboard-list mr-1"></i> Orders
                        </a>
                        <a href="tracking_order.php" class="nav-link px-3 py-2 text-sm font-medium">
                            <i class="fas fa-truck mr-1"></i> Track Order
                        </a>
                        <a href="view_inquiries.php" class="nav-link px-3 py-2 text-sm font-medium">
                            <i class="fas fa-envelope-open-text mr-1"></i> View Inquiries
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
                <a href="logout.php" class="flex flex-col items-center text-gray-600">
                    <i class="fas fa-sign-out-alt text-lg"></i>
                    <span class="text-xs mt-1">Logout</span>
                </a>
            </div>
        </div>
    </nav>
    <div class="container">
        <header>
            <h1>Your Shopping Cart</h1>
            <div class="cart-summary">
                <i class="fas fa-shopping-cart"></i> <?= $item_count ?> item<?= $item_count !== 1 ? 's' : '' ?>
            </div>
        </header>
        
        <?php if (empty($_SESSION['cart'])): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <p>Your cart is empty</p>
                <a href="pharmacy_home.php#featured-medicines" class="checkout-btn">Browse Products</a>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Medication</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['cart'] as $index => $item): ?>
                    <tr>
                        <td>
                            <strong>Drug ID: <?= $item['id'] ?></strong>
                        </td>
                        <td>
                            <div class="quantity-controls">
                                <button class="quantity-btn decrease" data-index="<?= $index ?>">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" class="quantity-input" value="<?= $item['quantity'] ?>" min="1" data-index="<?= $index ?>">
                                <button class="quantity-btn increase" data-index="<?= $index ?>">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </td>
                        <td class="price">$<?= number_format($item['unit_price'], 2) ?></td>
                        <td class="price">$<?= number_format($item['total_price'], 2) ?></td>
                        <td>
                            <button class="remove-item" data-index="<?= $index ?>">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="cart-footer">
                <a href="pharmacy_home.php#featured-medicines" class="continue-shopping">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
                
                <div class="cart-actions">
                    <div class="cart-total">
                        Total: <span>$<?= number_format($total_cost, 2) ?></span>
                    </div>
                    <a href="checkout.php" class="checkout-btn">
                        Proceed to Checkout <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Increase quantity
            const increaseButtons = document.querySelectorAll('.increase');
            increaseButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const index = this.getAttribute('data-index');
                    const input = document.querySelector(`.quantity-input[data-index="${index}"]`);
                    input.value = parseInt(input.value) + 1;
                    updateCart(index, input.value);
                });
            });
            
            // Decrease quantity
            const decreaseButtons = document.querySelectorAll('.decrease');
            decreaseButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const index = this.getAttribute('data-index');
                    const input = document.querySelector(`.quantity-input[data-index="${index}"]`);
                    if (parseInt(input.value) > 1) {
                        input.value = parseInt(input.value) - 1;
                        updateCart(index, input.value);
                    }
                });
            });
            
            // Manual quantity input
            const quantityInputs = document.querySelectorAll('.quantity-input');
            quantityInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const index = this.getAttribute('data-index');
                    if (parseInt(this.value) < 1) {
                        this.value = 1;
                    }
                    updateCart(index, this.value);
                });
            });
            
            // Remove item
            const removeButtons = document.querySelectorAll('.remove-item');
            removeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const index = this.getAttribute('data-index');
                    if (confirm('Are you sure you want to remove this item?')) {
                        removeItem(index);
                    }
                });
            });
            
            // Functions to update cart via AJAX
            function updateCart(index, quantity) {
                // This would normally be an AJAX request to update the cart
                console.log(`Update item at index ${index} to quantity ${quantity}`);
                // For demonstration, we'll reload the page after a short delay
                // In a real implementation, you would use AJAX to update the cart without reloading
                setTimeout(() => {
                    window.location.href = `update_cart.php?index=${index}&quantity=${quantity}`;
                }, 300);
            }
            
            function removeItem(index) {
                // This would normally be an AJAX request to remove the item
                console.log(`Remove item at index ${index}`);
                // For demonstration, we'll reload the page
                window.location.href = `remove_cart_item.php?index=${index}`;
            }
        });
    </script>
</div>
</body>
</html>