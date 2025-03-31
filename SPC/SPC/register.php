<?php
include 'db.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $mobile = $_POST['mobile'];
    $reg_number = $_POST['reg_number'];
    $user_type = $_POST['user_type'];
    $address = $_POST['address'];
    $province = $_POST['province'];
    $district = $_POST['district'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    
    $extra_fields = '';
    $extra_values = '';

    if ($user_type == 'warehouse_staff' || $user_type == 'manufacturing_plant_staff') {
        $department = $_POST['department'];
        $extra_fields = ", department";
        $extra_values = ", '$department'";
    } elseif ($user_type == 'pharmacy') {
        $license_number = $_POST['license_number'];
        $extra_fields = ", license_number";
        $extra_values = ", '$license_number'";
    } elseif ($user_type == 'supplier') {
        $company_name = $_POST['company_name'];
        $business_reg_number = $_POST['business_reg_number'];
        $extra_fields = ", company_name, business_reg_number";
        $extra_values = ", '$company_name', '$business_reg_number'";
    }

    $sql = "INSERT INTO users (name, email, password, mobile, reg_number, user_type, address, province, district, latitude, longitude $extra_fields) 
            VALUES ('$name', '$email', '$password', '$mobile', '$reg_number', '$user_type', '$address', '$province', '$district', '$latitude', '$longitude' $extra_values)";
    
    try {
        if (mysqli_query($conn, $sql)) {
            $success_message = "Registration successful! Your account has been created.";
        } else {
            throw new Exception(mysqli_error($conn));
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <!-- Leaflet Control Geocoder CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
            padding-bottom: 40px;
        }
        .registration-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            color: #0d6efd;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 15px;
        }
        .form-section {
            margin-bottom: 25px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .section-title {
            margin-bottom: 15px;
            color: #0d6efd;
            font-weight: 600;
        }
        #map {
            height: 400px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        .input-with-icon {
            position: relative;
        }
        .input-with-icon i {
            position: absolute;
            top: 10px;
            left: 10px;
            color: #6c757d;
        }
        .input-with-icon input,
        .input-with-icon select {
            padding-left: 35px;
        }
        .btn-register {
            background-color: #0d6efd;
            color: white;
            padding: 10px 30px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .btn-register:hover {
            background-color: #0b5ed7;
            color: white;
        }
        .alert {
            margin-bottom: 20px;
        }
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
        /* Modal styling */
        .modal-header.success {
            background-color: #198754;
            color: white;
        }
        .modal-header.error {
            background-color: #dc3545;
            color: white;
        }
        .modal-body i.success {
            color: #198754;
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .modal-body i.error {
            color: #dc3545;
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .modal-body {
            text-align: center;
            padding: 30px 20px;
        }
        .modal-footer {
            border-top: none;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="container registration-container">
        <div class="form-header">
            <h2><i class="bi bi-person-plus-fill me-2"></i>User Registration</h2>
            <p class="text-muted">Please fill in the registration form below</p>
        </div>

        <form method="POST" action="register.php" id="registrationForm">
            <div class="form-section">
                <h4 class="section-title"><i class="bi bi-person-vcard me-2"></i>Basic Information</h4>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label required-field">Full Name</label>
                        <div class="input-with-icon">
                            <i class="bi bi-person-fill"></i>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label required-field">Email Address</label>
                        <div class="input-with-icon">
                            <i class="bi bi-envelope-fill"></i>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="password" class="form-label required-field">Password</label>
                        <div class="input-with-icon">
                            <i class="bi bi-lock-fill"></i>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label required-field">Confirm Password</label>
                        <div class="input-with-icon">
                            <i class="bi bi-lock-fill"></i>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="mobile" class="form-label required-field">Mobile Number</label>
                        <div class="input-with-icon">
                            <i class="bi bi-telephone-fill"></i>
                            <input type="text" class="form-control" id="mobile" name="mobile" required length="10" pattern="\d{10}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="reg_number" class="form-label required-field">Registration Number</label>
                        <div class="input-with-icon">
                            <i class="bi bi-card-text"></i>
                            <input type="text" class="form-control" id="reg_number" name="reg_number" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h4 class="section-title"><i class="bi bi-building me-2"></i>User Role</h4>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="user_type" class="form-label required-field">User Type</label>
                        <div class="input-with-icon">
                            <i class="bi bi-person-badge"></i>
                            <select class="form-select" id="user_type" name="user_type" onchange="showFields()" required>
                                <option value="">Select User Type</option>
                                <option value="warehouse_staff">Warehouse Staff</option>
                                <option value="manufacturing_plant_staff">Manufacturing Plant Staff</option>
                                <option value="pharmacy">Pharmacy</option>
                                <option value="supplier">Supplier</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Warehouse/Manufacturing Plant Staff Fields -->
                <div id="warehouse_fields" class="row mb-3" style="display: none;">
                    <div class="col-md-12">
                        <label for="department" class="form-label required-field">Department</label>
                        <div class="input-with-icon">
                            <i class="bi bi-diagram-3"></i>
                            <input type="text" class="form-control" id="department" name="department">
                        </div>
                    </div>
                </div>

                <!-- Pharmacy Fields -->
                <div id="pharmacy_fields" class="row mb-3" style="display: none;">
                    <div class="col-md-12">
                        <label for="license_number" class="form-label required-field">License Number</label>
                        <div class="input-with-icon">
                            <i class="bi bi-file-earmark-text"></i>
                            <input type="text" class="form-control" id="license_number" name="license_number">
                        </div>
                    </div>
                </div>

                <!-- Supplier Fields -->
                <div id="supplier_fields" class="row mb-3" style="display: none;">
                    <div class="col-md-6">
                        <label for="company_name" class="form-label required-field">Company Name</label>
                        <div class="input-with-icon">
                            <i class="bi bi-building"></i>
                            <input type="text" class="form-control" id="company_name" name="company_name">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="business_reg_number" class="form-label required-field">Business Registration Number</label>
                        <div class="input-with-icon">
                            <i class="bi bi-file-earmark-text"></i>
                            <input type="text" class="form-control" id="business_reg_number" name="business_reg_number">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h4 class="section-title"><i class="bi bi-geo-alt-fill me-2"></i>Location Information</h4>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="address" class="form-label required-field">Address</label>
                        <div class="input-with-icon">
                            <i class="bi bi-house-door-fill"></i>
                            <input type="text" class="form-control" id="address" name="address" required>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="province" class="form-label required-field">Province</label>
                        <div class="input-with-icon">
                            <i class="bi bi-map-fill"></i>
                            <select class="form-select" id="province" name="province" onchange="updateDistricts()" required>
                                <option value="">Select Province</option>
                                <option value="Western">Western</option>
                                <option value="Central">Central</option>
                                <option value="Southern">Southern</option>
                                <option value="Northern">Northern</option>
                                <option value="Eastern">Eastern</option>
                                <option value="North Western">North Western</option>
                                <option value="North Central">North Central</option>
                                <option value="Uva">Uva</option>
                                <option value="Sabaragamuwa">Sabaragamuwa</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="district" class="form-label required-field">District</label>
                        <div class="input-with-icon">
                            <i class="bi bi-pin-map-fill"></i>
                            <select class="form-select" id="district" name="district" required>
                                <option value="">Select District</option>
                                <!-- Districts will be populated by JavaScript -->
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label required-field">Map Location (Click to set your location)</label>
                        <div id="map"></div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-with-icon">
                                    <i class="bi bi-geo"></i>
                                    <input type="text" class="form-control" id="latitude" name="latitude" placeholder="Latitude" readonly required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-with-icon">
                                    <i class="bi bi-geo"></i>
                                    <input type="text" class="form-control" id="longitude" name="longitude" placeholder="Longitude" readonly required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="row">
                    <div class="col text-center">
                        <button type="submit" class="btn btn-register">
                            <i class="bi bi-check-circle-fill me-2"></i>Register
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Modal for messages -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" id="modalHeader">
                    <h5 class="modal-title" id="modalTitle">Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <i class="bi" id="modalIcon"></i>
                    <p id="modalMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" id="modalButton" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <!-- Leaflet Control Geocoder JS -->
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <!-- Custom JavaScript -->
    <script>
        // Show message modal function
        // Show message modal function
        function showModal(type, title, message) {
            const modalHeader = document.getElementById('modalHeader');
            const modalTitle = document.getElementById('modalTitle');
            const modalIcon = document.getElementById('modalIcon');
            const modalMessage = document.getElementById('modalMessage');
            const modalButton = document.getElementById('modalButton');
            
            // Set modal content based on message type
            if (type === 'success') {
                modalHeader.className = 'modal-header success';
                modalTitle.textContent = title || 'Success';
                modalIcon.className = 'bi bi-check-circle-fill success d-block mb-3';
                modalButton.className = 'btn btn-success';
            } else {
                modalHeader.className = 'modal-header error';
                modalTitle.textContent = title || 'Error';
                modalIcon.className = 'bi bi-exclamation-triangle-fill error d-block mb-3';
                modalButton.className = 'btn btn-danger';
            }
            
            modalMessage.textContent = message;
            
            // Show the modal
            const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
            messageModal.show();

            // Redirect to login.php after success modal is closed
            if (type === 'success') {
                const modalElement = document.getElementById('messageModal');
                modalElement.addEventListener('hidden.bs.modal', function () {
                    window.location.href = 'login.php';
                });
            }
        }

        // Initialize dynamic form fields when user type changes
        function showFields() {
            const userType = document.getElementById('user_type').value;
            const warehouseFields = document.getElementById('warehouse_fields');
            const pharmacyFields = document.getElementById('pharmacy_fields');
            const supplierFields = document.getElementById('supplier_fields');
            
            // Remove required attribute from all special fields
            document.getElementById('department')?.removeAttribute('required');
            document.getElementById('license_number')?.removeAttribute('required');
            document.getElementById('company_name')?.removeAttribute('required');
            document.getElementById('business_reg_number')?.removeAttribute('required');
            
            // Hide all fields first
            warehouseFields.style.display = 'none';
            pharmacyFields.style.display = 'none';
            supplierFields.style.display = 'none';
            
            // Show fields based on user type
            if (userType === 'warehouse_staff' || userType === 'manufacturing_plant_staff') {
                warehouseFields.style.display = 'flex';
                document.getElementById('department').setAttribute('required', 'required');
            } else if (userType === 'pharmacy') {
                pharmacyFields.style.display = 'flex';
                document.getElementById('license_number').setAttribute('required', 'required');
            } else if (userType === 'supplier') {
                supplierFields.style.display = 'flex';
                document.getElementById('company_name').setAttribute('required', 'required');
                document.getElementById('business_reg_number').setAttribute('required', 'required');
            }
        }

        // Update districts dropdown based on selected province
        function updateDistricts() {
            const province = document.getElementById('province').value;
            const districtSelect = document.getElementById('district');
            
            // Clear current options
            districtSelect.innerHTML = "<option value=''>Select District</option>";
            
            // Define districts for each province
            const districts = {
                "Western": ["Colombo", "Gampaha", "Kalutara"],
                "Central": ["Kandy", "Matale", "Nuwara Eliya"],
                "Southern": ["Galle", "Matara", "Hambantota"],
                "Northern": ["Jaffna", "Kilinochchi", "Mannar", "Mullaitivu", "Vavuniya"],
                "Eastern": ["Trincomalee", "Batticaloa", "Ampara"],
                "North Western": ["Kurunegala", "Puttalam"],
                "North Central": ["Anuradhapura", "Polonnaruwa"],
                "Uva": ["Badulla", "Monaragala"],
                "Sabaragamuwa": ["Ratnapura", "Kegalle"]
            };
            
            // Populate districts for selected province
            if (districts[province]) {
                districts[province].forEach(function(district) {
                    const option = document.createElement('option');
                    option.value = district;
                    option.textContent = district;
                    districtSelect.appendChild(option);
                });
            }
        }

        // Initialize map
        function initMap() {
            // Center of Sri Lanka
            const sriLankaCenter = [7.8731, 80.7718];
            
            // Create map instance
            const map = L.map('map').setView(sriLankaCenter, 8);
            
            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);
            
            // Add search control
            const geocoder = L.Control.Geocoder.nominatim();
            const searchControl = L.Control.geocoder({
                geocoder: geocoder,
                defaultMarkGeocode: false,
                position: 'topleft',
                placeholder: 'Search for a location...'
            }).addTo(map);
            
            // Handle search results
            searchControl.on('markgeocode', function(e) {
                if (marker) {
                    map.removeLayer(marker);
                }
                const latlng = e.geocode.center;
                marker = L.marker(latlng).addTo(map);
                map.setView(latlng, 16);
                
                // Update form fields
                document.getElementById('latitude').value = latlng.lat.toFixed(6);
                document.getElementById('longitude').value = latlng.lng.toFixed(6);
            });
            
            // Variable to hold the marker
            let marker;
            
            // Add marker on map click
            map.on('click', function(e) {
                if (marker) {
                    map.removeLayer(marker);
                }
                marker = L.marker(e.latlng).addTo(map);
                
                // Update form fields
                document.getElementById('latitude').value = e.latlng.lat.toFixed(6);
                document.getElementById('longitude').value = e.latlng.lng.toFixed(6);
            });
        }

        // Form validation
        document.getElementById('registrationForm').addEventListener('submit', function(event) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                event.preventDefault();
                showModal('error', 'Password Error', 'Passwords do not match! Please ensure both passwords are identical.');
                return false;
            }
            
            const userType = document.getElementById('user_type').value;
            
            if (userType === '') {
                event.preventDefault();
                showModal('error', 'Selection Error', 'Please select a user type from the dropdown menu.');
                return false;
            }
            
            const latitude = document.getElementById('latitude').value;
            const longitude = document.getElementById('longitude').value;
            
            if (!latitude || !longitude) {
                event.preventDefault();
                showModal('error', 'Location Error', 'Please select a location on the map by clicking where your address is located.');
                return false;
            }
        });

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize map when DOM is loaded
            initMap();
            
            // Show fields for initial user type value
            showFields();
            
            // Update districts for initial province value
            updateDistricts();
            
            // Show success/error message from PHP if available
            <?php if (isset($success_message)): ?>
                showModal('success', 'Registration Successful', '<?= $success_message ?>');
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                showModal('error', 'Registration Failed', <?= json_encode($error_message) ?>);
            <?php endif; ?>
        });
    </script>
</body>
</html>