<nav>
    <i class='bx bx-menu toggle-sidebar' id ="menu-toggle"></i>
    <form action="#">
        <!-- Your form content here if needed -->
    </form>



    
    <a href="#" class="nav-link" id="notification-link">
        <?php
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        include 'connection.php';

        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        ?>
        <i class='bx bxs-bell icon' id="notification-icon" title ="Notifications"></i>

        <!-- Modal Structure for Notifications -->
        <div id="notifications-modal" class="modal">
            <div class="modal-content">
                 <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="font-weight: bold; color: #9B2035;">Notifications</h2>
                <?php
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }

                include 'connection.php';

                if (isset($_SESSION['user_id'])) {
                    $user_id = $_SESSION['user_id'];

                    // Count unread notifications
                    $count_sql = "SELECT COUNT(*) FROM Notif_User WHERE UserID = ? AND Status = 1";
                    $count_stmt = $conn->prepare($count_sql);
                    $count_stmt->bind_param("s", $user_id);
                    $count_stmt->execute();
                    $count_stmt->bind_result($unread_count);
                    $count_stmt->fetch();
                    $count_stmt->close();
                ?>
                    <!-- Read All Button -->
                    <button class="read-all-btn" id="readAllBtn" onclick="markAllAsRead()" <?php echo ($unread_count == 0) ? 'disabled' : ''; ?>>Read All</button>

                <?php } ?>

            </div>
                <!-- Close Button -->
                <span class="close">&times;</span> 
               
                <ul class="notifications-list">
                    <?php
                    // Query to get the notifications where the user is the recipient (NotifUserID = session user_id)
                    $sql = "
                    SELECT 
    ts.NotifID, 
    ts.TaskID, 
    ts.ContentID, 
    ts.UserID, 
    ts.Title, 
    ts.Content, 
    nu.Status, 
    ts.TimeStamp, 
    ua.fname, 
    ua.lname
FROM notifications ts
INNER JOIN notif_user nu ON ts.NotifID = nu.NotifID
INNER JOIN useracc ua ON ts.UserID = ua.UserID
WHERE nu.UserID = ?
ORDER BY 
    -- Ordering notifications by their status first (new first, then old)
    CASE 
        WHEN nu.Status = 1 THEN 1 -- New notifications (Status = 1)
        ELSE 2 -- Old notifications (Status = 0)
    END ASC, 
    -- Then by TimeStamp in descending order (latest first)
    ts.TimeStamp DESC;
";

                    // Prepare and execute the SQL statement
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $user_id);
                    $stmt->execute();
                    $stmt->bind_result($id, $taskID, $contentID, $notifUserID, $title, $content, $status, $timestamp, $fname, $lname);

                    // Fetch the results and display them
                    while ($stmt->fetch()) {
                        // Apply styles based on the notification status
                        $title_color = $status == 1 ? '#9B2035' : 'gray';
                        $notif_status_class = $status == 1 ? 'new-notification' : 'old-notification';

                        echo "<li class='notification-item {$notif_status_class}' onclick='handleNotificationClick({$id}, {$taskID}, {$contentID})'>";
                        echo "<div class='notif-header' style='color: {$title_color};'><strong>{$fname} {$lname}</strong></div>";
                        echo "<div class='notif-title' style='color: {$title_color}; font-weight:bold;'>{$title}</div>";
                        echo "<div class='notif-content' style='color: ; font-weight: normal;'>{$content}</div>";
                        echo "<div class='notif-timestamp' style='color:; font-weight: normal;'>{$timestamp}</div>";
                        echo "</li>";
                    }

                    // Close the prepared statement
                    $stmt->close();
                    ?>
                </ul>
                <!-- Read All Button -->
                    
            </div>
        </div>
        <?php } ?>
    </a>
    <a href="todo.php" class="nav-link">
        <i class='bx bxs-notepad icon'id="todo-icon" title="To-do's"></i>
        <!-- Notepad icon content -->
    </a>
    <span class="divider" ></span>
    <div class="profile">
        <?php
        // Ensure session is started at the beginning of the script if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Include database connection
        include 'connection.php';

        // Check if user is logged in
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];

            // Query to fetch profile image filename and user name
            $sql = "SELECT Profile, CONCAT(Fname, ' ', Lname) AS fullname FROM useracc WHERE UserID = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("s", $userId);
                $stmt->execute();
                $stmt->bind_result($profileImage, $fullName);
                $stmt->fetch();
                $stmt->close(); // Close statement after fetching results

                // Build path to profile image
                $profileImagePath = "img/UserProfile/" . $profileImage;

                // Check if the profile image exists
                if (file_exists($profileImagePath)) {
                    echo "<span class='user-name'>{$fullName}</span>";
                    echo "<img src='{$profileImagePath}' alt='Profile Image' class='profile-image'>";
                } else {
                    // Default image if profile image not found
                    echo "<span class='user-name'>{$fullName}</span>";
                    echo "<img src='default_profile_image.jpg' alt='Profile Image' class='profile-image'>";
                }
            } else {
                echo "Error preparing statement: " . $conn->error;
            }
        } else {
            echo "User not logged in.";
        }

        // Close database connection at the end of the script
        $conn->close();
        ?>
        <ul class="profile-link">
            <li><a href="profile.php"><i class='bx bxs-user-circle icon'></i> Profile</a></li>
            <li><a href="logout.php" id="logout"><i class='bx bxs-log-out-circle'></i> Logout</a></li>
        </ul>
    </div>
