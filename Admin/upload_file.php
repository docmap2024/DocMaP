<?php
// Define the target directory for the uploaded file
$targetDir = "TeacherData/";
$targetFile = $targetDir . "LNHS-Teachers.xlsx";

// Check if the file was uploaded via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES["file"])) {
    // Get the uploaded file details
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($_FILES["file"]["name"],PATHINFO_EXTENSION));

    // Check if the file is an Excel file
    if ($fileType != "xls" && $fileType != "xlsx") {
        // Send error response via AJAX
        echo json_encode(['status' => 'error', 'message' => 'Only Excel files are allowed.']);
        exit;
    }

    // Check if the file already exists
    if (file_exists($targetFile)) {
        // Delete the existing file
        unlink($targetFile);
    }

    // If the file is valid, move it to the target directory
    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
            // Send success response via AJAX
            echo json_encode(['status' => 'success', 'message' => 'The file has been uploaded.']);
        } else {
            // Send error response via AJAX
            echo json_encode(['status' => 'error', 'message' => 'There was an error uploading your file.']);
        }
    }
}
?>