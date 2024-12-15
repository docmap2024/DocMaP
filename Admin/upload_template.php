<?php
session_start();
include 'connection.php';

$response = ['status' => 'error', 'message' => 'Something went wrong'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['template_file'])) {
    $userId = $_SESSION['user_id']; // Assume logged-in UserID
    $name = mysqli_real_escape_string($conn, $_POST['template_name']);
    $file = $_FILES['template_file'];
    $mimetype = $file['type'];
    $size = $file['size'];

    // Create the 'Templates' folder if it doesn't exist
    $uploadDir = 'Templates/'; // Adjust path if needed
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755); // Create the folder with appropriate permissions
    }

    // Generate a unique filename (optional)
    $newFileName = uniqid() . '_' . basename($file['name']); 
    $uri = $uploadDir . $newFileName;

    $created_at = date('Y-m-d H:i:s');

    if (move_uploaded_file($file['tmp_name'], $uri)) {
        $query = "INSERT INTO `templates`( `UserID`, `name`, `filename`, `mimetype`, `size`, `uri`, `created_at`) VALUES ('$userId', '$name', '$newFileName', '$mimetype', '$size', '$uri', '$created_at')";
        if (mysqli_query($conn, $query)) {
            $response = ['status' => 'success', 'message' => 'Template uploaded successfully'];
        } else {
            $response['message'] = 'Database insertion failed';
        }
    } else {
        $response['message'] = 'File upload failed';
    }
}

echo json_encode($response);
?>