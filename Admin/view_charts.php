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
$schoolYears = $conn->query("SELECT DISTINCT Year_Range FROM SchoolYear ORDER BY Year_Range");

// Default filter value
$selectedYear = $_GET['school_year'] ?? null;

// Base SQL query with optional filter
$sql = "
    SELECT sy.Year_Range, 
           SUM(e.Enroll_Gross) AS total_enrollment, 
           AVG(d.Dropout_Rate) AS avg_dropout_rate, 
           AVG(p.Promotion_Rate) AS avg_promotion_rate, 
           SUM(c.Cohort_Rate) AS total_cohort_rate, 
           AVG(r.Repeaters_Rate) AS avg_repetition_rate, 
           AVG(t.Transition_Rate) AS avg_transition_rate
    FROM Performance_Indicator pi
    JOIN Grade g ON pi.Grade_ID = g.Grade_ID
    JOIN Enroll e ON pi.Enroll_ID = e.Enroll_ID
    JOIN Dropout d ON pi.Dropout_ID = d.Dropout_ID
    JOIN Promotion p ON pi.Promotion_ID = p.Promotion_ID
    JOIN Cohort_Survival c ON pi.Cohort_ID = c.Cohort_ID
    JOIN Repetition r ON pi.Repetition_ID = r.Repetition_ID
    JOIN Transition t ON pi.Transition_ID = t.Transition_ID
    JOIN SchoolYear sy ON pi.School_Year_ID = sy.School_Year_ID
    WHERE 1=1";

// Add filter
if ($selectedYear) {
    $sql .= " AND sy.Year_Range = '$selectedYear'";
}

$sql .= " GROUP BY sy.Year_Range";
$result = $conn->query($sql);

