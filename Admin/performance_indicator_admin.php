<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

$userId = $_SESSION['user_id'];


// Fetch available school years
$schoolYearsQuery = "
    SELECT DISTINCT sy.School_Year_ID, sy.Year_Range
    FROM schoolyear sy
    JOIN performance_indicator pi ON sy.School_Year_ID = pi.School_Year_ID
    ORDER BY sy.Year_Range DESC";
$schoolYearsResult = $conn->query($schoolYearsQuery);
$schoolYears = [];
if ($schoolYearsResult && $schoolYearsResult->num_rows > 0) {
    while ($row = $schoolYearsResult->fetch_assoc()) {
        $schoolYears[$row['School_Year_ID']] = $row['Year_Range'];
    }
}


// Fetch preparer information for all performance indicators
$preparersByYear = [];
$allPreparers = []; // Store all preparers for "All" years view
$preparersQuery = "
    SELECT
        pi.Performance_ID,   
        sy.School_Year_ID,
        sy.Year_Range,
        CONCAT(u.fname, ' ', u.mname, ' ', u.lname) AS Preparer_Name,
        u.URank AS Preparer_Rank,
        u.esig AS Teacher_Signature
    FROM 
        performance_indicator pi
    JOIN 
        useracc u ON pi.UserID = u.UserID
    JOIN
        schoolyear sy ON pi.School_Year_ID = sy.School_Year_ID
    ORDER BY
        sy.Year_Range DESC";
$preparersResult = $conn->query($preparersQuery);

