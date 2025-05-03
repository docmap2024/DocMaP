<?php
// Define the target directory and filename for GitHub
$githubTargetDir = "TeacherData/";
$filename = "LNHS-Teachers.xlsx";
$githubTargetFile = $githubTargetDir . $filename;

// Function to upload file to GitHub
function uploadToGitHub($tempFilePath, $targetPath) {
    $githubRepo = "docmap2024/DocMaP"; // Your GitHub repo
    $branch = "main";
    $githubToken = $_ENV['GITHUB_TOKEN'] ?? null;

    if (!$githubToken) {
        return ['status' => 'error', 'message' => 'GitHub token not configured'];
    }

    // Prepare file content from the temporary uploaded file
    $content = base64_encode(file_get_contents($tempFilePath));
    $data = [
        "message" => "Upload teacher data file",
        "content" => $content,
        "branch" => $branch
    ];

    // Check if file exists to get SHA for update
    $ch = curl_init("https://api.github.com/repos/$githubRepo/contents/Admin/$targetPath");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token $githubToken",
        "User-Agent: PHP-Script",
        "Accept: application/vnd.github.v3+json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        $fileData = json_decode($response, true);
        $data["sha"] = $fileData['sha']; // Required for updates
    }

    // Upload to GitHub
    $ch = curl_init("https://api.github.com/repos/$githubRepo/contents/Admin/$targetPath");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token $githubToken",
        "Content-Type: application/json",
        "User-Agent: PHP-Script"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode === 201 || $httpCode === 200;
}

// Check if the file was uploaded via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES["file"])) {
    // Get the uploaded file details
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION));

    // Check if the file is an Excel file
    if ($fileType != "xls" && $fileType != "xlsx") {
        echo json_encode(['status' => 'error', 'message' => 'Only Excel files are allowed.']);
        exit;
    }

    // Upload directly to GitHub from the temporary upload location
    if (uploadToGitHub($_FILES["file"]["tmp_name"], $githubTargetFile)) {
        echo json_encode(['status' => 'success', 'message' => 'File uploaded to GitHub successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload file to GitHub.']);
    }
}
?>