<?php
session_start();
$error = '';
$doner_name = '';
$phone = '';
$donor_email = '';  // Initialize this variable to avoid the undefined variable warning

// Add debugging for session variables
// echo "<script>console.log('Session data: " . json_encode($_SESSION) . "');</script>";

// Verify user is logged in as a donor
if (isset($_SESSION['type'])) {
    $type = $_SESSION['type'];
    // echo "<script>console.log('User type: " . $type . "');</script>";

    if ($type == 'doner') {
        $donor_email = $_SESSION['email'];
        $doner_name = $_SESSION['name'];
        $receiver_email = $_SESSION['receiver_email'] ?? '';
        $phone = $_SESSION['phone'] ?? '';
        // Debug the data
        // echo "<script>console.log('Donor: " . $donor_email . ", Receiver: " . ($receiver_email ?? 'not set') . "');</script>";
    } else {
        echo "<script>console.log('User not logged in as donor, redirecting...');</script>";
        echo "<script>alert('Please login as a donor');</script>";
        echo "<script>window.location.href='index.php';</script>";
        exit();
    }
} else {
    echo "<script>console.log('No user type found, redirecting...');</script>";
    echo "<script>alert('Please login as a donor');</script>";
    echo "<script>window.location.href='index.php';</script>";
    exit();
}

// Set receiver information from previous page
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['receiver_name'])) {
    $_SESSION['receiver_name'] = $_POST['receiver_name'];
    $_SESSION['receiver_email'] = $_POST['receiver_email'];
    $_SESSION['receiver_address'] = $_POST['receiver_address'];
    $_SESSION['receiver_phone'] = $_POST['receiver_phone'];
    $_SESSION['receiver_distance'] = $_POST['receiver_distance'];

    // echo "<script>console.log('POST data received: " . json_encode($_POST) . "');</script>";
    // echo "<script>console.log('Receiver info set in session');</script>";
}

// Function to sanitize input data
function sanitize_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to handle file uploads with better security
function handle_file_upload($files)
{
    $uploaded_image_paths = array();
    $upload_directory = "uploads/";
    $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
    $max_size = 5 * 1024 * 1024; // 5MB

    echo "<script>console.log('Starting file upload process');</script>";

    // Create directory if it doesn't exist
    if (!file_exists($upload_directory)) {
        mkdir($upload_directory, 0755, true);
        echo "<script>console.log('Created upload directory: $upload_directory');</script>";
    }

    // Check if files are uploaded
    if (isset($files['name']) && is_array($files['name'])) {
        echo "<script>console.log('Number of files: " . count($files['name']) . "');</script>";

        // Loop through each uploaded file
        for ($i = 0; $i < count($files['name']); $i++) {
            $image_name = basename($files['name'][$i]);
            $image_tmp = $files['tmp_name'][$i];
            $file_size = $files['size'][$i];
            $file_ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));

            echo "<script>console.log('Processing file: " . $image_name . ", Size: " . $file_size . ", Extension: " . $file_ext . "');</script>";

            // Validate file
            if (empty($image_name) || empty($image_tmp)) {
                echo "<script>console.log('Empty file name or temp path, skipping');</script>";
                continue;
            }

            // Check file type
            if (!in_array($file_ext, $allowed_types)) {
                echo "<script>console.log('Invalid file type: " . $file_ext . ", skipping');</script>";
                continue;
            }

            // Check file size
            if ($file_size > $max_size) {
                echo "<script>console.log('File too large: " . $file_size . " bytes, max is " . $max_size . " bytes, skipping');</script>";
                continue;
            }

            // Generate unique filename
            $new_filename = uniqid('donation_') . '.' . $file_ext;
            $target_file = $upload_directory . $new_filename;

            echo "<script>console.log('Attempting to move file to: " . $target_file . "');</script>";

            // Move uploaded file to the upload directory
            if (move_uploaded_file($image_tmp, $target_file)) {
                echo "<script>console.log('File uploaded successfully: " . $target_file . "');</script>";
                $uploaded_image_paths[] = $target_file;
            } else {
                echo "<script>console.log('Failed to move uploaded file, error: " . error_get_last()['message'] . "');</script>";
            }
        }
    } else {
        echo "<script>console.log('No files uploaded or not in expected format');</script>";
    }

    echo "<script>console.log('Total successful uploads: " . count($uploaded_image_paths) . "');</script>";
    return $uploaded_image_paths;
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sdp";

