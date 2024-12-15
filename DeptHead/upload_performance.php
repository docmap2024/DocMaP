<?php
include 'connection.php';

$school_year_id = $_POST['school_year_id'];
$rate = $_POST['rate'];
$type = $_POST['type'];  // 'enroll', 'dropout', 'promotion', etc.

switch($type) {
    case 'enroll':
        $sql = "INSERT INTO enroll (Rate, School_Year_ID) VALUES (?, ?)";
        break;
    case 'dropout':
        $sql = "INSERT INTO dropout (Rate, School_Year_ID) VALUES (?, ?)";
        break;
    case 'promotion':
        $sql = "INSERT INTO promotion (Rate, School_Year_ID) VALUES (?, ?)";
        break;
    case 'cohort_survival':
        $sql = "INSERT INTO cohort_survival (Rate, School_Year_ID) VALUES (?, ?)";
        break;
    case 'repetition':
        $sql = "INSERT INTO repetition (Rate, School_Year_ID) VALUES (?, ?)";
        break;
    default:
        echo 'Invalid type';
        exit;
}

$stmt = $conn->prepare($sql);
$stmt->bind_param('di', $rate, $school_year_id);

if ($stmt->execute()) {
    echo 'Data inserted successfully';
} else {
    echo 'Error inserting data';
}

$stmt->close();
$conn->close();
?>
