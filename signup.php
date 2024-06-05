<?php
    session_start();
    $name = $email = $type = $address = $number = '';

    // Connecting to the Database
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "sdp";
    $conn = mysqli_connect($servername, $username, $password, $dbname);

    if (!$conn) {
        die("Connection Failed: " . mysqli_connect_error());
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = isset($_POST['name']) ? $_POST['name'] : '';
        $email = isset($_POST['email']) ? $_POST['email'] : '';
        $type = isset($_POST['type']) ? $_POST['type'] : '';
        $number = isset($_POST['phone-number']) ? $_POST['phone-number'] : '';
        $address = isset($_POST['address']) ? $_POST['address'] : '';

        if (empty($name) || empty($email) || empty($type) || empty($address) || empty($number)) {
            echo "<script>console.log('$email');</script>";
            $_SESSION['error_message'] = "Please fill all the fields";
            header("Location: signup.php");
            exit(); // Terminate script execution after redirection
        } elseif (!preg_match("/^[a-zA-Z-' ]*$/", $name)) {
            $_SESSION['error_message'] = "Name: Please use only letters, numbers and spaces";
            header("Location: signup.php");
            exit();
        } elseif (!preg_match("/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/", $email)) {
            $_SESSION['error_message'] = "Email: Please use only letters, numbers, dots and dashes";
            header("Location: signup.php");
            exit();
        } elseif (!preg_match("/^[0-9]+$/", $number)) {
            $_SESSION['error_message'] = "Number: Please use only numbers";
            header("Location: signup.php");
            exit();
        } else {
            // No errors, proceed with session setup and database operations
            $_SESSION['error_message'] = "";
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            $_SESSION['type'] = $type;
            $_SESSION['c_number'] = $number;
            if ($type == 'doner') {
                $table = 'fooddoners';
            } else {
                $table = 'foodreceivers';
            }

            // Check if email already exists in the database
            $sql = "SELECT * FROM $table WHERE email='$email'";
            $result = mysqli_query($conn, $sql);
            if (mysqli_num_rows($result) > 0) {
                $_SESSION['error_message'] = "Email already exists";
                header("Location: signup.php");
                exit();
            } else {
                // Insert data into the table
                $sql_insert = "INSERT INTO $table (name, email, user_type, c_number, address) VALUES ('$name', '$email', '$type', '$number', '$address')";
                if (mysqli_query($conn, $sql_insert)) {
                    // Redirect to create password page
                    $location = 'create-password.php';
                    header("Location: $location");
                    exit(); // Terminate script execution after redirection
                } else {
                    $_SESSION['error_message'] = "Error: " . mysqli_error($conn);
                    header("Location: signup.php");
                    exit();
                }
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="website icon" type="image/png" href="images/food-flow-icon.png">
    <link rel="stylesheet"  href="signup.css"> 

    <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Meddon&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    
    <title>FoodFlow-Login</title>
</head>
<body>
    <div class="body">
        <div class="fixed-side">
            <div class="logo-side">
                <img src="images/foodflow-bg.png" alt="Logo" id="logo"/>
                <!-- <div class="logo" style="font-family: Kaushan Script, cursive;"><label class="" style="font-family: Meddon, cursive;">F</label>oodFlow</div> -->
            </div>
        </div>
        <div class="form">
            <center><h2>Create an account</h2></center>
            <form name="form" id="form" action="" method="POST">
                <?php
                    if (isset($_SESSION['error_message'])) {
                        echo '<p style="color: red;">' . $_SESSION['error_message'] . '</p>';
                        unset($_SESSION['error_message']);
                    }
                ?><br>
                <div class="input-wrapper">
                    <input type="text" class="input" id="name" name="name" placeholder=" ">
                    <label class="label" for="name">Name</label>
                </div><br><br>
                <div class="input-wrapper">
                    <input type="email" class="input" id="email" name="email" placeholder=" ">
                    <label class="label" for="email">Email</label>
                </div><br><br>
                <div class="input-wrapper">
                    <label for="type" class="radio-label">Type of User:</label><br><br>
                    <div class="radio">
                        <input type="radio" name="type" value="doner" id="doner"><label for="doner">Food Doner</label>
                        <input type="radio" name="type" value="foodreceiver" id="foodreceiver"><label for="foodreceiver">Food Receiver</label>
                    </div><br><br>
                </div>
                <div class="input-wrapper">
                    <input type="text" class="input" id="address" name="address" placeholder=" ">
                    <label class="label" for="address">Address</label>
                </div><br><br>
                <div class="input-container">
                    <select name="country-code" class="country-code">
                        <option name="country-code" value="+91">+91</option>
                        <option name="country-code" value="+91">+1</option>
                    </select>
                    <div class="input-wrapper">
                        <input type="tel" class="input tel-input" id="phone-number" name="phone-number" placeholder="">
                        <label class="label" for="phone-number">Contact Number </label>
                    </div>
                </div><br><br>
                <input type="submit" id="submit" name="submit" value="Continue"><br>
                <div class="login">
                    <p>Already have an account? <a href="login.php">Login</a></p>                
                </div>
            </form>
        </div>
    </div>
</body>
</html>