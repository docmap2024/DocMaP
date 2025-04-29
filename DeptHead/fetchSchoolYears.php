<?php
require_once 'connection.php'; // Include your database connection

$sql = "
    SELECT 
        sy.School_Year_ID, 
        sy.Year_Range, 
        q.Quarter_ID, 
        q.Quarter_Name, 
        q.Start_Date, 
        q.End_Date
    FROM schoolyear sy
    LEFT JOIN quarter q ON sy.School_Year_ID = q.School_Year_ID
    ORDER BY sy.Year_Range DESC, q.Start_Date ASC
";

$result = $conn->query($sql);

$schoolYears = [];
while ($row = $result->fetch_assoc()) {
    $schoolYearID = $row['School_Year_ID'];
    if (!isset($schoolYears[$schoolYearID])) {
        $schoolYears[$schoolYearID] = [
            "School_Year_ID" => $schoolYearID,
            "Year_Range" => $row['Year_Range'],
            "Quarters" => []
        ];
    }
    if (!empty($row['Quarter_ID'])) { // If there are quarters available, add them
        $schoolYears[$schoolYearID]["Quarters"][] = [
            "Quarter_ID" => $row['Quarter_ID'],
            "Quarter_Name" => $row['Quarter_Name'],
            "Start_Date" => $row['Start_Date'],
            "End_Date" => $row['End_Date']
        ];
    }
}

echo json_encode(array_values($schoolYears)); // Convert associative array to indexed array for JSON
$conn->close();
?>
