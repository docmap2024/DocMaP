<?php
// Include database connection
require_once 'connection.php'; // Ensure this points to your database connection file

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve posted data
    $schoolID = isset($_POST['schoolID']) ? $_POST['schoolID'] : '';
    $schoolName = isset($_POST['schoolName']) ? $_POST['schoolName'] : '';
    $address = isset($_POST['address']) ? $_POST['address'] : '';
    $region = isset($_POST['region']) ? $_POST['region'] : '';
    $country = isset($_POST['country']) ? $_POST['country'] : '';
    $organization = isset($_POST['organization']) ? $_POST['organization'] : '';
    $mobileNumber = isset($_POST['mobileNumber']) ? $_POST['mobileNumber'] : '';
    $socialMediaLink = isset($_POST['socialMediaLink']) ? $_POST['socialMediaLink'] : ''; // This matches the JavaScript key

    // Prepare SQL statements for updating the tables
    // Update school details
    $stmt1 = $conn->prepare("UPDATE school_details SET Name=?, Address=?, Region=?, Country=?, Organization=? WHERE School_ID=?");
    $stmt1->bind_param("ssssss", $schoolName, $address, $region, $country, $organization, $schoolID);

    // Update mobile number (always for Mobile_ID = 1)
    $stmt2 = $conn->prepare("UPDATE school_mobile SET Mobile_No=? WHERE Mobile_ID=1");
    $stmt2->bind_param("s", $mobileNumber);

    // Update social media link (always for Social_Media_ID = 1)
    $stmt3 = $conn->prepare("UPDATE social_media SET Social_Media_Link=? WHERE Social_Media_ID=1");
    $stmt3->bind_param("s", $socialMediaLink);

    // Execute all statements
    if ($stmt1->execute() && $stmt2->execute() && $stmt3->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'School details updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update school details.']);
    }

    // Close the statements
    $stmt1->close();
    $stmt2->close();
    $stmt3->close();
    
    // Close the database connection
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
