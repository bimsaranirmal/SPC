<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $mobile = $_POST['mobile'];
    $address = $_POST['address'];
    $user_email = $_SESSION['user_email']; // Assuming the email is stored in the session

    $update_sql = "UPDATE users SET name = ?, mobile = ?, address = ? WHERE email = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssss", $name, $mobile, $address, $user_email);

    if ($stmt->execute()) {
        $_SESSION['success_message5'] = "Profile updated successfully!";
    } else {
        $_SESSION['error_message5'] = "Error updating profile: " . $conn->error;
    }
    
    $_SESSION['target_section'] = "profile";
    header("Location: plant_home.php");
    exit();
}
?>