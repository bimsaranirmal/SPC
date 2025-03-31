<?php
require 'db.php';

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
} else {
    echo json_encode([]); 
}

$conn->close();
?>