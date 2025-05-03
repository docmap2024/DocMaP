<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not authenticated
    header("Location: ../index.php");
    exit();
}


include 'connection.php';

$user_id = $_SESSION['user_id'];

// Fetch user information
$sql_user = "SELECT * FROM useracc WHERE UserID = ?";
if ($stmt_user = $conn->prepare($sql_user)) {
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    $fname = '';
    if ($result_user->num_rows > 0) {
        $row = $result_user->fetch_assoc();
        $fname = $row['fname'];
    }
    $stmt_user->close();
} else {
    echo "Error preparing user query";
}


// Fetch distinct School Years
$schoolYears = $conn->query("SELECT DISTINCT Year_Range FROM schoolyear ORDER BY Year_Range");
$yearRanges = $conn->query("SELECT DISTINCT Year_Range FROM schoolyear ORDER BY Year_Range");

// Default filter values
$selectedYear = $_GET['school_year'] ?? null;
$selectedYearRange = $_GET['year_range'] ?? null;

// Base SQL query with optional filters
$sql = "
    SELECT sy.Year_Range, 
           SUM(e.Enroll_Gross) AS total_enrollment, 
           AVG(d.Dropout_Rate) AS avg_dropout_rate, 
           AVG(p.Promotion_Rate) AS avg_promotion_rate, 
           AVG(c.Cohort_Rate) AS avg_cohort_rate, 
           AVG(r.Repeaters_Rate) AS avg_repetition_rate, 
           AVG(t.Transition_Rate) AS avg_transition_rate
    FROM performance_indicator pi
    JOIN grade g ON pi.Grade_ID = g.Grade_ID
    JOIN enroll e ON pi.Enroll_ID = e.Enroll_ID
    JOIN dropout d ON pi.Dropout_ID = d.Dropout_ID
    JOIN promotion p ON pi.Promotion_ID = p.Promotion_ID
    JOIN cohort_survival c ON pi.Cohort_ID = c.Cohort_ID
    JOIN repetition r ON pi.Repetition_ID = r.Repetition_ID
    JOIN transition t ON pi.Transition_ID = t.Transition_ID
    JOIN schoolyear sy ON pi.School_Year_ID = sy.School_Year_ID
    WHERE 1=1";

// Add filters
if ($selectedYear) {
    $sql .= " AND sy.Year_Range = '$selectedYear'";
}
if ($selectedYearRange) {
    $sql .= " AND sy.Year_Range = '$selectedYearRange'";
}

$sql .= " GROUP BY sy.Year_Range";
$result = $conn->query($sql);


// Fetch the data
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
date_default_timezone_set('Asia/Manila'); // Set timezone to Manila
$hour = date('H');

if ($hour >= 5 && $hour < 12) {
    $greeting = "Good Morning";
} elseif ($hour >= 12 && $hour < 18) {
    $greeting = "Good Afternoon";
} else {
    $greeting = "Good Evening";
}

$totalDepartmentQuery = "SELECT COUNT(*) as total FROM department";
$totalDepartmentResult = mysqli_query($conn, $totalDepartmentQuery);
$totalDepartment = mysqli_fetch_assoc($totalDepartmentResult)['total'];

// Fetch total users, department heads, and teachers
$totalUsersQuery = "
    SELECT COUNT(*) AS total,  
           SUM(CASE WHEN role = 'Department Head' THEN 1 ELSE 0 END) AS department_head_count,
           SUM(CASE WHEN role = 'Teacher' THEN 1 ELSE 0 END) AS teacher_count
    FROM useracc 
    WHERE role != 'Admin' AND Status = 'Approved'";
$totalUsersResult = mysqli_query($conn, $totalUsersQuery);
$totalUsersData = mysqli_fetch_assoc($totalUsersResult);

// Assign PHP variables
$totalUsers = $totalUsersData['total'] ?? 0;
$departmentHeadCount = $totalUsersData['department_head_count'] ?? 0;
$teacherCount = $totalUsersData['teacher_count'] ?? 0;


