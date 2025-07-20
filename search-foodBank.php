<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

session_start();
// Initialize variables
$error = $name = "";

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sdp";

// Create a connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the session 'type' is set
if (isset($_SESSION['type'])) {
    $type = $_SESSION['type'];

    // If the user is a doner, fetch their details
    if ($type == 'doner') {
        $doner_email = $_SESSION['email'];
        $doner_name = $_SESSION['name'];
        $doner_phone = $_SESSION['phone'];
        $table = 'fooddoners';

        // Query to fetch all records from the fooddoners table
        $sql = "SELECT * FROM $table WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $doner_email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Fetch the first row from the result set
        if ($row = mysqli_fetch_assoc($result)) {
            $default_image = 'uploads/food-bank-logo.png';
            $image_path = $row['image_path'];
            $final_image = (!empty($image_path) && file_exists($image_path)) ? $image_path : $default_image;
        } else {
            // Handle case where no rows are found
            $error = "No records found.";
        }
    } else {
        // If the user is not a doner, redirect them to the login page
        echo "<script>alert('Please login as a doner');</script>";
        echo "<script>window.location.href='index.php';</script>";
        exit();
    }
} else {
    // If the session 'type' is not set, redirect to the login page
    echo "<script>alert('Please login');</script>";
    echo "<script>window.location.href='login.php';</script>";
    exit();
}

// Unified function for getting coordinates from address
function getLatLongFromAddress($address)
{
    if (empty($address)) return false;

    $formattedAddress = str_replace(' ', '+', $address);
    $apiKey = $_ENV['GOOGLE_MAPS_API_KEY'];
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$formattedAddress}&key={$apiKey}";

    $responseJson = file_get_contents($url);
    $response = json_decode($responseJson, true);

    // Check if the response contains results
    if (!empty($response['results'])) {
        $geometry = $response['results'][0]['geometry'];
        $latitude = $geometry['location']['lat'];
        $longitude = $geometry['location']['lng'];

        // Loop through address components to find the city and state
        $city = '';
        $state = '';
        foreach ($response['results'][0]['address_components'] as $component) {
            if (in_array('locality', $component['types'])) {
                $city = $component['long_name'];
            }
            if (in_array('administrative_area_level_1', $component['types'])) {
                $state = $component['long_name'];
            }
        }

        return array('latitude' => $latitude, 'longitude' => $longitude, 'city' => $city, 'state' => $state);
    } else {
        return false; // No results found
    }
}

// Calculate distance between two sets of coordinates
function calculateDistance($lat1, $lon1, $lat2, $lon2)
{
    // Radius of the Earth in kilometers
    $R = 6371;

    // Convert latitude and longitude from degrees to radians
    $lat1 = deg2rad($lat1);
    $lon1 = deg2rad($lon1);
    $lat2 = deg2rad($lat2);
    $lon2 = deg2rad($lon2);

    // Calculate the differences between the coordinates
    $dlat = $lat2 - $lat1;
    $dlon = $lon2 - $lon1;

    // Haversine formula
    $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlon / 2) * sin($dlon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $R * $c;

    return $distance;
}

// Function to determine urgency level
function getUrgencyLevel($daily_count, $req_people)
{
    if ($req_people == 0) return 'Unknown';

    $utilization = $daily_count / $req_people;
    if ($utilization < 0.3) return 'Critical';
    if ($utilization < 0.6) return 'High';
    if ($utilization < 0.9) return 'Moderate';
    return 'Low';
}

// Function to get urgency color class
function getUrgencyClass($urgency)
{
    switch ($urgency) {
        case 'Critical':
            return 'critical-need';
        case 'High':
            return 'high-need';
        case 'Moderate':
            return 'moderate-need';
        case 'Low':
            return 'low-need';
        default:
            return 'unknown-need';
    }
}

