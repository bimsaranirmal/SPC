<?php
session_start();
include 'db.php';

// Check if user is logged in and is a pharmacy user
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'pharmacy') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all orders for the current user
$sql = "SELECT po.id as order_id, po.drug_id, po.quantity, po.total_price, po.order_date, po.status,
        d.name as drug_name, d.description, u.address, 
        COALESCE(ot.tracking_id, 'Not Assigned') AS tracking_id
        FROM pharmacy_orders po
        JOIN drugs d ON po.drug_id = d.id
        JOIN users u ON po.user_id = u.id
        LEFT JOIN order_tracking ot ON po.id = ot.order_id
        WHERE po.user_id = ?
        ORDER BY po.order_date DESC";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Get user information
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT name FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_name = ($user_result->num_rows > 0) ? $user_result->fetch_assoc()['name'] : 'User';

// cancel order
//if (isset($_POST['cancel_order'])) {
   // $order_id = $_POST['order_id'];
   // $cancel_sql = "UPDATE pharmacy_orders SET status = 'Cancelled' WHERE id = ? AND user_id = ?";
   // $cancel_stmt = $conn->prepare($cancel_sql);
   // $cancel_stmt->bind_param("ii", $order_id, $user_id);
   // if ($cancel_stmt->execute()) {
   //     $_SESSION['alert'] = "Order cancelled successfully!";
   // } else {
   //     $_SESSION['alert'] = "Failed to cancel order.";
   // }
   // header("Location: view_pharmacy_orders.php");
   // exit();
