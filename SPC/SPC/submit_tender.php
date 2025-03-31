<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $order_id = $_POST['order_id'];
    $order_date = $_POST['order_date'];
    $supplier_email = $_POST['supplier_email'];
    $business_reg_number = $_POST['business_reg_number'];
    $tender_date = $_POST['tender_date'];
    $tender_amount = $_POST['tender_amount'];

    // Handle file upload
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["tender_document"]["name"]);
    if (move_uploaded_file($_FILES["tender_document"]["tmp_name"], $target_file)) {
        $document_path = $target_file;
    } else {
        $_SESSION['error_message'] = "Error uploading document.";
        header("Location: supplier_home.php");
        exit();
    }

    // Insert tender into the database
    $sql = "INSERT INTO tenders (order_id, order_date, supplier_email, business_reg_number, tender_date, tender_amount, document_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssds", $order_id, $order_date, $supplier_email, $business_reg_number, $tender_date, $tender_amount, $document_path);

    if ($stmt->execute()) {
        $_SESSION['success_message3'] = "Tender submitted successfully!";
    } else {
        $_SESSION['error_message3'] = "Error submitting tender: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
    $_SESSION['target_section'] = "orders";
    header("Location: supplier_home.php");
    exit();
}
?>