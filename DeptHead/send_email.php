<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';  // Adjust path if you are not using Composer

// Initialize PHPMailer
$mail = new PHPMailer(true);

// Fetch the user ID and MPS from the POST request
$userID = $_POST['userID'];
$mps = $_POST['mps'];

// Database connection
include 'connection.php';

// Check if userID is provided
if (!$userID || !$mps) {
    echo "❌ Missing UserID or MPS value.";
    exit;
}

// Fetch teacher info (name, email) using the user ID
$sql = "SELECT fname, lname, email FROM useracc WHERE UserID = '$userID' AND role = 'Teacher'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $teacherFullName = $row['fname'] . ' ' . $row['lname'];
    $teacherEmail = $row['email'];
} else {
    echo "❌ Teacher not found!";
    exit;
}

// Send email using PHPMailer
try {
    //Server settings
    $mail->isSMTP();                                            // Send using SMTP
    $mail->Host       = 'smtp.gmail.com';                       // Set the SMTP server (replace with your SMTP server)
    $mail->SMTPAuth   = true;                                     // Enable SMTP authentication
    $mail->Username   = 'proftal2024@gmail.com';                 // SMTP username (your email)
    $mail->Password = 'ytkj saab gnkb cxwa'; // Use your app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;                                    // TCP port for TLS (587)

    //Recipients
    $mail->setFrom('proftal2024@gmail.com', 'ProfTal');
    $mail->addAddress($teacherEmail);  // Add the correct teacher's email

    // Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'MPS Report Submission';
    $mail->Body    = "Hello $teacherFullName,<br><br>Your MPS is: <strong>$mps</strong><br><br>Regards,<br>Your School";

    // Send the email
    $mail->send();
    echo '✅ Email sent successfully!';
} catch (Exception $e) {
    echo "❌ Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}

$conn->close();
?>