echo "<script>console.log('Attempting database connection to: $dbname');</script>";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    echo "<script>console.error('Database connection failed: " . mysqli_connect_error() . "');</script>";
    die("Connection failed: " . mysqli_connect_error());
} else {
    echo "<script>console.log('Database connection successful');</script>";
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_donation'])) {
    echo "<script>console.log('Processing donation form submission');</script>";
    echo "<script>console.log('POST data: " . json_encode($_POST) . "');</script>";

    // Initialize errors array
    $errors = array();

    // Sanitize and validate input data
    $doner_name = sanitize_input($_POST['doner_name'] ?? '');
    $receiver_name = sanitize_input($_POST['receiver_name'] ?? '');

    // Handle donation type (multiple checkboxes)
    $donation_type = '';
    if (isset($_POST['donation_type']) && is_array($_POST['donation_type'])) {
        $donation_type = implode(', ', $_POST['donation_type']);
        echo "<script>console.log('Donation types selected: " . $donation_type . "');</script>";
    } else if (isset($_POST['donation_type'])) {
        $donation_type = sanitize_input($_POST['donation_type']);
        echo "<script>console.log('Single donation type: " . $donation_type . "');</script>";
    } else {
        echo "<script>console.log('No donation type selected');</script>";
    }

    $quantity = sanitize_input($_POST['quantity'] ?? '');
    $quantity_unit = sanitize_input($_POST['quantity_unit_hidden'] ?? '');
    $donation_info = sanitize_input($_POST['donation_info'] ?? '');
    $expDate = sanitize_input($_POST['expDate'] ?? '');
    $damage = sanitize_input($_POST['damage'] ?? '');
    $delivery_date = sanitize_input($_POST['delivary_date'] ?? '');
    $sealed = sanitize_input($_POST['sealed'] ?? '');
    $delivery = sanitize_input($_POST['delivery'] ?? '');

    echo "<script>console.log('Validated form data: {" .
        "doner_name: " . $doner_name . ", " .
        "receiver_name: " . $receiver_name . ", " .
        "quantity: " . $quantity . ", " .
        "quantity_unit: " . $quantity_unit . ", " .
        "expDate: " . $expDate . ", " .
        "delivery_date: " . $delivery_date . ", " .
        "sealed: " . $sealed . ", " .
        "delivery: " . $delivery .
        "}');</script>";

    // Validate required fields
    if (empty($doner_name)) $errors[] = "Donor name is required.";
    if (empty($receiver_name)) $errors[] = "Receiver name is required.";
    if (empty($donation_type)) $errors[] = "Donation type is required.";
    if (empty($quantity)) $errors[] = "Quantity is required.";
    if (empty($quantity_unit)) $errors[] = "Quantity unit is required.";
    if (empty($donation_info)) $errors[] = "Donation information is required.";
    if (empty($expDate)) $errors[] = "Expiration date is required.";
    if (empty($damage)) $errors[] = "Please specify if packaging has damage.";
    if (empty($delivery_date)) $errors[] = "Delivery date is required.";
    if (empty($sealed)) $errors[] = "Please specify if food is packaged and sealed.";
    if (empty($delivery)) $errors[] = "Please select a delivery option.";

    // Validate expiration date is in the future
    $current_date = date('Y-m-d');
    $max_exp_date = date('Y-m-d', strtotime('+1 year'));
    if (!empty($expDate) && $expDate <= $current_date) {
        $errors[] = "Expiration date must be after today.";
        echo "<script>console.log('Validation error: Expiration date must be after today');</script>";
    }
    if (!empty($expDate) && $expDate > $max_exp_date) {
        $errors[] = "Expiration date cannot be more than 1 year from now.";
        echo "<script>console.log('Validation error: Expiration date too far in future');</script>";
    }

    // Validate delivery date is not in the past (allow up to 30 days from now)
    $max_delivery_date = date('Y-m-d', strtotime('+30 days'));
    if (!empty($delivery_date) && $delivery_date < $current_date) {
        $errors[] = "Delivery date cannot be in the past.";
        echo "<script>console.log('Validation error: Delivery date is in the past');</script>";
    }
    if (!empty($delivery_date) && $delivery_date > $max_delivery_date) {
        $errors[] = "Delivery date cannot be more than 30 days from now.";
        echo "<script>console.log('Validation error: Delivery date too far in future');</script>";
    }

    if (!empty($errors)) {
        echo "<script>console.log('Validation errors: " . json_encode($errors) . "');</script>";
    } else {
        echo "<script>console.log('Form validation successful');</script>";
    }

    // Handle file uploads
    echo "<script>console.log('Files data: ', " . (isset($_FILES) ? json_encode(array_keys($_FILES)) : 'none') . ");</script>";
    $uploaded_image_paths = handle_file_upload($_FILES['files'] ?? []);

    // If no errors, proceed with database insertion
    if (empty($errors)) {
        echo "<script>console.log('Proceeding with database insertion');</script>";

        // Format uploaded image paths as a string
        $new_image_paths_str = implode(";", $uploaded_image_paths);
        echo "<script>console.log('Image paths to save: " . $new_image_paths_str . "');</script>";

        // Prepare and bind SQL statement
        $sql = "INSERT INTO donations (doner, receiver, food_type, quantity, quantity_unit, expiration_date, damage, donation_info, donation_img, pickup, packaged, donation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        echo "<script>console.log('SQL query: " . str_replace("'", "\\'", $sql) . "');</script>";

        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            echo "<script>console.log('Statement prepared successfully');</script>";

            mysqli_stmt_bind_param(
                $stmt,
                "sssssssssss",
                $donor_email,
                $receiver_email,
                $donation_type,
                $quantity,
                $quantity_unit,
                $expDate,
                $damage,
                $donation_info,
                $new_image_paths_str,
                $delivery,
                $sealed
            );

            echo "<script>console.log('Parameters bound to statement');</script>";

            if (mysqli_stmt_execute($stmt)) {
                echo "<script>console.log('Database insertion successful');</script>";

                // Clear session variables
                unset($_SESSION['receiver_name']);
                unset($_SESSION['receiver_email']);
                unset($_SESSION['receiver_address']);
                unset($_SESSION['receiver_phone']);
                unset($_SESSION['receiver_distance']);

                echo "<script>console.log('Session variables cleared');</script>";
                echo "<script>showSuccessPopup();</script>";
                exit();
            } else {
                $error = "Error: " . mysqli_error($conn);
                echo "<script>console.error('Database insertion failed: " . str_replace("'", "\\'", mysqli_error($conn)) . "');</script>";
            }

            // Close statement
            mysqli_stmt_close($stmt);
            echo "<script>console.log('Statement closed');</script>";
        } else {
            $error = "Error preparing statement: " . mysqli_error($conn);
            echo "<script>console.error('Statement preparation failed: " . str_replace("'", "\\'", mysqli_error($conn)) . "');</script>";
        }
    } else {
        $error = implode("<br>", $errors);
        echo "<script>console.error('Form has errors: " . str_replace("'", "\\'", $error) . "');</script>";
    }
}

