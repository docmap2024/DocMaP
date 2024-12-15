<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

header('Content-Type: application/json');

if (!isset($_POST['performanceID']) || !isset($_POST['schoolYearID']) || !isset($_POST['Grade_Level'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Required fields are missing!'
    ]);
    exit();
}

include 'connection.php'; // Include your database connection file

$userId = $_SESSION['user_id']; // Get the logged-in user ID
$response = ['success' => true, 'errors' => []];

// Collect the form data
$performanceID = $_POST['performanceID'];
$gradeLevel = $_POST['Grade_Level'];
$enrollGross = $_POST['Enroll_Gross'];
$enrollNet = $_POST['Enroll_Net'];
$cohortFigure = $_POST['Cohort_Figure'];
$cohortRate = $_POST['Cohort_Rate'];
$dropoutFigure = $_POST['Dropout_Figure'];
$dropoutRate = $_POST['Dropout_Rate'];
$repeatersFigure = $_POST['Repeaters_Figure'];
$repeatersRate = $_POST['Repeaters_Rate'];
$promotionFigure = $_POST['Promotion_Figure'];
$promotionRate = $_POST['Promotion_Rate'];
$transitionFigure = $_POST['Transition_Figure'];
$transitionRate = $_POST['Transition_Rate'];
$ageGroup = $_POST['Age_Group_12To15'];
$natScore = $_POST['NAT_Score'];
$schoolYearId = $_POST['schoolYearID'];

// Start transaction
$conn->begin_transaction();

$success = true;

