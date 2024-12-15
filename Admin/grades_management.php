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

    $sql = "
    SELECT fc.*, d.dept_name 
    FROM feedcontent fc
    JOIN department d ON fc.dept_ID = d.dept_ID
    WHERE fc.dept_ID = '$deptID'
    ";
    $result = $conn->query($sql);

    $feedcontent = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $contentID = $row['ContentID'];

            // Get the user count and associated user IDs, profiles, and full names
            $countSql = "
                SELECT uc.UserID,uc.Status, ua.Profile, 
                       CONCAT(ua.fname, ' ', ua.mname, '. ', ua.lname) AS FULLNAME
                FROM usercontent uc
                JOIN useracc ua ON uc.UserID = ua.UserID
                WHERE uc.ContentID = '$contentID'
                AND uc.Status=1;
            ";

            $countResult = $conn->query($countSql);

            $users = [];
            if ($countResult->num_rows > 0) {
                while ($userRow = $countResult->fetch_assoc()) {
                    $users[] = [
                        'UserID' => $userRow['UserID'],
                        'profile' => "../img/UserProfile/" . $userRow['Profile'],
                        'fullname' => $userRow['FULLNAME'], // Add the full name for tooltip
                    ];
                }
            }

            $feedcontent[] = [
                'ContentID' => $row['ContentID'],
                'Title' => $row['Title'],
                'Captions' => $row['Captions'],
                'dept_ID' => $row['dept_ID'],
                'dept_name' => $row['dept_name'], // Include department name
                'user_count' => count($users),
                'users' => $users,
                'ContentColor' => $row['ContentColor'], // Add color to the result
            ];
        }
    }
    echo json_encode(['feedcontent' => $feedcontent]);
}
elseif ($action == 'delete' && isset($_GET['id'])) {
    $contentID = $conn->real_escape_string($_GET['id']);

    // Step 1: Delete related rows in gradelevelfolders
    $deleteGradeLevelFolders = "DELETE FROM gradelevelfolders WHERE ContentID = '$contentID'";
    if ($conn->query($deleteGradeLevelFolders) === TRUE) {
        // Step 2: Delete the row from feedcontent
        $deleteFeedContent = "DELETE FROM feedcontent WHERE ContentID = '$contentID'";
        if ($conn->query($deleteFeedContent) === TRUE) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete from feedcontent: ' . $conn->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete from gradelevelfolders: ' . $conn->error]);
    }
}

elseif ($action == 'update' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['id'])) {
    // Existing update functionality
    $data = json_decode(file_get_contents("php://input"), true);

    $title = $conn->real_escape_string($data['Title']);
    $caption = $conn->real_escape_string($data['Captions']);
    $id = $conn->real_escape_string($_GET['id']);
    $color = $conn->real_escape_string($data['ContentColor']); // Get the updated color

    $sql = "UPDATE feedcontent SET Title = '$title', Captions = '$caption', ContentColor = '$color' WHERE ContentID = '$id'";

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