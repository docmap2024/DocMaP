<?php
$servername = "docmap_docmap";
$username = "mysql";
$password = "7fa99534b6489686fd81";
$dbname = "docmap2";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
