<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit();
}

include 'connection.php';

$yearRange = $_GET['year_range'] ?? null;

// Fetch performance data with optional filter
$sql = "
    SELECT sy.Year_Range, 
        SUM(e.Enroll_Gross) AS total_enrollment, 
        AVG(d.Dropout_Rate) AS avg_dropout_rate, 
        AVG(p.Promotion_Rate) AS avg_promotion_rate, 
        AVG(c.Cohort_Rate) AS avg_cohort_rate, 
        AVG(r.Repeaters_Rate) AS avg_repetition_rate, 
        AVG(t.Transition_Rate) AS avg_transition_rate
    FROM performance_indicator pi
    JOIN grade g ON pi.Grade_ID = g.Grade_ID
    JOIN enroll e ON pi.Enroll_ID = e.Enroll_ID
    JOIN dropout d ON pi.Dropout_ID = d.Dropout_ID
    JOIN promotion p ON pi.Promotion_ID = p.Promotion_ID
    JOIN cohort_survival c ON pi.Cohort_ID = c.Cohort_ID
    JOIN repetition r ON pi.Repetition_ID = r.Repetition_ID
    JOIN transition t ON pi.Transition_ID = t.Transition_ID
    JOIN schoolyear sy ON pi.School_Year_ID = sy.School_Year_ID
    WHERE 1=1";

// Add GROUP BY before any conditions
$sql .= " GROUP BY sy.Year_Range";

if ($yearRange) {
    $sql .= " HAVING sy.Year_Range = ?";  // Changed from WHERE to HAVING
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $yearRange);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$performanceData = [];
while ($row = $result->fetch_assoc()) {
    $performanceData[] = $row;
}

// Fetch MPS data with optional filter
$mpsQuery = "
    SELECT 
        q.Quarter_Name,
        sy.Year_Range,
        AVG(m.MPS) as avg_mps,
        COUNT(CASE WHEN m.`MPSBelow75` = 'Yes' THEN 1 END) as below_75_count,
        COUNT(*) as total_count
    FROM mps m
    JOIN quarter q ON m.Quarter_ID = q.Quarter_ID
    JOIN schoolyear sy ON q.School_Year_ID = sy.School_Year_ID
    WHERE 1=1";

// Add GROUP BY before any conditions
$mpsQuery .= " GROUP BY q.Quarter_Name, sy.Year_Range";

if ($yearRange) {
    $mpsQuery .= " HAVING sy.Year_Range = ?";  // Changed from WHERE to HAVING
    $stmt = $conn->prepare($mpsQuery);
    $stmt->bind_param("s", $yearRange);
    $stmt->execute();
    $mpsResult = $stmt->get_result();
} else {
    $mpsResult = $conn->query($mpsQuery);
}

$mpsData = [];
while ($row = $mpsResult->fetch_assoc()) {
    $mpsData[] = $row;
}

// Fetch productivity data
$productivityQuery = "
    SELECT 
        DATE_FORMAT(SubmitDate, '%Y-%m') as Month,
        COUNT(*) as SubmittedTasks,
        SUM(CASE WHEN Status = 'Approved' THEN 1 ELSE 0 END) as ApprovedTasks
    FROM task_user
    WHERE SubmitDate IS NOT NULL";

if ($yearRange) {
    $years = explode('-', $yearRange);
    $productivityQuery .= " AND YEAR(SubmitDate) BETWEEN ? AND ?";
    $productivityQuery .= " GROUP BY DATE_FORMAT(SubmitDate, '%Y-%m') ORDER BY Month";
    $stmt = $conn->prepare($productivityQuery);
    $stmt->bind_param("ss", $years[0], $years[1]);
    $stmt->execute();
    $productivityResult = $stmt->get_result();
} else {
    $productivityQuery .= " GROUP BY DATE_FORMAT(SubmitDate, '%Y-%m') ORDER BY Month";
    $productivityResult = $conn->query($productivityQuery);
}

$productivityData = [];
while ($row = $productivityResult->fetch_assoc()) {
    $productivityData[] = $row;
}

$conn->close();

header('Content-Type: application/json');
echo json_encode([
    'performanceData' => $performanceData,
    'mpsData' => $mpsData,
    'productivityData' => $productivityData
]);
?>