<?php
require 'connection.php'; // Include the database connection

// fetch_task_content.php
if (isset($_GET['taskID'])) {
    $taskID = intval($_GET['taskID']);
    $stmt = $pdo->prepare("SELECT taskContent FROM tasks WHERE TaskID = ?");
    $stmt->execute([$taskID]);
    $taskContent = $stmt->fetchColumn();
    echo $taskContent;
}

?>
