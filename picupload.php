<?php
session_start();
include 'connection.php';

function write_log($message) {
    $logfile = '/tmp/logfile.log';
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($logfile, "[$timestamp] $message\n", FILE_APPEND);
}

if (!isset($_SESSION['user_id'])) {
    write_log("Profile upload attempt by non-logged-in user");
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_FILES['file'])) {
    // File validation checks
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($_FILES['file']['type'], $allowedTypes)) {
        echo json_encode(['status' => 'error', 'message' => 'Only JPEG, PNG, and GIF images are allowed']);
        exit;
    }

    $maxSize = 2 * 1024 * 1024; // 2MB
    if ($_FILES['file']['size'] > $maxSize) {
        echo json_encode(['status' => 'error', 'message' => 'File too large. Max 2MB allowed.']);
        exit;
    }

    if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $conn->begin_transaction(); // Start transaction
        
        try {
            $fileTmpPath = $_FILES['file']['tmp_name'];
            $fileName = $_FILES['file']['name'];
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

            // Sanitize filename
            $fileName = preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $fileName);
            // Generate new file name
            $newFileName = sprintf('%06d_%s', random_int(100000, 999999), basename($fileName));

            // Get file content directly from temp file
            $fileContent = file_get_contents($fileTmpPath);
            if ($fileContent === false) {
                throw new Exception("Failed to read uploaded file: $fileName");
            }

            // GitHub upload
            $githubRepo = "docmap2024/DocMaP";
            $branch = "main";
            $githubFileName = "UserProfile/" . $newFileName;
            $uploadUrl = "https://api.github.com/repos/$githubRepo/contents/img/$githubFileName";

            $githubToken = $_ENV['GITHUB_TOKEN'] ?? getenv('GITHUB_TOKEN');
            if (!$githubToken) {
                throw new Exception("GitHub token not found");
            }

            $content = base64_encode($fileContent);
            $data = json_encode([
                "message" => "Adding new profile image",
                "content" => $content,
                "branch" => $branch
            ]);

            $headers = [
                "Authorization: token $githubToken",
                "Content-Type: application/json",
                "User-Agent: DocMaP",
                "Accept: application/vnd.github.v3+json"
            ];

            $ch = curl_init($uploadUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === false || $httpCode != 201) {
                throw new Exception("GitHub upload failed for profile image: $newFileName - HTTP Code: $httpCode");
            }

            $responseData = json_decode($response, true);
            $githubDownloadUrl = $responseData['content']['download_url'];

            // Update database with filename
            $stmt = $conn->prepare("UPDATE useracc SET profile = ? WHERE UserID = ?");
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . $conn->error);
            }

            $stmt->bind_param("si", $newFileName, $user_id);

            if (!$stmt->execute()) {
                throw new Exception("Database execute failed: " . $stmt->error);
            }

            $affectedRows = $stmt->affected_rows;
            $stmt->close();

            if ($affectedRows === 0) {
                throw new Exception("No rows affected - user may not exist");
            }

            $conn->commit(); // Commit transaction if all successful
            
            write_log("Profile successfully updated: $newFileName");
            echo json_encode([
                'status' => 'success', 
                'message' => 'Profile updated', 
                'filename' => $newFileName,
                'github_url' => $githubDownloadUrl
            ]);

        } catch (Exception $e) {
            $conn->rollback(); // Rollback on any error
            write_log("Error: " . $e->getMessage());
            echo json_encode([
                'status' => 'error', 
                'message' => 'Operation failed',
                'error_details' => $e->getMessage()
            ]);
        }
    } else {
        $errorMsg = 'File upload error.';
        if (isset($_FILES['file']['error'])) {
            $errorMsg .= ' Error code: ' . $_FILES['file']['error'];
        }
        write_log($errorMsg);
        echo json_encode(['status' => 'error', 'message' => $errorMsg]);
    }
} else {
    write_log("No file uploaded");
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded.']);
}

$conn->close();
?>