<?php
session_start();
include 'connection.php';

// Fetch the user ID from the session
$user_id = $_SESSION['user_id']; // Ensure this session variable is set upon login

// Fetch the current quarter
$currentDate = date('Y-m-d'); // Get today's date
$queryQuarter = "SELECT Quarter_ID, Quarter_Name 
                 FROM quarter 
                 WHERE '$currentDate' BETWEEN Start_Date AND End_Date";
$resultQuarter = mysqli_query($conn, $queryQuarter);
$quarterName = '';
$quarterID = '';

if ($resultQuarter) {
    if ($row = mysqli_fetch_assoc($resultQuarter)) {
        $quarterName = htmlspecialchars($row['Quarter_Name']);
        $quarterID = $row['Quarter_ID']; // Store Quarter_ID for filtering content
    } else {
        $quarterName = 'No current quarter';
    }
}

// Fetch content data that is not present in the mps table
$queryContent = "
    SELECT fs.ContentID, fs.Title, fs.Captions
    FROM feedcontent fs
    INNER JOIN usercontent uc ON fs.ContentID = uc.ContentID
    WHERE uc.UserID = $user_id 
      AND uc.Status = 1
      AND fs.ContentID NOT IN (
          SELECT ContentID 
          FROM mps 
          WHERE UserID = $user_id AND Quarter_ID = '$quarterID'
      )";
$resultContent = mysqli_query($conn, $queryContent);

// Store options in an array
$options = '';
if ($resultContent) {
    while ($row = mysqli_fetch_assoc($resultContent)) {
        $options .= '<option value="' . $row['ContentID'] . '">' 
                  . htmlspecialchars($row['Title']) . ' - ' 
                  . htmlspecialchars($row['Captions']) . '</option>';
    }
} else {
    $options .= '<option value="">No content available</option>';
}
// Check if there are any content items left to upload
$allContentUploaded = mysqli_num_rows($resultContent) == 0; // If no content is left, all content is uploaded

// Disable the "Upload MPS" button if all content is uploaded
$disableButton = $allContentUploaded ? 'disabled' : '';

// Fetch the MPS data for the user and current quarter
$queryMPS = "
    SELECT m.mpsID, m.UserID, m.ContentID, q.School_Year_ID, q.Quarter_Name, 
           CONCAT(fc.Title, ' - ', fc.Captions) AS GradeSection, 
           m.TotalNumOfItems, m.TotalNumOfStudents, m.TotalNumTested, 
           m.MPS, sy.Year_Range AS SY
    FROM mps m
    INNER JOIN quarter q ON m.Quarter_ID = q.Quarter_ID
    INNER JOIN feedcontent fc ON m.ContentID = fc.ContentID
    INNER JOIN schoolyear sy ON q.School_Year_ID = sy.School_Year_ID
    WHERE m.UserID = '$user_id' AND q.Quarter_ID = '$quarterID'";
$resultMPS = mysqli_query($conn, $queryMPS);

// Fetch the School Year data
$schoolyearQuery = "SELECT School_Year_ID, Year_Range FROM schoolyear";
$schoolyearResult = mysqli_query($conn, $schoolyearQuery);

// Fetch Grade Level data (content titles associated with the user)
$gradeLevelQuery = "
    SELECT fs.ContentID, fs.Title, fs.Captions
    FROM feedcontent fs
    INNER JOIN usercontent uc ON fs.ContentID = uc.ContentID
    WHERE uc.UserID = '$user_id' AND uc.Status = 1";
$gradeLevelResult = mysqli_query($conn, $gradeLevelQuery);

