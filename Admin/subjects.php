<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include your database connection file here
include 'connection.php';

// Query to fetch contents from feedcontent table
$sql = "SELECT * FROM feedcontent";
$result = mysqli_query($conn, $sql);

// Check if there are any records
if (mysqli_num_rows($result) > 0) {
    // Output data of each row

} else {
    echo "No content available.";
}

// Close database connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects</title>
    <!-- ======= Styles ====== -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
	<link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .cardBox {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            height: 300px;
        }

        .card {
            width: calc(33.33% - 20px); /* 3 cards per row */
            background-color: #f0f0f0;
            padding: 20px;
            box-sizing: border-box;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .card p {
            font-size: 14px;
            color: #666;
        }
    </style>
</head>

<body>
    <!-- SIDEBAR -->
	<section id="sidebar">
		<a href="#" class="brand"><i class='bx bxs-smile icon'></i> AdminSite</a>
		<?php include 'navbar.php'; ?>
		
	</section>
	<!-- SIDEBAR -->
    <section id="content">
		<!-- NAVBAR -->
		<?php include 'topbar.php'; ?>
		<!-- NAVBAR -->

		<!-- MAIN -->
		<main>
			<h1 class="title">Subjects</h1>
            <div class="cardBox">
            
                <?php
                if (mysqli_num_rows($result) > 0) {
                    mysqli_data_seek($result, 0); // Reset pointer to the beginning
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<div class='card'>";
                        echo "<h2><a href='tasks.php?content_id=" . $row['ContentID'] . "'>" . $row['Title'] . "</a></h2>";
                        echo "<p>" . $row['Captions'] . "</p>";
                        // Add more elements as needed (e.g., images, links)
                        echo "</div>";
                    }
                } else {
                    echo "No content available.";
                }
                ?>
            </div>
		</main>
		<!-- MAIN -->
	</section>



    
    <!-- =========== Scripts =========  -->
    <script src="assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</body>

</html>
