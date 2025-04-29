<?php
session_start();

// Redirect to index.php if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

// Initialize variables
$content_id = null;
$content_title = "";
$content_captions = "";
$color = "";
$tasks_exist = false;
$dept_name = "";
$dept_info = "";
$dept_type = "";

// Fetch content and tasks if content_id is provided
if (isset($_GET['content_id'])) {
    $content_id = $_GET['content_id'];
    $UserID = $_SESSION['user_id'];

    // Fetch content and department details
    $sql_content = "
        SELECT 
            fc.Title, 
            fc.Captions, 
            fc.ContentColor AS color, 
            fc.dept_ID, 
            d.dept_name,
            d.dept_type,
            d.dept_info
        FROM 
            feedcontent fc
        LEFT JOIN 
            department d ON fc.dept_ID = d.dept_ID
        WHERE 
            fc.ContentID = ?
    ";
    $stmt_content = $conn->prepare($sql_content);
    $stmt_content->bind_param("s", $content_id);
    $stmt_content->execute();
    $result_content = $stmt_content->get_result();

    if ($row_content = $result_content->fetch_assoc()) {
        $content_title = $row_content['Title'];
        $content_captions = $row_content['Captions'];
        $color = $row_content['color'];
        $dept_id = $row_content['dept_ID'];
        $dept_name = $row_content['dept_name'];
        $dept_type = $row_content['dept_type'];
        $dept_info = $row_content['dept_info'];
    }

    // Fetch tasks based on department type
    if ($dept_type == 'Academic') {
        // Fetch tasks for Academic departments
        $sql_tasks = "
            SELECT t.* 
            FROM tasks t 
            JOIN task_user tu ON t.TaskID = tu.TaskID 
            WHERE tu.ContentID = ? 
            AND t.Status = 'Assign' 
            AND tu.UserID = ? 
            ORDER BY t.Timestamp DESC
        ";
        $stmt_tasks = $conn->prepare($sql_tasks);
        $stmt_tasks->bind_param("si", $content_id, $UserID); // Bind content_id and UserID
    } elseif ($dept_type == 'Administrative') {
        // Fetch tasks for Administrative departments
        $sql_tasks = "
            SELECT t.* 
            FROM tasks t 
            JOIN task_department td ON t.TaskID = td.TaskID 
            WHERE td.dept_ID = ? 
            AND t.Status = 'Assign' 
            ORDER BY t.Timestamp DESC
        ";
        $stmt_tasks = $conn->prepare($sql_tasks);
        $stmt_tasks->bind_param("i", $dept_id); // Bind dept_id
    }

    $stmt_tasks->execute();
    $result_tasks = $stmt_tasks->get_result();

    if ($result_tasks->num_rows > 0) {
        $tasks_exist = true;
    }

    $stmt_tasks->close();
    $stmt_content->close();
}

