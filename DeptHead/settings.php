<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include your database connection file here
include 'connection.php';

// SQL query to fetch school details with mobile and social media information
$sql = "
SELECT 
    sd.school_details_ID,
    sd.School_ID,
    sd.Name,
    sd.Address,
    sd.Region,
    sd.Country,
    sd.Organization,
    sd.Logo,
    sd.Vision,
    sd.Mission,
    sm.Mobile_No,
    soc.Social_Media_Link
FROM 
    school_details sd
LEFT JOIN 
    school_mobile sm ON sd.Mobile_ID = sm.Mobile_ID
LEFT JOIN 
    social_media soc ON sd.Social_Media_ID = soc.Social_Media_ID
WHERE 
    sd.Mobile_ID = 1 AND soc.Social_Media_ID = 1;";

$result = mysqli_query($conn, $sql);

// Check if any records were returned
if (mysqli_num_rows($result) > 0) {
    // Fetch the details into an associative array
    $school_details = mysqli_fetch_assoc($result);
} else {
    $school_details = null; // No details found
}

// Close the database connection
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Details</title>
    <!-- ======= Styles ====== -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        /* Adjusted styling for left-aligned labels */
        .input-container {
            margin-bottom: 15px;
            text-align: left; /* Ensure text alignment for labels */
        }
        
        .input-container label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            text-align: left; /* Align label text to the left */
        }
        
        .input-container input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .rounded-container {
            position: relative; /* Set the container to position relative */
            border-radius: 10px; /* Rounded corners */
            padding: 20px; /* Padding for the container */
            background-color: #fff; /* Background color */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Optional shadow */
        }

        .image-container {
            width: 150px; /* Smaller fixed width */
            height: 150px; /* Smaller fixed height */
            border-radius: 50%; /* Keep the circular shape */
            overflow: hidden; /* Hide overflow to maintain circular shape */
            display: flex; /* Use flex to center the image */
            align-items: center; /* Center vertically */
            justify-content: center; /* Center horizontally */
            background-color: #e9ecef; /* Fallback background color */
            margin-bottom: 20px;
            margin-right: 20px; /* Add space to the right */
        }

        .image-container img {
            width: 100%; /* Make the image take the full width of the container */
            height: auto; /* Maintain the aspect ratio */
            object-fit: cover; /* Ensure the image covers the container */
        }

        .button-container {
            display: flex; /* Use flex to align buttons horizontally */
            gap: 5px; /* Add space between buttons */
            margin-top: 20px; /* Space above buttons */
        }

        .image-button-container {
            display: flex; /* Use flexbox for layout */
            align-items: center; /* Center items vertically */
        }

        .image-container {
            margin-right: 20px; /* Add space between the logo and buttons */
        }
        .nav-button {
        width: 100%; /* Make button take full width */
        margin-bottom: 10px; /* Space between buttons */
        background-color: transparent; /* Transparent background */
        color: #000; /* Default text color */
        text-align: center; /* Center text */
        display: flex; /* Flexbox for icon alignment */
        align-items: center; /* Center the icon and text vertically */
        justify-content: center; /* Center the icon and text horizontally */
        padding: 10px; /* Padding for the button */
        border:none;
        border-radius: 5px; /* Rounded corners */
        transition: background-color 0.3s, color 0.3s; /* Smooth background and text transition */
    }

    .nav-button i {
        margin-right: 10px; /* Space between icon and text */
    }

    .nav-button:hover {
        background-color: #9B2035; /* Background color on hover */
        color: white; /* Text color on hover */
    }

    .nav-button:active {
        background-color: #9B2035; /* Active background color */
        border:none;
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
                <h2 class="title" style="margin-bottom: 20px;">Settings</h2>
            </div>
            <div class="cardBox">
                <div class="row">
                    <div class="col-md-3">
                        <div class="rounded-container">
                            <h5 class="" style="margin-bottom: 20px;">Navigations</h5>
                            <button class="nav-button" id="showSchoolDetailsBtn">
                                <i class="bx bx-info-circle"></i> School Details
                            </button>
                            <button class="nav-button" id="showSchoolYearBtn">
                                <i class="bx bx-calendar"></i> School Year
                            </button>
                            <button class="nav-button" id="showSchoolPerformanceBtn">
                                <i class="bx bx-line-chart"></i> School Performance
                            </button>
                        </div>
                    </div>

                    <div class="col-md-9">
                        <div class="rounded-container" id="schoolDetailsContainer">
                            <h4 class="" style="margin-bottom: 20px;">School Details</h4>
                            <h5 class="" style="margin-bottom: 20px;">Logo</h5>
                            <?php if ($school_details): ?>
                                <div class="image-button-container d-flex align-items-center">
                                    <div class="image-container">
                                        <img src="../img/Logo/<?php echo $school_details['Logo']; ?>" alt="School Logo" class="img-fluid">
                                    </div>
                                    <div class="button-container">
                                        <button class="btn" id="uploadLogoButton" data-toggle="modal" data-target="#uploadLogoModal" style="background-color: #9B2035; color: white; margin-left: 10px;">Upload Logo</button>
                                        <button class="btn" id="deleteButton" style="border: 2px solid #9B2035; color: #9B2035; background-color: transparent;">Delete</button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p>No school details available.</p>
                            <?php endif; ?>

                            <h5 class="" style="margin-bottom: 20px;">Details</h5>
                            <?php if ($school_details): ?>
                                <form>
                                    <div class="form-row">
                                        <div class="input-container col-md-4">
                                            <label for="schoolName">School Name:</label>
                                            <input type="text" class="form-control" id="schoolName" value="<?php echo $school_details['Name']; ?>" readonly>
                                        </div>
                                        <div class="input-container col-md-4">
                                            <label for="schoolID">School ID:</label>
                                            <input type="text" class="form-control" id="schoolID" value="<?php echo $school_details['School_ID']; ?>" readonly>
                                        </div>
                                        <div class="input-container col-md-4">
                                            <label for="mobileNumber">Mobile Number:</label>
                                            <input type="text" class="form-control" id="mobileNumber" value="<?php echo $school_details['Mobile_No']; ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="input-container col-md-6">
                                            <label for="address">Address:</label>
                                            <input type="text" class="form-control" id="address" value="<?php echo $school_details['Address']; ?>" readonly>
                                        </div>
                                        <div class="input-container col-md-6">
                                            <label for="region">Region:</label>
                                            <input type="text" class="form-control" id="region" value="<?php echo $school_details['Region']; ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="input-container col-md-6">
                                            <label for="country">Country:</label>
                                            <input type="text" class="form-control" id="country" value="<?php echo $school_details['Country']; ?>" readonly>
                                        </div>
                                        <div class="input-container col-md-6">
                                            <label for="organization">Organization:</label>
                                            <input type="text" class="form-control" id="organization" value="<?php echo $school_details['Organization']; ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="input-container col-md-12">
                                            <label for="socialMedia">Social Media Link:</label>
                                            <input type="text" class="form-control" id="socialMedia" value="<?php echo $school_details['Social_Media_Link']; ?>" readonly>
                                        </div>
                                    </div>
                                </form>
                                <button class="btn" id="btnedit1" data-toggle="modal" data-target="#editDetialsModal" style="background-color: #9B2035; color: white; margin-left: 10px;">Update Details</button>
                            <?php else: ?>
                                <p>No school details available.</p>
                            <?php endif; ?>
                        </div>


                        <!-- Adding of School year and duration of Quarter -->
                        <div class="rounded-container" id="schoolYearContainer" style="display: block; position: relative;">
                            <h4 style="margin-bottom: 20px;">School Year Details</h4>
                            <button class="btn" id="openModalBtn" data-toggle="modal" data-target="#schoolyearModal" style="position: absolute; top: 15px; right: 15px; background-color: #9B2035; color: white; margin-left: 10px;">
                                Create New
                            </button>

                            <!-- Table for displaying school years -->
                            <table class="table table-bordered" style="margin-top: 40px;">
                                <thead>
                                    <tr>
                                        <th>School Year</th>
                                        <th>Quarter</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Rows will be dynamically generated by the script -->
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- School year performance -->
                        <div class="rounded-container" id="schoolPerformanceContainer" style="display: block;">
                            <h4 style="margin-bottom: 20px;">School Year Performance</h4>

                            <!-- Enrollment Section -->
                            <div class="performance-section" id="enrollmentSection">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5>Enrollment</h5>
                                    <button class="btn createNewBtn" data-type="enroll" data-toggle="modal" data-target="#schoolPerformanceModal"
                                        style="background-color: #9B2035; color: white;">
                                        Create New
                                    </button>
                                </div>
                                <table class="table table-bordered" id="enrollmentTable" style="margin-top: 10px;">
                                    <thead>
                                        <tr>
                                            <th>School Year</th>
                                            <th>Rate</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Dynamically generated rows -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Dropout Section -->
                            <div class="performance-section" id="dropoutSection">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5>Dropout</h5>
                                    <button class="btn createNewBtn" data-type="dropout" data-toggle="modal" data-target="#schoolPerformanceModal"
                                        style="background-color: #9B2035; color: white;">
                                        Create New
                                    </button>
                                </div>
                                <table class="table table-bordered" id="dropoutTable" style="margin-top: 10px;">
                                    <thead>
                                        <tr>
                                            <th>School Year</th>
                                            <th>Rate</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Dynamically generated rows -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Promotion Section -->
                            <div class="performance-section" id="promotionSection">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5>Promotion</h5>
                                    <button class="btn createNewBtn" data-type="promotion" data-toggle="modal" data-target="#schoolPerformanceModal"
                                        style="background-color: #9B2035; color: white;">
                                        Create New
                                    </button>
                                </div>
                                <table class="table table-bordered" id="promotionTable" style="margin-top: 10px;">
                                    <thead>
                                        <tr>
                                            <th>School Year</th>
                                            <th>Rate</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Dynamically generated rows -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Cohort-Survival Section -->
                            <div class="performance-section" id="cohortSurvivalSection">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5>Cohort-Survival</h5>
                                    <button class="btn createNewBtn" data-type="cohort_survival" data-toggle="modal" data-target="#schoolPerformanceModal"
                                        style="background-color: #9B2035; color: white;">
                                        Create New
                                    </button>
                                </div>
                                <table class="table table-bordered" id="cohortSurvivalTable" style="margin-top: 10px;">
                                    <thead>
                                        <tr>
                                            <th>School Year</th>
                                            <th>Rate</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Dynamically generated rows -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Repetition Section -->
                            <div class="performance-section" id="repetitionSection">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5>Repetition</h5>
                                    <button class="btn createNewBtn" data-type="repetition" data-toggle="modal" data-target="#schoolPerformanceModal"
                                        style="background-color: #9B2035; color: white;">
                                        Create New
                                    </button>
                                </div>
                                <table class="table table-bordered" id="repetitionTable" style="margin-top: 10px;">
                                    <thead>
                                        <tr>
                                            <th>School Year</th>
                                            <th>Rate</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Dynamically generated rows -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal for adding new data -->
            <div class="modal fade" id="schoolPerformanceModal" tabindex="-1" role="dialog" aria-labelledby="schoolPerformanceModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Performance Data</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="performanceForm">
                                <div class="form-group">
                                    <label for="schoolYear">School Year</label>
                                    <select id="schoolYear" class="form-control">
                                        <!-- Populated dynamically via JS -->
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="rate">Rate</label>
                                    <input type="number" step="0.01" id="rate" class="form-control" required>
                                </div>
                                    <input type="hidden" id="type" value="enroll">
                                    <button type="submit" class="btn btn-primary">Submit</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Modal -->
            <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel"></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                    </div>
                    <div class="modal-body">
                        <form id="editQuarterForm">
                        <input type="hidden" id="quarterID" name="quarterID">
                        <div class="mb-3">
                            <label for="startDate" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="startDate" name="startDate" required>
                        </div>
                        <div class="mb-3">
                            <label for="endDate" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="endDate" name="endDate" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                    </div>
                </div>
            </div>


            <!-- Modal for Uploading Logo -->
            <div class="modal fade" id="uploadLogoModal" tabindex="-1" role="dialog" aria-labelledby="uploadLogoModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="uploadLogoModalLabel">Upload School Logo</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="uploadLogoForm" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="logoFile">Choose a file:</label>
                                    <input type="file" class="form-control-file" id="logoFile" name="logoFile" accept=".png, .jpg, .jpeg" required>
                                </div>
                                <input type="hidden" name="school_id" value="<?php echo $school_details['School_ID']; ?>">
                                <button type="submit" class="btn btn-primary">Upload</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal for Updating Details -->
            <div class="modal fade" id="editDetialsModal" tabindex="-1" role="dialog" aria-labelledby="editDetialsModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editDetialsModalLabel">Edit School Details</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="editDetailsForm">
                                <div class="form-group">
                                    <label for="editSchoolName">School Name:</label>
                                    <input type="text" class="form-control" id="editSchoolName" name="editSchoolName" value="<?php echo $school_details['Name']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="editAddress">Address:</label>
                                    <input type="text" class="form-control" id="editAddress" name="editAddress" value="<?php echo $school_details['Address']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="editMobileNumber">Mobile Number:</label>
                                    <input type="text" class="form-control" id="editMobileNumber" name="editMobileNumber" value="<?php echo $school_details['Mobile_No']; ?>" required>
                                </div>
                                <input type="hidden" name="school_id" value="<?php echo $school_details['School_ID']; ?>">
                                <button type="submit" class="btn btn-primary">Update</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bootstrap Modal -->
            <div class="modal fade" id="schoolyearModal" tabindex="-1"role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="myModalLabel">Add Enrollment Rate Details </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="schoolYearForm">
                                <div class="mb-3">
                                    <label for="schoolYearInput" class="form-label">School Year</label>
                                    <input type="text" class="form-control" id="schoolYearInput" placeholder="Enter School Year" required>
                                </div>

                                <!-- Quarter One -->
                                <div class="mb-3">
                                    <label for="quarter1Input" class="form-label">Enrolled Student:</label>
                                    <input type="text" class="form-control" id="quarter1Input" value="Quarter One" readonly required>
                                </div>
                                <div class="row mb-3">
                                    <div class="col">
                                        <label for="startDate1Input" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="startDate1Input" required>
                                    </div>
                                    <div class="col">
                                        <label for="endDate1Input" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="endDate1Input" required>
                                    </div>
                                </div>

                                <!-- Quarter Two -->
                                <div class="mb-3">
                                    <label for="quarter2Input" class="form-label">Quarter:</label>
                                    <input type="text" class="form-control" id="quarter2Input" value="Quarter Two" readonly required>
                                </div>
                                <div class="row mb-3">
                                    <div class="col">
                                        <label for="startDate2Input" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="startDate2Input" required>
                                    </div>
                                    <div class="col">
                                        <label for="endDate2Input" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="endDate2Input" required>
                                    </div>
                                </div>

                                <!-- Quarter Three -->
                                <div class="mb-3">
                                    <label for="quarter3Input" class="form-label">Quarter:</label>
                                    <input type="text" class="form-control" id="quarter3Input" value="Quarter Three" readonly required>
                                </div>
                                <div class="row mb-3">
                                    <div class="col">
                                        <label for="startDate3Input" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="startDate3Input" required>
                                    </div>
                                    <div class="col">
                                        <label for="endDate3Input" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="endDate3Input" required>
                                    </div>
                                </div>

                                <!-- Quarter Four -->
                                <div class="mb-3">
                                    <label for="quarter4Input" class="form-label">Quarter:</label>
                                    <input type="text" class="form-control" id="quarter4Input" value="Quarter Four" readonly required>
                                </div>
                                <div class="row mb-3">
                                    <div class="col">
                                        <label for="startDate4Input" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="startDate4Input" required>
                                    </div>
                                    <div class="col">
                                        <label for="endDate4Input" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="endDate4Input" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary" id="saveChangesBtn">Save changes</button>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                           
                        </div>
                    </div>
                </div>
            </div>
            

            <!-- PErformance Modal -->
            <div class="modal fade" id="schoolyearModal" tabindex="-1"role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="myModalLabel">Add School Year and Quarter Details</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="schoolYearForm">
                                <div class="mb-3">
                                    <label for="schoolYearInput" class="form-label">School Year</label>
                                    <input type="text" class="form-control" id="schoolYearInput" placeholder="Enter School Year" required>
                                </div>

                                <!-- Quarter One -->
                                <div class="mb-3">
                                    <label for="quarter1Input" class="form-label">Quarter:</label>
                                    <input type="text" class="form-control" id="quarter1Input" value="Quarter One" readonly required>
                                </div>
                                <div class="row mb-3">
                                    <div class="col">
                                        <label for="startDate1Input" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="startDate1Input" required>
                                    </div>
                                    <div class="col">
                                        <label for="endDate1Input" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="endDate1Input" required>
                                    </div>
                                </div>

                                <!-- Quarter Two -->
                                <div class="mb-3">
                                    <label for="quarter2Input" class="form-label">Quarter:</label>
                                    <input type="text" class="form-control" id="quarter2Input" value="Quarter Two" readonly required>
                                </div>
                                <div class="row mb-3">
                                    <div class="col">
                                        <label for="startDate2Input" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="startDate2Input" required>
                                    </div>
                                    <div class="col">
                                        <label for="endDate2Input" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="endDate2Input" required>
                                    </div>
                                </div>

                                <!-- Quarter Three -->
                                <div class="mb-3">
                                    <label for="quarter3Input" class="form-label">Quarter:</label>
                                    <input type="text" class="form-control" id="quarter3Input" value="Quarter Three" readonly required>
                                </div>
                                <div class="row mb-3">
                                    <div class="col">
                                        <label for="startDate3Input" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="startDate3Input" required>
                                    </div>
                                    <div class="col">
                                        <label for="endDate3Input" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="endDate3Input" required>
                                    </div>
                                </div>

                                <!-- Quarter Four -->
                                <div class="mb-3">
                                    <label for="quarter4Input" class="form-label">Quarter:</label>
                                    <input type="text" class="form-control" id="quarter4Input" value="Quarter Four" readonly required>
                                </div>
                                <div class="row mb-3">
                                    <div class="col">
                                        <label for="startDate4Input" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="startDate4Input" required>
                                    </div>
                                    <div class="col">
                                        <label for="endDate4Input" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="endDate4Input" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary" id="saveChangesBtn">Save changes</button>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                           
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </section>


    <!-- Other HTML code -->

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Move your custom script after all libraries -->
    <script src="assets/js/script.js"></script>

    <script>
        $(document).ready(function () {
            // When any 'Create New' button is clicked, set the type based on the button's data-type attribute
            $('.createNewBtn').on('click', function () {
                const type = $(this).data('type');
                $('#type').val(type);  // Set the hidden type field in the modal form
                fetchSchoolYears();
            });

            // Submit form data for enrollment or other performance data
            $('#performanceForm').on('submit', function (e) {
                e.preventDefault();
                const schoolYear = $('#schoolYear').val();
                const rate = $('#rate').val();
                const type = $('#type').val();  // This value can be 'enroll', 'dropout', etc.

                $.post('upload_performance.php', {
                    school_year_id: schoolYear,
                    rate: rate,
                    type: type
                }, function (response) {
                    // After successful insert, hide the modal and refresh the performance data
                    $('#schoolPerformanceModal').modal('hide');

                    // Show SweetAlert for success
                    Swal.fire({
                        title: 'Success!',
                        text: 'Performance data has been successfully added.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Refresh performance data and page after the user clicks 'OK'
                            fetchEnrollData();
                            fetchDropoutData();
                            fetchPromoteData();
                            fetchCohortData();
                            fetchRepetitionData();

                            // Reset the form fields after a successful creation
                            $('#performanceForm').trigger('reset'); // Reset the entire form
                        }
                    });
                });
            });
        });

        $(document).ready(function () {
            // Fetch data when the page is loaded
            fetchEnrollData();
            fetchDropoutData();
            fetchPromoteData();
            fetchCohortData();
            fetchRepetitionData();
        });

        // Function to fetch and display enrollment data from the backend
        function fetchEnrollData() {
            $.getJSON('fetch_enroll.php', function (data) {
                let rows = '';
                if (data.enrollment.length > 0) {
                    $.each(data.enrollment, function (index, item) {
                        rows += `<tr>
                                    <td>${item.Year_Range}</td>
                                    <td>${item.Rate}</td>
                                    <td>
                                        <button class="btn btn-danger delete-btn" data-id="${item.Enroll_ID}">Delete</button>
                                    </td>
                                </tr>`;
                    });
                } else {
                    rows = `<tr><td colspan="3" class="text-center">No enrollment data available.</td></tr>`;
                }

                $('#enrollmentTable tbody').html(rows);
            });
        }

        $('#enrollmentTable').on('click', '.delete-btn', function () {
            const enrollId = $(this).data('id');  // This should be correctly fetching the ID
            console.log(`Attempting to delete enrollment ID: ${enrollId}`); // Check the ID
            
            // Use SweetAlert for confirmation
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Log the ID being sent for deletion
                    console.log(`Attempting to delete enrollment ID: ${enrollId}`);

                    $.post('delete_enroll.php', { id: enrollId }, function (response) {
                        console.log('Server response:', response); // Log the server response
                        
                        if (response.success) {
                            // Show SweetAlert success message
                            Swal.fire(
                                'Deleted!',
                                'The enrollment has been deleted.',
                                'success'
                            ).then(() => {
                                fetchEnrollData(); // Refresh the table after deletion
                            });
                        } else {
                            // Show SweetAlert error message
                            Swal.fire(
                                'Error!',
                                'There was an issue deleting the data: ' + response.message,
                                'error'
                            );
                        }
                    }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
                        console.error('AJAX request failed:', textStatus, errorThrown);
                        Swal.fire(
                            'Error!',
                            'Failed to delete the enrollment. Please try again later.',
                            'error'
                        );
                    });
                }
            });
        });


        // Function to fetch and display dropout data from the backend
        function fetchDropoutData() {
            $.getJSON('fetch_dropout.php', function (data) {
                let rows = '';
                if (data.dropout.length > 0) {
                    $.each(data.dropout, function (index, item) {
                        rows += `<tr>
                                    <td>${item.Year_Range}</td>
                                    <td>${item.Rate}</td>
                                    <td>
                                        <button class="btn btn-danger delete-btn" data-id="${item.Dropout_ID}">Delete</button>
                                    </td>
                                </tr>`;
                    });
                } else {
                    rows = `<tr><td colspan="3" class="text-center">No dropout data available.</td></tr>`;
                }
                $('#dropoutTable tbody').html(rows);
            });
        }

        // Event delegation to handle delete button clicks
        $('#dropoutTable').on('click', '.delete-btn', function () {
            const dropoutId = $(this).data('id');  // This should be correctly fetching the ID
            console.log(`Attempting to delete dropout ID: ${dropoutId}`); // Check the ID
            
            // Use SweetAlert for confirmation
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Log the ID being sent for deletion
                    console.log(`Attempting to delete dropout ID: ${dropoutId}`);

                    $.post('delete_dropout.php', { id: dropoutId }, function (response) {
                        console.log('Server response:', response); // Log the server response
                        
                        if (response.success) {
                            // Show SweetAlert success message
                            Swal.fire(
                                'Deleted!',
                                'The dropout rate has been deleted.',
                                'success'
                            ).then(() => {
                                fetchDropoutData();  // Refresh the table after deletion
                            });
                        } else {
                            // Show SweetAlert error message
                            Swal.fire(
                                'Error!',
                                'There was an issue deleting the data: ' + response.message,
                                'error'
                            );
                        }
                    }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
                        console.error('AJAX request failed:', textStatus, errorThrown);
                        Swal.fire(
                            'Error!',
                            'Failed to delete the dropout rate. Please try again later.',
                            'error'
                        );
                    });
                }
            });
        });


        // Function to fetch and display promotion data from the backend
        function fetchPromoteData() {
            $.getJSON('fetch_promotion.php', function (data) {
                let rows = '';
                if (data.promotion.length > 0) {
                    $.each(data.promotion, function (index, item) {
                        rows += `<tr>
                                    <td>${item.Year_Range}</td>
                                    <td>${item.Rate}</td>
                                    <td>
                                        <button class="btn btn-danger delete-btn" data-id="${item.Promotion_ID}">Delete</button>
                                    </td>
                                </tr>`;
                    });
                } else {
                    rows = `<tr><td colspan="3" class="text-center">No promotion data available.</td></tr>`;
                }
                $('#promotionTable tbody').html(rows);
            });
        }


        // Event delegation to handle delete button clicks
        $('#promotionTable').on('click', '.delete-btn', function () {
            const promotionId = $(this).data('id');  // This should be correctly fetching the ID
            console.log(`Attempting to delete promotion ID: ${promotionId}`); // Check the ID
            
            // Use SweetAlert for confirmation
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Log the ID being sent for deletion
                    console.log(`Attempting to delete promotion ID: ${promotionId}`);

                    $.post('delete_promotion.php', { id: promotionId }, function (response) {
                        console.log('Server response:', response); // Log the server response
                        
                        if (response.success) {
                            // Show SweetAlert success message
                            Swal.fire(
                                'Deleted!',
                                'The promotion rate has been deleted.',
                                'success'
                            ).then(() => {
                                fetchPromoteData();  // Refresh the table after deletion
                            });
                        } else {
                            // Show SweetAlert error message
                            Swal.fire(
                                'Error!',
                                'There was an issue deleting the data: ' + response.message,
                                'error'
                            );
                        }
                    }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
                        console.error('AJAX request failed:', textStatus, errorThrown);
                        Swal.fire(
                            'Error!',
                            'Failed to delete the promotion rate. Please try again later.',
                            'error'
                        );
                    });
                }
            });
        });



        // Function to fetch and display cohort-survival data from the backend
        function fetchCohortData() {
            $.getJSON('fetch_cohort.php', function (data) {
                let rows = '';
                if (data.cohort.length > 0) {
                    $.each(data.cohort, function (index, item) {
                        rows += `<tr>
                                    <td>${item.Year_Range}</td>
                                    <td>${item.Rate}</td>
                                    <td>
                                        <button class="btn btn-danger delete-btn" data-id="${item.Cohort_ID}">Delete</button>
                                    </td>
                                </tr>`;
                    });
                } else {
                    rows = `<tr><td colspan="3" class="text-center">No cohort-survival data available.</td></tr>`;
                }
                $('#cohortSurvivalTable tbody').html(rows);
            });
        }


        // Event delegation to handle delete button clicks
        $('#cohortSurvivalTable').on('click', '.delete-btn', function () {
            const cohortId = $(this).data('id');  // This should be correctly fetching the ID
            console.log(`Attempting to delete cohort ID: ${cohortId}`); // Check the ID
            
            // Use SweetAlert for confirmation
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Log the ID being sent for deletion
                    console.log(`Attempting to delete cohort ID: ${cohortId}`);

                    $.post('delete_cohort.php', { id: cohortId }, function (response) {
                        console.log('Server response:', response); // Log the server response
                        
                        if (response.success) {
                            // Show SweetAlert success message
                            Swal.fire(
                                'Deleted!',
                                'The cohort survival rate has been deleted.',
                                'success'
                            ).then(() => {
                                fetchCohortData();  // Refresh the table after deletion
                            });
                        } else {
                            // Show SweetAlert error message
                            Swal.fire(
                                'Error!',
                                'There was an issue deleting the data: ' + response.message,
                                'error'
                            );
                        }
                    }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
                        console.error('AJAX request failed:', textStatus, errorThrown);
                        Swal.fire(
                            'Error!',
                            'Failed to delete the cohort survival rate. Please try again later.',
                            'error'
                        );
                    });
                }
            });
        });



        // Function to fetch and display cohort-survival data from the backend
        function fetchRepetitionData() {
            $.getJSON('fetch_repetition.php', function (data) {
                let rows = '';
                if (data.repetition.length > 0) {
                    $.each(data.repetition, function (index, item) {
                        rows += `<tr>
                                    <td>${item.Year_Range}</td>
                                    <td>${item.Rate}</td>
                                    <td>
                                        <button class="btn btn-danger delete-btn" data-id="${item.Repetition_ID}">Delete</button>
                                    </td>
                                </tr>`;
                    });
                } else {
                    rows = `<tr><td colspan="3" class="text-center">No repetition data available.</td></tr>`;
                }
                $('#repetitionTable tbody').html(rows);
            });
        }


        // Event delegation to handle delete button clicks
        $('#repetitionTable').on('click', '.delete-btn', function () {
            const repetitionId = $(this).data('id');  // This should be correctly fetching the ID
            console.log(`Attempting to delete repetition ID: ${repetitionId}`); // Check the ID
            
            // Use SweetAlert for confirmation
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Log the ID being sent for deletion
                    console.log(`Attempting to delete repetition ID: ${repetitionId}`);

                    $.post('delete_repetition.php', { id: repetitionId }, function (response) {
                        console.log('Server response:', response); // Log the server response
                        
                        if (response.success) {
                            // Show SweetAlert success message
                            Swal.fire(
                                'Deleted!',
                                'The repetition rate has been deleted.',
                                'success'
                            ).then(() => {
                                fetchRepetitionData();
                            });
                        } else {
                            // Show SweetAlert error message
                            Swal.fire(
                                'Error!',
                                'There was an issue deleting the data: ' + response.message,
                                'error'
                            );
                        }
                    }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
                        console.error('AJAX request failed:', textStatus, errorThrown);
                        Swal.fire(
                            'Error!',
                            'Failed to delete the repetition rate. Please try again later.',
                            'error'
                        );
                    });
                }
            });
        });


        // Fetch school years and populate dropdown
        function fetchSchoolYears() {
            $.getJSON('fetch_schoolyear.php', function (data) {
                let options = '';
                $.each(data, function (index, item) {
                    options += `<option value="${item.School_Year_ID}">${item.Year_Range}</option>`;
                });
                $('#schoolYear').html(options);  // Populate the select element with school years
            });
        }
    </script>


    <script>
    function loadSchoolYears() {
        // Fetch data from the PHP file using AJAX
        $.ajax({
            url: 'getSchoolYearData.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.error) {
                    alert(data.error); // Handle error
                    return;
                }

                // Reference to the tbody of the table
                var tbody = $('#schoolYearContainer tbody');
                tbody.empty(); // Clear any existing rows

                // Loop through each record and create table rows
                data.forEach(function(row) {
                    var tr = `
                        <tr>
                            <td>${row.Year_Range}</td>
                            <td>${row.Quarter_Name}</td>
                            <td>${row.Start_Date}</td>
                            <td>${row.End_Date}</td>
                            <td>
                                <button class="btn btn-primary btn-rounded edit-btn" data-toggle="modal" data-target="#editModal" data-id="${row.Quarter_ID}" data-quarter="${row.Quarter_Name}" data-year="${row.Year_Range}">
                                        <i class="bx bx-edit"></i> 
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.append(tr); // Append the row to the table body
                });

                // Handle edit button click event
                $('.edit-btn').click(function() {
                    var quarterID = $(this).data('id');
                    var quarterName = $(this).data('quarter');
                    var yearRange = $(this).data('year');

                    // Update the modal title with Quarter_Name and Year_Range
                    $('#editModalLabel').html(`Edit Quarter Duration for <span style="color: #9B2035;">${quarterName} (${yearRange})</span>`);

                    // Fetch quarter details and populate the modal
                    fetchQuarterDetails(quarterID);
                });
            },
            error: function() {
                alert('Error fetching data.');
            }
        });
    }

    // Fetch specific quarter details by Quarter_ID
    function fetchQuarterDetails(quarterID) {
        $.ajax({
            url: 'getQuarterDetails.php',
            method: 'GET',
            data: { id: quarterID }, // Send quarter ID to the server
            dataType: 'json',
            success: function(data) {
                if (data.error) {
                    alert(data.error); // Handle error
                    return;
                }

                // Populate the modal with fetched data
                $('#quarterID').val(data.Quarter_ID);
                $('#startDate').val(data.Start_Date);
                $('#endDate').val(data.End_Date);
            },
            error: function() {
                alert('Error fetching quarter details.');
            }
        });
    }

    // Handle the form submission to update quarter data
    $('#editQuarterForm').submit(function(e) {
        e.preventDefault(); // Prevent form submission

        // Proceed with AJAX update request
        $.ajax({
            url: 'updateQuarter.php',
            method: 'POST',
            data: $(this).serialize(), // Serialize form data
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Quarter updated successfully!',
                    showConfirmButton: false,
                    timer: 1500
                });
                $('#editModal').modal('hide'); // Hide the modal
                loadSchoolYears(); // Reload the table
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error updating quarter!',
                });
            }
        });
    });

    // Call the function when the page loads
    $(document).ready(function() {
        loadSchoolYears();
    });
</script>








    <script>
        // JavaScript to toggle between school details, school year, and school performance
        // Function to hide all containers
        function hideAllContainers() {
            document.getElementById("schoolDetailsContainer").style.display = "none";
            document.getElementById("schoolYearContainer").style.display = "none";
            document.getElementById("schoolPerformanceContainer").style.display = "none";
        }

        // Initially hide all containers except the School Details container
        document.addEventListener('DOMContentLoaded', (event) => {
            hideAllContainers();
            document.getElementById("schoolDetailsContainer").style.display = "block";  // Show School Details by default
        });

        // Show School Details when clicking "School Details" button
        document.getElementById("showSchoolDetailsBtn").onclick = function() {
            hideAllContainers();
            document.getElementById("schoolDetailsContainer").style.display = "block";
        };

        // Show School Year when clicking "School Year" button
        document.getElementById("showSchoolYearBtn").onclick = function() {
            hideAllContainers();
            document.getElementById("schoolYearContainer").style.display = "block";
        };

        // Show School Performance when clicking "School Performance" button
        document.getElementById("showSchoolPerformanceBtn").onclick = function() {
            hideAllContainers();
            document.getElementById("schoolPerformanceContainer").style.display = "block";
        };


        $(document).ready(function() {
    $('#saveChangesBtn').on('click', function() {
        const schoolYear = $('#schoolYearInput').val();

        const quarters = [
            {
                name: 'Quarter One',
                start_date: $('#startDate1Input').val(),
                end_date: $('#endDate1Input').val()
            },
            {
                name: 'Quarter Two',
                start_date: $('#startDate2Input').val(),
                end_date: $('#endDate2Input').val()
            },
            {
                name: 'Quarter Three',
                start_date: $('#startDate3Input').val(),
                end_date: $('#endDate3Input').val()
            },
            {
                name: 'Quarter Four',
                start_date: $('#startDate4Input').val(),
                end_date: $('#endDate4Input').val()
            }
        ];

        $.ajax({
            url: 'upload_schoolyear.php',
            type: 'POST',
            data: {
                schoolYear: schoolYear,
                quarters: JSON.stringify(quarters)
            },
            success: function(response) {
                const result = JSON.parse(response);
                if (result.status === "success") {
                    swal({
                        title: "Success!",
                        text: "School year and quarters added successfully! ID: " + result.school_year_id,
                        icon: "success",
                        button: "Okay",
                    }).then(() => {
                        // Optionally, you can close the modal here
                        $('#schoolyearModal').modal('hide');
                    });
                } else {
                    swal({
                        title: "Error!",
                        text: "Error: " + result.message,
                        icon: "error",
                        button: "Okay",
                    });
                }
            },
            error: function() {
                swal({
                    title: "Error!",
                    text: "An error occurred while processing your request.",
                    icon: "error",
                    button: "Okay",
                });
            }
        });
    });
});

    </script>
    <script>
        $(document).ready(function () {
            // Event handler for uploading the logo
            $('#uploadLogoBtn').click(function (e) {
                e.preventDefault();
                var formData = new FormData($('#uploadLogoForm')[0]); // Make sure this matches your form ID

                $.ajax({
                    url: 'upload_logo.php', // Make sure this path is correct
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function (response) {
                        var data = JSON.parse(response);
                        if (data.status === 'success') {
                            $('.image-container img').attr('src', '../img/Logo/' + data.filename);
                            $('#uploadLogoModal').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'Logo updated successfully.'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message
                            });
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Upload failed',
                            text: textStatus
                        });
                    }
                });
            });

            // Event handler to populate and open edit details modal
            $('#btnedit1').click(function () {
                // Populate the fields with the current school details
                $('#schoolNameEdit').val($('#schoolName').val());
                $('#schoolIDEdit').val($('#schoolID').val());
                $('#mobileNumberEdit').val($('#mobileNumber').val());
                $('#addressEdit').val($('#address').val());
                $('#regionEdit').val($('#region').val());
                $('#countryEdit').val($('#country').val());
                $('#organizationEdit').val($('#organization').val());
                $('#socialMediaEdit').val($('#socialMedia').val());
                $('#editDetailsModal').modal('show'); // Open the modal
            });

            // Event handler for saving edited details
            $('#saveDetailsBtn').click(function (e) {
                e.preventDefault();

                // Gather the updated values
                var updatedDetails = {
                    schoolName: $('#schoolNameEdit').val(),
                    schoolID: $('#schoolIDEdit').val(),
                    mobileNumber: $('#mobileNumberEdit').val(),
                    address: $('#addressEdit').val(),
                    region: $('#regionEdit').val(),
                    country: $('#countryEdit').val(),
                    organization: $('#organizationEdit').val(),
                    socialMedia: $('#socialMediaEdit').val()
                };

                $.ajax({
                    url: 'update_school_details.php', // Path to your PHP update script
                    type: 'POST',
                    data: updatedDetails,
                    success: function (response) {
                        var data = JSON.parse(response);
                        if (data.status === 'success') {
                            // Update the displayed values
                            $('#schoolName').val(updatedDetails.schoolName);
                            $('#schoolID').val(updatedDetails.schoolID);
                            $('#mobileNumber').val(updatedDetails.mobileNumber);
                            $('#address').val(updatedDetails.address);
                            $('#region').val(updatedDetails.region);
                            $('#country').val(updatedDetails.country);
                            $('#organization').val(updatedDetails.organization);
                            $('#socialMedia').val(updatedDetails.socialMedia);
                            $('#editDetailsModal').modal('hide'); // Close the modal

                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'School details updated successfully.'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message
                            });
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Update failed',
                            text: textStatus
                        });
                    }
                });
            });
        });

    </script>
</body>

</html>
