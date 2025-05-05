<?php
session_start();
include 'connection.php';

// Function to write to log file
function write_log($message) {
    $logfile = '/tmp/logfile.log';
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($logfile, "[$timestamp] $message\n", FILE_APPEND);
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Check if a file was uploaded
    if (isset($_FILES['esignatureFile'])) {
        // File validation checks
        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($_FILES['esignatureFile']['size'] > $maxSize) {
            echo json_encode(['status' => 'error', 'message' => 'File too large. Max 2MB allowed.']);
            exit;
        }

        $allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
        if (!in_array($_FILES['esignatureFile']['type'], $allowedTypes)) {
            echo json_encode(['status' => 'error', 'message' => 'Only PNG, JPEG, and GIF images are allowed.']);
            exit;
        }

        if ($_FILES['esignatureFile']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['esignatureFile']['tmp_name'];
            $fileName = $_FILES['esignatureFile']['name'];
            
            // Sanitize filename
            $fileName = preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $fileName);
            $newFileName = sprintf('%06d_%s', random_int(100000, 999999), $fileName);

            // Get file content directly from temp file
            $fileContent = file_get_contents($fileTmpPath);
            if ($fileContent === false) {
                write_log("Failed to read uploaded file: $fileName");
                echo json_encode(['status' => 'error', 'message' => 'Failed to process file']);
                exit;
            }

            // GitHub Repository Details
            $githubRepo = "docmap2024/DocMaP";
            $branch = "main";
            $githubFileName = "e_sig/" . $newFileName;
            $uploadUrl = "https://api.github.com/repos/$githubRepo/contents/img/$githubFileName";
        
            // Fetch GitHub Token from Environment Variables
            $githubToken = $_ENV['GITHUB_TOKEN'] ?? null;
            if (!$githubToken) {
                write_log("GitHub token not found in environment variables");
                echo json_encode(['status' => 'error', 'message' => 'Server configuration error.']);
                exit;
            }
        
            // Prepare File Data for GitHub
            $content = base64_encode($fileContent);
            $data = json_encode([
                "message" => "Adding new e-signature file",
                "content" => $content,
                "branch" => $branch
            ]);
        
            $headers = [
                "Authorization: token $githubToken",
                "Content-Type: application/json",
                "User-Agent: DocMaP",
                "Accept: application/vnd.github.v3+json"
            ];
        
            // GitHub API Call
            $ch = curl_init($uploadUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                write_log("GitHub upload failed: $curlError");
                echo json_encode(['status' => 'error', 'message' => 'File upload to cloud storage failed.']);
                exit;
            } 
            
            $responseData = json_decode($response, true);
            if ($httpCode == 201) {
                $githubDownloadUrl = $responseData['content']['download_url'];
                write_log("E-signature uploaded to GitHub: $newFileName, URL: $githubDownloadUrl");
        
                // Update database with GitHub URL
                $query = "UPDATE useracc SET esig = ? WHERE UserID = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('si', $newFileName, $user_id);

                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success']);
                } else {
                    write_log("Database update failed: " . $stmt->error);
                    echo json_encode(['status' => 'error', 'message' => 'Database update failed.']);
                }
            } else {
                write_log("GitHub API error: HTTP $httpCode - " . print_r($responseData, true));
                $errorMsg = isset($responseData['message']) ? $responseData['message'] : 'File upload to cloud storage failed.';
                echo json_encode(['status' => 'error', 'message' => $errorMsg]);
            }
        } else {
            $errorMsg = 'File upload error.';
            if (isset($_FILES['esignatureFile']['error'])) {
                $errorMsg .= ' Error code: ' . $_FILES['esignatureFile']['error'];
            }
            write_log($errorMsg);
            echo json_encode(['status' => 'error', 'message' => $errorMsg]);
        }
    } else {
        write_log("No file uploaded");
        echo json_encode(['status' => 'error', 'message' => 'No file uploaded.']);
    }
} else {
    write_log("E-signature upload attempt by non-logged-in user");
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
}
?>