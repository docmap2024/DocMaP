<?php
// Include database connection
include 'connection.php';


// Log file path
$log_file = 'logfile.log';
$logMessage = "Delete request received at " . date('Y-m-d H:i:s') . "\n";

// Check if ID is passed
if (isset($_POST['id'])) {
    $enrollId = $_POST['id'];
    
    // Log the ID received
    $logMessage .= "Enroll ID received: $enrollId\n";
    
    // SQL query to delete the record
    $query = "DELETE FROM enroll WHERE Enroll_ID = ?";
    
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param('i', $enrollId);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $logMessage .= "Successfully deleted record with Enroll ID: $enrollId\n";
            echo json_encode(['success' => true]);
        } else {
            $logMessage .= "No record found or failed to delete record with Enroll ID: $enrollId\n";
            echo json_encode(['success' => false]);
        }
    } else {
        $logMessage .= "Failed to prepare SQL statement.\n";
        echo json_encode(['success' => false]);
    }
} else {
    $logMessage .= "No ID was provided in the request.\n";
    echo json_encode(['success' => false]);
}

// Append log message to the log file
file_put_contents($log_file, $logMessage, FILE_APPEND);
?>
