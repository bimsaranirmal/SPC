<?php
session_start();
include 'db.php'; // Include database connection

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'pharmacy') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: login.php");
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['mobile'];
    $address = $_POST['address'];
    $license_num = $_POST['license_number'];
    $province = $_POST['province'];
    $district = $_POST['district'];
    
    // Optional: Change password if provided
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Update user information
    $update_stmt = $conn->prepare("UPDATE users SET name=?, email=?, mobile=?, address=?, license_number=?, province=?, district=? WHERE id=?");
    $update_stmt->bind_param("sssssssi", $name, $email, $phone, $address, $license_num, $province, $district, $user_id);
    
    if ($update_stmt->execute()) {
        // Update session data
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        $_SESSION['mobile'] = $phone;
        $_SESSION['address'] = $address;
        $_SESSION['license_number'] = $license_num;
        $_SESSION['province'] = $province;
        $_SESSION['district'] = $district;
        
        $success_message = "Profile updated successfully!";
        
        // If user wants to change password
        if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
            // Verify current password
            if (password_verify($current_password, $user['password'])) {
                if ($new_password === $confirm_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $password_stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
                    $password_stmt->bind_param("si", $hashed_password, $user_id);
                    
                    if ($password_stmt->execute()) {
                        $success_message .= " Password changed successfully.";
                    } else {
                        $error_message = "Failed to update password. Please try again.";
                    }
                } else {
                    $error_message = "New passwords do not match.";
                }
            } else {
                $error_message = "Current password is incorrect.";
            }
        }
    } else {
        $error_message = "Failed to update profile. Please try again.";
    }
    
    // Refresh user data
    $refresh_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $refresh_stmt->bind_param("i", $user_id);
    $refresh_stmt->execute();
    $result = $refresh_stmt->get_result();
    $user = $result->fetch_assoc();
    $refresh_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - SPC Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            color: #333;
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
        .form-input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
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
                        <a href="submit_inquiry.php" class="nav-link px-3 py-2 text-sm font-medium">
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
                        <span class="text-sm"><?= htmlspecialchars($user['name']) ?></span>
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
                <a href="pharmacy_home.php" class="flex flex-col items-center text-gray-600">
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

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="border-b border-gray-200 p-6">
                <h1 class="text-2xl font-bold text-gray-800">Edit Profile</h1>
                <p class="text-gray-600 mt-2">Update your personal information and password</p>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($success_message)): ?>
                <div class="bg-green-100 text-green-800 p-4 mb-6 mx-6 mt-6 rounded-md flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2 text-green-500"></i>
                        <?= $success_message ?>
                    </div>
                    <button onclick="this.parentElement.style.display='none'" class="text-green-500 hover:text-green-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 text-red-800 p-4 mb-6 mx-6 mt-6 rounded-md flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2 text-red-500"></i>
                        <?= $error_message ?>
                    </div>
                    <button onclick="this.parentElement.style.display='none'" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <div class="p-6">
                <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
                    <!-- Personal Information -->
                    <div class="mb-8">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Personal Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input 
                                    type="text" 
                                    id="name" 
                                    name="name" 
                                    value="<?= htmlspecialchars($user['name']) ?>" 
                                    required
                                    class="form-input w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                                    required
                                    class="form-input w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                            </div>
                            <div>
                                <label for="mobile" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input 
                                    type="tel" 
                                    id="phone" 
                                    name="mobile" 
                                    value="<?= htmlspecialchars($user['mobile'] ?? '') ?>" 
                                    class="form-input w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                            </div>
                            <div>
                                <label for="reg_number" class="block text-sm font-medium text-gray-700 mb-1">Registration Number</label>
                                <input 
                                    type="text" 
                                    id="pharmacy_id" 
                                    value="<?= htmlspecialchars($user['reg_number'] ?? $user_id) ?>" 
                                    disabled
                                    class="form-input w-full px-4 py-2 border border-gray-200 bg-gray-50 rounded-md text-gray-500"
                                >
                            </div>

                            <div>
                                <label for="license_number" class="block text-sm font-medium text-gray-700 mb-1">License Number</label>
                                <input 
                                    type="text" 
                                    id="license_num"
                                    name="license_number" 
                                    value="<?= htmlspecialchars($user['license_number'] ?? $user_id) ?>" 
                                    class="form-input w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                            </div>

                            <div>
                               <label for="province" class="block text-sm font-medium text-gray-700 mb-1">Province</label>
                                        <input 
                                            type="text" 
                                            id="province" 
                                            name="province" 
                                            value="<?= htmlspecialchars($user['province'] ?? '') ?>" 
                                            class="form-input w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        >
                            </div>

                            <div>
                            <label for="district" class="block text-sm font-medium text-gray-700 mb-1">District</label>
                                        <input 
                                            type="text" 
                                            id="district" 
                                            name="district" 
                                            value="<?= htmlspecialchars($user['district'] ?? '') ?>" 
                                            class="form-input w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        >
                            </div>
                            
                        
                        <div class="mt-6 col-span-2">
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <textarea 
                                id="address" 
                                name="address" 
                                rows="3" 
                                class="form-input w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            ><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                    
                    <!-- Change Password -->
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Change Password</h2>
                        <p class="text-sm text-gray-600 mb-4">Leave these fields empty if you don't want to change your password</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                <input 
                                    type="password" 
                                    id="current_password" 
                                    name="current_password" 
                                    class="form-input w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                            </div>
                            <div></div>
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                <input 
                                    type="password" 
                                    id="new_password" 
                                    name="new_password" 
                                    class="form-input w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                            </div>
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    class="form-input w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                        <a href="pharmacy_home.php" class="text-gray-600 hover:text-gray-800">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Home
                        </a>
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition flex items-center">
                            <i class="fas fa-save mr-2"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="md:flex md:items-center md:justify-between">
                <div class="flex items-center">
                    <img class="h-8 w-auto" src="images/OIP-removebg-preview.png" alt="SPC Pharmacy Logo">
                    <span class="ml-2 text-lg font-semibold">SPC Pharmacy</span>
                </div>
                <div class="mt-4 md:mt-0">
                    <p class="text-center md:text-right text-sm text-gray-400">
                        &copy; <?= date('Y') ?> SPC Pharmacy. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>

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
                    window.location.href = 'logout.php';
                }
            });
        }

        // Password validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const currentPassword = document.getElementById('current_password').value;
            
            // Only validate if user is trying to change password
            if (newPassword || confirmPassword || currentPassword) {
                if (!currentPassword) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please enter your current password to change to a new password'
                    });
                    return;
                }
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'New passwords do not match'
                    });
                    return;
                }
                
                if (newPassword && newPassword.length < 6) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Password must be at least 6 characters long'
                    });
                    return;
                }
            }
        });
        
        // Auto-hide alert after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.bg-green-100, .bg-red-100');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>