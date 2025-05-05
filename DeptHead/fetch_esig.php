<?php
session_start();
include 'connection.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    $query = "SELECT esig FROM useracc WHERE UserID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $esig = $row['esig'];
        if ($esig) {
            // Convert filename to full GitHub URL
            $githubUrl = "https://raw.githubusercontent.com/docmap2024/DocMaP/main/img/e_sig/" . $esig;
            echo json_encode(['esig' => $githubUrl]);
        } else {
            echo json_encode(['esig' => null]);
        }
    } else {
        echo json_encode(['esig' => null]);
    }
} else {
    echo json_encode(['esig' => null]);
}
?>