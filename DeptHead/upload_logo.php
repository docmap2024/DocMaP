<?php
session_start();
require 'connection.php'; // Ensure this path is correct

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// Ensure the upload directory exists
$upload_dir = '../img/Logo/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Check if a file was uploaded
if (isset($_FILES['logoFile']) && $_FILES['logoFile']['error'] == UPLOAD_ERR_OK) { // Change 'file' to 'logoFile'
    $file_tmp = $_FILES['logoFile']['tmp_name']; // Change 'file' to 'logoFile'
    $file_ext = strtolower(pathinfo($_FILES['logoFile']['name'], PATHINFO_EXTENSION)); // Change 'file' to 'logoFile'
    
    // Sanitize and validate file extension (e.g., only allow jpg and png)
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_ext, $allowed_extensions)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type']);
        exit;
    }

    $unique_filename = uniqid() . '.' . $file_ext;
    $file_path = $upload_dir . $unique_filename;

    // Move the uploaded file to the desired directory
    if (move_uploaded_file($file_tmp, $file_path)) {
        // Update the user's logo in the database
        $stmt = $conn->prepare("UPDATE school_details SET Logo = ? WHERE school_details_ID = 1");
        $stmt->bind_param("s", $unique_filename);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Logo updated successfully', 'filename' => $unique_filename]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error']);
}

$conn->close();
?>
