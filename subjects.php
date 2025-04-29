<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

$user_id = $_SESSION['user_id'];

// Fetch contents assigned to the user
$sql = "SELECT fs.ContentID, fs.Title, fs.Captions, IFNULL(fs.ContentColor, '#9B2035') as ContentColor, fs.dept_ID, d.dept_name as deptname
        FROM feedcontent fs
        INNER JOIN usercontent uc ON fs.ContentID = uc.ContentID
        INNER JOIN department d ON fs.dept_ID = d.dept_ID
        WHERE uc.UserID = $user_id AND uc.Status=1";

$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$contents = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Fetch tasks per ContentID with "Assigned" status only, sorted by nearest due date & time
$task_query = "SELECT DISTINCT 
                    ts.TaskID, 
                    ts.Title AS TaskTitle, 
                    ts.ContentID, 
                    ts.DueDate, 
                    ts.DueTime
               FROM task_user tu
               INNER JOIN tasks ts ON tu.TaskID = ts.TaskID
               INNER JOIN usercontent uc ON ts.ContentID = uc.ContentID
               WHERE tu.UserID = $user_id 
               AND tu.Status = 'Assigned'  
               AND uc.Status = 'Active'
               AND ts.Type='Task'
               ORDER BY ts.DueDate ASC, ts.DueTime ASC";  // Sorting by nearest due date/time
 // Sorting by nearest due date/time

$task_result = mysqli_query($conn, $task_query);
if (!$task_result) {
    die("Query failed: " . mysqli_error($conn));
}

// Store tasks by ContentID
$tasks_by_content = [];
while ($task_row = mysqli_fetch_assoc($task_result)) {
    $contentID = $task_row['ContentID'];
    $taskID = $task_row['TaskID'];
    $taskTitle = $task_row['TaskTitle'];
    $dueDate = date("M d, Y", strtotime($task_row['DueDate'])); // Format due date
    $dueTime = date("h:i A", strtotime($task_row['DueTime'])); // Format due time

    $tasks_by_content[$contentID][] = [
        'taskID' => $taskID,
        'title' => $taskTitle,
        'dueDate' => $dueDate,
        'dueTime' => $dueTime
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Levels</title>
    <link rel="icon" type="image/png" href="img/Logo/docmap-logo-1.png">
    <!-- ======= Styles ====== -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
   <style>
        .container {
            width:100%;
            padding-top:15px;
        }

        
        .card h2 {
            font-size: 20px;
            margin-bottom: -1px;
            color: #fff;
        }

        .card p {
            font-size: 14px;
            color: #fff;
            
        }

        .search-container {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 20px;
            margin-right: 20px;
        }

        .search-bar {
            border-radius: 20px;
            padding: 10px 20px;
            border: 1px solid #ccc;
            width: 250px;
        }

        .fab {
            right: 20px;
            background-color: #9B2035;
            color: #fff;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .fab:hover {
            background-color: #7A192A; /* Darker shade */
            transform: scale(1.1); /* Slightly enlarges on hover */
        }

        .fab i {
            font-size: 30px;
        }


        .plus-icon {
            font-size: 30px;
            color: black;
            cursor: pointer;
            margin-left: 20px;
        }

        .search-container {
            position: relative;
        }

        .input {
            width: 150px;
            padding: 10px 0px 10px 40px;
            border-radius: 9999px;
            border: solid 1px #333;
            transition: all .2s ease-in-out;
            outline: none;
            opacity: 0.8;
        }

        .search-container svg {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translate(0, -50%);
        }

        .input:focus {
            opacity: 1;
            width: 250px;
        }

        .modal-backdrop {
            z-index: 1040 !important;
        }

        .modal {
            z-index: 1050 !important;
        }
        .taskLink:hover {
    text-decoration: underline !important;
}
    </style>
</head>

<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <?php include 'navbar.php'; ?>
    </section>
    <!-- SIDEBAR -->

    <section id="content">
        <!-- NAVBAR -->
        <?php include 'topbar.php'; ?>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="title" style="margin-bottom: 20px;">Grade Levels</h2>
                <div class="fab" data-toggle="modal" data-target="#exampleModal">
                    <i class='bx bx-plus'></i>
                </div>
            </div>
            <div class="container">
                <div class="row">
                    <?php
                    if (!empty($contents)) {
                        foreach ($contents as $row) {
                            $contentColor = htmlspecialchars($row['ContentColor'], ENT_QUOTES, 'UTF-8');
                            $contentId = htmlspecialchars($row['ContentID'], ENT_QUOTES, 'UTF-8');
                            $title = htmlspecialchars($row['Title'], ENT_QUOTES, 'UTF-8');
                            $captions = htmlspecialchars($row['Captions'], ENT_QUOTES, 'UTF-8');
                            $deptname = htmlspecialchars($row['deptname'], ENT_QUOTES, 'UTF-8');

                            echo "<div class='col-lg-4 col-md-6 col-12 mb-4'>";
                            echo "<div class='card' style='background-color: $contentColor; cursor: pointer; border-radius: 10px; width:100%; overflow: hidden; border: none; box-shadow: none;' onclick='redirectToPage(\"tasks.php?content_id=$contentId\")'>";
                            echo "<div class='card-body p-4 text-white'>";
                            echo "<h5 class='card-title mb-3'><a style='color:#fff; text-decoration: none; font-size: 25px;' href='tasks.php?content_id=$contentId'>$title - $captions</a></h5>";
                            echo "<p class='card-text' style='font-size: 13px;margin-top:-10px;'><i class='bx bxs-building' style='margin-right:5px;'></i>$deptname</p>";
                            echo "</div>";

                            if (isset($tasks_by_content[$contentId])) {
                                echo "<div class='card-footer' style='background-color: #fff; border-radius: 0 0 10px 10px; border: none; padding: 10px;'>";
                                echo "<ul style='padding-left: 20px; margin: 0; list-style-type: disc;'>"; // Ensures bullets appear

                                foreach ($tasks_by_content[$contentId] as $task) {
                                    $taskID = $task['taskID'];
                                    echo "<li style='font-size: 14px; font-weight: bold; padding: 5px 0;'>";
                                    echo "<a href='taskdetails.php?task_id=$taskID&content_id=$contentId&user_id=$user_id' class='taskLink' style='color: #007BFF; text-decoration: none;'>" . htmlspecialchars($task['title']) . "</a><br>";
                                    echo "<span style='font-size: 12px; color: #666;'>Due: {$task['dueDate']} at {$task['dueTime']}</span>";
                                    echo "</li>";
                                }

                                echo "</ul>"; // Close unordered list
                                echo "</div>";
                            }

                            echo "</div>";
                            echo "</div>";
                        }
                    } else {
                        echo "<div class='col-12 text-center'><p>No content available.</p></div>";
                    }
                    ?>
                </div>
            </div>



            <!-- JavaScript to Handle Clicks -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll('.content-card').forEach(card => {
            card.addEventListener('click', function (event) {
                // Prevent navigation if clicking a task link
                if (!event.target.closest('.taskLink')) {
                    window.location.href = this.getAttribute('data-url');
                }
            });
        });
    });
