<?php
session_start();
$error = $doner_name = $c_number = '';

// Verify user is logged in as a donor
if (isset($_SESSION['type'])) {
    $type = $_SESSION['type'];
    if($type == 'doner'){
        $donor_email = $_SESSION['email'];
        $doner_name = $_SESSION['name'];
        $receiver_email = $_SESSION['receiver_email'];
        $phone = $_SESSION['c_number'];
        // console the data
        echo "<script>console.log('Doner: '.$doner_email.', Receiver: '.$receiver_email)</script>";
    } else {
        echo "<script>alert('Please login as a donor');</script>";
        echo "<script>window.location.href='index.php';</script>";
        exit();
    }
} else {
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
}

// Function to sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to handle file uploads with better security
function handle_file_upload($files) {
    $uploaded_image_paths = array();
    $upload_directory = "uploads/";
    $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
    $max_size = 5 * 1024 * 1024; // 5MB

    // Create directory if it doesn't exist
    if (!file_exists($upload_directory)) {
        mkdir($upload_directory, 0755, true);
    }

    // Check if files are uploaded
    if (isset($files['name']) && is_array($files['name'])) {
        // Loop through each uploaded file
        for ($i = 0; $i < count($files['name']); $i++) {
            $image_name = basename($files['name'][$i]);
            $image_tmp = $files['tmp_name'][$i];
            $file_size = $files['size'][$i];
            $file_ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));

            // Validate file
            if (empty($image_name) || empty($image_tmp)) {
                continue;
            }

            // Check file type
            if (!in_array($file_ext, $allowed_types)) {
                continue;
            }

            // Check file size
            if ($file_size > $max_size) {
                continue;
            }

            // Generate unique filename
            $new_filename = uniqid('donation_') . '.' . $file_ext;
            $target_file = $upload_directory . $new_filename;

            // Move uploaded file to the upload directory
            if (move_uploaded_file($image_tmp, $target_file)) {
                $uploaded_image_paths[] = $target_file;
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

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_donation'])) {
    // Initialize errors array
    $errors = array();
    
    // Sanitize and validate input data
    $doner_name = sanitize_input($_POST['doner_name'] ?? '');
    $receiver_name = sanitize_input($_POST['receiver_name'] ?? '');
    
    // Handle donation type (multiple checkboxes)
    $donation_type = '';
    if (isset($_POST['donation_type']) && is_array($_POST['donation_type'])) {
        $donation_type = implode(', ', $_POST['donation_type']);
    } else if (isset($_POST['donation_type'])) {
        $donation_type = sanitize_input($_POST['donation_type']);
    }
    
    $quantity = sanitize_input($_POST['quantity'] ?? '');
    $quantity_unit = sanitize_input($_POST['quantity_unit_hidden'] ?? '');
    $donation_info = sanitize_input($_POST['donation_info'] ?? '');
    $expDate = sanitize_input($_POST['expDate'] ?? '');
    $damage = sanitize_input($_POST['damage'] ?? '');
    $delivery_date = sanitize_input($_POST['delivary_date'] ?? '');
    $sealed = sanitize_input($_POST['sealed'] ?? '');
    $delivery = sanitize_input($_POST['delivery'] ?? '');

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
    }

    // Validate delivery date is not in the past
    if (!empty($delivery_date) && $delivery_date < $current_date) {
        $errors[] = "Delivery date must be today or in the future.";
    }

    // Handle file uploads
    $uploaded_image_paths = handle_file_upload($_FILES['files'] ?? []);

    // If no errors, proceed with database insertion
    if (empty($errors)) {
        // Format uploaded image paths as a string
        $new_image_paths_str = implode(";", $uploaded_image_paths);

        // Prepare and bind SQL statement
        $sql = "INSERT INTO donations (doner, receiver, food_type, quantity, quantity_unit, expiration_date, damage, donation_info, donation_img, pickup, packaged, donation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssssssssss", 
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

            if (mysqli_stmt_execute($stmt)) {
                // Clear session variables
                unset($_SESSION['receiver_name']);
                unset($_SESSION['receiver_email']);
                unset($_SESSION['receiver_address']);
                unset($_SESSION['receiver_phone']);
                unset($_SESSION['receiver_distance']);
                
                echo "<script>alert('Thank You for your Donation');</script>";
                echo "<script>window.location.href='index.php';</script>";
                exit();
            } else {
                $error = "Error: " . mysqli_error($conn);
            }

            // Close statement
            mysqli_stmt_close($stmt);
        } else {
            $error = "Error preparing statement: " . mysqli_error($conn);
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Close connection
mysqli_close($conn);
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
        .error-message {
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

        <?php if (isset($_SESSION['receiver_name'])): ?>
            <div class="recipient-info">
                <h4>Recipient Information</h4>
                <p><strong>Name:</strong> <?php echo $_SESSION['receiver_name']; ?></p>
                <p><strong>Address:</strong> <?php echo $_SESSION['receiver_address'] ?? 'Not specified'; ?></p>
                <p><strong>Phone:</strong> <?php echo $_SESSION['receiver_phone'] ?? 'Not specified'; ?></p>
                <p><strong>Distance:</strong> <?php echo number_format($_SESSION['receiver_distance'], 2) ?? '0'; ?> km</p>
            </div>
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
                    <input type="checkbox" name="donation_type[]" value="non-perishable" id="non-perishable" <?php if(isset($_POST['donation_type']) && in_array('non-perishable', $_POST['donation_type'])) echo 'checked'; ?>>
                    <label for="non-perishable">Non-Perishable</label>
                    
                    <input type="checkbox" name="donation_type[]" value="perishable" id="perishable" <?php if(isset($_POST['donation_type']) && in_array('perishable', $_POST['donation_type'])) echo 'checked'; ?>>
                    <label for="perishable">Perishable</label>
                    
                    <input type="checkbox" name="donation_type[]" value="baby" id="baby" <?php if(isset($_POST['donation_type']) && in_array('baby', $_POST['donation_type'])) echo 'checked'; ?>>
                    <label for="baby">Baby Food and Formula</label>
                    
                    <input type="checkbox" name="donation_type[]" value="beverages" id="beverages" <?php if(isset($_POST['donation_type']) && in_array('beverages', $_POST['donation_type'])) echo 'checked'; ?>>
                    <label for="beverages">Beverages</label><br><br>
                    
                    <input type="checkbox" name="donation_type[]" value="snacks" id="snacks" <?php if(isset($_POST['donation_type']) && in_array('snacks', $_POST['donation_type'])) echo 'checked'; ?>>
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
                <label for="additionalValidation"></label>
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
                        <input type="radio" name="damage" value="yes" id="damage_yes" <?php if(isset($_POST['damage']) && $_POST['damage'] == 'yes') echo 'checked'; ?> required>
                        <label for="damage_yes">Yes</label>
                        <input type="radio" name="damage" value="no" id="damage_no" <?php if(isset($_POST['damage']) && $_POST['damage'] == 'no') echo 'checked'; ?>>
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
                    <input type="radio" name="sealed" value="Yes" id="yes" <?php if(isset($_POST['sealed']) && $_POST['sealed'] == 'Yes') echo 'checked'; ?> required>
                    <label for="yes">Yes </label>
                    <input type="radio" name="sealed" value="No" id="no" <?php if(isset($_POST['sealed']) && $_POST['sealed'] == 'No') echo 'checked'; ?>>
                    <label for="no">No</label>
                </div>
            </div><br>
            <div class="input-wrapper">
                <label class="required-field">Delivery Option:</label>
                <select name="delivery" id="select" class="select input" autocomplete="off" required>
                    <option value="" disabled selected>Select delivery option</option>
                    <option value="deliver to food bank" <?php if(isset($_POST['delivery']) && $_POST['delivery'] == 'deliver to food bank') echo 'selected'; ?>>I can deliver the food to the food bank</option>
                    <option value="food bank need to pickup" <?php if(isset($_POST['delivery']) && $_POST['delivery'] == 'food bank need to pickup') echo 'selected'; ?>>I need the food bank to arrange for pickup</option>
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
        // Updating the quantity unit based on the selected donation type
        document.addEventListener('DOMContentLoaded', function() {
            // Run once on page load to set initial values
            updateQuantityUnit();
            
            // Add event listeners to checkboxes
            const checkboxes = document.querySelectorAll('input[name="donation_type[]"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', updateQuantityUnit);
            });
            
            // Function to update quantity unit
            function updateQuantityUnit() {
                const selectedTypes = Array.from(document.querySelectorAll('input[name="donation_type[]"]:checked'))
                                        .map(checkbox => checkbox.value);
                const quantityUnitField = document.querySelector('.quantity_unit');
                const hiddenInput = document.querySelector('#quantity_unit_hidden');
                
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
            }
        });

        // Function to preview images before upload
        function previewImages(event) {
            const preview = document.getElementById('image-preview');
            preview.innerHTML = '';
            
            const files = event.target.files;
            const maxFiles = 5;
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            // Limit number of files
            const totalFiles = files.length > maxFiles ? maxFiles : files.length;
            
            for (let i = 0; i < totalFiles; i++) {
                const file = files[i];
                
                // Check file type
                if (!allowedTypes.includes(file.type)) {
                    alert('File type not allowed: ' + file.name + '. Only JPG, JPEG, PNG, and GIF are allowed.');
                    continue;
                }
                
                // Check file size
                if (file.size > maxSize) {
                    alert('File too large: ' + file.name + '. Maximum size is 5MB.');
                    continue;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = file.name;
                    img.title = file.name;
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
        }
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            // Check if at least one donation type is selected
            const donationTypes = document.querySelectorAll('input[name="donation_type[]"]:checked');
            if (donationTypes.length === 0) {
                alert('Please select at least one donation type');
                e.preventDefault();
                return;
            }
            
            // Validate quantity is a positive number
            const quantity = document.getElementById('quantity').value.trim();
            if (isNaN(quantity) || parseFloat(quantity) <= 0) {
                alert('Please enter a valid positive number for quantity');
                e.preventDefault();
                return;
            }
            
            // Validate expiration date is in the future
            const expDate = new Date(document.getElementById('expDate').value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            if (expDate < today) {
                alert('Expiration date must be in the future');
                e.preventDefault();
                return;
            }
            
            // Validate delivery date is not in the past
            const deliveryDate = new Date(document.getElementById('delivary_date').value);
            if (deliveryDate < today) {
                alert('Delivery date must be today or in the future');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>