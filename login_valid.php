<?php
session_start();

if (isset($_POST['submit'])) {
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "sdp";
    $conn = mysqli_connect($servername, $username, $password, $dbname);

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $email = $_POST["email"];
    $password = $_POST["password"];
    $type = $_POST["type"];

    // Check for empty fields
    if (empty($email) || empty($password) || empty($type)) {
        $_SESSION['error_message'] = "Please fill all the fields";
        header("Location: login.php");
        exit();
    }

    // Prepare SQL statement
    $sql = "";
    $table = "";
    if ($type == 'doner') {
        $sql = "SELECT * FROM fooddoners WHERE email=?";
        $table = "fooddoners";
    } else {
        $sql = "SELECT * FROM foodreceivers WHERE email=?";
        $table = "foodreceivers";
    }

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        if ($row) {
            $hashed_password = $row['password'];
            // Verify password
            if (password_verify($password, $hashed_password)) {
                // if($type == 'doner'){
                //     $_SESSION['doner_email'] = $email;
                //     $_SESSION['doner_name'] = $row['name'];
                //     $_SESSION['doner_c_number'] = $row['c_number'];
                // }else{
                //     $_SESSION['receiver_email'] = $email;
                //     $_SESSION['receiver_name'] = $row['name'];
                //     $_SESSION['receiver_c_number'] = $row['c_number'];
                // }
                $_SESSION['email'] = $email;
                $_SESSION['type'] = $type;
                $_SESSION['name'] = $row['name'];
                $_SESSION['c_number'] = $row['c_number'];
                $_SESSION['logged-in'] = true;
                if($type =='doner'){
                    header("Location: index.php");
                }else{
                    header("Location: profile.php");
                }
                // header("Location: profile.php");

                exit();
            } else {
                $_SESSION['error_message'] = "Invalid Email/Password! Please try again.";
                header("Location: login.php");
                exit();
            }
        } else {
            $_SESSION['error_message'] = "User not found! Try Again.";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Query failed.";
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>