// Check if the Print button should be disabled
$printButtonDisabled = (mysqli_num_rows($resultMPS) == 0) ? 'disabled' : ''; 
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MPS</title>
    <link rel="icon" type="image/png" href="img/Logo/docmap-logo-1.png">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Trajan+Pro&display=swap" rel="stylesheet">

    <!-- ======= Styles ====== -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    

    <style>
        .header-section {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-right: 20px;
            margin-bottom: 20px;
        }

        h1.title {
            margin: 0;
        }

        .btn-upload {
            background-color: #9B2035;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            margin-left: 10px;
        }

        .btn-upload:hover {
            background-color: #7a1a2b;
        }

        .readonly-input {
            background-color: #f0f0f0; /* Light gray background */
        }

        /* Style to center the table */
        .table-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        table {
            width: 80%;
            border-collapse: collapse;
        }

        th, td {
            text-align: center;
            padding: 10px;
        }

        th {
            background-color: #9B2035;
            color: white;
        }

        td {
            background-color: #ffff;
        }
        .btn-rounded {
            border-radius: 20px; /* Adjust the value for more or less rounding */
            margin: 0 5px; /* Space between buttons */
        }
        .text-danger {
            color: red; /* For MPS below 75 */
            font-weight:bold;
        }

        .text-success {
            color: green; /* For MPS 75 and above */
            font-weight:bold;
        }
            
        .modal-backdrop {
            z-index: 1040 !important; /* Ensure the backdrop appears above content */
        }

        .modal {
            z-index: 1050 !important; /* Ensure modal appears above backdrop */
        }
        /* Ensure the labels are on top of the input fields */
        .form-group {
            display: flex;
            flex-direction: column;
        
        }
        .form-group label {
           font-weight:bold;
           margin-bottom:-10px;
        }



        .form-control {
            width: 100%; /* Ensure the input fields take up the full width */
        }
        .btn-upload:disabled {
        background-color: #d3d3d3; /* Dimmed background color */
        color: #a9a9a9; /* Dimmed text color */
        cursor: not-allowed; /* Change cursor to indicate it's disabled */
        opacity: 0.5; /* Dim the button with reduced opacity */
    }



    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <?php include 'navbar.php'; ?>
    </section>

    <!-- CONTENT -->
    <section id="content">
        <!-- NAVBAR -->
        <?php include 'topbar.php'; ?>

        <!-- MAIN -->
        <main>
            <div class="header-section">
                <h1 class="title">MPS</h1>
                <button class="btn-upload" id="mpsButton" data-toggle="modal" data-target="#mpsModal" <?php echo $disableButton; ?>>
    Upload MPS
