<?php
session_start();
include 'connection.php';

$response = ['status' => 'error', 'message' => 'Something went wrong'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['template_file'])) {
    $userId = $_SESSION['user_id'];
    $name = mysqli_real_escape_string($conn, $_POST['template_name']);
    $file = $_FILES['template_file'];
    $mimetype = $file['type'];
    $size = $file['size'];
    $created_at = date('Y-m-d H:i:s');

    // Generate a unique filename
    $newFileName = uniqid() . '_' . basename($file['name']);
    
    // GitHub Repository Details
    $githubRepo = "docmap2024/DocMaP";
    $branch = "main";
    $githubFileName = "Admin/Templates/" . $newFileName;
    $uploadUrl = "https://api.github.com/repos/$githubRepo/contents/$githubFileName";
    
    // Fetch GitHub Token from Environment Variables
    $githubToken = $_ENV['GITHUB_TOKEN'] ?? null;
    if (!$githubToken) {
        error_log("GitHub token not found in environment variables");
        $response['message'] = 'Server configuration error';
        echo json_encode($response);
        exit;
    }
    
    // Get file content directly from the uploaded file
    $fileContent = file_get_contents($file['tmp_name']);
    $content = base64_encode($fileContent);
    
    // Prepare GitHub API data
    $data = json_encode([
        "message" => "Adding new template file",
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
    
    $githubResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 201) {
        // GitHub upload succeeded, now store metadata in database
        $githubFileUrl = "https://raw.githubusercontent.com/$githubRepo/$branch/$githubFileName";
        $query = "INSERT INTO `templates` (`UserID`, `name`, `filename`, `mimetype`, `size`, `uri`, `created_at`) 
                 VALUES ('$userId', '$name', '$newFileName', '$mimetype', '$size', '$githubFileUrl', '$created_at')";
        
        if (mysqli_query($conn, $query)) {
            $response = ['status' => 'success', 'message' => 'Template uploaded successfully to GitHub'];
        } else {
            $response['message'] = 'Database insertion failed';
            // You might want to delete the GitHub file here if the DB insert fails
        }
    } else {
        error_log("GitHub upload failed. HTTP Code: $httpCode, Error: $curlError");
        $response['message'] = 'Failed to upload template to GitHub';
    }
}

echo json_encode($response);
?>