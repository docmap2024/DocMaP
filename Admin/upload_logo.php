<?php
// Add strict error reporting at the very top
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logfile.log');
error_reporting(E_ALL);

session_start();
require 'connection.php';

// Ensure we only return JSON
header('Content-Type: application/json');

// Validate session first
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['status' => 'error', 'message' => 'User not logged in']));
}

// Set upload directory - use absolute path
$upload_dir = __DIR__ . '/img/Logo/';

// Create directory if it doesn't exist
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        http_response_code(500);
        exit(json_encode(['status' => 'error', 'message' => 'Failed to create upload directory']));
    }
}

// Check if directory is writable
if (!is_writable($upload_dir)) {
    http_response_code(500);
    exit(json_encode(['status' => 'error', 'message' => 'Upload directory not writable']));
}

// Check if file was uploaded
if (!isset($_FILES['logoFile']) || $_FILES['logoFile']['error'] !== UPLOAD_ERR_OK) {
    $error_message = 'No file uploaded or upload error';
    if (isset($_FILES['logoFile'])) {
        $error_codes = [
            0 => 'There is no error, the file uploaded with success',
            1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
            3 => 'The uploaded file was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk',
            8 => 'A PHP extension stopped the file upload'
        ];
        $error_message = $error_codes[$_FILES['logoFile']['error']] ?? 'Unknown upload error';
    }
    http_response_code(400);
    exit(json_encode(['status' => 'error', 'message' => $error_message]));
}

// Process the uploaded file
$file = $_FILES['logoFile'];
$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

if (!in_array($file_ext, $allowed_extensions)) {
    http_response_code(400);
    exit(json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPG, PNG, GIF allowed.']));
}

// Generate unique filename
$unique_filename = uniqid() . '.' . $file_ext;
$file_path = $upload_dir . $unique_filename;

// Move the uploaded file
if (!move_uploaded_file($file['tmp_name'], $file_path)) {
    http_response_code(500);
    exit(json_encode(['status' => 'error', 'message' => 'Failed to save uploaded file']));
}

// Update database
try {
    $stmt = $conn->prepare("UPDATE school_details SET Logo = ? WHERE school_details_ID = 1");
    $stmt->bind_param("s", $unique_filename);
    
    if (!$stmt->execute()) {
        // Delete the uploaded file if DB update fails
        unlink($file_path);
        http_response_code(500);
        exit(json_encode(['status' => 'error', 'message' => 'Database update failed: ' . $conn->error]));
    }
    
    $stmt->close();
    
    // Return success response
    echo json_encode([
        'status' => 'success', 
        'message' => 'Logo updated successfully', 
        'filename' => $unique_filename
    ]);
    
} catch (Exception $e) {
    // Clean up file if something went wrong
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    http_response_code(500);
    exit(json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]));
}

$conn->close();
?>