try {
    // Update Enroll table
    $sqlEnroll = "UPDATE Enroll 
                  SET Enroll_Gross = ?, Enroll_Net = ? 
                  WHERE Enroll_ID = (SELECT Enroll_ID FROM Performance_Indicator WHERE Performance_ID = ?)
                  AND Grade_ID = (SELECT Grade_ID FROM grade WHERE Grade_Level = ?) 
                  AND School_Year_ID = ? AND UserID = ?";
    $stmtEnroll = $conn->prepare($sqlEnroll);
    $stmtEnroll->bind_param('iiiisi', $enrollGross, $enrollNet, $performanceID, $gradeLevel, $schoolYearId, $userId);
    $stmtEnroll->execute();
    if ($stmtEnroll->error) {
        $success = false;
        $response['errors'][] = 'Error updating Enroll table: ' . $stmtEnroll->error;
    }

    // Update or Insert Totals for Total_Enroll based on the same School Year
    $result = $conn->query("
        SELECT Total_Enroll_ID 
        FROM Total_Enroll 
        WHERE Enroll_ID IN (
            SELECT Enroll_ID 
            FROM Enroll 
            WHERE School_Year_ID = '$schoolYearId'
        )
    ");

    if ($result->num_rows > 0) {
    // Update existing Total_Enroll record for the same school year
        $conn->query("
            UPDATE Total_Enroll
            SET Total_Enroll_Gross = (
                SELECT SUM(Enroll_Gross) 
                FROM Enroll 
                WHERE School_Year_ID = '$schoolYearId'
            ),
            Total_Enroll_Net = (
                SELECT SUM(Enroll_Net) 
                FROM Enroll 
                WHERE School_Year_ID = '$schoolYearId'
            )
            WHERE Enroll_ID IN (
                SELECT Enroll_ID 
                FROM Enroll 
                WHERE School_Year_ID = '$schoolYearId'
            )
        ");
    }

    // Update Dropout table
    $sqlDropout = "UPDATE Dropout 
                   SET Dropout_Figure = ?, Dropout_Rate = ? 
                   WHERE Dropout_ID = (SELECT Dropout_ID FROM Performance_Indicator WHERE Performance_ID = ?)
                   AND Grade_ID = (SELECT Grade_ID FROM grade WHERE Grade_Level = ?) 
                   AND School_Year_ID = ? AND UserID = ?";
    $stmtDropout = $conn->prepare($sqlDropout);
    $stmtDropout->bind_param('idiiii', $dropoutFigure, $dropoutRate, $performanceID, $gradeLevel, $schoolYearId, $userId);
    $stmtDropout->execute();
    if ($stmtDropout->error) {
        $success = false;
        $response['errors'][] = 'Error updating Dropout table: ' . $stmtDropout->error;
    }

    // Update or Insert Totals for Total_Dropout based on the same School Year
    $result = $conn->query("
        SELECT Total_Dropout_ID 
        FROM Total_Dropout 
        WHERE Dropout_ID IN (
            SELECT Dropout_ID 
            FROM Dropout 
            WHERE School_Year_ID = '$schoolYearId'
        )
    ");

    if ($result->num_rows > 0) {
    // Update existing Total_Dropout record for the same school year
        $conn->query("
            UPDATE Total_Dropout
            SET Total_Dropout_Figure = (
                SELECT SUM(Dropout_Figure) 
                FROM Dropout 
                WHERE School_Year_ID = '$schoolYearId'
            ),
            Total_Dropout_Rate = (
                SELECT SUM(Dropout_Rate) 
                FROM Dropout 
                WHERE School_Year_ID = '$schoolYearId'
            )
            WHERE Dropout_ID IN (
                SELECT Dropout_ID 
                FROM Dropout 
                WHERE School_Year_ID = '$schoolYearId'
            )
        ");
    }

    // Update Promotion table
    $sqlPromotion = "UPDATE Promotion 
                     SET Promotion_Figure = ?, Promotion_Rate = ? 
                     WHERE Promotion_ID = (SELECT Promotion_ID FROM Performance_Indicator WHERE Performance_ID = ?)
                     AND Grade_ID = (SELECT Grade_ID FROM grade WHERE Grade_Level = ?) 
                     AND School_Year_ID = ? AND UserID = ?";
    $stmtPromotion = $conn->prepare($sqlPromotion);
    $stmtPromotion->bind_param('idiiii', $promotionFigure, $promotionRate, $performanceID, $gradeLevel, $schoolYearId, $userId);
    $stmtPromotion->execute();
    if ($stmtPromotion->error) {
        $success = false;
        $response['errors'][] = 'Error updating Promotion table: ' . $stmtPromotion->error;
    }


    $result = $conn->query("
        SELECT Total_Promotion_ID 
        FROM Total_Promotion 
        WHERE Promotion_ID IN (
            SELECT Promotion_ID 
            FROM Promotion 
            WHERE School_Year_ID = '$schoolYearId'
        )
    ");

    if ($result->num_rows > 0) {
        $conn->query("
            UPDATE Total_Promotion
            SET Total_Promotion_Figure = (
                SELECT SUM(Promotion_Figure) 
                FROM Promotion 
                WHERE School_Year_ID = '$schoolYearId'
            ),
            Total_Promotion_Rate = (
                SELECT SUM(Promotion_Rate) 
                FROM Promotion 
                WHERE School_Year_ID = '$schoolYearId'
            )
            WHERE Promotion_ID IN (
                SELECT Promotion_ID 
                FROM Promotion 
                WHERE School_Year_ID = '$schoolYearId')
        ");
    }


    // Update Cohort_Survival table
    $sqlCohort = "UPDATE Cohort_Survival 
                  SET Cohort_Figure = ?, Cohort_Rate = ? 
                  WHERE Cohort_ID = (SELECT Cohort_ID FROM Performance_Indicator WHERE Performance_ID = ?)
                  AND School_Year_ID = ? AND UserID = ?";
    $stmtCohort = $conn->prepare($sqlCohort);
    $stmtCohort->bind_param('idiii', $cohortFigure, $cohortRate, $performanceID, $schoolYearId, $userId);
    $stmtCohort->execute();
    if ($stmtCohort->error) {
        $success = false;
        $response['errors'][] = 'Error updating Cohort_Survival table: ' . $stmtCohort->error;
    }

    // Update or Insert Totals for Total_Cohort based on the same School Year
    $result = $conn->query("
        SELECT Total_Cohort_ID 
        FROM Total_Cohort 
        WHERE Cohort_ID IN (
            SELECT Cohort_ID 
            FROM Cohort_Survival 
            WHERE School_Year_ID = '$schoolYearId'
        )
    ");

    if ($result->num_rows > 0) {
    // Update existing Total_Cohort record for the same school year
        $conn->query("
            UPDATE Total_Cohort
            SET Total_Cohort_Figure = (
                SELECT SUM(Cohort_Figure) 
                FROM Cohort_Survival 
                WHERE School_Year_ID = '$schoolYearId'
            ),
            Total_Cohort_Rate = (
                SELECT SUM(Cohort_Rate) 
                FROM Cohort_Survival 
                WHERE School_Year_ID = '$schoolYearId'
            )
            WHERE Cohort_ID IN (
                SELECT Cohort_ID 
                FROM Cohort_Survival 
                WHERE School_Year_ID = '$schoolYearId'
            )
        ");
    } 

    // Update Repetition table
    $sqlRepetition = "UPDATE Repetition 
                      SET Repeaters_Figure = ?, Repeaters_Rate = ? 
                      WHERE Repetition_ID = (SELECT Repetition_ID FROM Performance_Indicator WHERE Performance_ID = ?)
                      AND Grade_ID = (SELECT Grade_ID FROM grade WHERE Grade_Level = ?) 
                      AND School_Year_ID = ? AND UserID = ?";
    $stmtRepetition = $conn->prepare($sqlRepetition);
    $stmtRepetition->bind_param('idiiii', $repeatersFigure, $repeatersRate, $performanceID, $gradeLevel, $schoolYearId, $userId);
    $stmtRepetition->execute();
    if ($stmtRepetition->error) {
        $success = false;
        $response['errors'][] = 'Error updating Repetition table: ' . $stmtRepetition->error;
    }


    $result = $conn->query("
        SELECT Total_Repetition_ID 
        FROM Total_Repetition 
        WHERE Repetition_ID IN (
            SELECT Repetition_ID 
            FROM Repetition 
            WHERE School_Year_ID = '$schoolYearId'
        )
    ");

    if ($result->num_rows > 0) {
        $conn->query("
            UPDATE Total_Repetition
            SET Total_Repetition_Figure = (
                SELECT SUM(Repeaters_Figure) 
                FROM Repetition 
                WHERE School_Year_ID = '$schoolYearId'
            ),
            Total_Repetition_Rate = (
                SELECT SUM(Repeaters_Rate) 
                FROM Repetition 
                WHERE School_Year_ID = '$schoolYearId'
            )
            WHERE Repetition_ID IN (
                SELECT Repetition_ID 
                FROM Repetition 
                WHERE School_Year_ID = '$schoolYearId')
        ");
    }

    // Update Transition table
    $sqlTransition = "UPDATE Transition 
                      SET Transition_Figure = ?, Transition_Rate = ? 
                      WHERE Transition_ID = (SELECT Transition_ID FROM Performance_Indicator WHERE Performance_ID = ?)
                      AND Grade_ID = (SELECT Grade_ID FROM grade WHERE Grade_Level = ?) 
                      AND School_Year_ID = ? AND UserID = ?";
    $stmtTransition = $conn->prepare($sqlTransition);
    $stmtTransition->bind_param('idiiii', $transitionFigure, $transitionRate, $performanceID, $gradeLevel, $schoolYearId, $userId);
    $stmtTransition->execute();
    if ($stmtTransition->error) {
        $success = false;
        $response['errors'][] = 'Error updating Transition table: ' . $stmtTransition->error;
    }


    // Update or Insert Totals for Total_Transition based on the same School Yea
    $result = $conn->query("
        SELECT Total_Transition_ID 
        FROM Total_Transition 
        WHERE Transition_ID IN (
            SELECT Transition_ID 
            FROM Transition 
            WHERE School_Year_ID = '$schoolYearId'
        )
    ");

    if ($result->num_rows > 0) {
        $conn->query("
            UPDATE Total_Transition
            SET Total_Transition_Figure = 
            (SELECT SUM(Transition_Figure) 
            FROM Transition 
            WHERE School_Year_ID = '$schoolYearId'
            ),
            Total_Transition_Rate = (
            SELECT SUM(Transition_Rate) 
            FROM Transition 
            WHERE School_Year_ID = '$schoolYearId'
            )
            WHERE Transition_ID IN (
            SELECT Transition_ID 
            FROM Transition 
            WHERE School_Year_ID = '$schoolYearId'
            )
        ");
    }



    // Update Age table
    $sqlAge = "UPDATE Age 
               SET Age_Group_12To15 = ? 
               WHERE Age_ID = (SELECT Age_ID FROM Performance_Indicator WHERE Performance_ID = ?)
               AND Grade_ID = (SELECT Grade_ID FROM grade WHERE Grade_Level = ?) 
               AND School_Year_ID = ? AND UserID = ?";
    $stmtAge = $conn->prepare($sqlAge);
    $stmtAge->bind_param('iiiii', $ageGroup, $performanceID, $gradeLevel, $schoolYearId, $userId);
    $stmtAge->execute();
    if ($stmtAge->error) {
        $success = false;
        $response['errors'][] = 'Error updating Age table: ' . $stmtAge->error;
    }


    // Update or Insert Totals for Total_Age based on the same School Year
    $result = $conn->query("
        SELECT Total_Age_ID 
        FROM Total_Age 
        WHERE Age_ID IN (
            SELECT Age_ID 
            FROM Age 
            WHERE School_Year_ID = '$schoolYearId'
        )
    ");

    if ($result->num_rows > 0) {
        // Update existing Total_Age record for the same school year
        $conn->query("
            UPDATE Total_Age
            SET Total_Age = (
                SELECT SUM(Age_Group_12To15) 
                FROM Age 
                WHERE School_Year_ID = '$schoolYearId'
            )
            WHERE Age_ID IN (
                SELECT Age_ID 
                FROM Age 
                WHERE School_Year_ID = '$schoolYearId'
            )
        ");
    }


    // Update NAT table
    $sqlNAT = "UPDATE NAT 
               SET NAT_Score = ? 
               WHERE NAT_ID = (SELECT NAT_ID FROM Performance_Indicator WHERE Performance_ID = ?)
               AND Grade_ID = (SELECT Grade_ID FROM grade WHERE Grade_Level = ?) 
               AND School_Year_ID = ? AND UserID = ?";
    $stmtNAT = $conn->prepare($sqlNAT);
    $stmtNAT->bind_param('iiiii', $natScore, $performanceID, $gradeLevel, $schoolYearId, $userId);
    $stmtNAT->execute();
    if ($stmtNAT->error) {
        $success = false;
        $response['errors'][] = 'Error updating NAT table: ' . $stmtNAT->error;
    }


    // Update or Insert Totals for Total_NAT based on the same School Year
    $result = $conn->query("
        SELECT Total_NAT_ID 
        FROM Total_NAT 
        WHERE NAT_ID IN (
            SELECT NAT_ID 
            FROM NAT 
            WHERE School_Year_ID = '$schoolYearId'
        )
    ");

    if ($result->num_rows > 0) {
    // Update existing Total_NAT record for the same school year
        $conn->query("
            UPDATE Total_NAT
            SET Total_NAT = (
                SELECT SUM(NAT_Score) 
                FROM NAT 
                WHERE School_Year_ID = '$schoolYearId'
            )
            WHERE NAT_ID IN (
                SELECT NAT_ID 
                FROM NAT 
                WHERE School_Year_ID = '$schoolYearId'
            )
        ");
    }



    // Commit or rollback based on success
    if ($success) {
        $conn->commit();
        $response['success'] = true;
        $response['message'] = 'Record updated successfully!';
    } else {
        $conn->rollback();
        $response['success'] = false;
        $response['message'] = 'One or more updates failed. Check errors.';
    }
} catch (Exception $e) {
    $conn->rollback();
    $response['success'] = false;
    $response['message'] = 'Transaction failed: ' . $e->getMessage();
}

echo json_encode($response);
?>
