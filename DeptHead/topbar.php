<nav>
    <i class='bx bx-menu toggle-sidebar'></i>
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

       // Count unread notifications
       $count_sql = "SELECT COUNT(*) 
       FROM notif_user nu 
       INNER JOIN notifications ts 
       ON nu.NotifID = ts.NotifID 
       WHERE nu.UserID = ? 
       AND nu.Status = 1";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("s", $user_id);
$count_stmt->execute();
$count_stmt->bind_result($unread_count);
$count_stmt->fetch();
$count_stmt->close();
?>
<i class='bx bxs-bell notification-icon' id="notification-icon">
<?php if ($unread_count > 0): ?>
<span class="notification-badge"><?php echo $unread_count; ?></span>
<?php endif; ?>
</i>
        <!-- Modal Structure for Notifications -->
        <div class="notification-dropdown" id="notifications-dropdown">
            <div class="notification-dropdown-header">
                <span>Notifications</span>
                <span class="mark-all-read">Mark all as read</span>
            </div>
            <?php
                // Query to fetch notifications
                $sql = "SELECT DISTINCT ts.NotifID, ts.TaskID, ts.ContentID, ts.UserID, ts.Title, ts.Content, nu.Status, ts.TimeStamp, ua.fname, ua.lname
                        FROM notifications ts
                        INNER JOIN notif_user nu ON ts.NotifID = nu.NotifID
                        INNER JOIN useracc ua ON ts.UserID = ua.UserID
                        WHERE nu.UserID = ?
                        ORDER BY 
                        CASE 
                            WHEN nu.Status = 1 THEN 1 
                            ELSE 2 
                        END, 
                        ts.TimeStamp DESC;";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $user_id);
                $stmt->execute();
                $stmt->bind_result($id, $taskID, $contentID, $notifUserID, $title, $content, $status, $timestamp, $fname, $lname);

                echo "<ul class='notifications-list'>";

                $has_notifications = false;
                while ($stmt->fetch()) {
                    $has_notifications = true;
                    $notif_status_class = $status == 1 ? 'new-notification' : '';
                    $fullname = $fname . ' ' . $lname;
                    echo "<li class='notification-item {$notif_status_class}' 
                          data-notif-id='{$id}'
                          data-task-id='{$taskID}'
                          data-content-id='{$contentID}'>";
                    echo "<div class='notification-header'>";
                    echo "<span class='notification-sender'>{$fullname}</span>";
                    echo "<span class='notification-timestamp'>{$timestamp}</span>";
                    echo "</div>";
                    echo "<div class='notification-title'>{$title}</div>";
                    echo "<div class='notification-content'>{$content}</div>";
                    echo "</li>";
                }
                
                if (!$has_notifications) {
                    echo '<div class="notification-empty">No notifications found</div>';
                }
                
                echo "</ul>";
                    // Close the prepared statement
                    $stmt->close();
                    ?>
                </ul>
            </div>
        </div>
    <?php } ?>
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

                $profileImagePath = !empty($profileImage) 
                    ? "https://raw.githubusercontent.com/docmap2024/DocMaP/main/img/UserProfile/" . $profileImage
                    : "default_profile_image.jpg";
                
                echo "<span class='user-name' id='profile-username'>{$fullName}</span>";
                echo "<img src='{$profileImagePath}' alt='Profile Image' class='profile-image' id='profile-picture'>";
            
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
.profile {
        display: flex;
        align-items: center;
    }
    .user-name {
        margin-right: 10px;
        font-weight: bold;
        color: #9B2035;
    }
    .profile-image {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        transition: all 0.7s ease;
    }

    /* Loading state */
    .profile-image.updating {
        opacity: 0.7;
        filter: grayscale(30%);   /* Optional visual effect */
    }
    
    /* Notification System - Consistent Styles */
    .notification-container {
        position: relative;
        display: inline-block;
        margin-right: -10px;
        z-index: 1001; /* Ensure it stays above other elements */
    }

    .notification-icon {
        position: relative;
        cursor: pointer;
        font-size: 1.5rem;
        color: #333;
        padding: 0.5rem;
        transition: all 0.3s ease;
    }

    .notification-icon:hover {
        color: #9B2035;
    }

    .notification-icon .notification-badge {
        position: absolute;
        top: 0;
        right: 0;
        background-color: #9B2035;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    .notification-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background-color: white;
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        width: 430px;
        max-width: 90vw;
        z-index: 1002;
        border-radius: 10px;
        overflow: visible;
        padding: 0;
        border: 1px solid #e0e0e0;
    }

    .notification-dropdown-header {
        font-weight: bold;
        color: #9B2035;
        padding: 15px;
        margin: 0;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #f9f9f9;
    }

    .notification-dropdown-header .mark-all-read {
        font-size: 12px;
        color: #9B2035;
        cursor: pointer;
        text-decoration: underline;
    }

    .notifications-list {
        list-style: none;
        padding: 0;
        margin: 0;
        max-height: 500px;
        overflow-y: auto;
    }

    .notification-item {
        padding: 15px;
        margin: 0;
        border-bottom: 1px solid #f0f0f0;
        background-color: #e0e0e0;
        position: relative;
        transition: all 0.3s ease;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e0e0e0;
        max-height: 200px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        margin: 10px;
        opacity: 0.8;
    }

    .notification-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .notification-item:hover {
        background-color: #f9f9f9;
        box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
    }

    /* Specific styles for new and old notifications */
    .notification-item.new-notification {
        border-left: 3px solid #9B2035;
        background-color: #ffffff;
        border: 1px solid #9B2035;
        opacity: 1;
    }

    .notification-item.old-notification {
        background-color: #e0e0e0;
        border: 1px solid #e0e0e0;
        color: gray;
        opacity: 0.8;
    }

    /* Update notification content and timestamp color for old notifications */
    .notification-item.old-notification .notification-content,
    .notification-item.old-notification .notification-timestamp {
        color: gray;
    }

    .notification-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
    }

    .notification-sender {
        font-weight: bold;
        color: #333;
    }

    .notification-title {
        font-weight: bold;
        margin: 5px 0;
        color: #333;
        font-size: 0.95rem;
    }

    .notification-content {
        margin: 5px 0;
        color: #555;
        font-size: 0.85rem;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
    }

    .notification-timestamp {
        font-size: 0.75rem;
        color: #999;
        align-self: flex-end;
    }

    .notification-empty {
        text-align: center;
        padding: 20px;
        color: #777;
        font-style: italic;
    }

    /* Animation for new notifications */
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    .new-notification {
        animation: pulse 0.5s ease;
    }

    body, html {
        overflow-x: visible;
    }

    nav {
        position: relative;
        z-index: 1000; /* Lower than notification dropdown */
    }
