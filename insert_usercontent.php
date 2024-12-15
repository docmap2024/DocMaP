<?php
session_start();
include 'connection.php';

if (isset($_POST['code']) && isset($_SESSION['user_id'])) {
    $code = $_POST['code'];
    $user_id = $_SESSION['user_id'];

    // Fetch ContentID from feedcontent based on the given code
    $query = "SELECT ContentID FROM feedcontent WHERE ContentCode = '$code'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $content_id = $row['ContentID'];

        // Check if there's already an existing record with the same UserID and ContentID
        $check_query = "SELECT * FROM usercontent WHERE UserID = '$user_id' AND ContentID = '$content_id'";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            // Record exists, now check the Status
            $existing_record = mysqli_fetch_assoc($check_result);
            if ($existing_record['Status'] == 'Removed') {
                // If the status is 'Removed', update it to 'Active'
                $update_query = "UPDATE usercontent SET Status = 'Active' WHERE UserID = '$user_id' AND ContentID = '$content_id'";
                if (mysqli_query($conn, $update_query)) {
                    echo 'success';  // Return success on successful update
                } else {
                    echo 'error';  // Return error on failure
                }
            } else {
                // If status is not 'Removed', no action is needed (already active)
                echo 'exists';  // Indicate that the content is already active
            }
        } else {
            // If no record exists, insert a new record
            $insert_query = "INSERT INTO usercontent (UserID, ContentID, Status) VALUES ('$user_id', '$content_id', 'Active')";
            if (mysqli_query($conn, $insert_query)) {
                // Get the last inserted UserContentID
                $user_content_id = mysqli_insert_id($conn);

                // Insert the UserContentID into userfolders table
                $insert_folder_query = "INSERT INTO userfolders (UserContentID) VALUES ('$user_content_id')";
                if (mysqli_query($conn, $insert_folder_query)) {
                    echo 'success';  // Return success on successful insert
                } else {
                    echo 'error';  // Return error if userfolder insert fails
                }
            } else {
                echo 'error';  // Return error if usercontent insert fails
            }
        }
    } else {
        echo 'error';  // Return error if content is not found
    }

    mysqli_close($conn);
}

?>
