<?php
session_start();
include 'connection.php'; // Include your database connection file

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Check if a file was uploaded
    if (isset($_FILES['esignatureFile']) && $_FILES['esignatureFile']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'img/e_sig/';
        $fileTmpPath = $_FILES['esignatureFile']['tmp_name'];
        $fileName = $_FILES['esignatureFile']['name'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

        // Generate new file name with 6-digit random number
        $newFileName = sprintf('%06d_%s', random_int(100000, 999999), basename($fileName));
        $newFilePath = $uploadDir . $newFileName;

        // Move the file to the target directory
        if (move_uploaded_file($fileTmpPath, $newFilePath)) {
            // Update the database with the new file name
            $query = "UPDATE useracc SET esig = ? WHERE UserID = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('si', $newFileName, $user_id);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database update failed.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'File upload failed.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No file uploaded or invalid file.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
}
?>
