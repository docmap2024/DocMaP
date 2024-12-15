<?php
// Database connection
include 'connection.php';

// Check if ID is set in the request
if (isset($_GET['id'])) {
    $photoId = $_GET['id'];

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Prepare the SQL statement to get the photo name
        $sql = "SELECT photo_name FROM school_photos WHERE photo_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $photoId);

        if ($stmt->execute()) {
            $stmt->bind_result($photoName);
            if ($stmt->fetch()) {
                // File path to delete from the filesystem
                $photoPath = '../assets/School_Images/' . $photoName;

                // Check if file exists
                if (file_exists($photoPath)) {
                    // Prepare the SQL DELETE statement
                    $sqlDelete = "DELETE FROM school_photos WHERE photo_id = ?";
                    $stmtDelete = $conn->prepare($sqlDelete);
                    $stmtDelete->bind_param('i', $photoId);

                    if ($stmtDelete->execute() && unlink($photoPath)) {
                        // Commit the transaction
                        $conn->commit();
                        $response = ['success' => true];
                    } else {
                        // Rollback the transaction on failure
                        $conn->rollback();
                        $response = ['success' => false, 'message' => 'Error deleting photo.'];

                        if (!$stmtDelete->execute()) {
                            $response['message'] .= ' Database Error: ' . $stmtDelete->error;
                        }
                        if (!unlink($photoPath)) {
                            $response['message'] .= ' File Deletion Error: Unable to delete file.';
                        }
                    }

                    $stmtDelete->close();
                } else {
                    $conn->rollback();
                    $response = ['success' => false, 'message' => 'File not found on the filesystem.'];
                }
            } else {
                $conn->rollback();
                $response = ['success' => false, 'message' => 'Photo not found.'];
            }
        } else {
            $conn->rollback();
            $response = ['success' => false, 'message' => 'Error fetching photo from database: ' . $stmt->error];
        }

        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        $response = ['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()];
    }

    $conn->close();
} else {
    $response = ['success' => false, 'message' => 'Invalid request.'];
}

// Return JSON response
echo json_encode($response);
?>
