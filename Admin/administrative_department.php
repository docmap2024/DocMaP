<?php
    session_start();
?>
    <!DOCTYPE html>
        <html lang="en">
        <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
        <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
        <link href="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="assets/css/styles.css">
            <link rel="icon" type="image/png" href="../img/Logo/docmap-logo-1.png">
        <title>Administrative Departments</title>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
        <style>
            .container {
                width: 100%; /* Ensure full width for responsiveness */
                height: 600px;
                border-radius: 20px;
                margin: 0 auto;
                padding: 0 15px; /* Added horizontal padding for spacing */
                
            }

            .header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: -10px;
                margin-bottom: 20px;
            }

            .icon-button {
                background-color: #9b2035;
                color: white;
                border: none;
                cursor: pointer;
                border-radius: 90px;
                transition: background-color 0.3s;
                font-size: 24px;
                padding: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                height:60px;
                width: 60px;

            }

            .icon-button:hover {
                background-color: #861c2e;
            }

            /* Modal Styles */
            .modal {
                display: none;
                position: fixed;
                z-index: 1000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0, 0, 0, 0.6);
                align-items: center;
                justify-content: center;
            }

            .modal-content {
                background-color: #fff;
                padding: 30px;
                border-radius: 12px;
                width: 90%;
                max-width: 500px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
                position: relative;
                animation: fadeIn 0.3s ease-in-out;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .close {
                position: absolute;
                top: 15px;
                right: 15px;
                color: #aaa;
                font-size: 24px;
                font-weight: bold;
                cursor: pointer;
                transition: color 0.3s;
            }

            .close:hover,
            .close:focus {
                color: #333;
            }

            /* Form styles */
            form {
                display: flex;
                flex-direction: column;
                gap: 15px;
            }

            form label {
                font-size: 14px;
                font-weight: 500;
                color: #555;
            }

            form input[type="text"],
            form input[type="password"],
            form textarea {
                width: 100%;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 6px;
                font-size: 14px;
                color: #333;
                transition: border-color 0.3s ease;
            }

            form input[type="text"]:focus,
            form input[type="password"]:focus,
            form textarea:focus {
                border-color: #9b2035;
                outline: none;
            }

            form textarea {
                resize: vertical;
                min-height: 100px;
            }

            form button {
                padding: 12px;
                background-color: #28a745;
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 16px;
                font-weight: 500;
                cursor: pointer;
                transition: background-color 0.3s ease;
            }

            form button:hover {
                background-color: #218838;
            }

            /* Eye icon styling */
            .fa-eye,
            .fa-eye-slash {
                color: #9b2035;
                font-size: 16px;
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                cursor: pointer;
                transition: color 0.3s ease;
            }

            .fa-eye:hover,
            .fa-eye-slash:hover {
                color: #861c2e;
            }

            /* Input container for PIN field */
            .input-container {
                position: relative;
            }

            .input-container input {
                padding-right: 40px; /* Space for the eye icon */
            }

            /* Card styling with fixed height and text truncation */
            .card {
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                transition: transform 0.3s ease, box-shadow 0.3s ease;
                height: 100%; /* Make card fill its container */
                display: flex;
                flex-direction: column;
            }

            .card-body {
                padding: 20px;
                flex: 1; /* Allow card-body to grow and fill space */
                display: flex;
                flex-direction: column;
                overflow: hidden; /* Hide overflow */
            }

            .card-title {
                font-size: 1.25rem;
                font-weight: bold;
                margin-bottom: 10px;
                white-space: nowrap; /* Prevent title from wrapping */
                overflow: hidden;
                text-overflow: ellipsis; /* Add ellipsis if title is too long */
            }

            .card-text {
                font-size: 0.9rem;
                color: #555;
                margin-bottom: 15px;
                display: -webkit-box;
                -webkit-line-clamp: 3; /* Limit to 3 lines */
                -webkit-box-orient: vertical;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            /* Push the button container to the bottom */
            .card-footer {
                margin-top: auto; /* Pushes to the bottom */
                padding: 15px 20px 0; /* Top 15px, Left/Right 20px (matches card-body padding) */
                border-top: none;
                background: transparent;
                text-align: left; /* Align button to the left */
            }

            .btn-primary {
                background-color: #9b2035;
                border: none;
                padding: 8px 16px;
                font-size: 0.9rem;
                transition: background-color 0.3s ease;
                width: auto; /* Let the button width fit content */
                display: inline-block; /* Allows text-align to work */
                margin-left: 0; /* Remove any default margin */
            }

            .btn-primary:hover {
                background-color: #861c2e;
            }

            .dropdown {
                position: absolute;
                top: 10px;   /* Adjust as needed */
                right: 18px; /* Adjust as needed */
                /* Add spacing between the heading and dropdown */

            }

            .dropdown-toggle {
                background: none;
                border: none;
                cursor: pointer;
                background-color: transparent;
                color:black;
                margin-left: 10px;
            }

            .dropdown-menu {
                display: none; /* Hide the dropdown menu by default */
                position: absolute; /* Position dropdown menu absolutely */
                background-color: white; /* Background color for dropdown */
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Optional: Add shadow for depth */
                border-radius: 4px; /* Optional: Rounded corners */
                width: 200px; /* Set a maximum width for the dropdown menu */
                width: auto; /* Allows the width to adjust based on content */
                z-index: 1000;
            }

            /* Styles for tablets and larger (768px and up) */
            @media (min-width: 768px) {
                .dropdown-menu {
                    position: absolute; /* Switch to absolute on larger screens */
                    right: 0; /* Align to right of parent */
                    top: 100%; /* Position below the toggle button */
                    margin-top: 0; /* Remove the mobile margin */
                }
            }

            /* Styles for mobile screens (767px and below) */
            @media (max-width: 767px) {
                .dropdown-menu {
                    display: none; /* Hide the dropdown menu by default */
                    position: relative; /* Position dropdown menu absolutely */
                    background-color: white; /* Background color for dropdown */
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Optional: Add shadow for depth */
                    border-radius: 4px; /* Optional: Rounded corners */
                    width: 200px; /* Set a maximum width for the dropdown menu */
                    width: auto; /* Allows the width to adjust based on content */
                    z-index: 1000;
                }
            }

            .dropdown.active .dropdown-menu {
                display: block;
            }

            .dropdown-menu button {
                display: block;
                width: 100%;
                text-align: center;
                padding: 10px 15px;
                border: none;
                background: none;
                cursor: pointer;
                color: #333;
                font-size: 14px;
                
            }

            .dropdown-menu button:hover {
                background-color: #f0f0f0;
            }
            .dropdown button:hover {
                background-color: #f0f0f0;
                
            }

            /* PIN Validation Modal Styles */
            #pinValidationModal .modal-content {
                background-color: #fff;
                padding: 30px;
                border-radius: 12px;
                width: 90%;
                max-width: 400px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
                position: relative;
                animation: fadeIn 0.3s ease-in-out;
            }

            #pinValidationModal h2 {
                margin-bottom: 20px;
            }

            #pinValidationModal #pinError {
                margin-top: 10px;
            }

            
            /*---------- Modal Styles for #inviteUserModal ----------*/
            #inviteUserModal {
                display: none; /* Hidden by default */
                position: fixed;
                z-index: 1000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0, 0, 0, 0.6); /* Semi-transparent black background */
                align-items: center; /* Center vertically */
                justify-content: center; /* Center horizontally */
                display: flex; /* Enable flexbox for centering */
            }

            #inviteUserModal .modal-content {
                background-color: #fff;
                padding: 30px;
                border-radius: 12px;
                width: 90%;
                max-width: 800px; /* Adjusted max-width for better fit */
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
                position: relative;
                animation: fadeIn 0.3s ease-in-out;
                overflow: hidden; /* Prevent content from overflowing */
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            #inviteUserModal .close {
                position: absolute;
                top: 15px;
                right: 15px;
                color: #aaa;
                font-size: 24px;
                font-weight: bold;
                cursor: pointer;
                transition: color 0.3s;
            }

            #inviteUserModal .close:hover,
            #inviteUserModal .close:focus {
                color: #333;
            }

            /*---------- User Table inside Modal ----------*/
            #inviteUserModal .user-table-container {
                margin-top: 20px;
                max-height: 400px; /* Adjusted max-height for better fit */
                overflow-y: auto; /* Enable vertical scrolling */
                border: 1px solid #ccc;
                border-radius: 5px;
                box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
                width: 100%; /* Ensure the container fits within the modal */
            }

            #inviteUserModal .user-info-table {
                width: 100%; /* Ensure the table fits within the container */
                border-collapse: collapse;
                table-layout: fixed; /* Prevent table from overflowing */
            }

            #inviteUserModal .user-info-table th,
            #inviteUserModal .user-info-table td {
                text-align: left;
                padding: 12px; /* Increased padding for better readability */
                border: 1px solid #ddd;
                word-wrap: break-word; /* Ensure long text wraps within cells */
            }

            #inviteUserModal .user-info-table th {
                background-color: #9b2035; /* Dark red background for headers */
                color: white; /* White text for headers */
                font-weight: bold;
            }

            #inviteUserModal .user-info-table tbody tr:nth-child(even) {
                background-color: #f9f9f9; /* Alternate row color */
            }

            #inviteUserModal .user-info-table tbody tr:hover {
                background-color: #f1f1f1; /* Hover effect for rows */
            }

            #inviteUserModal .user-info-table input[type="checkbox"] {
                margin-left: auto;
                margin-right: auto;
                cursor: pointer;
            }

            /*---------- Button Styles ----------*/
            #inviteUserModal .btn-primary {
                background-color: #28a745; /* Green background */
                border: none;
                padding: 10px 20px;
                font-size: 1rem;
                border-radius: 6px;
                color: white;
                cursor: pointer;
                transition: background-color 0.3s ease;
                margin-top: 20px; /* Added margin for spacing */
                display: block;
                margin-left: auto;
                margin-right: auto;
            }

            #inviteUserModal .btn-primary:hover {
                background-color: #218838; /* Darker green on hover */
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
                    <div class="header">
                        <h1 class ="title">Administrative Departments</h1>
                        <button id="openModalBtn" class="icon-button">
                            <i class='bx bx-plus'></i>
                        </button>
                    </div>
                    <div class="container">
                        <div class="departments-container" id="departmentsContainer">
                            <div class="row">
                                <!-- Departments will be dynamically inserted here -->
                            </div>
                        </div>
                    </div>
                </main>

                <!-- Create Department Modal -->
                <div id="createModal" class="modal">
                    <div class="modal-content">
                        <span class="close" id="createModalClose">&times;</span>
                        <h2>Create Department</h2>
                        <form id="createDepartmentForm">
                            <label for="departmentName">Department Name:</label>
                            <input type="text" id="departmentName" name="departmentName" required>
                            <label for="departmentInfo">Department Info:</label>
                            <textarea id="departmentInfo" name="departmentInfo" required></textarea>
                            <label for="departmentPin">Department PIN (Optional):</label>
                            <div class="input-container">
                                <input type="password" id="departmentPin" name="departmentPin" maxlength="4">
                                <i class="fas fa-eye" id="toggleCreatePin"></i>
                            </div>
                            <button type="submit">Create</button>
                        </form>
                    </div>
                </div>

                <!-- Update Department Modal -->
                <div id="updateModal" class="modal">
                    <div class="modal-content">
                        <span class="close" id="updateModalClose">&times;</span>
                        <h2>Update Department</h2>
                        <form id="updateDepartmentForm">
                            <input type="hidden" id="updateDeptID" name="deptID">
                            <label for="updateDepartmentName">Department Name:</label>
                            <input type="text" id="updateDepartmentName" name="departmentName" required>
                            <label for="updateDepartmentInfo">Department Info:</label>
                            <textarea id="updateDepartmentInfo" name="departmentInfo" required></textarea>
                            <label for="updateDepartmentPin">Department PIN (Optional):</label>
                            <div class="input-container">
                                <input type="password" id="updateDepartmentPin" name="departmentPin" maxlength="4">
                                <i class="fas fa-eye" id="toggleUpdatePin"></i>
                            </div>
                            <button type="submit">Update</button>
                        </form>
                    </div>
                </div>

                <!-- PIN Validation Modal -->
                <div id="pinValidationModal" class="modal">
                    <div class="modal-content">
                        <span class="close" id="pinValidationModalClose">&times;</span>
                        <h2>Enter PIN</h2>
                        <form id="pinValidationForm">
                            <div class="input-container">
                                <input type="password" id="pinValidationInput" name="pinInput" maxlength="4" placeholder="Enter 4-digit PIN" required>
                                <i class="fas fa-eye" id="togglePinInput"></i>
                            </div>
                            <button type="submit">Submit</button>
                        </form>
                        <p id="pinError" style="color: red; display: none;">Incorrect PIN. Please try again.</p>
                    </div>
                </div>

                <!-- Modal for Inviting Users -->
                <div id="inviteUserModal" class="modal" style="display: none;">
                    <div class="modal-content">
                        <span class="close" onclick="closeModal('inviteUserModal')">&times;</span>
                        <h2>Invite Users to Department</h2>
                        <form id="inviteUsersForm" method="POST" action="send_invitations.php">
                            <div class="user-table-container">
                                <table id="usersTable" class="user-info-table">
                                    <thead>
                                        <tr>
                                            <th>Select</th>
                                            <th>Full Name</th>
                                            <th>Rank</th>
                                            <th>Email</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Database connection
                                        include 'connection.php';

                                        // Fetch users eligible to be invited
                                        $usersQuery = "SELECT UserID, CONCAT(fname, ' ', mname, ' ', lname) AS FullName, URank, email 
                                                    FROM useracc 
                                                    WHERE role IN ('Department Head', 'Teacher')";
                                        $usersResult = $conn->query($usersQuery);

                                        if ($usersResult->num_rows > 0) {
                                            while ($user = $usersResult->fetch_assoc()) {
                                                echo "
                                                <tr>
                                                    <td><input type='checkbox' name='userIDs[]' value='{$user['UserID']}'></td>
                                                    <td>" . htmlspecialchars($user['FullName']) . "</td>
                                                    <td>" . htmlspecialchars($user['URank'] ?? 'N/A') . "</td>
                                                    <td>" . htmlspecialchars($user['email']) . "</td>
                                                </tr>";
                                            }
                                        } else {
                                            echo "
                                            <tr>
                                                <td colspan='4'>No eligible users found.</td>
                                            </tr>";
                                        }

                                        // Close the database connection
                                        $conn->close();
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <input type="hidden" name="deptId" id="deptIdInput">
                            <input type="hidden" name="deptName" id="deptNameInput">
                            <input type="hidden" name="pin" id="pinValidationInput">
                            <button type="submit" class="btn btn-primary">Send Invitations</button>
                        </form>
                    </div>
                </div>
            </section>

            <script src="assets/js/script.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            
            <script>
                $(document).ready(function () {
                    // Function to fetch and display departments
                    function fetchAndDisplayDepartments() {
                        $.ajax({
                            url: 'fetch_school_department.php',
                            method: 'GET',
                            success: function (response) {
                                if (response.status === 'success') {
                                    const departments = response.departments;
                                    const departmentsContainer = $('#departmentsContainer .row');
                                    departmentsContainer.empty(); // Clear existing content

                                    departments.forEach(department => {
                                        // If pin is null, undefined, or empty, set to "none"
                                        const pinValue = department.pin ? department.pin : 'none';

                                        const departmentCard = `
                                            <div class="col-md-4 mb-4">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <h5 class="card-title">${department.dept_name}</h5>
                                                        <p class="card-text">${department.dept_info}</p>
                                                        
                                                        <!-- Hidden PIN initially -->
                                                        <p class="card-text pin-container" id="pin-${department.dept_ID}" style="display: none;">
                                                            <strong>PIN:</strong> <span>${pinValue}</span>
                                                        </p>

                                                        <div class="dropdown">
                                                            <button class="dropdown-toggle"></button>
                                                            <div class="dropdown-menu">
                                                                <button class="btn-icon" onclick="showUpdateModal(${department.dept_ID}, '${department.dept_name}', '${department.dept_info}', '${department.pin || ''}')">
                                                                    <i class='bx bx-edit-alt'></i> Edit
                                                                </button>
                                                                <button class="btn-icon btn-small" onclick="deleteDepartment(${department.dept_ID})">
                                                                    <i class='bx bx-trash'></i> Delete
                                                                </button>
                                                                <button class="btn-icon btn-small show-pin-btn" id="show-pin-btn-${department.dept_ID}" onclick="showDepartmentPin(${department.dept_ID})">
                                                                    <i class='bx bx-key'></i> Show Pin
                                                                </button>
                                                                <button class="btn-icon btn-small" onclick="inviteUser(${department.dept_ID}, '${department.dept_name}', '${department.pin || ''}')">
                                                                    <i class='bx bx-envelope'></i> Invite User
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <!-- Button container (pushed to the bottom) -->
                                                        <div class="card-footer">
                                                            <button class="btn btn-primary" onclick="accessDepartment(${department.dept_ID}, '${department.dept_name}')">Access</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        `;
                                        departmentsContainer.append(departmentCard);
                                    });
                                } else {
                                    console.error('Error:', response.message);
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error('AJAX Error:', error);
                            }
                        });
                    }

                    // Function to show/hide the PIN and toggle button text
                    window.showDepartmentPin = function (deptId) {
                        const pinElement = $(`#pin-${deptId}`);
                        const buttonElement = $(`#show-pin-btn-${deptId}`);

                        if (pinElement.is(':visible')) {
                            pinElement.hide();
                            buttonElement.html(`<i class='bx bx-key'></i> Show Pin`);
                        } else {
                            pinElement.show();
                            buttonElement.html(`<i class='bx bx-key'></i> Hide Pin`);
                        }
                    }

                    // Fetch departments on page load
                    fetchAndDisplayDepartments();

                    // Open Create Modal
                    $('#openModalBtn').click(function () {
                        $('#createModal').css('display', 'flex');
                    });

                    // Close Create Modal
                    $('#createModalClose').click(function () {
                        $('#createModal').css('display', 'none');
                    });

                    // Function to clear the Create Department form
                    function clearCreateDepartmentForm() {
                        $('#departmentName').val('');
                        $('#departmentInfo').val('');
                        $('#departmentPin').val('');
                    }

                    // Submit Create Department Form
                    $('#createDepartmentForm').submit(function (e) {
                        e.preventDefault();
                        const departmentName = $('#departmentName').val();
                        const departmentInfo = $('#departmentInfo').val();
                        const departmentPin = $('#departmentPin').val();

                        $.ajax({
                            url: 'create_school_department.php',
                            method: 'POST',
                            data: {
                                departmentName: departmentName,
                                departmentInfo: departmentInfo,
                                departmentPin: departmentPin
                            },
                            success: function (response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Department Created!',
                                    text: 'The department has been successfully created.'
                                }).then(() => {
                                    clearCreateDepartmentForm();  // Clear form on success
                                    $('#createModal').css('display', 'none'); // Close the modal
                                    fetchAndDisplayDepartments(); // Reload departments immediately
                                });
                            },
                            error: function (xhr, status, error) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'An error occurred while creating the department.'
                                });
                            }
                        });
                    });


                    // Function to show the Update Modal and populate it with department data
                    window.showUpdateModal = function (deptID, deptName, deptInfo, deptPin) {
                        // Populate the update modal fields
                        $('#updateDeptID').val(deptID);
                        $('#updateDepartmentName').val(deptName);
                        $('#updateDepartmentInfo').val(deptInfo);
                        $('#updateDepartmentPin').val(deptPin || '');

                        // Show the update modal
                        $('#updateModal').css('display', 'flex');
                    };

                    // Submit Update Department Form
                    $('#updateDepartmentForm').submit(function (e) {
                        e.preventDefault();
                        const deptID = $('#updateDeptID').val();
                        const departmentName = $('#updateDepartmentName').val();
                        const departmentInfo = $('#updateDepartmentInfo').val();
                        const departmentPin = $('#updateDepartmentPin').val();

                        $.ajax({
                            url: 'update_school_department.php',
                            method: 'POST',
                            data: {
                                deptID: deptID,
                                departmentName: departmentName,
                                departmentInfo: departmentInfo,
                                departmentPin: departmentPin
                            },
                            success: function (response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Department Updated!',
                                    text: 'The department has been successfully updated.'
                                }).then(() => {
                                    $('#updateModal').css('display', 'none'); // Close the modal
                                    fetchAndDisplayDepartments(); // Reload departments immediately
                                });
                            },
                            error: function (xhr, status, error) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'An error occurred while updating the department.'
                                });
                            }
                        });
                    });

                    // Close Update Modal
                    $('#updateModalClose').click(function () {
                        $('#updateModal').css('display', 'none');
                    });

                    // Function to delete a department
                    window.deleteDepartment = function (deptID) {
                        // Show a confirmation dialog
                        Swal.fire({
                            title: 'Are you sure?',
                            text: 'You are about to delete this department. This action cannot be undone!',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Yes, delete it!'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Send an AJAX request to delete the department
                                $.ajax({
                                    url: 'delete_school_department.php',
                                    method: 'POST',
                                    data: { deptID: deptID },
                                    success: function (response) {
                                        console.log('Response:', response); // Debugging: Log the response

                                        // Parse the response if it's a JSON string
                                        if (typeof response === 'string') {
                                            try {
                                                response = JSON.parse(response);
                                            } catch (e) {
                                                console.error('Failed to parse response:', e);
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'Error',
                                                    text: 'An error occurred while processing the response.'
                                                });
                                                return;
                                            }
                                        }

                                        // Check the status in the response
                                        if (response.status === 'success') {
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Department Deleted!',
                                                text: 'The department has been successfully deleted.'
                                            }).then(() => {
                                                fetchAndDisplayDepartments(); // Reload departments
                                            });
                                        } else {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Error',
                                                text: response.message || 'An error occurred while deleting the department.'
                                            });
                                        }
                                    },
                                    error: function (xhr, status, error) {
                                        console.error('AJAX Error:', error); // Debugging: Log the error
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: 'An error occurred while deleting the department.'
                                        });
                                    }
                                });
                            }
                        });
                    };

                    // Toggle PIN visibility for Create Department Modal
                    $('#toggleCreatePin').click(function () {
                        const pinInput = $('#departmentPin');
                        const icon = $(this);
                        if (pinInput.attr('type') === 'password') {
                            pinInput.attr('type', 'text');
                            icon.removeClass('fa-eye').addClass('fa-eye-slash');
                        } else {
                            pinInput.attr('type', 'password');
                            icon.removeClass('fa-eye-slash').addClass('fa-eye');
                        }
                    });

                    // Toggle PIN visibility for Update Department Modal
                    $('#toggleUpdatePin').click(function () {
                        const pinInput = $('#updateDepartmentPin');
                        const icon = $(this);
                        if (pinInput.attr('type') === 'password') {
                            pinInput.attr('type', 'text');
                            icon.removeClass('fa-eye').addClass('fa-eye-slash');
                        } else {
                            pinInput.attr('type', 'password');
                            icon.removeClass('fa-eye-slash').addClass('fa-eye');
                        }
                    });
                });
            </script>

            <script>
                function accessDepartment(deptID) {
                    // Show the PIN input modal
                    $('#pinValidationModal').css('display', 'flex');

                    // Clear any previous error
                    $('#pinError').hide();

                    // Submit handler
                    $('#pinValidationForm').off('submit').on('submit', function (e) {
                        e.preventDefault();
                        const enteredPin = $('#pinValidationInput').val();

                        $.ajax({
                            url: 'get_school_department_pin.php',
                            method: 'POST',
                            data: {
                                deptID: deptID,
                                pin: enteredPin  // Send user-entered PIN
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    // SweetAlert for successful PIN entry
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Correct PIN!',
                                        text: 'Accessing department...',
                                        timer: 2500,
                                        showConfirmButton: false,
                                        allowOutsideClick: false,
                                        willClose: () => {
                                            // Redirect to next module after alert closes
                                            // Add the `type` parameter to the URL
                                            window.location.href = `content_department.php?dept_ID=${deptID}`;
                                        }
                                    });

                                    // Optionally, you can also immediately hide the PIN modal if desired
                                    $('#pinValidationModal').css('display', 'none');
                                } else {
                                    $('#pinError').text('Incorrect PIN. Please try again.').show();
                                }
                            },
                            error: function () {
                                $('#pinError').text('An error occurred. Please try again.').show();
                            }
                        });
                    });
                }

                // Close PIN validation modal
                $('#pinValidationModalClose').click(function () {
                    $('#pinValidationModal').css('display', 'none');
                    $('#pinError').hide();
                    $('#pinValidationInput').val('');
                });

                // Toggle PIN visibility
                $('#togglePinInput').click(function () {
                    const pinInput = $('#pinValidationInput');
                    const icon = $(this);
                    if (pinInput.attr('type') === 'password') {
                        pinInput.attr('type', 'text');
                        icon.removeClass('fa-eye').addClass('fa-eye-slash');
                    } else {
                        pinInput.attr('type', 'password');
                        icon.removeClass('fa-eye-slash').addClass('fa-eye');
                    }
                });


                document.addEventListener('click', function (event) {
                    const dropdowns = document.querySelectorAll('.dropdown');
                    dropdowns.forEach(dropdown => {
                        const toggle = dropdown.querySelector('.dropdown-toggle');
                        const menu = dropdown.querySelector('.dropdown-menu');
                        
                        // Check if the clicked element is the dropdown toggle
                        if (event.target === toggle) {
                            dropdown.classList.toggle('active'); // Toggle the active class to show/hide the menu
                        } else {
                            dropdown.classList.remove('active'); // Close the dropdown if clicking outside
                        }
                    });
                });
            </script>

            <script>

                // Function to open the invite user modal
                function inviteUser(deptId, deptName, pin) {
                    // Set department details in hidden inputs
                    document.getElementById('deptIdInput').value = deptId;
                    document.getElementById('deptNameInput').value = deptName;
                    document.getElementById('pinValidationInput').value = pin;

                }

                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('inviteUserModal').style.display = 'none';
                });

                // Function to close the modal
                function closeModal(modalId) {
                    document.getElementById(modalId).style.display = 'none';
                }

                // Event listener for the "Invite User" button in the dropdown
                document.addEventListener('DOMContentLoaded', function () {
                    const inviteUserButtons = document.querySelectorAll('.dropdown-menu button[onclick*="inviteUser"]');
                    inviteUserButtons.forEach(button => {
                        button.addEventListener('click', function () {
                            const deptId = this.getAttribute('data-dept-id');
                            const deptName = this.getAttribute('data-dept-name');
                            const pin = this.getAttribute('data-pin');
                            inviteUser(deptId, deptName, pin);
                        });
                    });
                });

                // Ensure the modal is hidden on page load
                document.addEventListener('DOMContentLoaded', function () {
                    document.getElementById('inviteUserModal').style.display = 'none';
                });

                $(document).ready(function () {
                    $('#inviteUsersForm').on('submit', function (e) {
                        e.preventDefault(); // Prevent the default form submission
                        sendInvitations(); // Call the sendInvitations function
                    });
                });

                function sendInvitations() {
                    const selectedUsers = [];
                    $('input[name="userIDs[]"]:checked').each(function () {
                        selectedUsers.push($(this).val()); // Collect selected user IDs
                    });

                    if (selectedUsers.length === 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'No Users Selected',
                            text: 'Please select at least one user.',
                        });
                        return;
                    }

                    // Show SweetAlert to inform that emails are being sent
                    Swal.fire({
                        title: 'Sending Invitations',
                        text: 'Please wait while we send the invitations...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Send AJAX request to send invitations
                    $.ajax({
                        url: 'send_invitations.php',
                        method: 'POST',
                        data: {
                            deptId: $('#deptIdInput').val(),
                            deptName: $('#deptNameInput').val(),
                            pin: $('#pinValidationInput').val(),
                            userIDs: selectedUsers
                        },
                        success: function (response) {
                            Swal.close(); // Close the loading SweetAlert
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Invitations Sent',
                                    text: 'Invitations have been sent successfully!',
                                }).then(() => {
                                    closeModal('inviteUserModal'); // Close the modal after success
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Failed to Send Invitations',
                                    text: 'Failed to send invitations: ' + response.message,
                                });
                            }
                        },
                        error: function (xhr, status, error) {
                            Swal.close(); // Close the loading SweetAlert
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to send invitations. Please try again.',
                            });
                            console.error('AJAX Error:', error);
                        }
                    });
                }

                function closeModal(modalId) {
                    document.getElementById(modalId).style.display = 'none';
                }
            </script>
        </body>
    </html>