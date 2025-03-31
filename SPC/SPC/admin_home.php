<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require 'db.php';

// Fetch distinct user types and statuses for dropdown filters
$user_types_result = $conn->query("SELECT DISTINCT user_type FROM users");
$statuses_result = $conn->query("SELECT DISTINCT status FROM users");

$sql = "SELECT id, name, email, mobile, reg_number, address, province, district, user_type, status FROM users";
$result = $conn->query($sql);

function sendStatusEmail($email, $name, $status) {
    $subject = "Account Status Update";
    $message = "Dear $name,\n\nYour account status has been updated to: $status.\n\nThank you.";
    $headers = "From: your_email@yourdomain.com\r\nReply-To: your_email@yourdomain.com\r\nX-Mailer: PHP/" . phpversion();
    mail($email, $subject, $message, $headers);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $user_id = $_POST['user_id'];
    $new_status = $_POST['new_status'];

    // Fetch user's email and name
    $user_sql = "SELECT email, name FROM users WHERE id='$user_id'";
    $user_result = $conn->query($user_sql);
    $user = $user_result->fetch_assoc();

    // Update status in database
    $update_sql = "UPDATE users SET status='$new_status' WHERE id='$user_id'";
    if ($conn->query($update_sql) === TRUE) {
        sendStatusEmail($user['email'], $user['name'], ucfirst($new_status));
    }

    header("Location: admin_home.php");
    exit();
}

// Fetch existing drugs from the database
$drugs_sql = "SELECT * FROM drugs";
$drugs_result = $conn->query($drugs_sql);

