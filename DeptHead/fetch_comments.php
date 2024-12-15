<?php
session_start(); // Make sure to start the session to access session variables
include 'connection.php';

$task_id = $_GET['task_id'];
$content_id = $_GET['content_id'];
$user_id = $_GET['user_id']; // User ID passed from the modal
$session_user_id = $_SESSION['user_id']; // User ID from session

// Modify the SQL query to include the timestamp
$sql = "SELECT comments.Comment, comments.IncomingID, comments.OutgoingID, comments.timestamp, useracc.fname, useracc.lname, useracc.profile
        FROM comments 
        JOIN useracc ON comments.IncomingID = useracc.UserID 
        WHERE comments.ContentID = ? AND comments.TaskID = ? 
        AND (comments.OutgoingID = ? OR comments.OutgoingID = ?)
        ORDER BY comments.CommentID";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $content_id, $task_id, $user_id, $session_user_id);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    $profileImagePath = "../img/UserProfile/" . $row['profile']; // Constructing the path to the profile image
    
    // Format the timestamp to 'YYYY/MM/DD HH:MM'
    $timestamp = new DateTime($row['timestamp']);
    $formattedTimestamp = $timestamp->format('Y/m/d H:i'); // Change format as needed
    
    $comments[] = [
        'Comment' => $row['Comment'],
        'fname' => $row['fname'],
        'lname' => $row['lname'],
        'profile' => $profileImagePath, // Adding the full image path to the response
        'timestamp' => $formattedTimestamp // Adding formatted timestamp to the response
    ];
}

$response = [
    'success' => true,
    'comments' => $comments
];

header('Content-Type: application/json');
echo json_encode($response);
?>
