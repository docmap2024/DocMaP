<?php
$servername = "localhost";
$username = "root";
$password = "1a31bd02bd7bdbc569d0";
$dbname = "docmap2";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
