<?php
session_start();
include 'connection.php';

// Check if the id is set in the GET request
if (isset($_GET['id'])) {
    $templateId = $_GET['id'];
    
    // First, get the GitHub file details before deleting from database
    $query = "SELECT filename, uri FROM templates WHERE TemplateID = ?";
    if ($stmt = mysqli_prepare($conn, $query)) {
        mysqli_stmt_bind_param($stmt, 'i', $templateId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $template = mysqli_fetch_assoc($result);
        
        if ($template) {
            // GitHub Repository Details
            $githubRepo = "docmap2024/DocMaP";
            $branch = "main";
            $githubFileName = "Admin/Templates/" . $template['filename'];
            
            // Extract SHA hash from GitHub URL (needed for deletion)
            $githubFileUrl = $template['uri'];
            $githubApiUrl = "https://api.github.com/repos/$githubRepo/contents/$githubFileName?ref=$branch";
            
            // Fetch GitHub Token from Environment Variables
            $githubToken = $_ENV['GITHUB_TOKEN'] ?? null;
            if (!$githubToken) {
                $_SESSION['error'] = 'GitHub token not configured.';
                header("Location: templates.php");
                exit();
            }
            
            // First get the file's SHA (required for deletion)
            $ch = curl_init($githubApiUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: token $githubToken",
                "User-Agent: DocMaP",
                "Accept: application/vnd.github.v3+json"
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($httpCode === 200) {
                $fileInfo = json_decode($response, true);
                $sha = $fileInfo['sha'];
                
                // Prepare deletion data
                $data = json_encode([
                    "message" => "Deleting template file: " . $template['filename'],
                    "sha" => $sha,
                    "branch" => $branch
                ]);
                
                // Delete from GitHub
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                $deleteResponse = curl_exec($ch);
                $deleteHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($deleteHttpCode === 200) {
                    // Now delete from database
                    $deleteQuery = "DELETE FROM templates WHERE TemplateID = ?";
                    if ($deleteStmt = mysqli_prepare($conn, $deleteQuery)) {
                        mysqli_stmt_bind_param($deleteStmt, 'i', $templateId);
                        if (mysqli_stmt_execute($deleteStmt)) {
                            $_SESSION['message'] = 'Template deleted successfully from GitHub and database.';
                        } else {
                            $_SESSION['error'] = 'Database deletion failed after GitHub deletion.';
                        }
                    }
                } else {
                    $_SESSION['error'] = 'Failed to delete file from GitHub.';
                }
            } else {
                $_SESSION['error'] = 'Could not get file info from GitHub for deletion.';
                curl_close($ch);
            }
        } else {
            $_SESSION['error'] = 'Template not found in database.';
        }
    } else {
        $_SESSION['error'] = 'Failed to prepare SQL statement.';
    }
} else {
    $_SESSION['error'] = 'Invalid request. No template ID provided.';
}

header("Location: templates.php");
exit();
?>