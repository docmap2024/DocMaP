<?php
// Database connection
include 'connection.php';

// Set log file path to /tmp with a unique name
$log_file = '/tmp/logfile.log';

// Custom logging function with comprehensive error handling
function write_log($message) {
    global $log_file;
    
    // Create log entry with timestamp
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    
    try {
        // Attempt to write to log file
        if (file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX) === false) {
            // If writing fails, try creating the log file first
            file_put_contents($log_file, '');
            chmod($log_file, 0666); // Make it world-writable
            file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        }
    } catch (Exception $e) {
        // Ultimate fallback to system error log
        error_log("LOG FAILURE: Could not write to $log_file - " . $e->getMessage());
        error_log("Original message: $log_entry");
    }
}

// Handle AJAX request for updating a task
if (isset($_POST['update_task_id'])) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    $taskID = $_POST['update_task_id'];

    // Log form data
    write_log("Received form data: TaskID = $taskID, ContentIDs = " . 
        (isset($_POST['grade']) ? implode(',', $_POST['grade']) : 'None') . 
        ", Title = " . $_POST['update_title'] . 
        ", DueDate = " . $_POST['update_due_date'] . 
        ", DueTime = " . $_POST['update_due_time']);

    // Check if grades were submitted
    $contentIDs = isset($_POST['grade']) && !empty($_POST['grade']) ? implode(',', $_POST['grade']) : null;

    $title = $_POST['update_title'];
    $dueDate = $_POST['update_due_date'];
    $dueTime = $_POST['update_due_time'];
    $taskContent = $_POST['update_instructions'];
    $actionType = $_POST['actionType'];

    $scheduleDate = isset($_POST['update_schedule_date']) ? $_POST['update_schedule_date'] : null;
    $scheduleTime = isset($_POST['update_schedule_time']) ? $_POST['update_schedule_time'] : null;

    // Log the schedule date and time
    write_log("Schedule Date: " . ($scheduleDate ?: 'Not provided'));
    write_log("Schedule Time: " . ($scheduleTime ?: 'Not provided'));

    // Log the update request details
    write_log("Update Task - TaskID: $taskID, ContentIDs: " . ($contentIDs ?: 'None') . 
        ", Title: $title, DueDate: $dueDate, DueTime: $dueTime, " .
        "ActionType: $actionType, Schedule: " . 
        ($scheduleDate ? "$scheduleDate $scheduleTime" : 'Not scheduled'));

    // Prepare base SQL for updating tasks
    $sql = "UPDATE tasks SET Title = ?, taskContent = ?, DueDate = ?, DueTime = ?, Status = ?";

    // Append SQL for scheduled tasks
    if ($actionType == 'Schedule' && $scheduleDate && $scheduleTime) {
        $sql .= ", Schedule_Date = ?, Schedule_Time = ?";
    }

    // Append SQL for contentID if provided
    if ($contentIDs !== null) {
        $sql .= ", ContentID = ?";
    }

    $sql .= " WHERE TaskID = ?";

    // Prepare statement
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        write_log("SQL Prepare Error: " . $conn->error);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }

    // Bind parameters based on action type and contentID
    $bindSuccess = false;
    if ($actionType == 'Schedule' && $scheduleDate && $scheduleTime) {
        $status = 'Schedule';
        if ($contentIDs !== null) {
            $bindSuccess = $stmt->bind_param('sssssssi', $title, $taskContent, $dueDate, $dueTime, 
                $status, $scheduleDate, $scheduleTime, $contentIDs, $taskID);
        } else {
            $bindSuccess = $stmt->bind_param('ssssssi', $title, $taskContent, $dueDate, $dueTime, 
                $status, $scheduleDate, $scheduleTime, $taskID);
        }
    } else {
        $status = ($actionType == 'Assign') ? 'Assign' : 'Draft';
        if ($contentIDs !== null) {
            $bindSuccess = $stmt->bind_param('ssssssi', $title, $taskContent, $dueDate, $dueTime, 
                $status, $contentIDs, $taskID);
        } else {
            $bindSuccess = $stmt->bind_param('sssssi', $title, $taskContent, $dueDate, $dueTime, 
                $status, $taskID);
        }
    }

    if (!$bindSuccess) {
        write_log("Bind Param Error: " . $stmt->error);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }

    // Execute and handle the response
    $response = ['success' => false, 'message' => 'Unknown error'];
    if ($stmt->execute()) {
        $response = [
            'success' => true,
            'message' => 'Task updated successfully!'
        ];
        write_log("Task update successful - TaskID: $taskID");

        // Update task_department if contentID is not provided
        if ($contentIDs === null && isset($_POST['department']) && !empty($_POST['department'])) {
            $departments = $_POST['department'];
            write_log("Updating departments for TaskID: $taskID - " . implode(',', $departments));

            // Delete existing department associations
            $deleteSql = "DELETE FROM task_department WHERE TaskID = ?";
            $deleteStmt = $conn->prepare($deleteSql);
            if ($deleteStmt) {
                $deleteStmt->bind_param('i', $taskID);
                if ($deleteStmt->execute()) {
                    write_log("Deleted existing department associations");
                } else {
                    write_log("Delete departments failed: " . $deleteStmt->error);
                }
                $deleteStmt->close();
            }

            // Insert new department associations
            $insertSql = "INSERT INTO task_department (TaskID, dept_ID) VALUES (?, ?)";
            $insertStmt = $conn->prepare($insertSql);
            if ($insertStmt) {
                foreach ($departments as $deptID) {
                    $insertStmt->bind_param('ii', $taskID, $deptID);
                    if (!$insertStmt->execute()) {
                        write_log("Failed to insert department $deptID: " . $insertStmt->error);
                    }
                }
                $insertStmt->close();
            }
        }
    } else {
        $response = [
            'success' => false,
            'message' => 'Failed to update task.'
        ];
        write_log("Update Task Error: " . $stmt->error);
        write_log("Failed SQL: $sql");
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>