<?php
// Include database connection
include 'connection.php';


// Log file path
$log_file = 'logfile.log';
$logMessage = "Delete request received at " . date('Y-m-d H:i:s') . "\n";

// Check if ID is passed
if (isset($_POST['id'])) {
    $repetitionId = $_POST['id'];
    
    // Log the ID received
    $logMessage .= "Repetition ID received: $repetitionId\n";
    
    // SQL query to delete the record
    $query = "DELETE FROM repetition WHERE Repetition_ID = ?";
    
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param('i', $repetitionId);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $logMessage .= "Successfully deleted record with Repetition ID: $repetitionId\n";
            echo json_encode(['success' => true]);
        } else {
            $logMessage .= "No record found or failed to delete record with Repetition ID: $repetitionId\n";
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
