<?php
session_start();
include 'connection.php'; // Include your database connection file

// Function to write to log file
function write_log($message) {
    $logfile = '/tmp/logfile.log'; // Use tmp directory
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($logfile, "[$timestamp] $message\n", FILE_APPEND);
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Check if a file was uploaded
    if (isset($_FILES['esignatureFile']) && $_FILES['esignatureFile']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../img/e_sig/';
        $fileTmpPath = $_FILES['esignatureFile']['tmp_name'];
        $fileName = $_FILES['esignatureFile']['name'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $fileMimeType = $_FILES['esignatureFile']['type'];
        $fileSize = $_FILES['esignatureFile']['size'];

        // Generate new file name with 6-digit random number
        $newFileName = sprintf('%06d_%s', random_int(100000, 999999), basename($fileName));
        $newFilePath = $uploadDir . $newFileName;

        // First, move the file to the local target directory
        if (move_uploaded_file($fileTmpPath, $newFilePath)) {
            // GitHub Repository Details
            $githubRepo = "docmap2024/DocMaP"; // GitHub username/repo
            $branch = "main";
            $githubFileName = "e_sig/" . $newFileName; // Store in e_signatures folder
            $uploadUrl = "https://api.github.com/repos/$githubRepo/contents/img/$githubFileName";
        
            // Fetch GitHub Token from Environment Variables
            $githubToken = $_ENV['GITHUB_TOKEN'] ?? null;
            if (!$githubToken) {
                write_log("GitHub token not found in environment variables");
                echo json_encode(['status' => 'error', 'message' => 'Server configuration error.']);
                exit;
            }
        
            // Prepare File Data for GitHub
            $content = base64_encode(file_get_contents($newFilePath));
            $data = json_encode([
                "message" => "Adding new e-signature file",
                "content" => $content,
                "branch" => $branch
            ]);
        
            $headers = [
                "Authorization: token $githubToken",
                "Content-Type: application/json",
                "User-Agent: DocMaP"
            ];
        
            // GitHub API Call
            $ch = curl_init($uploadUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Delete Local File After Upload to GitHub
            if (file_exists($newFilePath)) {
                unlink($newFilePath);
                write_log("Local e-signature file deleted: $newFilePath");
            }

            if ($response === false) {
                write_log("GitHub upload failed for e-signature file: $newFileName");
                echo json_encode(['status' => 'error', 'message' => 'File upload to cloud storage failed.']);
                exit;
            } 
            
            $responseData = json_decode($response, true);
            if ($httpCode == 201) { // Successful upload
                $githubDownloadUrl = $responseData['content']['download_url'];
                write_log("E-signature file uploaded to GitHub: $newFileName, Download URL: $githubDownloadUrl");
        
                // Update the database with the GitHub URL
                $query = "UPDATE useracc SET esig = ? WHERE UserID = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('si', $githubDownloadUrl, $user_id);

                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success']);
                } else {
                    write_log("Database update failed for e-signature: " . $stmt->error);
                    echo json_encode(['status' => 'error', 'message' => 'Database update failed.']);
                }
            } else {
                write_log("GitHub upload failed for e-signature file: $newFileName - HTTP Code: $httpCode");
                echo json_encode(['status' => 'error', 'message' => 'File upload to cloud storage failed.']);
            }
        } else {
            write_log("Error moving uploaded e-signature file: $fileName");
            echo json_encode(['status' => 'error', 'message' => 'File upload failed.']);
        }
    } else {
        $errorMsg = 'No file uploaded or invalid file.';
        if (isset($_FILES['esignatureFile']['error'])) {
            $errorMsg .= ' Error code: ' . $_FILES['esignatureFile']['error'];
        }
        write_log($errorMsg);
        echo json_encode(['status' => 'error', 'message' => $errorMsg]);
    }
} else {
    write_log("E-signature upload attempt by non-logged-in user");
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
}
?>