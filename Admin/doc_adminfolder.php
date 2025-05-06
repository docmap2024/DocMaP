<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
include 'connection.php';

// Initialize variables
$documents = [];
$departmentName = '';

// Check if DepartmentFolderID is set in the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    // Sanitize the input to prevent SQL injection
    $departmentFolderId = intval($_GET['id']);

    // Fetch administrative documents from the database
    $query = "SELECT ad.Admin_Docu_ID, ad.UserID, ad.DepartmentFolderID, ad.name, 
                     ad.mimeType, ad.size, ad.uri, ad.Status, ad.TimeStamp,
                     ua.fname, ua.mname, ua.lname, ua.profile
              FROM administrative_document ad
              JOIN useracc ua ON ad.UserID = ua.UserID
              WHERE ad.DepartmentFolderID = $departmentFolderId AND ad.Status = 1";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($result)) {
        // Format user's full name
        $row['FullName'] = $row['fname'] . ' ' . $row['mname'] . '. ' . $row['lname'];
        $row['ProfileImagePath'] = '../img/UserProfile/' . $row['profile'];
        $documents[] = $row;
    }

    // Fetch the department name
    $departmentQuery = "SELECT Name FROM departmentfolders 
                       WHERE DepartmentFolderID = $departmentFolderId";
    $departmentResult = mysqli_query($conn, $departmentQuery);
    if (!$departmentResult) {
        die("Query failed: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($departmentResult) > 0) {
        $departmentRow = mysqli_fetch_assoc($departmentResult);
        $departmentName = $departmentRow['Name'];
    }
}

// Function to get the file icon based on the file extension
function getFileIcon($mimeType) {
    $icon = 'fas fa-file'; // Default file icon

    if (strpos($mimeType, 'pdf') !== false) {
        $icon = 'fas fa-file-pdf';
    } elseif (strpos($mimeType, 'word') !== false || strpos($mimeType, 'doc') !== false) {
        $icon = 'fas fa-file-word';
    } elseif (strpos($mimeType, 'excel') !== false) {
        $icon = 'fas fa-file-excel';
    } elseif (strpos($mimeType, 'image') !== false) {
        $icon = 'fas fa-file-image';
    } elseif (strpos($mimeType, 'powerpoint') !== false) {
        $icon = 'fas fa-file-powerpoint';
    }

    return $icon;
}

// Get all users who have uploaded documents in this department
$users = [];
if (!empty($documents)) {
    $userIds = array_unique(array_column($documents, 'UserID'));
    foreach ($documents as $doc) {
        if (!isset($users[$doc['UserID']])) {
            $users[$doc['UserID']] = [
                'UserID' => $doc['UserID'],
                'FullName' => $doc['FullName'],
                'ProfileImagePath' => $doc['ProfileImagePath']
            ];
        }
    }
    $users = array_values($users); // Reset array keys
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <title>Administrative Documents</title>
    <link rel="icon" type="image/png" href="../img/Logo/docmap-logo-1.png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .no-documents-message {
            text-align: center;
            font-size: 1.5rem;
            color: #555;
        }
        .search-filter {
            display: flex;
            justify-content: flex-end;
            margin: 20px 0;
            margin-top:30px;
            margin-bottom:30px;
        }
        .search-filter input {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
            max-width: 300px;
        }
        .document-item {
            background-color: white;
            border-radius: 10px;
            padding: 20px; /* Increase padding to make it wider */
            width: 100%; /* Full width within the grid column */
            display: flex;
            align-items: center; /* Align items vertically */
            text-align: left; /* Align text to the left */
            height: 80px; /* Adjust height if needed */
            transition: box-shadow 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden; /* Hide overflow */
        }

        .document-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .document-item .icon {
            font-size: 40px; /* Adjust icon size */
            color: #9B2035; /* Icon color */
            margin-right: 20px; /* Space between icon and text */
        }

        .document-name {
            white-space: nowrap; /* Prevent text wrapping */
            overflow: hidden; /* Hide overflow */
            text-overflow: ellipsis; /* Show ellipsis for overflow */
            max-width: calc(100% - 50px); /* Adjust based on icon size */
            text-decoration: none; /* Remove underline */
            color: black; /* Make text color black */
        }

        .document-name:hover {
            text-decoration: none; /* Ensure no underline on hover */
        }
        .document-item {
            text-decoration: none; /* Remove underline */
            display: flex;
            align-items: center;
            color: inherit; /* Inherit color from parent */
        }

        .document-item:hover {
            text-decoration: none; /* Ensure no underline on hover */
        }
        .filter-container {
            display: flex; /* Use flexbox to align elements */
            align-items: center; /* Center vertically */
            margin: 10px 0; /* Add margin for spacing */
        }

        .filter-select, .search-input {
            border: none; /* Remove borders */
            border-radius: 4px; /* Add rounded corners */
            padding: 10px; /* Add padding for spacing */
            margin-left: 10px; /* Add space between elements */
            font-size: 16px; /* Increase font size */
            outline: none; /* Remove outline on focus */
        }

        .filter-select {
            background-color: #ffff; /* Light gray background for select */
            color: #333; /* Dark text color */
            cursor: pointer; /* Change cursor to pointer */
        }

        .search-input {
            background-color: #fff; /* White background for input */
            color: #333; /* Dark text color */
            width: 200px; /* Set a fixed width for input */
        }

        .filter-select:hover, .search-input:hover {
            background-color: #ffff; /* Darker gray on hover */
        }

        .filter-select:focus, .search-input:focus {
            border: 1px solid #007bff; /* Blue border on focus */
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5); /* Light blue shadow on focus */
        }
        .user-option {
            position: relative;
            display: flex;
            align-items: center;
        }

        .user-image {
            width: 30px; /* Size of the image */
            height: 30px; /* Size of the image */
            border-radius: 50%; /* Make the image circular */
            background-size: cover;
            background-position: center;
            margin-right: 10px; /* Space between the image and the name */
        }

        .info-icon {
            position: absolute; /* Absolute positioning */
            top: 10px; /* Position from the top */
            right: 10px; /* Position from the right */
            margin-left: auto; /* Push icon to the right */
            cursor: pointer; /* Change cursor to pointer */
            display: none; /* Initially hidden */
            color: grey; /* Color of the icon */
            font-size: 20px; /* Adjust icon size if necessary */
        }

        .document-item:hover .info-icon {
            display: inline; /* Show icon on hover */
        }
        a {
            color: inherit; /* This will inherit the color from the parent element */
            text-decoration: none; /* This removes the underline */
        }


        .document-item {
            position: relative; /* Set the position relative to the document item */
            min-width: 200px; /* Adjust based on your layout */
            flex: 0 0 auto; /* Prevent items from shrinking */
            text-align: center;
        }
    </style>
