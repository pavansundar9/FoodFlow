<?php
session_start();

// Check if the user is logged in as a donor
if (!isset($_SESSION['type']) || $_SESSION['type'] !== 'doner') {
    echo "<script>alert('Please login as a donor');</script>";
    echo "<script>window.location.href='index.php';</script>";
    exit();
}

// Initialize donor session variables
$donor_email = $_SESSION['email'];
$receiver_email = $_SESSION['receiver_email'];
$doner_name = $_SESSION['name'];
$phone = $_SESSION['c_number'];

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
    $delivery_date = sanitize_input($_POST['delivery_date']);
    $sealed = sanitize_input($_POST['sealed']);
    $delivery = sanitize_input($_POST['delivery']);

    // Debugging console logs
    echo "<script>console.log('Receiver Name: $receiver_name');</script>";
    echo "<script>console.log('Donation Type: $donation_type');</script>";
    echo "<script>console.log('Quantity: $quantity');</script>";
    echo "<script>console.log('Quantity Unit: $quantity_unit');</script>";
    echo "<script>console.log('Donation Info: $donation_info');</script>";
    echo "<script>console.log('Expiration Date: $expDate');</script>";
    echo "<script>console.log('Damage: $damage');</script>";
    echo "<script>console.log('Delivery Date: $delivery_date');</script>";
    echo "<script>console.log('Sealed: $sealed');</script>";
    echo "<script>console.log('Delivery: $delivery');</script>";

    // Handle file uploads
    $uploaded_image_paths = handle_file_upload($_FILES['files']);

    // Check for empty fields
    if (empty($doner_name) || empty($receiver_name) || empty($donation_type) || empty($quantity) || 
        empty($quantity_unit) || empty($donation_info) || empty($expDate) || empty($damage) || 
        empty($delivery_date) || empty($sealed) || empty($delivery)) {
        $_SESSION['error_message'] = 'Please fill out all the required fields.';
        header('Location: donationform.php');
        exit();
    } else {
        // Format uploaded image paths as a string
        $new_image_paths_str = implode(";", $uploaded_image_paths);
        echo "<script>console.log('Uploaded Image Paths: $new_image_paths_str');</script>";

        // Prepare and bind SQL statement
        $sql = "INSERT INTO donations (doner, receiver, food_type, quantity, quantity_unit, expiration_date, damage, donation_info, donation_img, pickup, packaged, donation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $sql);

        // Correct variable bindings
        $pickup = $delivery;
        $packed = $sealed;
        
        mysqli_stmt_bind_param($stmt, "sssssssssss", $donor_email, $receiver_email, $donation_type, $quantity, $quantity_unit, $expDate, $damage, $donation_info, $new_image_paths_str, $pickup, $packed);

        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Thank You for your Donation');</script>";
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error: " . mysqli_error($conn);
            header("Location: donationform.php");
            exit();
        }

        // Close statement and connection
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
    }
}
?>
