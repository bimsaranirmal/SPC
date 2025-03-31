<?php
session_start();
include 'db.php'; // Include database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Fetch inquiries submitted by the logged-in user
$stmt = $conn->prepare("SELECT * FROM inquiries WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Inquiries - SPC Pharmacy</title>
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
        .table-header {
            background-color: #3b82f6;
            color: white;
        }
        .table-row:hover {
            background-color: #f1f5f9;
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
                        <a href="view_inquiries.php" class="nav-link active px-3 py-2 text-sm font-medium">
                            <i class="fas fa-envelope-open-text mr-1"></i> View Inquiries
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="hidden md:flex items-center px-3 py-1 bg-gray-100 text-gray-700 rounded-full">
                        <i class="fas fa-user-circle mr-2"></i>
                        <span class="text-sm"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    </div>
                    <a href="javascript:void(0);" onclick="confirmLogout()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition flex items-center text-sm font-medium">
    <i class="fas fa-sign-out-alt mr-2"></i> Logout
</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Inquiries Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Your Submitted Inquiries</h2>
        <div class="bg-white p-8 rounded-lg shadow-md">
            <?php if ($result->num_rows > 0): ?>
                <table class="min-w-full border-collapse border border-gray-200">
                    <thead>
                        <tr class="table-header">
                            <th class="px-4 py-2 text-left text-sm font-medium">#</th>
                            <th class="px-4 py-2 text-left text-sm font-medium">Inquiry Type</th>
                            <th class="px-4 py-2 text-left text-sm font-medium">Details</th>
                            <th class="px-4 py-2 text-left text-sm font-medium">Submitted At</th>
                            <th class="px-4 py-2 text-left text-sm font-medium">Status</th> <!-- New column for status -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="table-row border-t border-gray-200">
                                <td class="px-4 py-2 text-sm"><?= $count++ ?></td>
                                <td class="px-4 py-2 text-sm"><?= htmlspecialchars($row['inquiry_type']) ?></td>
                                <td class="px-4 py-2 text-sm"><?= htmlspecialchars($row['details']) ?></td>
                                <td class="px-4 py-2 text-sm"><?= htmlspecialchars($row['created_at']) ?></td>
                                <td class="px-4 py-2 text-sm">
                                    <span class="badge bg-<?= $row['status'] === 'Replied' ? 'green-500' : 'yellow-500' ?> text-white px-2 py-1 rounded">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-gray-600">You have not submitted any inquiries yet.</p>
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