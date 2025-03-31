<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $drug_id = $_POST['id'];
    $quantity = $_POST['quantity'];

    // Fetch drug price from DB
    $sql = "SELECT unit_price FROM drugs WHERE id = '$drug_id'";
    $result = $conn->query($sql);
    $drug = $result->fetch_assoc();
    
    if ($drug) {
        $unit_price = $drug['unit_price'];
        $total_price = $unit_price * $quantity;

        // Store in session (cart)
        $_SESSION['cart'][] = [
            'id' => $drug_id,
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'total_price' => $total_price
        ];
        
        $_SESSION['alert'] = "Drug successfully added to cart!";
        header("Location: pharmacy_home.php");
        exit();
    }
}
?>
