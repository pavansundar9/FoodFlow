<?php
session_start(); // Starting Session

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $type = $_SESSION['type'];
}
else{
    echo "<script>alert('Please Login/Signup First')</script>";
    $_SESSION['error_message'] = $error;
    header("Location: login.php");// Redirecting To Home Page 
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sdp";
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $receiver_type = isset($_POST['receiver_type']) ? $_POST['receiver_type'] : '';
    $receiver_other = isset($_POST['other_description']) ? $_POST['other_description'] : '';
    
    $req_bool = isset($_POST['requirement']) ? $_POST['requirement'] : '';
    $req_people = isset($_POST['people']) ? $_POST['people'] : '';

    $accept = isset($_POST['accept']) ? $_POST['accept'] : '';

    // $accept = $_POST['accept'];
    if(empty(trim($receiver_type)) || empty($req_bool) || empty(trim($accept))) {
        $_SESSION['error_message'] = "Please fill out all the fields.";
    } elseif($receiver_type == 'other' && empty(trim($receiver_other))) {
        $_SESSION['error_message'] = "Other field cannot be left blank.";
    } elseif($req_bool == 'yes' && empty($req_people)) {
        $_SESSION['error_message'] = "Number of people required should not be empty when requirement is yes.";
    }
    
    else {
        if($receiver_type == 'other'){
            $receiver_type = $receiver_other;
        }
        
        // Determine the table based on the type
        $table = ($type == 'doner') ? 'fooddoner' : 'foodreceivers';

        // Update database with form inputs
        $sql_update = "UPDATE $table SET receiver_type=?, req_bool=?, req_people=?, accept=?, daily_count=? WHERE email=?";
        $stmt_update = mysqli_prepare($conn, $sql_update);

        // Check if the statement was prepared successfully
        if ($stmt_update) {
            // Assuming $daily_count is a variable you want to bind, otherwise assign the constant value directly in the SQL statement
            $daily_count = 0;
            
            // Bind the parameters
            mysqli_stmt_bind_param($stmt_update, "ssissi", $receiver_type, $req_bool, $req_people, $accept, $daily_count, $email);
            
            // Execute the statement
            mysqli_stmt_execute($stmt_update);
            
            // Check for errors
            if (mysqli_stmt_errno($stmt_update)) {
                echo "Error: " . mysqli_stmt_error($stmt_update);
            } else {
                echo "Record updated successfully";
            }
            
            // Close the statement
            mysqli_stmt_close($stmt_update);
        } else {
            echo "Error preparing statement: " . mysqli_error($conn);
        }

        // Close the database connection
        mysqli_close($conn);

        // Redirect user after successful update
        $location = ($type == 'doner') ? 'doner_extra_info.php' : 'receiver_dashboard.php';
        header("Location: $location");
        exit();
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
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Meddon&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v5.15.4/css/all.css">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.5.2/css/all.css">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.5.2/css/sharp-thin.css">
    <title>Food Receiver Extra Info</title>
</head>
<body>
    <section class="receiver-extra-info">
        
        <form id="extra-info-form" action="" method="POST">
            <div class="back-button">
                <a><i class="fa-duotone fa-arrow-left" style="--fa-primary-color: #17272b; --fa-primary-opacity: .9; --fa-secondary-color: #91c11b; --fa-secondary-opacity: .7; font-size: 30px;"></i></a>
            </div>
            <?php
                if (isset($_SESSION['error_message'])) {
                    echo '<p style="color: red;">' . $_SESSION['error_message'] . '</p>';
                    unset($_SESSION['error_message']);
                }
            ?>
            <div style="border-left: #536d10 1px solid; padding: 10px;">
                <h3>Help us make your experience better...</h3>
                <div class="info-container">
                    <?php
                        if (isset($_SESSION['error_message'])) {
                            echo '<p style="color: red;">' . $_SESSION['error_message'] . '</p>';
                            unset($_SESSION['error_message']);
                        }
                    ?><br>
                    <div class="">
                        <label>Type of Food Receiver: </label> &nbsp;&nbsp;
                        <select class="receiver-type-select" name="receiver_type">
                            <option value="foodbank">Food Bank</option>
                            <option value="ngo">NGO</option>
                            <option value="orphanage/oldagehome">Orphanage / Old Age Home</option>
                            <!-- <option value="hospital">Hospital or Medical Center</option> -->
                            <option value="school">School or University</option>
                            <!-- <option value="restaurant">Restaurant or Cafe</option> -->
                            <option value="governmentagency">Government Agency (Police, Fire Department)</option>
                            <!-- <option value="retailstore">Retail Store (Supermarket, Grocery Store)</option> -->
                            <option value="other">Other (Please specify below) : </option>
                        </select>
                    </div><br>
                    <div class="input-wrapper" id="other-input" style="display: none;">
                        <input type="text" class="input" name="other_description"  placeholder="Specify other">
                        <label class="label" for="email">Specify other</label>
                    </div><br>

                    <div class="input-wrapper">
                        <label for="requirement" class="radio-label">We/I have set requirement for food</label><br><br>
                        <div class="radio">
                            <input type="radio" name="requirement" value="yes" id="requirement-yes"><label for="requirement-yes">Yes</label>
                            <input type="radio" name="requirement" value="no" id="requirement-no"><label for="requirement-no">No</label>
                        </div><br>
                        <div id="requirement-input" class="input-wrapper" style="display: none;">
                            <input type="number" class="input" name="people" id="people" min="1" placeholder="Number of people">
                            <label for="requirement" class="label">Please specify the number of people for your requirement:</label><br>
                        </div><br>
                    </div>

                    <div class="input-wrapper">
                        <label for="accept" class="radio-label">We/I accept</label><br><br>
                        <div class="radio">
                            <input type="radio" name="accept" value="non-perishable" id="non-perishable"><label for="non-perishable">Non-Perishable </label>
                            <input type="radio" name="accept" value="perishable" id="perishable"><label for="perishable">Perishable</label>
                            <input type="radio" name="accept" value="baby" id="baby"><label for="baby">Baby Food and formula</label>
                            <input type="radio" name="accept" value="all" id="all"><label for="all">Anything that can be donated</label>
                        </div><br><br>
                    </div>
                </div>
                <input type="submit" id="submit" name="submit" value="Continue"><br>
            </div>
            
        </form>

    </section>

    <script>
        // document.addEventListener("DOMContentLoaded", function() {
        //     var receiverTypeSelect = document.getElementById("receiver-type");
        //     var otherInput = document.getElementById("other-input");

        //     receiverTypeSelect.addEventListener("change", function() {
        //         if (receiverTypeSelect.value === "other") {
        //             otherInput.style.display = "block";
        //         } else {
        //             otherInput.style.display = "none";
        //         }
        //     });
        // });
        document.addEventListener("DOMContentLoaded", function() {
            var yesRadio = document.getElementById("requirement-yes");
            var noRadio = document.getElementById("requirement-no");
            var requirementInput = document.getElementById("requirement-input");

            var receiverTypeSelect = document.querySelector(".receiver-type-select"); // Changed to querySelector
            var otherInput = document.getElementById("other-input");

            yesRadio.addEventListener("change", function() {
                if (yesRadio.checked) {
                    requirementInput.style.display = "block";
                } else {
                    requirementInput.style.display = "none";
                }
            });
            noRadio.addEventListener("change", function() {
                if (noRadio.checked) {
                    requirementInput.style.display = "none";
                }
            });

            receiverTypeSelect.addEventListener("change", function() { // Changed to receiverTypeSelect
                if (receiverTypeSelect.value === "other") {
                    otherInput.style.display = "block";
                } else {
                    otherInput.style.display = "none";
                }
            });
        });


        
    </script>
</body>
</html>