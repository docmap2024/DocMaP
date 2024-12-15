<?php
include 'connection.php'; // Include your database connection

// Query to get the school year, quarter, start date, and end date
$query = "
    SELECT q.Quarter_ID, q.School_Year_ID, q.Quarter_Name, q.Start_Date, q.End_Date, sy.Year_Range
    FROM quarter q
    INNER JOIN schoolyear sy ON q.School_Year_ID = sy.School_Year_ID
";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['error' => 'Query failed: ' . mysqli_error($conn)]);
    exit;
}

// Fetch the data
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

// Return data as JSON
echo json_encode($data);
?>
