<?php
// Add strict error reporting at the very top
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logfile.log');
error_reporting(E_ALL);

session_start();
require 'connection.php';

// Ensure we only return JSON
header('Content-Type: application/json');

// Validate session first
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['status' => 'error', 'message' => 'User not logged in']));
}

// Check if file was uploaded
if (!isset($_FILES['logoFile']) || $_FILES['logoFile']['error'] !== UPLOAD_ERR_OK) {
    $error_message = 'No file uploaded or upload error';
    if (isset($_FILES['logoFile'])) {
        $error_codes = [
            0 => 'There is no error, the file uploaded with success',
            1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
            3 => 'The uploaded file was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk',
            8 => 'A PHP extension stopped the file upload'
        ];
        $error_message = $error_codes[$_FILES['logoFile']['error']] ?? 'Unknown upload error';
    }
    http_response_code(400);
    exit(json_encode(['status' => 'error', 'message' => $error_message]));
}

// Process the uploaded file
$file = $_FILES['logoFile'];
$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

if (!in_array($file_ext, $allowed_extensions)) {
    http_response_code(400);
    exit(json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPG, PNG, GIF allowed.']));
}

// Generate unique filename
$unique_filename = uniqid() . '.' . $file_ext;

// GitHub Repository Details
$githubRepo = "docmap2024/DocMaP"; // GitHub username/repo
$branch = "main";
$uploadUrl = "https://api.github.com/repos/$githubRepo/contents/Admin/img/Logo/$unique_filename";

// Fetch GitHub Token from Environment Variables
$githubToken = $_ENV['GITHUB_TOKEN'] ?? null;
if (!$githubToken) {
    http_response_code(500);
    exit(json_encode(['status' => 'error', 'message' => 'GitHub token not configured']));
}

// Prepare File Data for GitHub
$content = base64_encode(file_get_contents($file['tmp_name']));
$data = json_encode([
    "message" => "Uploading school logo",
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

if ($response === false) {
    http_response_code(500);
    exit(json_encode(['status' => 'error', 'message' => 'GitHub upload failed: ' . curl_error($ch)]));
}

$responseData = json_decode($response, true);
curl_close($ch);

if ($httpCode != 201) {
    http_response_code(500);
    exit(json_encode(['status' => 'error', 'message' => 'GitHub upload failed with status: ' . $httpCode]));
}

// Get the download URL from GitHub response
$githubDownloadUrl = $responseData['content']['download_url'];

// Update database with GitHub URL
try {
    $stmt = $conn->prepare("UPDATE school_details SET Logo = ? WHERE school_details_ID = 1");
    $stmt->bind_param("s", $unique_filename);
    
    if (!$stmt->execute()) {
        http_response_code(500);
        exit(json_encode(['status' => 'error', 'message' => 'Database update failed: ' . $conn->error]));
    }
    
    $stmt->close();
    
    // Return success response
    echo json_encode([
        'status' => 'success', 
        'message' => 'Logo updated successfully', 
        'filename' => $unique_filename,
        'url' => $githubDownloadUrl
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]));
}

$conn->close();
?>
