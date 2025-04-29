<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php'; // Database connection

$user_id = $_SESSION['user_id'];

// Fetch subjects
$subjectsQuery = "
    SELECT fs.ContentID, CONCAT(fs.Title, ' - ', fs.Captions) AS Subject
    FROM feedcontent fs
    INNER JOIN usercontent uc ON fs.ContentID = uc.ContentID
    WHERE uc.UserID = ? AND uc.Status = 1
";

$stmt = $conn->prepare($subjectsQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$subjectsResult = $stmt->get_result();
$subjects = [];
while ($row = $subjectsResult->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt->close();

// Query to fetch only Assigned tasks
$assignedQuery = "
    SELECT 
        ts.TaskID, ts.ApprovalStatus, ts.ContentID, ts.Type, ts.Title, 
        ts.DueDate, ts.DueTime, ts.taskContent, ts.TimeStamp, 
        tu.UserID, tu.Status AS TaskUserStatus, uc.Status AS UserContentStatus, 
        CONCAT(fc.Title, '-', fc.Captions) AS GS
    FROM task_user tu
    INNER JOIN tasks ts ON tu.TaskID = ts.TaskID
    INNER JOIN usercontent uc ON ts.ContentID = uc.ContentID
    INNER JOIN feedcontent fc ON fc.ContentID = ts.ContentID
    WHERE tu.UserID = ? 
        AND tu.Status = 'Assigned' 
        AND uc.Status = 'Active'
        AND ts.Type = 'Task'
";

$stmt = $conn->prepare($assignedQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$assignedResult = $stmt->get_result();

// Initialize arrays
$todayTasks = [];
$comingUpTasks = [];
$laterTasks = [];
$noDueTasks = [];

$today = strtotime('today');
$oneWeekFromToday = strtotime('+1 week');

while ($row = $assignedResult->fetch_assoc()) {
    $taskDueDateTime = strtotime($row['DueDate'] . ' ' . $row['DueTime']);

    if ($taskDueDateTime === false) {
        $noDueTasks[] = $row; // No due date
    } elseif ($taskDueDateTime < $today) {
        $missingTasks[] = $row; // Overdue tasks should be marked missing
    } elseif ($taskDueDateTime === $today) {
        $todayTasks[] = $row; // Task is due today
    } elseif ($taskDueDateTime <= $oneWeekFromToday) {
        $comingUpTasks[] = $row; // Task is due within a week
    } else {
        $laterTasks[] = $row; // Task is due later
    }
}
$stmt->close();

// Query to fetch only Missing tasks
$missingQuery = "
    SELECT 
        ts.TaskID, ts.ApprovalStatus, ts.ContentID, ts.Type, ts.Title, 
        ts.DueDate, ts.DueTime, ts.taskContent, ts.TimeStamp, 
        tu.UserID, tu.Status AS TaskUserStatus, uc.Status AS UserContentStatus, 
        CONCAT(fc.Title, '-', fc.Captions) AS GS
    FROM task_user tu
    INNER JOIN tasks ts ON tu.TaskID = ts.TaskID
    INNER JOIN usercontent uc ON ts.ContentID = uc.ContentID
    INNER JOIN feedcontent fc ON fc.ContentID = ts.ContentID
    WHERE tu.UserID = ? 
        AND tu.Status = 'Missing' 
        AND uc.Status = 'Active'
        AND ts.Type = 'Task'
";

$stmt = $conn->prepare($missingQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$missingResult = $stmt->get_result();

$missingTasks = [];
while ($row = $missingResult->fetch_assoc()) {
    $missingTasks[] = $row;
}
$stmt->close();

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" type="image/png" href="img/Logo/docmap-logo-1.png">
    <style>
        /* Optional styles for button active state */
        .button-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px; /* Adjust as needed */
            font-weight:bold;
        }

        .section-button {
            display: inline-block;
            margin: 0 10px; /* Adjust spacing */
            text-decoration: none; /* Remove default underline */
            color: #333; /* Text color */
            cursor: pointer;
            position: relative; /* Ensure position for pseudo-element */
            padding: 5px 10px; /* Adjust padding for button size */
        }

        .section-button.active::after {
            content: ''; /* Required for pseudo-element */
            position: absolute; /* Position relative to the button */
            left: 0;
            right: 0;
            bottom: -2px; /* Adjust as needed */
            height: 2.5px; /* Underline height */
            background-color: #9B2035; /* Underline color */
        }

        .task-container {
            background-color: #ffff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-left: 100px; /* Margin only on the left */
            margin-right: 100px; /* Margin only on the right */
            position: relative;
            height: 100px;
            margin-top: 20px;
        }

        .category-title {
            margin-top: 20px;
            font-size: 1.2em;
            margin-left: 100px;
            margin-right: 100px;
            margin-bottom: 20px;
        }

        .task-title {
            color: black;
            transition: color 0.3s ease; /* Smooth transition */
        }

        .task-title:hover {
            color: #9B2035; /* Hover color */
        }

        .task-icon {
            position: absolute;
            top: 50%;
            right: -25px; /* Adjust distance from the right */
            transform: translateY(-50%);
            width: 60px;
            height: 60px;
            background-color: #9B2035; /* Adjust color as needed */
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 50px;
        }

        .task-icon ion-icon {
            color: #fff;
            font-size: 25px;
        }
        .due-label{
            color:grey;
        }
        .gs{
            font-size: 14px;
            color:grey;
        }
        .count-circle {
            display: inline-block;
            width: 24px; /* Adjust size as needed */
            height: 24px; /* Adjust size as needed */
            border-radius: 50%; /* Make it circular */
            background-color: #9B2035; /* Circle color */
            color: white; /* Font color */
            text-align: center; /* Center text */
            line-height: 24px; /* Center text vertically */
            margin-left: 5px; /* Spacing from the button text */
            font-size: 14px; /* Font size */
        }
        .info-message {
            display: flex;
            align-items: center;
            margin-top: 5px; /* Space between title and message */
            margin-left: 100px; /* Move message to the right */  
        }

        .info-message p {
            font-size: 16px; /* Font size for the message */
            color: #555; /* Color for the message text */
            margin: 0; /* Remove default margin */
        }
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
        }



        .dropdown {
            padding: 10px;
            font-size: 14px;
            border-radius: 5px;
            border: 1px solid #ccc;
            background-color: white;
            cursor: pointer;
            max-width: 100%;
            font-weight:bold;
            
        }




    </style>
    <title>To-Do's</title>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <?php include 'navbar.php'; ?>
    </section>
    <!-- SIDEBAR -->

    <!-- NAVBAR -->
    <section id="content">
        <!-- NAVBAR -->
        <?php include 'topbar.php'; ?>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
          <div class="header-container">
                <h1 class="title">To-Do's</h1>
                <select id="taskFilter" class="dropdown">
                    <option value="">All Grade Level</option> <!-- Default Option -->
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= htmlspecialchars($subject['ContentID']) ?>">
                            <?= htmlspecialchars($subject['Subject']) ?> <!-- Now shows Title - Captions -->
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>



            <!-- Clickable buttons within the main content area -->
            <div class="button-container">
                <a href="#" class="section-button <?= empty($_GET['section']) || $_GET['section'] === 'assigned' ? 'active' : ''; ?>" data-section="assigned">
                    Assigned 
                    <span class="count-circle"><?= $assignedCount; ?></span> <!-- Ensure this exists -->
                </a>
                <a href="#" class="section-button <?= empty($_GET['section']) || $_GET['section'] === 'missing' ? 'active' : ''; ?>" data-section="missing">
                    Missing 
                    <span class="count-circle"><?= $missingCount; ?></span> <!-- Ensure this exists -->
                </a>
            </div>


            <!-- Assigned section content -->
            <div id="assigned-content" style="display: block;">
                <h2 class="category-title">Today</h2>
                <?php foreach ($todayTasks as $task) : ?>
                    <div class="task-container" data-content-id="<?= $task['ContentID']; ?>">
                        <a href='taskdetails.php?task_id=<?= $task['TaskID']; ?>&content_id=<?= $task['ContentID']; ?>&user_id=<?= $user_id; ?>' class='taskLink'>
                            <h3 class='task-title'><?= $task['Title']; ?></h3>
                            <p class="due-label">Due: <?= $task['DueDate']; ?> @<?= $task['DueTime']; ?> </p>
                            <p class="gs"><?= $task['GS']; ?></p>
                            <div class='task-icon'>
                                <ion-icon name='document-outline'></ion-icon>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>

                <h2 class="category-title">Coming Up</h2>
                <?php foreach ($comingUpTasks as $task) : ?>
                    <div class="task-container" data-content-id="<?= $task['ContentID']; ?>">
                        <a href='taskdetails.php?task_id=<?= $task['TaskID']; ?>&content_id=<?= $task['ContentID']; ?>&user_id=<?= $user_id; ?>' class='taskLink'>
                            <h3 class='task-title'><?= $task['Title']; ?></h3>
                            <p class="due-label">Due: <?= $task['DueDate']; ?> @<?= $task['DueTime']; ?> </p>
                            <p class="gs"><?= $task['GS']; ?></p>
                            <div class='task-icon'>
                                <ion-icon name='document-outline'></ion-icon>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>

                <h2 class="category-title">Later</h2>
                <?php foreach ($laterTasks as $task) : ?>
                    <div class="task-container" data-content-id="<?= $task['ContentID']; ?>">
                        <a href='taskdetails.php?task_id=<?= $task['TaskID']; ?>&content_id=<?= $task['ContentID']; ?>&user_id=<?= $user_id; ?>' class='taskLink'>
                            <h3 class='task-title'><?= $task['Title']; ?></h3>
                            <p class="due-label">Due: <?= $task['DueDate']; ?> @<?= $task['DueTime']; ?> </p>
                            <p class="gs"><?= $task['GS']; ?></p>
                            <div class='task-icon'>
                                <ion-icon name='document-outline'></ion-icon>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Missing section content -->
            <div id="missing-content" style="display: none;">
                <h2 class="category-title">Missing Tasks</h2>
                <div class="info-message" style="display: flex; align-items: center; margin-top: 10px;">
                    <ion-icon name='information-circle-outline' style="font-size: 24px; margin-right: 10px; color:#9B2035; "></ion-icon>
                    <p style="font-size: 14px; color: #555; margin: 0;">
                        Missing tasks do not accept outputs anymore. For late submissions, inquire with the admin for further instructions.
                    </p>
                </div>
                <?php foreach ($missingTasks as $task) : ?>
                    <div class="task-container" data-content-id="<?= $task['ContentID']; ?>">
                        <a href='taskdetails.php?task_id=<?= $task['TaskID']; ?>&content_id=<?= $task['ContentID']; ?>&user_id=<?= $user_id; ?>' class='taskLink'>
                            <h3 class='task-title'><?= $task['Title']; ?></h3>
                            <p class="due-label">Due: <?= $task['DueDate']; ?> @<?= $task['DueTime']; ?></p>
                            <p class="gs"><?= $task['GS']; ?></p>
                            <div class='task-icon'>
                                <ion-icon name='document-outline'></ion-icon>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>


        </main>
        <!-- MAIN -->
    </section>
    `<script>
        document.addEventListener('DOMContentLoaded', function() {
            const taskFilter = document.getElementById('taskFilter');
            const taskContainers = document.querySelectorAll('.task-container');
            const assignedButton = document.querySelector('.section-button[data-section="assigned"]');
            const missingButton = document.querySelector('.section-button[data-section="missing"]');

            // Function to update the counts
            function updateCounts() {
                const selectedContentID = taskFilter.value;

                let assignedCount = 0;
                let missingCount = 0;

                taskContainers.forEach(container => {
                    const contentID = container.getAttribute('data-content-id');
                    const isVisible = selectedContentID === "" || contentID === selectedContentID;

                    if (isVisible) {
                        if (container.closest('#assigned-content')) {
                            assignedCount++;
                        } else if (container.closest('#missing-content')) {
                            missingCount++;
                        }
                    }
                });

                // Update the counts in the buttons
                const assignedCountElement = assignedButton.querySelector('.count-circle');
                const missingCountElement = missingButton.querySelector('.count-circle');

                if (assignedCountElement) {
                    assignedCountElement.textContent = assignedCount;
                }
                if (missingCountElement) {
                    missingCountElement.textContent = missingCount;
                }
            }

            // Event listener for the grade level filter
            taskFilter.addEventListener('change', function() {
                const selectedContentID = this.value;

                taskContainers.forEach(container => {
                    const contentID = container.getAttribute('data-content-id');

                    if (selectedContentID === "" || contentID === selectedContentID) {
                        container.style.display = 'block'; // Show the task
                    } else {
                        container.style.display = 'none'; // Hide the task
                    }
                });

                // Update the counts after filtering
                updateCounts();
            });

            // Handle section buttons (Assigned and Missing)
            document.querySelectorAll('.section-button').forEach(button => {
                button.addEventListener('click', function() {
                    const section = this.getAttribute('data-section');

                    document.getElementById('assigned-content').style.display = section === 'assigned' ? 'block' : 'none';
                    document.getElementById('missing-content').style.display = section === 'missing' ? 'block' : 'none';

                    // Remove active class from all buttons
                    document.querySelectorAll('.section-button').forEach(btn => btn.classList.remove('active'));

                    // Add active class to the clicked button
                    this.classList.add('active');
                });
            });

            // Initialize counts on page load
            updateCounts();
        });
    </script>`
    
    <script>
        document.querySelectorAll('.section-button').forEach(button => {
            button.addEventListener('click', function() {
                const section = this.getAttribute('data-section');
                
                document.getElementById('assigned-content').style.display = section === 'assigned' ? 'block' : 'none';
                document.getElementById('missing-content').style.display = section === 'missing' ? 'block' : 'none';
                
                // Remove active class from all buttons
                document.querySelectorAll('.section-button').forEach(btn => btn.classList.remove('active'));
                
                // Add active class to the clicked button
                this.classList.add('active');
            });
        });
    </script>
    
       <script src="https://unpkg.com/ionicons@5.5.2/dist/ionicons.js"></script>
       <script src="assets/js/script.js"></script>  
</body>
</html>