</head>
<body>
    <section id="sidebar">
        <?php include 'navbar.php'; ?>
    </section>

    <section id="content">
        <?php include 'topbar.php'; ?>

        <main>
            <div class="Title">
                <h1 class="title">
                    <a href="doc_dfolder.php" style="text-decoration: none; color: inherit;">Documents</a>
                    <i class="fas fa-angle-right" style="margin: 0 8px; color: #9B2035;"></i> <!-- Right angle icon -->
                    <i class="fas fa-folder" style="margin-left: 8px; color: gray;"></i> <!-- Gray folder icon -->
                    <?php echo htmlspecialchars($departmentName); ?>   
                </h1>
            </div>

            <div class="container">
                <div class="search-filter">
                    <select id="filter-alphabetical" class="filter-select">
                        <option value="">Sort Alphabetically</option>
                        <option value="asc">A-Z</option>
                        <option value="desc">Z-A</option>
                    </select>

                    <select id="filter-date" class="filter-select">
                        <option value="">Sort by Date</option>
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                    </select>

                    <select id="userFilter" class="filter-select">
                        <option value="">Select User</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['UserID']; ?>" class="user-option">
                                <span class="user-image" style="background-image: url('<?php echo $user['ProfileImagePath']; ?>');"></span>
                                <?php echo $user['FullName']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="text" placeholder="Search by document name" id="search" style="margin-left:10px;">
                </div>

                <div class="row" id="documents-list">
                    <?php if (!empty($documents)): ?>
                        <?php foreach ($documents as $document): ?>
                            <div class="col-md-4 mb-4">
                                <div class="document-item" data-date="<?php echo htmlspecialchars($document['TimeStamp']); ?>" data-user="<?php echo $document['UserID']; ?>">
                                    <i class="<?php echo getFileIcon($document['mimeType']); ?> icon"></i>
                                    <div class="document-name">
                                        <a href="../Documents/<?php echo htmlspecialchars($document['name']); ?>" 
                                        target="_blank" 
                                        title="Open Document"
                                        style="text-decoration:none;color:black;">
                                            <?php 
                                            $displayName = preg_replace('/^[^_]*_/', '', $document['name']);
                                            echo htmlspecialchars($displayName); 
                                            ?>
                                        </a>
                                        <i class="fas fa-info-circle info-icon" 
                                        data-toggle="modal" 
                                        data-target="#modal-<?php echo $document['Admin_Docu_ID']; ?>" 
                                        title="View Details"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal for Document Details -->
                            <div class="modal fade" id="modal-<?php echo $document['Admin_Docu_ID']; ?>" tabindex="-1" role="dialog" aria-labelledby="modalLabel-<?php echo $document['Admin_Docu_ID']; ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalLabel-<?php echo $document['Admin_Docu_ID']; ?>">Document Details</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Document Name:</strong> <?php echo htmlspecialchars($displayName); ?></p>
                                            <p><strong>Uploaded By:</strong> <?php echo htmlspecialchars($document['FullName']); ?></p>
                                            <p><strong>Upload Time:</strong> <?php echo htmlspecialchars($document['TimeStamp']); ?></p>
                                            <p><strong>File Type:</strong> <?php echo htmlspecialchars($document['mimeType']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="no-documents-message">No administrative documents found.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </section>

    <!-- [Keep all your existing JavaScript from doc_grade_content.php] -->
    <script>
        $(document).ready(function () {
            // Store the original documents list (HTML) on page load
            let originalDocs = $('#documents-list').html();

            // Function to render filtered/sorted documents while maintaining grid layout
            function renderDocuments(documents) {
                let container = $('<div class="row"></div>'); // Rebuild row container
                documents.each(function () {
                    let col = $('<div class="col-md-4 mb-4"></div>'); // Preserve column layout
                    col.append($(this));
                    container.append(col);
                });
                $('#documents-list').html(container.html()); // Replace content
            }

            // Filter and Search Documents
            function filterDocuments() {
                let selectedUser = $('#userFilter').val();
                let searchQuery = $('#search').val().toLowerCase();

                // Filter by User
                let filteredDocs = selectedUser
                    ? $('.document-item').filter(function () {
                        return $(this).data('user') === selectedUser;
                    })
                    : $('.document-item');

                // Search by document name
                filteredDocs = filteredDocs.filter(function () {
                    let docName = $(this).find('.document-name').text().toLowerCase();
                    return docName.includes(searchQuery);
                });

                renderDocuments(filteredDocs); // Maintain grid layout
            }

            // Sort Alphabetically
            $('#filter-alphabetical').change(function () {
                let order = $(this).val();
                if (order === '') {
                    $('#documents-list').html(originalDocs); // Reset to original order
                } else {
                    let sortedDocs = $('.document-item').sort(function (a, b) {
                        let textA = $(a).find('.document-name').text().toUpperCase();
                        let textB = $(b).find('.document-name').text().toUpperCase();
                        return order === 'asc' ? textA.localeCompare(textB) : textB.localeCompare(textA);
                    });
                    renderDocuments(sortedDocs); // Maintain grid layout
                }
                filterDocuments(); // Reapply user and search filters
            });

            // Sort by Date
            $('#filter-date').change(function () {
                let order = $(this).val();
                if (order === '') {
                    $('#documents-list').html(originalDocs); // Reset to original order
                } else {
                    let sortedDocs = $('.document-item').sort(function (a, b) {
                        let dateA = new Date($(a).data('date'));
                        let dateB = new Date($(b).data('date'));
                        return order === 'newest' ? dateB - dateA : dateA - dateB;
                    });
                    renderDocuments(sortedDocs); // Maintain grid layout
                }
                filterDocuments(); // Reapply user and search filters
            });

                // Filter by User
                $('#userFilter').change(function() {
                    filterDocuments();
                });

                // Function to filter documents by user and search query
                function filterDocuments() {
                    let userId = $('#userFilter').val();
                    let query = $('#search').val().toLowerCase();

                    $('.document-item').each(function() {
                        let docName = $(this).find('.document-name').text().toLowerCase();
                        let itemUserId = $(this).data('user');

                        if ((userId === '' || userId == itemUserId) && (docName.indexOf(query) > -1)) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                }

                // Search by Document Name
                $('#search').keyup(function() {
                    let query = $(this).val().toLowerCase();
                    $('.document-item').each(function() {
                        let docName = $(this).find('.document-name').text().toLowerCase();
                        if (docName.indexOf(query) > -1) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                });

                // Append sorted documents back while maintaining grid structure
                function appendSortedDocs(sortedDocs) {
                    // Create the grid structure by iterating over each sorted document
                    let row = $('<div class="row"></div>');
                    let colContainer = $('<div class="col-md-4 mb-4"></div>');

                    sortedDocs.each(function() {
                        row.append($(this)); // Append the document item to the row
                    });

                    $('#documents-list').append(row);  // Append the entire row to the documents list
                }
            });
    </script>
    
    <script src="assets/js/script.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>