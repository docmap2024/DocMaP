<?php
session_start();
include 'connection.php'; // Ensure this file includes proper DB connection details

// Disable exception mode for mysqli temporarily
mysqli_report(MYSQLI_REPORT_OFF);

// Decode incoming JSON data
$data = json_decode(file_get_contents('php://input'), true);

try {
    // Loop through each user and update their details
    foreach ($data['users'] as $user) {
        $userID = $user['UserID'];
        $firstName = $user['firstName'];
        $middleName = $user['middleName'];
        $lastName = $user['lastName'];
        $rank = $user['rank'];
        $address = $user['address'];
        $mobile = $user['mobile'];
        $email = $user['email'];

        $sql = "UPDATE useracc 
                SET fname = ?, mname = ?, lname = ?, Rank = ?, address = ?, mobile = ?, email = ?
                WHERE UserID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssssi', $firstName, $middleName, $lastName, $rank, $address, $mobile, $email, $userID);

        // Execute the query
        if (!$stmt->execute()) {
            // Check if the error is due to a duplicate email
            if ($stmt->errno === 1062) { // Error code 1062 corresponds to a duplicate entry
                echo json_encode(['success' => false, 'error' => 'The email address already exists in the database.']);
            } else {
                echo json_encode(['success' => false, 'error' => $stmt->error]);
            }
            exit;
        }
    }

    // If all updates succeed, send a success response
    echo json_encode(['success' => true]);
} catch (mysqli_sql_exception $e) {
    // Handle uncaught exceptions
    if (str_contains($e->getMessage(), 'Duplicate entry')) {
        echo json_encode(['success' => false, 'error' => 'The email address already exists in the database.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'An unexpected error occurred: ' . $e->getMessage()]);
    }
} finally {
    // Restore exception mode for mysqli
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}
?>
