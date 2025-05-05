<?php
// Database connection
include 'connection.php';
session_start();

// Assume the user ID is stored in the session when the user logs in
$user_id = $_SESSION['user_id']; // Adjust this as per your session management
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'tasks';

// Fetch dept_ID from useracc table for the logged-in user
$sql_user_dept = "SELECT dept_ID FROM useracc WHERE UserID = ?"; // Adjust 'id' to the actual column name
$stmt_user_dept = $conn->prepare($sql_user_dept);
$stmt_user_dept->bind_param('i', $user_id);
$stmt_user_dept->execute();
$result_user_dept = $stmt_user_dept->get_result();

if ($row_user_dept = $result_user_dept->fetch_assoc()) {
    $user_dept_id = $row_user_dept['dept_ID'];
} else {
    // Handle case where user does not have a department assigned
    echo json_encode(['error' => 'User does not have a department assigned.']);
    exit;
}

// Check if department_ids are being sent
if (isset($_POST['department_ids'])) {
    $department_ids = explode(',', $_POST['department_ids']); // Convert comma-separated string to array

    if (!empty($department_ids)) {
        // Fetch data for the selected departments
        $placeholders = implode(',', array_fill(0, count($department_ids), '?'));
        $sql = "SELECT DISTINCT ContentID, Title, LEFT(Captions, 50) AS Captions FROM feedcontent WHERE dept_ID IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $types = str_repeat('i', count($department_ids));
        $stmt->bind_param($types, ...$department_ids);
        $stmt->execute();
        $result = $stmt->get_result();

        $grades = array();
        while ($row = $result->fetch_assoc()) {
            $grades[] = $row;
        }

        // Return JSON response
        echo json_encode($grades);
    } else {
        echo json_encode([]);
    }
    exit;
}

// Fetch departments for checkboxes
$sql_user_dept = "SELECT dept_ID FROM useracc WHERE UserID = ?";
$stmt_user_dept = $conn->prepare($sql_user_dept);
$stmt_user_dept->bind_param('i', $user_id);
$stmt_user_dept->execute();
$result_user_dept = $stmt_user_dept->get_result();

if ($row_user_dept = $result_user_dept->fetch_assoc()) {
    $user_dept_id = $row_user_dept['dept_ID'];
} else {
    echo json_encode(['error' => 'User does not have a department assigned.']);
    exit;
}


// SQL query to fetch departments based on the user's department ID
$sql = "SELECT dept_ID, dept_name FROM department WHERE dept_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_dept_id); // Use the fetched user's dept ID
$stmt->execute();
$result = $stmt->get_result();

$departments = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}


// Set the number of rows per page
$rows_per_page = 10;

// Get the current page number from the URL, default to 1 if not set
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Calculate the offset for the SQL query
$offset = ($page - 1) * $rows_per_page;

// Fetch tasks for display, filtered by user's dept_ID, ordered by timestamp (newest first)
$sql = "SELECT t.TaskID AS TaskID, t.Title AS TaskTitle, t.taskContent, t.DueDate, t.DueTime, d.dept_name, fc.Title AS ContentTitle, fc.Captions, t.Status, t.TimeStamp, fc.ContentID
        FROM tasks t
        LEFT JOIN feedcontent fc ON t.ContentID = fc.ContentID
        LEFT JOIN department d ON fc.dept_ID = d.dept_ID
        WHERE t.Type = 'Task' AND d.dept_ID = ? AND t.ApprovalStatus = 'Approved' 
        ORDER BY t.TimeStamp DESC  
        LIMIT ? OFFSET ?";
$stmt_tasks = $conn->prepare($sql);
$stmt_tasks->bind_param('iii', $user_dept_id, $rows_per_page, $offset);
$stmt_tasks->execute();
$result_tasks = $stmt_tasks->get_result();

$tasks = array();
if ($result_tasks->num_rows > 0) {
    while ($row = $result_tasks->fetch_assoc()) {
        $tasks[] = $row;
    }
}
$sql_pending_tasks = "SELECT t.TaskID AS TaskID, t.Title AS TaskTitle, t.taskContent, t.DueDate, t.DueTime, d.dept_name, fc.Title AS ContentTitle, fc.Captions, t.Status, t.TimeStamp
                      FROM tasks t
                      LEFT JOIN feedcontent fc ON t.ContentID = fc.ContentID
                      LEFT JOIN department d ON fc.dept_ID = d.dept_ID
                      WHERE t.Type = 'Task' AND t.ApprovalStatus = 'Pending'
                      ORDER BY t.TimeStamp DESC
                      LIMIT ? OFFSET ?";
$stmt_pending_tasks = $conn->prepare($sql_pending_tasks);
$stmt_pending_tasks->bind_param('ii', $rows_per_page, $offset); // Adjust as needed
$stmt_pending_tasks->execute();
$result_pending_tasks = $stmt_pending_tasks->get_result();

$pending_tasks = array();
if ($result_pending_tasks->num_rows > 0) {
    while ($row = $result_pending_tasks->fetch_assoc()) {
        $pending_tasks[] = $row;
    }
}


// Query to get the total number of tasks for pagination calculation
$sql_total = "SELECT COUNT(*) as total FROM tasks t
              LEFT JOIN feedcontent fc ON t.ContentID = fc.ContentID
              WHERE t.Type = 'Task' AND fc.dept_ID = ?";
$stmt_total = $conn->prepare($sql_total);
$stmt_total->bind_param('i', $user_dept_id);
$stmt_total->execute();
$result_total = $stmt_total->get_result();

if ($result_total) {
    $total_tasks = $result_total->fetch_assoc()['total'];
    $total_pages = ceil($total_tasks / $rows_per_page);
} else {
    // Handle error in total tasks query
    echo "Error: " . $conn->error;
    exit;
}

// Handle AJAX request for deleting a task
if (isset($_POST['task_id'])) {
    $task_id = $_POST['task_id'];

    // SQL query to delete a task based on TaskID
    $sql = "DELETE FROM tasks WHERE TaskID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $task_id);

    if ($stmt->execute()) {
        $response = array('success' => true, 'message' => 'Task deleted successfully.');
    } else {
        $response = array('success' => false, 'message' => 'Failed to delete task.');
    }

    echo json_encode($response);
    exit;
}

$query = "SELECT DISTINCT Title FROM tasks WHERE Type='Task'";
$result = $conn->query($query);

// Automatically reject overdue tasks with Pending ApprovalStatus
date_default_timezone_set('Asia/Manila');
$currentDate = date('Y-m-d');
$currentTime = date('H:i:s');


$sql_auto_reject = "UPDATE tasks 
                    SET ApprovalStatus = 'Rejected' 
                    WHERE ApprovalStatus = 'Pending' 
                    AND (DueDate < ? 
                        OR (DueDate = ? AND DueTime < ?))";

$stmt_auto_reject = $conn->prepare($sql_auto_reject);
$stmt_auto_reject->bind_param('sss', $currentDate, $currentDate, $currentTime);

