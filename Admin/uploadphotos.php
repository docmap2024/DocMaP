<?php
// Include the database connection
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photos'])) {
    $uploadsDir = '../assets/School_Images/'; // Updated directory path
    $response = ['success' => true, 'files' => []];

    // Check if directory exists, if not create it
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }

    foreach ($_FILES['photos']['tmp_name'] as $key => $tmpName) {
        $originalName = basename($_FILES['photos']['name'][$key]);
        $randomNumber = rand(100, 999); // Generate a 3-digit random number
        $photoName = $randomNumber . '_' . $originalName; // Append random number to the file name
        $mimeType = mime_content_type($tmpName);
        $fileSize = $_FILES['photos']['size'][$key];
        $filePath = $uploadsDir . $photoName;

        // Validate file
        if ($fileSize > 5000000) { // Limit file size to 5MB
            $response['success'] = false;
            $response['message'] = 'File size exceeds 5MB.';
            echo json_encode($response);
            exit;
        }

        // Move uploaded file
        if (move_uploaded_file($tmpName, $filePath)) {
            // Insert into database
            $stmt = $conn->prepare("INSERT INTO school_photos (photo_name, mime_type, file_size, file_path, uploaded_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssis", $photoName, $mimeType, $fileSize, $filePath);

            if ($stmt->execute()) {
                $response['files'][] = ['name' => $photoName, 'path' => $filePath];
            } else {
                $response['success'] = false;
                $response['message'] = 'Database insertion failed: ' . $stmt->error;
                echo json_encode($response);
                exit;
            }

            $stmt->close();
        } else {
            $response['success'] = false;
            $response['message'] = 'Failed to upload file.';
            echo json_encode($response);
            exit;
        }
    }

    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

$conn->close();
?>
