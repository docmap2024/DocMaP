<?php
session_start();

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    // Unset all of the session variables
    session_unset();

    // Destroy the session
    session_destroy();
}

// Redirect to index.php after logging out
header("Location: ../index.php");
exit();
?>
