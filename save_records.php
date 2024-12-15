<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php'; // Include your database connection file

// Retrieve and sanitize input data
$userId = $_SESSION['user_id'];
$schoolYear = $conn->real_escape_string($_POST['school_year']);
$grade = $conn->real_escape_string($_POST['grade']);
$enrollGross = $conn->real_escape_string($_POST['enrollment_gross']);
$enrollNet = $conn->real_escape_string($_POST['enrollment_net']);
$cohortFigure = $conn->real_escape_string($_POST['cohort_figure']);
$cohortRate = $conn->real_escape_string($_POST['cohort_rate']);
$dropFigure = $conn->real_escape_string($_POST['drop_figure']);
$dropRate = $conn->real_escape_string($_POST['drop_rate']);
$repeatersFigure = $conn->real_escape_string($_POST['repeaters_figure']);
$repeatersRate = $conn->real_escape_string($_POST['repeaters_rate']);
$graduationFigure = $conn->real_escape_string($_POST['graduation_figure']);
$graduationRate = $conn->real_escape_string($_POST['graduation_rate']);
$transitionFigure = $conn->real_escape_string($_POST['transition_figure']);
$transitionRate = $conn->real_escape_string($_POST['transition_rate']);
$age = $conn->real_escape_string($_POST['age']);
$natScore = $conn->real_escape_string($_POST['nat_score']);

// Insert into respective tables and retrieve IDs
$conn->query("INSERT INTO Enroll (Enroll_Gross, Enroll_Net, School_Year_ID, Grade_ID, UserID) VALUES ('$enrollGross', '$enrollNet', '$schoolYear', '$grade', '$userId')");
$enrollId = $conn->insert_id;

