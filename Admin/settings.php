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
    sd.City_Muni,
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
    <title>General</title>
    <link rel="icon" type="image/png" href="../img/Logo/docmap-logo-1.png">

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
        .sticky-nav {
            position: -webkit-sticky; /* For Safari */
            position: sticky;
            top: 0;
            z-index: 1000; /* Ensures the element stays on top */
            background-color: white; /* Optional: Changes background color when sticky */
            box-shadow: 0 2px 5px rgba(0,0,0,0.2); /* Optional: Adds a shadow to make it stand out */
            padding: 15px; /* Optional: Padding for better content spacing */
        }

        .add-icon  {
            position: absolute;
            top: 20px; /* Adjust as needed for alignment */
            right: 20px; /* Adjust as needed for alignment */
            width: 40px;
            height: 40px;
            background-color: #9B2035;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); /* Optional: Adds a subtle shadow */
            
        }

        .add-icon:hover {
            background-color: darkred; /* Optional: Changes color on hover */
        }
        .photogal{
            margin-top:40px;
        }
        .delete-icon{
            position: absolute;
            top: -10px; /* Adjust as needed for alignment */
            right: -6px; /* Adjust as needed for alignment */
            width: 25px;
            height: 25px;
            background-color: lightgrey;
            color: black;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 12px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); /* Optional: Adds a subtle shadow */
        }
        .delete-icon:hover {
            background-color: grey; /* Optional: Changes color on hover */
        }
        .btnProfile{
        background-color: #9b2035;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 25px; /* Adjust for desired roundness */
            cursor: pointer;
            transition: background-color 0.3s; /* Add a smooth transition effect */
                        font-weight:bold;
        }

        .btnProfile:hover {
            background-color: #7a182a; /* Darker shade on hover */
        }
        .btnProfile1{
        background-color: #9b2035;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 25px; /* Adjust for desired roundness */
            cursor: pointer;
            transition: background-color 0.3s; /* Add a smooth transition effect */
            font-weight:bold;
            margin-bottom:10px;
                        
        }

        .btnProfile1:hover {
            background-color: #7a182a; /* Darker shade on hover */
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
                        <div class="rounded-container sticky-nav" >
                            <h5 class="" style="margin-bottom: 20px;">Navigations</h5>
                            <button class="nav-button" id="showSchoolDetailsBtn">
                                <i class="bx bx-info-circle"></i> School Details
                            </button>
                            <button class="nav-button" id="showSchoolYearBtn">
                                <i class="bx bx-calendar"></i> School Year
                            </button>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div id="schoolDetailsContainer">
                            <div class="rounded-container">
                                <h4 class="mb-3">School Details</h4>
                                <h5 class="mb-3">Logo</h5>
                                <?php if ($school_details): ?>
                                    <div class="d-flex align-items-center">
                                        <div class="image-container">
                                            <img src="<?php echo $school_details['Logo']; ?>" alt="School Logo" class="img-fluid">
                                        </div>
                                        <div class="button-container ml-2">
                                            <button class=" btnProfile" id="uploadLogoButton" data-toggle="modal" data-target="#uploadLogoModal">Upload Logo</button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p>No school details available.</p>
                                <?php endif; ?>

                                <h5 class="mt-4 mb-3">Details</h5>
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
                                            <div class="input-container col-md-4">
                                                <label for="address">Address:</label>
                                                <input type="text" class="form-control" id="address" value="<?php echo $school_details['Address']; ?>" readonly>
                                            </div>
                                            <div class="input-container col-md-4">
                                                <label for="citymuni">City/Municipality:</label>
                                                <input type="text" class="form-control" id="citymuni" value="<?php echo $school_details['City_Muni']; ?>" readonly>
                                            </div>
                                            <div class="input-container col-md-4">
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
                                    <button class="btn btn-primary mt-3 btnProfile" id="btnedit1" data-toggle="modal" data-target="#editDetialsModal">Update Details</button>
                                <?php else: ?>
                                    <p>No school details available.</p>
                                <?php endif; ?>
                            </div>

                            <div class="rounded-container mt-4">
                                <h5>Photos</h5>
                                <div id="photoGallery" class="row photogal">
                                    <!-- Photos will be dynamically displayed here -->
                                </div>
                                <div class="add-icon" data-toggle="modal" data-target="#uploadPhotoModal">
                                    <i class="bx bx-plus"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Adding of School year and duration of Quarter -->
                        <div class="rounded-container mt-4" id="schoolYearContainer">
                            <h4 class="mb-3">School Year Details</h4>
                            <button class="btnProfile1 float-right" id="openModalBtn" data-toggle="modal" data-target="#schoolyearModal">
                                Create New
                            </button>

                            <!-- Table for displaying school years -->
                            <table class="table table-bordered mt-3">
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

            <!-- Modal -->
            <div class="modal fade" id="uploadPhotoModal" tabindex="-1" aria-labelledby="uploadPhotoModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="uploadModalLabel">Upload Photos</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <label for="fileInput" class="form-label">Select up to 5 images:</label>
                            <input type="file" class="form-control" id="fileInput" accept="image/*" multiple>
                            <p class="mt-2 text-muted">Only images are allowed. Max 5 files.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" id="uploadButton">Upload</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
                            <form id="uploadLogoForm" enctype="multipart/form-data" method="post">
                                <div class="form-group">
                                    <label for="logoFile">Choose a file:</label>
                                    <input type="file" class="form-control-file" id="logoFile" name="logoFile" accept=".png,.jpg,.jpeg" required>
                                </div>
                                <input type="hidden" name="school_id" value="<?php echo $school_details['School_ID']; ?>">
                                <button type="submit" id="uploadLogoBtn" class="btn btn-primary">Upload</button>
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
                                    <label for="editSchoolID">School ID:</label>
                                    <input type="text" class="form-control" id="editSchoolID" name="editSchoolID" value="<?php echo $school_details['School_ID']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="editSchoolName">School Name:</label>
                                    <input type="text" class="form-control" id="editSchoolName" name="editSchoolName" value="<?php echo $school_details['Name']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="editCityMuni">City/Municipality:</label>
                                    <input type="text" class="form-control" id="editCityMuni" name="editCityMuni" value="<?php echo $school_details['City_Muni']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="editAddress">Address:</label>
                                    <input type="text" class="form-control" id="editAddress" name="editAddress" value="<?php echo $school_details['Address']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="editRegion">Region:</label>
                                    <input type="text" class="form-control" id="editRegion" name="editRegion" value="<?php echo $school_details['Region']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="editMobileNumber">Mobile Number:</label>
                                    <input type="text" class="form-control" id="editMobileNumber" name="editMobileNumber" value="<?php echo $school_details['Mobile_No']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="editCountry">Country:</label>
                                    <input type="text" class="form-control" id="editCountry" name="editCountry" value="<?php echo $school_details['Country']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="editOrganization">Organization:</label>
                                    <input type="text" class="form-control" id="editOrganization" name="editOrganization" value="<?php echo $school_details['Organization']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="editSocialMedia">Social Media:</label>
                                    <input type="text" class="form-control" id="editSocialMedia" name="editSocialMedia" value="<?php echo $school_details['Social_Media_Link']; ?>" required>
                                </div>
                                <input type="hidden" name="school_id" value="<?php echo $school_details['School_ID']; ?>">
                                <button type="submit" class="btn btn-primary" id="saveDetailsBtn">Update</button>
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
            

            <!-- Performance Modal -->
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Move your custom script after all libraries -->
    <script src="assets/js/script.js"></script>
    <script>
        document.getElementById('uploadButton').addEventListener('click', function () {
            const fileInput = document.getElementById('fileInput');
            const files = fileInput.files;

            if (files.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Warning',
                    text: 'Please select files to upload.'
                });
                return;
            }

            const formData = new FormData();
            for (let i = 0; i < files.length; i++) {
                formData.append('photos[]', files[i]);
            }

            // AJAX request for file upload
            fetch('uploadphotos.php', {
                method: 'POST',
                body: formData,
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Files uploaded successfully!'
                        }).then(() => {
                            // Refresh the entire page
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error uploading files: ' + data.message
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while uploading files.'
                    });
                });
        });

        document.addEventListener('DOMContentLoaded', function () {
            fetchPhotos();

            function fetchPhotos() {
                fetch('fetchphotos.php')
                    .then(response => response.json())
                    .then(data => {
                        const photoGallery = document.getElementById('photoGallery');
                        photoGallery.innerHTML = ''; // Clear previous content

                        if (data.success) {
                            if (data.files.length === 0) {
                                // No photos available
                                photoGallery.innerHTML = `
                                    <div class="text-center">
                                        <p>No available photos. Click the plus button below to start uploading.</p>
                                        <button id="uploadPhotoBtn" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Upload Photo
                                        </button>
                                    </div>
                                `;

                                // Add event listener for upload button
                                const uploadPhotoBtn = document.getElementById('uploadPhotoBtn');
                                uploadPhotoBtn.addEventListener('click', () => {
                                    // Open upload modal or redirect to upload page
                                    console.log('Upload button clicked'); // Replace with your logic
                                });
                            } else {
                                // Populate gallery with photos
                                data.files.forEach(photo => {
                                    const photoItem = document.createElement('div');
                                    photoItem.classList.add('col-md-4', 'mb-4');
                                    photoItem.innerHTML = `
                                        <div class="rounded-container position-relative">
                                            <img src="../assets/School_Images/${photo.name}" alt="${photo.name}" class="img-fluid rounded">
                                            <div class="delete-icon position-absolute top-0 end-0 m-2" data-photo-id="${photo.id}"> 
                                                <i class="fas fa-times"></i>
                                            </div>
                                        </div>
                                    `;
                                    photoGallery.appendChild(photoItem);
                                });

                                // Add delete icon click event listeners
                                document.querySelectorAll('.delete-icon').forEach(icon => {
                                    icon.addEventListener('click', deletePhoto);
                                });
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error fetching photos: ' + data.message
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while fetching photos.'
                        });
                    });
            }


            function deletePhoto(event) {
                const photoId = event.currentTarget.getAttribute('data-photo-id');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You will not be able to recover this photo!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`deletephoto.php?id=${photoId}`)
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('Deleted!', 'Your photo has been deleted.', 'success');
                                    fetchPhotos(); // Refresh the photo gallery
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: data.message || 'An error occurred while deleting the photo.'
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'An error occurred while deleting the photo.'
                                });
                            });
                    }
                });
            }


        });

    </script>

    <script>
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
        console.log("Upload button clicked");
        
        var formData = new FormData($('#uploadLogoForm')[0]);
        
        $.ajax({
            url: 'upload_logo.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json', // Explicitly expect JSON
            success: function (data) {
                console.log("Success response:", data);
                
                if (data.status === 'success') {
                    // Update the image source with cache-busting parameter
                    var newSrc = '../img/Logo/' + data.filename + '?t=' + new Date().getTime();
                    $('.image-container img').attr('src', newSrc);
                    
                    $('#uploadLogoModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message
                    }).then(function () {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Unknown error occurred'
                    });
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error("AJAX error:", jqXHR.responseText, textStatus, errorThrown);
                
                try {
                    // Try to parse the response if it might be JSON
                    var errorResponse = JSON.parse(jqXHR.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Upload Failed',
                        text: errorResponse.message || 'Unknown error occurred'
                    });
                } catch (e) {
                    // If not JSON, show raw response
                    Swal.fire({
                        icon: 'error',
                        title: 'Upload Failed',
                        text: jqXHR.responseText || textStatus
                    });
                }
            }
        });
    });

    // Event handler to populate and open edit details modal
    $('#btnedit1').click(function () {
        // Populate the fields with the current school details
        $('#editSchoolID').val($('#schoolID').val());
        $('#editSchoolName').val($('#schoolName').val());
        $('#editCityMuni').val($('#citymuni').val());
        $('#editAddress').val($('#address').val());
        $('#editRegion').val($('#region').val());
        $('#editMobileNumber').val($('#mobileNumber').val());
        $('#editCountry').val($('#country').val());
        $('#editOrganization').val($('#organization').val());
        $('#editSocialMedia').val($('#socialMedia').val());
        $('#schoolIDHidden').val($('#schoolID').val()); // Set hidden field value
        $('#editDetailsModal').modal('show'); // Open the modal
    });

    // Event handler for saving edited details
    $('#editDetailsForm').submit(function (e) {
        e.preventDefault();

        // Gather the updated values from the form
        var updatedDetails = {
            schoolID: $('#editSchoolID').val(),
            schoolName: $('#editSchoolName').val(),
            cityMuni: $('#editCityMuni').val(),
            address: $('#editAddress').val(),
            region: $('#editRegion').val(),
            mobileNumber: $('#editMobileNumber').val(),
            country: $('#editCountry').val(),
            organization: $('#editOrganization').val(),
            socialMediaLink: $('#editSocialMedia').val() // This needs to match the PHP variable name
        };

        $.ajax({
            url: 'update_school_details.php', // Path to your PHP update script
            type: 'POST',
            data: updatedDetails,
            success: function (response) {
                var data = JSON.parse(response);
                if (data.status === 'success') {
                    // Update the displayed values (if needed)
                    $('#schoolName').text(updatedDetails.schoolName);
                    $('#address').text(updatedDetails.address);
                    // Close the modal
                    $('#editDetailsModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'School details updated successfully.'
                    }).then(function () {
                        location.reload(); // Refresh the page after success
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
