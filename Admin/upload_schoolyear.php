<?php
// Include your database connection file
include 'connection.php'; // Make sure this file is in the same directory or provide the correct path

// Get data from the AJAX request
$schoolYear = $_POST['schoolYear'];
$quarters = json_decode($_POST['quarters'], true); // Assuming quarters is a JSON array

// Prepare and bind the insert statement for school year
$stmt = $conn->prepare("INSERT INTO schoolyear (Year_Range) VALUES (?)");
$stmt->bind_param("s", $schoolYear);

// Execute the statement
if ($stmt->execute()) {
    // Get the last inserted ID (School_Year_ID)
    $schoolYearId = $stmt->insert_id;

    // Prepare the insert statement for quarters
    $quarterStmt = $conn->prepare("INSERT INTO quarter (School_Year_ID, Quarter_Name, Start_Date, End_Date) VALUES (?, ?, ?, ?)");

    // Loop through the quarters array and insert each quarter
    foreach ($quarters as $quarter) {
        $quarterName = $quarter['name'];
        $startDate = $quarter['start_date'];
        $endDate = $quarter['end_date'];

        // Bind parameters for quarter insertion
        $quarterStmt->bind_param("isss", $schoolYearId, $quarterName, $startDate, $endDate);
        $quarterStmt->execute();
    }

    echo json_encode(["status" => "success", "school_year_id" => $schoolYearId]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to insert school year."]);
}

// Close the statements and connection
$stmt->close();
$quarterStmt->close();
$conn->close();
?>
