<?php
// Database connection
include 'connection.php';

// Handle AJAX request for updating a task
if (isset($_POST['update_task_id'])) {
    $taskID = $_POST['update_task_id'];

    // Log form data
    error_log("Received form data: TaskID = $taskID, ContentIDs = " . (isset($_POST['grade']) ? implode(',', $_POST['grade']) : 'None') . ", Title = " . $_POST['update_title'] . ", DueDate = " . $_POST['update_due_date'] . ", DueTime = " . $_POST['update_due_time'] . "\n", 3, 'logfile.log');

    // Check if grades were submitted
    if (isset($_POST['grade']) && !empty($_POST['grade'])) {
        $contentIDs = implode(',', $_POST['grade']); // Combine selected grades into a comma-separated string
    } else {
        error_log("No grades selected for TaskID: $taskID\n", 3, 'logfile.log');
        exit;
    }

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


    // Prepare base SQL
    $sql = "UPDATE tasks SET ContentID = ?, Title = ?, taskContent = ?, DueDate = ?, DueTime = ?, Status = ?";

    // Append SQL for scheduled tasks
    if ($actionType == 'Schedule' && $scheduleDate && $scheduleTime) {
        error_log("Schedule Date: $scheduleDate, Schedule Time: $scheduleTime\n", 3, 'logfile.log');
        $sql .= ", Schedule_Date = ?, Schedule_Time = ?";
    }

    $sql .= " WHERE TaskID = ?";

    // Prepare statement
    $stmt = $conn->prepare($sql);

    // Bind parameters based on action type
    if ($actionType == 'Schedule' && $scheduleDate && $scheduleTime) {
        $status = 'Schedule';
        error_log("Prepared statement with schedule date and time\n", 3, 'logfile.log');
        $stmt->bind_param('ssssssssi', $contentIDs, $title, $taskContent, $dueDate, $dueTime, $status, $scheduleDate, $scheduleTime, $taskID);
    } else {
        $status = ($actionType == 'Assign') ? 'Assign' : 'Draft';
        $stmt->bind_param('ssssssi', $contentIDs, $title, $taskContent, $dueDate, $dueTime, $status, $taskID);
    }

    // Execute and handle the response
    $response = array();
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Task updated successfully!';
        // Log successful update
        error_log("Task update successful - TaskID: $taskID, Status: $actionType\n", 3, 'logfile.log');
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