//}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - SPC Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7fa;
            color: #333;
        }
        .header-gradient {
            background: linear-gradient(135deg, #4299E1, #2D3748);
        }
        .order-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }
        .empty-orders {
            min-height: 300px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
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
        .footer-link {
            transition: all 0.2s ease;
        }
        .footer-link:hover {
            color: #60a5fa;
        }
    </style>
</head>
<body class="antialiased">
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
                        <a href="view_pharmacy_orders.php" class="nav-link active px-3 py-2 text-sm font-medium">
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

    <!-- Header Section -->
    <div class="header-gradient text-white py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold">My Orders</h1>
            <p class="mt-2">View and track all your medication orders</p>
        </div>
    </div>

    <!-- Orders Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($result->num_rows > 0): ?>
            <div class="grid grid-cols-1 gap-6">
                <?php while ($order = $result->fetch_assoc()): ?>
                    <div class="bg-white p-6 rounded-lg shadow-md order-card">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                    Order #<?= $order['order_id'] ?>
                                </span>
                                <h2 class="text-xl font-bold mt-2"><?= htmlspecialchars($order['drug_name']) ?></h2>
                                <p class="text-gray-600 mt-1"><?= htmlspecialchars($order['description']) ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">
                                    Ordered on: <?= date('M d, Y', strtotime($order['order_date'])) ?>
                                </p>
                                <p class="text-green-600 font-bold text-lg mt-1">
                                    $<?= number_format($order['total_price'], 2) ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="mt-4 flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="text-gray-700">Quantity:</span>
                                <span class="ml-2 font-semibold"><?= $order['quantity'] ?></span>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="showOrderDetails(<?= $order['order_id'] ?>, '<?= $order['status'] ?>', '<?= htmlspecialchars($order['address']) ?>' , '<?= htmlspecialchars($order['tracking_id']) ?>')" 
                                        class="bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200 transition">
                                    View Details
                                </button>
                                <button onclick="reorderItem(<?= $order['drug_id'] ?>, <?= $order['quantity'] ?>)" class="bg-green-100 text-green-700 px-3 py-1 rounded hover:bg-green-200 transition">
                                    Reorder
                                </button>
                                <?php if ($order['status'] !== 'Cancelled'): ?>
                                    <form method='POST' action='cancel_pharmacy_orders.php'>
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <button type="submit" name="cancel_order" 
                                                class="bg-red-100 text-red-700 px-3 py-1 rounded hover:bg-red-200 transition" onclick="return confirm('Are you sure you want to cancel this order?')">
                                            Cancel
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-md p-8 empty-orders text-center">
                <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
                <h2 class="text-2xl font-bold text-gray-700">No Orders Yet</h2>
                <p class="text-gray-500 mt-2 mb-6">You haven't placed any orders yet.</p>
                <a href="pharmacy_home.php" class="bg-blue-500 text-white px-6 py-2 rounded-md hover:bg-blue-600 transition">
                    Browse Medicines
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Order Details Modal -->
    <div id="orderModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded-lg max-w-lg w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Order Details</h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="orderDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="mt-6 flex justify-end">
                <button onclick="closeModal()" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300 transition">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                <div>
                    <div class="flex items-center mb-4">
                        <img class="h-10 w-auto" src="images/OIP-removebg-preview.png" alt="SPC Pharmacy Logo">
                        <span class="ml-2 text-xl font-semibold">SPC Pharmacy</span>
                    </div>
                    <p class="text-gray-400 mb-4">Your trusted healthcare partner since 2010. We are committed to providing quality medications and exceptional service.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-white footer-link">About Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white footer-link">Our Services</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white footer-link">Privacy Policy</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white footer-link">Terms & Conditions</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact Us</h3>
                    <ul class="space-y-3 text-gray-400">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-3 text-gray-500"></i>
                            <span>123 Healthcare Avenue, Medical District, CA 91234</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone-alt mr-3 text-gray-500"></i>
                            <span>+1 (555) 123-4567</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-3 text-gray-500"></i>
                            <span>support@spcpharmacy.com</span>
                        </li>
                    </ul>
                    
                    <div class="mt-6">
                        <h4 class="text-sm font-semibold mb-2">Subscribe to our newsletter</h4>
                        <div class="flex">
                            <input 
                                type="email" 
                                placeholder="Your email" 
                                class="w-full px-3 py-2 text-sm rounded-l-md bg-gray-700 border-gray-600 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                            <button class="bg-blue-600 text-white px-4 py-2 rounded-r-md hover:bg-blue-700 transition text-sm">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-10 pt-6 text-center text-gray-400 text-sm">
                <p>&copy; <?= date('Y') ?> SPC Pharmacy. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Initialize Feather Icons
        feather.replace();

        // Show order details modal
        function showOrderDetails(orderId, status, address, trackingId) {
            const modal = document.getElementById('orderModal');
            const content = document.getElementById('orderDetailsContent');
            
            // Simulating order details fetching
            content.innerHTML = `
                <div class="animate-pulse">
                    <p class="text-center text-gray-500">Loading order #${orderId} details...</p>
                </div>
            `;
            
            modal.classList.remove('hidden');
            
            // Simulate AJAX request with setTimeout
            setTimeout(() => {
                // This would normally come from a server response
                content.innerHTML = `
                    <div class="border-b pb-4">
                        <p class="text-sm text-gray-500">Order ID:</p>
                        <p class="font-semibold">#${orderId}</p>
                    </div>
                    <div class="border-b py-4">
                        <p class="text-sm text-gray-500">Order Status:</p>
                        <p class="font-semibold">${status}</p>
                    </div>
                    <div class="border-b py-4">
                        <p class="text-sm text-gray-500">Payment Method:</p>
                        <p class="font-semibold">Cash On Delivery (Default Method)</p>
                    </div>
                    <div class="py-4">
                        <p class="text-sm text-gray-500">Shipping Address:</p>
                        <p class="font-semibold">${address}</p>
                    </div>
                    <div class="py-4">
                        <p class="text-sm text-gray-500">Tracking ID:</p>
                        <p class="font-semibold">${trackingId}</p>
                    </div>
                `;
            }, 1000);
        }

        // Close modal
        function closeModal() {
            const modal = document.getElementById('orderModal');
            modal.classList.add('hidden');
        }

        // Reorder item
        function reorderItem(drugId, quantity) {
            // Create a form to submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'add_to_cart.php';
            
            // Add drug_id input (use 'id' as the key to match PHP code)
            const drugIdInput = document.createElement('input');
            drugIdInput.type = 'hidden';
            drugIdInput.name = 'id'; // Ensure this matches the PHP code
            drugIdInput.value = drugId;
            form.appendChild(drugIdInput);
            
            // Add quantity input
            const quantityInput = document.createElement('input');
            quantityInput.type = 'hidden';
            quantityInput.name = 'quantity';
            quantityInput.value = quantity;
            form.appendChild(quantityInput);
            
            // Append form to body and submit
            document.body.appendChild(form);
            form.submit();
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('orderModal');
            if (event.target === modal) {
                closeModal();
            }
        });

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