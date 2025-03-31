<?php
session_start();

// Check if user is logged in and is a pharmacy user
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'pharmacy') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['index']) && isset($_GET['quantity'])) {
    $index = $_GET['index'];
    $quantity = (int)$_GET['quantity'];
                
    // Validate index exists in cart
    if (isset($_SESSION['cart'][$index])) {
        // Ensure quantity is at least 1
        $quantity = max(1, $quantity);
                    
        // Update quantity and recalculate total price
        $_SESSION['cart'][$index]['quantity'] = $quantity;
         $_SESSION['cart'][$index]['total_price'] = 
        $_SESSION['cart'][$index]['unit_price'] * $quantity;
    }
}

// Redirect back to cart page
header("Location: view_cart.php");
exit();
