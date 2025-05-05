<?php
session_start();
include 'connection.php';
include 'log_function.php'; // Optional, use only if available

if (!isset($_SESSION['user_id'])) {
    write_log("Profile upload attempt by non-logged-in user");
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'img/UserProfile/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileTmpPath = $_FILES['file']['tmp_name'];
    $fileName = $_FILES['file']['name'];
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

    // Generate new file name with 6-digit random number
    $newFileName = sprintf('%06d_%s', random_int(100000, 999999), basename($fileName));
    $newFilePath = $uploadDir . $newFileName;

    // Move to local directory
    if (move_uploaded_file($fileTmpPath, $newFilePath)) {
        // GitHub details
        $githubRepo = "docmap2024/DocMaP";
        $branch = "main";
        $githubFileName = "UserProfile/" . $newFileName;
        $uploadUrl = "https://api.github.com/repos/$githubRepo/contents/img/$githubFileName";

        $githubToken = $_ENV['GITHUB_TOKEN'] ?? getenv('GITHUB_TOKEN');
        if (!$githubToken) {
            write_log("GitHub token not found");
            echo json_encode(['status' => 'error', 'message' => 'Server configuration error']);
            exit;
        }

        $content = base64_encode(file_get_contents($newFilePath));
        $data = json_encode([
            "message" => "Adding new profile image",
            "content" => $content,
            "branch" => $branch
        ]);

        $headers = [
            "Authorization: token $githubToken",
            "Content-Type: application/json",
            "User-Agent: DocMaP"
        ];

        $ch = curl_init($uploadUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Remove local file after upload
        if (file_exists($newFilePath)) {
            unlink($newFilePath);
            write_log("Local profile image deleted: $newFilePath");
        }

        if ($response === false || $httpCode != 201) {
            write_log("GitHub upload failed for profile image: $newFileName - HTTP Code: $httpCode");
            echo json_encode(['status' => 'error', 'message' => 'File upload to GitHub failed']);
            exit;
        }

        $responseData = json_decode($response, true);
        $githubDownloadUrl = $responseData['content']['download_url'];

        // Maintain your SQL query structure
        $stmt = $conn->prepare("UPDATE useracc SET profile = ? WHERE UserID = ?");
        $stmt->bind_param("si", $newFileName, $user_id); // Do NOT change this per instructions

        if ($stmt->execute()) {
            write_log("Profile updated with new image: $newFileName");
            echo json_encode(['status' => 'success', 'message' => 'Profile updated', 'filename' => $newFileName]);
        } else {
            write_log("Database update failed for profile: " . $stmt->error);
            echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
        }
        $stmt->close();
    } else {
        write_log("Error moving uploaded profile file: $fileName");
        echo json_encode(['status' => 'error', 'message' => 'File move failed']);
    }
} else {
    $errorMsg = 'No file uploaded or invalid file.';
    if (isset($_FILES['file']['error'])) {
        $errorMsg .= ' Error code: ' . $_FILES['file']['error'];
    }
    write_log($errorMsg);
    echo json_encode(['status' => 'error', 'message' => $errorMsg]);
}

$conn->close();
?>
