<?php
session_start();
include 'connection.php'; // Replace with your database connection file

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userIDs = $input['userIDs'] ?? [];

    if (!empty($userIDs)) {
        try {
            $placeholders = implode(',', array_fill(0, count($userIDs), '?'));
            $sql = "DELETE FROM useracc WHERE UserID IN ($placeholders)";

            $stmt = $conn->prepare($sql);
            $stmt->execute($userIDs);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Failed to delete users.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No users selected.']);
    }
}
?>
