<?php
// filepath: c:\xampp\htdocs\SPC\supplier_home.php

session_start();

// Check if the user is logged in and is a supplier
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'supplier') {
    header("Location: login.php");
    exit();
}

require 'db.php';

$user_id = $_SESSION['user_id'];
$sql = "SELECT name, email, mobile, reg_number, company_name, business_reg_number, address, province, district, latitude, longitude FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($supplier_name, $supplier_email, $supplier_mobile, $supplier_reg_number, $supplier_company_name, $supplier_business_reg_number, $supplier_address, $supplier_province, $supplier_district, $supplier_latitude, $supplier_longitude);
$stmt->fetch();
$stmt->close();

// Get the logged-in supplier's name and email
$supplier_name = $_SESSION['user_name'];
$supplier_email = $_SESSION['user_email'];

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

$tenders_sql = "SELECT * FROM tenders WHERE supplier_email = ? ORDER BY tender_date DESC";
$stmt = $conn->prepare($tenders_sql);
$stmt->bind_param("s", $supplier_email); // Use the logged-in supplier's email
$stmt->execute();
$tenders_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
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
            boarde
            font-size: 1.5rem;
            font-weight: bold;
            color: white !important;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
            width: 100%;
        }
        .welcome-card {
            margin-top:10px;
            margin-bottom:10px;
            background-color:rgb(81, 104, 129);
            color: white;
            border-radius: 10px;
            padding: 20px;
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
        .table-wrapper {
            max-height: 400px; /* Set the maximum height for the table */
            overflow-y: auto; /* Enable vertical scrolling */
            border: 1px solid #dee2e6; /* Optional: Add a border around the table */
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h4 class="text-center"><i class="bi bi-person-circle"></i> Supplier Panel</h4>
        <hr>
        
        <a href="javascript:void(0);" onclick="showSection('dashboard')"><i class="bi bi-house-door-fill"></i> Dashboard</a>
        <a href="javascript:void(0);" onclick="showSection('orders')"><i class="bi bi-cart-fill"></i> Orders</a>
        <a href="javascript:void(0);" onclick="showSection('profile')"><i class="bi bi-person-fill"></i> Profile</a>
        <a href="javascript:void(0);" onclick="showSection('tenders')"><i class="bi bi-file-earmark-text"></i> Tenders</a>
        <a href="javascript:void(0);" class="text-danger" onclick="confirmLogout();"><i class="bi bi-box-arrow-right"></i> Logout</a>
        
    </div>

    <!-- Content -->
    <div class="content">
        <!-- Header Bar -->
        <nav class="navbar navbar-dark bg-primary">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h1">
                    <i class="bi bi-building"></i> State Pharmaceutical Cooperation (SPC)
                </span>
            </div>
        </nav>
        <!-- Welcome Card -->
        <div class="welcome-card">
            <p>Welcome, <?php echo htmlspecialchars($supplier_name); ?>!</p>
            <p>Email: <?php echo htmlspecialchars($supplier_email); ?></p>
        </div>

        <!-- Sections -->
        <div id="dashboard" class="section">

        <nav class="navbar navbar-dark bg-primary">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h1">
                    <i class="bi bi-building"></i> Dashboard
                </span>
            </div>
        </nav>

            <!-- Quick Stats Row -->
            <div class="row mt-3">
                <div class="col-md-4 mb-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-cart"></i> Active Orders</h5>
                            <?php
                            $active_orders_sql = "SELECT COUNT(*) as count FROM orders WHERE ordering_source = 'Supplier' AND status = 'Pending'";
                            $active_orders_result = $conn->query($active_orders_sql);
                            $active_orders = $active_orders_result->fetch_assoc()['count'];
                            ?>
                            <h3><?php echo $active_orders; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-file-earmark-text"></i> Submitted Tenders</h5>
                            <?php
                            $tenders_sql = "SELECT COUNT(*) as count FROM tenders WHERE supplier_email = '$supplier_email'";
                            $tenders_result = $conn->query($tenders_sql);
                            $tenders_count = $tenders_result->fetch_assoc()['count'];
                            ?>
                            <h3><?php echo $tenders_count; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-check-circle"></i> Approved Tenders</h5>
                            <?php
                            $approved_tenders_sql = "SELECT COUNT(*) as count FROM tenders WHERE supplier_email = '$supplier_email' AND status = 'Approved'";
                            $approved_tenders_result = $conn->query($approved_tenders_sql);
                            $approved_tenders = $approved_tenders_result->fetch_assoc()['count'];
                            ?>
                            <h3><?php echo $approved_tenders; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="container mt-4">
            <div class="row">
                <!-- About SPC Card -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0"><i class="bi bi-info-circle"></i> About SPC</h5>
                        </div>
                        <div class="card-body">
                            <p>The State Pharmaceuticals Corporation of Sri Lanka (SPC) was established in 1971 as a state-owned enterprise. Our main objectives include:</p>
                            <ul>
                                <li>Ensuring the availability of safe, effective, and high-quality medicines</li>
                                <li>Making essential medicines accessible at affordable prices</li>
                                <li>Maintaining buffer stocks of essential medicines</li>
                                <li>Supporting the national healthcare system through efficient distribution</li>
                            </ul>
                            <p>As a supplier, you play a crucial role in helping us achieve these objectives and serve the healthcare needs of Sri Lanka.</p>
                        </div>
                    </div>
                </div>

                <!-- Rules and Regulations Card -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0"><i class="bi bi-journal-text"></i> Rules and Regulations</h5>
                        </div>
                        <div class="card-body">
                            <h6 class="text-primary">Key Requirements for Suppliers:</h6>
                            <ul>
                                <li>Must maintain valid business registration and relevant licenses</li>
                                <li>All pharmaceutical products must be registered with NMRA</li>
                                <li>Must comply with Good Manufacturing Practice (GMP) standards</li>
                                <li>Ensure timely delivery of orders as per agreements</li>
                                <li>Maintain proper documentation for all transactions</li>
                            </ul>

                            <h6 class="text-primary mt-3">Tender Submission Guidelines:</h6>
                            <ul>
                                <li>Submit all required documents before the deadline</li>
                                <li>Ensure competitive pricing while maintaining quality</li>
                                <li>Include detailed product specifications and certifications</li>
                                <li>Maintain transparency in pricing and terms</li>
                                <li>Follow proper documentation formats as specified</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
    </div>

        <div id="profile" class="section" style="display: none;">
        <nav class="navbar navbar-dark bg-primary">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h1">
                    <i class="bi bi-building"></i> Profile
                </span>
            </div>
        </nav>
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success_message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error_message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <form method="POST" action="update_profile.php">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($supplier_name); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($supplier_email); ?>" readonly>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="mobile" class="form-label">Mobile Number</label>
                        <input type="text" class="form-control" id="mobile" name="mobile" value="<?php echo htmlspecialchars($supplier_mobile); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="reg_number" class="form-label">Registration Number</label>
                        <input type="text" class="form-control" id="reg_number" name="reg_number" value="<?php echo htmlspecialchars($supplier_reg_number); ?>" readonly>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="company_name" class="form-label">Company Name</label>
                        <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($supplier_company_name); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="business_reg_number" class="form-label">Business Registration Number</label>
                        <input type="text" class="form-control" id="business_reg_number" name="business_reg_number" value="<?php echo htmlspecialchars($supplier_business_reg_number); ?>" readonly>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($supplier_address); ?>" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="province" class="form-label">Province</label>
                        <input type="text" class="form-control" id="province" name="province" value="<?php echo htmlspecialchars($supplier_province); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="district" class="form-label">District</label>
                        <input type="text" class="form-control" id="district" name="district" value="<?php echo htmlspecialchars($supplier_district); ?>" readonly>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="latitude" class="form-label">Latitude</label>
                        <input type="text" class="form-control" id="latitude" name="latitude" value="<?php echo htmlspecialchars($supplier_latitude); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="longitude" class="form-label">Longitude</label>
                        <input type="text" class="form-control" id="longitude" name="longitude" value="<?php echo htmlspecialchars($supplier_longitude); ?>" readonly>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </div>


        <!-- Placed Orders -->
        <div id="orders" class="section" style="display: none;">
            <div class="container mt-4">
            <?php if (isset($_SESSION['success_message3'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success_message3']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success_message3']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message3'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error_message3']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error_message3']); ?>
            <?php endif; ?>

            <nav class="navbar navbar-dark bg-primary">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h1">
                    <i class="bi bi-building"></i> Placed Orders
                </span>
            </div>
        </nav>
                
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
                <table class="table table-bordered table-striped" id="ordersTable">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Order Date</th>
                            <th>Ordering Person</th>
                            <th>Company Email</th>
                            <th>Ordering Source</th>
                            <th>Status</th>
                            <th>Items</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch all orders
                        $orders_sql = "SELECT * FROM orders WHERE ordering_source = 'Supplier' ORDER BY order_date DESC";
                        $orders_result = $conn->query($orders_sql);

                        while ($order = $orders_result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $order['id'] . "</td>";
                            echo "<td>" . $order['order_date'] . "</td>";
                            echo "<td>" . $order['ordering_person_name'] . "</td>";
                            echo "<td>" . $order['company_email'] . "</td>";
                            echo "<td>" . $order['ordering_source'] . "</td>";
                            echo "<td><span class='badge bg-" . ($order['status'] === 'Canceled' ? 'danger' : ($order['status'] === 'Closed' ? 'success' : 'warning')) . "'>" . $order['status'] . "</span></td>";
                            echo "<td>
                                <button type='button' class='btn btn-info btn-sm view-items' data-order-id='" . $order['id'] . "' data-bs-toggle='modal' data-bs-target='#orderItemsModal'>View Items</button>
                            </td>";
                            echo "<td>";
                            if ($order['status'] !== 'Closed') {
                                echo "<button type='button' class='btn btn-primary btn-sm submit-tender' 
                                        data-order-id='" . $order['id'] . "' 
                                        data-order-date='" . $order['order_date'] . "' 
                                        data-bs-toggle='modal' 
                                        data-bs-target='#tenderFormModal'>
                                        Submit Tender
                                    </button>";
                            } else {
                                echo "<button class='btn btn-secondary btn-sm' disabled>Closed</button>";
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

        <div class="modal fade" id="tenderFormModal" tabindex="-1" aria-labelledby="tenderFormModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="tenderFormModalLabel">Submit Tender</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="submit_tender.php" enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="order_id" class="form-label">Order ID</label>
                                    <input type="text" class="form-control" id="order_id" name="order_id" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="order_date" class="form-label">Order Date</label>
                                    <input type="text" class="form-control" id="order_date" name="order_date" readonly>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="supplier_email" class="form-label">Supplier Email</label>
                                    <input type="email" class="form-control" id="supplier_email" name="supplier_email" value="<?php echo htmlspecialchars($supplier_email); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="business_reg_number" class="form-label">Business Registration Number</label>
                                    <input type="text" class="form-control" id="business_reg_number" name="business_reg_number" value="<?php echo htmlspecialchars($supplier_business_reg_number); ?>" readonly>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="tender_date" class="form-label">Tender Date</label>
                                    <input type="datetime-local" class="form-control" id="tender_date" name="tender_date" value="<?php echo date('Y-m-d\TH:i'); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="tender_amount" class="form-label">Tender Amount</label>
                                    <input type="number" class="form-control" id="tender_amount" name="tender_amount" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="tender_document" class="form-label">Upload Document</label>
                                    <input type="file" class="form-control" id="tender_document" name="tender_document" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Submit Tender</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <div id="tenders" class="section" style="display: none;">
            <div class="container mt-4">
            <nav class="navbar navbar-dark bg-primary">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h1">
                    <i class="bi bi-building"></i> All Tenders
                </span>
            </div>
        </nav>

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
                    </tr>
                </thead>
                <tbody>
                    <?php
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
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
                </div>
            </div>
        </div>
    </div>

    

    <!-- JavaScript -->
    <script>
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.style.display = 'none';
            });

            // Show the selected section
            document.getElementById(sectionId).style.display = 'block';
        }

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

        document.addEventListener("DOMContentLoaded", function () {
            const orderDateFilter = document.getElementById("orderDateFilter");
            const orderSourceFilter = document.getElementById("orderSourceFilter");
            const orderStatusFilter = document.getElementById("orderStatusFilter");

            function filterOrdersTable() {
                const selectedDate = orderDateFilter.value;
                const selectedSource = orderSourceFilter.value.toLowerCase();
                const selectedStatus = orderStatusFilter.value.toLowerCase();

                document.querySelectorAll("#ordersTable tbody tr").forEach(row => {
                    const orderDate = row.cells[1].textContent.trim(); // Order Date is in column index 1
                    const orderSource = row.cells[4].textContent.trim().toLowerCase(); // Order Source is in column index 4
                    const orderStatus = row.cells[5].textContent.trim().toLowerCase(); // Order Status is in column index 5

                    const dateMatch = !selectedDate || orderDate.startsWith(selectedDate); // Match date
                    const sourceMatch = !selectedSource || orderSource === selectedSource; // Match source
                    const statusMatch = !selectedStatus || orderStatus === selectedStatus; // Match status

                    // Apply all filters
                    row.style.display = dateMatch && sourceMatch && statusMatch ? "" : "none";
                });
            }

            orderDateFilter.addEventListener("change", filterOrdersTable);
            orderSourceFilter.addEventListener("change", filterOrdersTable);
            orderStatusFilter.addEventListener("change", filterOrdersTable);
        });

        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll(".submit-tender").forEach(button => {
                button.addEventListener("click", function () {
                    const orderId = this.getAttribute("data-order-id");
                    const orderDate = this.getAttribute("data-order-date");

                    // Populate the modal fields
                    document.getElementById("order_id").value = orderId;
                    document.getElementById("order_date").value = orderDate;
                });
            });
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
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>