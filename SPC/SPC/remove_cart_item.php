<?php
            session_start();

            // Check if user is logged in and is a pharmacy user
            if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'pharmacy') {
                header("Location: login.php");
                exit();
            }

            // Check if index is provided
            if (isset($_GET['index']) && isset($_SESSION['cart'])) {
                $index = $_GET['index'];
                
                // Check if the index exists in the cart array
                if (isset($_SESSION['cart'][$index])) {
                    // Remove the item from the cart
                    unset($_SESSION['cart'][$index]);
                    // Reindex the array
                    $_SESSION['cart'] = array_values($_SESSION['cart']);
                }
            }

            // Redirect back to cart page
            header("Location: view_cart.php");
            exit();