</nav>

<style>
    #menu-toggle {
    transition: transform 0.3s ease, color 0.3s ease;
    color: #333; /* Default color */
    }

    #menu-toggle:hover {
        transform: scale(1.3) rotate(10deg);
        color: #9b2035; /* Change to desired hover color */
    }
    #notification-icon {
    transition: transform 0.3s ease, color 0.3s ease;
    color: #333; /* Default color */
    }

    #notification-icon:hover {
        transform: scale(1.3) rotate(10deg);
        color: #9b2035; /* Change to desired hover color */
    }
    #todo-icon {
    transition: transform 0.3s ease, color 0.3s ease;
    color: #333; /* Default color */
    }

    #todo-icon:hover {
        transform: scale(1.3) rotate(10deg);
        color: #9b2035; /* Change to desired hover color */
    }
    .profile-image {
    transition: transform 0.3s ease, color 0.3s ease;
    color: #333; /* Default color */
    }

    .profile-image:hover {
        transform: scale(1.1) rotate(10deg);
        color: #9b2035; /* Change to desired hover color */
    }

    /* ... Your existing styles ... */
          .read-all-btn {
        background-color: #9B2035;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .read-all-btn:hover {
        background-color: #7A1729; /* Darker shade on hover */
    }
    .profile {
        display: flex;
        align-items: center;
    }

    .user-name {
        margin-right: 10px; /* Adjust the space between the name and image */
        font-weight: bold;
        color: #9B2035; /* Underline color */
    }

    .profile-image {
        width: 40px; /* Adjust the width as needed */
        height: 40px; /* Adjust the height as needed */
        border-radius: 50%; /* Make the image round */
    }

    /* Modal styling */
    .notification-item {
        padding: 15px;
        margin-bottom: 10px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        background-color: white;
        position: relative;
        transition: background-color 0.3s, box-shadow 0.3s;
        border: 1px solid grey;
        max-height: 200px; /* Set maximum height for the item */
        overflow: hidden; /* Hide overflow content */
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    /* Specific styles for new and old notifications */
    /* Specific styles for new and old notifications */
.new-notification {
    background-color: #ffffff;
    border: 1px solid #9B2035;
}

.old-notification {
    background-color: #f0f0f0;
    border: 1px solid gray;
    color: gray;
}

/* Update notification content and timestamp color for old notifications */
.old-notification .notif-content,
.old-notification .notif-timestamp {
    color: gray;  /* Change text color to gray for old notifications */
}
.new-notification .notif-content,
.new-notification .notif-timestamp {
    color: black;  /* Change text color to gray for old notifications */
}


/* Hover effects */
.notification-item:hover {
    box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
}

    .notifications-list {
        max-height: 500px;
        overflow-y: auto;
    }

    /* Modal styling */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        background-color: #fefefe;
        padding: 20px;
        border-radius: 10px;
        width: 700px;
        margin: 0 auto;
        transform: translateY(-100%);
        animation: slideDown 0.5s ease forwards;
    }

    @keyframes slideDown {
        from {
            transform: translateY(-100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .close:hover,
    .close:focus {
        color: #000;
        text-decoration: none;
        cursor: pointer;
    }

    /* Styles for the new card */
.notification-card {
    background: linear-gradient(to left, #FFD700, #fff); /* Yellow to white gradient */
    border: 2px solid #DAA520; /* Slightly darker yellow border */
    border-radius: 10px;
    padding: 5px;
    display: flex;
    align-items: center;
  
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    height:50px;
}

.coin-container {
    flex-shrink: 0; /* Prevent the coin from shrinking */
    margin-right: 5px;
}

.coin-gif {
    width: 50px;
    height: 50px;
    animation: spin 1s infinite linear; /* Spinning animation */
}

.points {
    font-weight: bold;
    font-size: 16px;
    color: #9B2035;
}

/* Spinning animation for the coin (sideways) */
@keyframes spin {
    from {
        transform: rotateY(0deg);
    }
    to {
        transform: rotateY(360deg);
    }
}
    

/* Notification modal styling */
.modal-content {
    background-color: #fefefe;
    padding: 20px;
    border-radius: 10px;
    width: 700px;
    margin: 0 auto;
    transform: translateY(-100%);
    animation: slideDown 0.5s ease forwards;
}

/* Rest of your existing styles */
.read-all-btn:disabled {
    background-color: #ccc;
    cursor: not-allowed;
}

</style>




<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function markAllAsRead() {
    Swal.fire({
        title: "Mark all as read?",
        text: "This will mark all unread notifications as read.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#9B2035",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, mark all!",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('mark_all_as_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI: Change styles of unread notifications
                    document.querySelectorAll('.new-notification').forEach(item => {
                        item.classList.remove('new-notification');
                        item.classList.add('old-notification');
                        item.querySelector('.notif-title').style.color = 'gray';
                    });

                    // Disable the button after marking as read
                    document.getElementById('readAllBtn').disabled = true;

                    // Show success alert
                    Swal.fire({
                        title: "Success!",
                        text: "All notifications have been marked as read.",
                        icon: "success",
                        confirmButtonColor: "#9B2035"
                    });
                } else {
                    Swal.fire({
                        title: "Error!",
                        text: "Failed to mark notifications as read.",
                        icon: "error",
                        confirmButtonColor: "#9B2035"
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: "Error!",
                    text: "Something went wrong.",
                    icon: "error",
                    confirmButtonColor: "#9B2035"
                });
            });
        }
    });
}
</script>




<script>
    // Get modal element
    var modal = document.getElementById("notifications-modal");

    // Get the notification icon to open the modal
    var notificationIcon = document.getElementById("notification-icon");

    // Get the close button within the modal
    var closeBtn = document.querySelector(".close");

    // Get the notification link
    var notificationLink = document.getElementById("notification-link");

    // When the user clicks the notification icon or link, open the modal and apply background blur
    notificationIcon.onclick = notificationLink.onclick = function() {
        modal.style.display = "block";
        document.body.classList.add("modal-open"); // Add blur effect
    }

    // When the user clicks the close button, close the modal and remove background blur
    closeBtn.onclick = function() {
        modal.style.display = "none";
        document.body.classList.remove("modal-open"); // Remove blur effect
    }

    // Close the modal when clicking outside of it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
            document.body.classList.remove("modal-open"); // Remove blur effect
        }
    }

    // Add a function to handle notification clicks
    function handleNotificationClick(notifId, taskId, contentId) {
        // Update notification status via AJAX
        fetch('update_notification_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ notifId: notifId })
        })
        .then(response => {
            if (response.ok) {
                // Redirect to task details page
                window.location.href = `taskdetails.php?task_id=${encodeURIComponent(taskId)}&content_id=${encodeURIComponent(contentId)}`;
            } else {
                console.error('Failed to update notification status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    // Logout functionality with SweetAlert
    document.getElementById('logout').addEventListener('click', function(e) {
        e.preventDefault();

        Swal.fire({
            title: 'Are you sure?',
            text: 'You will be logged out of your account!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, log me out!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = this.getAttribute('href');
            }
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>