<?php
session_start();
$error = $doner_name = $c_number = '';

if (isset($_SESSION['type'])) {
    $type = $_SESSION['type'];
    if($type =='doner'){
        $donor_email = $_SESSION['email'];
        $doner_name = $_SESSION['name'];
        $phone = $_SESSION['c_number'];
    } else {
        echo "<script>alert('please login as a donor');</script>";
        echo "<script>window.location.href='index.php';</script>";
    }
} else {
    echo "<script>alert('please login as a donor');</script>";
    echo "<script>window.location.href='index.php';</script>";
}

// Function to sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to handle file uploads
function handle_file_upload($files) {
    $uploaded_image_paths = array();
    $upload_directory = "uploads/";

    // Check if files are uploaded
    if (isset($files['name']) && is_array($files['name'])) {
        // Loop through each uploaded file
        for ($i = 0; $i < count($files['name']); $i++) {
            $image_name = basename($files['name'][$i]);
            $image_tmp = $files['tmp_name'][$i];
            $target_file = $upload_directory . $image_name;

            // Move uploaded file to the upload directory
            if (!empty($image_name) && !empty($image_tmp)) {
                if (move_uploaded_file($image_tmp, $target_file)) {
                    $uploaded_image_paths[] = $target_file; // Add uploaded image path to array
                }
            }
        }
    }

    return $uploaded_image_paths;
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
    // Sanitize and validate input data
    $doner_name = sanitize_input($_POST['doner_name']);
    $receiver_name = sanitize_input($_POST['receiver_name']);
    $donation_type = sanitize_input($_POST['donation_type']);
    $quantity = sanitize_input($_POST['quantity']);
    $quantity_unit = sanitize_input($_POST['quantity_unit_hidden']);  // Ensure quantity_unit is correctly assigned
    $donation_info = sanitize_input($_POST['donation_info']);
    $expDate = sanitize_input($_POST['expDate']);
    $damage = sanitize_input($_POST['damage']);
    $delivery_date = sanitize_input($_POST['delivary_date']);
    $sealed = sanitize_input($_POST['sealed']);
    $delivery = sanitize_input($_POST['delivery']);

    // Handle file uploads
    $uploaded_image_paths = handle_file_upload($_FILES['files']);

    // Check for empty fields
    if (empty($doner_name) || empty($receiver_name) || empty($donation_type) || empty($quantity) || 
        empty($quantity_unit) || empty($donation_info) || empty($expDate) || empty($damage) || 
        empty($delivery_date) || empty($sealed) || empty($delivery)) {
        $_SESSION['error_message'] = 'Please fill out all the required fields.';
    } else {
        // Format uploaded image paths as a string
        $new_image_paths_str = implode(";", $uploaded_image_paths);

        // Prepare and bind SQL statement
        $sql = "INSERT INTO donations (doner, receiver, food_type, quantity, quantity_unit, expiration_date, damage, donation_info, donation_img, pickup, packaged, donation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $sql);

        // Correct variable bindings
        $pickup = $delivery;
        $packed = $sealed;
        
        mysqli_stmt_bind_param($stmt, "sssssssssss", $donor_email, $_SESSION['receiver_email'], $donation_type, $quantity, $quantity_unit, $expDate, $damage, $donation_info, $new_image_paths_str, $pickup, $packed);

        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Thank You for your Donation');</script>";
            echo "<script>window.location.href='index.php';</script>";
            exit();
        } else {
            $_SESSION['error_message'] = "Error: " . mysqli_error($conn);
        }

        // Close statement and connection
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="website icon" type="image/png" href="images/food-flow-icon.png">
    <title>FoodFlow - Donation Form</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Meddon&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Meddon&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&family=Paprika&family=Tenor+Sans&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.5.2/css/all.css">

    <link rel="stylesheet" href="signup.css">
</head>
<body>
    <h3 class="form-heading">More Info about the donation</h3>
    <div class="back-button" style="position: absolute; margin-left: 300px;">
        <a href="search-foodBank.php" style="color: transparent">
            <i class="fa-duotone fa-arrow-left" style="--fa-primary-color: #17272b; --fa-primary-opacity: .9; --fa-secondary-color: #91c11b; --fa-secondary-opacity: .7; font-size: 30px;"></i>
        </a>
    </div>
    <section class="donation-form">
        <form action="" class="form" method="POST" enctype="multipart/form-data" style="border-left: #536d10 5px solid; padding: 10px; border-radius: 20px;">
            <?php
            if (isset($_SESSION['error_message'])) {
                echo '<center><p style="color: red;">' . $_SESSION['error_message'] . '</p></center>';
                unset($_SESSION['error_message']);
            }
            ?>
            <div class="doner-receiver">
                <div class="input-wrapper">
                    <input type="text" class="input" id="doner_name" name="doner_name" value="<?php echo $doner_name ?>" required>
                    <label class="label" for="doner_name">Donor Name</label>
                </div>
                &nbsp;&nbsp;<i class="fa-regular fa-arrow-right-long-to-line"></i>
                <div class="input-wrapper">
                    <input type="text" class="input" id="receiver_name" name="receiver_name" value="<?php echo $_SESSION['receiver_name'] ?>" required>
                    <label for="receiver_name" class="label">Receiver Name</label>
                </div>
            </div>
            <br>
            <div class="input-wrapper">
                <label for="donation_type">Type of food being donated:</label>
                <div class="checkbox" id="checkbox">
                    <input type="checkbox" name="donation_type" value="non-perishable" id="non-perishable"><label for="non-perishable">Non-Perishable</label>
                    <input type="checkbox" name="donation_type" value="perishable" id="perishable"><label for="perishable">Perishable</label>
                    <input type="checkbox" name="donation_type" value="baby" id="baby"><label for="baby">Baby Food and formula</label>
                    <input type="checkbox" name="donation_type" value="beverages" id="beverages"><label for="beverages">Beverages</label><br><br>
                    <input type="checkbox" name="donation_type" value="snacks" id="snacks"><label for="snacks">Snacks and treats</label>
                </div><br><br>
            </div>
            <div style="display: flex; gap: 30px; align-items: center;align-self: flex-start; ">
                <div class="input-wrapper">
                    <input type="text" class="input" id="quantity" name="quantity" style="border-color: transparent; border-bottom: 1px solid black;" placeholder="" required>
                    <label for="quantity" class="label">Quantity:</label>
                </div>
                <input type="hidden" name="quantity_unit_hidden" id="quantity_unit_hidden">
                <p class="quantity_unit">weight/count/volume</p>
            </div>
            <div class="input-wrapper">
                <label for="additionalValidation"></label>
                <div id="additionalValidation">
                <br>
                <div class="input-wrapper">
                    <input class="input" type="text" id="donation_info" name="donation_info" placeholder="" required>
                    <label class="label" id="info_label" for="donation_info">More Information:</label>
                </div><br><br>
                <div class="input-wrapper">
                    <input class="input" type="date" id="expDate" name="expDate" required>
                    <label class="label" for="expDate">Expiration Date:</label>
                </div><br><br>
                <div class="input-wrapper">
                    <label for="damage">Does the packaging have any damage, leaks, or tampering:</label>
                    <div class="radio" id="radio">
                        <input type="radio" name="damage" value="yes" id="damage_yes">
                        <label for="damage_yes">Yes</label>
                        <input type="radio" name="damage" value="no" id="damage_no">
                        <label for="damage_no">No</label>
                    </div>
                </div><br><br>
            </div>
            <div class="input-wrapper">
                <input class="input" type="date" id="delivary_date" name="delivary_date" required>
                <label for="delivary_date" class="label">Date of delivery/donation:</label>
            </div><br>
            <div class="input-wrapper">
                <label>Is the Food Packaged and Sealed?</label>
                <div class="radio" id="packaged">
                    <input type="radio" name="sealed" value="Yes" id="yes"><label for="yes">Yes </label>
                    <input type="radio" name="sealed" value="No" id="no"><label for="no">No</label>
                </div>
            </div><br>
            <div class="input-wrapper">
                <label>Delivery Option:</label>
                <select name="delivery" id="select" class="select input" autocomplete="off" required>
                    <option value="deliver to food bank">I can deliver the food to the food bank</option>
                    <option value="food bank need to pickup">I need the food bank to arrange for pickup</option>
                </select><br><br><br>
            </div>
            <input type="file" name="files[]" id="files" style="align-self: flex-start;" onchange="previewImages(event)" multiple>
            <div class="preview" id="image-preview" style="align-self: flex-start;"></div>
            <br>
            <input type="submit" id="submit" class="" value="Donate"><br><br>
        </form>
    </section>
    <script>
        // Updating the quantity unit based on the selected donation type
        document.querySelectorAll('input[name="donation_type"]').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                const selectedTypes = Array.from(document.querySelectorAll('input[name="donation_type"]:checked'))
                                            .map(checkbox => checkbox.value);
                const quantityUnitField = document.querySelector('.quantity_unit');
                const select_input = document.querySelector('#quantity_unit_hidden');
                if (selectedTypes.includes('perishable') || selectedTypes.includes('non-perishable')) {
                    quantityUnitField.textContent = 'Weight';
                    select_input.value = 'Weight';
                } else if (selectedTypes.includes('baby')) {
                    quantityUnitField.textContent = 'count/volume';
                    select_input.value = 'count/volume';
                } else if (selectedTypes.includes('beverages')) {
                    quantityUnitField.textContent = 'volume/count';
                    select_input.value = 'volume/count';
                } else if (selectedTypes.includes('snacks')) {
                    quantityUnitField.textContent = 'count';
                    select_input.value = 'count';
                } else {
                    quantityUnitField.textContent = 'weight/count/volume';
                    select_input.value = '';
                }
            });
        });

        // Previewing uploaded images
        function previewImages(event) {
            const preview = document.getElementById('image-preview');
            preview.innerHTML = '';
            const files = event.target.files;
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>