// Close connection
mysqli_close($conn);
echo "<script>console.log('Database connection closed');</script>";

// Add debugging for when the script reaches the end
echo "<script>console.log('PHP script execution completed');</script>";
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

    <style>
        :root {
            --primary-color: #17272b;
            --secondary-color: #ff8a3d;
            --accent-color: #91c11b;
            --text-dark: #333;
            --text-light: #666;
            --border-radius: 8px;
            --transition: all 0.3s ease;
            --font-family: 'Arial', sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-family);
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
            margin: 0;
        }

        /* Form Container */
        .donation-form {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
            padding: 0;
        }

        /* Modern Date Input Styling */
        .donation-form input[type="date"] {
            width: 100%;
            padding: 12px 16px;
            font-size: 1rem;
            border: 2px solid #e0e0e0;
            background-color: white;
            color: var(--text-dark);
            border-radius: var(--border-radius);
            transition: var(--transition);
            height: auto;
            font-family: var(--font-family);
            cursor: pointer;
            position: relative;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2391c11b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3e%3c/rect%3e%3cline x1='16' y1='2' x2='16' y2='6'%3e%3c/line%3e%3cline x1='8' y1='2' x2='8' y2='6'%3e%3c/line%3e%3cline x1='3' y1='10' x2='21' y2='10'%3e%3c/line%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 20px;
            padding-right: 45px;
        }

        .donation-form input[type="date"]:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(145, 193, 27, 0.1);
            background-color: #fafffe;
        }

        .donation-form input[type="date"]:hover {
            border-color: var(--accent-color);
            background-color: #fafffe;
        }

        /* Hide the default calendar icon on webkit browsers */
        .donation-form input[type="date"]::-webkit-calendar-picker-indicator {
            opacity: 0;
            position: absolute;
            right: 12px;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        /* Style the date input when it has a value */
        .donation-form input[type="date"]:valid {
            color: var(--text-dark);
            font-weight: 500;
        }

        /* Placeholder styling for empty date inputs */
        .donation-form input[type="date"]:invalid {
            color: #aaa;
        }

        /* Firefox specific styling */
        @-moz-document url-prefix() {
            .donation-form input[type="date"] {
                background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2391c11b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3e%3c/rect%3e%3cline x1='16' y1='2' x2='16' y2='6'%3e%3c/line%3e%3cline x1='8' y1='2' x2='8' y2='6'%3e%3c/line%3e%3cline x1='3' y1='10' x2='21' y2='10'%3e%3c/line%3e%3c/svg%3e");
            }
        }

        /* Enhanced styling for better visual appeal */
        .donation-form .input-wrapper:has(input[type="date"]) {
            position: relative;
        }

        .donation-form .input-wrapper:has(input[type="date"])::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: var(--border-radius);
            background: linear-gradient(135deg, transparent 0%, rgba(145, 193, 27, 0.02) 100%);
            pointer-events: none;
            z-index: 0;
            opacity: 0;
            transition: var(--transition);
        }

        .donation-form .input-wrapper:has(input[type="date"]:focus)::before {
            opacity: 1;
        }

        /* Make sure the input stays above the pseudo-element */
        .donation-form input[type="date"] {
            position: relative;
            z-index: 1;
        }

        /* Additional modern touches */
        .donation-form input[type="date"]::placeholder {
            color: #aaa;
            opacity: 1;
            font-style: italic;
        }

        /* Form heading */
        .form-heading {
            font-family: var(--font-family);
            background: linear-gradient(135deg, var(--primary-color), #2c3e50);
            color: white;
            text-align: center;
            margin: 0;
            padding: 30px 20px;
            font-size: 2.2rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        /* Back button */
        .back-button {
            position: absolute;
            top: 30px;
            left: 30px;
            z-index: 10;
        }

        .back-button a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transition: var(--transition);
            backdrop-filter: blur(10px);
            text-decoration: none;
        }

        .back-button a:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        /* Main form container styling */
        .donation-form {
            padding: 0;
        }

        /* Form content wrapper */
        /* .form-content-wrapper {
            padding: 40px;
            padding-top: 0;
        } */

        /* Error message styling */
        .error-message {
            background-color: #ffe6e6;
            border-left: 4px solid #e74c3c;
            color: #c0392b;
            padding: 15px 20px;
            margin-bottom: 30px;
            border-radius: 5px;
            font-weight: 500;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Form styling */
        .form {
            border-left: 5px solid var(--accent-color) !important;
            background: #fafafa;
            padding: 30px !important;
            border-radius: 20px !important;
            transition: box-shadow 0.3s ease;
        }

        .form:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        /* Doner-receiver section */
        .doner-receiver {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            width: 100%;
            margin-top: 0;
            margin-bottom: 30px;
            position: relative;
        }

        .doner-receiver .input-wrapper {
            flex: 1;
            position: relative;
            margin-bottom: 0;
        }

        /* Labels - proper positioning and visibility */
        .donation-form .label {
            display: block !important;
            position: static !important;
            visibility: visible !important;
            opacity: 1 !important;
            transform: none !important;
            background: transparent !important;
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.95rem;
            z-index: auto;
            padding: 0;
        }

        /* Specific fixes for donor and recipient labels */
        .donation-form label[for="receiver_name"],
        .donation-form label[for="doner_name"] {
            display: block !important;
            position: static !important;
            visibility: visible !important;
            opacity: 1 !important;
            transform: none !important;
            background: transparent !important;
            font-weight: 600;
            margin-bottom: 8px;
            z-index: auto;
        }

        /* Arrow styling */
        .fa-arrow-right-long-to-line {
            color: var(--accent-color);
            font-size: 1.8rem;
            display: block;
            margin: 0 15px;
            flex-shrink: 0;
        }

        /* Input styling */
        .donation-form .input {
            width: 100%;
            padding: 12px 16px;
            font-size: 1rem;
            border: 2px solid #e0e0e0;
            background-color: white;
            color: var(--text-dark);
            border-radius: var(--border-radius);
            transition: var(--transition);
            height: auto;
        }

        .donation-form .input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(145, 193, 27, 0.1);
        }

        /* Remove problematic placeholder and label transitions */
        .donation-form .input::placeholder {
            color: #aaa;
            opacity: 1;
        }

        /* Input wrapper styling */
        .input-wrapper {
            position: relative;
            margin-bottom: 25px;
        }

        /* Checkbox and radio styling - Use existing signup.css styles */
        .donation-form .checkbox,
        .donation-form .radio {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }

        /* Let signup.css handle the radio and checkbox styling - minimal overrides only */
        .donation-form .checkbox label,
        .donation-form .radio label {
            cursor: pointer;
            transition: var(--transition);
        }

        /* Quantity section styling */
        .quantity-section {
            display: flex;
            gap: 20px;
            align-items: end;
            margin-bottom: 25px;
            flex-wrap: wrap;
            justify-content: flex-start;
        }

        .quantity-section .input-wrapper {
            flex: 1;
            min-width: 200px;
            margin-bottom: 0;
        }

        /* Quantity unit display */
        .quantity_unit {
            background: linear-gradient(135deg, #e8f5e8, #d4edda);
            border: 2px solid var(--accent-color);
            padding: 12px 20px;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            color: var(--primary-color);
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
        }

        /* Select dropdown styling */
        .select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2391c11b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 16px;
            padding-right: 50px;
        }

        /* File input styling */
        input[type="file"] {
            border: 2px dashed #ccc;
            padding: 20px;
            border-radius: var(--border-radius);
            width: 100%;
            margin-top: 5px;
            background: #fafafa;
            transition: var(--transition);
        }

        input[type="file"]:hover {
            border-color: var(--accent-color);
            background: #f0f8f0;
        }

        /* Image preview section */
        .image-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        /* Labels for required fields */
        .required-field {
            font-weight: 600;
            color: var(--primary-color);
        }

        .required-field::after {
            content: ' *';
            color: var(--secondary-color);
            font-weight: bold;
        }

        input[type="checkbox"]:checked+.input-label,
        input[type="radio"]:checked+.input-label {
            color: white;
        }


        /* Submit button enhancement */
        input[type="submit"] {
            background: linear-gradient(135deg, var(--accent-color), #7aa116);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 20px;
        }

        input[type="submit"]:hover {
            background: linear-gradient(135deg, #7aa116, var(--accent-color));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(145, 193, 27, 0.3);
        }

        /* Small text styling */
        small {
            color: var(--text-light);
            font-size: 0.85rem;
            display: block;
            margin-top: 5px;
        }

        /* Success Popup Styling */
        .success-popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            backdrop-filter: blur(5px);
        }

        .success-popup-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 90%;
            animation: popupSlideIn 0.3s ease-out;
        }

        @keyframes popupSlideIn {
            from {
                opacity: 0;
                transform: translate(-50%, -60%);
            }

            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: var(--accent-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: white;
        }

        .success-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .success-message {
            color: var(--text-light);
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .success-button {
            background: linear-gradient(135deg, var(--accent-color), #7aa116);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .success-button:hover {
            background: linear-gradient(135deg, #7aa116, var(--accent-color));
            transform: translateY(-2px);
        }

        /* Image preview with remove buttons */
        .image-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .image-container {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e0e0e0;
        }

        .preview-image {
            width: 100%;
            height: 100px;
            object-fit: cover;
            display: block;
        }

        .remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(231, 76, 60, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .remove-image:hover {
            background: rgba(231, 76, 60, 1);
            transform: scale(1.1);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .doner-receiver {
                flex-direction: column;
                gap: 15px;
            }

            .doner-receiver .input-wrapper {
                width: 100%;
            }

            .fa-arrow-right-long-to-line {
                transform: rotate(90deg);
                margin: 10px 0;
            }

            .quantity-section {
                flex-direction: column;
                align-items: stretch;
            }

            .quantity_unit {
                order: -1;
                text-align: center;
            }

            .donation-form .checkbox,
            .donation-form .radio {
                flex-direction: column;
            }

            .back-button {
                top: 20px;
                left: 20px;
            }

            /* .form-content-wrapper {
                padding: 20px;
            } */

            .form-heading {
                font-size: 1.8rem;
                padding: 20px 15px;
            }
        }

        @media (max-width: 480px) {

            .donation-form .checkbox,
            .donation-form .radio {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>

<body>


    <section class="donation-form">
        <h3 class="form-heading">Donation Information</h3>
        <div class="back-button">
            <a href="search-foodBank.php">
                <i class="fa-duotone fa-arrow-left" style="--fa-primary-color: #ffffff; --fa-primary-opacity: .9; --fa-secondary-color: #ffffff; --fa-secondary-opacity: .7; font-size: 20px;"></i>
            </a>
        </div>
        <div class="form-content-wrapper">
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="form" method="POST" enctype="multipart/form-data">
                <div class="doner-receiver">
                    <div class="input-wrapper">
                        <label class="label required-field" for="doner_name">Donor Name</label>
                        <input type="text" class="input" id="doner_name" name="doner_name" value="<?php echo $doner_name; ?>" required>
                    </div>
                    &nbsp;&nbsp;<i class="fa-regular fa-arrow-right-long-to-line" style="margin-top: 50px; margin-left: -20px;"></i>
                    <div class="input-wrapper">
                        <label for="receiver_name" class="label required-field">Recipient Name</label>
                        <input type="text" class="input" id="receiver_name" name="receiver_name"
                            value="<?php echo isset($_SESSION['receiver_name']) ? $_SESSION['receiver_name'] : ''; ?>" required>
                    </div>
                </div>

                <div class="input-wrapper" style="margin-bottom: -10px;">
                    <label for="donation_type" class="required-field" style="font-size: 0.95rem;">Type of food being donated:</label>
                    <div class="checkbox" id="checkbox">
                        <input type="checkbox" name="donation_type[]" value="non-perishable" id="non-perishable" <?php if (isset($_POST['donation_type']) && in_array('non-perishable', $_POST['donation_type'])) echo 'checked'; ?>>
                        <label class="input-label" for="non-perishable">Non-Perishable</label>

                        <input type="checkbox" name="donation_type[]" value="perishable" id="perishable" <?php if (isset($_POST['donation_type']) && in_array('perishable', $_POST['donation_type'])) echo 'checked'; ?>>
                        <label class="input-label" for="perishable">Perishable</label>

                        <input type="checkbox" name="donation_type[]" value="baby" id="baby" <?php if (isset($_POST['donation_type']) && in_array('baby', $_POST['donation_type'])) echo 'checked'; ?>>
                        <label class="input-label" for="baby">Baby Food and Formula</label>

                        <input type="checkbox" name="donation_type[]" value="beverages" id="beverages" <?php if (isset($_POST['donation_type']) && in_array('beverages', $_POST['donation_type'])) echo 'checked'; ?>>
                        <label class="input-label" for="beverages">Beverages</label><br><br>

                        <input type="checkbox" name="donation_type[]" value="snacks" id="snacks" <?php if (isset($_POST['donation_type']) && in_array('snacks', $_POST['donation_type'])) echo 'checked'; ?>>
                        <label class="input-label" for="snacks">Snacks and Treats</label>
                    </div><br><br>
                </div>

                <div style="display: flex; gap: 30px; align-items: center; align-self: flex-start; ">
                    <div class="input-wrapper">
                        <label for="quantity" class="label required-field">Quantity:</label>
                        <input type="text" class="input" id="quantity" name="quantity" style="margin-top: 10px;" value="<?php echo isset($_POST['quantity']) ? $_POST['quantity'] : ''; ?>" required>
                    </div>
                    <input type="hidden" name="quantity_unit_hidden" id="quantity_unit_hidden" value="<?php echo isset($_POST['quantity_unit_hidden']) ? $_POST['quantity_unit_hidden'] : ''; ?>">
                    <p class="quantity_unit">weight/count/volume</p>
                </div>

                <div class="input-wrapper">
                    <div id="additionalValidation">
                        <div class="input-wrapper">
                            <label class="label required-field" id="info_label" for="donation_info">More Information:</label>
                            <input class="input" type="text" id="donation_info" name="donation_info" value="<?php echo isset($_POST['donation_info']) ? $_POST['donation_info'] : ''; ?>" required>
                        </div>

                        <div class="input-wrapper">
                            <label class="label required-field" for="expDate">Expiration Date:</label>
                            <input class="input" type="date" id="expDate" name="expDate" value="<?php echo isset($_POST['expDate']) ? $_POST['expDate'] : ''; ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" max="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" required>
                        </div>

                        <div class="input-wrapper">
                            <label for="damage" class="required-field" style="font-size: 0.95rem;">Does the packaging have any damage, leaks, or tampering:</label>
                            <div class="radio" id="radio">
                                <input type="radio" name="damage" value="yes" id="damage_yes" <?php if (isset($_POST['damage']) && $_POST['damage'] == 'yes') echo 'checked'; ?> required>
                                <label for="damage_yes" class="input-label">Yes</label>
                                <input type="radio" name="damage" value="no" id="damage_no" <?php if (isset($_POST['damage']) && $_POST['damage'] == 'no') echo 'checked'; ?>>
                                <label for="damage_no" class="input-label">No</label>
                            </div>
                        </div>
                    </div>

                    <div class="input-wrapper">
                        <label for="delivary_date" class="label required-field">Date of delivery/donation:</label>
                        <input class="input" type="date" id="delivary_date" name="delivary_date" value="<?php echo isset($_POST['delivary_date']) ? $_POST['delivary_date'] : ''; ?>" min="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                    </div>

                    <div class="input-wrapper">
                        <label class="required-field" style="font-size: 0.95rem;">Is the Food Packaged and Sealed?</label>
                        <div class="radio" id="packaged">
                            <input type="radio" name="sealed" value="Yes" id="yes" <?php if (isset($_POST['sealed']) && $_POST['sealed'] == 'Yes') echo 'checked'; ?> required>
                            <label for="yes" class="input-label">Yes </label>
                            <input type="radio" name="sealed" value="No" id="no" <?php if (isset($_POST['sealed']) && $_POST['sealed'] == 'No') echo 'checked'; ?>>
                            <label for="no" class="input-label">No</label>
                        </div>
                    </div>

                    <div class="input-wrapper">
                        <label class="required-field">Delivery Option:</label>
                        <select name="delivery" id="select" class="select input" autocomplete="off" required>
                            <option value="" disabled selected>Select delivery option</option>
                            <option value="deliver to food bank" <?php if (isset($_POST['delivery']) && $_POST['delivery'] == 'deliver to food bank') echo 'selected'; ?>>I can deliver the food to the food bank</option>
                            <option value="food bank need to pickup" <?php if (isset($_POST['delivery']) && $_POST['delivery'] == 'food bank need to pickup') echo 'selected'; ?>>I need the food bank to arrange for pickup</option>
                        </select>
                    </div>

                    <div class="input-wrapper">
                        <label for="files">Upload Photos of Donation (Optional):</label>
                        <input type="file" name="files[]" id="files" onchange="previewImages(event)" multiple accept="image/*">
                        <small>Max 5MB per file. Allowed types: JPG, JPEG, PNG, GIF</small>
                        <div class="image-preview" id="image-preview"></div>
                    </div>

                    <input type="submit" id="submit" name="submit_donation" value="Donate">
                </div>
            </form>
        </div>
    </section>

    <div id="successPopup" class="success-popup">
        <div class="success-popup-content">
            <div class="success-icon">
                <i class="fa-solid fa-check"></i>
            </div>
            <h3 class="success-title">Donation Successful!</h3>
            <p class="success-message">Thank you for your generous donation. Your contribution will help those in need.</p>
            <button class="success-button" onclick="redirectToProfile()">Go to Profile</button>
        </div>
    </div>

    <script>
        // Debug utility function
        function debug(message, data = null) {
            if (data) {
                console.log(`[DEBUG] ${message}`, data);
            } else {
                console.log(`[DEBUG] ${message}`);
            }
        }

        // Log when page loads
        document.addEventListener('DOMContentLoaded', function() {
            debug('Page loaded and DOM ready');

            // Debug form elements
            debug('Form found', document.querySelector('form') ? true : false);
            debug('Donation type checkboxes found', document.querySelectorAll('input[name="donation_type[]"]').length);

            // Run once on page load to set initial values
            updateQuantityUnit();
            debug('Initial quantity unit set');

            // Add event listeners to checkboxes
            const checkboxes = document.querySelectorAll('input[name="donation_type[]"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    debug(`Checkbox changed: ${this.value}, checked: ${this.checked}`);
                    updateQuantityUnit();
                });
            });

            // Add form submission debugging
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    debug('Form submission attempted');

                    // Let the validation function handle the rest
                });
            }

            debug('All event listeners attached');
        });

        // Function to update quantity unit
        function updateQuantityUnit() {
            const selectedTypes = Array.from(document.querySelectorAll('input[name="donation_type[]"]:checked'))
                .map(checkbox => checkbox.value);
            const quantityUnitField = document.querySelector('.quantity_unit');
            const hiddenInput = document.querySelector('#quantity_unit_hidden');

            debug('Selected donation types', selectedTypes);

            if (selectedTypes.includes('perishable') || selectedTypes.includes('non-perishable')) {
                quantityUnitField.textContent = 'Weight';
                hiddenInput.value = 'Weight';
            } else if (selectedTypes.includes('baby')) {
                quantityUnitField.textContent = 'count/volume';
                hiddenInput.value = 'count/volume';
            } else if (selectedTypes.includes('beverages')) {
                quantityUnitField.textContent = 'volume/count';
                hiddenInput.value = 'volume/count';
            } else if (selectedTypes.includes('snacks')) {
                quantityUnitField.textContent = 'count';
                hiddenInput.value = 'count';
            } else {
                quantityUnitField.textContent = 'weight/count/volume';
                hiddenInput.value = '';
            }

            debug('Quantity unit updated to', hiddenInput.value);
        }

        // Function to preview images before upload
        let selectedFiles = []; // Global array to track selected files

        function previewImages(event) {
            debug('Image preview requested');
            const files = Array.from(event.target.files);
            debug('Files selected', files.length);

            const maxFiles = 5;
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            const maxSize = 5 * 1024 * 1024; // 5MB

            // Validate and add new files to selectedFiles array
            files.forEach((file, index) => {
                debug(`Processing file ${index+1}/${files.length}: ${file.name}, type: ${file.type}, size: ${file.size}`);

                // Check if we've reached max files
                if (selectedFiles.length >= maxFiles) {
                    debug(`Maximum ${maxFiles} files allowed`);
                    alert(`Maximum ${maxFiles} files allowed`);
                    return;
                }

                // Check file type
                if (!allowedTypes.includes(file.type)) {
                    debug(`Invalid file type: ${file.type} for ${file.name}`);
                    alert('File type not allowed: ' + file.name + '. Only JPG, JPEG, PNG, and GIF are allowed.');
                    return;
                }

                // Check file size
                if (file.size > maxSize) {
                    debug(`File too large: ${file.size} bytes for ${file.name}`);
                    alert('File too large: ' + file.name + '. Maximum size is 5MB.');
                    return;
                }

                // Add file to selectedFiles array
                const fileObj = {
                    file: file,
                    id: Date.now() + Math.random() // Unique ID
                };
                selectedFiles.push(fileObj);
            });

            // Update the file input with current selected files
            updateFileInput();

            // Render preview
            renderImagePreview();
        }

        function updateFileInput() {
            const fileInput = document.getElementById('files');
            const dt = new DataTransfer();

            selectedFiles.forEach(fileObj => {
                dt.items.add(fileObj.file);
            });

            fileInput.files = dt.files;
        }

        function renderImagePreview() {
            const preview = document.getElementById('image-preview');
            preview.innerHTML = '';

            selectedFiles.forEach((fileObj, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    debug(`File ${fileObj.file.name} loaded successfully`);

                    const container = document.createElement('div');
                    container.className = 'image-container';

                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = fileObj.file.name;
                    img.title = fileObj.file.name;
                    img.className = 'preview-image';

                    const removeBtn = document.createElement('button');
                    removeBtn.innerHTML = '×';
                    removeBtn.className = 'remove-image';
                    removeBtn.type = 'button';
                    removeBtn.onclick = () => removeImage(fileObj.id);

                    container.appendChild(img);
                    container.appendChild(removeBtn);
                    preview.appendChild(container);

                    debug('Preview image added to DOM');
                };
                reader.onerror = function(e) {
                    debug(`Error reading file ${fileObj.file.name}`, e);
                };
                reader.readAsDataURL(fileObj.file);
            });
        }

        function removeImage(fileId) {
            debug('Removing image with ID:', fileId);
            selectedFiles = selectedFiles.filter(fileObj => fileObj.id !== fileId);
            updateFileInput();
            renderImagePreview();
            debug('Image removed, remaining files:', selectedFiles.length);
        }

        // Add success popup functions (add new functions)
        function showSuccessPopup() {
            debug('Showing success popup');
            const popup = document.getElementById('successPopup');
            if (popup) {
                popup.style.display = 'block';
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
            }
        }

        function redirectToProfile() {
            debug('Redirecting to profile page');
            document.body.style.overflow = 'auto'; // Restore scrolling
            window.location.href = 'profile.php';
        }

        // Form validation (continued)
        document.querySelector('form').addEventListener('submit', function(e) {
            debug('Form submission validation started');
            let hasErrors = false;

            // Check if at least one donation type is selected
            const donationTypes = document.querySelectorAll('input[name="donation_type[]"]:checked');
            if (donationTypes.length === 0) {
                debug('Error: No donation type selected');
                alert('Please select at least one donation type');
                e.preventDefault();
                hasErrors = true;
                return;
            }

            // Validate quantity is a positive number
            const quantity = document.getElementById('quantity').value.trim();
            debug('Validating quantity:', quantity);
            if (isNaN(quantity) || parseFloat(quantity) <= 0) {
                debug('Error: Invalid quantity value');
                alert('Please enter a valid positive number for quantity');
                e.preventDefault();
                hasErrors = true;
                return;
            }

            // Validate expiration date is in the future
            const expDate = new Date(document.getElementById('expDate').value);
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(today.getDate() + 1);
            const maxExpDate = new Date(today);
            maxExpDate.setFullYear(today.getFullYear() + 1);

            today.setHours(0, 0, 0, 0);
            tomorrow.setHours(0, 0, 0, 0);
            expDate.setHours(0, 0, 0, 0);
            maxExpDate.setHours(0, 0, 0, 0);

            debug('Validating expiration date:', expDate, 'Tomorrow:', tomorrow, 'Max date:', maxExpDate);

            if (expDate <= today) {
                debug('Error: Expiration date must be after today');
                alert('Expiration date must be after today');
                e.preventDefault();
                hasErrors = true;
                return;
            }

            if (expDate > maxExpDate) {
                debug('Error: Expiration date too far in future');
                alert('Expiration date cannot be more than 1 year from now');
                e.preventDefault();
                hasErrors = true;
                return;
            }

            // Validate delivery date is not in the past
            // Validate delivery date is today or future, but within 30 days
            const deliveryDate = new Date(document.getElementById('delivary_date').value);
            const maxDeliveryDate = new Date(today);
            maxDeliveryDate.setDate(today.getDate() + 30);

            deliveryDate.setHours(0, 0, 0, 0);
            maxDeliveryDate.setHours(0, 0, 0, 0);

            debug('Validating delivery date:', deliveryDate, 'Today:', today, 'Max delivery date:', maxDeliveryDate);

            if (deliveryDate < today) {
                debug('Error: Delivery date is in the past');
                alert('Delivery date cannot be in the past');
                e.preventDefault();
                hasErrors = true;
                return;
            }

            if (deliveryDate > maxDeliveryDate) {
                debug('Error: Delivery date too far in future');
                alert('Delivery date cannot be more than 30 days from now');
                e.preventDefault();
                hasErrors = true;
                return;
            }
            // Check if damage option is selected
            const damageOptions = document.querySelectorAll('input[name="damage"]:checked');
            debug('Damage selection:', damageOptions.length > 0);
            if (damageOptions.length === 0) {
                debug('Error: No damage option selected');
                alert('Please specify if the packaging has any damage');
                e.preventDefault();
                hasErrors = true;
                return;
            }

            // Check if sealed option is selected
            const sealedOptions = document.querySelectorAll('input[name="sealed"]:checked');
            debug('Sealed selection:', sealedOptions.length > 0);
            if (sealedOptions.length === 0) {
                debug('Error: No sealed option selected');
                alert('Please specify if the food is packaged and sealed');
                e.preventDefault();
                hasErrors = true;
                return;
            }

            // Check if delivery option is selected
            const deliveryOption = document.getElementById('select').value;
            debug('Delivery option selected:', deliveryOption);
            if (!deliveryOption) {
                debug('Error: No delivery option selected');
                alert('Please select a delivery option');
                e.preventDefault();
                hasErrors = true;
                return;
            }

            // Validate donation info is not empty
            const donationInfo = document.getElementById('donation_info').value.trim();
            debug('Donation info:', donationInfo);
            if (!donationInfo) {
                debug('Error: No donation info provided');
                alert('Please provide information about your donation');
                e.preventDefault();
                hasErrors = true;
                return;
            }

            if (!hasErrors) {
                debug('Form validation successful, submitting form');
            }
        });

        // Add file input change event listener for debugging
        document.getElementById('files').addEventListener('change', function(e) {
            debug('Files selected:', e.target.files.length);

            // Log each file's details
            if (e.target.files.length > 0) {
                for (let i = 0; i < e.target.files.length; i++) {
                    const file = e.target.files[i];
                    debug(`File ${i+1}: ${file.name}, type: ${file.type}, size: ${file.size} bytes`);
                }
            }
        });

        // Debug any potential issues with the date inputs
        const expDateInput = document.getElementById('expDate');
        const deliveryDateInput = document.getElementById('delivary_date');

        if (expDateInput) {
            expDateInput.addEventListener('change', function() {
                debug('Expiration date changed to:', this.value);
                // Check if date is valid
                const selectedDate = new Date(this.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                if (selectedDate < today) {
                    debug('Warning: Selected expiration date is in the past');
                }
            });
        }

        if (deliveryDateInput) {
            deliveryDateInput.addEventListener('change', function() {
                debug('Delivery date changed to:', this.value);
                // Check if date is valid
                const selectedDate = new Date(this.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                if (selectedDate < today) {
                    debug('Warning: Selected delivery date is in the past');
                }
            });
        }

        // Debug donor and receiver names
        const donerNameInput = document.getElementById('doner_name');
        const receiverNameInput = document.getElementById('receiver_name');

        if (donerNameInput) {
            debug('Initial donor name:', donerNameInput.value);
            donerNameInput.addEventListener('input', function() {
                debug('Donor name changed to:', this.value);
            });
        }

        if (receiverNameInput) {
            debug('Initial receiver name:', receiverNameInput.value);
            receiverNameInput.addEventListener('input', function() {
                debug('Receiver name changed to:', this.value);
            });
        }

        // Debug form submission process
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function() {
                debug('Form submitted');

                // Log all form values for debugging
                const formData = new FormData(this);
                const formValues = {};
                for (let pair of formData.entries()) {
                    formValues[pair[0]] = pair[1];
                }
                debug('Form values:', formValues);
            });
        }

        // Initial debug of session data if available
        debug('Page URL:', window.location.href);
        debug('User agent:', navigator.userAgent);

        // Debug any console errors that might occur
        window.onerror = function(message, source, lineno, colno, error) {
            debug('JavaScript error occurred:', {
                message,
                source,
                lineno,
                colno,
                error: error ? error.stack : 'No stack trace'
            });
            return false; // Let default error handling continue
        };

        // Debug AJAX requests if any
        (function(open) {
            XMLHttpRequest.prototype.open = function() {
                this.addEventListener('load', function() {
                    debug('AJAX request completed:', {
                        url: this._url,
                        status: this.status,
                        responseType: this.responseType
                    });
                });
                this.addEventListener('error', function() {
                    debug('AJAX request failed:', {
                        url: this._url
                    });
                });
                this._url = arguments[1];
                open.apply(this, arguments);
            };
        })(XMLHttpRequest.prototype.open);
    </script>
</body>

</html>