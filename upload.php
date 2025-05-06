<?php
session_start();
include 'connection.php';

// Log helper
function write_log($message) {
    $logfile = '/tmp/logfile.log';
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($logfile, "[$timestamp] $message\n", FILE_APPEND);
}

$response = ['success' => false, 'error' => null];

if (!isset($_SESSION['user_id'])) {
    write_log("Unauthorized document upload attempt");
    $response['error'] = "User not logged in.";
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_POST['task_id'], $_POST['content_id'])) {
    $response['error'] = "Task ID and Content ID are required.";
    echo json_encode($response);
    exit();
}

$task_id = $_POST['task_id'];
$content_id = $_POST['content_id'];

// Update previous document status
$updateStatusQuery = "UPDATE documents SET Status = 1 WHERE UserID = ? AND TaskID = ? AND ContentID = ? AND Status != 1";
$stmt = $conn->prepare($updateStatusQuery);
$stmt->bind_param("iii", $user_id, $task_id, $content_id);
if (!$stmt->execute()) {
    $response['error'] = "Error updating document status: " . $stmt->error;
    echo json_encode($response);
    exit();
}

// Process file uploads
if (!empty($_FILES['files']['name'][0])) {
    foreach ($_FILES['files']['name'] as $key => $name) {
        $tmpPath = $_FILES['files']['tmp_name'][$key];
        $originalFileName = preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $name);
        $uniqueFileName = sprintf('%06d_%s', random_int(100000, 999999), $originalFileName);

        // Size validation (max 5MB)
        if ($_FILES['files']['size'][$key] > 5 * 1024 * 1024) {
            $response['error'] = "File too large: $name (max 5MB)";
            echo json_encode($response);
            exit();
        }

        $fileContent = file_get_contents($tmpPath);
        if ($fileContent === false) {
            write_log("Failed to read file: $originalFileName");
            $response['error'] = "Failed to read uploaded file.";
            echo json_encode($response);
            exit();
        }

        // === GitHub Upload ===
        $repo = "docmap2024/DocMaP";
        $branch = "main";
        $uploadPath = "Documents/" . $uniqueFileName; // Ensure it's always in Documents/
        $uploadUrl = "https://api.github.com/repos/$repo/contents/$uploadPath";
        write_log("Uploading to: $uploadUrl");

        $githubToken = $_ENV['GITHUB_TOKEN'] ?? null;
        if (!$githubToken) {
            write_log("Missing GitHub token");
            $response['error'] = "Server configuration error.";
            echo json_encode($response);
            exit();
        }

        $payload = json_encode([
            "message" => "Upload document: $uniqueFileName",
            "content" => base64_encode($fileContent),
            "branch" => $branch
        ]);

        $headers = [
            "Authorization: token $githubToken",
            "Content-Type: application/json",
            "User-Agent: DocMaP"
        ];

        $ch = curl_init($uploadUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $responseGitHub = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($responseGitHub === false || $httpCode != 201) {
            write_log("GitHub error: $curlError - $responseGitHub");
            $response['error'] = "GitHub upload failed.";
            echo json_encode($response);
            exit();
        }

        $ghData = json_decode($responseGitHub, true);
        $downloadUrl = $ghData['content']['download_url'] ?? '';

        // === Get GradeLevelFolderID ===
        $gradeLevelID = null;
        $gradeQuery = "SELECT GradeLevelFolderID FROM gradelevelfolders WHERE ContentID = ? LIMIT 1";
        $stmt = $conn->prepare($gradeQuery);
        $stmt->bind_param("i", $content_id);
        $stmt->execute();
        $stmt->bind_result($gradeLevelID);
        $stmt->fetch();
        $stmt->close();

        if (!$gradeLevelID) {
            $response['error'] = "GradeLevelFolderID not found.";
            echo json_encode($response);
            exit();
        }

        // === Get UserFolderID ===
        $userContentID = null;
        $stmt = $conn->prepare("SELECT UserContentID FROM usercontent WHERE UserID = ? AND ContentID = ?");
        $stmt->bind_param("ii", $user_id, $content_id);
        $stmt->execute();
        $stmt->bind_result($userContentID);
        $stmt->fetch();
        $stmt->close();

        if (!$userContentID) {
            $response['error'] = "UserContentID not found.";
            echo json_encode($response);
            exit();
        }

        $userFolderID = null;
        $stmt = $conn->prepare("SELECT UserFolderID FROM userfolders WHERE UserContentID = ?");
        $stmt->bind_param("i", $userContentID);
        $stmt->execute();
        $stmt->bind_result($userFolderID);
        $stmt->fetch();
        $stmt->close();

        if (!$userFolderID) {
            $response['error'] = "UserFolderID not found.";
            echo json_encode($response);
            exit();
        }

        // === Insert document ===
        $stmt = $conn->prepare("INSERT INTO documents 
            (GradeLevelFolderID, UserFolderID, UserID, ContentID, TaskID, name, uri, mimeType, size, Status, TimeStamp) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'application/octet-stream', 0, 1, NOW())");
        $stmt->bind_param("iiiiiss", $gradeLevelID, $userFolderID, $user_id, $content_id, $task_id, $uniqueFileName, $downloadUrl);
        if (!$stmt->execute()) {
            $response['error'] = "Database insert failed: " . $stmt->error;
            echo json_encode($response);
            exit();
        }

        $response['success'] = true;
    }
}

// Update task_user status
$updateTaskUser = $conn->prepare("UPDATE task_user SET Status = 'Submitted', SubmitDate = NOW() WHERE TaskID = ? AND UserID = ? AND Status != 'Submitted'");
$updateTaskUser->bind_param("ii", $task_id, $user_id);
$updateTaskUser->execute();

echo json_encode($response);
exit();
?>