if ($preparersResult && $preparersResult->num_rows > 0) {
    while ($row = $preparersResult->fetch_assoc()) {
        $preparersByYear[$row['School_Year_ID']] = [
            'name' => $row['Preparer_Name'],
            'rank' => $row['Preparer_Rank'],
            'year_range' => $row['Year_Range'],
            'esig' => $row['Teacher_Signature']
        ];
        $allPreparers[] = [
            'name' => $row['Preparer_Name'],
            'rank' => $row['Preparer_Rank'],
            'year_range' => $row['Year_Range'],
            'esig' => $row['Teacher_Signature']
        ];
    }
}


    // Fetch records based on the selected school year
    $selectedYear = isset($_GET['year']) && ctype_digit($_GET['year']) ? $_GET['year'] : "All";
    $records = [];
    if ($selectedYear === "All") {
        foreach ($schoolYears as $yearId => $year) {
            $query = "
                SELECT 
                    sy.Year_Range,
                    g.Grade_Level,
                    e.Enroll_Gross,
                    e.Enroll_Net,
                    c.Cohort_Figure,
                    c.Cohort_Rate,
                    d.Dropout_Figure,
                    d.Dropout_Rate,
                    r.Repeaters_Figure,
                    r.Repeaters_Rate,
                    p.Promotion_Figure,
                    p.Promotion_Rate,
                    t.Transition_Figure,
                    t.Transition_Rate,
                    a.Age_Group_12To15,
                    n.NAT_Score
                FROM performance_indicator pi
                JOIN schoolyear sy ON pi.School_Year_ID = sy.School_Year_ID
                JOIN grade g ON pi.Grade_ID = g.Grade_ID
                JOIN enroll e ON pi.Enroll_ID = e.Enroll_ID
                JOIN dropout d ON pi.Dropout_ID = d.Dropout_ID
                JOIN promotion p ON pi.Promotion_ID = p.Promotion_ID
                JOIN cohort_survival c ON pi.Cohort_ID = c.Cohort_ID
                JOIN repetition r ON pi.Repetition_ID = r.Repetition_ID
                JOIN age a ON pi.Age_ID = a.Age_ID
                JOIN nat n ON pi.NAT_ID = n.NAT_ID
                JOIN transition t ON pi.Transition_ID = t.Transition_ID
                WHERE sy.School_Year_ID = $yearId
                ORDER BY g.Grade_Level";
            $result = $conn->query($query);
            $records[$year] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        }
    } else {
        $query = "
            SELECT 
                sy.Year_Range,
                g.Grade_Level,
                e.Enroll_Gross,
                e.Enroll_Net,
                c.Cohort_Figure,
                c.Cohort_Rate,
                d.Dropout_Figure,
                d.Dropout_Rate,
                r.Repeaters_Figure,
                r.Repeaters_Rate,
                p.Promotion_Figure,
                p.Promotion_Rate,
                t.Transition_Figure,
                t.Transition_Rate,
                a.Age_Group_12To15,
                n.NAT_Score
            FROM performance_indicator pi
            JOIN schoolyear sy ON pi.School_Year_ID = sy.School_Year_ID
            JOIN grade g ON pi.Grade_ID = g.Grade_ID
            JOIN enroll e ON pi.Enroll_ID = e.Enroll_ID
            JOIN dropout d ON pi.Dropout_ID = d.Dropout_ID
            JOIN promotion p ON pi.Promotion_ID = p.Promotion_ID
            JOIN cohort_survival c ON pi.Cohort_ID = c.Cohort_ID
            JOIN repetition r ON pi.Repetition_ID = r.Repetition_ID
            JOIN age a ON pi.Age_ID = a.Age_ID
            JOIN nat n ON pi.NAT_ID = n.NAT_ID
            JOIN transition t ON pi.Transition_ID = t.Transition_ID
            WHERE sy.School_Year_ID = $selectedYear
            ORDER BY g.Grade_Level";
        $result = $conn->query($query);
        $records[$schoolYears[$selectedYear]] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Process records to merge cohort data for grades 7-10
$processedRecords = [];
foreach ($records as $year => $yearRecords) {
    $processedYearRecords = [];
    $cohort7to10 = ['figure' => 0, 'rate' => 0, 'count' => 0];
    
    // First pass to calculate cohort data for grades 7-10
    foreach ($yearRecords as $record) {
        if ($record['Grade_Level'] >= 7 && $record['Grade_Level'] <= 10) {
            $cohort7to10['figure'] += $record['Cohort_Figure'];
            $cohort7to10['rate'] += $record['Cohort_Rate'];
            $cohort7to10['count']++;
        }
    }
    
    
    
    // Second pass to create processed records
    foreach ($yearRecords as $record) {
        $processedRecord = $record;
        $processedRecord['isJuniorHigh'] = ($record['Grade_Level'] >= 7 && $record['Grade_Level'] <= 10);
        $processedRecord['cohort7to10'] = $cohort7to10;
        $processedYearRecords[] = $processedRecord;
    }
    
    $processedRecords[$year] = $processedYearRecords;
}

// Determine the preparer name to display in the signature section
$displayPreparerName = "Unknown Preparer";
$displayPreparerRank = "";
$displayTeacherSignature = "";

if ($selectedYear === "All") {
    // For "All" years, show the most recent preparer
    if (!empty($allPreparers)) {
        $displayPreparerName = $allPreparers[0]['name']; // First one is most recent due to ORDER BY DESC
        $displayPreparerRank = $allPreparers[0]['rank'];
        $displayTeacherSignature = $allPreparers[0]['esig'];
    }
} elseif (isset($preparersByYear[$selectedYear])) {
    $displayPreparerName = $preparersByYear[$selectedYear]['name'];
    $displayPreparerRank = $preparersByYear[$selectedYear]['rank'];
    $displayTeacherSignature = $preparersByYear[$selectedYear]['esig'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Indicator</title>
    <link rel="icon" type="image/png" href="../img/Logo/docmap-logo-1.png">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .header-section {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap; /* Allow items to wrap on smaller screens */
            padding-right: 20px;
            margin-bottom: 20px;
        }

        h1.title {
            margin: 0;
            font-size: 1.5rem; /* Adjust font size for better scaling */
        }

        .table-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            overflow-x: auto; /* Allow horizontal scrolling for smaller screens */
        }

        table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: center; /* Center text horizontally */
            vertical-align: middle; /* Center text vertically */
            padding: 10px;
            font-size: 1rem;
            word-wrap: break-word; /* Ensure long text wraps */
        }

        th {
            background-color: #9B2035;
            color: white;
        }

        td {
            background-color: #fff;
        }


        .button-group {
            display: flex;
            flex-wrap: wrap; /* Allow buttons to wrap on smaller screens */
            gap: 10px; /* Optional gap between buttons */
        }

        .centered {
            text-align: center;
            vertical-align: middle;
        }

        .text-school-year {
            margin-top: 20px;
        }

        /* Media queries for responsiveness */

        /* For small screens (phones) */
        @media (max-width: 600px) {
            h1.title {
                font-size: 1.2rem;
            }

            .header-section {
                flex-direction: column; /* Stack header items vertically */
                align-items: flex-start; /* Align items to the start */
                padding-right: 10px;
            }

            table {
                font-size: 0.9rem; /* Reduce font size for smaller screens */
            }

            th, td {
                padding: 8px; /* Reduce padding */
            }

            .button-group {
                justify-content: center; /* Center buttons */
            }
        }

        /* For medium screens (tablets) */
        @media (max-width: 900px) {
            h1.title {
                font-size: 1.4rem;
            }

            .header-section {
                flex-direction: row;
                justify-content: center; /* Center header items */
            }

            table {
                width: 90%; /* Adjust table width */
            }

            th, td {
                padding: 10px;
            }
        }
        .printbtn{
            background-color: green;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 25px; /* Adjust for desired roundness */
            cursor: pointer;
            transition: background-color 0.3s; /* Add a smooth transition effect */
            font-weight:bold;
        }
        .printbtn:hover{
             background-color: darkgreen;
        }
        
        .merged-cohort-cell {
            vertical-align: middle !important;
            text-align: center !important;
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
                <h1 class="title">Performance Indicator</h1>
                <div class="button-group">
                    <button class="printbtn" id="printButton" onclick="fetchSchoolDetailsAndPrint()">Print</button>
                </div>
            </div>

            <div class="filter-station">
                <label for="schoolYearFilter">School Year:</label>
                <select id="schoolYearFilter" class="form-control" onchange="filterByYear()">
                    <option value="All" <?= $selectedYear === "All" ? "selected" : "" ?>>All</option>
                    <?php foreach ($schoolYears as $yearId => $year): ?>
                        <option value="<?= $yearId ?>" <?= $selectedYear == $yearId ? "selected" : "" ?>>
                            <?= $year ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if (!empty($processedRecords)): ?>
                    <?php foreach ($processedRecords as $year => $yearRecords): ?>
                        <h3 class="text-center text-school-year"><?= $year ?></h3>
                        <div class="table-container">
                            <table class="table table-bordered" id="performance_table">
                                <thead>
                                    <tr>
                                        <th colspan="1">GRADE</th>
                                        <th colspan="2">ENROLLMENT</th>
                                        <th colspan="2">COHORT</th>
                                        <th colspan="2">DROP/SCHOOL LEAVERS</th>
                                        <th colspan="2">REPEATERS</th>
                                        <th colspan="2">GRADUATION</th>
                                        <th colspan="2">TRANSITION</th>
                                        <th colspan="2">AGE</th>
                                        <th rowspan="2">NAT</th>
                                    </tr>
                                    <tr>
                                        <th>LEVEL</th>
                                        <th>GROSS</th>
                                        <th>NET</th>
                                        <th>FIGURE</th>
                                        <th>RATE</th>
                                        <th>FIGURE</th>
                                        <th>RATE</th>
                                        <th>FIGURE</th>
                                        <th>RATE</th>
                                        <th>FIGURE</th>
                                        <th>RATE</th>
                                        <th>FIGURE</th>
                                        <th>RATE</th>
                                        <th colspan='2'>12-15</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totals = [
                                        'Enroll_Gross' => 0,
                                        'Enroll_Net' => 0,
                                        'Cohort_Figure' => 0,
                                        'Cohort_Rate' => 0,
                                        'Dropout_Figure' => 0,
                                        'Dropout_Rate' => 0,
                                        'Repeaters_Figure' => 0,
                                        'Repeaters_Rate' => 0,
                                        'Promotion_Figure' => 0,
                                        'Promotion_Rate' => 0,
                                        'Transition_Figure' => 0,
                                        'Transition_Rate' => 0,
                                        'Age_Group_12To15' => 0,
                                        'NAT_Score' => 0
                                    ];

                                    // Calculate totals
                                    foreach ($yearRecords as $record) {
                                        foreach ($totals as $key => $value) {
                                            $totals[$key] += $record[$key];
                                        }
                                    }

                                    // Calculate rates
                                    $totals['Dropout_Rate'] = ($totals['Enroll_Gross'] > 0) 
                                        ? ($totals['Dropout_Figure'] / $totals['Enroll_Gross']) * 100 
                                        : 0;
                                    $totals['Repeaters_Rate'] = ($totals['Enroll_Gross'] > 0) 
                                        ? ($totals['Repeaters_Figure'] / $totals['Enroll_Gross']) * 100 
                                        : 0;
                                    ?>

                                    <?php 
                                    $displayedCohort7to10 = false;
                                    foreach ($yearRecords as $record): 
                                        $isJuniorHigh = $record['isJuniorHigh'];
                                    ?>
                                        <tr>
                                            <td><?= $record['Grade_Level'] ?></td>
                                            <td><?= $record['Enroll_Gross'] ?></td>
                                            <td><?= $record['Enroll_Net'] ?></td>
                                            
                                            <?php if ($isJuniorHigh): ?>
                                                <?php if (!$displayedCohort7to10): ?>
                                                    <td rowspan="4" class="merged-cohort-cell"><?= $record['cohort7to10']['figure'] ?></td>
                                                    <td rowspan="4" class="merged-cohort-cell"><?= number_format($record['cohort7to10']['rate'], 2) ?></td>
                                                    <?php $displayedCohort7to10 = true; ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <td><?= $record['Cohort_Figure'] ?></td>
                                                <td><?= number_format($record['Cohort_Rate'], 2) ?></td>
                                            <?php endif; ?>
                                            
                                            <td><?= $record['Dropout_Figure'] ?></td>
                                            <td><?= number_format($record['Dropout_Rate'], 2) ?></td>
                                            <td><?= $record['Repeaters_Figure'] ?></td>
                                            <td><?= number_format($record['Repeaters_Rate'], 2) ?></td>
                                            <td><?= $record['Promotion_Figure'] ?></td>
                                            <td><?= $record['Promotion_Rate'] ?></td>
                                            <td><?= $record['Transition_Figure'] ?></td>
                                            <td><?= $record['Transition_Rate'] ?></td>
                                            <td colspan="2"><?= $record['Age_Group_12To15'] ?></td>
                                            <td><?= $record['NAT_Score'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <tr style="font-weight: bold; background-color: #f0f0f0;">
                                        <td>TOTAL</td>
                                        <td><?= $totals['Enroll_Gross'] ?></td>
                                        <td><?= $totals['Enroll_Net'] ?></td>
                                        <td></td>
                                        <td></td>
                                        <td><?= $totals['Dropout_Figure'] ?></td>
                                        <td><?= number_format($totals['Dropout_Rate'], 2) ?></td>
                                        <td><?= $totals['Repeaters_Figure'] ?></td>
                                        <td><?= number_format($totals['Repeaters_Rate'], 2) ?></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td colspan="2"><?= $totals['Age_Group_12To15'] ?></td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th colspan="16" class="text-center">No records available.</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="assets/js/script.js"></script>

    <script>

        function calculateHrWidth(text, fontSize = '1.2rem') {
            // Create a temporary span element to measure the text width
            const span = document.createElement('span');
            span.style.visibility = 'hidden';
            span.style.position = 'absolute';
            span.style.fontSize = fontSize;
            span.style.fontWeight = 'bold'; // Match your name style
            span.style.fontFamily = "'Times New Roman', serif"; // Match your font
            span.style.whiteSpace = 'nowrap';
            span.textContent = text;
            
            document.body.appendChild(span);
            const width = span.offsetWidth;
            document.body.removeChild(span);
            
            // Return width plus some padding (e.g., 20px)
            return `${width + 20}px`;
        }

        function filterByYear() {
            const selectedYear = document.getElementById("schoolYearFilter").value;
            window.location.href = "?year=" + selectedYear;
        }

            function fetchSchoolDetailsAndPrint() {
                const isAllYears = <?= $selectedYear === 'All' ? 'true' : 'false' ?>;
                const yearsData = <?= json_encode(array_keys($processedRecords)) ?>;
                const selectedYearText = "<?= $selectedYear === 'All' ? '' : $schoolYears[$selectedYear] ?>";

                $.ajax({
                    url: 'getSchoolDetails.php',
                    method: 'GET',
                    success: function(data) {

                        // Initialize the variable at the beginning
                        let tablesToPrint = '';

                        const logo = data.Logo
                            ? `<img src="../img/Logo/${data.Logo}" alt="School Logo" style="width: 130px; height: auto;" />`
                            : '<p>No Logo Available</p>';
                        
                        const depedLogo = `
                            <div style="text-align: center; margin-bottom: 15px;">
                                <img src="../img/Logo/deped_logo.png" alt="DepEd Logo" style="width: 90px; height: auto;" />
                            </div>
                        `;

                        const schoolDetails = `
                            <div class="header-container">
                                ${depedLogo}
                                <div class="school-details" style="text-align: center;">
                                    <p style='font-family: "Old English Text MT", serif; font-weight:bold; font-size:20px; margin: 0;'>Republic of the ${data.Country || 'Philippines'}</p>
                                    <p style='font-family: "Old English Text MT", serif;font-weight:bold; font-size:28px; margin: 4px 0 2px 0;'>${data.Organization || 'Department of Education'}</p>
                                    <p style="text-transform: uppercase; font-family: 'Tahoma'; font-size: 16px; margin: 1px;">Region ${data.Region || 'IV-A'}</p>
                                    <p style="text-transform: uppercase; font-family: 'Tahoma'; font-size: 16px; margin: 1px;">Schools Division of Batangas</p>
                                    <p style="text-transform: uppercase; font-family: 'Tahoma'; font-size: 16px; margin: 1px;">${data.Name || 'School Name'}</p>
                                    <p style="text-transform: uppercase;  font-family: 'Tahoma'; font-size: 16px; margin: 1px;">${data.Address}, ${data.City_Muni}</p>
                                </div>
                            </div>
                            <hr style="max-width:100%; margin: 10px auto; border: 1px solid black;">

                            
                            <div class="additional-titles" style="text-align: center; font-family: 'Times New Roman', serif;">
                                <h3 style="margin-top: 10px;">Performance Indicator</h3>
                                ${!isAllYears ? `<span style="margin: 0; font-size: 1.5rem;">S.Y. ${selectedYearText}</span>` : ''}
                            </div>
                        `;

                        // Calculate widths for both names
                        const preparerName = "<?= strtoupper($displayPreparerName) ?>";
                        const principalName = data.Principal_FullName.toUpperCase();
                        
                        // Get the computed font size from your style (1.2rem)
                        const fontSize = '1.2rem';
                        
                        const preparerHrWidth = calculateHrWidth(preparerName, fontSize);
                        const principalHrWidth = calculateHrWidth(principalName, fontSize);

                        const signatureSection = `
                            <div class="signature-section" style="margin-top: 50px; text-align: center; display: flex; justify-content: space-between; align-items: flex-start; font-size: ${fontSize}">
                                <div style="text-align: center; flex: 1;">
                                    <span style="font-weight:bold;">PREPARED BY:</span><br/><br/>
                                    <div style="margin-bottom: -60px; margin-top: -60px;">
                                        <img src="https://raw.githubusercontent.com/docmap2024/DocMaP/main/img/e_sig/<?= $displayTeacherSignature ?>" alt="Preparer Signature" style="height: 150px; width: 150px; margin-bottom: 5px;" onerror="this.style.display='none'">
                                    </div>
                                    <span style="font-weight:bold;">${preparerName}</span>
                                    <hr style="width: ${preparerHrWidth}; margin: 0 auto; border: 1px solid black;">
                                    <?php if (!empty($displayPreparerRank)): ?>
                                        <span><?= ($displayPreparerRank) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="text-align: center; flex: 1;">
                                    <span style="font-weight:bold;">NOTED BY:</span><br/><br/>
                                    <div style="margin-bottom: -55px; margin-top: -65px;">
                                        <img src="https://raw.githubusercontent.com/docmap2024/DocMaP/main/img/e_sig/${data.Principal_Signature}" alt="Principal Signature" style="height: 150px; width: 150px; margin-bottom: 5px;" onerror="this.style.display='none'">
                                    </div>
                                    <span style="font-weight:bold;">${principalName}</span>
                                    <hr style="width: ${principalHrWidth}; margin: 0 auto; border: 1px solid black;">
                                    <span>${data.Principal_Role}</span>
                                </div>
                            </div>
                        `;

                        const footer = `
                            <div class="footer">
                                <div style="display: flex; align-items: center; max-width: 100%; padding: 0 20px;">
                                    <div class="footer-logo">
                                        <img src="../img/Logo/DEPED_MATATAGLOGO.PNG" style="width: 170px; height: auto; margin-right: 10px;" />
                                        <img src="https://raw.githubusercontent.com/docmap2024/DocMaP/main/img/Logo/${data.Logo}" style="width: 110px; height: auto;" />
                                    </div>
                                    <div class="footer-details">
                                        <p style="margin-bottom: -5px;">${data.Address || ''} ${data.City_Muni || ''} ${data.School_ID ? 'School ID: ' + data.School_ID : ''}</p>
                                        <p style="margin-bottom: -5px;">${data.Mobile_No ? 'Contact nos.: ' + data.Mobile_No : ''} ${data.landline ? ' Landline: ' + data.landline : ''}</p>
                                        <p class="footer-underline">${data.Email || ''}</p>
                                    </div>
                                </div>
                            </div>
                        `;

                        // Generate content for each year
                        if (isAllYears) {
                            // For "All" years, create a separate section for each year
                            const tableContainers = document.querySelectorAll('.table-container');
                            
                            yearsData.forEach((year, index) => {
                                const yearHeader = `<h4 style="text-align: center; margin: 0 ;">S. Y. ${year}</h4>`;
                                
                                // Get the table HTML for this year
                                const tableHTML = tableContainers[index] ? tableContainers[index].outerHTML : '';
                                
                                tablesToPrint += `
                                    <div class="print-section" style="page-break-after: ${index < yearsData.length - 1 ? 'always' : 'auto'};">
                                        ${schoolDetails}
                                        ${yearHeader}
                                        ${tableHTML}
                                        ${signatureSection}
                                        ${footer}
                                    </div>
                                `;
                            });
                        } else {
                            // For single year, just use the existing table
                            const tableHTML = document.querySelector('.table-container') ? document.querySelector('.table-container').outerHTML : '';
                            tablesToPrint = `
                                <div class="print-section">
                                    ${schoolDetails}
                                    ${tableHTML}
                                    ${signatureSection}
                                    ${footer}
                                </div>
                            `;
                        }

                        const win = window.open('', '', 'height=700,width=900');
                        win.document.write(`
                            <!DOCTYPE html>
                            <html lang="en">
                            <head>
                                <meta charset="UTF-8">
                                <meta name="robots" content="noindex, nofollow">
                                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                <title>Performance Indicator Report</title>
                                <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
                                <style>
                                    @media print {
                                        @page {
                                            margin: 5mm 5mm 5mm 5mm; 
                                            size: auto;
                                            marks: none; /* Removes crop marks */
                                        }
                                        body {
                                            margin: 0 !important;
                                            padding: 0;
                                        }
                                        .footer {
                                            position: fixed;
                                            bottom: 0;
                                        }
                                        /* Remove browser-added headers/footers */
                                        thead { display: table-header-group; }
                                        tfoot { display: table-footer-group; }
                                    }
                                    body {
                                        font-family: 'Times New Roman', serif;
                                        margin: 0;
                                        position: relative;
                                        padding-bottom: 100px; /* Space for footer */
                                    }

                                    .table-container table {
                                        width: 100%;
                                        border-collapse: collapse;
                                        margin-top: 20px;
                                    }
                                    table {
                                        max-width: 100%;
                                        table-layout: fixed;
                                        border: 2px solid #000 !important; /* Thicker outer border */
                                    }
                                    th, td {
                                        text-align: center;
                                        vertical-align: middle;
                                        padding: 6px;
                                        word-wrap: break-word;
                                        border: 1.5px solid #000 !important; /* Thicker cell borders */
                                    }
                                    th {
                                        background-color: #9B2035;
                                        color: black;
                                        font-weight: bold;
                                        font-size: 0.8rem;
                                        border: 2px solid #000 !important; /* Even thicker header borders */
                                    }

                                    td {
                                        background-color: #ffffff;
                                    }
                                    

                                    .header-container {
                                        margin-bottom: 20px;
                                    }

                                    .school-details {
                                        text-align: center;
                                    }

                                    .school-details p, .school-details h2 {
                                        margin: 0;
                                    }

                                    .logo img {
                                        width: 130px;
                                        height: auto;
                                    }

                                    .additional-titles {
                                        margin: 20px 0;
                                    }

                                    .signature-section {
                                        margin-top: 60px;
                                        margin-bottom: 100px; /* Space above footer */
                                        page-break-inside: avoid;
                                    }

                                    .merged-cohort-cell {
                                        vertical-align: middle !important;
                                        text-align: center !important;
                                        border: 1.5px solid #000 !important;
                                    }
                                    hr {
                                        border-top: 2px solid black;
                                    }
                                    /* Add border to the entire table */
                                    .table-bordered {
                                        border: 2px solid #000 !important;
                                    }
                                    /* Add border to table cells */
                                    .table-bordered th,
                                    .table-bordered td {
                                        border: 1.5px solid #000 !important;
                                    }
                                    /* Add thicker border to header cells */
                                    .table-bordered thead th {
                                        border-bottom-width: 2px !important;
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
                                    }
                                    
                                </style>
                            </head>
                            <body>
                                ${tablesToPrint}
                            </body>
                            </html>
                        `);

                        win.document.close();
                        win.onload = function() {
                            win.print();
                            win.close();
                        };
                    }
                });
            }
    </script>
</body>
</html>