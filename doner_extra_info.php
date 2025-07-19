<?php
session_start();

// Check authentication
if (!isset($_SESSION['email']) || !isset($_SESSION['type'])) {
    $_SESSION['error_message'] = "Please Login/SignUp First";
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$type = $_SESSION['type'];

// Database connection
$conn = mysqli_connect("localhost", "root", "", "sdp");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $doner_type = $_POST['doner_type'] ?? '';
    $doner_other = $_POST['other_description'] ?? '';
    $typical_donation = $_POST['typical_donation'] ?? '';
    $typical_donation_other = $_POST['typical-donation-input'] ?? '';
    $delivary = $_POST['delivary'] ?? '';

    // Validation
    if (empty(trim($doner_type)) || empty($typical_donation) || empty(trim($delivary))) {
        $_SESSION['error_message'] = "Please fill out all the fields.";
    } elseif (($doner_type == 'other' && empty(trim($doner_other))) ||
        ($typical_donation == 'other' && empty($typical_donation_other))
    ) {
        $_SESSION['error_message'] = "Other field cannot be left blank.";
    } else {
        // Process form data
        if ($doner_type == 'other') $doner_type = $doner_other;
        if ($typical_donation == 'other') $typical_donation = $typical_donation_other;

        $table = ($type == 'doner') ? 'fooddoners' : 'foodreceivers';

        // Update database
        $sql_update = "UPDATE $table SET doner_type=?, typical_donation=?, delivary=? WHERE email=?";
        $stmt_update = mysqli_prepare($conn, $sql_update);

        if ($stmt_update) {
            mysqli_stmt_bind_param($stmt_update, "ssss", $doner_type, $typical_donation, $delivary, $email);

            if (mysqli_stmt_execute($stmt_update)) {
                if (mysqli_stmt_affected_rows($stmt_update) > 0) {
                    $_SESSION['logged-in'] = true;
                    $location = ($type == 'doner') ? 'index.php' : 'receiver_dashboard.php';
                    header("Location: $location");
                    exit();
                } else {
                    $_SESSION['error_message'] = "No changes were made to your data.";
                }
            } else {
                $_SESSION['error_message'] = "Failed to update your data. Please try again.";
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $_SESSION['error_message'] = "Database error. Please contact support.";
        }
        header("Location: edit_profile.php");
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
    <link rel="stylesheet" href="signup.css">
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.5.2/css/all.css">
    <title>Food Donor Extra Info</title>

    <style>
        /* Override conflicting styles from signup.css */
        
        /* Fix form alignment */
        .form {
            align-items: flex-start !important;
            text-align: left !important;
            max-width: 800px;
        }

        .form form {
            align-items: flex-start !important;
        }

        /* Back button styling */
        .back-button {
            margin-bottom: 20px;
        }

        .back-button a {
            display: inline-block;
            cursor: pointer;
            transition: var(--transition);
        }

        .back-button a:hover {
            transform: translateX(-5px);
        }

        .back-button i {
            font-size: 24px;
            color: var(--primary-color);
        }

        /* Error message styling */
        .error-message {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 12px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            width: 100%;
        }

        /* Form group styling */
        .form-group {
            margin-bottom: 25px;
            width: 100%;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 12px;
            font-size: 16px;
            position: static !important; /* Override floating label */
            background: transparent !important;
            transform: none !important;
            padding: 0 !important;
        }

        /* Select dropdown styling */
        .doner-type-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            background: white;
            font-family: 'Josefin Sans', sans-serif !important;
            font-weight: 400;
            transition: var(--transition);
        }

        .doner-type-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 5px rgba(23, 39, 43, 0.3);
        }

        /* Radio button styling - Hide actual radio buttons and style labels as buttons */
        .custom-radio-wrapper {
            display: flex;
            justify-content: flex-start;
            flex-wrap: wrap;
            gap: 15px;
            margin: 15px 0;
        }

        .custom-radio-wrapper input[type="radio"] {
            display: none !important; /* Hide the actual radio button */
        }

        .custom-radio-wrapper label {
            display: inline-block !important;
            margin: 0 !important;
            padding: 12px 20px !important;
            border: 1px solid #ddd !important;
            border-radius: 10px !important; /* Rounded button style like in first image */
            background: white !important;
            cursor: pointer !important;
            font-weight: normal !important;
            font-size: 16px !important;
            color: #333 !important;
            transition: all 0.3s ease !important;
            position: static !important;
            transform: none !important;
        }

        .custom-radio-wrapper label:hover {
            background-color: #f5f5f5 !important;
            border-color: var(--primary-color) !important;
        }

        .custom-radio-wrapper input[type="radio"]:checked + label {
            background-color: var(--primary-color) !important;
            color: white !important;
            border-color: var(--primary-color) !important;
            transform: none !important;
        }

        /* Text input for "other" options */
        .other-input {
            margin-top: 15px;
            width: 100%;
        }

        /* Regular other input (like donor type) */
        .other-input.regular label {
            display: block !important;
            margin-bottom: 8px !important;
            font-weight: 600 !important;
            color: var(--primary-color) !important;
            padding: 0 !important;
            border: none !important;
            background: transparent !important;
            border-radius: 0 !important;
            font-size: 16px !important;
            cursor: default !important;
            position: static !important;
            transform: none !important;
        }

        .other-input.regular input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            background: white !important;
            color: black !important;
            font-family: 'Josefin Sans', sans-serif !important;
            font-weight: 400;
        }

        .other-input.regular input[type="text"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 5px rgba(23, 39, 43, 0.3);
        }

        /* Floating label input for typical donation */
        .other-input.floating {
            position: relative;
            margin-top: 15px;
            width: 100%;
        }

        .other-input.floating input[type="text"] {
            width: 100%;
            padding: 20px 15px 10px 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            background: white !important;
            color: black !important;
            font-family: 'Josefin Sans', sans-serif !important;
            font-weight: 400;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .other-input.floating input[type="text"]:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 5px rgba(23, 39, 43, 0.3);
        }

        .other-input.floating label {
            position: absolute !important;
            top: 15px !important;
            left: 15px !important;
            font-size: 16px !important;
            color: #999 !important;
            pointer-events: none !important;
            transition: all 0.3s ease !important;
            background: white !important;
            padding: 0 5px !important;
            margin: 0 !important;
            border: none !important;
            border-radius: 0 !important;
            font-weight: normal !important;
            cursor: default !important;
        }

        .other-input.floating input[type="text"]:focus + label,
        .other-input.floating input[type="text"]:not(:placeholder-shown) + label,
        .other-input.floating input[type="text"].has-value + label {
            top: -8px !important;
            font-size: 12px !important;
            color: var(--primary-color) !important;
            font-weight: 600 !important;
        }

        /* Hide class */
        .hidden {
            display: none !important;
        }

        /* Submit button */
        input[type="submit"] {
            margin: 20px auto 0 auto;
            width: 200px;
            display: block;
            text-align: center;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .form {
                margin: 10px;
                padding: 20px;
                min-width: auto;
                width: 95%;
            }
            
            .custom-radio-wrapper {
                flex-direction: column;
                gap: 10px;
            }

            .custom-radio-wrapper label {
                padding: 10px 16px !important;
                font-size: 14px !important;
            }
        }
    </style>
