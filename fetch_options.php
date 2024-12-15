<?php
session_start();
include 'connection.php'; // Include your database connection file

$response = [];

// Fetch School Years
$schoolYears = [];
$result = $conn->query("SELECT School_Year_ID, Year_Range FROM schoolyear");
while ($row = $result->fetch_assoc()) {
    $schoolYears[] = $row;
}

// Fetch Grades for the specific chairperson
$grades = [];
if (isset($_SESSION['user_id'])) {
    $userID = $_SESSION['user_id'];

    // Use prepared statement for SQL query
    $query = "
        SELECT g.Grade_ID, g.Grade_Level
        FROM grade g
        INNER JOIN chairperson c ON g.Grade_ID = c.Grade_ID
        WHERE c.UserID = ?
    ";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $userID); // Bind the userID as an integer
        $stmt->execute(); // Execute the statement
        $result = $stmt->get_result(); // Get the result

        while ($row = $result->fetch_assoc()) {
            $grades[] = $row;
        }

        $stmt->close(); // Close the statement
    } else {
        die("Error preparing the SQL statement: " . $conn->error);
    }
}

$response['schoolYears'] = $schoolYears;
$response['grades'] = $grades;

echo json_encode($response);
?>
