<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "connection.php";

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'create' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    // Generate ContentCode (6-character random string)
    $contentCode = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789"), 0, 6);

    $title = $conn->real_escape_string($data['title']);
    $caption = $conn->real_escape_string($data['caption']);
    $deptID = $conn->real_escape_string($data['deptID']);
    $color = $conn->real_escape_string($data['color']); // Capture the color

    // Insert into feedcontent with the color value
    $sql = "INSERT INTO feedcontent (ContentCode, Title, Captions, dept_ID, ContentColor) 
            VALUES ('$contentCode', '$title', '$caption', '$deptID', '$color')"; // Include ContentColor

    if ($conn->query($sql) === TRUE) {
        // Get the newly created ContentID
        $contentID = $conn->insert_id;

        // Get the DepartmentFolderID that matches the dept_ID in departmentfolders
        $departmentQuery = "SELECT DepartmentFolderID FROM departmentfolders WHERE dept_ID = '$deptID' LIMIT 1";
        $departmentResult = $conn->query($departmentQuery);

        if ($departmentResult && $departmentResult->num_rows > 0) {
            $departmentRow = $departmentResult->fetch_assoc();
            $departmentFolderID = $departmentRow['DepartmentFolderID'];

            // Insert into gradelevelfolders with the DepartmentFolderID and new ContentID
            $creationTimestamp = date('Y-m-d H:i:s');
            $gradeLevelSql = "INSERT INTO gradelevelfolders (DepartmentFolderID, ContentID, CreationTimestamp) 
                              VALUES ('$departmentFolderID', '$contentID', '$creationTimestamp')";

            if ($conn->query($gradeLevelSql) === TRUE) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => $conn->error]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'DepartmentFolderID not found']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
} elseif ($action == 'read' && isset($_GET['deptID'])) {
    // Existing read functionality
    $deptID = $conn->real_escape_string($_GET['deptID']);

    $sql = "SELECT * FROM feedcontent WHERE dept_ID = '$deptID'";
    $result = $conn->query($sql);

    $feedcontent = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Get the user count for the current ContentID
            $contentID = $row['ContentID'];
            $countSql = "SELECT COUNT(*) AS user_count FROM usercontent WHERE ContentID = '$contentID'";
            $countResult = $conn->query($countSql);
            $countRow = $countResult->fetch_assoc();
            $userCount = $countRow['user_count'];

            $feedcontent[] = [
                'ContentID' => $row['ContentID'],
                'Title' => $row['Title'],
                'Captions' => $row['Captions'],
                'dept_ID' => $row['dept_ID'],
                'user_count' => $userCount,
                'ContentColor' => $row['ContentColor'] // Add color to the result
            ];
        }
    }
    echo json_encode(['feedcontent' => $feedcontent]);
} elseif ($action == 'delete' && isset($_GET['id'])) {
    // Existing delete functionality
    $contentID = $conn->real_escape_string($id);

    // First, delete related rows in gradelevelfolders
    $deleteGradeLevelFolders = "DELETE FROM gradelevelfolders WHERE ContentID = '$contentID'";
    
    if ($conn->query($deleteGradeLevelFolders) === TRUE) {
        // Now delete the row from feedcontent
        $deleteFeedContent = "DELETE FROM feedcontent WHERE ContentID = '$contentID'";
    
        if ($conn->query($deleteFeedContent) === TRUE) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
} elseif ($action == 'update' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['id'])) {
    // Existing update functionality
    $data = json_decode(file_get_contents("php://input"), true);

    $title = $conn->real_escape_string($data['Title']);
    $caption = $conn->real_escape_string($data['Captions']);
    $id = $conn->real_escape_string($_GET['id']);

    $sql = "UPDATE feedcontent SET Title = '$title', Captions = '$caption' WHERE ContentID = '$id'";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
} else {
    echo json_encode(['status' => 'invalid action']);
}

$conn->close();
?>