// Fetch department and its tasks if deptId is provided
if (isset($_GET['deptId'])) {
    $deptId = $_GET['deptId'];

    // Fetch department details
    $sql_dept = "
        SELECT 
            d.dept_name,
            d.dept_type,
            d.dept_info
        FROM 
            department d
        WHERE 
            d.dept_ID = ?
    ";
    $stmt_dept = $conn->prepare($sql_dept);
    $stmt_dept->bind_param("i", $deptId);
    $stmt_dept->execute();
    $result_dept = $stmt_dept->get_result();

    if ($row_dept = $result_dept->fetch_assoc()) {
        $dept_name = $row_dept['dept_name'];
        $dept_type = $row_dept['dept_type'];
        $dept_info = $row_dept['dept_info'];
    }

    // Fetch tasks for the department
    $sql_tasks = "
        SELECT t.* 
        FROM tasks t 
        JOIN task_department td ON t.TaskID = td.TaskID 
        WHERE td.dept_ID = ? 
        AND t.Status = 'Assign' 
        ORDER BY t.Timestamp DESC
    ";
    $stmt_tasks = $conn->prepare($sql_tasks);
    $stmt_tasks->bind_param("i", $deptId);
    $stmt_tasks->execute();
    $result_tasks = $stmt_tasks->get_result();

    if ($result_tasks->num_rows > 0) {
        $tasks_exist = true;
    }

    $stmt_tasks->close();
    $stmt_dept->close();
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks</title>
    <link rel="icon" type="image/png" href="img/Logo/docmap-logo-1.png">
    <!-- ======= Styles ====== -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .taskList {
            list-style-type: none;
            padding: 0;
        }
        .taskList li {
            margin-bottom: 10px;
            padding: 10px;
            background-color: #f0f0f0;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .contentCard {
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            color: #fff;
            margin-left: 50px; /* Margin only on the left */
            margin-right: 50px; /* Margin only on the right */
            height: 300px;
        }
        .contentCard h2 {
            font-size: 40px;
        }
        .contentCard p {
            font-size: 16px;
            line-height: 1.6;
        }
        .taskContainer {
            background-color: #FFFF;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-left: 50px; /* Margin only on the left */
            margin-right: 50px; /* Margin only on the right */
            position: relative;
            height: 100px;
        }
        .taskContainer h3 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        .taskContainer p {
            font-size: 14px;
            color: #666;
        }
        .taskIcon {
            position: absolute;
            top: 50%;
            right: -25px; /* Adjust distance from the right */
            transform: translateY(-50%);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 50px;
        }
        .taskIcon ion-icon {
            color: #fff;
            font-size: 25px;
        }
        .tasktitle {
            color: black;
            transition: color 0.3s ease; /* Smooth transition */
        }
        .tasktitle:hover {
            color: <?php echo htmlspecialchars($color); ?>; /* Hover color dynamically set */
        }
    </style>
</head>
<body>
    <section id="sidebar"><?php include 'navbar.php'; ?></section>
    <section id="content">
        <?php include 'topbar.php'; ?>
        <main>
            <h1 class="title">Content</h1>

            <!-- Content Details -->
            <div class="contentCard" style="background-color: <?php echo ($dept_type == 'Administrative') ? '#9b2035' : $color; ?>;">
                <?php if ($dept_type == 'Academic'): ?>
                    <!-- Display Academic Department Information -->
                    <h2><?php echo $content_title; ?></h2>
                    <p><?php echo $content_captions; ?></p>
                <?php elseif ($dept_type == 'Administrative'): ?>
                    <!-- Display Administrative Department Information -->
                    <h2><?php echo $dept_name; ?></h2>
                    <p><?php echo $dept_info; ?></p>
                <?php endif; ?>
            </div>

            <!-- Tasks List -->
            <div class="taskList">
                <?php
                if ($tasks_exist) {
                    while ($row_task = mysqli_fetch_assoc($result_tasks)) {
                        $taskID = $row_task['TaskID'];
                        $taskTitle = $row_task['Title'];
                        $taskTimestamp = $row_task['TimeStamp'];
                        $taskType = $row_task['Type'];
                        $iconClass = '';
                        switch ($taskType) {
                            case 'Task': $iconClass = 'document-outline'; break;
                            case 'Announcement': $iconClass = 'notifications-outline'; break;
                        }
                        ?>
                        <a href='update_status_task.php?task_id=<?php echo htmlspecialchars($taskID); ?>&content_id=<?php echo htmlspecialchars($content_id); ?>' class='taskLink'>
                            <div class='taskContainer'>
                                <h3 class='tasktitle'><?php echo htmlspecialchars($taskTitle); ?></h3>
                                <p>Posted: <?php echo htmlspecialchars($taskTimestamp); ?></p>
                                <div class='taskIcon' style="background-color: <?php echo htmlspecialchars($color); ?>;">
                                    <ion-icon name='<?php echo htmlspecialchars($iconClass); ?>'></ion-icon>
                                </div>
                            </div>
                        </a>
                        <?php
                    }
                } else {
                    echo "<p style='text-align: center; margin-top: 50px;'>No tasks available.</p>";
                }
                ?>
            </div>
        </main>
    </section>
    <!-- Include SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php if (!empty($successMessage)): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '<?php echo $successMessage; ?>',
                confirmButtonText: 'OK'
            });
        <?php elseif (!empty($errorMessage)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?php echo $errorMessage; ?>',
                confirmButtonText: 'OK'
            });
        <?php endif; ?>
    </script>
    <!-- Scripts -->
    <script src="assets/js/script.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>