if (!$stmt_auto_reject->execute()) {
    // Log or handle error if needed
    error_log('Error updating overdue tasks: ' . $stmt_auto_reject->error);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
     <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/png" href="../img/Logo/docmap-logo-1.png">

    <title>Create Task</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://unpkg.com/ionicons@5.5.2/dist/ionicons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <style>
        

        .header {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
        }

        
        .buttonTask {
            background-color: #9b2035;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 25px; /* Adjust for desired roundness */
            cursor: pointer;
            transition: background-color 0.3s; /* Add a smooth transition effect */
            float: right;
           font-size:18px;
            font-weight:bold;
        }

        .buttonTask:hover {
            background-color: #7a182a;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem; /* Space below each form group */
        }

        label {
            display: block; /* Ensures label takes up full width */
            margin-bottom: 0.5rem; /* Space between label and input */
            font-weight: bold;
            color: #333;
        }

        input[type="text"],
        input[type="date"],
        textarea,
        select {
            width: 100%; /* Full width of the container */
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Includes padding and border in width calculation */
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="date"]:focus,
        textarea:focus,
        select:focus {
            border-color: #007bff; /* Highlight border color on focus */
            outline: none; /* Remove default outline */
        }

        textarea {
            resize: vertical; /* Allows vertical resizing only */
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%; /* Full viewport width */
            height: 100vh; /* Full viewport height */
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            
        }


        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 1200px; /* 80% of viewport width */
           
            height: auto; /* 80% of viewport height */
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            overflow-y: auto; /* Scroll if content exceeds the height */
            position: relative;
            
        }

        .modal-header {
            display: flex;
            justify-content: flex-end; /* Align items to the right */
            align-items: center;
            padding: 5px;
            position: relative; /* Position relative for absolute positioning */
        }

        .modal-container{
            margin-top: -40px;
        }

        .header-task{
            margin-bottom: 30px;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            margin-left: 25px; /* Space between dropdown and close button */
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        h2 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }

        table th, table td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            background-color: #fff;
        }

        table th {
            background-color: #9b2035;
            color: #fff;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }
      

        .form-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .form-left,
        .form-right {
            flex: 1;
            min-width: 300px;
        }

        /* Additional space for attachment field */
        .form-left .form-group input[type="file"] {
            padding: 20px;
            height: 150px; /* Increase height to make it more spacious */
            border: 2px dashed #ccc;
            background-color: #fafafa;
            display: block;
            margin-top: 10px;
        }
        
        .form-right label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }

        .form-right select,
        .form-right input[type="date"],
        .form-right input[type="time"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-right select:focus,
        .form-right input[type="date"]:focus {
            border-color: #007bff;
            outline: none;
        }

        /*---------------------Update Modal-----------*/
        input[type="text"],
        input[type="date"],
        textarea #update_instructions,
        select {
            width: 100%; /* Full width of the container */
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Includes padding and border in width calculation */
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="date"]:focus,
        textarea:focus,
        select:focus {
            border-color: #007bff; /* Highlight border color on focus */
            outline: none; /* Remove default outline */
        }

        textarea#update_instructions{
            resize: vertical; /* Allows vertical resizing only */
            height: 160px; /* Adjust the height as needed */
        }

        .update-modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 118vw; /* Full viewport width */
            height: 100vh; /* Full viewport height */
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            padding-top: 60px;
        }

        .update-modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 70vw; /* 100% of viewport width */
            max-width: 1200px; /* Optional: maximum width */
            height: 75vh; /* 70% of viewport height */
            max-height: 800px; /* Optional: maximum height */
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            overflow-y: relative; /* Scroll if content exceeds the height */
            position: relative;
            top: 50%; /* Center the modal vertically */
            transform: translateY(-50%); /* Center the modal vertically */
        }


        .update-modal-container{
            margin-top: -40px;
        }

        .update-header-task{
            margin-bottom: 30px;
        }

        .update-form-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .update-form-left,
        .update-form-right {
            flex: 1;
            min-width: 300px;
        }
        
        .update-form-right label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }

        .update-form-right select,
        .update-form-right input[type="date"],
        .update-form-right input[type="time"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .update-form-right select:focus,
        .update-form-right input[type="date"],
        .update-form-right input[type="time"]:focus {
            border-color: #007bff;
            outline: none;
        }
        /*!--------------------Update Modal---------------*/

        .buttonEdit {
            background-color: blue;
            color: white;
            border: none;
            border-radius: 50%; /* Makes the button a perfect circle */
            padding: 10px;
            cursor: pointer;
            transition: background-color 0.3s; /* Smooth transition effect */
            width: 40px; /* Set a fixed width for the button */
            height: 40px; /* Set a fixed height for the button */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .buttonEdit:hover {
            background-color: darkblue; /* Darker shade on hover for emphasis */
        }
        .buttonDelete {
            background-color: #d92b2b; /* A deep red color */
            color: white;
            border: none;
            border-radius: 50%; /* Makes the button a perfect circle */
            padding: 10px;
            cursor: pointer;
            transition: background-color 0.3s; /* Smooth transition effect for background color */
            width: 40px; /* Set a fixed width for the button */
            height: 40px; /* Set a fixed height for the button */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .buttonDelete:hover {
            background-color: #b72828; /* Slightly darker red on hover for emphasis */
        }
        .button-group2 {
            display: flex; /* Align buttons in a row */
             float:right;
            margin-bottom:10px;

            
            
        }
       
        .button-group {
            display: flex; /* Align buttons in a row */
            
            float:right;
            margin-bottom: 10px;
            
        }

        .buttonEdit,
        .buttonDelete {
            flex: 1; /* Make buttons take equal width if needed */
            text-align: center; /* Center text within buttons */
        }
        
        table td {
            padding: 12px;
            text-align: center; /* Center horizontally */
            vertical-align: middle; /* Center vertically */
            border-bottom: 1px solid #ddd;
            background-color: #fff;
            max-width: 200px; /* Adjust width as needed */
            white-space: nowrap; /* Prevent text wrapping */
            overflow: hidden; /* Hide overflow text */
            text-overflow: ellipsis; /* Add ellipsis */
        }

        table td p {
            margin: 0; /* Remove default margins */
            line-height: 1.4; /* Improve readability */
        }


        .search-container {
            display: flex;
            align-items: center;
            position: relative;
            margin-right:20px;
        }

        .search-container .search-bar {
            display: none;
            width: 100%;
            max-width: 300px;
        }

        .search-bar input {
            width: 200px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .search-container .search-icon {
            cursor: pointer;
            font-size: 1.5em;
            margin-right: 10px;
        }
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-toggle {
            background-color: #ededed;
            color: black;
            padding: 10px 15px;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s, border-color 0.3s;
            width: 100%;
        }

        .dropdown-toggle:focus {
            border-color: #007bff;
            outline: none;
        }

        .dropdown-menu {
        display: none;
        position: absolute;
        background-color: white; /* Set background to white */
        max-width: 230px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        z-index: 1;
        margin-top: 5px;
        border-radius: 4px;
        /* Remove the gradient */
        /* background: linear-gradient(to bottom, #f0f0f0, #e0e0e0); */
        }

        .dropdown-menu.show {
        display: block;
        max-height: 380px; /* Adjust this value as needed */
        overflow-y: auto;
        scroll-behavior: smooth;
        background-color: white; /* Set background to white */
        }

        .checkbox-container, .checkbox-all-container {
            padding: 6px 8px;
    border-bottom: 1px solid #ddd;
    display: flex;
    align-items: center;
    gap: 10px; /* Adds space between checkbox and label */
        }

        .checkbox-container input, .checkbox-all-container input {
            cursor: pointer;
    outline: none;
    border: 1px solid #ccc;
    border-radius: 3px;
    width: 16px;
    height: 16px;
        }

        .checkbox-container label, .checkbox-all-container label {
        margin: 0;
        font-weight: 500;
        }

        .custom-checkbox {
        outline: none !important;
        box-shadow: none !important;
        background: transparent;
        border: none;
        }

        .custom-checkbox:focus {
        outline: none !important;
        box-shadow: none !important;
        background: transparent;
        }

        .checkbox-all-container input[type="checkbox"], 
        .checkbox-container input[type="checkbox"] {
        margin-left: -65px;
        margin-right: -77px; /* Adds space between checkbox and label */
        cursor: pointer;
        border: 1px solid #ccc;
        border-radius: 3px;
        }

        .checkbox-container label,
        .checkbox-all-container label {
        cursor: pointer;
        /* Remove inline-block and use flexbox for better control */
        /* display: inline-block; */
        margin-left: 5px;
        transition: color 0.2s ease;
        /* Center the label text */
        text-align: left;
        /* Add width to the label for better justification */
        width: 100%;
        }

        .checkbox-container label:hover,
        .checkbox-all-container label:hover,
        .checkbox-container input:hover,
        .checkbox-all-container input:hover {
        color: #9b2035;
        }

        .checkbox-container input:checked + label,
        .checkbox-all-container input:checked + label {
        color: #9b2035;
        }

        .checkbox-container input:checked,
        .checkbox-all-container input:checked {
        background-color: #9b2035;
        border-color:#9b2035;
        }
        /* Style for the dropdown button with arrow */
        .submitdropdown-toggle {
            background-color: #4CAF50; /* Green background */
            color: white; /* White text */
            padding: 10px 15px; /* Padding */
            font-size: 16px; /* Font size */
            border: none; /* No border for the button */
            cursor: pointer; /* Pointer cursor */
            border-radius: 4px; /* Rounded corners */
            display: flex;
            align-items: center; /* Center the content vertically */
            width: auto; /* Allow button to auto-size */
        }

        /* Arrow styling */
        .arrow-down {
            display: inline-block;
            margin-left: 10px;
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 5px solid white; /* White arrow */
        }

        /* Style for the dropdown menu */
        .submitdropdown-menu {
            display: none; /* Initially hidden */
            position: absolute;
            background-color: white; /* White background */
            min-width: 115px; /* Minimum width */
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2); /* Shadow */
            z-index: 1; /* Ensure it's above other elements */
            padding: 6px 0;
            border-radius: 4px;
            border: none; /* Remove border for the dropdown menu */
        }

        /* Dropdown item styling */
        .submitdropdown-item {
            color: black; /* Black text */
            padding: 10px 15px; /* Padding */
            text-decoration: none; /* Remove underline */
            display: block; /* Block display */
            cursor: pointer; /* Pointer cursor */
            border: none; /* Remove border for dropdown items */
            background-color: transparent; /* Set default background color to transparent */
            width: 100%; /* Make the item take the full width of the dropdown menu */
            text-align: left; /* Align text to the left */
        }

        /* Hover effect for dropdown items */
        .submitdropdown-item:hover {
            background-color: #f1f1f1; /* Light gray background on hover */
        }

        /* Show dropdown menu when toggle is clicked */
        .submitdropdown.show .submitdropdown-menu {
            display: block; /* Show menu */
        }


        /*------------------------Schedule Modal-----------------------------------*/
        .scheduleModal, .updatescheduleModal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0, 0, 0, 0.4); /* Black w/ opacity */
        }

        .small-modal {
            position: absolute; /* Position it absolutely within the modal */
            background-color: #fefefe;
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Default width for other modals */
            max-width: 300px; /* Set a maximum width */
            max-height: 350px; /* Set a maximum height */
            overflow-y: auto; /* Allow scrolling if content is too tall */
            border-radius: 5px; /* Slightly rounded corners */
            
            /* Centering the modal */
            top: 50%; /* Move it to the middle of the screen vertically */
            left: 60%; /* Move it to the middle of the screen horizontally */
            transform: translate(-50%, -50%); /* Offset it back by half its width and height */
        }

        /* Style for the button container */
        .button-container {
            display: flex; /* Use flexbox for button alignment */
            justify-content: center; /* Center the buttons horizontally */
            margin-top: 20px; /* Space above the buttons */
        }

        /* Style for the buttons */
        .cancel {
            color: #d92b2b; /* Change to your desired color */
            cursor: pointer; /* Change cursor to pointer */
            font-weight: bold; /* Make the text bold */
            padding: 10px 15px; /* Padding for the cancel button */
            border: 1px solid #d92b2b; /* Border to match the text color */
            border-radius: 4px; /* Rounded corners */
            background-color: white; /* White background */
            margin-right: 10px; /* Space between buttons */
        }

        /* Confirm button styles */
        .confirm-button {
            background-color: #28a745; /* Green background */
            color: white; /* White text */
            border: none; /* Remove default border */
            padding: 10px 15px; /* Padding for the button */
            border-radius: 4px; /* Rounded corners */
            cursor: pointer; /* Change cursor to pointer */
            font-weight: bold; /* Make the text bold */
        }

        /* Hover effect for the confirm button */
        .confirm-button:hover {
            background-color: #218838; /* Darker green on hover */
        }

        /* Hover effect for the cancel button */
        .cancel:hover {
            background-color: #e9ecef; /* Light gray on hover */
        }

        /* Style for the smaller schedule modal */
        .small-modal {
            padding: 10px; /* Less padding for a compact look */
        }

        /* Add consistent styling for input fields */
        input[type="date"],
        input[type="time"] {
            width: 100%; /* Make the input fields take the full width */
            padding: 10px; /* Add some padding */
            margin: 8px 0; /* Margin for spacing */
            border: 1px solid #ccc; /* Light border */
            border-radius: 4px; /* Rounded corners */
            box-sizing: border-box; /* Include padding and border in element's total width and height */
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            margin-bottom: 30px;
        }

        .pagination a {
            padding: 10px 15px;
            margin: 0 5px;
            text-decoration: none;
            background-color: transparent; /* Make the background of the page numbers transparent */
            color:grey;
        
            border-radius: 50%; /* Make buttons circular */
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .pagination a.active {
            background-color: transparent;
            color: #9b2035;
            font-weight:bold;
        }

        .pagination a:hover {
            background-color: #ddd;
        }

        .pagination a.prev-button, .pagination a.next-button {
            background-color: #a53649; /* Lighter shade for Previous and Next */
            border-color: transparent; 
            color: white;/* Lighter border color */
        }

        .pagination a.prev-button:hover, .pagination a.next-button:hover {
            background-color: #a43150; /* Slightly darker shade when hovered */
        }

        .pagination a.first-button, .pagination a.last-button {
            background-color: #9b2035; /* Dark background color for First and Last */
            color: white;
        }

        .pagination a.first-button:hover, .pagination a.last-button:hover {
            background-color: #a43150; /* Lighter shade on hover for First and Last */
        }

        /*--------------------------Title Dropdown-----------------*/
        .title-dropdown-container {
            display: flex;
            align-items: center;
            position: relative;
            margin-bottom: 20px;
        }

        .title-dropdown-container input {
            flex: 1;
            padding-right: 30px; /* Adjust for dropdown button space */
        }

        .title-dropdown-toggle {
            background: none;
            border: none;
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }

        .title-dropdown-menu {
            display: none;
            position: absolute;
            background-color: #fff;
            border: 1px solid #ddd;
            z-index: 1;
            max-height: 200px; /* Adjust this height if needed */
            overflow-y: auto; /* Enable vertical scrolling */
            width: 100%;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .title-dropdown-menu.show {
            display: block;
        }

        .title-dropdown-item {
            padding: 8px 16px;
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            cursor: pointer;
        }

        .title-dropdown-item:hover {
            background-color: #f1f1f1;
        }
        .editor-container {
            width: 500px; /* Set custom width */
            margin: 0 auto; /* Center align */
            
        }
        /* CKEditor content area */
        .ck-editor__editable {
            min-height: 250px; /* Set desired height */
            overflow-y: auto; 
        }
        /* Hidden textarea */
        #instructions {
            display: none;
        }
        .attachment {
            border: 2px dashed #ccc;
            padding: 10px;
            border-radius: 5px;
            display: inline-block;
            width: fit-content;
            position: relative; /* To position the remove button */
            min-width:100%;
            max-height:100%;
        }

        .file-container {
            display: flex;
            flex-wrap: wrap; /* Allows items to wrap onto new lines */
            gap: 10px;
            margin-top: 10px;
        }

       .file-item {
            flex: 1 1 calc(25% - 10px); /* Divide space equally for up to 4 columns */
            max-width: calc(25% - 10px);
            background-color: #f1f1f1;
            border-radius: 5px;
            padding: 5px 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative; /* To position the remove button */
            

        }

        .file-name {
            flex-grow: 1;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
        }

        .remove-file {
            background-color: #ff4d4d;
            border: none;
            color: white;
            padding: 0 5px;
            cursor: pointer;
            position: absolute;
            top: 50%;
            right: 5px;
            transform: translateY(-50%);
            border-radius: 3px;
            font-size: 14px;
            line-height: 1;
        }


        /*------------------Approval Task------------------*/
        button {
            padding: 5px 10px;
            cursor: pointer;
        }

        .success {
            background-color: #dff0d8;
            border-color: #d6e9c6;
            color: #3c763d;
        }

        .error {
            background-color: #f2dede;
            border-color: #ebccd1;
            color: #a94442;
        }

        .action-buttons {
            margin-bottom: 20px;
            text-align: right;
        }

        button {
            padding: 5px 10px;
            margin-left: 5px;
            cursor: pointer;
        }

       /* Style for Approve and Reject buttons */
        #approveSelected {
            background-color: #28a745; /* Green */
             color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 25px; /* Adjust for desired roundness */
            cursor: pointer;
            transition: background-color 0.3s; /* Add a smooth transition effect */
            font-size:18px;
            font-weight:bold;

        }          

        #rejectSelected {
            background-color: #dc3545; /* Red */
             color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 25px; /* Adjust for desired roundness */
            cursor: pointer;
            transition: background-color 0.3s; /* Add a smooth transition effect */
           font-size:18px;
            font-weight:bold;

        }

        /* Style when hovering over the buttons */
        #approveSelected:hover {
            background-color: #218838; /* Darker green */
        }

        #rejectSelected:hover {
            background-color: #c82333; /* Darker red */
        }

        /* Style for the tab container */
        .tabs {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 20px;
        }

        /* Style for individual tab buttons */
        .tab-button {
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            color: #555;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
            margin-right: 10px;
            background-color:transparent;
        }

        .tab-button:hover {
            background-color: #ddd;
        }

        .tab-button.active {
            color: #9b2035;
            border-bottom: 2px solid #9b2035; /* Highlight active tab */
        }

        .hidden {
            display: none;
        }
        .dropdown-okay-button {
            margin-top: 10px;
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: block;
            width: 100%;
            text-align: center;
        }

        .dropdown-okay-button:hover {
            background-color: #45a049;
        }

        /* Task View Modal - Specific Styles */
        #taskViewModal {
            display: none;
            position: fixed;
            z-index: 1050; /* Higher than your existing modal */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }

        #taskViewModal .task-modal-content {
            background-color: #ffffff;
            margin: 5% auto;
            padding: 25px;
            border: none;
            width: 80%;
            max-width: 700px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            animation: taskModalFadeIn 0.3s;
        }

        @keyframes taskModalFadeIn {
            from {opacity: 0; transform: translateY(-20px);}
            to {opacity: 1; transform: translateY(0);}
        }

        #taskViewModal .task-modal-close {
            color: #6c757d;
            float: right;
            font-size: 28px;
            font-weight: bold;
            margin-top: -10px;
            margin-right: -10px;
        }

        #taskViewModal .task-modal-close:hover,
        #taskViewModal .task-modal-close:focus {
            color:rgb(0, 0, 0);
            text-decoration: none;
            cursor: pointer;
        }

        /* Task View Header */
        #taskViewModal .task-view-header {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        #taskViewModal #taskViewTitle {
            margin: 0;
            color:rgb(0, 0, 0);
            font-size: 24px;
            font-weight: 600;
        }

        /* Task Meta Information */
        #taskViewModal .task-meta-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }

        #taskViewModal .task-meta-item {
            margin-bottom: 5px;
        }

        #taskViewModal .task-meta-label {
            display: block;
            font-size: 13px;
            color:rgb(0, 0, 0);
            margin-bottom: 3px;
            font-weight: bold;
        }

        #taskViewModal .task-meta-value {
            font-size: 15px;
            color: #343a40;
            font-weight: 500;
        }

        #taskViewModal #taskViewStatus {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
        }

        /* Task Content Area */
        #taskViewModal .task-content-area {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        #taskViewModal .task-content-area h3 {
            margin-top: 0;
            color:rgb(0, 0, 0);
            font-size: 18px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        #taskViewModal #taskViewContent {
            line-height: 1.6;
            color: #495057;
        }

        #taskViewModal #taskViewContent p {
            margin-bottom: 15px;
        }

        #taskViewModal #taskViewContent img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin: 10px 0;
        }

        /* View Full Task Button Styles */
        .task-view-footer {
            margin-top: 20px;
            text-align: right;
            border-top: 1px solid #e9ecef;
            padding-top: 15px;
        }

        .buttonViewFullTask {
            background-color: #4a6fdc;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .buttonViewFullTask:hover {
            background-color: #3a5bc7;
        }

        .buttonViewFullTask i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <?php include 'navbar.php'; ?>
    </section>
    <!-- SIDEBAR -->

    <!-- CONTENT -->
    <section id="content">
        <!-- NAVBAR -->
        <?php include 'topbar.php'; ?>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
        <div class="tab-buttons">
    <button class="tab-button <?php echo $active_tab === 'tasks' ? 'active' : ''; ?>" onclick="switchTab('tasks')">Tasks</button>

