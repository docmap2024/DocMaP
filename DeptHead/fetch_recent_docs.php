<?php
include 'connection.php';

$user_id = $_SESSION['user_id'];  // Ensure session is already active
if (!isset($_SESSION['dept_ID'])) {
    echo "Department ID not found in session.";
    exit;
}
$dept_ID = $_SESSION['dept_ID']; // Get dept_ID from session

// Query to get recent tasks and teacher names for a specific department
$sql = "
    SELECT 
        ua.UserID,
        CONCAT(ua.fname, ' ', ua.lname) AS name,
        t.Title AS taskTitle,
        t.timestamp AS taskTimestamp
    FROM 
        useracc ua
    LEFT JOIN 
        task_user tu ON ua.UserID = tu.UserID
    LEFT JOIN 
        tasks t ON tu.TaskID = t.TaskID
    LEFT JOIN 
        feedcontent fc ON t.ContentID = fc.ContentID
    WHERE 
        ua.role = 'Teacher'
        AND fc.dept_ID = ? AND tu.Status = 'Submitted'  
    ORDER BY 
        t.timestamp DESC
    LIMIT 2;  -- Limit to 2 most recent tasks
";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $dept_ID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        echo "<ul style='list-style: none; padding-left: 0;'>";

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<div style='max-height: 200px; overflow-y: auto;'>
            <ul style='list-style: none; padding-left: 0; margin: 0;'>
                <li class='task-item' style='margin-bottom: 15px;'>
                    <div style='font-size: 16px; font-weight:bold' >Title:
                        " . htmlspecialchars($row['taskTitle']) . "
                    </div>
                     <small>Submitted by:</small>
                    <div style='font-size: 14px; color: #777;'>
                        <small>" . htmlspecialchars($row['name']) . "</small>
                    </div>
                </li>
            </ul>
          </div>";
        }

        echo "</ul>";
    } else {
        echo "<p>No recent submissions found.</p>";
    }

    mysqli_stmt_close($stmt);
} else {
    echo "<p>Error with query execution.</p>";
}
?>

<style>
    /* General Styles for Task Item */
    .task-item {
        margin-bottom: 15px;
        padding: 10px;
        font-size: 16px;
        border-bottom: 1px solid #ddd;
        display: flex;
        flex-direction: column;
    }

    .task-item strong {
        font-size: 18px;
        color: #333;
    }

    .task-item small {
        font-size: 14px;
        color: #777;
    }

    /* Scrollable Container */
    .scrollable-container {
        max-height: 200px;
        overflow-y: auto;
        padding-right: 5px; /* To prevent scroll from being cut off */
    }

    /* Responsive Styles */
    @media (max-width: 768px) {
        .task-item {
            font-size: 14px;
        }

        .task-item strong {
            font-size: 16px;
        }

        .task-item small {
            font-size: 12px;
        }
    }

    @media (max-width: 576px) {
        .task-item {
            padding: 5px;
            font-size: 12px;
        }

        .task-item strong {
            font-size: 14px;
        }

        .task-item small {
            font-size: 10px;
        }
    }
</style>
