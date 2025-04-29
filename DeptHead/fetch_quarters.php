<?php
include 'connection.php';

if (isset($_GET['school_year_id'])) {
    $schoolYearID = intval($_GET['school_year_id']);

    $query = "SELECT DISTINCT Quarter_ID, Quarter_Name FROM quarter WHERE School_Year_ID = ? ORDER BY Quarter_ID ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $schoolYearID);
    $stmt->execute();
    $result = $stmt->get_result();

    $quarters = [];
    while ($row = $result->fetch_assoc()) {
        $quarters[] = $row;
    }

    echo json_encode($quarters);
}

$conn->close();
?>