</script>
        </main>
        <!-- MAIN -->
    </section>

    <!-- Floating Action Button -->
   

    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Add New Content</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Add your form or content here -->
                    <form>
                        <div class="form-group">
                            <label for="contentCode">Content Code</label>
                            <input type="text" class="form-control" id="contentCode" placeholder="Enter Code">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="saveButton" disabled>Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- =========== Scripts =========  -->
    <script src="assets/js/script.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script>
    function console.logContentID(contentID) {
        console.log("ContentID passed to tasks.php:", contentID);
    }
</script>

    <script>
$(document).ready(function() {
    // Check if the content code exists
    $('#contentCode').on('input', function() {
        var code = $(this).val();

        // Only perform the AJAX request if code is not empty
        if (code) {
            $.ajax({
                url: 'check_code.php',
                type: 'POST',
                data: { code: code },
                success: function(response) {
                    if (response == 'exists') {
                        $('#saveButton').prop('disabled', false); // Enable button if code exists
                    } else {
                        $('#saveButton').prop('disabled', true); // Disable button if code doesn't exist
                    }
                },
                error: function() {
                    console.log('Error occurred while checking the code.');
                }
            });
        } else {
            $('#saveButton').prop('disabled', true); // Disable button if code input is empty
        }
    });

    // Handle content save on button click
    $('#saveButton').on('click', function() {
        var code = $('#contentCode').val();

        if (code) {  // Ensure there's a code to submit
            $.ajax({
                url: 'insert_usercontent.php',
                type: 'POST',
                data: { code: code },
                success: function(response) {
                    if (response == 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Content Added',
                            text: 'Content added successfully.',
                        }).then(() => {
                            $('#exampleModal').modal('hide');
                            location.reload(); // Reload the page to reflect changes
                        });
                    } else if (response == 'exists') {
                        Swal.fire({
                            icon: 'info',
                            title: 'Already Active',
                            text: 'You are already part of this grade.',
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error adding content.',
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while saving content.',
                    });
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Content code is missing.',
            });
        }
    });

    // Search functionality for cards
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const cards = document.querySelectorAll('.card');
        
        cards.forEach(card => {
            const title = card.querySelector('h2').innerText.toLowerCase();
            if (title.includes(searchTerm)) {
                card.style.display = 'block';  // Show card if it matches search
            } else {
                card.style.display = 'none';  // Hide card if it doesn't match search
            }
        });
    });
});

    </script>
</body>

</html>
