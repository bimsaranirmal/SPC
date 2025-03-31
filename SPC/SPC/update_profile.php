<?php
// filepath: c:\xampp\htdocs\SPC\update_profile.php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'supplier') {
    header("Location: login.php");
    exit();
}

require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $name = $_POST['name'];
    $mobile = $_POST['mobile'];
    $company_name = $_POST['company_name'];
    $address = $_POST['address'];

    $sql = "UPDATE users SET name = ?, mobile = ?, company_name = ?, address = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $name, $mobile, $company_name, $address, $user_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Profile updated successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update profile. Please try again.";
    }

    $stmt->close();
    $conn->close();
    
    $_SESSION['target_section'] = "profile";
    header("Location: supplier_home.php");
    exit();
}
?>