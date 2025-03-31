<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $order_id = $_POST['order_id'];

    // First, delete the tracking entry (to avoid foreign key issues)
    $delete_tracking_sql = "DELETE FROM order_tracking WHERE order_id = ?";
    $stmt1 = $conn->prepare($delete_tracking_sql);
    $stmt1->bind_param("i", $order_id);
    $stmt1->execute();

    // Now delete the order
    $delete_order_sql = "DELETE FROM pharmacy_orders WHERE id = ?";
    $stmt2 = $conn->prepare($delete_order_sql);
    $stmt2->bind_param("i", $order_id);

    if ($stmt2->execute()) {
        $_SESSION['alert'] = 'Order has been canceled successfully.';
        header('Location: view_pharmacy_orders.php');
        exit();
    } else {
        $_SESSION['alert'] = 'Error canceling the order: ' . $stmt2->error;
        header('Location: view_pharmacy_orders.php');
        exit();
    }
}
?>
