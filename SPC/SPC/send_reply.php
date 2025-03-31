<?php
session_start();
include 'db.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $recipient_email = $_POST['recipient_email'];
    $reply_message = $_POST['reply_message'];
    $inquiry_id = $_POST['inquiry_id']; // Get the inquiry ID

    // Validate input
    if (empty($recipient_email) || empty($reply_message) || empty($inquiry_id)) {
        $_SESSION['alert'] = "All fields are required.";
        header("Location: admin_home.php");
        exit();
    }

    // Send email
    $subject = "Reply to Your Inquiry";
    $headers = "From: admin@yourdomain.com\r\nReply-To: admin@yourdomain.com\r\nX-Mailer: PHP/" . phpversion();

    if (mail($recipient_email, $subject, $reply_message, $headers)) {
        // Update inquiry status to "Replied"
        $stmt = $conn->prepare("UPDATE inquiries SET status = 'Replied' WHERE id = ?");
        $stmt->bind_param("i", $inquiry_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['alert'] = "Reply sent successfully!";
    } else {
        $_SESSION['alert'] = "Failed to send reply. Please try again.";
    }
    
    $_SESSION['target_section'] = "inquiries";
    header("Location: admin_home.php");
    exit();
}
?>