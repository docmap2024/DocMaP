<?php
// Database connection
include 'connection.php';

// Set log file path to /tmp which is always writable
$log_file = '/tmp/update_announcement.log';

// Custom logging function with error handling
function write_log($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    
    // Write to log file
    if (file_put_contents($log_file, $log_entry, FILE_APPEND) === false) {
        // Fallback to system error log if file writing fails
        error_log("Failed to write to log file: $log_entry");
    }
}

// Handle AJAX request for updating a task
if (isset($_POST['update_task_id'])) {
    // Log all POST data
    write_log("POST Data: " . print_r($_POST, true));

    $taskID = $_POST['update_task_id'];
    $title = $_POST['update_title'];
    $taskContent = $_POST['update_instructions'];
    $actionType = $_POST['actionType'];

    // Initialize contentIDs variable
    $contentIDs = null;

    // Check if grades were submitted
    if (isset($_POST['update_grade']) && !empty($_POST['update_grade'])) {
        $contentIDs = implode(',', $_POST['update_grade']);
    }

    // Validate actionType
    $allowedActions = ['Assign', 'Draft', 'Schedule'];
    if (!in_array($actionType, $allowedActions)) {
        $response = [
            'success' => false,
            'message' => 'Invalid action type.'
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $dueDate = NULL;
    $dueTime = NULL;

    // Variables for schedule-specific data
    $scheduleDate = isset($_POST['update_schedule_date']) ? $_POST['update_schedule_date'] : null;
    $scheduleTime = isset($_POST['update_schedule_time']) ? $_POST['update_schedule_time'] : null;

    // Validate schedule date and time
    if ($actionType == 'Schedule') {
        if (empty($scheduleDate) || empty($scheduleTime)) {
            $response = [
                'success' => false,
                'message' => 'Schedule date and time are required for scheduled tasks.'
            ];
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }

    // Log the update request details
    write_log("Update Task - TaskID: $taskID, ContentIDs: $contentIDs, Title: $title, DueDate: $dueDate, DueTime: $dueTime, Instructions: $taskContent, ActionType: $actionType");

    // Prepare base SQL
    $sql = "UPDATE tasks SET Title = ?, taskContent = ?, DueDate = ?, DueTime = ?, Status = ?";
    
    // Add ContentID to SQL if it exists
    if ($contentIDs !== null) {
        $sql .= ", ContentID = ?";
    }

    // Append SQL for scheduled tasks
    if ($actionType == 'Schedule' && $scheduleDate && $scheduleTime) {
        write_log("Schedule Date: $scheduleDate, Schedule Time: $scheduleTime");
        $sql .= ", Schedule_Date = ?, Schedule_Time = ?";
    }

    $sql .= " WHERE TaskID = ?";

    // Prepare statement
    $stmt = $conn->prepare($sql);

    // Bind parameters based on action type and contentIDs existence
    if ($actionType == 'Schedule' && $scheduleDate && $scheduleTime) {
        $status = 'Schedule';
        if ($contentIDs !== null) {
            $stmt->bind_param('ssssssssi', $title, $taskContent, $dueDate, $dueTime, $status, $contentIDs, $scheduleDate, $scheduleTime, $taskID);
        } else {
            $stmt->bind_param('sssssssi', $title, $taskContent, $dueDate, $dueTime, $status, $scheduleDate, $scheduleTime, $taskID);
        }
        write_log("Prepared statement with schedule date and time");
    } else {
        $status = ($actionType == 'Assign') ? 'Assign' : 'Draft';
        if ($contentIDs !== null) {
            $stmt->bind_param('ssssssi', $title, $taskContent, $dueDate, $dueTime, $status, $contentIDs, $taskID);
        } else {
            $stmt->bind_param('sssssi', $title, $taskContent, $dueDate, $dueTime, $status, $taskID);
        }
    }

    // Execute and handle the response
    $response = array();
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Announcement updated successfully!';
        write_log("Announcement update successful - TaskID: $taskID, Status: $actionType");

        // Update task_department if contentID is not provided
        if ($contentIDs === null && isset($_POST['department']) && !empty($_POST['department'])) {
            $departments = $_POST['department'];
            write_log("Updating task_department for TaskID: $taskID with Departments: " . implode(',', $departments));

            // Delete existing department associations
            $deleteSql = "DELETE FROM task_department WHERE TaskID = ?";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bind_param('i', $taskID);
            if ($deleteStmt->execute()) {
                write_log("Deleted existing department associations for TaskID: $taskID");
            } else {
                write_log("Failed to delete department associations for TaskID: $taskID. Error: " . $deleteStmt->error);
            }
            $deleteStmt->close();

            // Insert new department associations
            $insertSql = "INSERT INTO task_department (TaskID, dept_ID) VALUES (?, ?)";
            $insertStmt = $conn->prepare($insertSql);
            foreach ($departments as $deptID) {
                $insertStmt->bind_param('ii', $taskID, $deptID);
                if ($insertStmt->execute()) {
                    write_log("Inserted department association for TaskID: $taskID, DeptID: $deptID");
                } else {
                    write_log("Failed to insert department association for TaskID: $taskID, DeptID: $deptID. Error: " . $insertStmt->error);
                }
            }
            $insertStmt->close();
        }
    } else {
        $response['success'] = false;
        $response['message'] = 'Failed to update announcement.';
        write_log("Update Announcement Error: " . $stmt->error);
        write_log("SQL Query: $sql");
        write_log("Parameters: ContentIDs: $contentIDs, Title: $title, TaskContent: $taskContent, DueDate: $dueDate, DueTime: $dueTime, Status: $status, TaskID: $taskID");
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