</div>

<!-- Task Table (Tasks Tab) -->
<?php
$groupedTasks = [];
foreach ($tasks as $task) {
    // Fetch TaskTitle, TimeStamp, and UserID
    $taskTitle = isset($task['TaskTitle']) ? $task['TaskTitle'] : 'Untitled';
    $timeStamp = isset($task['TimeStamp']) ? $task['TimeStamp'] : 'NoTimeStamp';
    $userID = isset($task['UserID']) ? $task['UserID'] : 'NoUserID'; // Default to 'NoUserID' if not present

    // Create the grouping key based on TaskTitle, TimeStamp, and UserID
    $key = $taskTitle . '_' . $timeStamp . '_' . $userID;

    // If the key doesn't exist, initialize it as an empty array
    if (!isset($groupedTasks[$key])) {
        $groupedTasks[$key] = [];
    }

    // Add the task to the group
    $groupedTasks[$key][] = $task;
}


?>

<div id="tasks" class="tab-content" style="display: <?php echo $active_tab === 'tasks' ? 'block' : 'none'; ?>;">
    <div class="container">
        <div class="header">
            <h1>Tasks</h1>
            <div class="button-group2   ">
                <div class="search-container">
                    <i class="fas fa-search search-icon" onclick="toggleSearchBar()"></i>
                    <div class="search-bar">
                        <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search for names..">
                    </div>
                </div>

                
                <div class="filter-section" style="margin-right:10px;">
                    <select id="statusFilter" onchange="filterByStatus()">
                        <option value="">Filter By Status</option>
                        <option value="Assign">Assign</option>
                        <option value="Draft">Draft</option>
                        <option value="Schedule">Schedule</option>
                    </select>
                </div>
                

                <button type="button" class="buttonTask" onclick="openModal()">Create Task</button>
            </div>
        </div>
       <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Content</th>
                    <th>Department</th>
                    <th>Grade</th>
                    <th>Due Date</th>
                    <th>Due Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="taskTableBody">
                <?php if (!empty($groupedTasks)): ?>
                    <?php foreach ($groupedTasks as $groupKey => $tasks): ?>
                        <!-- Render only the first task in each group -->
                        <?php $mainTask = $tasks[0]; ?>
                        <tr class="task-row" data-status="<?php echo htmlspecialchars($mainTask['Status']); ?>"
                            onclick="viewTask(
                                '<?php echo $mainTask['TaskID']; ?>',
                                '<?php echo htmlspecialchars(addslashes($mainTask['TaskTitle']), ENT_QUOTES); ?>',
                                '<?php echo addslashes($mainTask['taskContent']); ?>',
                                '<?php echo htmlspecialchars(addslashes($mainTask['dept_name']), ENT_QUOTES); ?>',
                                '<?php echo htmlspecialchars(addslashes($mainTask['ContentTitle'] . ' - ' . $mainTask['Captions']), ENT_QUOTES); ?>',
                                '<?php echo $mainTask['DueDate']; ?>',
                                '<?php echo $mainTask['DueTime']; ?>',
                                '<?php echo $mainTask['Status']; ?>',
                                '<?php echo isset($mainTask['ContentID']) ? $mainTask['ContentID'] : ''; ?>'
                            )" style="cursor: pointer;">
                            <td rowspan="<?php echo count($tasks); ?>">
                                <?php echo htmlspecialchars($mainTask['TaskTitle']); ?>
                            </td>
                            <td><?php echo addslashes($mainTask['taskContent']); ?></td>
                            <td><?php echo htmlspecialchars($mainTask['dept_name']); ?></td>
                            <td><?php echo htmlspecialchars($mainTask['ContentTitle'] . ' - ' . $mainTask['Captions']); ?></td>
                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($mainTask['DueDate']))); ?></td>
                            <td><?php echo htmlspecialchars(date('h:i A', strtotime($mainTask['DueTime']))); ?></td>
                            <td style="font-weight:bold; color: <?php echo $mainTask['Status'] == 'Assign' ? 'green' : ($mainTask['Status'] == 'Schedule' ? 'blue' : 'grey'); ?>;">
                                <?php echo htmlspecialchars($mainTask['Status']); ?>
                            </td>
                            <td>
                                <div class="button-group">
                                    <button class="buttonEdit" 
                                        onclick="event.stopPropagation(); editTask(
                                            '<?php echo $mainTask['TaskID']; ?>', 
                                            '<?php echo htmlspecialchars(addslashes($mainTask['TaskTitle']), ENT_QUOTES); ?>', 
                                            '<?php echo htmlspecialchars(addslashes($mainTask['taskContent']), ENT_QUOTES); ?>', 
                                            '<?php echo htmlspecialchars(addslashes($mainTask['dept_name']), ENT_QUOTES); ?>', 
                                            '<?php echo htmlspecialchars(addslashes($mainTask['ContentTitle'] . ' - ' . $mainTask['Captions']), ENT_QUOTES); ?>', 
                                            '<?php echo $mainTask['DueDate']; ?>', 
                                            '<?php echo $mainTask['DueTime']; ?>'
                                        )">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="buttonDelete" onclick="event.stopPropagation(); deleteTask('<?php echo $mainTask['TaskID']; ?>')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <!-- Render additional rows for other tasks in the group -->
                        <?php for ($i = 1; $i < count($tasks); $i++): ?>
                            <?php $subTask = $tasks[$i]; ?>
                            <tr class="task-row" data-status="<?php echo htmlspecialchars($subTask['Status']); ?>"
                                onclick="viewTask(
                                    '<?php echo $subTask['TaskID']; ?>',
                                    '<?php echo htmlspecialchars(addslashes($subTask['TaskTitle']), ENT_QUOTES); ?>',
                                    '<?php echo addslashes($subTask['taskContent']); ?>',
                                    '<?php echo htmlspecialchars(addslashes($subTask['dept_name']), ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars(addslashes($subTask['ContentTitle'] . ' - ' . $subTask['Captions']), ENT_QUOTES); ?>',
                                    '<?php echo $subTask['DueDate']; ?>',
                                    '<?php echo $subTask['DueTime']; ?>',
                                    '<?php echo $subTask['Status']; ?>',
                                    '<?php echo isset($subTask['ContentID']) ? $subTask['ContentID'] : ''; ?>'
                                )" style="cursor: pointer;">
                                <td><?php echo addslashes($subTask['taskContent']); ?></td>
                                <td><?php echo htmlspecialchars($subTask['dept_name']); ?></td>
                                <td><?php echo htmlspecialchars($subTask['ContentTitle'] . ' - ' . $subTask['Captions']); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($subTask['DueDate']))); ?></td>
                                <td><?php echo htmlspecialchars(date('h:i A', strtotime($subTask['DueTime']))); ?></td>
                                <td style="font-weight:bold; color: <?php echo $subTask['Status'] == 'Assign' ? 'green' : ($subTask['Status'] == 'Schedule' ? 'blue' : 'grey'); ?>;">
                                    <?php echo htmlspecialchars($subTask['Status']); ?>
                                </td>
                                <td>
                                    <div class="button-group">
                                        <button class="buttonEdit" 
                                            onclick="event.stopPropagation(); editTask(
                                                '<?php echo $subTask['TaskID']; ?>', 
                                                '<?php echo htmlspecialchars(addslashes($subTask['TaskTitle']), ENT_QUOTES); ?>', 
                                                '<?php echo htmlspecialchars(addslashes($subTask['taskContent']), ENT_QUOTES); ?>', 
                                                '<?php echo htmlspecialchars(addslashes($subTask['dept_name']), ENT_QUOTES); ?>', 
                                                '<?php echo htmlspecialchars(addslashes($subTask['ContentTitle'] . ' - ' . $subTask['Captions']), ENT_QUOTES); ?>', 
                                                '<?php echo $subTask['DueDate']; ?>', 
                                                '<?php echo $subTask['DueTime']; ?>'
                                            )">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="buttonDelete" onclick="event.stopPropagation(); deleteTask('<?php echo $subTask['TaskID']; ?>')">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endfor; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">No tasks available</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Pagination for Tasks Tab -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=1&tab=tasks" class="first-button" title="back to first">
                <i class='bx bx-chevrons-left'></i>
            </a>
        <?php endif; ?>

        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&tab=tasks" class="prev-button">
                <i class='bx bx-chevron-left'></i>
            </a>
        <?php endif; ?>

        <?php
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        for ($i = $start_page; $i <= $end_page; $i++):
        ?>
            <a href="?page=<?php echo $i; ?>&tab=tasks" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>&tab=tasks" class="next-button">
                <i class='bx bx-chevron-right'></i>
            </a>
        <?php endif; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $total_pages; ?>&tab=tasks" class="last-button">
                <i class='bx bx-chevrons-right'></i>
            </a>
        <?php endif; ?>
    </div>
