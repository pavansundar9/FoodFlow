<?php
session_start();
$error = '';
$doner_name = '';
$phone = '';
$donor_email = '';  // Initialize this variable to avoid the undefined variable warning

// Add debugging for session variables
echo "<script>console.log('Session data: " . json_encode($_SESSION) . "');</script>";

// Verify user is logged in as a donor
if (isset($_SESSION['type'])) {
    $type = $_SESSION['type'];
    echo "<script>console.log('User type: " . $type . "');</script>";

    if ($type == 'doner') {
        $donor_email = $_SESSION['email'];
        $doner_name = $_SESSION['name'];
        $receiver_email = $_SESSION['receiver_email'] ?? '';
        $phone = $_SESSION['phone'] ?? '';
        // Debug the data
        echo "<script>console.log('Donor: " . $donor_email . ", Receiver: " . ($receiver_email ?? 'not set') . "');</script>";
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

    echo "<script>console.log('POST data received: " . json_encode($_POST) . "');</script>";
    echo "<script>console.log('Receiver info set in session');</script>";
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
    if (!empty($expDate) && $expDate < $current_date) {
        $errors[] = "Expiration date must be in the future.";
        echo "<script>console.log('Validation error: Expiration date is in the past');</script>";
    }

    // Validate delivery date is not in the past
    if (!empty($delivery_date) && $delivery_date < $current_date) {
        $errors[] = "Delivery date must be today or in the future.";
        echo "<script>console.log('Validation error: Delivery date is in the past');</script>";
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
                echo "<script>alert('Thank You for your Donation');</script>";
                echo "<script>window.location.href='index.php';</script>";
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
        /* .error-message {
            color: #e74c3c;
            font-weight: bold;
            margin-bottom: 15px;
            text-align: center;
        }
        .recipient-info {
            background-color: #f8f9fa;
            border-left: 4px solid #91c11b;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .recipient-info h4 {
            margin: 0 0 5px 0;
            color: #536d10;
        }
        .recipient-info p {
            margin: 5px 0;
        }
        .back-button {
            position: absolute;
            margin-left: 300px;
        }
        .back-button a {
            color: transparent;
        }
        .image-preview img {
            max-width: 100px;
            max-height: 100px;
            margin: 5px;
            border: 1px solid #ddd;
            padding: 3px;
            border-radius: 5px;
        }

        .required-field::after {
            content: ' *';
            color: red;
        } */

        /* Donation Form Specific Styles */
        .donation-form {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color:#c0392b;
        }

        /* Form heading */
        .form-heading {
            font-family: var(--font-family);
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 20px;
            font-size: 2rem;
            position: relative;
            padding-bottom: 10px;
        }

        .form-heading:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background-color: var(--secondary-color);
        }

        /* Doner-receiver section */
        .doner-receiver {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
            margin-top: 2rem;
            position: relative;
            /* Ensure positioning context for the arrow */
        }

        .doner-receiver .input-wrapper {
            flex: 1;
            position: relative;
        }

        /* Fix for label visibility - ensure labels appear above inputs */
        .donation-form .label {
            position: absolute;
            top: -10px;
            /* Move label above the input */
            left: 10px;
            color: var(--primary-color);
            background-color: white;
            padding: 0 5px;
            font-size: 0.9rem;
            z-index: 2;
            pointer-events: none;
        }

        /* Arrow styling fix */
        .fa-arrow-right-long-to-line {
            color: var(--primary-color);
            font-size: 1.5rem;
            display: block;
            margin: 0 10px;
        }

        /* Input styling */
        .donation-form .input {
            padding-top: 15px;
            /* Add padding to accommodate the label */
            font-size: 1rem;
            border: 1px solid #ccc;
            background-color: transparent;
            color: var(--text-dark);
            border-radius: 5px;
            transition: var(--transition);
            width: 100%;
            height: 50px;
            /* Increase height to ensure text is visible */
        }

        /* Override the CSS that's hiding labels */
        .donation-form .input::placeholder {
            color: #aaa;
            opacity: 1;
        }

        .donation-form .input:focus+.label,
        .donation-form .input:not(:placeholder-shown)+.label {
            transform: none;
            font-size: 0.9rem;
            background-color: white;
            padding: 0 5px;
            top: -10px;
            /* Keep label above input when focused */
        }

        /* Checkbox and radio styling specific to donation form */
        .donation-form .checkbox,
        .donation-form .radio {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .donation-form .checkbox label,
        .donation-form .radio label {
            min-width: 120px;
            text-align: center;
        }

        /* Additional validation section */
        /* #additionalValidation {
            width: 100%;
            background-color: #fafafa;
            border-radius: var(--border-radius);
            padding: 15px;
            margin-bottom: 20px;
        } */

        /* Image preview section */
        .image-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        /* Select dropdown styling */
        .select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
            padding-right: 2.5rem;
        }

        /* Quantity unit display */
        .quantity_unit {
            background-color: #f0f0f0;
            padding: 8px 15px;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            color: var(--primary-color);
            width: auto;
            text-align: center;
        }

        /* File input styling */
        input[type="file"] {
            border: 1px dashed #ccc;
            padding: 10px;
            border-radius: var(--border-radius);
            width: 100%;
            margin-top: 5px;
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

        /* Back button position */
        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 10;
        }

        /* Submit button enhancement */
        input[type="submit"] {
            background: linear-gradient(to right, var(--primary-color), #1f3d44);
            transition: all 0.3s ease;
            font-size: 1.1rem;
            letter-spacing: 1px;
        }

        input[type="submit"]:hover {
            background: linear-gradient(to right, var(--secondary-color), #ff9f5b);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 138, 61, 0.3);
        }

        /* Responsive adjustments specifically for the donation form */
        @media (max-width: 768px) {
            .doner-receiver {
                flex-direction: column;
            }

            .donation-form .checkbox label,
            .donation-form .radio label {
                min-width: 100px;
            }

            .back-button {
                margin-left: 0;
                top: 10px;
                left: 10px;
            }
        }

        /* Custom styling for the form border */
        .donation-form .form {
            border-left: 5px solid var(--accent-color) !important;
            transition: box-shadow 0.3s ease;
        }

        .donation-form .form:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        /* Error message styling */
        .error-message {
            background-color: #ffe6e6;
            border-left: 4px solid #e74c3c;
            color: #c0392b;
            padding: 12px 15px;
            margin-bottom: 20px;
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

        /* Fix for form labels that may be getting hidden due to existing CSS */
        .donation-form .input-wrapper {
            position: relative;
            margin-bottom: 20px;
        }

        /* Fix for recipient name label specifically */
        .donation-form label[for="receiver_name"],
        .donation-form label[for="doner_name"] {
            transform: none !important;
            visibility: visible !important;
            opacity: 1 !important;
            display: block !important;
            z-index: 5;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <h3 class="form-heading">Donation Information</h3>
    <div class="back-button">
        <a href="search-foodBank.php">
            <i class="fa-duotone fa-arrow-left" style="--fa-primary-color: #17272b; --fa-primary-opacity: .9; --fa-secondary-color: #91c11b; --fa-secondary-opacity: .7; font-size: 30px;"></i>
        </a>
    </div>
    <section class="donation-form">
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="form" method="POST" enctype="multipart/form-data" style="border-left: #536d10 5px solid; padding: 10px; border-radius: 20px;">
            <div class="doner-receiver">
                <div class="input-wrapper">
                    <input type="text" class="input" id="doner_name" name="doner_name" value="<?php echo $doner_name; ?>" required>
                    <label class="label required-field" for="doner_name">Donor Name</label>
                </div>
                &nbsp;&nbsp;<i class="fa-regular fa-arrow-right-long-to-line"></i>
                <div class="input-wrapper">
                    <input type="text" class="input" id="receiver_name" name="receiver_name"
                        value="<?php echo isset($_SESSION['receiver_name']) ? $_SESSION['receiver_name'] : ''; ?>" required>
                    <label for="receiver_name" class="label required-field">Recipient Name</label>
                </div>
            </div>
            <br>
            <div class="input-wrapper">
                <label for="donation_type" class="required-field">Type of food being donated:</label>
                <div class="checkbox" id="checkbox">
                    <input type="checkbox" name="donation_type[]" value="non-perishable" id="non-perishable" <?php if (isset($_POST['donation_type']) && in_array('non-perishable', $_POST['donation_type'])) echo 'checked'; ?>>
                    <label for="non-perishable">Non-Perishable</label>

                    <input type="checkbox" name="donation_type[]" value="perishable" id="perishable" <?php if (isset($_POST['donation_type']) && in_array('perishable', $_POST['donation_type'])) echo 'checked'; ?>>
                    <label for="perishable">Perishable</label>

                    <input type="checkbox" name="donation_type[]" value="baby" id="baby" <?php if (isset($_POST['donation_type']) && in_array('baby', $_POST['donation_type'])) echo 'checked'; ?>>
                    <label for="baby">Baby Food and Formula</label>

                    <input type="checkbox" name="donation_type[]" value="beverages" id="beverages" <?php if (isset($_POST['donation_type']) && in_array('beverages', $_POST['donation_type'])) echo 'checked'; ?>>
                    <label for="beverages">Beverages</label><br><br>

                    <input type="checkbox" name="donation_type[]" value="snacks" id="snacks" <?php if (isset($_POST['donation_type']) && in_array('snacks', $_POST['donation_type'])) echo 'checked'; ?>>
                    <label for="snacks">Snacks and Treats</label>
                </div><br><br>
            </div>
            <div style="display: flex; gap: 30px; align-items: center;align-self: flex-start; ">
                <div class="input-wrapper">
                    <input type="text" class="input" id="quantity" name="quantity" style="border-color: transparent; border-bottom: 1px solid black;" value="<?php echo isset($_POST['quantity']) ? $_POST['quantity'] : ''; ?>" required>
                    <label for="quantity" class="label required-field">Quantity:</label>
                </div>
                <input type="hidden" name="quantity_unit_hidden" id="quantity_unit_hidden" value="<?php echo isset($_POST['quantity_unit_hidden']) ? $_POST['quantity_unit_hidden'] : ''; ?>">
                <p class="quantity_unit">weight/count/volume</p>
            </div>
            <div class="input-wrapper">
                <div id="additionalValidation">
                    <br>
                    <div class="input-wrapper">
                        <input class="input" type="text" id="donation_info" name="donation_info" value="<?php echo isset($_POST['donation_info']) ? $_POST['donation_info'] : ''; ?>" required>
                        <label class="label required-field" id="info_label" for="donation_info">More Information:</label>
                    </div><br><br>
                    <div class="input-wrapper">
                        <input class="input" type="date" id="expDate" name="expDate" value="<?php echo isset($_POST['expDate']) ? $_POST['expDate'] : ''; ?>" min="<?php echo date('Y-m-d'); ?>" required>
                        <label class="label required-field" for="expDate">Expiration Date:</label>
                    </div><br><br>
                    <div class="input-wrapper">
                        <label for="damage" class="required-field">Does the packaging have any damage, leaks, or tampering:</label>
                        <div class="radio" id="radio">
                            <input type="radio" name="damage" value="yes" id="damage_yes" <?php if (isset($_POST['damage']) && $_POST['damage'] == 'yes') echo 'checked'; ?> required>
                            <label for="damage_yes">Yes</label>
                            <input type="radio" name="damage" value="no" id="damage_no" <?php if (isset($_POST['damage']) && $_POST['damage'] == 'no') echo 'checked'; ?>>
                            <label for="damage_no">No</label>
                        </div>
                    </div><br><br>
                </div>
                <div class="input-wrapper">
                    <input class="input" type="date" id="delivary_date" name="delivary_date" value="<?php echo isset($_POST['delivary_date']) ? $_POST['delivary_date'] : ''; ?>" min="<?php echo date('Y-m-d'); ?>" required>
                    <label for="delivary_date" class="label required-field">Date of delivery/donation:</label>
                </div><br>
                <div class="input-wrapper">
                    <label class="required-field">Is the Food Packaged and Sealed?</label>
                    <div class="radio" id="packaged">
                        <input type="radio" name="sealed" value="Yes" id="yes" <?php if (isset($_POST['sealed']) && $_POST['sealed'] == 'Yes') echo 'checked'; ?> required>
                        <label for="yes">Yes </label>
                        <input type="radio" name="sealed" value="No" id="no" <?php if (isset($_POST['sealed']) && $_POST['sealed'] == 'No') echo 'checked'; ?>>
                        <label for="no">No</label>
                    </div>
                </div><br>
                <div class="input-wrapper">
                    <label class="required-field">Delivery Option:</label>
                    <select name="delivery" id="select" class="select input" autocomplete="off" required>
                        <option value="" disabled selected>Select delivery option</option>
                        <option value="deliver to food bank" <?php if (isset($_POST['delivery']) && $_POST['delivery'] == 'deliver to food bank') echo 'selected'; ?>>I can deliver the food to the food bank</option>
                        <option value="food bank need to pickup" <?php if (isset($_POST['delivery']) && $_POST['delivery'] == 'food bank need to pickup') echo 'selected'; ?>>I need the food bank to arrange for pickup</option>
                    </select><br><br><br>
                </div>
                <div class="input-wrapper" style="align-self: flex-start;">
                    <label for="files">Upload Photos of Donation (Optional):</label>
                    <input type="file" name="files[]" id="files" onchange="previewImages(event)" multiple accept="image/*">
                    <small>Max 5MB per file. Allowed types: JPG, JPEG, PNG, GIF</small>
                    <div class="image-preview" id="image-preview"></div>
                </div>
                <br>
                <input type="submit" id="submit" name="submit_donation" value="Donate"><br><br>
        </form>
    </section>
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
        function previewImages(event) {
            debug('Image preview requested');
            const preview = document.getElementById('image-preview');
            preview.innerHTML = '';

            const files = event.target.files;
            debug('Files selected', files.length);

            const maxFiles = 5;
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            const maxSize = 5 * 1024 * 1024; // 5MB

            // Limit number of files
            const totalFiles = files.length > maxFiles ? maxFiles : files.length;

            for (let i = 0; i < totalFiles; i++) {
                const file = files[i];
                debug(`Processing file ${i+1}/${totalFiles}: ${file.name}, type: ${file.type}, size: ${file.size}`);

                // Check file type
                if (!allowedTypes.includes(file.type)) {
                    debug(`Invalid file type: ${file.type} for ${file.name}`);
                    alert('File type not allowed: ' + file.name + '. Only JPG, JPEG, PNG, and GIF are allowed.');
                    continue;
                }

                // Check file size
                if (file.size > maxSize) {
                    debug(`File too large: ${file.size} bytes for ${file.name}`);
                    alert('File too large: ' + file.name + '. Maximum size is 5MB.');
                    continue;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    debug(`File ${file.name} loaded successfully`);
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = file.name;
                    img.title = file.name;
                    preview.appendChild(img);
                    debug('Preview image added to DOM');
                };
                reader.onerror = function(e) {
                    debug(`Error reading file ${file.name}`, e);
                };
                reader.readAsDataURL(file);
                debug(`Started reading file ${file.name}`);
            }
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
            today.setHours(0, 0, 0, 0);
            debug('Validating expiration date:', expDate, 'Today:', today);

            if (expDate < today) {
                debug('Error: Expiration date is in the past');
                alert('Expiration date must be in the future');
                e.preventDefault();
                hasErrors = true;
                return;
            }

            // Validate delivery date is not in the past
            const deliveryDate = new Date(document.getElementById('delivary_date').value);
            debug('Validating delivery date:', deliveryDate);

            if (deliveryDate < today) {
                debug('Error: Delivery date is in the past');
                alert('Delivery date must be today or in the future');
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