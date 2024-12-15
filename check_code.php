<?php
include 'connection.php';

if (isset($_POST['code'])) {
    $code = $_POST['code'];

    $query = "SELECT ContentID FROM feedcontent WHERE ContentCode = '$code'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        echo 'exists';
    } else {
        echo 'not exists';
    }

    mysqli_close($conn);
}
?>
