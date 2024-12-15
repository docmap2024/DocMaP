<?php
session_start();
include 'connection.php';

header('Content-Type: application/json'); // Ensure we're sending JSON

// Check if dept_ID is set in the session
if (!isset($_SESSION['dept_ID'])) {
    echo json_encode(['error' => 'dept_ID not set in session.']);
    exit;
}

// Get dept_ID from session
$dept_ID = $_SESSION['dept_ID'];

// Get user ID from session (you can use this if necessary for further processing)
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
                    m.LowestScore, m.MPS, sy.Year_Range AS SY,
                    CONCAT(ua.fname, ' ', ua.lname) AS SubTeacher
             FROM mps m
             INNER JOIN quarter q ON m.Quarter_ID = q.Quarter_ID
             INNER JOIN feedcontent fc ON m.ContentID = fc.ContentID
             INNER JOIN schoolyear sy ON q.School_Year_ID = sy.School_Year_ID
             INNER JOIN useracc ua ON m.UserID = ua.UserID
             WHERE fc.dept_ID = ?"; // Filter by dept_ID

// Apply additional filters
if (!empty($school_year)) {
    $queryMPS .= " AND q.School_Year_ID = ?";
}
if (!empty($quarter)) {
    $queryMPS .= " AND q.Quarter_ID = ?";
}
if (!empty($grade_level)) {
    $queryMPS .= " AND m.ContentID = ?";
}
if (!empty($instructor)) {
    $queryMPS .= " AND m.UserID = ?";
}

// Prepare the statement
$stmt = $conn->prepare($queryMPS);

// Bind parameters
$params = [$dept_ID];
if (!empty($school_year)) $params[] = $school_year;
if (!empty($quarter)) $params[] = $quarter;
if (!empty($grade_level)) $params[] = $grade_level;
if (!empty($instructor)) $params[] = $instructor;

// Use 's' for string and 'i' for integer when binding params
$types = str_repeat('s', count($params));
$stmt->bind_param($types, ...$params);

// Execute the statement
$stmt->execute();
$resultMPS = $stmt->get_result();

if (!$resultMPS) {
    echo json_encode(['error' => 'Query failed: ' . $stmt->error]);
    exit;
}

// Fetch and return filtered data as JSON
$data = [];
while ($row = $resultMPS->fetch_assoc()) {
    $data[] = $row;
}

// Output the data
echo json_encode($data);

// Close the statement and connection
$stmt->close();
$conn->close();
?>
