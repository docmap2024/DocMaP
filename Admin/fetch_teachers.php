<?php
session_start();

// Include the database connection file
require_once 'connection.php'; // Adjust the path as needed

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the teacher ID and department ID from the form data
    $teacherID = $_POST['teacherID'];
    $deptID = $_POST['deptID'];

    // Check if there is already a department head for the given deptID
    $stmt = $conn->prepare("SELECT UserID FROM useracc WHERE dept_ID = ? AND UserID != ?");
    $stmt->bind_param("is", $deptID, $teacherID); // Search for other department heads with the same deptID
    $stmt->execute();
    $result = $stmt->get_result();

    // If a department head exists, set their dept_ID to NULL and update their role to "Teacher"
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $existingHeadID = $row['UserID'];

            // Set the existing department head's dept_ID to NULL and change their role to "Teacher"
            $updateStmt = $conn->prepare("UPDATE useracc SET dept_ID = NULL, role = 'Teacher' WHERE UserID = ?");
            $updateStmt->bind_param("s", $existingHeadID);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }

    // Update the department ID for the selected teacher and set their role to "Department Head"
    $stmt = $conn->prepare("UPDATE useracc SET dept_ID = ?, role = 'Department Head' WHERE UserID = ?");
    $stmt->bind_param("is", $deptID, $teacherID); // 'i' for integer, 's' for string

    if ($stmt->execute()) {
        echo 'Success!'; // Return success message
    } else {
        echo 'Error updating department head: ' . $stmt->error; // Return error message
    }

    // Close the statement
    $stmt->close();
}

// Close the connection
$conn->close();
?>