</head>

<body>
    <div class="body">
        <div class="form">
            <div class="back-button">
                <a href="javascript:history.back()">
                    <i class="fa-duotone fa-arrow-left"></i>
                </a>
            </div>

            <?php
            if (isset($_SESSION['error_message'])) {
                echo '<div class="error-message">' . $_SESSION['error_message'] . '</div>';
                unset($_SESSION['error_message']);
            }
            ?>

            <form action="" method="POST">
                <h2>Help us make your experience better...</h2>

                <div class="form-group">
                    <label for="doner_type">Type of Food Donor:</label>
                    <select class="doner-type-select" name="doner_type" id="doner_type" required>
                        <option value="">Select donor type</option>
                        <option value="individual">Individual</option>
                        <option value="community-kitchen">Community Kitchen</option>
                        <option value="restaurant/cafe">Restaurant / Cafe</option>
                        <option value="corporate-cafeterias">Corporate Cafeterias</option>
                        <option value="event-caterers">Event Caterers</option>
                        <option value="supermarket/grocery-store">Supermarket / Grocery Store</option>
                        <option value="other">Other (Please specify below)</option>
                    </select>
                </div>

                <div class="other-input floating hidden" id="other-input">
                    <input type="text" name="other_description" placeholder=" ">
                    <label>Specify other</label>
                </div>

                <div class="form-group">
                    <label>We/I typically donate this type of food:</label>
                    <div class="custom-radio-wrapper">
                        <input type="radio" name="typical_donation" value="non-perishable" id="non-perishable" required>
                        <label for="non-perishable">Non-Perishable</label>

                        <input type="radio" name="typical_donation" value="perishable" id="perishable">
                        <label for="perishable">Perishable</label>

                        <input type="radio" name="typical_donation" value="baby" id="baby">
                        <label for="baby">Baby Food and Formula</label>

                        <input type="radio" name="typical_donation" value="beverages" id="beverages">
                        <label for="beverages">Beverages</label>

                        <input type="radio" name="typical_donation" value="snacks" id="snacks">
                        <label for="snacks">Snacks and Treats</label>

                        <input type="radio" name="typical_donation" value="other" id="otherOption">
                        <label for="otherOption">Other</label>

                        <input type="radio" name="typical_donation" value="no" id="no">
                        <label for="no">Nothing like that</label>
                    </div>

                    <div class="other-input floating hidden" id="typical-donation-input">
                        <input type="text" name="typical-donation-input" placeholder=" ">
                        <label>Please specify your typical donation</label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Availability for delivery:</label>
                    <div class="custom-radio-wrapper">
                        <input type="radio" name="delivary" value="yes" id="delivary-yes" required>
                        <label for="delivary-yes">Yes</label>

                        <input type="radio" name="delivary" value="no" id="delivary-no">
                        <label for="delivary-no">No</label>
                    </div>
                </div>

                <input type="submit" value="Continue">
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const donerTypeSelect = document.getElementById("doner_type");
            const otherInput = document.getElementById("other-input");
            const typicalDonationInput = document.getElementById("typical-donation-input");
            const typicalRadios = document.querySelectorAll('input[name="typical_donation"]');

            // Show/hide donor type "Other" text input
            donerTypeSelect.addEventListener("change", function() {
                if (this.value === "other") {
                    otherInput.classList.remove("hidden");
                } else {
                    otherInput.classList.add("hidden");
                }
            });

            // Show/hide typical donation "Other" text input
            typicalRadios.forEach(function(radio) {
                radio.addEventListener("change", function() {
                    if (this.value === "other") {
                        typicalDonationInput.classList.remove("hidden");
                        // Focus on the text input
                        setTimeout(function() {
                            const textInput = typicalDonationInput.querySelector('input[type="text"]');
                            textInput.focus();
                        }, 100);
                    } else {
                        typicalDonationInput.classList.add("hidden");
                    }
                });
            });

            // Handle floating labels for both floating inputs
            const floatingInputs = document.querySelectorAll('.other-input.floating input[type="text"]');
            
            floatingInputs.forEach(function(input) {
                // Check if input has value
                function checkValue() {
                    if (input.value.trim() !== '') {
                        input.classList.add('has-value');
                    } else {
                        input.classList.remove('has-value');
                    }
                }

                input.addEventListener('input', checkValue);
                input.addEventListener('blur', checkValue);
                input.addEventListener('focus', checkValue);
                
                // Initial check
                checkValue();
            });
        });
    </script>
</body>
</html>