// Update or Insert Totals for Total_Enroll based on the same School Year
$result = $conn->query("
    SELECT Total_Enroll_ID 
    FROM Total_Enroll 
    WHERE Enroll_ID IN (
        SELECT Enroll_ID 
        FROM Enroll 
        WHERE School_Year_ID = '$schoolYear'
    )
");

if ($result->num_rows > 0) {
    // Update existing Total_Enroll record for the same school year
    $conn->query("
        UPDATE Total_Enroll
        SET Total_Enroll_Gross = (
            SELECT SUM(Enroll_Gross) 
            FROM Enroll 
            WHERE School_Year_ID = '$schoolYear'
        ),
        Total_Enroll_Net = (
            SELECT SUM(Enroll_Net) 
            FROM Enroll 
            WHERE School_Year_ID = '$schoolYear'
        )
        WHERE Enroll_ID IN (
            SELECT Enroll_ID 
            FROM Enroll 
            WHERE School_Year_ID = '$schoolYear'
        )
    ");
} else {
    // Insert new Total_Enroll record for the same school year
    $conn->query("
        INSERT INTO Total_Enroll (Total_Enroll_Gross, Total_Enroll_Net, Enroll_ID)
        VALUES (
            (SELECT SUM(Enroll_Gross) 
             FROM Enroll 
             WHERE School_Year_ID = '$schoolYear'),
            (SELECT SUM(Enroll_Net) 
             FROM Enroll 
             WHERE School_Year_ID = '$schoolYear'),
            '$enrollId'
        )
    ");
}

$conn->query("INSERT INTO Cohort_Survival (Cohort_Figure, Cohort_Rate, School_Year_ID, Grade_ID, UserID) VALUES ('$cohortFigure', '$cohortRate', '$schoolYear', '$grade', '$userId')");
$cohortId = $conn->insert_id;

// Update or Insert Totals for Total_Cohort based on the same School Year
$result = $conn->query("
    SELECT Total_Cohort_ID 
    FROM Total_Cohort 
    WHERE Cohort_ID IN (
        SELECT Cohort_ID 
        FROM Cohort_Survival 
        WHERE School_Year_ID = '$schoolYear'
    )
");

if ($result->num_rows > 0) {
    // Update existing Total_Cohort record for the same school year
    $conn->query("
        UPDATE Total_Cohort
        SET Total_Cohort_Figure = (
            SELECT SUM(Cohort_Figure) 
            FROM Cohort_Survival 
            WHERE School_Year_ID = '$schoolYear'
        ),
        Total_Cohort_Rate = (
            SELECT SUM(Cohort_Rate) 
            FROM Cohort_Survival 
            WHERE School_Year_ID = '$schoolYear'
        )
        WHERE Cohort_ID IN (
            SELECT Cohort_ID 
            FROM Cohort_Survival 
            WHERE School_Year_ID = '$schoolYear'
        )
    ");
} else {
    // Insert new Total_Cohort record for the same school year
    $conn->query("
        INSERT INTO Total_Cohort (Total_Cohort_Figure, Total_Cohort_Rate, Cohort_ID)
        VALUES (
            (SELECT SUM(Cohort_Figure) 
             FROM Cohort_Survival 
             WHERE School_Year_ID = '$schoolYear'),
            (SELECT SUM(Cohort_Rate) 
             FROM Cohort_Survival 
             WHERE School_Year_ID = '$schoolYear'),
            '$cohortId'
        )
    ");
}

// Continue inserting other records
$conn->query("INSERT INTO Dropout (Dropout_Figure, Dropout_Rate, School_Year_ID, Grade_ID, UserID) VALUES ('$dropFigure', '$dropRate', '$schoolYear', '$grade', '$userId')");
$dropoutId = $conn->insert_id;

// Update or Insert Totals for Total_Dropout based on the same School Year
$result = $conn->query("
    SELECT Total_Dropout_ID 
    FROM Total_Dropout 
    WHERE Dropout_ID IN (
        SELECT Dropout_ID 
        FROM Dropout 
        WHERE School_Year_ID = '$schoolYear'
    )
");

if ($result->num_rows > 0) {
    // Update existing Total_Dropout record for the same school year
    $conn->query("
        UPDATE Total_Dropout
        SET Total_Dropout_Figure = (
            SELECT SUM(Dropout_Figure) 
            FROM Dropout 
            WHERE School_Year_ID = '$schoolYear'
        ),
        Total_Dropout_Rate = (
            SELECT SUM(Dropout_Rate) 
            FROM Dropout 
            WHERE School_Year_ID = '$schoolYear'
        )
        WHERE Dropout_ID IN (
            SELECT Dropout_ID 
            FROM Dropout 
            WHERE School_Year_ID = '$schoolYear'
        )
    ");
} else {
    // Insert new Total_Dropout record for the same school year
    $conn->query("
        INSERT INTO Total_Dropout (Total_Dropout_Figure, Total_Dropout_Rate, Dropout_ID)
        VALUES (
            (SELECT SUM(Dropout_Figure) 
             FROM Dropout 
             WHERE School_Year_ID = '$schoolYear'),
            (SELECT SUM(Dropout_Rate) 
             FROM Dropout 
             WHERE School_Year_ID = '$schoolYear'),
            '$dropoutId'
        )
    ");
}

$conn->query("INSERT INTO Repetition (Repeaters_Figure, Repeaters_Rate, School_Year_ID, Grade_ID, UserID) VALUES ('$repeatersFigure', '$repeatersRate', '$schoolYear', '$grade', '$userId')");
$repetitionId = $conn->insert_id;

$result = $conn->query("
        SELECT Total_Repetition_ID 
        FROM Total_Repetition 
        WHERE Repetition_ID IN (
            SELECT Repetition_ID 
            FROM Repetition 
            WHERE School_Year_ID = '$schoolYear'
        )
");

if ($result->num_rows > 0) {
    $conn->query("
        UPDATE Total_Repetition
        SET Total_Repetition_Figure = (
            SELECT SUM(Repeaters_Figure) 
            FROM Repetition 
            WHERE School_Year_ID = '$schoolYear'
        ),
        Total_Repetition_Rate = (
            SELECT SUM(Repeaters_Rate) 
            FROM Repetition 
            WHERE School_Year_ID = '$schoolYear'
        )
        WHERE Repetition_ID IN (
            SELECT Repetition_ID 
            FROM Repetition 
            WHERE School_Year_ID = '$schoolYear')
    ");
} else {
    $conn->query("
        INSERT INTO Total_Repetition (Total_Repetition_Figure, Total_Repetition_Rate, Repetition_ID)
        VALUES (
            (SELECT SUM(Repeaters_Figure) 
            FROM Repetition 
            WHERE School_Year_ID = '$schoolYear'),
            (SELECT SUM(Repeaters_Rate) 
            FROM Repetition 
            WHERE School_Year_ID = '$schoolYear'),
            '$repetitionId'
        )
    ");
}

$conn->query("INSERT INTO Promotion (Promotion_Figure, Promotion_Rate, School_Year_ID, Grade_ID, UserID) VALUES ('$graduationFigure', '$graduationRate', '$schoolYear', '$grade', '$userId')");
$promotionId = $conn->insert_id;

$result = $conn->query("
    SELECT Total_Promotion_ID 
    FROM Total_Promotion 
    WHERE Promotion_ID IN (
        SELECT Promotion_ID 
        FROM Promotion 
        WHERE School_Year_ID = '$schoolYear'
    )
");

if ($result->num_rows > 0) {
    $conn->query("
        UPDATE Total_Promotion
        SET Total_Promotion_Figure = (
            SELECT SUM(Promotion_Figure) 
            FROM Promotion 
            WHERE School_Year_ID = '$schoolYear'
        ),
        Total_Promotion_Rate = (
            SELECT SUM(Promotion_Rate) 
            FROM Promotion 
            WHERE School_Year_ID = '$schoolYear'
        )
        WHERE Promotion_ID IN (
            SELECT Promotion_ID 
            FROM Promotion 
            WHERE School_Year_ID = '$schoolYear')
    ");
} else {
    $conn->query("
        INSERT INTO Total_Promotion (Total_Promotion_Figure, Total_Promotion_Rate, Promotion_ID)
        VALUES (
            (SELECT SUM(Promotion_Figure) 
            FROM Promotion 
            WHERE School_Year_ID = '$schoolYear'),
            (SELECT SUM(Promotion_Rate) 
            FROM Promotion 
            WHERE School_Year_ID = '$schoolYear'),
            '$promotionId'
        )
    ");
}

$conn->query("INSERT INTO Transition (Transition_Figure, Transition_Rate, School_Year_ID, Grade_ID, UserID) VALUES ('$transitionFigure', '$transitionRate', '$schoolYear', '$grade', '$userId')");
$transitionId = $conn->insert_id;

// Update or Insert Totals for Total_Transition based on the same School Yea
$result = $conn->query("
        SELECT Total_Transition_ID 
        FROM Total_Transition 
        WHERE Transition_ID IN (
            SELECT Transition_ID 
            FROM Transition 
            WHERE School_Year_ID = '$schoolYear'
        )
");

if ($result->num_rows > 0) {
    $conn->query("
        UPDATE Total_Transition
        SET Total_Transition_Figure = 
        (SELECT SUM(Transition_Figure) 
        FROM Transition 
        WHERE School_Year_ID = '$schoolYear'
        ),
        Total_Transition_Rate = (
        SELECT SUM(Transition_Rate) 
        FROM Transition 
        WHERE School_Year_ID = '$schoolYear'
        )
        WHERE Transition_ID IN (
        SELECT Transition_ID 
        FROM Transition 
        WHERE School_Year_ID = '$schoolYear'
        )
    ");
} else {
    $conn->query("
        INSERT INTO Total_Transition (Total_Transition_Figure, Total_Transition_Rate, Transition_ID)
        VALUES (
            (SELECT SUM(Transition_Figure) 
            FROM Transition 
            WHERE School_Year_ID = '$schoolYear'),
            (SELECT SUM(Transition_Rate) 
            FROM Transition 
            WHERE School_Year_ID = '$schoolYear'),
            '$transitionId'
        )
    ");
}


$conn->query("INSERT INTO Age (Age_Group_12To15, School_Year_ID, Grade_ID, UserID) VALUES ('$age', '$schoolYear', '$grade', '$userId')");
$ageId = $conn->insert_id;

// Update or Insert Totals for Total_Age based on the same School Year
$result = $conn->query("
    SELECT Total_Age_ID 
    FROM Total_Age 
    WHERE Age_ID IN (
        SELECT Age_ID 
        FROM Age 
        WHERE School_Year_ID = '$schoolYear'
    )
");

if ($result->num_rows > 0) {
    // Update existing Total_Age record for the same school year
    $conn->query("
        UPDATE Total_Age
        SET Total_Age = (
            SELECT SUM(Age_Group_12To15) 
            FROM Age 
            WHERE School_Year_ID = '$schoolYear'
        )
        WHERE Age_ID IN (
            SELECT Age_ID 
            FROM Age 
            WHERE School_Year_ID = '$schoolYear'
        )
    ");
} else {
    // Insert new Total_Age record for the same school year
    $conn->query("
        INSERT INTO Total_Age (Total_Age, Age_ID)
        VALUES (
            (SELECT SUM(Age_Group_12To15) 
             FROM Age 
             WHERE School_Year_ID = '$schoolYear'),
            '$ageId'
        )
    ");
}

$conn->query("INSERT INTO NAT (NAT_Score, School_Year_ID, Grade_ID, UserID) VALUES ('$natScore', '$schoolYear', '$grade', '$userId')");
$natId = $conn->insert_id;

// Update or Insert Totals for Total_NAT based on the same School Year
$result = $conn->query("
    SELECT Total_NAT_ID 
    FROM Total_NAT 
    WHERE NAT_ID IN (
        SELECT NAT_ID 
        FROM NAT 
        WHERE School_Year_ID = '$schoolYear'
    )
");

if ($result->num_rows > 0) {
    // Update existing Total_NAT record for the same school year
    $conn->query("
        UPDATE Total_NAT
        SET Total_NAT = (
            SELECT SUM(NAT_Score) 
            FROM NAT 
            WHERE School_Year_ID = '$schoolYear'
        )
        WHERE NAT_ID IN (
            SELECT NAT_ID 
            FROM NAT 
            WHERE School_Year_ID = '$schoolYear'
        )
    ");
} else {
    // Insert new Total_NAT record for the same school year
    $conn->query("
        INSERT INTO Total_NAT (Total_NAT, NAT_ID)
        VALUES (
            (SELECT SUM(NAT_Score) 
             FROM NAT 
             WHERE School_Year_ID = '$schoolYear'),
            '$natId'
        )
    ");
}

// Insert into Performance_Indicator
$conn->query("INSERT INTO Performance_Indicator (
    UserID, School_Year_ID, Grade_ID, Enroll_ID, Dropout_ID, Promotion_ID, Cohort_ID, Repetition_ID, Age_ID, NAT_ID, Transition_ID
) VALUES (
    '$userId','$schoolYear', '$grade', '$enrollId', '$dropoutId', '$promotionId', '$cohortId', '$repetitionId', '$ageId', '$natId', '$transitionId'
)");

// Check for success
if ($conn->affected_rows > 0) {  
    echo "Record added successfully.";
} else {
    echo "Error adding record: " . $conn->error;
}

$conn->close();
?>
