<?php
include 'connection.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in.']);
    exit;
}

$userID = $_SESSION['user_id'];

// Fetch the department ID for the logged-in user
$queryDept = "SELECT dept_ID FROM useracc WHERE UserID = ?";
$stmtDept = $conn->prepare($queryDept);
$stmtDept->bind_param('i', $userID);
$stmtDept->execute();
$resultDept = $stmtDept->get_result();

if ($resultDept->num_rows > 0) {
    $rowDept = $resultDept->fetch_assoc();
    $dept_id = $rowDept['dept_ID']; 
} else {
    echo json_encode(['error' => 'User department not found.']);
    exit;
}

// Get the school year and quarter from the GET request
$schoolYear = isset($_GET['school_year']) ? $_GET['school_year'] : '';
$quarter = isset($_GET['quarter']) ? $_GET['quarter'] : '';

// SQL query
$queryMPS = "
    SELECT CONCAT(fc.Title, ' - ', fc.Captions) AS GradeSection, 
           AVG(m.MPS) AS AvgMPS
    FROM mps m
    INNER JOIN quarter q ON m.Quarter_ID = q.Quarter_ID
    INNER JOIN schoolyear sy ON q.School_Year_ID = sy.School_Year_ID
    INNER JOIN feedcontent fc ON m.ContentID = fc.ContentID
    INNER JOIN department d ON fc.dept_ID = d.dept_ID
    WHERE fc.dept_ID = ?
";

// Add filters
$params = [$dept_id];
$types = "i";

if (!empty($schoolYear)) {
    $queryMPS .= " AND q.School_Year_ID = ?";
    $params[] = $schoolYear;
    $types .= "i";
}

if (!empty($quarter)) {
    $queryMPS .= " AND q.Quarter_ID = ?";
    $params[] = $quarter;
    $types .= "i";
}

$queryMPS .= " GROUP BY GradeSection ORDER BY GradeSection ASC";

// Prepare and execute the query
$stmt = $conn->prepare($queryMPS);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$resultMPS = $stmt->get_result();

// Fetch data into an array
$data = [];
while ($row = $resultMPS->fetch_assoc()) {
    $data[] = [
        "grade_section" => $row['GradeSection'],
        "avg_mps" => round($row['AvgMPS'], 2) // Format to 2 decimal places
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($data);

// Close statements
$stmtDept->close();
$stmt->close();
?>