// Search for nearby food banks based on coordinates
function searchFoodbank($address)
{
    global $conn;

    // Increase search radius (in kilometers) to include more results in the state
    $radius = 100; // Increased from 10 to 100 to get more results in the state

    // Geocode the user's address
    $user_coordinates = getLatLongFromAddress($address);

    // Check if geocoding was successful
    if ($user_coordinates !== false) {
        $user_latitude = $user_coordinates['latitude'];
        $user_longitude = $user_coordinates['longitude'];
        $user_state = $user_coordinates['state'];

        // SQL query to select all food banks
        $sql = "SELECT * FROM foodreceivers";
        $result = mysqli_query($conn, $sql);

        // Check for errors in query execution
        if (!$result) {
            die('Error in SQL query: ' . mysqli_error($conn));
        }

        // Array to store food bank details
        $foodbanks = array();

        // Fetch results and store in array
        while ($row = mysqli_fetch_assoc($result)) {
            // Geocode each food bank's address
            $foodbank_coordinates = getLatLongFromAddress($row['address']);
            if ($foodbank_coordinates !== false) {
                $foodbank_latitude = $foodbank_coordinates['latitude'];
                $foodbank_longitude = $foodbank_coordinates['longitude'];
                $foodbank_state = $foodbank_coordinates['state'];

                // Calculate distance between user and food bank
                $distance = calculateDistance($user_latitude, $user_longitude, $foodbank_latitude, $foodbank_longitude);

                // Only include food banks in the same state and within the radius
                if ($foodbank_state == $user_state && $distance <= $radius) {
                    // Add food bank details along with distance to the array
                    $row['distance'] = $distance;
                    $foodbanks[] = $row;
                }
            }
        }

        // Sort food banks by distance (shortest first)
        usort($foodbanks, function ($a, $b) {
            return $a['distance'] - $b['distance'];
        });

        // Return the food banks
        return array('foodbanks' => $foodbanks, 'state' => $user_state);
    } else {
        // Geocoding failed
        return false;
    }
}

