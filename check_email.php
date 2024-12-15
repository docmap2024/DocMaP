<?php
    include('connection.php'); // Include database connection

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = htmlspecialchars($_POST['email']);

        // Prepare the statement to check for the email in the database
        $stmt = $conn->prepare("SELECT COUNT(*) FROM useracc WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();

        // Return a JSON response
        echo json_encode(['exists' => $count > 0]);

        $stmt->close();
        $conn->close();
    }
?>
