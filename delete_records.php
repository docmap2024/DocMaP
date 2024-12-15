<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}
include 'connection.php'; // Include your database connection file

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the Performance_ID from the POST request
    $inputData = json_decode(file_get_contents('php://input'), true);
    $performanceId = $inputData['Performance_ID'] ?? null;

    if ($performanceId) {
        try {
            // Start a transaction
            $conn->begin_transaction();

            // Delete the Performance_Indicator record and all associated child records via cascading
            $query = "DELETE FROM Performance_Indicator WHERE Performance_ID = ?";
            $stmt = $conn->prepare($query);

            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }

            $stmt->bind_param("i", $performanceId);
            $stmt->execute();

            // Commit the transaction
            $conn->commit();

            echo json_encode(["status" => "success", "message" => "Record deleted successfully."]);
        } catch (Exception $e) {
            // Roll back the transaction on error
            $conn->rollback();
            echo json_encode(["status" => "error", "message" => "Failed to delete record: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid Performance_ID provided."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}


?>