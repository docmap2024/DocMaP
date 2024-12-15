<?php
session_start();
include 'connection.php';

header('Content-Type: application/json'); // Ensure we're sending JSON

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get filter values from the request
$school_year = isset($_POST['school_year']) ? $_POST['school_year'] : '';
$quarter = isset($_POST['quarter']) ? $_POST['quarter'] : '';
$grade_level = isset($_POST['grade_level']) ? $_POST['grade_level'] : '';
$instructor = isset($_POST['instructor']) ? $_POST['instructor'] : ''; // Get instructor filter

// Build the base query
$queryMPS = "SELECT m.mpsID, m.UserID, m.ContentID, q.School_Year_ID, q.Quarter_Name, 
                    CONCAT(fc.Title, ' - ', fc.Captions) AS GradeSection, 
                    m.TotalNumOfStudents, m.TotalNumTested, m.HighestScore, 
                    m.LowestScore, m.MPS,sy.Year_Range AS SY,
                    CONCAT(ua.fname, ' ', ua.lname) AS SubTeacher
             FROM mps m
             INNER JOIN quarter q ON m.Quarter_ID = q.Quarter_ID
             INNER JOIN feedcontent fc ON m.ContentID = fc.ContentID
             INNER JOIN schoolyear sy ON q.School_Year_ID = sy.School_Year_ID
             INNER JOIN useracc ua ON m.UserID = ua.UserID
             ";

// Apply filters
if (!empty($school_year)) {
    $queryMPS .= " AND q.School_Year_ID = '$school_year'";
}
if (!empty($quarter)) {
    $queryMPS .= " AND q.Quarter_ID = '$quarter'";
}
if (!empty($grade_level)) {
    $queryMPS .= " AND m.ContentID = '$grade_level'";
}
if (!empty($instructor)) {
    $queryMPS .= " AND m.UserID = '$instructor'"; // Assuming UserID refers to the instructor
}

// Execute the query
$resultMPS = mysqli_query($conn, $queryMPS);

if (!$resultMPS) {
    echo json_encode(['error' => 'Query failed: ' . mysqli_error($conn)]);
    exit;
}

// Fetch and return filtered data as JSON
$data = [];
if ($resultMPS) {
    while ($row = mysqli_fetch_assoc($resultMPS)) {
        $data[] = $row;
    }
}

// Output the data
echo json_encode($data);
?>