</style>
<script>
    // Notification dropdown toggle
    document.getElementById('notification-icon').addEventListener('click', function(e) {
        e.stopPropagation();
        const dropdown = document.getElementById('notifications-dropdown');
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
        const dropdown = document.getElementById('notifications-dropdown');
        if (dropdown.style.display === 'block') {
            dropdown.style.display = 'none';
        }
    });

    // Prevent dropdown from closing when clicking inside
    document.getElementById('notifications-dropdown').addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Mark all as read functionality
    document.querySelector('.mark-all-read').addEventListener('click', function() {
        // AJAX call to mark all notifications as read
        fetch('mark_notifications_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?> })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove new notification styles and update badge
                document.querySelectorAll('.new-notification').forEach(item => {
                    item.classList.remove('new-notification');
                    item.classList.add('old-notification');
                });
                const badge = document.querySelector('.notification-badge');
                if (badge) badge.remove();
            }
        });
    });

    function handleNotificationClick(notifId, taskId, contentId) {
        console.log("Notification clicked:", {notifId, taskId, contentId});
        
        // Update notification status via AJAX
        fetch('update_notification_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                notifId: notifId,
                taskId: taskId,
                contentId: contentId
            })
        })
        .then(response => {
            if (response.ok) {
                // Build URL based on available parameters
                let url = `taskdetails.php?task_id=${encodeURIComponent(taskId)}`;
                
                // Only add content_id if it exists and is not empty/null
                if (contentId && contentId !== 'null' && contentId !== '') {
                    url += `&content_id=${encodeURIComponent(contentId)}`;
                }
                
                window.location.href = url;
            } else {
                console.error('Failed to update notification status');
                alert('Failed to update notification status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating notification');
        });
    }

    // Add click handlers to all notification items
    document.addEventListener('DOMContentLoaded', function() {
        const notificationItems = document.querySelectorAll('.notification-item');
        notificationItems.forEach(item => {
            item.addEventListener('click', function() {
                const notifId = this.getAttribute('data-notif-id');
                const taskId = this.getAttribute('data-task-id');
                const contentId = this.getAttribute('data-content-id');
                
                // Only require taskId to be present
                if (!taskId) {
                    console.error("Missing Task ID:", {notifId, taskId, contentId});
                    alert('This notification is missing required task information');
                    return;
                }
                
                handleNotificationClick(notifId, taskId, contentId);
            });
        });
    });

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