// Fetch MPS data
$mpsQuery = "
    SELECT 
        q.Quarter_Name,
        sy.Year_Range,
        AVG(m.MPS) as avg_mps,
        COUNT(CASE WHEN m.`MPSBelow75` = 'Yes' THEN 1 END) as below_75_count,
        COUNT(*) as total_count
    FROM mps m
    JOIN quarter q ON m.Quarter_ID = q.Quarter_ID
    JOIN schoolyear sy ON q.School_Year_ID = sy.School_Year_ID
    GROUP BY q.Quarter_ID, sy.Year_Range
    ORDER BY sy.Year_Range, q.Quarter_Name";
$mpsResult = $conn->query($mpsQuery);
$mpsData = [];
while ($row = $mpsResult->fetch_assoc()) {
    $mpsData[] = $row;
}


// Fetch total departments and breakdown by type
$departmentQuery = "
    SELECT COUNT(*) as total, 
           SUM(CASE WHEN dept_type = 'Academic' THEN 1 ELSE 0 END) AS academic_count,
           SUM(CASE WHEN dept_type = 'Administrative' THEN 1 ELSE 0 END) AS administrative_count
    FROM department";
$departmentResult = mysqli_query($conn, $departmentQuery);
$departmentData = mysqli_fetch_assoc($departmentResult);

// Assign PHP variables
$totalDepartment = $departmentData['total'] ?? 0;
$academicCount = $departmentData['academic_count'] ?? 0;
$administrativeCount = $departmentData['administrative_count'] ?? 0;



