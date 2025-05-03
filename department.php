<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include your database connection file here
include 'connection.php';

// Get the logged-in user's ID from the session
$user_id = $_SESSION['user_id'];

// Query to fetch the department information for the logged-in user
$sql = "SELECT d.dept_ID, d.dept_name, d.dept_info, d.dept_type 
        FROM useracc u
        JOIN user_department ud ON u.UserID = ud.UserID
        JOIN department d ON ud.dept_ID = d.dept_ID
        WHERE u.UserID = ? AND d.dept_type = 'Administrative'";

// Prepare and execute the query
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Initialize an array to store department data
$departments = [];

// Check if there are any records
if ($result->num_rows > 0) {
    // Fetch all department details
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row; // Add each department to the array
    }
} else {
    echo "No department found for this user.";
}

// Close the statement and database connection
$stmt->close();
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrative Department</title>
    <link rel="icon" type="image/png" href="img/Logo/docmap-logo-1.png">
    <!-- ======= Styles ====== -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- Include Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .cardBox {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            height: 100%;
            margin-top: 30px;
            padding: 0 15px;
        }

        .card {
            width: calc(33.33% - 20px); /* 3 cards per row */
            padding: 20px;
            box-sizing: border-box;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            cursor: pointer; /* Indicates the card is clickable */
        }

        .card h2 {
            font-size: 20px;
            margin-bottom: -1px;
            color: #9B2035;
        }

        .card p {
            font-size: 14px;
            color: grey;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            position: relative;
            animation: fadeIn 0.3s ease-in-out;
        }

        .modal-content input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .modal-content button {
            padding: 10px 20px;
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .modal-content button:hover {
            background-color: #218838;
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

        /* Style for the eye icon */
        #togglePinInput {
            color: #9B2035; /* Match your theme color */
            transition: color 0.3s;
        }

        #togglePinInput:hover {
            color: #7a1a2b; /* Darker shade on hover */
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
            <h1 class="title">Administrative</h1>
            <div class="cardBox">
                <?php
                if (!empty($departments)) {
                    foreach ($departments as $department) {
                        echo "<div class='card' data-dept-id='{$department['dept_ID']}' onclick='openModal({$department['dept_ID']})'>";
                        echo "<h2>{$department['dept_name']}</h2>";
                        echo "<p>{$department['dept_info']}</p>";
                        echo "</div>";
                    }
                } else {
                    echo "<div class='card'>";
                    echo "<p>No department information available.</p>";
                    echo "</div>";
                }
                ?>
            </div>
        </main>
        <!-- MAIN -->
    </section>

    <!-- Modal for PIN Entry -->
    <div id="pinModal" class="modal">
        <div class="modal-content">
            <span class="close" id="pinValidationModalClose">&times;</span>
            <h2>Enter PIN</h2>
            <div style="position: relative;">
                <input type="password" id="pinInput" placeholder="Enter 4-digit PIN" maxlength="4">
                <i class="fas fa-eye" id="togglePinInput" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"></i>
            </div>
            <button onclick="submitPin()">Submit</button>
            <p id="errorMessage" style="color: red; display: none;">Invalid PIN. Please try again.</p>
        </div>
    </div>

    <!-- =========== Scripts =========  -->
    <script src="assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        let selectedDeptId = null;

        // Function to open the modal
        function openModal(deptId) {
            selectedDeptId = deptId;
            document.getElementById('pinModal').style.display = 'flex';
        }

        // Function to submit the PIN
        function submitPin() {
            const pin = document.getElementById('pinInput').value;
            if (pin.length !== 4 || !/^\d{4}$/.test(pin)) {
                document.getElementById('errorMessage').style.display = 'block';
                return;
            }

            // Send PIN to the server for verification
            fetch('verify_pin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ deptId: selectedDeptId, pin: pin }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to the department's task page
                    window.location.href = `tasks.php?deptId=${selectedDeptId}`;
                } else {
                    document.getElementById('errorMessage').style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('pinModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };

        // Close PIN validation modal using plain JavaScript
        document.getElementById('pinValidationModalClose').addEventListener('click', function () {
            document.getElementById('pinModal').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'none';
            document.getElementById('pinInput').value = '';
        });

        // Toggle PIN visibility
        document.getElementById('togglePinInput').addEventListener('click', function () {
            const pinInput = document.getElementById('pinInput');
            if (pinInput.type === 'password') {
                pinInput.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash'); // Change icon to "eye-slash" when visible
            } else {
                pinInput.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye'); // Change icon back to "eye" when hidden
            }
        });
    </script>
</body>
</html>