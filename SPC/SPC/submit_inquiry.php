<?php
session_start();
include 'db.php'; // Include database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT name, email FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_name = $user['name'];
$user_email = $user['email'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $inquiry_type = $_POST['inquiry_type'];
    $details = $_POST['details'];

    // Validate form data
    if (empty($inquiry_type) || empty($details)) {
        $_SESSION['alert'] = "All fields are required.";
    } else {
        // Insert inquiry into the database
        $stmt = $conn->prepare("INSERT INTO inquiries (user_id, name, email, inquiry_type, details) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $user_name, $user_email, $inquiry_type, $details);

        if ($stmt->execute()) {
            $_SESSION['alert'] = "Your inquiry has been submitted successfully!";
        } else {
            $_SESSION['alert'] = "Failed to submit your inquiry. Please try again.";
        }

        $stmt->close();
    }

    header("Location: submit_inquiry.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Inquiry - SPC Pharmacy</title>
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
        .alert {
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            animation: fadeIn 0.5s ease-out;
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
                        <a href="pharmacy_home.php" class="nav-link px-3 py-2 text-sm font-medium">
                            <i class="fas fa-home mr-1"></i> Home
                        </a>
                        <a href="view_cart.php" class="nav-link px-3 py-2 text-sm font-medium">
                            <i class="fas fa-shopping-cart mr-1"></i> Cart
                        </a>
                        <a href="view_pharmacy_orders.php" class="nav-link px-3 py-2 text-sm font-medium">
                            <i class="fas fa-clipboard-list mr-1"></i> Orders
                        </a>
                        <a href="submit_inquiry.php" class="nav-link active px-3 py-2 text-sm font-medium">
                            <i class="fas fa-question-circle mr-1"></i> Submit Inquiry
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
    </nav>

    <!-- Alert Message -->
    <?php if (isset($_SESSION['alert'])): ?>
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

    <!-- Inquiry Form Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Submit an Inquiry</h2>
        <div class="bg-white p-8 rounded-lg shadow-md">
            <form method="POST" action="submit_inquiry.php">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            value="<?= htmlspecialchars($user_name) ?>" 
                            readonly 
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100"
                        >
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?= htmlspecialchars($user_email) ?>" 
                            readonly 
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100"
                        >
                    </div>
                </div>
                <div class="mt-6">
                    <label for="inquiry_type" class="block text-sm font-medium text-gray-700">Inquiry Type</label>
                    <select 
                        id="inquiry_type" 
                        name="inquiry_type" 
                        required 
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm"
                    >
                        <option value="Order">Order</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="mt-6">
                    <label for="details" class="block text-sm font-medium text-gray-700">Inquiry Details</label>
                    <textarea 
                        id="details" 
                        name="details" 
                        rows="4" 
                        required 
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm"
                        placeholder="Provide details about your inquiry..."
                    ></textarea>
                </div>
                <div class="mt-6">
                    <button 
                        type="submit" 
                        class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition"
                    >
                        Submit Inquiry
                    </button>
                </div>
            </form>
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