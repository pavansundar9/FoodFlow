<?php
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
    $apiKey = 'AIzaSyB6yIQr2JGOVXaDifaI_cE96odWcoNXsPA';
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
                if(isset($_SESSION['logged-in'])){
                    echo '<li><a href="profile.php">Profile</a></li>';
                    echo '<li><a href="logout.php">Logout</a></li>';
                }
                else
                    echo '<li><a href="login.php">Login/Register</a></li>';
            ?>
        </div>
    </nav>
    <section>
        <?php if ($error) {
            echo "<p class='error'>" . $error . "</p>";
        } ?>
        <form class="location-form" method="POST" action="" id="searchForm">
            <input class="input" type="text" id="addressInput" name="addressInput" placeholder="Enter your(pick-up) address">
            <button type="button" id="getLocationBtn" class="location-button">
                <i class="fa fa-location-dot" style="font-size: 30px"></i>
            </button>
            <button type="submit" name="search-foodBank" class="search-submit" id="searchBtn">Search</button>
        </form>
    </section>
    <!-- <section>
        <p id="location-display" style="margin-top: 10px; font-weight: bold;">Getting your location...</p>
    </section> -->
    <section class="the-details">
        <!-- Search Results -->
        <section class="results" id="searchResults">
            <!-- Dynamic content will be added here -->
            <div id="loadingIndicator" style="text-align: center; display: none;">
                <p>Searching for food receivers near you...</p>
                <!-- You can add a loading spinner here if desired -->
            </div>
            <div id="jsResults">
                <!-- JavaScript will populate this area -->
            </div>
            
            <?php
            if (!empty($foodbanks)) {
                echo "<p class='fetching'>Food receivers in <u>" . $state . "</u> near you, sorted by distance:</p>";
                echo "<ul>";
                foreach ($foodbanks as $bank) {
                    // Calculate distance between user and food bank
                    $distance = number_format($bank['distance'], 2); // Format distance to two decimal places

                    // Use a default image if the image_path is empty or not found
                    $default_image = 'uploads/food-bank-logo.png';
                    $image_path = (!empty($bank['image_path']) && file_exists($bank['image_path'])) ? $bank['image_path'] : $default_image;
                    
                    // Display each food bank as a container
                    echo "<div class='foodbank-container'>
                            <img class='foodbank-icon' src='{$image_path}' alt='Food Bank Icon'>
                            <div class='foodbank-details'>
                                <div style='display: flex; justify-content: space-between;'>
                                    <h3 class='foodbank-name'>{$bank['name']}</h3>";
                    echo ($bank['req_bool'] == 'yes') ? "<span class='req'>{$bank['daily_count']}/{$bank['req_people']}</span>" : ""; // Conditionally display req_bool
                    echo "
                                </div>
                                <p class='foodbank-address'>{$bank['address']}</p>
                                <p class='foodbank-phone'>{$bank['phone']}</p>
                                <p class='foodbank-distance'>{$distance} km away</p>
                                <form method='POST' action='donationform.php' style='align-self: flex-end;'>
                                    <input type='hidden' name='receiver_name' value='{$bank['name']}'>
                                    <input type='hidden' name='receiver_email' value='{$bank['email']}'>
                                    <input type='hidden' name='receiver_address' class='foodbank-address' value='{$bank['address']}'>
                                    <input type='hidden' name='receiver_phone' class='foodbank-phone' value='{$bank['phone']}'>
                                    <input type='hidden' name='receiver_distance' class='foodbank-distance' value='{$bank['distance']}'>
                                    <button type='submit' class='donate-btn'>Donate</button>
                                </form>
                            </div>
                        </div>";
                }
                echo "</ul>";
                $_SESSION['receiver_email'] = $bank['email'];
            } else if ($_SERVER["REQUEST_METHOD"] == "POST") {
                echo "<p>No food banks found near your location.</p>";
            }
            ?>
        </section>
        <section class="user-details1">
            <!-- User details section -->
        </section>
    </section>
    <script>
        document.getElementById('user-icon').addEventListener('click', function() {
            const other_menu = document.getElementById('other-menu');
            if (other_menu.style.display === 'none' || other_menu.style.display === '') {
                other_menu.style.display = 'block';
            } else {
                other_menu.style.display = 'none';
            }
        });

        // Hide the menu if clicked outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('other-menu');
            const userIcon = document.getElementById('user-icon');
            if (!menu.contains(event.target) && !userIcon.contains(event.target)) {
                menu.style.display = 'none';
            }
        });

        // Function to fetch foodbanks based on address
        async function fetchFoodbanks(address) {
            document.getElementById('loadingIndicator').style.display = 'block'; // Show the loading indicator

            const formData = new FormData();
            formData.append('addressInput', address);
            formData.append('search-foodBank', 'true');
            console.log("Fetching foodbanks for address:", address);
            console.log("Form data:", formData);

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                console.log("Response status:", response.status);

                console.log("Response URL:", response.url);
                console.log("Response headers:", response.headers);
                if (response.ok) {
                    const html = await response.text(); // Get the response as HTML
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    console.log("Parsed HTML:", doc);
                    // Extract the search results from the response
                    const results = doc.querySelector('#searchResults').innerHTML;

                    // Update the search results dynamically
                    document.getElementById('searchResults').innerHTML = results;

                    // Hide the loading indicator
                    document.getElementById('loadingIndicator').style.display = 'none';
                } else {
                    throw new Error('Failed to fetch food receivers.');
                }
            } catch (error) {
                console.error('Error fetching foodbanks:', error);
                document.getElementById('loadingIndicator').style.display = 'none';
                document.getElementById('jsResults').innerHTML = '<p>Error fetching food receivers. Please try again.</p>';
            }
        }

        // Get location automatically on page load
        function getUserLocation() {
            // const locationDisplay = document.getElementById('location-display');
            const addressInput = document.getElementById('addressInput');
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        // Reverse geocode to get address
                        fetch(`https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=AIzaSyB6yIQr2JGOVXaDifaI_cE96odWcoNXsPA`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.results && data.results.length > 0) {
                                    const address = data.results[0].formatted_address;
                                    addressInput.value = address;
                                    // locationDisplay.innerText = "Location detected: " + address;
                                    
                                    // Automatically search for foodbanks using the detected address
                                    fetchFoodbanks(address);
                                } else {
                                    // locationDisplay.innerText = "Couldn't get your address. Please enter it manually.";
                                }
                            })
                            .catch(error => {
                                console.error("Error getting address:", error);
                                // locationDisplay.innerText = "Error getting your address. Please enter it manually.";
                            });
                    },
                    function(error) {
                        console.error("Geolocation error:", error);
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                alert("User denied the request for Geolocation.");
                                // locationDisplay.innerText = "Location access denied. Please enter your address manually.";
                                break;
                            case error.POSITION_UNAVAILABLE:
                                alert("Location information is unavailable.");
                                // locationDisplay.innerText = "Location information unavailable. Please enter your address manually.";
                                break;
                            case error.TIMEOUT:
                                alert("The request to get user location timed out.");
                                // locationDisplay.innerText = "Location request timed out. Please enter your address manually.";
                                break;
                            default:
                                alert("An unknown error occurred.");
                                // locationDisplay.innerText = "Unknown error getting location. Please enter your address manually.";
                        }
                    }
                );
            } else {
                // Browser doesn't support Geolocation
                alert("Geolocation is not supported by this browser.");
                // locationDisplay.innerText = "Geolocation not supported in your browser. Please enter your address manually.";
            }
        }

        // Run immediately on page load
        window.onload = function() {
            // Check if we already have search results displayed (page was reloaded after search)
            if (document.querySelector('.foodbank-container')) {
                // document.getElementById('location-display').innerText = 
                //     "Location found. Showing food receivers near you sorted by distance.";
                // No need to call getUserLocation again, as we already have results
                alert("Location found. Showing food receivers near you sorted by distance.");
            } else {
                getUserLocation();
            }
        };

        // Also attach to button for manual refresh
        document.getElementById('getLocationBtn').addEventListener('click', function() {
            // document.getElementById('location-display').innerText = "Getting your location...";
            getUserLocation();
        });

        // Handle form submission via JavaScript
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            const addressInput = document.getElementById('addressInput');
            if (addressInput.value.trim() === '') {
                e.preventDefault();
                alert("Please enter an address before searching.");
                // document.getElementById('location-display').innerText = "Please enter an address before searching.";
            }
        });
    </script>
</body>
</html>