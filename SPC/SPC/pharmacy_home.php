<?php
session_start();
include 'db.php'; // Include database connection

// Check if user is logged in and is a pharmacy user
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'pharmacy') {
    header("Location: login.php");
    exit();
}

// Handle search requests
if (isset($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $stmt = $conn->prepare("SELECT id, drug_id, name, unit_price, stock FROM drugs WHERE name LIKE ?");
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $search_result = $stmt->get_result();
    $drugs = [];
    while ($row = $search_result->fetch_assoc()) {
        $drugs[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($drugs);
    exit();
}

// Fetch drugs from the database
$sql = "SELECT * FROM drugs LIMIT 8"; // Show 8 featured medicines for better display
$result = $conn->query($sql);

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
    <title>SPC Pharmacy - Your Trusted Healthcare Partner</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        .hero-gradient {
            background: linear-gradient(135deg, #3b82f6, #1e40af);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
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
        .medicine-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .medicine-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0,0,0,0.1);
        }
        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        .quantity-input {
            width: 60px;
            text-align: center;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
        }
        .add-to-cart-btn {
            transition: all 0.3s ease;
        }
        .add-to-cart-btn:hover {
            transform: translateY(-2px);
        }
        .footer-link {
            transition: all 0.2s ease;
        }
        .footer-link:hover {
            color: #60a5fa;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        /* Alert message styling */
        .alert {
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(-10px); }
            100% { opacity: 1; transform: translateY(0); }
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
                        <a href="pharmacy_home.php" class="nav-link active px-3 py-2 text-sm font-medium">
                            <i class="fas fa-home mr-1"></i> Home
                        </a>
                        <a href="view_cart.php" class="nav-link px-3 py-2 text-sm font-medium">
                            <i class="fas fa-shopping-cart mr-1"></i> Cart
                        </a>
                        <a href="view_pharmacy_orders.php" class="nav-link px-3 py-2 text-sm font-medium">
                            <i class="fas fa-clipboard-list mr-1"></i> Orders
                        </a>
                        <a href="submit_inquiry.php" class="nav-link px-3 py-2 text-sm font-medium">
                            <i class="fas fa-question-circle mr-1"></i> Submit Inquiry
                        </a>
                        <a href="view_inquiries.php" class="nav-link px-3 py-2 text-sm font-medium">
                            <i class="fas fa-envelope-open-text mr-1"></i> View Inquiries
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-4 cursor-pointer">
                    <div class="hidden md:flex items-center px-3 py-1 bg-gray-100 text-gray-700 rounded-full" id="userInfoTrigger">
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
                <a href="submit_inquiry.php" class="flex flex-col items-center text-gray-600">
                    <i class="fas fa-question-circle text-lg"></i>
                    <span class="text-xs mt-1">Submit Inquiry</span>
                </a>
                <a href="view_inquiries.php" class="flex flex-col items-center text-gray-600">
                    <i class="fas fa-envelope-open-text text-lg"></i>
                    <span class="text-xs mt-1">View Inquiries</span>
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

    <!-- Hero Section -->
    <div class="hero-gradient text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="md:flex md:items-center">
                <div class="md:w-1/2 mb-10 md:mb-0">
                    <h1 class="text-3xl md:text-4xl font-bold mb-4 leading-tight">Welcome to SPC Pharmacy</h1>
                    <p class="text-lg md:text-xl mb-8 opacity-90">Your Trusted Healthcare Partner - Quality Medications Delivered Quickly and Securely</p>
                    
                    <!-- Search Bar -->
                    <div class="relative max-w-md">
                        <input 
                            type="text" 
                            id="searchInput"
                            placeholder="Search medications, health products..." 
                            class="search-input w-full px-4 py-3 pl-10 rounded-lg text-gray-900 focus:outline-none"
                            aria-label="Search medications"
                        >
                        <span class="absolute left-3 top-3 text-gray-400">
                            <i class="fas fa-search"></i>
                        </span>
                        <button 
                            id="searchButton"
                            class="absolute right-0 top-0 h-full bg-green-500 text-white px-6 rounded-r-lg hover:bg-green-600 transition flex items-center">
                            Search
                        </button>
                        <div id="searchResults" class="absolute w-full bg-white mt-1 rounded-lg shadow-lg z-50 hidden"></div>
                    </div>

                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const searchInput = document.getElementById('searchInput');
                        const searchButton = document.getElementById('searchButton');
                        const searchResults = document.getElementById('searchResults');

                        function performSearch() {
                            const searchTerm = searchInput.value.trim();
                            if (searchTerm.length < 2) {
                                searchResults.classList.add('hidden');
                                return;
                            }

                            // Perform AJAX request
                            fetch('pharmacy_home.php?search=' + encodeURIComponent(searchTerm))
                                .then(response => response.json())
                                .then(data => {
                                    searchResults.innerHTML = '';
                                    if (data.length > 0) {
                                        data.forEach(drug => {
                                            const div = document.createElement('div');
                                            div.className = 'p-3 hover:bg-gray-100 cursor-pointer flex justify-between items-center';
                                            div.innerHTML = `
                                                <div>
                                                    <div class="font-medium text-gray-800">${drug.name}</div>
                                                    <div class="text-sm text-gray-600">$${drug.unit_price}</div>
                                                </div>
                                                <div class="text-sm text-gray-500">Stock: ${drug.stock}</div>
                                            `;
                                            div.onclick = () => window.location.href = `drug_details.php?id=${drug.drug_id}`;
                                            searchResults.appendChild(div);
                                        });
                                        searchResults.classList.remove('hidden');
                                    } else {
                                        searchResults.innerHTML = '<div class="p-3 text-gray-500">No results found</div>';
                                        searchResults.classList.remove('hidden');
                                    }
                                })
                                .catch(error => {
                                    console.error('Search error:', error);
                                    searchResults.innerHTML = '<div class="p-3 text-red-500">Error performing search</div>';
                                    searchResults.classList.remove('hidden');
                                });
                        }

                        // Search on button click
                        searchButton.addEventListener('click', performSearch);

                        // Search on enter key
                        searchInput.addEventListener('keypress', function(e) {
                            if (e.key === 'Enter') {
                                performSearch();
                            }
                        });

                        // Live search as user types (with debounce)
                        let debounceTimer;
                        searchInput.addEventListener('input', function() {
                            clearTimeout(debounceTimer);
                            debounceTimer = setTimeout(performSearch, 300);
                        });

                        // Close search results when clicking outside
                        document.addEventListener('click', function(e) {
                            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                                searchResults.classList.add('hidden');
                            }
                        });
                    });
                    </script>
                </div>
                <div class="md:w-1/2 md:pl-10">
                    <img 
                        src="images/high-angle-pill-foils-plastic-containers.png" 
                        alt="Healthcare Professional" 
                        class="w-full object-cover" 
                        style="max-height: 350px"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Medicines Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12" id="featured-medicines">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl font-bold text-gray-800">Featured Medications</h2>
            <a href="#" class="text-blue-600 hover:text-blue-800 flex items-center">
                View all <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="medicine-card bg-white overflow-hidden">
                        <div class="h-40 bg-blue-50 flex items-center justify-center">
                            <i class="fas fa-pills text-5xl text-blue-400"></i>
                        </div>
                        <div class="p-5">
                            <h3 class="font-semibold text-lg text-gray-800"><?= htmlspecialchars($row['name']) ?></h3>
                            <p class="text-gray-600 text-sm mt-2 h-12 overflow-hidden"><?= htmlspecialchars($row['description']) ?></p>
                            <div class="flex justify-between items-center mt-4">
                                <span class="text-green-600 font-bold text-lg">$<?= number_format($row['unit_price'], 2) ?></span>
                                <span class="text-sm text-gray-500">Stock: <?= $row['stock'] ?></span>
                            </div>
                            
                            <form method="POST" action="add_to_cart.php" class="mt-4 flex items-center justify-between">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <div class="flex items-center">
                                    <label for="quantity-<?= $row['id'] ?>" class="sr-only">Quantity</label>
                                    <input 
                                        id="quantity-<?= $row['id'] ?>"
                                        type="number" 
                                        name="quantity" 
                                        min="1" 
                                        max="<?= $row['stock'] ?>" 
                                        value="1"
                                        required 
                                        class="quantity-input px-2 py-1 border rounded"
                                    >
                                </div>
                                <button type="submit" class="add-to-cart-btn bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 flex items-center">
                                    <i class="fas fa-cart-plus mr-2"></i> Add
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-4 py-8 text-center text-gray-500">
                    <i class="fas fa-pills text-5xl mb-4"></i>
                    <h3 class="text-xl font-medium">No medications available at the moment</h3>
                    <p class="mt-2">Please check back later or contact support.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Services Section -->
    <div class="bg-gray-50 py-12" id="services">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-8 text-center">Our Services</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-6 rounded-lg shadow-sm card-hover text-center">
                    <div class="w-16 h-16 mx-auto bg-blue-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-truck text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Fast Delivery</h3>
                    <p class="text-gray-600">Get your medications delivered to your doorstep within 24 hours.</p>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm card-hover text-center">
                    <div class="w-16 h-16 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-shield-alt text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Genuine Medicines</h3>
                    <p class="text-gray-600">All our products are sourced directly from authorized manufacturers.</p>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm card-hover text-center">
                    <div class="w-16 h-16 mx-auto bg-purple-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-headset text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">24/7 Support</h3>
                    <p class="text-gray-600">Our pharmacists are available round the clock to address your concerns.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-blue-50 rounded-lg p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Quick Resources</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <a href="#" class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md transition flex items-center">
                    <i class="fas fa-file-prescription text-blue-500 text-xl mr-3"></i>
                    <span class="text-gray-800 font-medium">Upload Prescription</span>
                </a>
                
                <a href="#" class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md transition flex items-center">
                    <i class="fas fa-stethoscope text-blue-500 text-xl mr-3"></i>
                    <span class="text-gray-800 font-medium">Healthcare Articles</span>
                </a>
                
                <a href="#" class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md transition flex items-center">
                    <i class="fas fa-question-circle text-blue-500 text-xl mr-3"></i>
                    <span class="text-gray-800 font-medium">FAQs</span>
                </a>
                
                <a href="#" class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md transition flex items-center">
                    <i class="fas fa-phone-alt text-blue-500 text-xl mr-3"></i>
                    <span class="text-gray-800 font-medium">Contact Support</span>
                </a>
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
                        <li><a href="pharmacy_home.php#services" class="text-gray-400 hover:text-white footer-link">Our Services</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white footer-link">Privacy Policy</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white footer-link">Terms & Conditions</a></li>
                        <li><a href="tracking_order.php" class="text-gray-400 hover:text-white footer-link">Tracking Order</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact Us</h3>
                    <ul class="space-y-3 text-gray-400">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-3 text-gray-500"></i>
                            <span>MHCS Building, 75, Sir Baron Jayathilake Mawatha, Colombo 01</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone-alt mr-3 text-gray-500"></i>
                            <span>+94 (11) 2320452</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-3 text-gray-500"></i>
                            <span>logistics@spc.lk</span>
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

    <!-- Add this HTML code before the closing </body> tag -->

<!-- Profile Overlay -->
<div id="profileOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg w-full max-w-2xl p-6 mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-gray-800">User Profile</h3>
            <button id="closeProfileBtn" class="text-gray-500 hover:text-gray-800">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div id="profileContent" class="mb-6">
            <div class="flex items-center mb-4">
                <div class="bg-blue-100 rounded-full p-3 mr-4">
                    <i class="fas fa-user-circle text-3xl text-blue-600"></i>
                </div>
                <div>
                    <h4 id="profileName" class="text-lg font-medium"></h4>
                    <p id="profileEmail" class="text-gray-600"></p>
                    <p id="profilePharmacyId" class="text-gray-600"></p>
                    <p id="profileUserType" class="text-gray-600"></p>
                </div>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Phone</p>
                        <p id="profilePhone" class="font-medium"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Address</p>
                        <p id="profileAddress" class="font-medium"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">License Number</p>
                        <p id="profileLicenseNumber" class="font-medium"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Joined</p>
                        <p id="profileJoined" class="font-medium"></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="flex justify-between">
            <button id="editBtn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                <i class="fas fa-edit mr-2"></i> Edit Profile
            </button>
            <button id="closeBtn" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300 transition">
                Close
            </button>
        </div>
    </div>
</div>

    <script>
        // Initialize Font Awesome (equivalent of Feather Icons initialization)
        // No need for feather.replace() as we're using FontAwesome now
        
        // Auto-hide alert after 5 seconds
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.style.display = 'none';
            }
        }, 5000);

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
                    // Redirect to login.php
                    window.location.href = 'logout.php';
                }
            });
        }

        // Profile Overlay functionality

        document.addEventListener('DOMContentLoaded', function() {
        // DOM elements
        const userInfoTrigger = document.getElementById('userInfoTrigger');
        const overlay = document.getElementById('profileOverlay');
        const closeBtn = document.getElementById('closeProfileBtn');
        const closeBtnBottom = document.getElementById('closeBtn');
        const editBtn = document.getElementById('editBtn');
        
        // Current user ID from session
        const currentUserId = <?= $_SESSION['user_id'] ?>;
        
        <?php
        // Fetch user details from the database
        $user_query = "SELECT name, email, mobile, reg_number, address, province, district, user_type, license_number, created_at 
                       FROM users 
                       WHERE id = ?";
        $stmt = $conn->prepare($user_query);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $user_details = $stmt->get_result()->fetch_assoc();
        ?>
        
        // Function to fetch user data
        function fetchUserData(userId) {
            // Set the user details in the profile overlay
            document.getElementById('profileName').textContent = '<?= htmlspecialchars($user_details['name']) ?>';
            document.getElementById('profileEmail').textContent = '<?= htmlspecialchars($user_details['email']) ?>';
            document.getElementById('profilePhone').textContent = '<?= htmlspecialchars($user_details['mobile']) ?>';
            document.getElementById('profileAddress').textContent = '<?= htmlspecialchars($user_details['address']) . ", " . htmlspecialchars($user_details['district']) . ", " . htmlspecialchars($user_details['province']) ?>';
            document.getElementById('profilePharmacyId').textContent = '<?= htmlspecialchars($user_details['reg_number']) ?>';
            document.getElementById('profileLicenseNumber').textContent = '<?= htmlspecialchars($user_details['license_number']) ?>';
            document.getElementById('profileJoined').textContent = '<?= date("F j, Y g:i A", strtotime($user_details['created_at'])) ?>';
        }
        
        // Open profile when user name/avatar is clicked
        userInfoTrigger.addEventListener('click', function() {
            // Load user data and show popup
            fetchUserData(currentUserId);
            overlay.style.display = 'flex';
        });
        
        // Close profile popup using X button
        closeBtn.addEventListener('click', function() {
            overlay.style.display = 'none';
        });
        
        // Close profile popup using Close button
        closeBtnBottom.addEventListener('click', function() {
            overlay.style.display = 'none';
        });
        
        // Edit profile
        editBtn.addEventListener('click', function() {
            // Redirect to edit profile page
            window.location.href = 'edit_pharmacy_userProfile.php';
        });
    });

    </script>
</body>
</html>