</button>            </div>

            <!-- Modal for Uploading MPS -->
            <div class="modal fade" id="mpsModal" tabindex="-1" role="dialog" aria-labelledby="mpsModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="mpsModalLabel">Upload MPS</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                        <form id="uploadMpsForm" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="contentSelect">Grade and Section:</label>
                                        <select class="form-control" id="contentSelect" name="content_id" required>
                                            <?php echo $options; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="quarterInput">Quarter:</label>
                                        <input type="text" class="form-control readonly-input" id="quarterInput" name="quarter_name" value="<?php echo htmlspecialchars($quarterName); ?>" readonly required>
                                        <input type="hidden" name="quarter_id" value="<?php echo htmlspecialchars($quarterID); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="totalItems">Total Number of Items:</label>
                                        <input type="number" class="form-control" id="totalItems" name="total_items" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="totalStudents">Total No. of Students:</label>
                                        <input type="number" class="form-control" id="totalStudents" name="total_students" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="totalTested">Total No. Tested:</label>
                                        <input type="number" class="form-control" id="totalTested" name="total_tested" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="totalScores">Total Scores:</label>
                                        <input type="number" class="form-control" id="totalScores" name="total_scores" required>
                                    </div>
                                </div>
                            </div>

                            

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="mps">MPS (Mean Percentage Score):</label>
                                        <input type="number" class="form-control readonly-input" id="mps" name="mps" step="0.01" readonly>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" id="uploadMpsButton" class="btn btn-primary">Upload</button>
                        </form>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Dropdowns Above the Table -->
            <div class ="filter-station">
                <h5>Filter Options</h5>
                <div class="row mb-3">
                    <div class="col">
                        <label for="schoolYearSelect">School Year:</label>
                        <select class="form-control" id="schoolYearSelect" name="school_year" required>
                            <option value="">Select School Year</option>
                            <?php while($row = mysqli_fetch_assoc($schoolyearResult)): ?>
                                <option value="<?= $row['School_Year_ID'] ?>"><?= $row['Year_Range'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col">
                        <label for="quarterSelect">Quarter:</label>
                        <select class="form-control" id="quarterSelect" name="quarter" required>
                            <option value="">Select Quarter</option>
                            <option value="1">First Quarter</option>
                            <option value="2">Second Quarter</option>
                            <option value="3">Third Quarter</option>
                            <option value="4">Fourth Quarter</option>
                        </select>
                    </div>
                    <div class="col">
                        <label for="gradeLevelSelect">Grade Level:</label>
                        <select class="form-control" id="gradeLevelSelect" name="grade_level" required>
                            <option value="">Select Grade Level</option>
                            <?php while($row = mysqli_fetch_assoc($gradeLevelResult)): ?>
                                <option value="<?= $row['ContentID'] ?>"><?= $row['Title'] ?> - <?= $row['Captions'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col d-flex align-items-end justify-content-between">
                        <button class="btn btn-primary" id="filterButton">Filter</button>
                        <button class="btn btn-success" id="printButton" onclick="fetchSchoolDetailsAndPrint()" <?php echo $printButtonDisabled; ?> style="background-color:<?php echo $printButtonDisabled ? '#d6d6d6' : '#28a745'; ?>; cursor:<?php echo $printButtonDisabled ? 'not-allowed' : 'pointer'; ?>;">
                            Print
                        </button>
                    </div>
                </div>
            </div>
           
            <!-- Centered Table for Displaying MPS Data -->
            <div class="table-container">
                <table class="table table-bordered" id="mpsTable">
                    <thead>
                        <tr>
                            <th>School Year</th>
                            <th>Quarter</th>
                            <th>Grade/Section</th>
                            <th>Total No. Items</th>
                            <th>Total No. Students</th>
                            <th>Total Takers</th>
                           
                            <th>MPS</th>
                            <th>Mastery/Achievement Level</th>
                            <th  class="action-column">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($resultMPS && mysqli_num_rows($resultMPS) > 0) {
                            while ($row = mysqli_fetch_assoc($resultMPS)) {
                                $mps = htmlspecialchars($row['MPS']);
                                $mpsClass = $mps < 75 ? 'text-danger' : 'text-success';

                                // Determine Mastery/Achievement Level
                                if ($mps >= 96) {
                                    $achievementLevel = "Mastered";
                                } elseif ($mps >= 86) {
                                    $achievementLevel = "Closely Approximating Mastery";
                                } elseif ($mps >= 66) {
                                    $achievementLevel = "Moving Towards Mastery";
                                } elseif ($mps >= 35) {
                                    $achievementLevel = "Average";
                                } elseif ($mps >= 15) {
                                    $achievementLevel = "Low";
                                } elseif ($mps >= 5) {
                                    $achievementLevel = "Very Low";
                                } else {
                                    $achievementLevel = "Absolutely No Mastery";
                                }

                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($row['SY']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['Quarter_Name']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['GradeSection']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['TotalNumOfItems']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['TotalNumOfStudents']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['TotalNumTested']) . '</td>';
                                echo '<td style="font-weight:bold;" class="' . $mpsClass . '">' . $mps . '%</td>';
                                echo '<td style="font-weight:bold;">' . $achievementLevel . '</td>'; // New column for Mastery/Achievement Level
                                echo '<td class="action-column">';
                                    echo '<button class="btn btn-primary btn-rounded edit-btn" data-id="' . htmlspecialchars($row['mpsID']) . '" data-toggle="modal" data-target="#editModal">
                                            <i class="bx bx-show-alt" ></i>
                                        </button>';
                                echo '<button class="btn btn-danger btn-rounded delete-btn" data-id="' . htmlspecialchars($row['mpsID']) . '" data-toggle="modal" data-target="#deleteModal">
                                        <i class="bx bx-trash"></i>
                                    </button>';
                                 
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="11">No data available for the current quarter</td></tr>';
                        }
                        ?>
                    </tbody>


                </table>
            </div>
            <!-- Edit Modal -->
            <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel">MPS Computation</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Computation Formula:</strong></p>
                            <p><strong>MPS = (Total Scores / (Total No. of Items * Total No. Tested)) * 100</strong></p>

                            <p><strong>Details:</strong></p>
                            <div id="computationDetails">
                                <!-- Computation details will be displayed here -->
                            </div>
                            <div id="computedMPS">
                                <!-- Computed MPS will be displayed here -->
                            </div>
                        </div>
                    
                    </div>
                </div>
            </div>


        </main>
    </section>
    <!-- jQuery, Bootstrap JS, and other dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Your custom scripts -->
    <script src="assets/js/script.js"></script>
    <script>
        
     // Handle the click event on the Edit button
$(document).on('click', '.edit-btn', function() {
    const mpsID = $(this).data('id'); // Get the mpsID for the clicked row

    // Make an AJAX request to fetch the necessary data for this record
    $.ajax({
        url: 'get_mps_details.php', // The PHP script that fetches details based on mpsID
        method: 'POST',
        data: {
            mpsID: mpsID
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                const totalScoresValue = data.totalScoresValue;
                const totalItemsValue = data.totalItemsValue;
                const totalTestedValue = data.totalTestedValue;

                // Calculate the MPS based on the formula
                const mps = (totalScoresValue / (totalItemsValue * totalTestedValue)) * 100;

                // Prepare the computation details to display in the modal
                const computationDetails = `
                    <p><strong>Total Scores Value:</strong> ${totalScoresValue}</p>
                    <p><strong>Total Items Value:</strong> ${totalItemsValue}</p>
                    <p><strong>Total Tested Value:</strong> ${totalTestedValue}</p>
                    <p><strong>Computed MPS:</strong> ${mps.toFixed(2)}%</p>
                `;

                // Insert the computation details into the modal
                $('#computationDetails').html(computationDetails);
                $('#computedMPS').html(`<p><strong>MPS = (${totalScoresValue}) / (${totalItemsValue} * ${totalTestedValue}) × 100 = ${mps.toFixed(2)}%</strong></p>`);
            } else {
                // Handle error case (if no data found)
                $('#computationDetails').html('<p>No data available for this MPS ID.</p>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching MPS details:', error);
        }
    });
});

    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const totalItems = document.getElementById('totalItems');
        const totalTested = document.getElementById('totalTested');
        const totalScores = document.getElementById('totalScores');
        const mps = document.getElementById('mps');

        function calculateMPS() {
            const totalItemsValue = parseFloat(totalItems.value) || 0;
            const totalTestedValue = parseFloat(totalTested.value) || 0;
            const totalScoresValue = parseFloat(totalScores.value) || 0;

            if (totalItemsValue > 0 && totalTestedValue > 0) {
                const mpsValue = (totalScoresValue / (totalItemsValue * totalTestedValue)) * 100;
                mps.value = mpsValue.toFixed(2);

                // Change the color and make the MPS text bold
                if (mpsValue >= 75) {
                    mps.style.color = 'green';  // Green if MPS is 75 or above
                    mps.style.fontWeight = 'bold'; // Make it bold
                } else {
                    mps.style.color = 'red'; // Red if MPS is below 75
                    mps.style.fontWeight = 'bold'; // Make it bold
                }
            } else {
                mps.value = '';
                mps.style.color = ''; // Reset color
                mps.style.fontWeight = ''; // Reset font weight
            }
        }

        totalItems.addEventListener('input', calculateMPS);
        totalTested.addEventListener('input', calculateMPS);
        totalScores.addEventListener('input', calculateMPS);
    });
</script>




<script>
function openEditModal(button) {
    // Get the mpsID from the button's data-id attribute
    var mpsID = button.getAttribute('data-id');
    
    // Log the mpsID to the console for debugging
    console.log('MPS ID passed:', mpsID);

    // Make AJAX request to fetch data for the given mpsID
    $.ajax({
        url: 'getMpsData.php',
        type: 'GET',
        data: { mps_id: mpsID },
        dataType: 'json',
        success: function(response) {
            console.log('AJAX Success:', response); // Log the entire response for debugging
            
            // Check if response contains the necessary data
            if (response && response.mpsID) {
                // Log individual response fields to debug
                console.log('Quarter:', response.Quarter_Name);
                console.log('Total Students:', response.TotalNumOfStudents);
                console.log('Total Tested:', response.TotalNumTested);
                
                console.log('MPS:', response.MPS);
                console.log('Total Items:', response.TotalNumOfItems);
                console.log('Total Scores:', response.TotalScores);
                console.log('Content ID:', response.ContentID);
                console.log('Grade Section:', response.GradeSection);

                // Set values in modal form fields
                $('#quarterInput').val(response.Quarter_Name); // Quarter
                $('#totalStudents').val(response.TotalNumOfStudents); // Total Students
                $('#totalTested').val(response.TotalNumTested); // Total Tested
                
                $('#mps').val(response.MPS); // MPS (Mean Percentage Score)
                $('#totalItems').val(response.TotalNumOfItems); // Total Items
                $('#totalScores').val(response.TotalScores); // Total Scores
                $('#contentSelect').val(response.GradeSection); // Grade and Section (displayed as GradeSection)
                
                // Set hidden fields (content_id and quarter_id)
                $('input[name="content_id"]').val(response.ContentID); // Hidden content ID
                $('input[name="quarter_id"]').val(response.School_Year_ID); // Hidden Quarter ID (or use `QuarterID` if it's the correct field)

                // Show the modal (Ensure jQuery is loaded)
                $('#editModal').modal('show');
            } else {
                console.error('No valid data received for the selected MPS ID.');
                alert('No data found for the selected MPS ID.');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            console.log('Response Text:', xhr.responseText);
            alert('An error occurred while fetching data. Please try again.');
        }
    });
}
</script>





    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    
<script>
function fetchSchoolDetailsAndPrint() {
    // Fetch school details using AJAX
    $.ajax({
        url: 'getSchoolDetails.php',
        method: 'GET',
        success: function(data) {
            // Prepare the logo and school details for the print view
            var logo = data.Logo ? '<img src="img/Logo/DEPEDLOGO.png" style="width: 90px; height: auto; " />' : '<p>No Logo Available</p>';
            var teacherSignature = data.Teacher_Signature ? `<img src="img/e_sig/${data.Teacher_Signature}" style="width:150px; height:auto;" />` : '<p>No Signature Available</p>';
            var principalSignature = data.Principal_Signature ? `<img src="img/e_sig/${data.Principal_Signature}" style="width:150px; height:auto;" />` : '<p>No Signature Available</p>';

            var schoolDetails = `
                <div class="header-content" style="text-align: center;">
                    <div class="logo">${logo}</div>
                    <div class="school-details">
                        <p style='font-family: "Old English Text MT", serif; font-weight:bold; font-size:20px;'>Republic of the ${data.Country}</p>
                        <p  style='font-family: "Old English Text MT", serif;font-weight:bold; font-size:28px;'>${data.Organization}</p>
                        <p style="text-transform: uppercase; font-family: 'Tahoma'; font-weight: bold; font-size: 13px;">REGION ${data.Region}</p>
                        <p style="text-transform: uppercase; font-family: 'Tahoma'; font-weight: 900; font-size: 16px;">SCHOOLS DIVISION OF BATANGAS</p>
                        <p style="text-transform: uppercase; font-family: 'Tahoma'; font-weight: 900; font-size: 16px;">LIAN SUB-OFFICE</p>
                        <p style="text-transform: uppercase;font-weight: 900; font-family: 'Tahoma'; font-size: 16px;">${data.Name} - ${data.School_ID}</p>

                    </div>
                </div>
                <hr style="border: 1.5px solid black; margin: 10px 0;" />

                <div class="additional-titles" style="text-align: center; font-family: 'arial', serif; font-weight:bold;">
                    <h3>Mean Percentage Score</h3>
                </div>
            `;

            // Hide action columns in the table
            var actionColumns = document.querySelectorAll('.action-column');
            actionColumns.forEach(function(column) {
                column.style.display = 'none';
            });

            // Create print content
            var printContent = schoolDetails + document.getElementById("mpsTable").outerHTML;

            // Add signature lines at the bottom with e-signatures
            printContent += `
               <div class="signature-section" style="margin-top: 50px; text-align: center; display: flex; justify-content: space-between; align-items: flex-start;">
                    <div style="text-align: center; flex: 1; position: relative;">
                        <span>Prepared by:</span><br/><br/>
                        <div style="position: relative; display: inline-block;">
                            <span class="signature-name" style="font-weight: bold; display: block; position: relative; z-index: 1; margin-top: 40px;">
                                ${data.Teacher_FullName}
                            </span>
                            ${data.Teacher_Signature ? `
                                <img src="img/e_sig/${data.Teacher_Signature}" 
                                    style="width:150px; height:auto; position: absolute; top: -25px; left: 50%; transform: translate(-50%, 0); opacity: 0.9; z-index: 2;" />
                            ` : ''}
                        </div>
                        <hr style="max-width: 50%; margin: 0 auto;" />
                        <span style="font-weight:bold;">${data.Teacher_Role || 'Teacher'}</span>
                    </div>

                    <div style="text-align: center; flex: 1; position: relative;">
                        <span>Approved by:</span><br/><br/>
                        <div style="position: relative; display: inline-block;">
                            <span class="signature-name" style="font-weight: bold; display: block; position: relative; z-index: 1; margin-top: 40px;">
                                ${data.Principal_FullName}
                            </span>
                            ${data.Principal_Signature ? `
                                <img src="img/e_sig/${data.Principal_Signature}" 
                                    style="width:150px; height:auto; position: absolute; top: -25px; left: 50%; transform: translate(-50%, 0); opacity: 0.9; z-index: 2;" />
                            ` : ''}
                        </div>
                        <hr style="max-width: 50%; margin: 0 auto;" />
                        <span style="font-weight:bold;">${data.Principal_Role || 'Principal'}</span>
                    </div>
                </div>


                <!-- Footer Section -->
                    <div class="footer" style="display: flex; align-items: center; justify-content: space-between; margin-top: 50px; padding: 10px; border-top: 2.5px solid black;">
                    <div class="footer-left" style="display: flex; align-items: center; margin-left: 50px; flex: 1;">
                        <img src="img/Logo/DEPED_MATATAGLOGO.png" style="width: 280px; height: auto; margin-right: 10px;" />
                        <img src="img/Logo/${data.School_Logo}" style="width: 110px; height: auto;" />
                    </div>
                    <div class="footer-right" style="text-align: left; font-size: 12px; flex: 2; margin-left:30px; font-size:18px;">
                        <p>${data.Address} ${data.City_Muni} School ID: ${data.School_ID}</p>
                        <p>Contact nos.: ${data.Mobile_No} Landline: ${data.landline}</p>
                        <p style="text-decoration: underline; color: blue;">${data.Email}</p>
                    </div>
                </div>

            `;

            // Open the print window
            var win = window.open('', '', 'height=700,width=900');

            // Write the content to the new window
            win.document.write(`
                <html>
                    <head>
                        <title>Print Table</title>
                        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
                        <style>
                            .header-content {
                                display: flex;
                                flex-direction: column;
                                align-items: center;
                                margin-bottom: 20px;
                            }
                            .school-details {
                                text-align: center;
                                
                            }
                            .school-details h2 {
                                font-size: 1.5em;
                                font-weight: bold;
                            }
                            .school-details p {
                                margin-bottom: -5px;
                            }
                            hr {
                                border-top: 1px solid black;
                                width: 100%;
                                margin-top: 10px;
                            }
                            table {
                                width: 100%;
                                border-collapse: collapse;
                            }
                            th, td {
                                text-align: center;
                                vertical-align: middle;
                                border: 1px solid black;
                                padding: 8px;
                            }
                            .additional-titles {
                                margin-top: 10px;
                                margin-bottom: 50px;
                            }
                            .signature-section {
                                margin-top: 50px;
                                text-align: center;
                                display: flex;
                                justify-content: space-between;
                                align-items: flex-start;
                            }
                            .signature-section div {
                                flex: 1;
                                text-align: center;
                            }
                            .watermark {
                                position: absolute;
                                top: 50%;
                                left: 50%;
                                transform: translate(-50%, -50%);
                                opacity: 0.1;
                                pointer-events: none;
                                z-index: -1;
                            }
                            .footer {
                                position: fixed;
                                bottom: 0;
                                width: 100%;
                                padding: 10px 20px;
                                border-top: 2px solid black;
                                background-color: white;
                            }

                            .footer img {
                                width: 100px;
                                height: auto;
                            }
                            .footer p {
                                margin-bottom:-3px;
                                flex-grow: 1;
                                font-family: 'Times New Roman', serif;
                               
                            }

                        </style>
                    </head>
                    <body>
                    ${printContent}
                    <img src="img/Logo/${data.Logo}" class="watermark" style="width: 700px;" />
                </body>
                </html>
            `);

            win.document.close();

            win.onload = function() {
                win.print();
                win.close();
            };

            actionColumns.forEach(function(column) {
                column.style.display = '';
            });
        },
        error: function() {
            alert("Error fetching school details.");
        }
    });
}
</script>








<script>
$(document).ready(function() {
    // When the filter button is clicked
    $('#filterButton').click(function() {
        // Fetch selected values from the dropdowns
        const schoolYear = $('#schoolYearSelect').val();
        const quarter = $('#quarterSelect').val();
        const gradeLevel = $('#gradeLevelSelect').val();
        
        console.log('Filter button clicked');
        console.log('Selected School Year:', schoolYear);
        console.log('Selected Quarter:', quarter);
        console.log('Selected Grade Level:', gradeLevel);

        // Check if any of the selected filter values are empty
        if (!schoolYear || !quarter || !gradeLevel) {
            // Show SweetAlert instead of default alert
            Swal.fire({
                icon: 'warning', // You can change the icon to 'error', 'success', etc.
                title: 'Invalid Selection',
                text: 'Please select valid options for all filters.',
                confirmButtonText: 'OK'
            });
            return; // Prevent the AJAX request if any filter is not selected
        }

        // AJAX request to fetch filtered data
        $.ajax({
            url: 'filter-mps.php',
            method: 'POST',
            data: {
                school_year: schoolYear,
                quarter: quarter,
                grade_level: gradeLevel
            },
            dataType: 'json',
            success: function(data) {
                console.log('Data received:', data); // Check the response data
                const tbody = $('#mpsTable tbody');
                tbody.empty(); // Clear current table rows
                
                if (data.length > 0) {
                    data.forEach(row => {
                        const mpsClass = row.MPS < 75 ? 'text-danger' : 'text-success';
                       const html = `
                            <tr>
                                <td>${row.SY}</td>
                                <td>${row.Quarter_Name}</td>
                                <td>${row.GradeSection}</td>
                                <td>${row.TotalNumOfItems}</td>
                                <td>${row.TotalNumOfStudents}</td>
                                <td>${row.TotalNumTested}</td>
                                <td class="${mpsClass}">${row.MPS}</td>
                                <td class="achievement-column" style="font-weight:bold;">${row.AchievementLevel}</td>
                                <td class="action-column">
                                    <button class="btn btn-primary btn-rounded edit-btn" data-id="${row.mpsID}" data-toggle="modal" data-target="#editModal">
                                        <i class="bx bx-show-alt"></i>
                                    </button>
                                    <button class="btn btn-danger btn-rounded delete-btn" data-id="${row.mpsID}" data-toggle="modal" data-target="#deleteModal">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;

                        tbody.append(html);
                    });
                    $('#printButton').prop('disabled', false);
                } else {
                    tbody.append('<tr><td colspan="9">No data found</td></tr>');
                    $('#printButton').prop('disabled', true).css({
                        'background-color': '#d6d6d6',
                        'cursor': 'not-allowed'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error); // Log any AJAX errors
                console.log(xhr.responseText); // Log the response
            }
        });
    });

    // Use event delegation to handle the edit button click
    $(document).on('click', '.edit-btn', function() {
        const mpsID = $(this).data('id');
        console.log('Edit button clicked for MPS ID:', mpsID);
        // Add code to populate the edit modal here
    });

    // Use event delegation to handle the delete button click
    $(document).on('click', '.delete-btn', function() {
        const mpsID = $(this).data('id');

        Swal.fire({
            title: 'Are you sure you want to delete this record?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            input: 'password',
            inputPlaceholder: 'Enter your password'
        }).then((result) => {
            if (result.isConfirmed) {
                const password = result.value;
                if (password) {
                    $.ajax({
                        url: 'delete_mps.php',
                        type: 'POST',
                        data: {
                            mpsID: mpsID,
                            password: password
                        },
                        success: function(response) {
                            const res = JSON.parse(response);
                            if (res.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: 'Your record has been deleted.',
                                    icon: 'success',
                                    confirmButtonText: 'OK',
                                }).then(() => {
                                    location.reload(); // Refresh to see changes
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: res.message,
                                    icon: 'error',
                                    confirmButtonText: 'Try Again',
                                    showCloseButton: true
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', error);
                            Swal.fire('Error!', 'An error occurred while processing your request.', 'error');
                        }
                    });
                } else {
                    Swal.fire('Please enter your password!');
                }
            }
        });
    });
});

</script>



    
    <!-- Upload of MPS -->
<script>
$(document).ready(function () {
    $(document).on('click', '#uploadMpsButton', function () {
        var formData = new FormData($('#uploadMpsForm')[0]);

        // Debug: Log FormData to the console
        for (var pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        $.ajax({
            url: 'upload_mps.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                console.log("Raw Response: ", response); // Log the response to the console

                try {
                    const res = JSON.parse(response); // Parse the JSON response
                    console.log("Parsed Response: ", res); // Debug parsed response

                    if (res.status === 'success') {
                        // Show success SweetAlert
                        Swal.fire({
                            title: 'Success!',
                            text: 'MPS uploaded successfully!',
                            icon: 'success',
                            confirmButtonText: 'OK',
                            showCloseButton: true
                        }).then(() => {
                            $('#mpsModal').modal('hide');
                            $('#uploadMpsForm')[0].reset();
                            location.reload(); // Reload the page
                        });
                    } else {
                        // Show error SweetAlert
                        Swal.fire({
                            title: 'Error!',
                            text: res.message || 'An unexpected error occurred.',
                            icon: 'error',
                            confirmButtonText: 'Try Again',
                            showCloseButton: true
                        });
                    }
                } catch (e) {
                    console.error("Parsing error:", e, response);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Invalid response from the server.',
                        icon: 'error',
                        confirmButtonText: 'Close',
                        showCloseButton: true
                    });
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while processing your request.',
                    icon: 'error',
                    confirmButtonText: 'Close',
                    showCloseButton: true
                });
            }
        });
    });
});

</script>


<!-- Delete of MPS -->
<script>
$(document).ready(function() {
    $('.delete-btn').on('click', function() {
        const mpsID = $(this).data('id');

        Swal.fire({
            title: 'Are you sure you want to delete this record?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            input: 'password',
            inputPlaceholder: 'Enter your password'
        }).then((result) => {
            if (result.isConfirmed) {
                const password = result.value;
                if (password) {
                    $.ajax({
                        url: 'delete_mps.php',
                        type: 'POST',
                        data: {
                            mpsID: mpsID,
                            password: password
                        },
                        success: function(response) {
                            const res = JSON.parse(response);
                            if (res.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: 'Your record has been deleted.',
                                    icon: 'success',
                                    confirmButtonText: 'OK',
                                    
                                }).then(() => {
                                    location.reload(); // Refresh to see changes
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: res.message,
                                    icon: 'error',
                                    confirmButtonText: 'Try Again',
                                    showCloseButton: true
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', error);
                            Swal.fire('Error!', 'An error occurred while processing your request.', 'error');
                        }
                    });
                } else {
                    Swal.fire('Please enter your password!');
                }
            }
        });
    });
});
</script>



</body>
</html>
