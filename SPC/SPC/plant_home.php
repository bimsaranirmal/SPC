<?php
// filepath: c:\xampp\htdocs\SPC\supplier_home.php

session_start();

// Check if the user is logged in and is a supplier
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'manufacturing_plant_staff') {
    header("Location: login.php");
    exit();
}

require 'db.php';


// Get the logged-in supplier's name and email
$manufacturing_plant_staff_name = $_SESSION['user_name'];
$manufacturing_plant_staff_email = $_SESSION['user_email'];


// Fetch logged-in user details
$user_email = $_SESSION['user_email']; // Assuming the email is stored in the session
$user_sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

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
        header("Location: plant_home.php");
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
        header("Location: plant_home.php");
        exit();
    } else {
        echo "<script>alert('Error updating drug: " . $conn->error . "');</script>";
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['closed_order'])) {
    $order_id = intval($_POST['closed_order_id']);

    // Update the order status to 'Canceled'
    $cancel_order_sql = "UPDATE orders SET status = 'Closed' WHERE id = $order_id";
    if ($conn->query($cancel_order_sql) === TRUE) {
        $_SESSION['success_message2'] = "Order closed successfully!";
    } else {
        $_SESSION['error_message2'] = "Error canceling order: " . $conn->error;
    }
    $_SESSION['target_section'] = "placedOrders";
    header("Location: plant_home.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manufature Plant Dashboard</title>
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
        <h4 class="text-center"><i class="bi bi-person-circle"></i> Manufature Plant Panel</h4>
        <hr>
        
        <a href="javascript:void(0);" onclick="showSection('dashboard')"><i class="bi bi-house-door-fill"></i> Dashboard</a>
        <a href="javascript:void(0);" onclick="showSection('placedOrders')"><i class="bi bi-cart-fill"></i> Orders</a>
        <a href="javascript:void(0);" onclick="showSection('profile')"><i class="bi bi-person-fill"></i> Profile</a>
        <a href="javascript:void(0);" onclick="showSection('drugDetails')"><i class="bi bi-file-earmark-text"></i> Drug Details</a>
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
            <p>Welcome, <?php echo htmlspecialchars($manufacturing_plant_staff_name); ?>!</p>
            <p>Email: <?php echo htmlspecialchars($manufacturing_plant_staff_email); ?></p>
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

            <!-- Rules and Regulations Section -->
            <div class="row mb-4 mt-5">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5><i class="bi bi-clipboard-check"></i> SPC Rules & Regulations</h5>
                        </div>
                        <div class="card-body" style="height: 300px; overflow-y: auto;">
                            <ol>
                                <li>All manufacturing plants must maintain GMP (Good Manufacturing Practice) certification</li>
                                <li>Regular quality control inspections are mandatory</li>
                                <li>Drug prices must align with SPC regulated rates</li>
                                <li>Proper documentation of manufacturing processes required</li>
                                <li>Environmental safety protocols must be followed</li>
                                <li>All drug batches must undergo quality testing</li>
                                <li>Proper waste disposal procedures must be maintained</li>
                                <li>Regular staff training documentation required</li>
                                <li>Emergency protocols must be clearly displayed</li>
                                <li>Monthly compliance reports submission mandatory</li>
                            </ol>
                        </div>
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
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5><i class="bi bi-building"></i> Manufacturing Plant Statistics</h5>
                        </div>
                        <div class="card-body" style="height: 300px">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="border rounded p-3 text-center">
                                        <h6><i class="bi bi-box-seam"></i> Total Products</h6>
                                        <h3><?php echo $drugs_result->num_rows; ?></h3>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="border rounded p-3 text-center">
                                        <h6><i class="bi bi-cart"></i> Pending Orders</h6>
                                        <h3><?php echo $pending_count; ?></h3>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="border rounded p-3 text-center">
                                        <h6><i class="bi bi-exclamation-triangle"></i> Low Stock Items</h6>
                                        <h3><?php echo $low_stock_count; ?></h3>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="border rounded p-3 text-center">
                                        <h6><i class="bi bi-check-circle"></i> Completed Orders</h6>
                                        <h3><?php echo $closed_count; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
            <nav class="navbar navbar-dark bg-primary">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h1">
                    <i class="bi bi-building"></i>Drug Details
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
                <div class="row mb-4 mt-3">
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
                                <option value="SCP">SPC</option>
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
            <nav class="navbar navbar-dark bg-primary">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h1">
                    <i class="bi bi-building"></i>Placed Orders
                </span>
            </div>
        </nav>
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

                <div class="row mb-4 mt-3">
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
                            $orders_sql = "SELECT * FROM orders WHERE ordering_source = 'SPC' ORDER BY order_date DESC";
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
                                            <input type='hidden' name='closed_order_id' value='" . $order['id'] . "'>
                                            <button type='submit' name='closed_order' class='btn btn-danger btn-sm'>Close</button>
                                        </form>";
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

        <div id="profile" class="section" style="display: none;">
    <div class="container mt-4">
    <nav class="navbar navbar-dark bg-primary">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h1">
                    <i class="bi bi-building"></i>Profile
                </span>
            </div>
        </nav>

        <?php if (isset($_SESSION['success_message5'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success_message5']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message5']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <form method="POST" action="update_plant_profile.php">
            <div class="row mb-3 mt-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="mobile" class="form-label">Mobile Number</label>
                    <input type="text" class="form-control" id="mobile" name="mobile" value="<?php echo htmlspecialchars($user['mobile']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="reg_number" class="form-label">Registration Number</label>
                    <input type="text" class="form-control" id="reg_number" name="reg_number" value="<?php echo htmlspecialchars($user['reg_number']); ?>" readonly>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="province" class="form-label">Province</label>
                    <input type="text" class="form-control" id="province" name="province" value="<?php echo htmlspecialchars($user['province']); ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label for="district" class="form-label">District</label>
                    <input type="text" class="form-control" id="district" name="district" value="<?php echo htmlspecialchars($user['district']); ?>" readonly>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="latitude" class="form-label">Latitude</label>
                    <input type="text" class="form-control" id="latitude" name="latitude" value="<?php echo htmlspecialchars($user['latitude']); ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label for="longitude" class="form-label">Longitude</label>
                    <input type="text" class="form-control" id="longitude" name="longitude" value="<?php echo htmlspecialchars($user['longitude']); ?>" readonly>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </div>
</div>
    </div>
    
    <script>
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.style.display = 'none';
            });

            // Show the selected section
            document.getElementById(sectionId).style.display = 'block';
        }

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
        document.addEventListener("DOMContentLoaded", function () {
            // Check if the target section is set in the session
            <?php if (isset($_SESSION['target_section'])): ?>
                const targetSection = "<?php echo $_SESSION['target_section']; ?>";
                showSection(targetSection); // Navigate to the target section
                <?php unset($_SESSION['target_section']); // Clear the session variable ?>
            <?php endif; ?>
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
    </script>

</body>
</html>