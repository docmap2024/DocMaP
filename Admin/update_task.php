<?php
// Database connection
include 'connection.php';

// Handle AJAX request for updating a task
if (isset($_POST['update_task_id'])) {
    $taskID = $_POST['update_task_id'];

    // Log form data
    error_log("Received form data: TaskID = $taskID, ContentIDs = " . (isset($_POST['grade']) ? implode(',', $_POST['grade']) : 'None') . ", Title = " . $_POST['update_title'] . ", DueDate = " . $_POST['update_due_date'] . ", DueTime = " . $_POST['update_due_time'] . "\n", 3, 'logfile.log');

    // Check if grades were submitted
    $contentIDs = isset($_POST['grade']) && !empty($_POST['grade']) ? implode(',', $_POST['grade']) : null;

    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logfile.log');

    $title = $_POST['update_title'];
    $dueDate = $_POST['update_due_date'];
    $dueTime = $_POST['update_due_time'];
    $taskContent = $_POST['update_instructions'];
    $actionType = $_POST['actionType']; // Added actionType from the form

    $scheduleDate = isset($_POST['update_schedule_date']) ? $_POST['update_schedule_date'] : null;
    $scheduleTime = isset($_POST['update_schedule_time']) ? $_POST['update_schedule_time'] : null;

    // Log the schedule date and time
    error_log("Schedule Date: " . $scheduleDate . "\n", 3, 'logfile.log');
    error_log("Schedule Time: " . $scheduleTime . "\n", 3, 'logfile.log');

    // Log the update request details to logfile.log
    error_log("Update Task - TaskID: $taskID, ContentIDs: $contentIDs, Title: $title, DueDate: $dueDate, DueTime: $dueTime, Instructions: $taskContent, ActionType: $actionType, Schedule Date: " . ($scheduleDate ? $scheduleDate : 'Not set') . ", Schedule Time: " . ($scheduleTime ? $scheduleTime : 'Not set') . "\n", 3, 'logfile.log');

    // Prepare base SQL for updating tasks
    $sql = "UPDATE tasks SET Title = ?, taskContent = ?, DueDate = ?, DueTime = ?, Status = ?";

    // Append SQL for scheduled tasks
    if ($actionType == 'Schedule' && $scheduleDate && $scheduleTime) {
        error_log("Schedule Date: $scheduleDate, Schedule Time: $scheduleTime\n", 3, 'logfile.log');
        $sql .= ", Schedule_Date = ?, Schedule_Time = ?";
    }

    // Append SQL for contentID if provided
    if ($contentIDs !== null) {
        $sql .= ", ContentID = ?";
    }

    $sql .= " WHERE TaskID = ?";

    // Prepare statement
    $stmt = $conn->prepare($sql);

    // Bind parameters based on action type and contentID
    if ($actionType == 'Schedule' && $scheduleDate && $scheduleTime) {
        $status = 'Schedule';
        if ($contentIDs !== null) {
            $stmt->bind_param('sssssssi', $title, $taskContent, $dueDate, $dueTime, $status, $scheduleDate, $scheduleTime, $contentIDs, $taskID);
        } else {
            $stmt->bind_param('ssssssi', $title, $taskContent, $dueDate, $dueTime, $status, $scheduleDate, $scheduleTime, $taskID);
        }
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
        $response['message'] = 'Task updated successfully!';
        // Log successful update
        error_log("Task update successful - TaskID: $taskID, Status: $actionType\n", 3, 'logfile.log');

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
        $response['message'] = 'Failed to update task.';
        // Log error details
        error_log("Update Task Error: " . $stmt->error . "\n", 3, 'logfile.log');
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