<?php
session_start(); // Starting Session 

// $error = '';

if (isset($_SESSION['email'])  && isset($_SESSION['type'])) {
    $email = $_SESSION['email'];
    $type = $_SESSION['type'];
}
else{
    echo "<script>alert('Please Login/Signup First')</script>";
    $error = "Please Login/SignUp First";
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
    $doner_type = isset($_POST['doner_type']) ? $_POST['doner_type'] : '';
    $doner_other = isset($_POST['other_description']) ? $_POST['other_description'] : '';
    
    $typical_donation = isset($_POST['typical_donation']) ? $_POST['typical_donation'] : '';
    $typical_donation_other = isset($_POST['typical-donation-input']) ? $_POST['typical-donation-input'] : '';

    $delivary = isset($_POST['delivary']) ? $_POST['delivary'] : '';

    // $delivary = $_POST['delivary'];
    if(empty(trim($doner_type)) || empty($typical_donation) || empty(trim($delivary))) {
        $_SESSION['error_message'] = "Please fill out all the fields.";
    } elseif($doner_type == 'other' && empty(trim($doner_other)) || $typical_donation == 'other' && empty($typical_donation_other)) {
        $_SESSION['error_message'] = "Other field cannot be left blank.";
    } 
    // elseif($req_bool == 'yes' && empty($req_people)) {
    //     $error = "Number of people required should not be empty when requirement is yes.";
    // }
    
    else {
        if($doner_type == 'other'){
            $doner_type = $doner_other;
        }
        if($typical_donation == 'other'){
            $typical_donation = $typical_donation_other;
        }
        
        $table = ($type == 'doner') ? 'fooddoners' : 'foodreceivers';

        // Update database with form inputs
        $sql_update = "UPDATE $table SET doner_type=?, typical_donation=?, delivary=? WHERE email=?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "ssss", $doner_type, $typical_donation, $delivary, $email);
        mysqli_stmt_execute($stmt_update);

        
        // Redirect user after successful update
        $location = ($type == 'doner') ? 'index.php' : 'receiver_dashboard.php';
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
    <section class="doner-extra-info">
        
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
                            echo '<center><p style="color: red; font-size: 1rem;">' . $_SESSION['error_message'] . '</p></center>';
                            unset($_SESSION['error_message']);
                        }
                    ?><br>
                    <div class="">
                        <label>Type of Food Doner: </label> &nbsp;&nbsp;
                        <select class="doner-type-select" name="doner_type">
                            <option value="individual">Individual</option>
                            <option value="community-kitchen">Community Kitchen</option>
                            <option value="restaurant/cafe">Restaurant / Cafe</option>

                            <!-- <option value="hospital">Hospital or Medical Center</option> -->
                            <option value="corporate-cafeterias">Corporate Cafeterias</option>
                            <!-- <option value="restaurant">Restaurant or Cafe</option> -->
                            <option value="Eevent-caterers">Event Caterers</option>
                            <!-- <option value="retailstore">Retail Store (Supermarket, Grocery Store)</option> -->
                            <option value="supermarket / grocery-store">Supermarket / Grocery Store</option>
                            <option value="other">Other (Please specify below) : </option>
                        </select>
                    </div><br>
                    <div class="input-wrapper" id="other-input" style="display: none;">
                        <input type="text" class="input" name="other_description"  placeholder="Specify other">
                        <label class="label" for="other_description">Specify other</label>
                    </div><br>

                    <div class="input-wrapper">
                        <label for="typical_donation" class="radio-label">We/I tipically donet this type of food: </label><br><br>
                        <div class="radio">
                            <input type="radio" name="typical_donation" value="non-perishable" id="non-perishable"><label for="non-perishable">Non-Perishable </label>
                            <input type="radio" name="typical_donation" value="perishable" id="perishable"><label for="perishable">Perishable</label>
                            <input type="radio" name="typical_donation" value="baby" id="baby"><label for="baby">Baby Food and formula</label>
                            <input type="radio" name="typical_donation" value="beverages" id="beverages"><label for="beverages">Beverages</label><br><br>                                
                            <input type="radio" name="typical_donation" value="snacks" id="snacks"><label for="snacks">Snacks and treats</label>
                            <input type="radio" name="typical_donation" value="other" id="other"><label for="other">Other</label><br><br>                                
                            <input type="radio" name="typical_donation" value="no" id="no"><label for="no">Nothing like that.</label>
                        </div><br>

                        <div id="typical-donation-input" class="input-wrapper" style="display: none;">
                            <input type="text" class="input" name="typical-donation-input" id="typical-donation-input" placeholder="">
                            <label for="requirement" class="label">Please specify your typical donation:</label><br>
                        </div><br>
                    </div>

                    <div class="input-wrapper">
                        <label for="delivary" class="radio-label">Availability for a delivary:</label><br><br>
                        <div class="radio">
                            <input type="radio" name="delivary" value="yes" id="delivary-yes"><label for="delivary-yes">Yes</label>
                            <input type="radio" name="delivary" value="no" id="delivary-no"><label for="delivary-no">No</label>
                        </div><br><br>
                        <!-- <div id="delivary-input" class="input-wrapper" style="display: none;">
                            <input type="number" class="input" name="people" id="people" min="1" placeholder="Number of people">
                            <label for="pick" class="label">Please specify the number of people for your requirement:</label><br>
                        </div><br> -->
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

            var donerTypeSelect = document.querySelector(".doner-type-select"); // Changed to querySelector
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

            donerTypeSelect.addEventListener("change", function() { // Changed to receiverTypeSelect
                if (donerTypeSelect.value === "other") {
                    otherInput.style.display = "block";
                } else {
                    otherInput.style.display = "none";
                }
            });
        });


        
    </script>
</body>
</html>