// Fetch the data
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

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
    <title>Dashboard | Home</title>
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
        .card {
            background-color: #9B2035;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            flex: 1;
            min-width: 200px;
            box-sizing: border-box;
            height: 290%;
            margin-top: -50px;
        }
        .card .head {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card .head h2, .card .head p {
            margin: 0;
        }
        .card .head i.icon {
            font-size: 40px;
            color: #9B2035;
        }

        @keyframes animate {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(-100%);
            }
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
        
        /* Responsive Charts */
        .chart-container {
            width: 100%;
            overflow: hidden;
        }
        .chart-item {
            position: relative;
            width: 100%;
            padding-bottom: 60%; /* Aspect ratio for charts */
            margin-bottom: 20px;
        }
        .chart-item canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100% !important;
            height: 100% !important;
            background: #ffffff;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 10px;
        }
        
        .charts {
            margin-top: 30px;
        }
        .filter-container {
            background-color: #ffff;
            padding: 10px; 
            box-shadow: 4px 4px 30px rgba(0, 0, 0, 0.05); 
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .filter-container label {
            color: #000;
            font-weight: bold;
        }
        
        /* Print button styling */
        .print-btn {
            margin-left: auto;
            margin-right: 20px;
            font-size: 20px;
            color: #9b2035;
            background: none;
            border: none;
            cursor: pointer;
        }
        .print-btn:hover {
            color: #7a1a2c;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .chart-item {
                padding-bottom: 80%; /* Taller aspect ratio for medium screens */
            }
            .filter-container {
                margin-bottom: 15px;
            }
        }
        
        @media (max-width: 768px) {
            .title {
                flex-direction: column;
                align-items: flex-start;
            }
            .print-btn {
                margin: 15px 0 0 0;
                align-self: flex-end;
            }
            .chart-item {
                padding-bottom: 100%; /* Square aspect ratio for small screens */
            }
            .col-md-6 {
                margin-bottom: 30px;
            }
        }
        
        @media (max-width: 576px) {
            .chart-item {
                padding-bottom: 120%; /* Taller aspect ratio for extra small screens */
            }
            .filter-container {
                padding: 15px;
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
        <main id="printableContent"> <h1 class="title" style="display: flex; align-items: center;">
                School Performance
                <a href="#" onclick="printPage();" style="margin-left: auto; margin-right: 20px; font-size: 20px; color: #9b2035;">
                    <i class="fas fa-print"></i> Print All
                </a>
            </h1>
            <div class="info-data">
                <div class="row">
 
                    <!-- Filter Section -->
                    <div class="col-md-6">
                        <div class="filter-container">
                            <form method="GET" action="">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label for="filterYear">Select School Year:</label>
                                        <select id="filterYear" name="school_year" class="form-control">
                                            <option value="">All Years</option>
                                            <?php while ($row = $schoolYears->fetch_assoc()) : ?>
                                                <option value="<?php echo $row['Year_Range']; ?>" 
                                                    <?php if ($row['Year_Range'] == $selectedYear) echo 'selected'; ?>>
                                                    <?php echo $row['Year_Range']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Chart Section -->
                    <div class="col-md-12" style="margin-top: 20px;">
                        <div class="chart-container">
                            <!-- Row 1: Two charts -->
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Enrollment Rate</h6>
                                    <div class="chart-item">
                                        <canvas id="enrollmentChart"></canvas>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6>Dropout Rate</h6>
                                    <div class="chart-item">
                                        <canvas id="dropoutChart"></canvas>
                                    </div>
                                </div>    
                            </div>

                            <!-- Row 2: Two more charts -->
                            <div class="row" style="margin-top: 20px;">
                                <div class="col-md-6">
                                    <h6>Cohort-Survival Rate</h6>
                                    <div class="chart-item">
                                        <canvas id="cohortChart"></canvas>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6>Repetition Rate</h6>
                                    <div class="chart-item">
                                        <canvas id="repetitionChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Row 3: One chart -->
                            <div class="row" style="margin-top: 20px;">
                                <div class="col-md-6">
                                    <h6>Promotion Rate</h6>
                                    <div class="chart-item">
                                        <canvas id="promotionChart"></canvas>
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
        function printPage() {
            // Create a new window for printing
            const printWindow = window.open('', '_blank');
            
            // Get all canvas elements and their titles
            const charts = document.querySelectorAll('canvas');
            const chartTitles = document.querySelectorAll('.col-md-6 h6');
            
            // Fetch school details via AJAX
            $.ajax({
                url: 'getSchoolDetails.php',
                method: 'GET',
                success: function(data) {
                    // Function to generate header HTML
                    function getHeaderHTML() {
                        return `
                            <div class="header-container">
                                <div style="text-align: center; margin-bottom: 15px;">
                                    <img src="img/Logo/deped_logo.png" alt="DepEd Logo" style="width: 90px; height: auto;" />
                                </div>
                                <div class="school-details">
                                    <p style='font-family: "Old English Text MT", serif; font-weight:bold; font-size:20px; margin: 0;'>Republic of the ${data.Country || 'Philippines'}</p>
                                    <p style='font-family: "Old English Text MT", serif;font-weight:bold; font-size:28px; margin: 4px 0 2px 0;'>${data.Organization || 'Department of Education'}</p>
                                    <p style="text-transform: uppercase; font-family: 'Tahoma'; font-size: 16px; margin: 1px;">Region ${data.Region || 'IV-A'}</p>
                                    <p style="text-transform: uppercase; font-family: 'Tahoma'; font-size: 16px; margin: 1px;">Schools Division of Batangas</p>
                                    <p style="text-transform: uppercase; font-family: 'Tahoma'; font-size: 16px; margin: 1px;">${data.Name || 'School Name'}</p>
                                    <p style="text-transform: uppercase;  font-family: 'Tahoma'; font-size: 16px; margin: 1px;">${data.Address}, ${data.City_Muni}</p>
                                </div>
                            </div>
                            <hr style="max-width:100%; margin: 0 auto; border: 1px solid black;">
                            <div class="report-title">
                                <h2>Performance Indicator</h2>
                                ${<?php echo $selectedYear ? "'<p class=\"school-year\">School Year: " . htmlspecialchars($selectedYear) . "</p>'" : "''" ?>}
                            </div>
                        `;
                    }

                    // Start building the HTML content
                    let htmlContent = `
                        <html>
                            <head>
                                <title>School Performance Indicator Report</title>
                                <style>
                                    body { 
                                        font-family: 'Times New Roman', serif; 
                                        padding: 20px; 
                                        position: relative;
                                        min-height: 100vh;
                                        padding-bottom: 100px;
                                    }
                                    .page {
                                        page-break-after: always;
                                        padding-bottom: 50px;
                                    }
                                    .page:last-child {
                                        page-break-after: auto;
                                    }
                                    .chart-container {
                                        width: 100%;
                                        margin-bottom: 30px;
                                        page-break-inside: avoid;
                                    }
                                    .print-chart { 
                                        width: 100%;
                                        max-width: 800px;
                                        margin: 0 auto;
                                    }
                                    .print-chart img { 
                                        max-width: 100%; 
                                        height: auto;
                                        display: block;
                                        margin: 0 auto;
                                    }
                                    .chart-title {
                                        text-align: center;
                                        font-size: 16px;
                                        margin-bottom: 10px;
                                        font-weight: bold;
                                    }
                                    h1 { 
                                        color: #9B2035; 
                                        text-align: center; 
                                    }
                                    h2 { 
                                        color: #333; 
                                        margin-top: 20px;
                                        text-align: center;
                                    }
                                    @page { 
                                        size: Legal portrait; 
                                        margin: 15mm; 
                                    }
                                    .header-container {
                                        margin-bottom: 20px;
                                    }
                        
                                    .school-details { 
                                        text-align: center; 
                                    }
                                    .footer {
                                        position: fixed;
                                        bottom: 0;
                                        left: 0;
                                        right: 0;
                                        width: 100%;
                                        background-color: white;
                                        padding: 5px 0;
                                        border-top: 2.5px solid #000;
                                        margin-bottom: 15px;
                                    }

                                    hr {
                                            border-top: 1px solid black;
                                            width: 100%;
                                            margin: 10px auto;
                                        }

                                    .footer-logo {
                                        display: flex;
                                        align-items: center;
                                        margin-right: 20px;
                                    }

                                    .footer-details {
                                        text-align: left;
                                        font-size: 1rem;
                                        line-height: 0.9;
                                    }

                                    .footer-underline {
                                        color: blue;
                                        text-decoration: underline;
                                    }
                                    .report-title {
                                        text-align: center;
                                        margin-top: -10px;
                                    }
                                    .school-year {
                                        text-align: center;
                                        margin-bottom: 20px;
                                    }
                                    .page-number {
                                        position: fixed;
                                        bottom: 15px;
                                        right: 15px;
                                        font-size: 10px;
                                    }
                                </style>
                            </head>
                            <body>
                                <div class="page">
                                    ${getHeaderHTML()}
                                    <div class="charts-container">
                    `;
                    
                    // Add each chart to the content
                    charts.forEach((chart, index) => {
                        // Convert canvas to image
                        const imageData = chart.toDataURL('image/png');
                        
                        htmlContent += `
                            <div class="chart-container">
                                <div class="print-chart">
                                    <div class="chart-title">${chartTitles[index]?.textContent || 'Chart ' + (index + 1)}</div>
                                    <img src="${imageData}">
                                </div>
                            </div>
                        `;
                        
                        // Start a new page after every 2 charts (except last one)
                        if ((index + 1) % 2 === 0 && index < charts.length - 1) {
                            htmlContent += `
                                </div>
                                <div class="page-number">Page ${Math.floor((index + 1)/2) + 1}</div>
                                </div>
                                <div class="page">
                                    ${getHeaderHTML()}
                                    <div class="charts-container">
                            `;
                        }
                    });
                
                    
                    // Add footer with details beside logo
                    htmlContent += `
                            <div class="footer">
                                <div style="display: flex; align-items: center; max-width: 100%; padding: 0 20px;">
                                    <div class="footer-logo">
                                        <img src="  img/Logo/DEPED_MATATAGLOGO.PNG" style="width: 170px; height: auto; margin-right: 10px;" />
                                        ${data.Logo ? `<img src="../img/Logo/${data.Logo}" alt="School Logo" style="width: 80px; height: auto;" />` : ''}
                                    </div>
                                    <div class="footer-details">
                                        <p style="margin-bottom: -5px;">${data.Address || ''} ${data.City_Muni || ''} ${data.School_ID ? 'School ID: ' + data.School_ID : ''}</p>
                                        <p style="margin-bottom: -5px;">${data.Mobile_No ? 'Contact nos.: ' + data.Mobile_No : ''} ${data.landline ? ' Landline: ' + data.landline : ''}</p>
                                        <p class="footer-underline">${data.Email || ''}</p>
                                    </div>
                                </div>
                            </div>
                        </body>
                    </html>
                    `;
                    
                    // Write the content to the new window
                    printWindow.document.open();
                    printWindow.document.write(htmlContent);
                    printWindow.document.close();
                    
                    // Wait for images to load before printing
                    printWindow.onload = function() {
                        setTimeout(() => {
                            printWindow.print();
                            printWindow.close();
                        }, 500);
                    };
                },
                error: function() {
                    // Fallback if AJAX fails
                    alert('Failed to load school details. Printing without header information.');
                    printWithoutSchoolDetails();
                }
            });
        }

        // Fallback print function if AJAX fails
        function printWithoutSchoolDetails() {
            const printWindow = window.open('', '_blank');
            const charts = document.querySelectorAll('canvas');
            const chartTitles = document.querySelectorAll('.col-md-6 h6');
            
            let htmlContent = `
                <html>
                    <head>
                        <title>School Performance Indicator Report</title>
                        <style>
                            body { 
                                font-family: 'Times New Roman', serif; 
                                padding: 20px; 
                                position: relative;
                                min-height: 100vh;
                                padding-bottom: 100px;
                            }
                            .page {
                                page-break-after: always;
                                padding-bottom: 50px;
                            }
                            .page:last-child {
                                page-break-after: auto;
                            }
                            .chart-container {
                                width: 100%;
                                margin-bottom: 30px;
                                page-break-inside: avoid;
                            }
                            .print-chart { 
                                width: 100%;
                                max-width: 800px;
                                margin: 0 auto;
                            }
                            .print-chart img { 
                                max-width: 100%; 
                                height: auto;
                                display: block;
                                margin: 0 auto;
                            }
                            .chart-title {
                                text-align: center;
                                font-size: 16px;
                                margin-bottom: 10px;
                                font-weight: bold;
                            }
                            h1 { 
                                color: #9B2035; 
                                text-align: center; 
                            }
                            h2 { 
                                color: #333; 
                                margin-top: 20px;
                                text-align: center;
                            }
                            @page { 
                                size: A4 portrait; 
                                margin: 15mm; 
                            }
                            .header-container {
                                display: flex;
                                flex-direction: column;
                                align-items: center;
                                margin-bottom: 20px;
                            }
                            .logo-top {
                                margin-bottom: 10px;
                            }
                            .footer {
                                position: fixed;
                                bottom: 0;
                                width: 100%;
                                background-color: white;
                                margin-top: 30px;
                                padding: 15px 20px;
                                border-top: 2.5px solid #000;
                            }

                            .footer-logo {
                                display: flex;
                                align-items: center;
                                margin-right: 20px;
                            }

                            .footer-details {
                                text-align: left;
                                font-size: 1.5rem;
                                line-height: 1.3;
                            }

                            .footer-underline {
                                color: blue;
                                text-decoration: underline;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="page">
                            <div class="header-container">
                                <div class="logo-top">
                                    <img src="../img/Logo/deped_logo.png" alt="DepEd Logo" style="width: 90px; height: auto;" />
                                </div>
                                <h1>School Performance Report</h1>
                            </div>
                            <div class="charts-container">
            `;
            
            charts.forEach((chart, index) => {
                const imageData = chart.toDataURL('image/png');
                
                htmlContent += `
                    <div class="chart-container">
                        <div class="print-chart">
                            <div class="chart-title">${chartTitles[index]?.textContent || 'Chart ' + (index + 1)}</div>
                            <img src="${imageData}">
                        </div>
                    </div>
                `;
                
                if ((index + 1) % 2 === 0 && index < charts.length - 1) {
                    htmlContent += `
                        </div>
                        <div class="page-number">Page ${Math.floor((index + 1)/2) + 1}</div>
                        </div>
                        <div class="page">
                            <div class="header-container">
                                <div class="logo-top">
                                    <img src="../img/Logo/deped_logo.png" alt="DepEd Logo" style="width: 90px; height: auto;" />
                                </div>
                                <h1>School Performance Report</h1>
                            </div>
                            <div class="charts-container">
                    `;
                }
            });
            
            htmlContent += `
                    </div>
                    <div class="page-number">Page ${Math.ceil(charts.length/2)}</div>
                </div>
                <div class="footer">
                    <div class="footer-logo">
                        <!-- No logo in fallback version -->
                    </div>
                    <div class="footer-details">
                        <p><strong>School Performance Indicator System</strong></p>
                        <p>Printed on ${new Date().toLocaleDateString()}</p>
                    </div>
                </div>
            </body>
        </html>
        `;
            
            printWindow.document.open();
            printWindow.document.write(htmlContent);
            printWindow.document.close();
            
            printWindow.onload = function() {
                setTimeout(() => {
                    printWindow.print();
                    printWindow.close();
                }, 500);
            };
        }
    </script>  

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const yearDropdown = document.querySelector('select[name="school_year"]');

            yearDropdown.addEventListener("change", () => {
                const schoolYear = yearDropdown.value;
                const url = new URL(window.location.href);

                if (schoolYear) {
                    url.searchParams.set("school_year", schoolYear);
                } else {
                    url.searchParams.delete("school_year");
                }

                window.location.href = url.toString();
            });

            const data = <?php echo json_encode($data); ?>;
            const labels = data.map((item) => `${item.Year_Range}`);
            const enrollmentData = data.map((item) => item.total_enrollment);
            const dropoutData = data.map((item) => item.avg_dropout_rate);
            const promotionData = data.map((item) => item.avg_promotion_rate);
            const cohortData = data.map((item) => item.total_cohort_rate);
            const repetitionData = data.map((item) => item.avg_repetition_rate);

            function createChart(canvasId, label, labels, data) {
                const ctx = document.getElementById(canvasId).getContext("2d");
                new Chart(ctx, {
                    type: "bar",
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: label,
                                data: data,
                                backgroundColor: "rgba(155, 32, 53, 1)", // Solid color
                                borderColor: "rgba(155, 32, 53, 1)",
                                borderWidth: 1,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: 'black' // Makes y-axis labels black
                                }
                            },
                            x: {
                                ticks: {
                                    color: 'black' // Makes x-axis labels black
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                labels: {
                                    color: 'black',
                                    font: {
                                        size: 12
                                    }
                                }
                            }
                        },
                        // 3D effect configuration
                        animation: {
                            onComplete: function() {
                                var ctx = this.ctx;
                                ctx.shadowColor = 'rgba(0, 0, 0, 0.5)';
                               
                            }
                        },
                        elements: {
                            bar: {
                                borderRadius: 5, // Slightly rounded corners for 3D effect
                                borderSkipped: 'bottom', // Keep bottom edge sharp
                            }
                        }
                    },
                });
            }

            createChart("enrollmentChart", "Total Enrollment", labels, enrollmentData);
            createChart("dropoutChart", "Dropout Rate (%)", labels, dropoutData);
            createChart("promotionChart", "Promotion Rate (%)", labels, promotionData);
            createChart("cohortChart", "Cohort Survival Rate (%)", labels, cohortData);
            createChart("repetitionChart", "Repetition Rate (%)", labels, repetitionData);
        });
    </script>
</body>
</html>
