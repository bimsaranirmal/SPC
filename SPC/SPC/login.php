<?php
session_start();
include 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if ($email === "admin@gmail.com" && $password === "admin123") {
        $_SESSION['user_type'] = "admin";
        header("Location: admin_home.php");
        exit();
    }

    $sql = "SELECT id, name,email, password, user_type, status FROM users WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $name,$db_email, $hashed_password, $user_type, $status);

    if ($stmt->num_rows > 0) {
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            if ($status === "approved") {
                $_SESSION['user_id'] = $id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $db_email;
                $_SESSION['user_type'] = $user_type;

                switch ($user_type) {
                    case "warehouse_staff":
                        header("Location: warehouse_home.php");
                        break;
                    case "manufacturing_plant_staff":
                        header("Location: plant_home.php");
                        break;
                    case "pharmacy":
                        header("Location: pharmacy_home.php");
                        break;
                    case "supplier":
                        header("Location: supplier_home.php");
                        break;
                    default:
                        header("Location: index.php");
                }
                exit();
            } else {
                $error = "Your account is not approved yet.";
            }
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No user found.";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Pharmaceutical Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #0d6efd;
            --primary-dark: #0b5ed7;
            --secondary-color: #f8f9fa;
            --text-color: #212529;
            --light-gray: #e9ecef;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            max-width: 960px;
            width: 100%;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .login-header {
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            padding: 25px 20px;
            border-radius: 15px 15px 0 0;
        }
        
        .login-header h2 {
            margin: 0;
            font-weight: 600;
        }
        
        .login-header p {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .input-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .input-group-text {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px 0 0 10px;
        }
        
        .form-control {
            border-radius: 0 10px 10px 0;
            height: 50px;
            border: 1px solid var(--light-gray);
            padding-left: 15px;
        }
        
        .form-control:focus {
            box-shadow: none;
            border-color: var(--primary-color);
        }
        
        .btn-login {
            height: 50px;
            background-color: var(--primary-color);
            border: none;
            border-radius: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            transition: all 0.3s;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }
        
        .login-footer {
            text-align: center;
            padding: 0 30px 30px;
        }
        
        .register-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link:hover {
            text-decoration: underline;
        }
        
        .logo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .logo i {
            font-size: 40px;
            color: var(--primary-color);
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
        }
        
        /* Modal customization */
        .modal-error .modal-header {
            background-color: #dc3545;
            color: white;
            border-bottom: none;
        }
        
        .modal-error .modal-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }
        
        .modal-error .modal-message {
            font-size: 1.1rem;
            margin-bottom: 0;
        }
        
        .modal-error .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .modal-error .modal-footer {
            border-top: none;
            padding-top: 0;
        }
        
        .modal-error .btn-close-modal {
            background-color: #dc3545;
            color: white;
            padding: 0.5rem 2rem;
            font-weight: 500;
        }
        
        .modal-error .btn-close-modal:hover {
            background-color: #bb2d3b;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="login-container">
                    <div class="login-header">
                        <div class="logo-container">
                            <div class="logo">
                                <i class="bi bi-capsule"></i>
                            </div>
                        </div>
                        <h2>Welcome Back</h2>
                        <p>Please sign in to your account</p>
                    </div>
                    
                    <div class="login-body">
                        <form method="POST" id="loginForm">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                                <input type="email" class="form-control" name="email" id="email" placeholder="Email Address" required>
                            </div>
                            
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                                <button type="button" class="password-toggle" id="togglePassword">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-login">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                            </button>
                        </form>
                    </div>
                    
                    <div class="login-footer">
                        <p>Don't have an account? <a href="register.php" class="register-link">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade modal-error" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="errorModalLabel">Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-exclamation-circle-fill modal-icon"></i>
                    <p class="modal-message" id="errorModalMessage"></p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password visibility toggle
            const togglePassword = document.getElementById('togglePassword');
            const password = document.getElementById('password');
            
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.querySelector('i').classList.toggle('bi-eye');
                this.querySelector('i').classList.toggle('bi-eye-slash');
            });
            
            // Handle form submission with validation
            const loginForm = document.getElementById('loginForm');
            
            loginForm.addEventListener('submit', function(event) {
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;
                
                // Basic validation
                if (!email || !password) {
                    event.preventDefault();
                    showErrorModal('Please enter both email and password');
                    return false;
                }
                
                // Email format validation
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(email)) {
                    event.preventDefault();
                    showErrorModal('Please enter a valid email address');
                    return false;
                }
            });
            
            // Function to show error in modal
            function showErrorModal(message) {
                document.getElementById('errorModalMessage').textContent = message;
                const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                errorModal.show();
            }
            
            // Show PHP error messages in modal if they exist
            <?php if (!empty($error)): ?>
                showErrorModal(<?php echo json_encode($error); ?>);
            <?php endif; ?>
        });
    </script>
</body>
</html>