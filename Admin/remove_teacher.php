<?php
header('Content-Type: application/json');
include('connection.php'); // Your DB connection file

// Semaphore API credentials
define('SEMAPHORE_API_URL', 'https://api.semaphore.co/api/v4/messages');
define('SEMAPHORE_API_KEY', 'd796c0e11273934ac9d789536133684a');

// Read JSON data from request body
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['userId']) && isset($data['contentId'])) {
    $userId = $data['userId'];
    $contentId = $data['contentId'];

    // Begin a transaction for safe updates
    $conn->begin_transaction();

    try {
        // Update Status to 'Removed' in the usercontent table
        $sql = "UPDATE usercontent SET Status = 'Removed' WHERE ContentID = ? AND UserID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $contentId, $userId);

        if (!$stmt->execute()) {
            throw new Exception("Failed to update user status");
        }

        // Fetch user details
        $userQuery = "
            SELECT 
                UPPER(CONCAT(fname, ' ', lname)) AS FullName, 
                mobile 
            FROM 
                useracc
            WHERE 
                UserID = ?";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $user = $userResult->fetch_assoc();

        if (!$user) {
            throw new Exception("User not found");
        }

        // Fetch content and department details
        $contentQuery = "
            SELECT 
                CONCAT(Title, '-', Captions) AS FullContent, 
                dept_ID
            FROM 
                feedcontent 
            WHERE 
                ContentID = ?";
        $contentStmt = $conn->prepare($contentQuery);
        $contentStmt->bind_param("i", $contentId);
        $contentStmt->execute();
        $contentResult = $contentStmt->get_result();
        $content = $contentResult->fetch_assoc();

        if (!$content) {
            throw new Exception("Content not found");
        }

        $deptQuery = "SELECT dept_name FROM department WHERE dept_ID = ?";
        $deptStmt = $conn->prepare($deptQuery);
        $deptStmt->bind_param("i", $content['dept_ID']);
        $deptStmt->execute();
        $deptResult = $deptStmt->get_result();
        $department = $deptResult->fetch_assoc();

        if (!$department) {
            throw new Exception("Department not found");
        }

        // Construct the message
        $message = "ALERT!\n\nHello " . $user['FullName'] . "! You have just been removed from " . 
            $content['FullContent'] . " (" . $department['dept_name'] . "). If you believe this is wrong, contact admin immediately. Thank you.";

        // Send SMS via Semaphore API
        $smsData = [
            'apikey' => SEMAPHORE_API_KEY,
            'number' => $user['mobile'],
            'message' => $message,
            'sendername' => 'DocMaP'
        ];

        $ch = curl_init(SEMAPHORE_API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($smsData));
        $response = curl_exec($ch);
        curl_close($ch);

        $smsResponse = json_decode($response, true);

        if (!isset($smsResponse['status']) || $smsResponse['status'] !== 'success') {
            // Log SMS failure for later review but proceed with the transaction commit
            error_log("SMS sending failed: " . ($smsResponse['message'] ?? 'Unknown error'));
        }

        // Commit the transaction regardless of SMS success
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'User status updated to Removed and SMS sent']);
    } catch (Exception $e) {
        // Rollback the transaction in case of any errors
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    // Missing data
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
}

$conn->close();