// Handle drug submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_drug'])) {
    $drug_id = $_POST['drug_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $supplier_type = $_POST['supplier_type'];
    $reg_number = $_POST['reg_number'];
    $manufacture_date = $_POST['manufacture_date'];
    $expire_date = $_POST['expire_date'];
    $capacity = $_POST['capacity'];
    $stock = $_POST['stock'];
    $unit_price = $_POST['unit_price'];

    $insert_sql = "INSERT INTO drugs (drug_id, name, description, supplier_type, reg_number, manufacture_date, expire_date, capacity, stock, unit_price) 
                   VALUES ('$drug_id', '$name', '$description', '$supplier_type', '$reg_number', '$manufacture_date', '$expire_date', '$capacity', '$stock', '$unit_price')";
    if ($conn->query($insert_sql) === TRUE) {
        $_SESSION['success_message'] = "Drug added successfully!";
        $_SESSION['target_section'] = "drugDetails";
        header("Location: admin_home.php");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_drug'])) {
    $id = $_POST['id'];
    $drug_id = $_POST['drug_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $supplier_type = $_POST['supplier_type'];
    $reg_number = $_POST['reg_number'];
    $manufacture_date = $_POST['manufacture_date'];
    $expire_date = $_POST['expire_date'];
    $capacity = $_POST['capacity'];
    $stock = $_POST['stock'];
    $unit_price = $_POST['unit_price'];

    $update_sql = "UPDATE drugs SET 
                   drug_id='$drug_id',
                   name='$name', 
                   description='$description', 
                   supplier_type='$supplier_type', 
                   reg_number='$reg_number', 
                   manufacture_date='$manufacture_date', 
                   expire_date='$expire_date', 
                   capacity='$capacity', 
                   stock='$stock', 
                   unit_price='$unit_price' 
                   WHERE id='$id'";
    if ($conn->query($update_sql) === TRUE) {
        $_SESSION['success_message'] = "Drug updated successfully!";
        $_SESSION['target_section'] = "drugDetails";
        header("Location: admin_home.php");
        exit();
    } else {
        echo "<script>alert('Error updating drug: " . $conn->error . "');</script>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_drug'])) {
    $drug_id = $_POST['delete_drug_id'];

    $delete_sql = "DELETE FROM drugs WHERE id='$drug_id'";
    if ($conn->query($delete_sql) === TRUE) {
        $_SESSION['success_message'] = "Drug deleted successfully!";
        $_SESSION['target_section'] = "drugDetails";
        header("Location: admin_home.php");
        exit();
    } else {
        echo "<script>alert('Error deleting drug: " . $conn->error . "');</script>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    $ordering_person_name = $_POST['ordering_person_name'];
    $company_email = $_POST['company_email'];
    $ordering_source = $_POST['ordering_source'];
    $order_date = date("Y-m-d H:i:s"); // Set the current date and time
    $drug_ids = $_POST['drug_id'];
    $quantities = $_POST['quantity'];

    // Insert the order into the orders table
    $insert_order_sql = "INSERT INTO orders (order_date, ordering_person_name, company_email, ordering_source) 
                         VALUES ('$order_date', '$ordering_person_name', '$company_email', '$ordering_source')";
    if ($conn->query($insert_order_sql) === TRUE) {
        $order_id = $conn->insert_id; // Get the ID of the newly created order

        // Insert each drug item into the order_items table
        for ($i = 0; $i < count($drug_ids); $i++) {
            $drug_id = $drug_ids[$i];
            $quantity = $quantities[$i];

            // Insert the order item
            $insert_item_sql = "INSERT INTO order_items (order_id, drug_id, quantity) 
                                VALUES ('$order_id', '$drug_id', '$quantity')";
            $conn->query($insert_item_sql);
        }

        $_SESSION['success_message1'] = "Order placed successfully!";
        $_SESSION['target_section'] = "drugOrders";
        header("Location: admin_home.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error placing order: " . $conn->error;
        $_SESSION['target_section'] = "drugOrders";
        header("Location: admin_home.php");
        exit();
    }
}

if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);

    $items_sql = "SELECT oi.quantity, d.drug_id, d.name, d.description, d.capacity 
                  FROM order_items oi 
                  JOIN drugs d ON oi.drug_id = d.id 
                  WHERE oi.order_id = $order_id";
    $items_result = $conn->query($items_sql);

    $items = [];
    while ($item = $items_result->fetch_assoc()) {
        $items[] = $item;
    }

    echo json_encode($items);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_order'])) {
    $order_id = intval($_POST['cancel_order_id']);

    // Update the order status to 'Canceled'
    $cancel_order_sql = "UPDATE orders SET status = 'Canceled' WHERE id = $order_id";
    if ($conn->query($cancel_order_sql) === TRUE) {
        $_SESSION['success_message2'] = "Order canceled successfully!";
    } else {
        $_SESSION['error_message2'] = "Error canceling order: " . $conn->error;
    }
    $_SESSION['target_section'] = "placedOrders";
    header("Location: admin_home.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['approve_tender'])) {
        $tender_id = intval($_POST['tender_id']);
        $order_id = intval($_POST['order_id']);

        // Fetch supplier email and tender details
        $tender_sql = "SELECT supplier_email, tender_amount FROM tenders WHERE id = $tender_id";
        $tender_result = $conn->query($tender_sql);
        $tender = $tender_result->fetch_assoc();
        $supplier_email = $tender['supplier_email'];
        $tender_amount = $tender['tender_amount'];

        // Approve the tender
        $approve_tender_sql = "UPDATE tenders SET status = 'Approved' WHERE id = $tender_id";
        $close_order_sql = "UPDATE orders SET status = 'Closed' WHERE id = $order_id";

        if ($conn->query($approve_tender_sql) === TRUE && $conn->query($close_order_sql) === TRUE) {
            // Send email notification
            $subject = "Tender Approved";
            $message = "Dear Supplier,\n\nYour tender with ID $tender_id for Order ID $order_id has been approved.\n\nTender Amount: $tender_amount\n\nThank you.";
            $headers = "From: admin@yourdomain.com\r\nReply-To: admin@yourdomain.com\r\nX-Mailer: PHP/" . phpversion();

            mail($supplier_email, $subject, $message, $headers);

            $_SESSION['success_message4'] = "Tender approved, order status updated to Closed, and email sent to the supplier!";
        } else {
            $_SESSION['error_message4'] = "Error approving tender: " . $conn->error;
        }
        
        $_SESSION['target_section'] = "tenders";
        header("Location: admin_home.php");
        exit();
    }

    if (isset($_POST['reject_tender'])) {
        $tender_id = intval($_POST['tender_id']);

        // Fetch supplier email
        $tender_sql = "SELECT supplier_email FROM tenders WHERE id = $tender_id";
        $tender_result = $conn->query($tender_sql);
        $tender = $tender_result->fetch_assoc();
        $supplier_email = $tender['supplier_email'];

        // Reject the tender
        $reject_tender_sql = "UPDATE tenders SET status = 'Rejected' WHERE id = $tender_id";

        if ($conn->query($reject_tender_sql) === TRUE) {
            // Send email notification
            $subject = "Tender Rejected";
            $message = "Dear Supplier,\n\nYour tender with ID $tender_id has been rejected.\n\nThank you.";
            $headers = "From: admin@yourdomain.com\r\nReply-To: admin@yourdomain.com\r\nX-Mailer: PHP/" . phpversion();

            mail($supplier_email, $subject, $message, $headers);

            $_SESSION['success_message4'] = "Tender rejected and email sent to the supplier!";
        } else {
            $_SESSION['error_message4'] = "Error rejecting tender: " . $conn->error;
        }
        
        $_SESSION['target_section'] = "tenders";
        header("Location: admin_home.php");
        exit();
    }
}

// Fetch pharmacy orders with tracking numbers
$pharmacy_orders_sql = "
    SELECT 
        o.id AS order_id, 
        u.email AS user_email, 
        d.name AS drug_name, 
        o.quantity, 
        o.total_price, 
        o.order_date, 
        o.status AS order_status, 
        t.tracking_id, 
        t.status AS tracking_status, 
        t.updated_at 
    FROM pharmacy_orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN drugs d ON o.drug_id = d.id
    LEFT JOIN order_tracking t ON o.id = t.order_id
    ORDER BY o.order_date DESC";
$pharmacy_orders_result = $conn->query($pharmacy_orders_sql);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['tracking_status'])) {
        $order_id = intval($_POST['order_id']);
        $tracking_status = $_POST['tracking_status'];

        $update_tracking_sql = "UPDATE order_tracking SET status = '$tracking_status' WHERE order_id = $order_id";

        if ($conn->query($update_tracking_sql) === TRUE) {
            $_SESSION['success_message37'] = "Tracking status updated successfully!";
        } else {
            $_SESSION['error_message37'] = "Error updating tracking status: " . $conn->error;
        }
        $_SESSION['target_section'] = "pharmacyOrders";
        header("Location: admin_home.php");
        exit();
    }

    if (isset($_POST['complete_order'])) {
        $order_id = intval($_POST['order_id']);
        $update_order_sql = "UPDATE pharmacy_orders SET status = 'Completed' WHERE id = $order_id";
        $update_tracking_sql = "UPDATE order_tracking SET status = 'Delivered' WHERE order_id = $order_id";

        if ($conn->query($update_order_sql) === TRUE && $conn->query($update_tracking_sql) === TRUE) {
            $_SESSION['success_message37'] = "Order marked as completed successfully, and tracking status updated!";
        } else {
            $_SESSION['error_message37'] = "Error completing order: " . $conn->error;
        }
        $_SESSION['target_section'] = "pharmacyOrders";
        header("Location: admin_home.php");
        exit();
    }

    if (isset($_POST['cancel_order1'])) {
        $order_id = intval($_POST['order_id']);
        $update_order_sql = "UPDATE pharmacy_orders SET status = 'Cancelled' WHERE id = $order_id";
        $update_tracking_sql = "UPDATE order_tracking SET status = 'Pending' WHERE order_id = $order_id";

        if ($conn->query($update_order_sql) === TRUE && $conn->query($update_tracking_sql) === TRUE) {
            $_SESSION['success_message37'] = "Order canceled successfully, and tracking status updated!";
        } else {
            $_SESSION['error_message37'] = "Error canceling order: " . $conn->error;
        }
        $_SESSION['target_section'] = "pharmacyOrders";
        header("Location: admin_home.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            display: flex;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .sidebar {
            width: 250px;
            background-color: #343a40;
            color: white;
            padding: 20px;
            position: fixed;
            height: 100%;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px 15px;
            border-radius: 5px;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white !important;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
            width: 100%;
        }
        .container {
            margin-top: 30px;
        }
        .status-badge {
            font-size: 14px;
            padding: 5px 10px;
            border-radius: 12px;
        }
        .status-pending {
            background-color: #ffc107;
            color: black;
        }
        .status-approved {
            background-color: #28a745;
            color: white;
        }
        .status-rejected {
            background-color: #dc3545;
            color: white;
        }
        .table thead th {
            position: sticky; 
            top: 0; 
            background-color: #007bff;
            color: white; 
            z-index: 1;
            border-bottom: 2px solid #dee2e6; 
        }
        .table th {
            background-color: #007bff;
            color: white;
        }
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 30px;
        }
        .table-wrapper {
            max-height: 400px; /* Set the maximum height for the table */
            overflow-y: auto; /* Enable vertical scrolling */
            border: 1px solid #dee2e6; /* Optional: Add a border around the table */
        }
        .low-stock {
            background-color:rgb(231, 91, 86) !important; /* Red for low stock */
            color: white !important;
        }

        .medium-stock {
            background-color: yellow !important; /* Yellow for medium stock */
            color: black !important;
        }

        .high-stock {
            background-color: green !important; /* Green for high stock */
            color: white !important;
        }

    </style>
</head>
<body>
    
    <!-- Sidebar -->
    <div class="sidebar">
        <h4 class="text-center"><i class="bi bi-person-circle"></i> Admin Panel</h4>
        <hr>
        <a href="javascript:void(0);" onclick="showSection('userDetails')"><i class="bi bi-people-fill"></i> User Details</a>
        <a href="javascript:void(0);" onclick="showSection('drugDetails')"><i class="bi bi-capsule-pill"></i> Drug Details</a>
        <a href="javascript:void(0);" onclick="showSection('drugOrders')"><i class="bi bi-cart"></i> Place Drug Orders</a>
        <a href="javascript:void(0);" onclick="showSection('placedOrders')"><i class="bi bi-list-check"></i> Placed Orders</a>
        <a href="javascript:void(0);" onclick="showSection('tenders')"><i class="bi bi-file-earmark-text"></i> Tenders</a>
        <a href="javascript:void(0);" onclick="showSection('pharmacyOrders')"><i class="bi bi-truck"></i> Pharmacy Orders</a>
        <a href="javascript:void(0);" onclick="showSection('inquiries')"><i class="bi bi-envelope"></i> Inquiries</a>
        <a href="javascript:void(0);" class="text-danger" onclick="confirmLogout();">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>

    <div class="content">

        <!-- Header Bar -->
        <nav class="navbar navbar-dark bg-primary">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h1">
                    
                    <i class="bi bi-building"></i> State Pharmaceutical Cooperation (SPC)
                </span>
            </div>
        </nav>
    
        <!-- User Details -->
        <div id="userDetails" class="section">
            <h3 class="mt-4">User Details</h3>
            <?php
                // Initialize counters for user statuses
                $approved_count = 0;
                $rejected_count = 0;
                $pending_count = 0;

                // Loop through the users to calculate counts
                $result->data_seek(0); 
                while ($row = $result->fetch_assoc()) {
                    if ($row['status'] === 'approved') {
                        $approved_count++;
                    } elseif ($row['status'] === 'rejected') {
                        $rejected_count++;
                    } elseif ($row['status'] === 'pending') {
                        $pending_count++;
                    }
                }

                $result->data_seek(0);
            ?>
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-success">
                        <div class="card-body">
                            <h5 class="card-title text-success">
                                <i class="bi bi-check-circle-fill"></i> Approved Users
                            </h5>
                            <p class="card-text">
                                Total number of approved users.
                            </p>
                            <h3 class="text-success">
                                <strong id="approvedCount"><?php echo $approved_count; ?></strong>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-danger">
                        <div class="card-body">
                            <h5 class="card-title text-danger">
                                <i class="bi bi-x-circle-fill"></i> Rejected Users
                            </h5>
                            <p class="card-text">
                                Total number of rejected users.
                            </p>
                            <h3 class="text-danger">
                                <strong id="rejectedCount"><?php echo $rejected_count; ?></strong>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-warning">
                        <div class="card-body">
                            <h5 class="card-title text-warning">
                                <i class="bi bi-hourglass-split"></i> Pending Users
                            </h5>
                            <p class="card-text">
                                Total number of pending users.
                            </p>
                            <h3 class="text-warning">
                                <strong id="pendingCount"><?php echo $pending_count; ?></strong>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters: Search by Email, Filter by User Type, Filter by Status -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search by email...">
                </div>
                <div class="col-md-4">
                    <select id="userTypeFilter" class="form-select">
                        <option value="">Filter by User Type</option>
                        <?php while ($user_type = $user_types_result->fetch_assoc()) { ?>
                            <option value="<?php echo $user_type['user_type']; ?>"><?php echo ucfirst(str_replace("_", " ", $user_type['user_type'])); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <select id="statusFilter" class="form-select">
                        <option value="">Filter by Status</option>
                        <?php while ($status = $statuses_result->fetch_assoc()) { ?>
                            <option value="<?php echo $status['status']; ?>"><?php echo ucfirst($status['status']); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
                    
            <!-- User Table with Scrollbar -->
            <div class="table-wrapper">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name <i class="bi bi-person"></i></th>
                            <th>Email <i class="bi bi-envelope"></i></th>
                            <th>Mobile <i class="bi bi-phone"></i></th>
                            <th>Reg No. <i class="bi bi-card-list"></i></th>
                            <th>Address <i class="bi bi-geo-alt"></i></th>
                            <th>Province <i class="bi bi-map"></i></th>
                            <th>District <i class="bi bi-building"></i></th>
                            <th>User Type <i class="bi bi-person-badge"></i></th>
                            <th>Status <i class="bi bi-info-circle"></i></th>
                            <th>Action <i class="bi bi-gear"></i></th>
                        </tr>
                    </thead>
                    <tbody id="userTable">
                        <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $row["id"]; ?></td>
                            <td><?php echo $row["name"]; ?></td>
                            <td><?php echo $row["email"]; ?></td>
                            <td><?php echo $row["mobile"]; ?></td>
                            <td><?php echo $row["reg_number"]; ?></td>
                            <td><?php echo $row["address"]; ?></td>
                            <td><?php echo $row["province"]; ?></td>
                            <td><?php echo $row["district"]; ?></td>
                            <td data-raw-type="<?php echo $row["user_type"]; ?>"><?php echo ucfirst(str_replace("_", " ", $row["user_type"])); ?></td>
                            <td><span class="status-badge <?php echo "status-" . $row["status"]; ?>"><?php echo ucfirst($row["status"]); ?></span></td>
                            <td>
                                <form method="POST" class="d-flex">
                                    <input type="hidden" name="user_id" value="<?php echo $row["id"]; ?>">
                                    <select name="new_status" class="form-select form-select-sm me-2">
                                        <option value="approved" <?php if ($row["status"] == "approved") echo "selected"; ?>>Approved</option>
                                        <option value="rejected" <?php if ($row["status"] == "rejected") echo "selected"; ?>>Rejected</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary"><i class="bi bi-check-circle"></i> Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
            
        <?php
            // Initialize counters
            $low_stock_count = 0;    // Stock 0-10
            $medium_stock_count = 0; // Stock 10-100
            $high_stock_count = 0;   // Stock > 100

            while ($drug = $drugs_result->fetch_assoc()) { 
                $stock = $drug["stock"];

                // Categorize stock levels
                if ($stock <= 10) {
                    $stock_class = 'low-stock';
                    $low_stock_count++;
                } elseif ($stock <= 100) {
                    $stock_class = 'medium-stock';
                    $medium_stock_count++;
                } else {
                    $stock_class = 'high-stock';
                    $high_stock_count++;
                }
            }
        ?>
        
        <!-- Drug Details -->
        <div id="drugDetails" class="section" style="display: none;">
            <div class="container mt-4">
                <h3 class="mt-4">Drug Details</h3>
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card border-danger">
                                <div class="card-body">
                                    <h5 class="card-title text-danger">
                                        <i class="bi bi-exclamation-triangle-fill"></i> Low Stock
                                    </h5>
                                    <p class="card-text">
                                        Drugs with stock levels between <strong>0-10</strong>.
                                    </p>
                                    <h3 class="text-danger">
                                        <strong id="lowStockCount"><?php echo $low_stock_count; ?></strong>
                                    </h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-warning">
                                <div class="card-body">
                                    <h5 class="card-title text-warning">
                                        <i class="bi bi-exclamation-circle-fill"></i> Medium Stock
                                    </h5>
                                    <p class="card-text">
                                        Drugs with stock levels between <strong>10-100</strong>.
                                    </p>
                                    <h3 class="text-warning">
                                        <strong id="mediumStockCount"><?php echo $medium_stock_count; ?></strong>
                                    </h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-success">
                                <div class="card-body">
                                    <h5 class="card-title text-success">
                                        <i class="bi bi-check-circle-fill"></i> High Stock
                                    </h5>
                                    <p class="card-text">
                                        Drugs with stock levels above <strong>100</strong>.
                                    </p>
                                    <h3 class="text-success">
                                        <strong id="highStockCount"><?php echo $high_stock_count; ?></strong>
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addDrugModal">Add New Drug</button>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <input type="text" id="searchBar" class="form-control" placeholder="Search by email...">
                        </div>
                        <div class="col-md-4">
                            <select id="supplierTypeFilter" class="form-select">
                                <option value="">Filter by Supplier Type</option>
                                <option value="SPC">SPC</option>
                                <option value="Supplier">Supplier</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select id="stockFilter" class="form-select">
                                <option value="">Filter by Stock</option>
                                <option value="0-10">0-10</option>
                                <option value="10-100">10-100</option>
                                <option value="100-500">100-500</option>
                                <option value="500+">500+</option>
                            </select>
                        </div>
                    </div>

                    
                
                    <!-- Scrollable Drug Table -->
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6;">
                        <table class="table table-bordered" id="drugTable"> <!-- Add id="drugTable" -->
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Drug ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Supplier Type</th>
                                <th>Registration Number</th>
                                <th>Manufacture Date</th>
                                <th>Expire Date</th>
                                <th>Capacity</th>
                                <th>Stock</th>
                                <th>Unit Price</th> <!-- Add Unit Price Column -->
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                                <?php
                                $drugs_result->data_seek(0); // Reset pointer to fetch results again
                                while ($drug = $drugs_result->fetch_assoc()) {
                                    $stock = $drug["stock"];
                                    if ($stock <= 10) {
                                        $stock_class = 'low-stock';
                                    } elseif ($stock <= 100) {
                                        $stock_class = 'medium-stock';
                                    } else {
                                        $stock_class = 'high-stock';
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $drug["id"]; ?></td>
                                    <td><?php echo $drug["drug_id"]; ?></td>
                                    <td><?php echo $drug["name"]; ?></td>
                                    <td><?php echo $drug["description"]; ?></td>
                                    <td><?php echo $drug["supplier_type"]; ?></td>
                                    <td><?php echo $drug["reg_number"]; ?></td>
                                    <td><?php echo $drug["manufacture_date"]; ?></td>
                                    <td><?php echo $drug["expire_date"]; ?></td>
                                    <td><?php echo $drug["capacity"]; ?></td>
                                    <td class="<?php echo $stock_class; ?>"><?php echo $drug["stock"]; ?></td>
                                    <td><?php echo number_format($drug["unit_price"], 2); ?></td> <!-- Display Unit Price -->
                                    <td>
                                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#updateDrugModal" 
                                            data-id="<?php echo $drug['id']; ?>" 
                                            data-drug_id="<?php echo $drug['drug_id']; ?>"
                                            data-name="<?php echo $drug['name']; ?>"
                                            data-description="<?php echo $drug['description']; ?>"
                                            data-supplier_type="<?php echo $drug['supplier_type']; ?>"
                                            data-reg_number="<?php echo $drug['reg_number']; ?>"
                                            data-manufacture_date="<?php echo $drug['manufacture_date']; ?>"
                                            data-expire_date="<?php echo $drug['expire_date']; ?>"
                                            data-capacity="<?php echo $drug['capacity']; ?>"
                                            data-stock="<?php echo $drug['stock']; ?>"
                                            data-unit_price="<?php echo $drug['unit_price']; ?>">Update</button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirmDelete();">
                                            <input type="hidden" name="delete_drug_id" value="<?php echo $drug['id']; ?>">
                                            <button type="submit" name="delete_drug" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <script>
                        function confirmDelete() {
                            return confirm("Are you sure you want to delete this drug?");
                        }
                    </script>

                    <!-- Add Drug Modal -->
                    <div class="modal fade" id="addDrugModal" tabindex="-1" aria-labelledby="addDrugModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Add New Drug</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    
                                </div>
                                
                                <div class="modal-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">Drug ID</label>
                                            <input type="text" class="form-control" name="drug_id" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Name</label>
                                            <input type="text" class="form-control" name="name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" name="description" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Supplier Type</label>
                                            <select class="form-select" name="supplier_type" required>
                                                <option value="SCP">SPC</option>
                                                <option value="Supplier">Supplier</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Registration Number</label>
                                            <input type="text" class="form-control" name="reg_number" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Manufacture Date</label>
                                            <input type="date" class="form-control" name="manufacture_date" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Expire Date</label>
                                            <input type="date" class="form-control" name="expire_date" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Capacity</label>
                                            <input type="text" class="form-control" name="capacity" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Stock</label>
                                            <input type="number" class="form-control" name="stock" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Unit Price</label>
                                            <input type="number" step="0.01" class="form-control" name="unit_price" required>
                                        </div>
                                        <button type="submit" name="add_drug" class="btn btn-success">Add Drug</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Update Drug Modal -->
                    <div class="modal fade" id="updateDrugModal" tabindex="-1" aria-labelledby="updateDrugModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="updateDrugModalLabel">Update Drug</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST">
                                        <input type="hidden" name="id" id="updateDrugId">
                                        <div class="mb-3">
                                            <label class="form-label">Drug ID</label>
                                            <input type="text" class="form-control" name="drug_id" id="updateDrugIdInput" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Name</label>
                                            <input type="text" class="form-control" name="name" id="updateName" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" name="description" id="updateDescription" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Supplier Type</label>
                                            <select class="form-select" name="supplier_type" id="updateSupplierType" required>
                                                <option value="SCP">SPC</option>
                                                <option value="Supplier">Supplier</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Registration Number</label>
                                            <input type="text" class="form-control" name="reg_number" id="updateRegNumber" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Manufacture Date</label>
                                            <input type="date" class="form-control" name="manufacture_date" id="updateManufactureDate" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Expire Date</label>
                                            <input type="date" class="form-control" name="expire_date" id="updateExpireDate" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Capacity</label>
                                            <input type="text" class="form-control" name="capacity" id="updateCapacity" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Stock</label>
                                            <input type="number" class="form-control" name="stock" id="updateStock" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Unit Price</label>
                                            <input type="number" step="0.01" class="form-control" name="unit_price" id="updateUnitPrice" required>
                                        </div>
                                        <button type="submit" name="update_drug" class="btn btn-success">Update Drug</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <div>
            </div>
        </div>
        </div>

        <!-- Placed Orders -->
        <div id="drugOrders" class="section" style="display: none;">
            <div class="container mt-4">
                <h3 class="mt-4">Place Drug Orders</h3>

                <?php if (isset($_SESSION['success_message1'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message1']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_message1']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>
                <form method="POST" id="orderForm">
                    <div class="mb-3">
                        <label class="form-label">Ordering Person Name</label>
                        <input type="text" class="form-control" name="ordering_person_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Company Email</label>
                        <input type="email" class="form-control" name="company_email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ordering Source</label>
                        <select class="form-select" name="ordering_source" required>
                            <option value="SPC">SPC</option>
                            <option value="Supplier">Supplier</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Order Date and Time</label>
                        <input type="datetime-local" class="form-control" name="order_date" id="orderDate" readonly required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Search and Filter Drugs</label>
                        <div class="row">
                            <div class="col-md-4">
                                <input type="text" id="orderSearchBar" class="form-control" placeholder="Search by drug name...">
                            </div>
                        <div class="col-md-4">
                        <select id="orderSupplierTypeFilter" class="form-select">
                            <option value="">Filter by Supplier Type</option>
                            <option value="SPC">SPC</option>
                            <option value="Supplier">Supplier</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select id="orderStockFilter" class="form-select">
                            <option value="">Filter by Stock</option>
                            <option value="0-10">0-10</option>
                            <option value="10-100">10-100</option>
                            <option value="100-500">100-500</option>
                            <option value="500+">500+</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table for Selecting Drugs -->
            <div class="mb-3">
                <label class="form-label">Available Drugs</label>
                <table class="table table-bordered" id="availableDrugsTable">
                    <thead>
                        <tr>
                            <th>Drug ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Stock</th>
                            <th>Supplier Type</th>
                            <th>Capacity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $drugs_result->data_seek(0); // Reset pointer to fetch results again
                        while ($drug = $drugs_result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $drug['drug_id'] . "</td>";
                            echo "<td>" . $drug['name'] . "</td>";
                            echo "<td>" . $drug['description'] . "</td>";
                            echo "<td>" . $drug['stock'] . "</td>";
                            echo "<td>" . $drug['supplier_type'] . "</td>";
                            echo "<td>" . $drug['capacity'] . "</td>";
                            echo "<td><button type='button' class='btn btn-success btn-sm add-drug' 
                                    data-id='" . $drug['id'] . "' 
                                    data-drug_id='" . $drug['drug_id'] . "' 
                                    data-name='" . $drug['name'] . "' 
                                    data-description='" . $drug['description'] . "' 
                                    data-stock='" . $drug['stock'] . "'
                                    data-capacity='" . $drug['capacity'] . "'>
                                    <i class='bi bi-plus-circle'></i> </button></td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Table for Selected Drugs -->
            <div class="mb-3">
                <label class="form-label">Selected Drugs</label>
                <table class="table table-bordered" id="selectedDrugsTable">
                    <thead>
                        <tr>
                            <th>Drug ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Capacity</th>
                            <th>Stock</th>
                            <th>Quantity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="selectedDrugs">
                        <!-- Selected drugs will be added here dynamically -->
                    </tbody>
                </table>
            </div>

            <button type="submit" name="place_order" class="btn btn-primary" onclick="return confirmPlaceOrder();">Place Order</button>
            </form>
            </div>
        </div>


        <?php
            // Query to count orders by status
            $order_counts_sql = "SELECT status, COUNT(*) AS count FROM orders GROUP BY status";
            $order_counts_result = $conn->query($order_counts_sql);

            // Initialize counts
            $canceled_count = 0;
            $pending_count = 0;
            $closed_count = 0;

            // Loop through the results and assign counts
            while ($row = $order_counts_result->fetch_assoc()) {
                if ($row['status'] === 'Canceled') {
                    $canceled_count = $row['count'];
                } elseif ($row['status'] === 'Pending') {
                    $pending_count = $row['count'];
                } elseif ($row['status'] === 'Closed') {
                    $closed_count = $row['count'];
                }
            }
        ?>
        <!-- Placed Orders -->
        <div id="placedOrders" class="section" style="display: none;">
            <div class="container mt-4">
                <h3 class="mt-4">Placed Orders</h3>
                <?php if (isset($_SESSION['success_message2'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message2']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_message2']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message2'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error_message2']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error_message2']); ?>
                <?php endif; ?>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card border-danger">
                            <div class="card-body">
                                <h5 class="card-title text-danger">
                                    <i class="bi bi-x-circle-fill"></i> Canceled Orders
                                </h5>
                                <p class="card-text">Total number of canceled orders.</p>
                                <h3 class="text-danger">
                                    <strong><?php echo $canceled_count; ?></strong>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-warning">
                            <div class="card-body">
                                <h5 class="card-title text-warning">
                                    <i class="bi bi-hourglass-split"></i> Pending Orders
                                </h5>
                                <p class="card-text">Total number of pending orders.</p>
                                <h3 class="text-warning">
                                    <strong><?php echo $pending_count; ?></strong>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-success">
                            <div class="card-body">
                                <h5 class="card-title text-success">
                                    <i class="bi bi-check-circle-fill"></i> Closed Orders
                                </h5>
                                <p class="card-text">Total number of closed orders.</p>
                                <h3 class="text-success">
                                    <strong><?php echo $closed_count; ?></strong>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="orderDateFilter" class="form-label">Filter by Order Date</label>
                        <input type="date" id="orderDateFilter" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="orderSourceFilter" class="form-label">Filter by Order Source</label>
                        <select id="orderSourceFilter" class="form-select">
                            <option value="">All Sources</option>
                            <option value="SPC">SPC</option>
                            <option value="Supplier">Supplier</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="orderStatusFilter" class="form-label">Filter by Order Status</label>
                        <select id="orderStatusFilter" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="Pending">Pending</option>
                            <option value="Closed">Closed</option>
                            <option value="Canceled">Canceled</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Order Date</th>
                                <th>Ordering Person</th>
                                <th>Company Email</th>
                                <th>Ordering Source</th>
                                <th>Status</th>
                                <th>Items</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch all orders
                            $orders_sql = "SELECT * FROM orders ORDER BY order_date DESC";
                            $orders_result = $conn->query($orders_sql);

                            while ($order = $orders_result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $order['id'] . "</td>";
                                echo "<td>" . $order['order_date'] . "</td>";
                                echo "<td>" . $order['ordering_person_name'] . "</td>";
                                echo "<td>" . $order['company_email'] . "</td>";
                                echo "<td>" . $order['ordering_source'] . "</td>";
                                echo "<td><span class='badge bg-" . ($order['status'] === 'Canceled' ? 'danger' : ($order['status'] === 'Closed' ? 'success' : 'warning')) . "'>" . $order['status'] . "</span></td>";
                                echo "<td><button type='button' class='btn btn-info btn-sm view-items' data-order-id='" . $order['id'] . "' data-bs-toggle='modal' data-bs-target='#orderItemsModal'>View Items</button></td>";
                                echo "<td>";
                                if ($order['status'] === 'Pending') {
                                    echo "<form method='POST' style='display:inline;' onsubmit='return confirmCancel();'>
                                            <input type='hidden' name='cancel_order_id' value='" . $order['id'] . "'>
                                            <button type='submit' name='cancel_order' class='btn btn-danger btn-sm'>Cancel</button>
                                        </form>";
                                } else {
                                    echo "<button class='btn btn-secondary btn-sm' disabled>Cancel</button>";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="orderItemsModal" tabindex="-1" aria-labelledby="orderItemsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="orderItemsModalLabel">Order Items</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Drug ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Capacity</th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody id="orderItemsTable">
                                <!-- Order items will be dynamically loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inquiries Section -->
        <div id="inquiries" class="section" style="display: none;">
            <div class="container mt-4">
                <h3 class="mt-4">All Inquiries</h3>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" id="inquiryEmailFilter" class="form-control" placeholder="Search by email...">
                    </div>
                    <div class="col-md-4">
                        <select id="inquiryTypeFilter" class="form-select">
                            <option value="">Filter by Inquiry Type</option>
                            <option value="Order">Order</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select id="inquiryStatusFilter" class="form-select">
                            <option value="">Filter by Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Replied">Replied</option>
                        </select>
                    </div>
                </div>

                <?php
                // Fetch all inquiries from the database
                $inquiries_sql = "SELECT * FROM inquiries ORDER BY created_at DESC";
                $inquiries_result = $conn->query($inquiries_sql);
                ?>

                <?php if ($inquiries_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Inquiry Type</th>
                                    <th>Details</th>
                                    <th>Submitted At</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($inquiry = $inquiries_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($inquiry['id']) ?></td>
                                        <td><?= htmlspecialchars($inquiry['user_id']) ?></td>
                                        <td><?= htmlspecialchars($inquiry['name']) ?></td>
                                        <td><?= htmlspecialchars($inquiry['email']) ?></td>
                                        <td><?= htmlspecialchars($inquiry['inquiry_type']) ?></td>
                                        <td><?= htmlspecialchars($inquiry['details']) ?></td>
                                        <td><?= htmlspecialchars($inquiry['created_at']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $inquiry['status'] === 'Replied' ? 'success' : 'warning' ?>">
                                                <?= htmlspecialchars($inquiry['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button 
                                                class="btn btn-primary btn-sm reply-btn" 
                                                data-id="<?= htmlspecialchars($inquiry['id']) ?>" 
                                                data-email="<?= htmlspecialchars($inquiry['email']) ?>" 
                                                data-name="<?= htmlspecialchars($inquiry['name']) ?>" 
                                                data-details="<?= htmlspecialchars($inquiry['details']) ?>" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#replyModal">
                                                Reply
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No inquiries found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reply Modal -->
        <div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="replyModalLabel">Reply to Inquiry</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="replyForm" method="POST" action="send_reply.php">
                            <input type="hidden" id="inquiryId" name="inquiry_id"> <!-- Hidden field for inquiry ID -->
                            <div class="mb-3">
                                <label for="recipientEmail" class="form-label">Recipient Email</label>
                                <input type="email" class="form-control" id="recipientEmail" name="recipient_email" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="inquiryDetails" class="form-label">Inquiry Details</label>
                                <textarea class="form-control" id="inquiryDetails" rows="3" readonly></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="replyMessage" class="form-label">Reply Message</label>
                                <textarea class="form-control" id="replyMessage" name="reply_message" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-success">Send Reply</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="tenders" class="section" style="display: none;">
            <div class="container mt-4">
                <h3 class="mt-4">All Tenders</h3>

                <?php if (isset($_SESSION['success_message4'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message4']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_message4']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message4'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error_message4']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error_message4']); ?>
                <?php endif; ?>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="tenderOrderIdFilter" class="form-label">Filter by Order ID</label>
                        <input type="text" id="tenderOrderIdFilter" class="form-control" placeholder="Enter Order ID">
                    </div>
                    <div class="col-md-4">
                        <label for="tenderOrderDateFilter" class="form-label">Filter by Order Date</label>
                        <input type="date" id="tenderOrderDateFilter" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="tenderStatusFilter" class="form-label">Filter by Tender Status</label>
                        <select id="tenderStatusFilter" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Tender ID</th>
                                <th>Order ID</th>
                                <th>Order Date</th>
                                <th>Supplier Email</th>
                                <th>Business Registration Number</th>
                                <th>Tender Date</th>
                                <th>Tender Amount</th>
                                <th>Document</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch all tenders
                            $tenders_sql = "SELECT * FROM tenders ORDER BY tender_date DESC";
                            $tenders_result = $conn->query($tenders_sql);

                            while ($tender = $tenders_result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $tender['id'] . "</td>";
                                echo "<td>" . $tender['order_id'] . "</td>";
                                echo "<td>" . $tender['order_date'] . "</td>";
                                echo "<td>" . $tender['supplier_email'] . "</td>";
                                echo "<td>" . $tender['business_reg_number'] . "</td>";
                                echo "<td>" . $tender['tender_date'] . "</td>";
                                echo "<td>" . number_format($tender['tender_amount'], 2) . "</td>";
                                echo "<td><a href='" . $tender['document_path'] . "' target='_blank' class='btn btn-primary btn-sm'>View Document</a></td>";
                                echo "<td><span class='badge bg-" . ($tender['status'] === 'Rejected' ? 'danger' : ($tender['status'] === 'Approved' ? 'success' : 'warning')) . "'>" . $tender['status'] . "</span></td>";
                                echo "<td>";
                                if ($tender['status'] === 'Pending') {
                                    echo "<form method='POST' style='display:inline;'>
                                            <input type='hidden' name='tender_id' value='" . $tender['id'] . "'>
                                            <input type='hidden' name='order_id' value='" . $tender['order_id'] . "'>
                                            <button type='submit' name='approve_tender' class='btn btn-success btn-sm'>Approve</button>
                                            <button type='submit' name='reject_tender' class='btn btn-danger btn-sm'>Reject</button>
                                        </form>";
                                } else {
                                    echo "<button class='btn btn-secondary btn-sm' disabled>No Actions</button>";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- Pharmacy Orders Section -->
    <div id="pharmacyOrders" class="section" style="display: none;">
        <div class="container mt-4">
            <h3 class="mt-4">Pharmacy Orders</h3>

            <?php if (isset($_SESSION['success_message37'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success_message37']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success_message37']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message37'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error_message37']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error_message37']); ?>
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="pharmacyOrderDateFilter" class="form-label">Filter by Order Date</label>
                    <input type="date" id="pharmacyOrderDateFilter" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="pharmacyOrderStatusFilter" class="form-label">Filter by Order Status</label>
                    <select id="pharmacyOrderStatusFilter" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Pending">Pending</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="pharmacyTrackingIdFilter" class="form-label">Filter by Tracking ID</label>
                    <input type="text" id="pharmacyTrackingIdFilter" class="form-control" placeholder="Enter Tracking ID">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="pharmacyOrdersTable">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>User Email</th>
                            <th>Drug Name</th>
                            <th>Quantity</th>
                            <th>Total Price</th>
                            <th>Order Date</th>
                            <th>Order Status</th>
                            <th>Tracking ID</th>
                            <th>Tracking Status</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $pharmacy_orders_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $order['order_id']; ?></td>
                                <td><?php echo $order['user_email']; ?></td>
                                <td><?php echo $order['drug_name']; ?></td>
                                <td><?php echo $order['quantity']; ?></td>
                                <td><?php echo number_format($order['total_price'], 2); ?></td>
                                <td><?php echo $order['order_date']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $order['order_status'] === 'Completed' ? 'success' : ($order['order_status'] === 'Cancelled' ? 'danger' : 'warning'); ?>">
                                        <?php echo $order['order_status']; ?>
                                    </span>
                                </td>
                                <td><?php echo $order['tracking_id'] ?? 'N/A'; ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                        <select name="tracking_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="Pending" <?php if ($order['tracking_status'] === 'Pending') echo 'selected'; ?>>Pending</option>
                                            <option value="Shipped" <?php if ($order['tracking_status'] === 'Shipped') echo 'selected'; ?>>Shipped</option>
                                            <option value="Delivered" <?php if ($order['tracking_status'] === 'Delivered') echo 'selected'; ?>>Delivered</option>
                                        </select>
                                    </form>
                                </td>
                                <td><?php echo $order['updated_at'] ?? 'N/A'; ?></td>
                                <td>
                                    <?php if ($order['order_status'] === 'Pending'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            <button type="submit" name="complete_order" class="btn btn-success btn-sm">Complete</button>
                                            <button type="submit" name="cancel_order1" class="btn btn-danger btn-sm">Cancel</button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm" disabled>No Actions</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- JavaScript for Filtering -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const searchInput = document.getElementById("searchInput");
            const userTypeFilter = document.getElementById("userTypeFilter");
            const statusFilter = document.getElementById("statusFilter");

            function filterTable() {
                const searchText = searchInput.value.toLowerCase();
                const userType = userTypeFilter.value.toLowerCase();
                const status = statusFilter.value.toLowerCase();

                document.querySelectorAll("#userTable tr").forEach(row => {
                    const email = row.cells[2].textContent.toLowerCase();
                    const type = row.cells[8].getAttribute("data-raw-type").toLowerCase(); // Use raw user_type from data attribute
                    const stat = row.cells[9].textContent.toLowerCase();

                    row.style.display = (!searchText || email.includes(searchText)) &&
                                        (!userType || type === userType) &&
                                        (!status || stat === status) ? "" : "none";
                });
            }

            searchInput.addEventListener("keyup", filterTable);
            userTypeFilter.addEventListener("change", filterTable);
            statusFilter.addEventListener("change", filterTable);

        
        });

        document.addEventListener("DOMContentLoaded", function() {
            const drugSearchInput = document.getElementById("searchBar");
            const stockFilter = document.getElementById("stockFilter");
            const supplierTypeFilter = document.getElementById("supplierTypeFilter"); // Add supplier type filter

            function filterDrugTable() {
                const searchText = drugSearchInput.value.toLowerCase();
                const stockRange = stockFilter.value;
                const supplierType = supplierTypeFilter.value.toLowerCase(); // Get selected supplier type

                document.querySelectorAll("#drugTable tbody tr").forEach(row => {
                    const drugName = row.cells[2].textContent.toLowerCase(); // Adjust the cell index if needed
                    const stock = parseInt(row.cells[9].textContent, 10); // Assuming stock is in column index 9
                    const supplier = row.cells[4].textContent.toLowerCase(); // Assuming supplier type is in column index 4

                    let stockMatch = false;
                    if (stockRange === "0-10") {
                        stockMatch = stock >= 0 && stock <= 10;
                    } else if (stockRange === "10-100") {
                        stockMatch = stock > 10 && stock <= 100;
                    } else if (stockRange === "100-500") {
                        stockMatch = stock > 100 && stock <= 500;
                    } else if (stockRange === "500+") {
                        stockMatch = stock > 500;
                    } else {
                        stockMatch = true; // No filter applied
                    }

                    const supplierMatch = !supplierType || supplier === supplierType; // Match supplier type

                    row.style.display = (!searchText || drugName.includes(searchText)) && stockMatch && supplierMatch ? "" : "none";
                });
            }

            drugSearchInput.addEventListener("keyup", filterDrugTable);
            stockFilter.addEventListener("change", filterDrugTable);
            supplierTypeFilter.addEventListener("change", filterDrugTable); // Add event listener for supplier type filter
        });

        // Populate the Update Drug Modal with selected drug details
        document.querySelectorAll('button[data-bs-target="#updateDrugModal"]').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('updateDrugId').value = this.getAttribute('data-id');
                document.getElementById('updateDrugIdInput').value = this.getAttribute('data-drug_id');
                document.getElementById('updateName').value = this.getAttribute('data-name');
                document.getElementById('updateDescription').value = this.getAttribute('data-description');
                document.getElementById('updateSupplierType').value = this.getAttribute('data-supplier_type');
                document.getElementById('updateRegNumber').value = this.getAttribute('data-reg_number');
                document.getElementById('updateManufactureDate').value = this.getAttribute('data-manufacture_date');
                document.getElementById('updateExpireDate').value = this.getAttribute('data-expire_date');
                document.getElementById('updateCapacity').value = this.getAttribute('data-capacity');
                document.getElementById('updateStock').value = this.getAttribute('data-stock');
                document.getElementById('updateUnitPrice').value = this.getAttribute('data-unit_price'); // Populate Unit Price
            });
        });

        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.style.display = 'none';
            });

            // Show the selected section
            document.getElementById(sectionId).style.display = 'block';
        }

        document.addEventListener("DOMContentLoaded", function () {
            const selectedDrugsTable = document.getElementById("selectedDrugs");

            // Handle "+" button click in the available drugs table
            document.querySelectorAll(".add-drug").forEach(button => {
                button.addEventListener("click", function () {
                    const drugId = this.getAttribute("data-id");
                    const drugDrugId = this.getAttribute("data-drug_id");
                    const drugName = this.getAttribute("data-name");
                    const drugDescription = this.getAttribute("data-description");
                    const drugCapacity = this.getAttribute("data-capacity");
                    const drugStock = this.getAttribute("data-stock");

                    // Check if the drug is already added
                    if (document.querySelector(`#drug-${drugId}`)) {
                        alert("This drug is already added to the order.");
                        return;
                    }

                    // Create a new row for the selected drug
                    const row = document.createElement("tr");
                    row.id = `drug-${drugId}`;
                    row.innerHTML = `
                        <td>
                            <input type="hidden" name="drug_id[]" value="${drugId}">
                            ${drugDrugId}
                        </td>
                        <td>${drugName}</td>
                        <td>${drugDescription}</td>
                        <td>${drugCapacity}</td>
                        <td>${drugStock}</td>
                        <td>
                            <input type="number" class="form-control" name="quantity[]" placeholder="Quantity" min="1" required>
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm remove-drug">Remove</button>
                        </td>
                    `;

                    // Add the row to the selected drugs table
                    selectedDrugsTable.appendChild(row);

                    // Handle "Remove" button click
                    row.querySelector(".remove-drug").addEventListener("click", function () {
                        row.remove();
                    });
                });
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            const searchInput = document.getElementById("orderSearchBar");
            const supplierTypeFilter = document.getElementById("orderSupplierTypeFilter");
            const stockFilter = document.getElementById("orderStockFilter");

            function filterOrderDrugsTable() {
                const searchText = searchInput.value.toLowerCase();
                const supplierType = supplierTypeFilter.value.toLowerCase();
                const stockRange = stockFilter.value;

                document.querySelectorAll("#availableDrugsTable tbody tr").forEach(row => {
                    const drugName = row.cells[1].textContent.toLowerCase(); // Drug name is in column index 1
                    const supplier = row.cells[4].textContent.toLowerCase(); // Supplier type is in column index 4
                    const stock = parseInt(row.cells[3].textContent, 10); // Stock is in column index 3

                    let stockMatch = false;
                    if (stockRange === "0-10") {
                        stockMatch = stock >= 0 && stock <= 10;
                    } else if (stockRange === "10-100") {
                        stockMatch = stock > 10 && stock <= 100;
                    } else if (stockRange === "100-500") {
                        stockMatch = stock > 100 && stock <= 500;
                    } else if (stockRange === "500+") {
                        stockMatch = stock > 500;
                    } else {
                        stockMatch = true; // No stock filter applied
                    }

                    const supplierMatch = !supplierType || supplier === supplierType; // Match supplier type
                    const searchMatch = !searchText || drugName.includes(searchText); // Match search text

                    row.style.display = searchMatch && supplierMatch && stockMatch ? "" : "none";
                });
            }

            searchInput.addEventListener("keyup", filterOrderDrugsTable);
            supplierTypeFilter.addEventListener("change", filterOrderDrugsTable);
            stockFilter.addEventListener("change", filterOrderDrugsTable);
        });

        document.addEventListener("DOMContentLoaded", function () {
            const orderDateInput = document.getElementById("orderDate");

            // Set the current date and time in the input field
            const now = new Date();
            const formattedDate = now.toISOString().slice(0, 16); // Format as "YYYY-MM-DDTHH:mm"
            orderDateInput.value = formattedDate;
        });

        function confirmPlaceOrder() {
            return confirm("Are you sure you want to place this order?");
        }

        document.addEventListener("DOMContentLoaded", function () {
            const orderItemsTable = document.getElementById("orderItemsTable");

            // Handle "View Items" button click
            document.querySelectorAll(".view-items").forEach(button => {
                button.addEventListener("click", function () {
                    const orderId = this.getAttribute("data-order-id");

                    // Clear the table before loading new items
                    orderItemsTable.innerHTML = "";

                    // Fetch order items via AJAX
                    fetch(`fetch_order_items.php?order_id=${orderId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.length > 0) {
                                data.forEach(item => {
                                    const row = document.createElement("tr");
                                    row.innerHTML = `
                                        <td>${item.drug_id}</td>
                                        <td>${item.name}</td>
                                        <td>${item.description}</td>
                                        <td>${item.capacity}</td>
                                        <td>${item.quantity}</td>
                                    `;
                                    orderItemsTable.appendChild(row);
                                });
                            } else {
                                const row = document.createElement("tr");
                                row.innerHTML = `<td colspan="5" class="text-center">No items found for this order.</td>`;
                                orderItemsTable.appendChild(row);
                            }
                        })
                        .catch(error => console.error("Error fetching order items:", error));
                });
            });
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
                    // Redirect to login.php
                    window.location.href = 'logout.php';
                }
            });
        }

        document.addEventListener("DOMContentLoaded", function () {
            const orderDateFilter = document.getElementById("orderDateFilter");
            const orderSourceFilter = document.getElementById("orderSourceFilter");
            const orderStatusFilter = document.getElementById("orderStatusFilter");

            function filterOrdersTable() {
                const selectedDate = orderDateFilter.value;
                const selectedSource = orderSourceFilter.value.toLowerCase();
                const selectedStatus = orderStatusFilter.value.toLowerCase();

                document.querySelectorAll("#placedOrders tbody tr").forEach(row => {
                    const orderDate = row.cells[1].textContent.trim(); // Order Date is in column index 1
                    const orderSource = row.cells[4].textContent.trim().toLowerCase(); // Order Source is in column index 4
                    const orderStatus = row.cells[5].textContent.trim().toLowerCase(); // Order Status is in column index 5

                    const dateMatch = !selectedDate || orderDate.startsWith(selectedDate); // Match date
                    const sourceMatch = !selectedSource || orderSource === selectedSource; // Match source
                    const statusMatch = !selectedStatus || orderStatus === selectedStatus; // Match status

                    row.style.display = dateMatch && sourceMatch && statusMatch ? "" : "none";
                });
            }

            orderDateFilter.addEventListener("change", filterOrdersTable);
            orderSourceFilter.addEventListener("change", filterOrdersTable);
            orderStatusFilter.addEventListener("change", filterOrdersTable);
        });

        function confirmCancel() {
            return confirm("Are you sure you want to cancel this order?");
        }

        document.addEventListener("DOMContentLoaded", function () {
            const tenderOrderIdFilter = document.getElementById("tenderOrderIdFilter");
            const tenderOrderDateFilter = document.getElementById("tenderOrderDateFilter");
            const tenderStatusFilter = document.getElementById("tenderStatusFilter");

            function filterTendersTable() {
                const orderId = tenderOrderIdFilter.value.trim().toLowerCase();
                const orderDate = tenderOrderDateFilter.value;
                const status = tenderStatusFilter.value.toLowerCase();

                document.querySelectorAll("#tenders tbody tr").forEach(row => {
                    const rowOrderId = row.cells[1].textContent.trim().toLowerCase(); // Order ID is in column index 1
                    const rowOrderDate = row.cells[2].textContent.trim(); // Order Date is in column index 2
                    const rowStatus = row.cells[8].textContent.trim().toLowerCase(); // Tender Status is in column index 8

                    const orderIdMatch = !orderId || rowOrderId.includes(orderId); // Match Order ID
                    const orderDateMatch = !orderDate || rowOrderDate.startsWith(orderDate); // Match Order Date
                    const statusMatch = !status || rowStatus === status; // Match Tender Status

                    row.style.display = orderIdMatch && orderDateMatch && statusMatch ? "" : "none";
                });
            }

            tenderOrderIdFilter.addEventListener("keyup", filterTendersTable);
            tenderOrderDateFilter.addEventListener("change", filterTendersTable);
            tenderStatusFilter.addEventListener("change", filterTendersTable);
        });

        document.addEventListener("DOMContentLoaded", function () {
            // Check if the target section is set in the session
            <?php if (isset($_SESSION['target_section'])): ?>
                const targetSection = "<?php echo $_SESSION['target_section']; ?>";
                showSection(targetSection); // Navigate to the target section
                <?php unset($_SESSION['target_section']); // Clear the session variable ?>
            <?php endif; ?>
        });

        document.addEventListener("DOMContentLoaded", function () {
            const replyButtons = document.querySelectorAll(".reply-btn");
            const recipientEmailInput = document.getElementById("recipientEmail");
            const inquiryDetailsTextarea = document.getElementById("inquiryDetails");
            const inquiryIdInput = document.getElementById("inquiryId");

            replyButtons.forEach(button => {
                button.addEventListener("click", function () {
                    const email = this.getAttribute("data-email");
                    const details = this.getAttribute("data-details");
                    const inquiryId = this.getAttribute("data-id"); // Get the inquiry ID

                    recipientEmailInput.value = email;
                    inquiryDetailsTextarea.value = details;
                    inquiryIdInput.value = inquiryId; // Set the inquiry ID in the hidden input
                });
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            const emailFilter = document.getElementById("inquiryEmailFilter");
            const typeFilter = document.getElementById("inquiryTypeFilter");
            const statusFilter = document.getElementById("inquiryStatusFilter");

            function filterInquiriesTable() {
                const emailText = emailFilter.value.toLowerCase();
                const selectedType = typeFilter.value.toLowerCase();
                const selectedStatus = statusFilter.value.toLowerCase();

                document.querySelectorAll("#inquiries tbody tr").forEach(row => {
                    const email = row.cells[3].textContent.toLowerCase(); // Email is in column index 3
                    const type = row.cells[4].textContent.toLowerCase(); // Inquiry Type is in column index 4
                    const status = row.cells[7].textContent.toLowerCase(); // Status is in column index 7

                    const emailMatch = !emailText || email.includes(emailText);
                    const typeMatch = !selectedType || type === selectedType;
                    const statusMatch = !selectedStatus || status === selectedStatus;

                    row.style.display = emailMatch && typeMatch && statusMatch ? "" : "none";
                });
            }

            emailFilter.addEventListener("keyup", filterInquiriesTable);
            typeFilter.addEventListener("change", filterInquiriesTable);
            statusFilter.addEventListener("change", filterInquiriesTable);
        });

        document.addEventListener("DOMContentLoaded", function () {
            const orderDateFilter = document.getElementById("pharmacyOrderDateFilter");
            const orderStatusFilter = document.getElementById("pharmacyOrderStatusFilter");
            const trackingIdFilter = document.getElementById("pharmacyTrackingIdFilter");

            function filterPharmacyOrdersTable() {
                const selectedDate = orderDateFilter.value;
                const selectedStatus = orderStatusFilter.value.toLowerCase();
                const trackingId = trackingIdFilter.value.trim().toLowerCase();

                document.querySelectorAll("#pharmacyOrdersTable tbody tr").forEach(row => {
                    const orderDate = row.cells[5].textContent.trim(); // Order Date is in column index 5
                    const orderStatus = row.cells[6].textContent.trim().toLowerCase(); // Order Status is in column index 6
                    const trackingIdValue = row.cells[7].textContent.trim().toLowerCase(); // Tracking ID is in column index 7

                    const dateMatch = !selectedDate || orderDate.startsWith(selectedDate); // Match Order Date
                    const statusMatch = !selectedStatus || orderStatus === selectedStatus; // Match Order Status
                    const trackingIdMatch = !trackingId || trackingIdValue.includes(trackingId); // Match Tracking ID

                    row.style.display = dateMatch && statusMatch && trackingIdMatch ? "" : "none";
                });
            }

            orderDateFilter.addEventListener("change", filterPharmacyOrdersTable);
            orderStatusFilter.addEventListener("change", filterPharmacyOrdersTable);
            trackingIdFilter.addEventListener("keyup", filterPharmacyOrdersTable);
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>
