<?php

include 'connection.php';
session_start(); // Start session if not already started

$user_id = $_SESSION['user_id']; // Initialize $user_id from session

// Query to fetch subjects from feedcontent table including ContentColor
$sql_subjects = "SELECT fs.ContentID, fs.Title, fs.Captions, fs.ContentColor, uc.Timestamp
                FROM feedcontent fs
                INNER JOIN usercontent uc ON fs.ContentID = uc.ContentID
                WHERE uc.UserID = $user_id AND uc.Status = 1";
$result_subjects = mysqli_query($conn, $sql_subjects);

$subjects = [];

if (mysqli_num_rows($result_subjects) > 0) {
    while ($row_subject = mysqli_fetch_assoc($result_subjects)) {
        $subject_id = $row_subject['ContentID'];
        $title = $row_subject['Title'];
        $captions = $row_subject['Captions'];
        $content_color = htmlspecialchars($row_subject['ContentColor']); // Fetch the ContentColor
        $user_joined_timestamp = $row_subject['Timestamp']; // Timestamp when user joined

        // Generate initials
        $words = explode(' ', $title);
        $initials = strtoupper(substr($words[0], 0, 1));
        if (isset($words[1]) && is_numeric($words[1])) {
            $initials .= $words[1]; // Include the full number (e.g., "G10")
        } else {
            $initials .= strtoupper(substr($words[1], 0, 1)); // Default for non-numeric
        }

        // Get total tasks for this ContentID associated with the user after their join Timestamp
        $sql_total_tasks = "SELECT COUNT(*) AS total_tasks 
                            FROM tasks t
                            WHERE t.ContentID = '$subject_id' 
                              AND t.Timestamp >= '$user_joined_timestamp'
                              AND t.Status ='Assign'
                              AND t.Type = 'Task'
                              AND t.ApprovalStatus = 'Approved'";
        $result_total_tasks = mysqli_query($conn, $sql_total_tasks);
        $total_tasks = ($row_total_tasks = mysqli_fetch_assoc($result_total_tasks)) ? $row_total_tasks['total_tasks'] : 0;

        // Get counts of tasks with "Submitted" and "Approved" status
        $sql_status_counts = "SELECT 
                                SUM(CASE WHEN tu.Status = 'Submitted' THEN 1 ELSE 0 END) AS submitted_count,
                                SUM(CASE WHEN tu.Status = 'Approved' THEN 1 ELSE 0 END) AS approved_count
                              FROM task_user tu
                              INNER JOIN tasks t ON tu.TaskID = t.TaskID
                              WHERE t.ContentID = $subject_id 
                                AND t.Timestamp >= '$user_joined_timestamp' 
                                AND tu.UserID = $user_id";
        $result_status_counts = mysqli_query($conn, $sql_status_counts);
        $submitted_count = 0;
        $approved_count = 0;
        if ($row_status_counts = mysqli_fetch_assoc($result_status_counts)) {
            $submitted_count = $row_status_counts['submitted_count'];
            $approved_count = $row_status_counts['approved_count'];
        }

        // Calculate the total of submitted and approved tasks
        $total_approved_submitted = $submitted_count + $approved_count;

        // Prepare subject data for JSON response
        $subjects[] = [
            'id' => $subject_id,
            'title' => $title,
            'captions' => $captions,
            'initials' => $initials,
            'color' => $content_color,
            'total_tasks' => $total_tasks,
            'submitted_count' => $submitted_count,
            'approved_count' => $approved_count,
            'total_approved_submitted' => $total_approved_submitted, // Include total
        ];
    }
}

echo json_encode($subjects);
mysqli_close($conn);

?>