</div>

            <!-- Modal -->
            <div id="taskModal" class="modal" >
                <div class="modal-content"style="width:1200px;">
                    <!-- Header with dropdown and close button -->
                    <div class="modal-header">
                        <!-- Dropdown for submission options -->
                        <div class="submitdropdown">
                            <button class="dropdown-toggle submitdropdown-toggle" type="button" onclick="toggleDropdown('submitDropdown')">
                                Assign <span class="arrow-down"></span>
                            </button>
                            <div id="submitDropdown" class="dropdown-menu submitdropdown-menu">
                                <button type="button" class="dropdown-item submitdropdown-item" onclick="submitForm('Assign')">Assign</button>
                                <button type="button" class="dropdown-item submitdropdown-item" onclick="submitForm('Schedule')">Schedule</button>
                                <button type="button" class="dropdown-item submitdropdown-item" onclick="submitForm('Draft')">Save as Draft</button>
                            </div>
                        </div>
                        <span class="close" onclick="closeModal()">&times;</span>
                    </div>

                    <div class="modal-container">
                        <h1 class="header-task">New Task</h1>
                        <form id="taskForm" action=" " method="post" enctype="multipart/form-data">
                            <div class="form-container">
                                <div class="form-left">
                                    <!-- Title and Instructions -->
                                    <div class="form-section">
                                        <label for="title">Title:</label>
                                        <div class="title-dropdown-container">
                                            <input type="text" id="title" name="title" required>
                                            <button class="title-dropdown-toggle" type="button" onclick="titletoggleDropdown('titleDropdown')">
                                                <i class="fas fa-caret-down"></i>
                                            </button>
                                            <div id="titleDropdown" class="title-dropdown-menu">
                                                <?php while ($row = $result->fetch_assoc()): ?>
                                                    <button type="button" class="title-dropdown-item" onclick="setTitle('<?php echo htmlspecialchars($row['Title'], ENT_QUOTES, 'UTF-8'); ?>')">
                                                        <?php echo htmlspecialchars($row['Title']); ?>
                                                    </button>
                                                <?php endwhile; ?>
                                            </div>
                                        </div>
                                        <label for="instructions">Instructions:</label>
                                        <div class="form-group editor-container">
                                            <div id="editor"></div>
                                            <textarea id="instructions" name="instructions" rows="4" required></textarea>
                                        </div>
                                    </div>    
                                </div>
                                
                                <div class="form-right">
                                    <!-- Department with checkboxes -->
                                    <div class="form-section">
                                        <label for="department">Department:</label>
                                        <div class="form-group">
                                            <div class="dropdown">
                                                <button class="dropdown-toggle" type="button" onclick="toggleDropdown('departmentDropdown')">Select Department</button>
                                                <div id="departmentDropdown" class="dropdown-menu" style="width:100%;">
                                                    <div class="checkbox-all-container">
                                                        
                                                    </div>
                                                    <?php foreach ($departments as $dept) : ?>
                                                    <div class="checkbox-container">
                                                        <input type="checkbox" class="custom-checkbox" name="department[]" value="<?= $dept['dept_ID'] ?>" onchange="updateGrades()">
                                                        <label><?= $dept['dept_name'] ?></label>
                                                    </div>
                                                    <?php endforeach; ?>
                                                    <button type="button" class="dropdown-okay-button" onclick="closeDropdown('departmentDropdown')">Okay</button>

                                                </div>
                                            </div>
                                        </div>

                                        <!-- Grade with checkboxes -->
                                        <label for="grade">Grade:</label>
                                        <div class="form-group">
                                            <div class="dropdown">
                                                <button class="dropdown-toggle" type="button" onclick="toggleDropdown('gradeDropdown')">Select Grade</button>
                                                <div id="gradeDropdown" class="dropdown-menu"style="width:150%; ">
                                                    <div class="checkbox-all-container">
                                                        <input type="checkbox" class="custom-checkbox checkbox-all" id="selectAllGrades" onclick="selectAll('gradeDropdown', 'grade[]')">
                                                        <label for="selectAllGrades">All</label>
                                                    </div>
                                                    <div id="gradesContainer">
                                                        <p>No grades available. Please select a department.</p>
                                                    </div>
                                                    <button type="button" class="dropdown-okay-button" onclick="closeDropdown('gradeDropdown')">Okay</button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Due Date -->
                                        <label for="due-date">Due Date:</label>
                                        <div class="form-group">
                                            <input type="date" id="due-date" name="due-date" required>
                                        </div>

                                        <!-- Due Time -->
                                        <label for="due-time">Due Time:</label>
                                        <div class="form-group">
                                            <input type="time" id="due-time" name="due-time" required>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                            <!-- Attachment -->
                            <label for="file">Attachment: <span style ="font-size:12px; color: grey;">(optional)</span></label>
                            <div class="form-section attachment">
                                <div class="form-group ">
                                    <input type="file" id="file" name="file[]" multiple onchange="displaySelectedFiles(event)"style="background-color:transparent;">                                   
                                </div>
                                <div id="fileContainer" class="file-container row" ></div>
                            </div>
                            <!-- Hidden input to track the action -->
                            <input type="hidden" id="taskAction" name="taskAction" value="assign">
                        </form>
                    </div>
                </div>
            </div>


            <!-- Update Modal -->
            <div id="editModal" class="update-modal" style="display: none;">
                <div class="update-modal-content">
                    <!-- Header with dropdown and close button -->
                    <div class="modal-header">
                        <!-- Dropdown for submission options -->
                        <div class="submitdropdown updatesubmitdropdown">
                            <button class="dropdown-toggle submitdropdown-toggle" type="button" onclick="toggleDropdown('updatesubmitDropdown')">
                                Assign <span class="arrow-down"></span>
                            </button>
                            <div id="updatesubmitDropdown" class="dropdown-menu submitdropdown-menu">
                                <button type="button" class="dropdown-item submitdropdown-item" onclick="updatesubmitForm('Assign')">Assign</button>
                                <button type="button" class="dropdown-item submitdropdown-item" onclick="updatesubmitForm('Draft')">Save as Draft</button>
                                <button type="button" class="dropdown-item submitdropdown-item" onclick="updatesubmitForm('Schedule')">Schedule</button>
                            </div>
                        </div>
                        <span class="close" onclick="closeEditModal()">&times;</span>
                    </div>
                    <div class="update-modal-container">
                        <h1 class="update-header-reminder">Edit Task</h1>
                        <form id="updateForm">
                            <input type="hidden" id="update_task_id" name="update_task_id">
                            <input type="hidden" id="actionType" name="actionType">
                            <div class="form-container">
                                <div class="form-left">
                                    <!-- Title and Instructions -->
                                    <div class="form-section">
                                        <label for="update_title">Title:</label>
                                        <div class="form-group">
                                            <input type="text" id="update_title" name="update_title" required>
                                        </div>
                                        <label for="update_instructions">Instructions:</label>
                                        <div class="form-group">
                                            <textarea id="update_instructions" name="update_instructions" rows="4" required></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-right">
                                    <div class="form-section">
                                    <label for="edit_department">Department:</label>
                                            <div class="form-group">
                                                <div class="dropdown">
                                                    <button class="dropdown-toggle" type="button" onclick="toggleDropdown('updatedepartmentDropdown')">Select Department</button>
                                                    <div id="updatedepartmentDropdown" class="dropdown-menu" style = "width: 100%; ">
                                                        <div class="checkbox-all-container">
                                                        </div>
                                                        <?php foreach ($departments as $dept) : ?>
                                                            <div class="checkbox-container">
                                                                <input type="checkbox" class="custom-checkbox" name="department[]" value="<?= $dept['dept_ID'] ?>" onchange="updateGradesInEditModal()">
                                                                <label><?= $dept['dept_name'] ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <button type="button" class="dropdown-okay-button" onclick="closeDropdown('updatedepartmentDropdown')">Okay</button>
                                                    </div>
                                                </div>
                                            </div>

                                           <!-- Update Grade with checkboxes -->
                                           <label for="update_grade">Grade:</label>
                                            <div class="form-group">
                                                <div class="dropdown">
                                                    <button class="dropdown-toggle" type="button" onclick="toggleDropdown('updategradeDropdown')">Select Grade</button>
                                                    <div id="updategradeDropdown" class="dropdown-menu" style = "width: 150%;">
                                                        <div class="checkbox-all-container">
                                                            <input type="checkbox"
                                                            class="custom-checkbox checkbox-all" name="update_grade[]" 
                                                            value="<?= $grade['grade_ID'] ?>" onchange="updateGradesInEditModal()">
                                                            <label for="EditselectAllGrades">All</label>
                                                        </div>
                                                        <div id="updategradesContainer">
                                                            <p>No grades available. Please select a department.</p>
                                                        </div>
                                                        <button type="button" class="dropdown-okay-button" onclick="closeDropdown('updategradeDropdown')">Okay</button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Update Due Date -->
                                            <label for="update_due_date">Due Date:</label>
                                            <div class="form-group">
                                                <input type="date" id="update_due_date" name="update_due_date" required>
                                            </div>
                                            
                                            <!-- Update Due Time -->
                                            <label for="update_due_time">Due Time:</label>
                                            <div class="form-group">
                                                <input type="time" id="update_due_time" name="update_due_time" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

           <!-- Schedule Modal -->
            <div id="scheduleModal" class="scheduleModal">
                <div class="modal-content small-modal">
                    <h2>Schedule Task</h2>
                    <label for="schedule-date">Schedule Date:</label>
                    <input type="date" id="schedule-date" name="schedule-date" required>
                    <label for="schedule-time">Schedule Time:</label>
                    <input type="time" id="schedule-time" name="schedule-time" required>
                    <div class="button-container">
                        <span class="cancel" onclick="closeScheduleModal()">Cancel</span>
                        <button type="button" class="confirm-button" onclick="confirmSchedule()">Confirm</button>
                    </div>
                </div>
            </div>

           <!-- Update Schedule Modal -->
           <div id="updatescheduleModal" class="updatescheduleModal">
                <div class="modal-content small-modal">
                    <h2>Schedule Task</h2>
                    <label for="update_schedule_date">Schedule Date:</label>
                    <input type="date" id="update_schedule_date" name="update_schedule_date" required>
                    <label for="update_schedule_time">Schedule Time:</label>
                    <input type="time" id="update_schedule_time" name="update_schedule_time" required>
                    <div class="button-container">
                        <span class="cancel" onclick="closeUpdateScheduleModal()">Cancel</span>
                        <button type="button" class="confirm-button" onclick="updateconfirmSchedule()">Confirm</button>
                    </div>
                </div>
            </div>
        

            <!-- Task View Modal -->
            <div id="taskViewModal" class="modal">
                <div class="task-modal-content">
                    <span class="task-modal-close" onclick="closeViewModal()">&times;</span>
                    
                    <div class="task-view-header">
                        <h2 id="taskViewTitle"></h2>
                    </div>
                    
                    <div class="task-meta-container">
                        <div class="task-meta-item">
                            <span class="task-meta-label">Status</span>
                            <span id="taskViewStatus" class="task-meta-value"></span>
                        </div>
                        <div class="task-meta-item">
                            <span class="task-meta-label">Department</span>
                            <span id="taskViewDepartment" class="task-meta-value"></span>
                        </div>
                        <div class="task-meta-item">
                            <span class="task-meta-label">Grade/Section</span>
                            <span id="taskViewGradeSection" class="task-meta-value"></span>
                        </div>
                        <div class="task-meta-item">
                            <span class="task-meta-label">Due Date</span>
                            <span id="taskViewDueDate" class="task-meta-value"></span>
                        </div>
                    </div>
                    
                    <div class="task-content-area">
                        <h3>Task Details</h3>
                        <div id="taskViewContent" class="task-view-content"></div>
                    </div>

                    <div class="task-view-footer">
                        <button id="viewFullTaskBtn" class="buttonViewFullTask" onclick="viewFullTask()">
                            <i class="fas fa-external-link-alt"></i> View Full Task
                        </button>
                    </div>
                </div>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->
     
    <script>
        function setTitle(title) {
            // Assuming you want to set the selected title in an input field or display it somewhere
            document.getElementById('selectedTitle').innerText = title; // Adjust based on where you want to display it
        }
    </script>
    <script>
        // Filter by Status
        function filterByStatus() {
            const statusFilter = document.getElementById('statusFilter').value; // Get selected value
            const rows = document.querySelectorAll('.task-row'); // Get all task rows
            
            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status'); // Get the row's status attribute
                if (statusFilter === '' || rowStatus === statusFilter) {
                    row.style.display = ''; // Show the row
                } else {
                    row.style.display = 'none'; // Hide the row
                }
            });
        }
    </script>

    <script>
        function displaySelectedFiles(event) {
            const fileContainer = document.getElementById('fileContainer');
            fileContainer.innerHTML = ''; // Clear existing file containers

            for (const file of event.target.files) {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item col-md-3'; // Bootstrap column class

                const fileName = document.createElement('span');
                fileName.className = 'file-name';
                fileName.textContent = file.name;

                const removeButton = document.createElement('button');
                removeButton.className = 'remove-file btn btn-danger btn-sm';
                removeButton.textContent = 'x';
                removeButton.onclick = () => removeFile(fileItem);

                fileItem.appendChild(fileName);
                fileItem.appendChild(removeButton);

                fileContainer.appendChild(fileItem);
            }
        }

        function removeFile(fileItem) {
            const fileInput = document.getElementById('file');
            const files = Array.from(fileInput.files);
            const fileName = fileItem.querySelector('.file-name').textContent;

            // Find index of the file to be removed
            const index = files.findIndex(file => file.name === fileName);
            if (index > -1) {
                // Remove the file from the FileList
                const dt = new DataTransfer();
                files.splice(index, 1);
                files.forEach(file => dt.items.add(file));
                fileInput.files = dt.files;

                // Remove the file item from the DOM
                fileItem.remove();
            }
        }



    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Get the current date in the required format
            function getCurrentDate() {
            const today = new Date();
            const year = today.getFullYear();
            let month = today.getMonth() + 1;
            let day = today.getDate();

            // Add leading zeros for months and days less than 10
            month = month < 10 ? '0' + month : month;
            day = day < 10 ? '0' + day : day;

            return `${year}-${month}-${day}`;
            }

            // Set the min attribute of the date picker to the current date
            document.getElementById('due-date').min = getCurrentDate();

            // Add an event listener to the date picker
            document.getElementById('due-date').addEventListener('input', function () {
            // Get the selected date
            const selectedDate = this.value;

            // Check if the selected date is in the past
            if (selectedDate < getCurrentDate()) {
                alert('Please select a future date.');
                this.value = getCurrentDate(); // Reset the value to the current date
            }
            });
        });
    </script>
    <script>
    // JavaScript to switch tabs
        function switchTab(tab) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('tab', tab);
            window.location.search = urlParams.toString();
        }
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const approveButton = document.getElementById('approveSelected');
        const rejectButton = document.getElementById('rejectSelected');

        fetchTasks();

        function fetchTasks() {
            fetch('taskApproval.php')
                .then(response => response.json())
                .then(tasks => {
                    const taskTableBody = document.querySelector('#taskTable tbody');
                    taskTableBody.innerHTML = '';

                    if (tasks.length === 0) {
                        // Show no-task message
                        const noTaskMessage = document.createElement('tr');
                        noTaskMessage.innerHTML = '<td colspan="6" style="text-align: center; color: grey;">No Pending Task Available</td>';
                        taskTableBody.appendChild(noTaskMessage);

                        // Hide the buttons
                        approveButton.style.display = 'none';
                        rejectButton.style.display = 'none';
                    } else {
                        // Populate the task table
                        tasks.forEach(task => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td><input type="checkbox" class="task-checkbox" data-task-id="${task.TaskID}"></td>
                                <td>${task.Title}</td>
                                <td>${task.taskContent}</td>
                                <td>${task.Type}</td>
                                <td>${task.fname} ${task.lname}</td>
                                <td>${task.DueDate}</td>
                            `;
                            taskTableBody.appendChild(row);
                        });

                        // Show the buttons
                        approveButton.style.display = 'inline-block';
                        rejectButton.style.display = 'inline-block';
                    }
                })
                .catch(error => console.error('Error fetching tasks:', error));
        }

        document.getElementById('approveSelected').addEventListener('click', () => {
            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to approve the selected task(s).",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, approve it!',
                cancelButtonText: 'No, cancel!',
            }).then((result) => {
                if (result.isConfirmed) {
                    handleBatchAction('approve');
                }
            });
        });

        document.getElementById('rejectSelected').addEventListener('click', () => {
            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to reject the selected task(s).",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, reject it!',
                cancelButtonText: 'No, cancel!',
            }).then((result) => {
                if (result.isConfirmed) {
                    handleBatchAction('reject');
                }
            });
        });

        function handleBatchAction(action) {
            const selectedTasks = Array.from(document.querySelectorAll('.task-checkbox:checked')).map(checkbox => checkbox.dataset.taskId);

            if (selectedTasks.length === 0) {
                showNotification('No tasks selected.', 'error');
                return;
            }

            fetch('taskApproval.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    taskIDs: JSON.stringify(selectedTasks),
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                // Show SweetAlert for success message
                Swal.fire({
                    title: action === 'approve' ? 'Approved!' : 'Rejected!',
                    text: `The selected task(s) have been successfully ${action}d.`,
                    icon: data.status === 'success' ? 'success' : 'error',
                    timer: 2000, // Show for 2 seconds
                    showConfirmButton: false,
                });

                // Refresh tasks after the alert
                setTimeout(() => {
                    fetchTasks(); // Refresh task list after action
                }, 2000); // Refresh after 2.5 seconds to allow alert to show
            })
            .catch(error => {
                console.error('Error during batch action:', error);
                showNotification('An error occurred while processing your request.', 'error');
            });
        }
    });
</script>

    <script>
        ClassicEditor
            .create(document.querySelector('#editor'), {
                placeholder: 'Enter details here...' // Set placeholder text
            })
            .then(editor => {
                // Sync editor data to the hidden textarea
                editor.model.document.on('change:data', () => {
                    document.querySelector('#instructions').value = editor.getData();
                });
                
                // Apply a fixed width to the editor's outermost container
                editor.ui.view.editable.element.closest('.ck-editor').style.maxWidth = '560px';
            })
            .catch(error => {
                console.error(error);
            });
    </script>
    

    <script>
        // Function to handle form submission based on selected action
        function submitForm(action) {
            // Update the hidden input with the selected action
            document.getElementById('taskAction').value = action;

            // Update the dropdown button text to reflect the chosen action
            const dropdownButton = document.querySelector('.submitdropdown-toggle');
            dropdownButton.innerHTML = action.charAt(0).toUpperCase() + action.slice(1) + ' <span class="arrow-down"></span>';

            // Close the dropdown after selection
            toggleDropdown('submitDropdown');

            // Check if the action is assign, draft, or schedule
            if (action === 'Assign') {
                // Submit the form via AJAX
                submitTaskForm();
            } else if (action === 'Schedule') {
                // Show modal for schedule date and time
                document.getElementById('scheduleModal').style.display = 'block';
            } else {
                // For draft, submit the form directly
                submitTaskForm_Draft();
            }
        }



        function confirmSchedule() {
            // Get values from the Schedule modal
            const scheduleDate = document.getElementById('schedule-date').value;
            const scheduleTime = document.getElementById('schedule-time').value;

            // Check if schedule date and time are provided
            if (!scheduleDate || !scheduleTime) {
                alert("Please fill in both the schedule date and time.");
                return;
            }

            // Combine date and time into a Date object (for validation)
            const scheduledDateTime = new Date(`${scheduleDate}T${scheduleTime}`);
            const currentDateTime = new Date();

            // Ensure the scheduled date and time are in the future
            if (scheduledDateTime <= currentDateTime) {
                alert("Please select a future date and time.");
                return;
            }

            // Update the main form with schedule date and time
            const taskForm = document.getElementById('taskForm');
            const scheduleDateInput = document.createElement('input');
            scheduleDateInput.type = 'hidden';
            scheduleDateInput.name = 'schedule-date';
            scheduleDateInput.value = scheduleDate;
            
            const scheduleTimeInput = document.createElement('input');
            scheduleTimeInput.type = 'hidden';
            scheduleTimeInput.name = 'schedule-time';
            scheduleTimeInput.value = scheduleTime;
            
            taskForm.appendChild(scheduleDateInput);
            taskForm.appendChild(scheduleTimeInput);

            // Submit the form after updating the data
            submitTaskForm_Schedule();

            // Close the schedule modal
            closeScheduleModal();
        }


        function openScheduleModal() {
            document.getElementById('scheduleModal').style.display = 'block';
        }

        function closeScheduleModal() {
            document.getElementById('scheduleModal').style.display = 'none';
        }


        // Function to select/deselect all checkboxes and trigger updateGrades
        function selectAll(containerId, checkboxName) {
            const container = document.getElementById(containerId);
            const checkboxes = container.querySelectorAll(`input[name="${checkboxName}"]`);
            const selectAllCheckbox = container.querySelector('.checkbox-all');

            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });

            if (checkboxName === 'department[]') {
                updateGrades();
            }
        }

        // Function to fetch and update available grades based on selected departments
        function updateGrades() {
            const selectAllDepartmentsCheckbox = document.getElementById('selectAllDepartments');
            let selectedDepartments;

            if (selectAllDepartmentsCheckbox && selectAllDepartmentsCheckbox.checked) {
                selectedDepartments = Array.from(document.querySelectorAll('input[name="department[]"]'))
                    .map(input => input.value)
                    .join(',');
            } else {
                selectedDepartments = Array.from(document.querySelectorAll('input[name="department[]"]:checked'))
                    .map(input => input.value)
                    .join(',');
            }

            const gradesContainer = document.getElementById('gradesContainer');
            gradesContainer.innerHTML = '<p>Loading grades...</p>';

            if (selectedDepartments) {
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({ department_ids: selectedDepartments }),
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    gradesContainer.innerHTML = ''; // Clear existing grades

                    if (data.length > 0) {
                        data.forEach(grade => {
                        const checkbox = document.createElement('div');
                        checkbox.className = 'checkbox-container'; 
                        checkbox.innerHTML = `
                            <input type="checkbox" class="custom-checkbox" name="grade[]" value="${grade.ContentID}">
                            <label class="grade-title">${grade.Title} - ${grade.Captions}</label> 
                        `;
                        gradesContainer.appendChild(checkbox);
                        });
                    } else {
                        gradesContainer.innerHTML = '<p>No grades available for the selected departments.</p>';
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    gradesContainer.innerHTML = '<p>Error loading grades. Please try again.</p>';
                });
            } else {
                gradesContainer.innerHTML = '<p>No grades available. Please select a department.</p>';
            }
        }

        function submitTaskForm() {
            // Get form data
            const formData = new FormData(document.getElementById('taskForm'));

            // Make an AJAX request to your PHP script (upload_task.php)
            fetch('../Admin/upload_task.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Success alert with SweetAlert
                    Swal.fire({
                        icon: 'success',
                        title: 'Task Created',
                        text: 'Your task has been created successfully!',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Reload the page to display the new task
                            location.reload();
                        }
                    });
                } else {
                    // Error alert with SweetAlert
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while creating the task. Please try again.'
                });
            });
        }

        function submitTaskForm_Schedule() {
            // Get form data
            const formData = new FormData(document.getElementById('taskForm'));

            // Make an AJAX request to your PHP script (upload_task.php)
            fetch('upload_task.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Success alert with SweetAlert
                    Swal.fire({
                        icon: 'success',
                        title: 'Task Scheduled',
                        text: 'Your task has been scheduled successfully!',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Reload the page to display the new task
                            location.reload();
                        }
                    });
                } else {
                    // Error alert with SweetAlert
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while creating the task. Please try again.'
                });
            });
        }


        function submitTaskForm_Draft() {
            // Get form data
            const formData = new FormData(document.getElementById('taskForm'));

            // Make an AJAX request to your PHP script (upload_task.php)
            fetch('upload_task.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Success alert with SweetAlert
                    Swal.fire({
                        icon: 'success',
                        title: 'Task Draft',
                        text: 'Your task has been drafted successfully!',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Reload the page to display the new task
                            location.reload();
                        }
                    });
                } else {
                    // Error alert with SweetAlert
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while creating the task. Please try again.'
                });
            });
        }


        function toggleDropdown(dropdownId) {
            const dropdown = document.getElementById(dropdownId);
            dropdown.classList.toggle('show');
        }



        // Close the dropdown if the user clicks outside of it
        window.onclick = function(event) {
            if (!event.target.matches('.dropdown-toggle')) {
                const dropdowns = document.getElementsByClassName("dropdown-menu");
                for (let i = 0; i < dropdowns.length; i++) {
                    const openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
// Close the dropdown when "Okay" button is clicked
function closeDropdown(dropdownId) {
            const dropdown = document.getElementById(dropdownId);

            if (dropdown) {
                dropdown.classList.remove('show');
                if (currentOpenDropdown === dropdown) {
                    currentOpenDropdown = null;
                }
            }
        }

        function openModal() {
            document.getElementById('taskModal').style.display = 'block';
        }


        function closeModal() {
            document.getElementById('taskModal').style.display = 'none';
        }




        function updateGradesInEditModal(selectedGrades = []) {
            const selectAllDepartmentsCheckbox = document.getElementById('selectAllDepartments');
            let selectedDepartments;

            // Determine which departments to include based on "Select All" checkbox
            if (selectAllDepartmentsCheckbox && selectAllDepartmentsCheckbox.checked) {
                selectedDepartments = Array.from(document.querySelectorAll('#updatedepartmentDropdown input[name="department[]"]'))
                    .map(input => input.value)
                    .join(',');
            } else {
                selectedDepartments = Array.from(document.querySelectorAll('#updatedepartmentDropdown input[name="department[]"]:checked'))
                    .map(input => input.value)
                    .join(',');
            }

            const updategradesContainer = document.getElementById('updategradesContainer');

            if (selectedDepartments) {
                // Fetch grades based on selected departments
                fetch('', { 
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ department_ids: selectedDepartments })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    updategradesContainer.innerHTML = ''; // Clear existing grades
                    if (data.length > 0) {
                        data.forEach(grade => {
                            const isChecked = selectedGrades.includes(grade.Title) ? 'checked' : ''; // Check if this grade is selected
                            const checkbox = document.createElement('div');
                            checkbox.className = 'checkbox-container';
                            checkbox.innerHTML = `
                                <input type="checkbox" name="grade[]" value="${grade.ContentID}" ${isChecked} 
                                style="outline: none !important; box-shadow: none !important;">
                                <label style="font-weight: bold;">${grade.Title} - ${grade.Captions}</label>`;
                            updategradesContainer.appendChild(checkbox);
                        });

                        const selectAllGradesCheckbox = document.getElementById('selectAllGrades');
                        if (!selectAllGradesCheckbox) {
                            const selectAllLabel = document.createElement('label');
                            selectAllLabel.innerHTML = `
                                <input type="checkbox" id="selectAllGrades" onclick="selectAll('updategradesContainer', 'grade[]')"> All Grades`;
                            updategradesContainer.insertBefore(selectAllLabel, updategradesContainer.firstChild);
                        }
                    } else {
                        updategradesContainer.innerHTML = '<p>No grades available for the selected departments.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching grades:', error);
                    updategradesContainer.innerHTML = '<p>Error loading grades. Please try again.</p>';
                });
            } else {
                updategradesContainer.innerHTML = '<p>No grades available. Please select a department.</p>';
            }
        }


        function updateconfirmSchedule() {
            // Get values from the Schedule modal
            const scheduleDate = document.getElementById('update_schedule_date').value;
            const scheduleTime = document.getElementById('update_schedule_time').value;

            // Check if schedule date and time are provided
            if (!scheduleDate || !scheduleTime) {
                alert("Please fill in both the schedule date and time.");
                return;
            }

            // Combine date and time into a Date object (for validation)
            const scheduledDateTime = new Date(`${scheduleDate}T${scheduleTime}`);
            const currentDateTime = new Date();

            // Ensure the scheduled date and time are in the future
            if (scheduledDateTime <= currentDateTime) {
                alert("Please select a future date and time.");
                return;
            }

            // Update the main form with schedule date and time
            const taskForm = document.getElementById('updateForm');
            
            // Add the schedule date and time to the form
            const scheduleDateInput = document.createElement('input');
            scheduleDateInput.type = 'hidden';
            scheduleDateInput.name = 'update_schedule_date';
            scheduleDateInput.value = scheduleDate;
            
            const scheduleTimeInput = document.createElement('input');
            scheduleTimeInput.type = 'hidden';
            scheduleTimeInput.name = 'update_schedule_time';
            scheduleTimeInput.value = scheduleTime;

            taskForm.appendChild(scheduleDateInput);
            taskForm.appendChild(scheduleTimeInput);

            // Call the function to submit the form
            updateTask_Schedule();

            // Close the modal after submission
            closeUpdateScheduleModal();
        }



        function openUpdateScheduleModal() {
            document.getElementById('updatescheduleModal').style.display = 'block';
        }

        function closeUpdateScheduleModal() {
            document.getElementById('updatescheduleModal').style.display = 'none';
        }


        function editTask(taskID, title, content, deptName, gradeTitles, dueDate) {
    document.getElementById('update_task_id').value = taskID;
    document.getElementById('update_title').value = title;

    // Strip HTML tags and newline characters from the content
    const sanitizedContent = content.replace(/<[^>]*>/g, '').replace(/\n/g, ''); // Remove all HTML tags and newlines
    document.getElementById('update_instructions').value = sanitizedContent;

    // Set the due date and time
    const dueDateObj = new Date(dueDate);
    document.getElementById('update_due_date').value = dueDateObj.toISOString().split('T')[0]; // Format to YYYY-MM-DD
    document.getElementById('update_due_time').value = dueDateObj.toTimeString().split(' ')[0]; // Format to HH:mm:ss

    // Set selected department and update grades
    const departmentSelect = document.querySelectorAll('input[name="department[]"]');
    departmentSelect.forEach(option => {
        option.checked = false; // Clear previous selections
        if (option.nextElementSibling.innerText.trim() === deptName) {
            option.checked = true; // Check this department
        }
    });

    // Pass the selected grades to the function
    updateGradesInEditModal(gradeTitles);

    document.getElementById('editModal').style.display = 'block'; // Show the edit modal
}



        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }


        // Submit form based on selected action (Assign, Draft, or Schedule)
        function updatesubmitForm(actionType) {
            const form = document.getElementById('updateForm');
            const title = document.getElementById('update_title').value;
            const content = document.getElementById('update_instructions').value;
            const dueDate = document.getElementById('update_due_date').value;
            const dueTime = document.getElementById('update_due_time').value;

            // Validation check for required fields
            if (!title || !content || !dueDate || !dueTime) {
                alert("Please fill out all the required fields.");
                return;
            }

            // Set the action type
            document.getElementById('actionType').value = actionType;

            // Handle form submission based on the action (Assign, Draft, Schedule)
            if (actionType === 'Assign') {
                form.action = 'update_task.php';
                updateTask(); // Submit the form for Assign
            } else if (actionType === 'Draft') {
                form.action = 'update_task.php';
                updateTask_Draft(); // Submit the form for Draft
            } else if (actionType === 'Schedule') {
                // Open the schedule modal for scheduling
                openUpdateScheduleModal();
            }
        }



        // Toggle the submit dropdown visibility
        function toggleSubmitDropdown() {
            var dropdown = document.getElementById('submitDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        function updateTask() {
            const form = document.getElementById('updateForm');
            const formData = new FormData(form);

            // Log the grades being sent
            const grades = formData.getAll('grade[]');
            console.log('Selected Grades:', grades); // Log selected grades to confirm

            // Log all form data for debugging
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }

            fetch('update_task.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Update Response:', data); // Log the response for debugging
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Task Updated',
                        text: 'Your task has been assigned successfully!',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload(); // Reload page to reflect changes
                    });
                    closeEditModal(); // Close modal after successful update
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while updating the task.'
                });
            });
        }

        function updateTask_Draft() {
            const form = document.getElementById('updateForm');
            const formData = new FormData(form);

            // Log the grades being sent
            const grades = formData.getAll('grade[]');
            console.log('Selected Grades:', grades); // Log selected grades to confirm

            // Log all form data for debugging
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }

            fetch('update_task.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Update Response:', data); // Log the response for debugging
                if (data.success) {
                    // Success alert with SweetAlert
                    Swal.fire({
                        icon: 'success',
                        title: 'Task Updated',
                        text: 'Your task has been drafted successfully!',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Reload the page to display the new task
                            location.reload();
                        }
                    });
                    closeEditModal(); // Close modal after successful update
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while updating the task.'
                });
            });
        }

        function updateTask_Schedule() {
            const form = document.getElementById('updateForm');
            const formData = new FormData(form);

            // Log the grades being sent
            const grades = formData.getAll('grade[]');
            console.log('Selected Grades:', grades); // Log selected grades to confirm

            // Log all form data for debugging
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }

            fetch('update_task.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Update Response:', data); // Log the response for debugging
                if (data.success) {
                    // Success alert with SweetAlert
                    Swal.fire({
                        icon: 'success',
                        title: 'Task Updated',
                        text: 'Your task has been scheduled successfully!',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Reload the page to display the new task
                            location.reload();
                        }
                    });
                    closeEditModal(); // Close modal after successful update
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while updating the task.'
                });
            });
        }

        function deleteTask(taskId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('delete_task.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ task_id: taskId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Deleted!', 'Your task has been deleted.', 'success');
                            // Reload or update the task table
                            document.querySelector(`button[onclick="deleteTask('${taskId}')"]`).closest('tr').remove();
                        } else {
                            Swal.fire('Error!', data.message || 'Failed to delete the task.', 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error!', 'Something went wrong. Please try again later.', 'error');
                        console.error(error);
                    });
                }
            });
        }

        // JavaScript for search functionality
        function toggleSearchBar() {
            var searchBar = document.querySelector('.search-bar');
            searchBar.style.display = searchBar.style.display === 'none' || searchBar.style.display === '' ? 'block' : 'none';
        }

        document.getElementById('searchInput').addEventListener('input', function() {
            var searchValue = this.value.trim().toLowerCase();
            var rows = document.querySelectorAll('#taskTableBody tr');

            rows.forEach(function(row) {
                var title = row.getElementsByTagName('td')[0]; // Assuming title is the first column
                if (title) {
                    var textValue = title.textContent || title.innerText;
                    if (textValue.toLowerCase().indexOf(searchValue) > -1) {
                        row.style.display = ''; // Show row if the search term matches
                    } else {
                        row.style.display = 'none'; // Hide row if no match
                    }
                }
            });
        });



        function autoUpdateScheduledTasks() {
            const apiUrl = 'auto_assign_tasks.php';
            console.log('Fetching from URL:', apiUrl); // Log the API URL being fetched

            fetch(apiUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data); // Add logging here
                    if (data.status === 'success') {
                        console.log('Scheduled tasks have been assigned:', data.assigned_tasks);
                        Swal.fire({
                            icon: 'success',
                            title: 'Tasks Assigned',
                            text: 'Scheduled tasks have been successfully assigned!',
                            confirmButtonText: 'OK'
                        });
                    } else if (data.status === 'no_tasks') {
                        console.log(data.message); // Log no tasks to assign
                    }
                })
                .catch(error => {
                    console.error('Error:', error); // Log the error
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while updating scheduled tasks.'
                    });
                });
        }

        //JavaScript code for autoUpdateScheduledTasks function
        window.addEventListener('load', function() {
            autoUpdateScheduledTasks();
        });
        
        setInterval(autoUpdateScheduledTasks, 60000);

        // Optionally, trigger the check immediately when the script loads
        autoUpdateScheduledTasks();


       function titletoggleDropdown(dropdownId) {
        document.getElementById(dropdownId).classList.toggle("show");
    }

    
    function setTitle(title, taskID) {
        document.getElementById('title').value = title;
        // Store the TaskID in a hidden input if needed
        var taskIDInput = document.createElement('input');
        taskIDInput.type = 'hidden';
        taskIDInput.name = 'taskID';
        taskIDInput.value = taskID;
        document.getElementById('titleDropdown').appendChild(taskIDInput);

        // Close the dropdown after setting the title
        var dropdowns = document.getElementsByClassName("title-dropdown-menu");
        for (var i = 0; i < dropdowns.length; i++) {
            var openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) {
                openDropdown.classList.remove('show');
            }
        }
    }


    window.onclick = function(event) {
        if (!event.target.closest('.title-dropdown-container')) {
            var dropdowns = document.getElementsByClassName("title-dropdown-menu");
            for (var i = 0; i < dropdowns.length; i++) {
            var openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) {
                openDropdown.classList.remove('show');
            }
            }
        }
    }
    </script>

    <script>
        // Global variables to store task data
        let currentTaskId = null;
        let currentContentId = null;

        // Function to open task view modal
        function viewTask(taskId, title, content, department, gradeSection, dueDate, dueTime, status, contentId = '') {
            console.log('Received ContentID:', contentId);  // Debug line
            // Store the IDs for the view button
            currentTaskId = taskId;
            currentContentId = contentId || '';
            
            // Format the date and time if needed
            const formattedDate = new Date(dueDate).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
            const formattedTime = new Date(`1970-01-01T${dueTime}`).toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });

            // Populate the modal with task details
            document.getElementById('taskViewTitle').textContent = title;
            document.getElementById('taskViewContent').innerHTML = content;
            document.getElementById('taskViewDepartment').textContent = department;
            document.getElementById('taskViewGradeSection').textContent = gradeSection;
            document.getElementById('taskViewDueDate').textContent = `${formattedDate} at ${formattedTime}`;
            document.getElementById('taskViewStatus').textContent = status;
            document.getElementById('taskViewStatus').style.color = 
                status === 'Assign' ? 'green' : (status === 'Schedule' ? 'blue' : 'grey');

            // Show the modal
            document.getElementById('taskViewModal').style.display = 'block';

            // Enable/disable view button based on contentId
            const viewBtn = document.getElementById('viewFullTaskBtn');
            viewBtn.onclick = function() {
                viewFullTask(currentTaskId, currentContentId);
            };
        }

        // Modified viewFullTask function
        function viewFullTask(taskId = null, contentId = null) {
            const useTaskId = taskId || currentTaskId;
            const useContentId = contentId || currentContentId;
            
            if (!useTaskId) return;
            
            // Construct URL with parameters
            let url = `taskdetails.php?task_id=${encodeURIComponent(useTaskId)}`;
            
            // Only add content_id if it exists and isn't empty
            if (useContentId && useContentId.trim() !== '') {
                url += `&content_id=${encodeURIComponent(useContentId)}`;
            }
            
            // Navigate to the task details page
            window.location.href = url;
        }

        // Close modal function
        function closeViewModal() {
            currentTaskId = null;
            currentContentId = null;
            document.getElementById('taskViewModal').style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('taskViewModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>

    <script>
        // Prevent row click when clicking action buttons
        document.querySelectorAll('.button-group button').forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
