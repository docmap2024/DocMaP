<?php
// Database connection
include 'connection.php';

// Handle AJAX request for updating a task
if (isset($_POST['update_task_id'])) {
    // Log all POST data
    error_log("POST Data: " . print_r($_POST, true) . "\n", 3, 'logfile.log');

    $taskID = $_POST['update_task_id'];
    $title = $_POST['update_title'];
    $taskContent = $_POST['update_instructions'];
    $actionType = $_POST['actionType']; // Added actionType from the form

    // Initialize contentIDs variable
    $contentIDs = null;

    // Check if grades were submitted (content IDs are optional now)
    if (isset($_POST['update_grade']) && !empty($_POST['update_grade'])) {
        $contentIDs = implode(',', $_POST['update_grade']); // Combine selected grades into a comma-separated string
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

    // Set as null since no field exists
    $dueDate = NULL;
    $dueTime = NULL;

    // Variables for schedule-specific data
    $scheduleDate = isset($_POST['update_schedule_date']) ? $_POST['update_schedule_date'] : null;
    $scheduleTime = isset($_POST['update_schedule_time']) ? $_POST['update_schedule_time'] : null;

    // Validate schedule date and time for scheduled tasks
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

    // Log the update request details to logfile.log
    error_log("Update Task - TaskID: $taskID, ContentIDs: $contentIDs, Title: $title, DueDate: $dueDate, DueTime: $dueTime, Instructions: $taskContent, ActionType: $actionType\n", 3, 'logfile.log');

    // Prepare base SQL
    $sql = "UPDATE tasks SET Title = ?, taskContent = ?, DueDate = ?, DueTime = ?, Status = ?";
    
    // Add ContentID to SQL if it exists
    if ($contentIDs !== null) {
        $sql .= ", ContentID = ?";
    }

    // Append SQL for scheduled tasks
    if ($actionType == 'Schedule' && $scheduleDate && $scheduleTime) {
        error_log("Schedule Date: $scheduleDate, Schedule Time: $scheduleTime\n", 3, 'logfile.log');
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
        error_log("Prepared statement with schedule date and time\n", 3, 'logfile.log');
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
        // Log successful update
        error_log("Announcement update successful - TaskID: $taskID, Status: $actionType\n", 3, 'logfile.log');

        // Update task_department if contentID is not provided
        if ($contentIDs === null && isset($_POST['department']) && !empty($_POST['department'])) {
            $departments = $_POST['department'];

            // Log the departments array
            error_log("Updating task_department for TaskID: $taskID with Departments: " . implode(',', $departments) . "\n", 3, 'logfile.log');

            // Delete existing department associations for the task
            $deleteSql = "DELETE FROM task_department WHERE TaskID = ?";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bind_param('i', $taskID);
            if ($deleteStmt->execute()) {
                error_log("Deleted existing department associations for TaskID: $taskID\n", 3, 'logfile.log');
            } else {
                error_log("Failed to delete department associations for TaskID: $taskID. Error: " . $deleteStmt->error . "\n", 3, 'logfile.log');
            }
            $deleteStmt->close();

            // Insert new department associations
            $insertSql = "INSERT INTO task_department (TaskID, dept_ID) VALUES (?, ?)";
            $insertStmt = $conn->prepare($insertSql);
            foreach ($departments as $deptID) {
                $insertStmt->bind_param('ii', $taskID, $deptID);
                if ($insertStmt->execute()) {
                    error_log("Inserted department association for TaskID: $taskID, DeptID: $deptID\n", 3, 'logfile.log');
                } else {
                    error_log("Failed to insert department association for TaskID: $taskID, DeptID: $deptID. Error: " . $insertStmt->error . "\n", 3, 'logfile.log');
                }
            }
            $insertStmt->close();
        }
    } else {
        $response['success'] = false;
        $response['message'] = 'Failed to update announcement.';
        // Log error details
        error_log("Update Announcement Error: " . $stmt->error . "\n", 3, 'logfile.log');
        error_log("SQL Query: $sql\n", 3, 'logfile.log');
        error_log("Parameters: ContentIDs: $contentIDs, Title: $title, TaskContent: $taskContent, DueDate: $dueDate, DueTime: $dueTime, Status: $status, TaskID: $taskID\n", 3, 'logfile.log');
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