$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" />
    <!-- Load Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Dashboard | Home</title>
    <link rel="icon" type="image/png" href="../img/Logo/docmap-logo-1.png">
    <style>
        .title {
            padding-bottom: 30px;
        }

        .info-data {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: -10px;
        }

        .card .head {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card .head h1, .card {
            margin: 0;
            font-weight: bold;
        }

        .card .head i.icon {
            font-size: 40px;
            color: #9B2035;
        }

        .user-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-welcome {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        canvas {
            background: #ffffff;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 10px;
        }

        .charts {
            margin-top: 30px;
        }

        .container {
            padding: 20px; 
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); 
            border-radius: 10px; 
            background: #fff; 
            margin-bottom: 20px;
        }

        .card {
            padding: 20px; 
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); 
            border-radius: 10px; 
            background: #fff; 
            margin-bottom: 20px;
        }

        #productivityChart {
            display: block;
            width: 100%;
            max-height: 400px;
        }

        .carousel-inner canvas {
            width: 100% !important;
            height: 400px !important;
            max-height: 400px;
        }

        .carousel-controls {
            position: absolute;
            top: -50px;
            right: 50px;
        }

        .carousel-control-prev,
        .carousel-control-next {
            width: 40px;
            height: 40px;
            background-color: #fff;
            border-radius: 50%; 
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            background-color: #9b2035; 
        }

        .circle-button {
            position: absolute;
            top: 0;
            right: 50px;
            width: 40px;
            height: 40px;
            background-color: #9b2035;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .doughnut-container {
            width: 70px; 
            height: 70px; 
            display: inline-block; 
            position: relative; 
        }

        .doughnut-container canvas {
            width: 100% !important; 
            height: 100% !important;
        }

        .user-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 10px; 
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); 
            background-color: #ffffff; 
            transition: box-shadow 0.3s; 
        }

        .user-container:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2); 
        }

        .table {
            width: 100%;                     
            border-collapse: collapse;       
            margin: 20px 0;                  
        }

        .table th {
            background-color: #9B2035;         
            color: white;                     
            text-align: center;              
        }

        .table td {
            padding: 10px;                    
            text-align: center;               
            border: 1px solid #ccc;          
            background-color: #ffff;
            font-weight: bold;
        }

        .table tr:nth-child(even) {
            background-color: #f2f2f2;        
        }

        .table tr:nth-child(odd) {
            background-color: #ffffff;        
        }

        .table tr:hover {
            background-color: #e0e0e0;        
        }

        .card-body {
            position: relative;
        }

        .icon1 {
            color: #9b2035;
            font-size: 40px;
            position: absolute;
            top: 15px;
            right: 15px;
        }

        .chart-container {
            width: 90px;
            height: 90px;
            margin: 0 auto;
            border: none;
            background-color: transparent;
            position: relative;
        }

        .chart-container.top-right {
            position: absolute;
            top: -30px;
            right: 1px;
        }

        canvas {
            border: none;
            background-color: transparent;
            position: relative;
        }

        #mpsTrendChart {
            width: 100% !important;
            height: 200px !important;
            padding: 10px;
        }

        .filter{
            display: flex-start;  
            margin-left: auto; 
            margin-top: -50px;
        }

        .filter label{
            align-items: center;
            margin-right: 10px; 
            font-weight: bold; 
            color: #000; 
            font-size: 18px;
        }

        /* Responsive Styles */
        @media (max-width: 1200px) {
            .col-md-5, .col-md-7 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            .carousel-controls {
                top: -40px;
                right: 20px;
            }
            
            .circle-button {
                right: 20px;
            }
            
            .info-data > .row > .col-md-12 > .row {
                flex-direction: column;
            }
            .filter {
                margin-top: -40px;
            }
        }

        @media (max-width: 992px) {
            .card {
                margin-bottom: 15px;
            }
            
            .carousel-inner canvas {
                height: 300px !important;
            }
            
            .school-year-selector {
                margin-left: 0;
                margin-top: 15px;
                width: 100%;
            }
            
            .title-container {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .title {
                margin-bottom: 15px;
            }

            .filter {
                margin-top: 10px;
                margin-left: 0;
                justify-content: flex-start;
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .card {
                padding: 15px;
                margin-bottom: 10px;
            }
            
            .carousel-inner canvas {
                height: 250px !important;
            }
            
            .carousel-controls {
                top: -35px;
                right: 15px;
            }
            
            .circle-button {
                width: 35px;
                height: 35px;
                right: 15px;
            }
            
            .carousel-control-prev,
            .carousel-control-next {
                width: 35px;
                height: 35px;
            }
            
            .icon1 {
                font-size: 30px;
            }
            
            .chart-container,
            .chart-container.top-right {
                width: 60px;
                height: 60px;
            }
            
            .table {
                font-size: 14px;
            }
            
            #search-input {
                width: 200px !important;
            }

            .filter {
                margin-top: 15px;
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter label {
                margin-bottom: 5px;
                margin-right: 0;
            }
        }

        @media (max-width: 576px) {
            .title {
                font-size: 24px;
                padding-bottom: 15px;
            }
            
            .card {
                padding: 10px;
            }
            
            .carousel-inner canvas {
                height: 200px !important;
            }
            
            .carousel-controls {
                top: -30px;
                right: 10px;
            }
            
            .circle-button {
                width: 30px;
                height: 30px;
                font-size: 14px;
                right: 10px;
            }
            
            .carousel-control-prev,
            .carousel-control-next {
                width: 30px;
                height: 30px;
            }
            
            .icon1 {
                font-size: 25px;
                top: 10px;
                right: 10px;
            }
            
            .chart-container,
            .chart-container.top-right {
                width: 50px;
                height: 50px;
                top: -20px;
            }
            
            .table {
                font-size: 12px;
            }
            
            #search-input {
                width: 150px !important;
                font-size: 12px;
            }
            
            .user-container {
                flex-direction: column;
                text-align: center;
            }
            
            .user-container h1, .user-container h5 {
                font-size: 1.2rem;
            }
            
            .info-data {
                flex-direction: column;
            }
            
            .col-md-6, .col-md-5, .col-md-7 {
                padding-left: 5px;
                padding-right: 5px;
            }

            .filter {
                align-items: flex-start;
            }
            
            .filter .form-control {
                width: 100% !important;
            }
        }

        @media (max-width: 400px) {
            .title {
                font-size: 20px;
            }
            
            .card {
                padding: 8px;
            }
            
            .carousel-inner canvas {
                height: 180px !important;
            }
            
            .table {
                font-size: 11px;
            }
            
            #search-input {
                width: 120px !important;
            }
            
            .chart-container,
            .chart-container.top-right {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>

<?php
// Check if login was successful
if (isset($_SESSION['login_success']) && $_SESSION['login_success'] === true) {
    // Unset the session variable after using it
    unset($_SESSION['login_success']);
    echo "
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'success',
            title: 'Login Successful',
            text: 'Welcome back, " . htmlspecialchars($fname) . "!',
            confirmButtonText: 'OK'
        });
    });
    </script>";
}
?>
    <!-- SIDEBAR -->
    <section id="sidebar">
        
        <?php include 'navbar.php'; ?>
    </section>
    <!-- SIDEBAR -->

    <!-- NAVBAR -->
    <section id="content">
        <?php include 'topbar.php'; ?>
        <!-- NAVBAR -->

       <!-- MAIN -->
    <main>
        <div style="display: flex; align-items: center; margin-bottom: 30px;">
            <h2 class="title" style="font-weight: bold; color: #9b2035; margin: 0;">
                <?php echo $greeting; ?>, <?php echo htmlspecialchars($fname); ?>!
                <img src="img/EYYY.gif" alt="Animated GIF" style="height: 30px; vertical-align: middle;">
            </h2>
        </div>
        <div class="filter">
                <label>School Year:</label>
                <select id="yearRangeFilter" class="form-control" style="width: 200px;">
                    <option value="">All Year Ranges</option>
                <?php while ($year = $yearRanges->fetch_assoc()): ?>
                    <option value="<?= $year['Year_Range'] ?>" <?= ($selectedYearRange == $year['Year_Range']) ? 'selected' : '' ?>>
                        <?= $year['Year_Range'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="info-data" style= "margin-top: 10px;">
            <div class="row">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="card" style="height:230px;">
                                                <div class="card-body">
                                                    <!-- Top Row: Content -->
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <p style="font-weight: bold;margin-top:-20px; font-size: 20px;">Users</p>
                                                            <h1 style="font-weight:bold; margin-top: -5px; margin-bottom: 25px;"><?php echo $totalUsers; ?></h1>
                                                        </div>
                                                        <div class="col-6 text-end">
                                                            <i class="fa-solid fa-users icon1"></i>
                                                        </div>
                                                    </div>

                                                    <!-- Bottom Row: Empty Space -->
                                                   <div class="row">
                                                        <div class="col-12">
                                                            <div class="row">
                                                                <!-- Department Head Count -->
                                                                <div class="col-6 text-left">
                                                                    <h4 style="font-weight: bold;"><?php echo $departmentHeadCount; ?></h4>
                                                                    <p style="font-weight: bold; font-size: 13px; margin-top: 10px;">Dept Head</p>
                                                                </div>

                                                                <!-- Teacher Count -->
                                                                <div class="col-6 text-left">
                                                                    <h4 style="font-weight: bold;"><?php echo $teacherCount; ?></h4>
                                                                    <p style="font-weight: bold; font-size: 13px; margin-top: 10px;">Teacher</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card" style="height:230px;">
                                                <div class="card-body">
                                                    <!-- Top Row: Content -->
                                                    <div class="row">
                                                        <div class="col-10">
                                                            <p style="font-weight: bold; margin-top:-20px; font-size: 20px;">Departments</p>
                                                            <h1 style="font-weight:bold; margin-top: -5px; margin-bottom: 25px;"><?php echo $totalDepartment; ?></h1>
                                                        </div>
                                                        <div class="col-2 text-end">
                                                            <i class="fa-solid fa-building icon1"></i>
                                                        </div>
                                                    </div>

                                                    <!-- Bottom Row: Empty Space -->
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="row">
                                                                <!-- Academic Department Count -->
                                                                <div class="col-6 text-left">
                                                                    <h4 style="font-weight: bold;"><?php echo $academicCount; ?></h4>
                                                                    <p style="font-weight: bold; font-size: 13px; margin-top: 10px;">Academic</p>
                                                                </div>

                                                                <!-- Administrative Department Count -->
                                                                <div class="col-6 text-left">
                                                                    <h4 style="font-weight: bold;"><?php echo $administrativeCount; ?></h4>
                                                                    <p style="font-weight: bold; font-size: 13px; margin-top: 10px;">Administrative</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="card" style="height:260px;">
                                                <h4>MPS Performance Trend</h4>
                                                <canvas id="mpsTrendChart" style="height: 200px;"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div id="chartCarousel" class="carousel slide container" data-ride="carousel">
                                <h4 class="">School Performance</h4>
                                <!-- Carousel controls on top -->
                                <div class="carousel-controls">
                                    <a href="view_charts.php" class="circle-button">
                                        <i class="fa-solid fa-eye" style="color:white;text-decoration:none;"></i>
                                    </a>
                                    <a class="carousel-control-prev" href="#chartCarousel" role="button" data-slide="prev">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                    <a class="carousel-control-next" href="#chartCarousel" role="button" data-slide="next">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </div>

                                <div class="carousel-inner">
                                    <!-- Chart 1 -->
                                    <div class="carousel-item active">                                 
                                        <h6>ENROLLMENT (Total Enrollment)</h6>
                                        <canvas id="enrollmentChart"></canvas>                                   
                                    </div>
                                    <!-- Chart 2 -->
                                    <div class="carousel-item">                                     
                                        <h6>DROP OUT RATE (%)</h6>
                                        <canvas id="dropoutChart"></canvas>                                      
                                    </div>
                                    <!-- Chart 3 -->
                                    <div class="carousel-item">                                       
                                        <h6>PROMOTION RATE (%)</h6>
                                        <canvas id="promotionChart"></canvas>                                       
                                    </div>
                                    <!-- Chart 4 -->
                                    <div class="carousel-item">                                      
                                        <h6>COHORT SURVIVAL RATE (%)</h6>
                                        <canvas id="cohortChart"></canvas>                                       
                                    </div>
                                    <!-- Chart 5 -->
                                    <div class="carousel-item">                                     
                                        <h6>REPETITION RATE (%)</h6>
                                        <canvas id="repetitionChart"></canvas>                                       
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                

                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="card" style="height: 500px; overflow-y: auto; position: relative;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="Title">Task Submissions / Department</h5>
                                    <!-- Search bar -->
                                    <input type="text" id="search-input" class="form-control" placeholder="Search..." style="width: 250px;">
                                </div>
                                <table class="table table-bordered table-striped" style="margin-top: 15px;">
                                    <thead>
                                        <tr>
                                            <th>Department</th>
                                            <th>Department Head</th>
                                            <th>Tasks Completion</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="department-table-body">
                                        <!-- Data will be inserted here via AJAX -->
                                    </tbody>
                                </table>
                            </div>        
                        </div>
                        <div class="col-md-7">
                            <div class="card" style="height: 97%; padding: 15px;">
                                <div class="head">
                                    <h5 style="margin-bottom: 15px;">Productivity by Month</h5>
                                </div>
                                <!-- Canvas container -->
                                <div style="width: 100%; height: 88%; display: flex; justify-content: center; align-items: center;">
                                    <canvas id="productivityChart" style="width: 90%; height: 100%;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>        
                </div>
            </div>
        </div>
    </main>
    <!-- MAIN -->
    </section>

    <!-- NAVBAR -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Store chart instances
            const charts = {
                enrollment: null,
                dropout: null,
                promotion: null,
                cohort: null,
                repetition: null,
                mpsTrend: null,
                productivity: null
            };
            
            // Initialize all charts
            function initializeCharts() {
                const data = <?php echo json_encode($data); ?>;
                const mpsData = <?php echo json_encode($mpsData); ?>;
                const productivityData = <?php echo json_encode($productivityData ?? []); ?>;
                
                // Performance charts
                createPerformanceCharts(data);
                
                // MPS Trend Chart
                createMpsTrendChart(mpsData);
                
                // Productivity Chart
                createProductivityChart(productivityData);
            }
            
            // Create performance charts
            function createPerformanceCharts(data) {
                const labels = data.map((item) => `${item.Year_Range}`);
                
                charts.enrollment = createChart("enrollmentChart", "Total Enrollment", labels, 
                    data.map((item) => item.total_enrollment));
                charts.dropout = createChart("dropoutChart", "Dropout Rate (%)", labels, 
                    data.map((item) => item.avg_dropout_rate));
                charts.promotion = createChart("promotionChart", "Promotion Rate (%)", labels, 
                    data.map((item) => item.avg_promotion_rate));
                charts.cohort = createChart("cohortChart", "Cohort Survival Rate (%)", labels, 
                    data.map((item) => item.avg_cohort_rate));
                charts.repetition = createChart("repetitionChart", "Repetition Rate (%)", labels, 
                    data.map((item) => item.avg_repetition_rate));
            }
            
            // Create MPS trend chart
            function createMpsTrendChart(mpsData) {
                const labels = mpsData.map(item => item.Quarter_Name);
                const yearRanges = mpsData.map(item => item.Year_Range);
                const avgMps = mpsData.map(item => item.avg_mps);
                const below75Percent = mpsData.map(item => (item.below_75_count / item.total_count) * 100 || 0);

                const ctx = document.getElementById('mpsTrendChart').getContext('2d');
                charts.mpsTrend = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Average MPS %',
                                data: avgMps,
                                borderColor: '#9B2035',
                                backgroundColor: 'rgba(155, 32, 53, 0.1)',
                                tension: 0.3,
                                fill: true
                            },
                            {
                                label: 'Below 75% %',
                                data: below75Percent,
                                borderColor: '#FF5733',
                                backgroundColor: 'rgba(255, 87, 51, 0.1)',
                                borderDash: [5, 5],
                                tension: 0.3
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: false,
                                min: 50,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    beforeTitle: function(context) {
                                        const dataIndex = context[0].dataIndex;
                                        return 'Year: ' + yearRanges[dataIndex];
                                    },
                                    label: function(context) {
                                        return context.dataset.label + ': ' + context.raw.toFixed(1) + '%';
                                    }
                                }
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
            
            // Create productivity chart
            function createProductivityChart(data) {
                const labels = data.map(item => item.Month);
                const submittedTasks = data.map(item => parseInt(item.SubmittedTasks));
                const approvedTasks = data.map(item => parseInt(item.ApprovedTasks));

                const ctx = document.getElementById('productivityChart').getContext('2d');
                charts.productivity = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Submitted Tasks',
                                data: submittedTasks,
                                borderColor: 'blue',
                                backgroundColor: 'rgba(66, 133, 244, 0.2)',
                                fill: true,
                                tension: 0.4
                            },
                            {
                                label: 'Approved Tasks',
                                data: approvedTasks,
                                borderColor: 'green',
                                backgroundColor: 'rgba(52, 168, 83, 0.2)',
                                fill: true,
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                title: { display: true, text: 'Month' }
                            },
                            y: {
                                title: { display: true, text: 'Tasks' },
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
            
            // Generic chart creation function
            function createChart(canvasId, label, labels, data) {
                const ctx = document.getElementById(canvasId).getContext("2d");
                return new Chart(ctx, {
                    type: "bar",
                    data: {
                        labels: labels,
                        datasets: [{
                            label: label,
                            data: data,
                            backgroundColor: "rgba(155, 32, 53, 0.2)",
                            borderColor: "rgba(155, 32, 53, 1)",
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
            
            // Update charts with filtered data
            function updateChartsWithFilter(yearRange) {
                Swal.fire({
                    title: 'Loading data...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
                
                fetch(`fetch_dashboard_data.php?year_range=${yearRange || ''}`)
                    .then(response => response.json())
                    .then(data => {
                        updatePerformanceCharts(data.performanceData);
                        updateMpsTrendChart(data.mpsData);
                        updateProductivityChart(data.productivityData);
                        Swal.close();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'Failed to load data', 'error');
                    });
            }
            
            // Update performance charts
            function updatePerformanceCharts(data) {
                const labels = data.map((item) => `${item.Year_Range}`);
                
                if (charts.enrollment) {
                    charts.enrollment.data.labels = labels;
                    charts.enrollment.data.datasets[0].data = data.map((item) => item.total_enrollment);
                    charts.enrollment.update();
                }
                
                if (charts.dropout) {
                    charts.dropout.data.labels = labels;
                    charts.dropout.data.datasets[0].data = data.map((item) => item.avg_dropout_rate);
                    charts.dropout.update();
                }
                
                if (charts.promotion) {
                    charts.promotion.data.labels = labels;
                    charts.promotion.data.datasets[0].data = data.map((item) => item.avg_promotion_rate);
                    charts.promotion.update();
                }
                
                if (charts.cohort) {
                    charts.cohort.data.labels = labels;
                    charts.cohort.data.datasets[0].data = data.map((item) => item.avg_cohort_rate);
                    charts.cohort.update();
                }
                
                if (charts.repetition) {
                    charts.repetition.data.labels = labels;
                    charts.repetition.data.datasets[0].data = data.map((item) => item.avg_repetition_rate);
                    charts.repetition.update();
                }
            }
            
            // Update MPS trend chart
            function updateMpsTrendChart(mpsData) {
                if (!charts.mpsTrend) return;
                
                const labels = mpsData.map(item => item.Quarter_Name);
                const avgMps = mpsData.map(item => item.avg_mps);
                const below75Percent = mpsData.map(item => (item.below_75_count / item.total_count) * 100 || 0);
                
                charts.mpsTrend.data.labels = labels;
                charts.mpsTrend.data.datasets[0].data = avgMps;
                charts.mpsTrend.data.datasets[1].data = below75Percent;
                charts.mpsTrend.update();
            }
            
            // Update productivity chart
            function updateProductivityChart(productivityData) {
                if (!charts.productivity) return;
                
                const labels = productivityData.map(item => item.Month);
                const submittedTasks = productivityData.map(item => parseInt(item.SubmittedTasks));
                const approvedTasks = productivityData.map(item => parseInt(item.ApprovedTasks));
                
                charts.productivity.data.labels = labels;
                charts.productivity.data.datasets[0].data = submittedTasks;
                charts.productivity.data.datasets[1].data = approvedTasks;
                charts.productivity.update();
            }
            
            // Initialize charts on page load
            initializeCharts();
            
            // Handle year range filter changes
            document.getElementById('yearRangeFilter').addEventListener('change', (e) => {
                const selectedYearRange = e.target.value;
                history.pushState({}, '', `?year_range=${selectedYearRange || ''}`);
                updateChartsWithFilter(selectedYearRange);
            });
            
            // Other existing functions (for user cards, etc.)
            $(document).ready(function() {
                // Overall user count and role-specific counts
                const totalUsers = <?php echo $totalUsers; ?>;
                const deptHeadCount = <?php echo $departmentHeadCount; ?>;
                const teacherCount = <?php echo $teacherCount; ?>;
                const totalDepartment = <?php echo $totalDepartment; ?>;
                const academicCount = <?php echo $academicCount; ?>;
                const administrativeCount = <?php echo $administrativeCount; ?>;

                // Validate totalUsers to prevent division by zero
                const deptHeadPercentage = totalUsers > 0 ? (deptHeadCount / totalUsers) * 100 : 0;
                const teacherPercentage = totalUsers > 0 ? (teacherCount / totalUsers) * 100 : 0;
                
                // Validate totalDepartment to prevent division by zero
                const academicPercentage = totalDepartment > 0 ? (academicCount / totalDepartment) * 100 : 0;
                const administrativePercentage = totalDepartment > 0 ? (administrativeCount / totalDepartment) * 100 : 0;

                

               

            
            });
            
            // Search functionality
            document.getElementById('search-input').addEventListener('keyup', function() {
                var input = this.value.toLowerCase();
                var tableRows = document.querySelectorAll('#department-table-body tr');

                tableRows.forEach(function(row) {
                    var rowText = row.textContent.toLowerCase();
                    row.style.display = rowText.includes(input) ? '' : 'none';
                });
            });
            
            // Task submission table
            $(document).ready(function() {
                $.ajax({
                    url: 'fetch_tasks.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        let tableBody = '';
                        
                        $.each(response, function(index, dept) {
                            let tasks = dept.totalSubmit + " / " + dept.totalAssigned;
                            let userRows = dept.users.map(user => `
                                <div class="user-row">
                                    <img src="../img/UserProfile/${user.profile}" alt="${user.fullname}" class="profile-image" />
                                    ${user.fullname}
                                </div>
                            `).join('');

                            tableBody += `
                                <tr>
                                    <td>${dept.dept_name}</td>
                                    <td>${userRows}</td>
                                    <td>${tasks}</td>
                                    <td>
                                        <div class="chart-container">
                                            <canvas id="trendChart${index}" width="40" height="40"></canvas>
                                        </div>
                                    </td>
                                </tr>`;
                        });

                        $('#department-table-body').html(tableBody);

                        // Initialize charts after the table body is populated
                        $.each(response, function(index, dept) {
                            let ctx = document.getElementById(`trendChart${index}`).getContext('2d');
                            new Chart(ctx, {
                                type: 'pie',
                                data: {
                                    labels: ['Submitted', 'Assigned'],
                                    datasets: [{
                                        data: [dept.totalSubmit, dept.totalAssigned],
                                        backgroundColor: ['rgba(75, 192, 192, 0.6)', 'rgba(255, 99, 132, 0.6)'],
                                        borderColor: ['rgba(75, 192, 192, 1)', 'rgba(255, 99, 132, 1)'],
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: { enabled: false }
                                    },
                                    elements: { arc: { borderWidth: 1 } }
                                }
                            });
                        });
                    },
                    error: function(error) {
                        console.log("Error fetching data", error);
                    }
                });
            });
        });
    </script>
</body>
</html>
