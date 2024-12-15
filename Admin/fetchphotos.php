<?php
// Database connection
include 'connection.php';

$response = ['success' => false, 'files' => []];

// Prepare the SQL SELECT statement
$sql = "SELECT photo_id, photo_name FROM school_photos";
$stmt = $conn->prepare($sql);

if ($stmt->execute()) {
    $stmt->bind_result($photoId, $photoName);
    $files = [];
    while ($stmt->fetch()) {
        $photoPath = '../assets/School_Images/' . $photoName;
        if (file_exists($photoPath)) {
            $files[] = ['id' => $photoId, 'name' => $photoName];
        } else {
            // If the file doesn't exist, continue to the next
            continue;
        }
    }
    $response['success'] = true;
    $response['files'] = $files;
} else {
    $response['message'] = 'Error fetching photos from database.';
}

$stmt->close();
$conn->close();

// Return JSON response
echo json_encode($response);
?>