// Process form submission
$foodbanks = array();
$state = "";
$city = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['search-foodBank'])) {
        $address = $_POST['addressInput'];

        // Validate address input
        if (empty($address)) {
            $error = "Please enter an address";
        } else {
            $coordinates = getLatLongFromAddress($address);

            if ($coordinates !== false) {
                $city = $coordinates['city'];
                $state = $coordinates['state'];
                // Fetch food banks near the provided coordinates
                $results = searchFoodbank($address);
                if ($results !== false) {
                    $foodbanks = $results['foodbanks'];
                    $state = $results['state'];
                }
            } else {
                $error = "Failed to fetch coordinates. Please try again.";
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
    <title>Food Donation Search</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Meddon&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Meddon&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&family=Paprika&family=Tenor+Sans&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.5.2/css/all.css">
    <link rel="stylesheet" href="style.css">

    <style>
        /* --- UI Consistency Fixes --- */
        .foodbank-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin: 15px 0;
            padding: 25px;
            border-left: 5px solid #4CAF50;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-left: auto;
            margin-right: auto;
            max-width: 900px;
            width: 100%;
        }

        .foodbank-container:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .foodbank-container.not-accepting {
            border-left-color: #f44336;
            background: #fafafa;
            opacity: 0.85;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-left {
            display: flex;
            align-items: center;
            flex: 1;
            min-width: 300px;
        }

        .the-details{
            margin: 20px;
        }

        .org-logo {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid #e9ecef;
        }

        .receiver-type-badge {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(52, 152, 219, 0.3);
            display: inline-block;
            /* margin: 0 auto; */
        }

        .org-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            gap: 4px;
        }

        .foodbank-name {
            color: #2c3e50;
            font-size: 1.3em;
            font-weight: 600;
            margin: 0;
            line-height: 1.2;
        }

        .badges-container {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .capacity-display {
            background: linear-gradient(135deg, #ecf0f1, #bdc3c7);
            padding: 8px 14px;
            border-radius: 20px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.85em;
            border: 1px solid #d5dbdb;
        }

        .contact-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            line-height: 1.6;
        }

        .contact-section p {
            margin: 10px 0;
            color: #495057;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95em;
        }

        .contact-section i {
            color: #3498db;
            width: 18px;
            text-align: center;
            font-size: 1.1em;
        }

        .acceptance-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .accepting {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
            box-shadow: 0 2px 4px rgba(21, 87, 36, 0.1);
        }

        .not-accepting {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
            box-shadow: 0 2px 4px rgba(114, 28, 36, 0.1);
        }

        .urgency-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .capacity-bar-container {
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            height: 12px;
            position: relative;
            border: 1px solid #dee2e6;
        }

        .capacity-text {
            font-size: 0.9em;
            color: #6c757d;
            margin-top: 8px;
            font-weight: 500;
        }

        .action-section {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            flex-wrap: wrap;
            padding-top: 5px;
        }

        .donate-btn,
        .details-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 120px;
            justify-content: center;
        }

        .donate-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 3px 8px rgba(40, 167, 69, 0.3);
        }

        .donate-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #218838, #1ea471);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
        }

        .donate-btn:disabled {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            cursor: not-allowed;
            opacity: 0.7;
            transform: none;
            box-shadow: none;
        }

        .details-btn {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            box-shadow: 0 3px 8px rgba(23, 162, 184, 0.3);
        }

        .details-btn:hover {
            background: linear-gradient(135deg, #138496, #0f6674);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.4);
        }

        .details-panel {
            display: none;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 8px;
            border: 1px solid #dee2e6;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .details-panel h4 {
            color: #2c3e50;
            margin: 0 0 15px 0;
            font-size: 1.1em;
            font-weight: 600;
        }

        .details-panel>div>div {
            margin: 8px 0;
            padding: 5px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .details-panel>div>div:last-child {
            border-bottom: none;
        }

        .statistics-summary {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 12px;
            margin: 25px 0;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .stat-item {
            text-align: center;
            flex: 1;
            min-width: 100px;
        }

        .stat-number {
            font-size: 1.8em;
            font-weight: 700;
            color: #2c3e50;
            display: block;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.85em;
            color: #6c757d;
            margin-top: 5px;
            font-weight: 500;
        }

        .search-results-header {
            font-size: 1.2em;
            font-weight: 600;
            color: #2c3e50;
            margin: 20px 0;
            padding: 15px 0;
            border-bottom: 2px solid #e9ecef;
        }

        .no-results {
            text-align: center;
            padding: 50px 20px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 12px;
            margin: 20px 0;
            border: 1px solid #dee2e6;
        }

        .no-results h3 {
            color: #2c3e50;
            margin: 15px 0;
            font-weight: 600;
        }

        .no-results-icon i {
            font-size: 3em;
            color: #6c757d;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .foodbank-container {
                padding: 20px;
                margin: 12px 0;
            }

            .header-section {
                flex-direction: column;
                gap: 15px;
            }

            .header-left {
                min-width: unset;
                width: 100%;
            }

            .badges-container {
                justify-content: flex-start;
                width: 100%;
            }

            .action-section {
                justify-content: stretch;
                gap: 10px;
            }

            .donate-btn,
            .details-btn {
                flex: 1;
                min-width: unset;
            }

            .statistics-summary {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }

            .stat-item {
                min-width: unset;
            }
        }

        /* Remove any centering from .results */
        .results {
            display: block;
            /* Remove align-items: center; and justify-content: center; if present */
        }
    </style>
</head>

<body>
    <nav>
        <img src="images/foodflow-logo.png" alt="logo">
        <ul>
            <li class="main-menu"><a href="index.php">Home</a></li>
            <li class="main-menu"><a href="index.php#services">Services</a></li>
            <li class="main-menu"><a href="index.php#about">About Us</a></li>
            <li class="main-menu"><a href="index.php#contact">Contact</a></li>
            <li class="user">
                <p class="user-details">
                    <?php
                    if (isset($_SESSION['email']))
                        echo $doner_email;
                    else
                        echo "Guest&nbsp;";
                    ?>
                </p>
                <a href="javascript:void(0)">
                    <div class="user-icon">
                        <img src="
                            <?php
                            if (isset($image_path) && !empty($image_path) && file_exists($image_path)) {
                                echo $image_path;
                            } else {
                                echo "images/user.png";
                            }
                            ?>"
                            alt="user icon" id="user-icon">
                    </div>
                </a>
            </li>
        </ul>
        <div id="other-menu">
            <li class="main-menu-other"><a href="index.php#home">Home</a></li>
            <li class="main-menu-other"><a href="index.php#services">Services</a></li>
            <li class="main-menu-other"><a href="index.php#about">About Us</a></li>
            <li class="main-menu-other"><a href="index.php#contact">Contact</a></li>
            <hr class="main-menu-other">
            <?php
            if (isset($_SESSION['logged-in'])) {
                echo '<li><a href="profile.php">Profile</a></li>';
                echo '<li><a href="logout.php">Logout</a></li>';
            } else
                echo '<li><a href="login.php">Login/Register</a></li>';
            ?>
        </div>
    </nav>

    <section>
        <?php if ($error) {
            echo "<div class='alert alert-danger'>" . $error . "</div>";
        } ?>
        <form class="location-form" method="POST" action="" id="searchForm">
            <input class="input" type="text" id="addressInput" name="addressInput" placeholder="Enter your(pick-up) address">
            <button type="button" id="getLocationBtn" class="location-button">
                <i class="fa fa-location-dot" style="font-size: 30px"></i>
            </button>
            <button type="submit" name="search-foodBank" class="search-submit" id="searchBtn">Search</button>
        </form>
    </section>

    <section class="the-details">
        <!-- Search Results -->
        <section class="results" id="searchResults">
            <!-- Dynamic content will be added here -->
            <div id="loadingIndicator" style="text-align: center; display: none;">
                <p>Searching for food receivers near you...</p>
                <div class="loading-spinner"></div>
            </div>
            <div id="jsResults">
                <!-- JavaScript will populate this area -->
            </div>

            <?php
            // Consistent Food Receiver Card Display Section (UI improved, structure fixed)
            if (!empty($foodbanks)) {
                // Calculate statistics
                $total_receivers = count($foodbanks);
                $accepting_count = 0;
                $critical_count = 0;
                $total_capacity = 0;
                $total_current = 0;

                foreach ($foodbanks as $bank) {
                    // FIX: Use req_bool for accepting donations count
                    if ($bank['req_bool'] == '1') $accepting_count++;
                    $urgency = getUrgencyLevel($bank['daily_count'], $bank['req_people']);
                    if ($urgency == 'Critical') $critical_count++;
                    $total_capacity += $bank['req_people'];
                    $total_current += $bank['daily_count'];
                }

                echo "<p class='search-results-header'>Food receivers in <strong>" . htmlspecialchars($state) . "</strong> near you, sorted by distance:</p>";

                // Statistics summary
                echo "<div class='statistics-summary'>";
                echo "<div class='stat-item'><div class='stat-number'>{$total_receivers}</div><div class='stat-label'>Total Receivers</div></div>";
                echo "<div class='stat-item'><div class='stat-number'>{$accepting_count}</div><div class='stat-label'>Accepting Donations</div></div>";
                echo "<div class='stat-item'><div class='stat-number'>{$critical_count}</div><div class='stat-label'>Critical Need</div></div>";
                echo "<div class='stat-item'><div class='stat-number'>" . number_format($total_current) . "</div><div class='stat-label'>People Currently Served</div></div>";
                echo "</div>";

                foreach ($foodbanks as $bank) {
                    // Sanitize and prepare data
                    $distance = number_format($bank['distance'], 1);
                    $name = htmlspecialchars($bank['name']);
                    $address = htmlspecialchars($bank['address']);
                    $phone = htmlspecialchars($bank['phone']);
                    $email = htmlspecialchars($bank['email']);
                    $receiver_type = !empty($bank['receiver_type']) ? ucfirst(htmlspecialchars($bank['receiver_type'])) : 'Organization';

                    // Image handling
                    $default_image = 'uploads/food-bank-logo.png';
                    $image_path = (!empty($bank['image_path']) && file_exists($bank['image_path'])) ? $bank['image_path'] : $default_image;

                    // Calculate capacity and urgency
                    $daily_count = (int)$bank['daily_count'];
                    $req_people = (int)$bank['req_people'];
                    $urgency = getUrgencyLevel($daily_count, $req_people);
                    $urgency_class = getUrgencyClass($urgency);
                    $capacity_percentage = ($req_people > 0) ? min(($daily_count / $req_people) * 100, 100) : 0;

                    // Determine acceptance status
                    $is_accepting = ($bank['req_bool'] == '1'); // FIX: use req_bool
                    $acceptance_class = $is_accepting ? 'accepting' : 'not-accepting';
                    $acceptance_text = $is_accepting ? 'Accepting Donations' : 'Not Accepting Donations';

                    // Container class
                    $container_class = $is_accepting ? 'foodbank-container' : 'foodbank-container not-accepting';

                    // Generate unique ID for this card
                    $card_id = 'foodbank_' . md5($email . $name);

                    echo "<div class='{$container_class}' id='{$card_id}'>
                            <div class='header-section'>
                                <div class='header-left'>
                                    <img class='org-logo foodbank-icon' src='{$image_path}' alt='{$name} Logo' onerror=\"this.src='{$default_image}'\">
                                    <div class='org-info'>
                                        <h3 class='foodbank-name'>{$name}</h3>
                                        <span class='receiver-type-badge'>{$receiver_type}</span>
                                    </div>
                                </div>
                                <div class='badges-container'>";
                    // FIX: Use req_bool for badges and capacity
                    if ($bank['req_bool'] == '1' && $req_people > 0) {
                        echo "<span class='capacity-display'>{$daily_count}/{$req_people}</span>";
                    }
                    echo "<span class='acceptance-status {$acceptance_class}'>{$acceptance_text}</span>";
                    if ($bank['req_bool'] == '1' && $is_accepting) {
                        echo "<span class='urgency-badge {$urgency_class}'>{$urgency} Need</span>";
                    }
                    echo "</div>
                            </div>";

                    // Contact Information
                    echo "<div class='contact-section'>
                            <p><i class='fas fa-map-marker-alt'></i> {$address}</p>
                            <p><i class='fas fa-phone'></i> {$phone}</p>
                            <p><i class='fas fa-envelope'></i> {$email}</p>
                            <p><i class='fas fa-route'></i> {$distance} km away</p>
                        </div>";

                    // Capacity Progress Bar (only show if capacity tracking is enabled)
                    if ($bank['req_bool'] == '1' && $req_people > 0) {
                        $remaining_capacity = max(0, $req_people - $daily_count);
                        echo "<div class='capacity-bar-container'>
                                <div class='capacity-bar {$urgency_class}' style='width: {$capacity_percentage}%; height:100%;'></div>
                              </div>
                              <div class='capacity-text'>
                                Serving {$daily_count} of {$req_people} people daily
                                " . ($remaining_capacity > 0 ? "({$remaining_capacity} slots available)" : "(At capacity)") . "
                              </div>";
                    }

                    // Action Buttons
                    echo "<div class='action-section'>";
                    if ($is_accepting) {
                        echo "<form method='POST' action='donationform.php' class='donation-form' style='display:inline;'>
                                <input type='hidden' name='receiver_name' value='{$name}'>
                                <input type='hidden' name='receiver_email' value='{$email}'>
                                <input type='hidden' name='receiver_address' value='{$address}'>
                                <input type='hidden' name='receiver_phone' value='{$phone}'>
                                <input type='hidden' name='receiver_distance' value='{$distance}'>
                                <input type='hidden' name='receiver_type' value='{$receiver_type}'>
                                <button type='submit' class='donate-btn'>
                                    <i class='fas fa-hand-holding-heart'></i> Donate Now
                                </button>
                              </form>";
                    }
                    echo "<button type='button' class='details-btn' onclick='toggleDetails(this)' aria-expanded='false'>
                            <i class='fas fa-info-circle'></i> More Details
                          </button>
                        </div>";

                    // Details Panel (Hidden by default)
                    echo "<div class='details-panel'>
                            <h4>Organization Details</h4>
                            <div>
                                <div><strong>Type:</strong> {$receiver_type}</div>
                                <div><strong>User Category:</strong> " . ucfirst($bank['user_type'] ?? 'Not specified') . "</div>";
                    if ($bank['req_bool'] == '1' && $req_people > 0) {
                        echo "<div><strong>Daily Capacity:</strong> Up to {$req_people} people</div>
                              <div><strong>Current Service:</strong> {$daily_count} people daily</div>
                              <div><strong>Utilization:</strong> " . number_format($capacity_percentage, 1) . "%</div>";
                        if ($urgency == 'Critical') {
                            echo "<div style='background:#f8d7da;color:#721c24;padding:10px;border-radius:6px;margin-top:10px;'>
                                    <strong><i class='fas fa-exclamation-triangle'></i> Critical Status:</strong>
                                    This organization urgently needs food donations!
                                  </div>";
                        }
                    }
                    echo "<div><strong>Donation Status:</strong> {$acceptance_text}</div>
                          <div><strong>Last Updated:</strong> " . date('F j, Y') . "</div>
                        </div>
                      </div>";

                    echo "</div>"; // End foodbank-container
                }
            } else if ($_SERVER["REQUEST_METHOD"] == "POST") {
                echo "<div class='no-results'>
                        <div class='no-results-icon'>
                            <i class='fas fa-search'></i>
                        </div>
                        <h3>No Food Receivers Found</h3>
                        <p>No food receivers found near your location in <strong>" . htmlspecialchars($state) . "</strong>.</p>
                        <div class='no-results-suggestions'>
                            <p>Try:</p>
                            <ul>
                                <li>Expanding your search to nearby areas</li>
                                <li>Checking back later as new receivers join regularly</li>
                                <li>Contacting local community centers for alternatives</li>
                            </ul>
                        </div>
                      </div>";
            }
            ?>

        </section>
        <section class="user-details1">
            <!-- User details section -->
        </section>
    </section>

    <script>
        const GOOGLE_MAPS_API_KEY = "<?php echo $_ENV['GOOGLE_MAPS_API_KEY']; ?>";

        // Global variables
        let currentLocation = null;
        let searchResults = [];

        // Initialize the application when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            initializeApp();
        });

        // Initialize the application
        function initializeApp() {
            setupEventListeners();

            // Check if we already have search results displayed (page was reloaded after search)
            if (document.querySelector('.foodbank-container')) {
                displayLocationStatus("Location found. Showing food receivers near you sorted by distance.");
            } else {
                // Automatically get user location on page load
                getUserLocation();
            }
        }

        // Setup all event listeners
        function setupEventListeners() {
            // User icon menu toggle
            const userIcon = document.getElementById('user-icon');
            if (userIcon) {
                userIcon.addEventListener('click', toggleUserMenu);
            }

            // Hide menu when clicking outside
            document.addEventListener('click', handleOutsideMenuClick);

            // Get location button
            const getLocationBtn = document.getElementById('getLocationBtn');
            if (getLocationBtn) {
                getLocationBtn.addEventListener('click', handleLocationButtonClick);
            }

            // Search form submission
            const searchForm = document.getElementById('searchForm');
            if (searchForm) {
                searchForm.addEventListener('submit', handleSearchFormSubmit);
            }

            // Address input enter key
            const addressInput = document.getElementById('addressInput');
            if (addressInput) {
                addressInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        handleSearchFormSubmit(e);
                    }
                });

                // Auto-suggest functionality (optional enhancement)
                let debounceTimeout;
                addressInput.addEventListener('input', function(e) {
                    clearTimeout(debounceTimeout);
                    debounceTimeout = setTimeout(() => {
                        const query = e.target.value.trim();
                        if (query.length >= 3) {
                            // You can implement address autocomplete here if needed
                            // handleAddressAutocomplete(query);
                        }
                    }, 300);
                });
            }
        }

        // Toggle user menu
        function toggleUserMenu() {
            const otherMenu = document.getElementById('other-menu');
            if (otherMenu) {
                if (otherMenu.style.display === 'none' || otherMenu.style.display === '') {
                    otherMenu.style.display = 'block';
                } else {
                    otherMenu.style.display = 'none';
                }
            }
        }

        // Handle clicks outside the menu
        function handleOutsideMenuClick(event) {
            const menu = document.getElementById('other-menu');
            const userIcon = document.getElementById('user-icon');

            if (menu && userIcon &&
                !menu.contains(event.target) &&
                !userIcon.contains(event.target)) {
                menu.style.display = 'none';
            }
        }

        // Handle location button click
        function handleLocationButtonClick() {
            displayLocationStatus("Getting your location...");
            getUserLocation();
        }

        // Handle search form submission
        function handleSearchFormSubmit(e) {
            e.preventDefault();

            const addressInput = document.getElementById('addressInput');
            const address = addressInput.value.trim();

            if (address === '') {
                alert("Please enter an address before searching.");
                displayLocationStatus("Please enter an address before searching.");
                return false;
            }

            // Show loading state
            showLoadingState();

            // Perform the search
            fetchFoodbanks(address);
            return false;
        }

        // Get user's current location
        function getUserLocation() {
            const addressInput = document.getElementById('addressInput');

            if (!navigator.geolocation) {
                handleLocationError("Geolocation is not supported by this browser.");
                return;
            }

            // Show loading state
            displayLocationStatus("Detecting your location...");

            const options = {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000 // 5 minutes cache
            };

            navigator.geolocation.getCurrentPosition(
                handleLocationSuccess,
                handleLocationError,
                options
            );
        }

        // Handle successful location detection
        function handleLocationSuccess(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            currentLocation = {
                lat,
                lng
            };

            displayLocationStatus("Location detected. Getting address details...");

            // Reverse geocode to get address
            reverseGeocode(lat, lng)
                .then(address => {
                    const addressInput = document.getElementById('addressInput');
                    if (addressInput) {
                        addressInput.value = address;
                    }

                    displayLocationStatus("Location found: " + address);

                    // Automatically search for foodbanks using the detected address
                    fetchFoodbanks(address);
                })
                .catch(error => {
                    console.error("Error getting address:", error);
                    handleLocationError("Error getting your address. Please enter it manually.");
                });
        }

        // Handle location detection errors
        function handleLocationError(error) {
            let errorMessage = "";

            if (typeof error === 'string') {
                errorMessage = error;
            } else {
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = "Location access denied. Please enter your address manually.";
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = "Location information unavailable. Please enter your address manually.";
                        break;
                    case error.TIMEOUT:
                        errorMessage = "Location request timed out. Please enter your address manually.";
                        break;
                    default:
                        errorMessage = "Unknown error getting location. Please enter your address manually.";
                }
            }

            displayLocationStatus(errorMessage);
            console.error("Geolocation error:", error);
        }

        // Reverse geocode coordinates to address
        async function reverseGeocode(lat, lng) {
            const apiKey = GOOGLE_MAPS_API_KEY;
            const url = `https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=${apiKey}`;

            try {
                const response = await fetch(url);
                const data = await response.json();

                if (data.results && data.results.length > 0) {
                    return data.results[0].formatted_address;
                } else {
                    throw new Error("No address found for the given coordinates");
                }
            } catch (error) {
                throw new Error("Failed to get address from coordinates");
            }
        }

        // Fetch foodbanks based on address
        async function fetchFoodbanks(address) {
            if (!address || address.trim() === '') {
                displayError("Please provide a valid address.");
                return;
            }

            showLoadingState();

            const formData = new FormData();
            formData.append('addressInput', address.trim());
            formData.append('search-foodBank', 'true');

            console.log("Fetching foodbanks for address:", address);

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest' // Optional: helps server identify AJAX requests
                    }
                });

                console.log("Response status:", response.status);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const html = await response.text();
                console.log("Response received, parsing HTML...");

                // Parse the response and update the UI
                updateSearchResults(html);

                displayLocationStatus(`Search completed for: ${address}`);

            } catch (error) {
                console.error('Error fetching foodbanks:', error);
                displayError('Error fetching food receivers. Please check your connection and try again.');
            } finally {
                hideLoadingState();
            }
        }

        // Update search results with new HTML
        function updateSearchResults(html) {
            try {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Extract the search results from the response
                const newResultsSection = doc.querySelector('.results');
                const currentResultsSection = document.querySelector('.results');

                if (newResultsSection && currentResultsSection) {
                    // Update the entire results section
                    currentResultsSection.innerHTML = newResultsSection.innerHTML;

                    // Re-attach event listeners for new dynamic content
                    attachDynamicEventListeners();

                    // Scroll to results
                    currentResultsSection.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });

                    console.log("Search results updated successfully");
                } else {
                    throw new Error("Could not find results section in response");
                }
            } catch (error) {
                console.error('Error updating search results:', error);
                displayError('Error displaying search results. Please try again.');
            }
        }

        // Attach event listeners to dynamically loaded content
        function attachDynamicEventListeners() {
            // Re-attach details toggle buttons
            const detailButtons = document.querySelectorAll('.details-btn');
            detailButtons.forEach(button => {
                // Remove any existing listeners to prevent duplicates
                button.replaceWith(button.cloneNode(true));
            });

            // Re-attach the toggleDetails function to new buttons
            const newDetailButtons = document.querySelectorAll('.details-btn');
            newDetailButtons.forEach(button => {
                button.addEventListener('click', function() {
                    toggleDetails(this);
                });
            });

            // Attach any other dynamic content listeners here
            attachDonationFormListeners();
            attachImageErrorHandlers();
        }

        // Attach donation form listeners
        function attachDonationFormListeners() {
            const donationForms = document.querySelectorAll('form[action="donationform.php"]');
            donationForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    // Add any pre-submission validation or processing here
                    const receiverName = form.querySelector('input[name="receiver_name"]');
                    if (receiverName) {
                        console.log(`Initiating donation to: ${receiverName.value}`);
                    }

                    // You can add a confirmation dialog here if needed
                    // if (!confirm(`Proceed with donation to ${receiverName.value}?`)) {
                    //     e.preventDefault();
                    //     return false;
                    // }
                });
            });
        }

        // Handle image loading errors
        function attachImageErrorHandlers() {
            const foodbankImages = document.querySelectorAll('.foodbank-icon');
            foodbankImages.forEach(img => {
                img.addEventListener('error', function() {
                    // Fallback to default image if the original fails to load
                    this.src = 'uploads/food-bank-logo.png';
                });
            });
        }

        // Toggle details panel for food receivers
        function toggleDetails(button) {
            const container = button.closest('.foodbank-container');
            if (!container) return;

            const detailsPanel = container.querySelector('.details-panel');
            if (!detailsPanel) return;

            const isVisible = detailsPanel.style.display === 'block';

            if (isVisible) {
                detailsPanel.style.display = 'none';
                button.textContent = 'More Details';
                button.setAttribute('aria-expanded', 'false');
            } else {
                detailsPanel.style.display = 'block';
                button.textContent = 'Less Details';
                button.setAttribute('aria-expanded', 'true');

                // Smooth scroll to make sure the details are visible
                detailsPanel.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }
        }

        // Show loading state
        function showLoadingState() {
            const loadingIndicator = document.getElementById('loadingIndicator');
            const searchBtn = document.getElementById('searchBtn');
            const getLocationBtn = document.getElementById('getLocationBtn');

            if (loadingIndicator) {
                loadingIndicator.style.display = 'block';
            }

            if (searchBtn) {
                searchBtn.disabled = true;
                searchBtn.textContent = 'Searching...';
            }

            if (getLocationBtn) {
                getLocationBtn.disabled = true;
            }
        }

        // Hide loading state
        function hideLoadingState() {
            const loadingIndicator = document.getElementById('loadingIndicator');
            const searchBtn = document.getElementById('searchBtn');
            const getLocationBtn = document.getElementById('getLocationBtn');

            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
            }

            if (searchBtn) {
                searchBtn.disabled = false;
                searchBtn.textContent = 'Search';
            }

            if (getLocationBtn) {
                getLocationBtn.disabled = false;
            }
        }

        // Display location status messages
        function displayLocationStatus(message) {
            // You can create a status display element or use existing ones
            console.log("Location Status:", message);

            // Optional: Create a temporary status message
            showTemporaryMessage(message, 'info');
        }

        // Display error messages
        function displayError(message) {
            console.error("Error:", message);

            const jsResults = document.getElementById('jsResults');
            if (jsResults) {
                jsResults.innerHTML = `
                    <div class="error-message" style="
                        background: #f8d7da;
                        color: #721c24;
                        padding: 15px;
                        border-radius: 8px;
                        border: 1px solid #f5c6cb;
                        margin: 20px 0;
                        text-align: center;
                    ">
                        <i class="fa fa-exclamation-triangle" style="margin-right: 8px;"></i>
                        ${message}
                    </div>
                `;
            }

            showTemporaryMessage(message, 'error');
        }

        // Show temporary message
        function showTemporaryMessage(message, type = 'info') {
            // Remove existing temporary messages
            const existingMessages = document.querySelectorAll('.temp-message');
            existingMessages.forEach(msg => msg.remove());

            // Create new message element
            const messageDiv = document.createElement('div');
            messageDiv.className = 'temp-message';
            messageDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 20px;
                border-radius: 6px;
                z-index: 10000;
                max-width: 300px;
                font-weight: 500;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                transition: all 0.3s ease;
            `;

            // Set colors based on type
            switch (type) {
                case 'error':
                    messageDiv.style.background = '#f8d7da';
                    messageDiv.style.color = '#721c24';
                    messageDiv.style.borderLeft = '4px solid #dc3545';
                    break;
                case 'success':
                    messageDiv.style.background = '#d4edda';
                    messageDiv.style.color = '#155724';
                    messageDiv.style.borderLeft = '4px solid #28a745';
                    break;
                default: // info
                    messageDiv.style.background = '#d1ecf1';
                    messageDiv.style.color = '#0c5460';
                    messageDiv.style.borderLeft = '4px solid #17a2b8';
            }

            messageDiv.textContent = message;
            document.body.appendChild(messageDiv);

            // Remove after 5 seconds
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.style.opacity = '0';
                    messageDiv.style.transform = 'translateX(100%)';
                    setTimeout(() => messageDiv.remove(), 300);
                }
            }, 5000);
        }

        // Utility function to format distance
        function formatDistance(distance) {
            if (distance < 1) {
                return (distance * 1000).toFixed(0) + 'm';
            } else {
                return distance.toFixed(1) + 'km';
            }
        }

        // Utility function to validate email
        function validateEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Utility function to validate phone number
        function validatePhone(phone) {
            const phoneRegex = /^[\+]?[\d\s\-\(\)]{10,}$/;
            return phoneRegex.test(phone);
        }

        // Enhanced error handling for the entire application
        window.addEventListener('error', function(e) {
            console.error('Global error caught:', e.error);
            // You can send error reports to your server here if needed
        });

        // Handle unhandled promise rejections
        window.addEventListener('unhandledrejection', function(e) {
            console.error('Unhandled promise rejection:', e.reason);
            e.preventDefault(); // Prevent the default browser console error
        });

        // Optional: Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + S for search
            if (e.altKey && e.key === 's') {
                e.preventDefault();
                const addressInput = document.getElementById('addressInput');
                if (addressInput) {
                    addressInput.focus();
                }
            }

            // Alt + L for location
            if (e.altKey && e.key === 'l') {
                e.preventDefault();
                getUserLocation();
            }

            // Escape to close details panels
            if (e.key === 'Escape') {
                const openPanels = document.querySelectorAll('.details-panel[style*="display: block"]');
                openPanels.forEach(panel => {
                    const button = panel.parentNode.querySelector('.details-btn');
                    if (button) {
                        toggleDetails(button);
                    }
                });
            }
        });

        // Performance monitoring (optional)
        if ('performance' in window) {
            window.addEventListener('load', function() {
                const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
                console.log(`Page load time: ${loadTime}ms`);
            });
        }

        // Initialize everything when the script loads
        console.log("Food donation search system initialized");
    </script>
</body>

</html>