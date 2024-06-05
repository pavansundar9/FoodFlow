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
    if(isset($_SESSION['type'])) {
        $type = $_SESSION['type'];
        
        // If the user is a doner, fetch their details
        if ($type == 'doner') {
            $doner_email = $_SESSION['email'];
            $doner_name = $_SESSION['name'];
            $doner_c_number = $_SESSION['c_number'];
            $table = 'fooddoners';
            
            // Query to fetch all records from the fooddoners table
            $sql = "SELECT * FROM $table";
            $result = mysqli_query($conn, $sql);
            
            // Fetch the first row from the result set
            if ($row = mysqli_fetch_assoc($result)) {
                $image_path = $row['image_path'];
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
    function get_IP_address(){
        foreach (array('HTTP_CLIENT_IP',
                    'HTTP_X_FORWARDED_FOR',
                    'HTTP_X_FORWARDED',
                    'HTTP_X_CLUSTER_CLIENT_IP',
                    'HTTP_FORWARDED_FOR',
                    'HTTP_FORWARDED',
                    'REMOTE_ADDR') as $key){
            if (array_key_exists($key, $_SERVER) === true){
                foreach (explode(',', $_SERVER[$key]) as $IPaddress){
                    $IPaddress = trim($IPaddress);

                    if (filter_var($IPaddress,
                                FILTER_VALIDATE_IP,
                                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
                        !== false) {

                        return $IPaddress;
                    }
                }
            }
        }
        return null;
    }

    function searchFoodbank($address){
        global $conn;
    
        // Define search radius (in kilometers)
        $radius = 10;
    
        // Geocode the user's address
        $user_coordinates = getLatLongFromAddress($address);
        // echo $user_coordinates;
        // Check if geocoding was successful
        if ($user_coordinates !== false) {
            $user_latitude = $user_coordinates['latitude'];
            $user_longitude = $user_coordinates['longitude'];
    
            // SQL query to select all food banks
            $sql = "SELECT * FROM foodreceivers";
    
            $result = mysqli_query($conn, $sql);
    
            // Check for errors in query execution
            if (!$result) {
                die('Error in SQL query: ' . mysqli_error($conn));
            }
    
            // Array to store food bank details
            $foodbanks = array();
            // print_r($foodbanks);
            // Fetch results and store in array
            while ($row = mysqli_fetch_assoc($result)) {
                // Geocode each food bank's address
                $foodbank_coordinates = getLatLongFromAddress($row['address']);
                if ($foodbank_coordinates !== false) {
                    $foodbank_latitude = $foodbank_coordinates['latitude'];
                    $foodbank_longitude = $foodbank_coordinates['longitude'];
    
                    // Calculate distance between user and food bank
                    $distance = calculateDistance($user_latitude, $user_longitude, $foodbank_latitude, $foodbank_longitude);
    
                    // Add food bank details along with distance to the array
                    $row['distance'] = $distance;
                    $foodbanks[] = $row;
                }
            }
    
            // Sort food banks by distance
            usort($foodbanks, function($a, $b) {
                return $a['distance'] - $b['distance'];
            });
    
            // Filter food banks within the search radius
            $filtered_foodbanks = array_filter($foodbanks, function($bank) use ($radius) {
                return $bank['distance'] < $radius;
            });
    
            // Return filtered food banks
            return $filtered_foodbanks;
        } else {
            // Geocoding failed
            return false;
        }
    }
    
    function getLatLongFromAddress($address){
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
            
            // Loop through address components to find the city
            $city = '';
            foreach ($response['results'][0]['address_components'] as $component) {
                if (in_array('locality', $component['types'])) {
                    $city = $component['long_name'];
                    break;
                }
            }
            
            return array('latitude' => $latitude, 'longitude' => $longitude, 'city' => $city);
        } else {
            return false; // No results found
        }
    }
    
    function getAddressFromLatLong($lat,$long){
        // Make request to Nominatim
        $apiKey = 'AIzaSyB6yIQr2JGOVXaDifaI_cE96odWcoNXsPA';
        $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$lat},{$long}&key={$apiKey}";
        $response = file_get_contents($url);

        // Handle response
        if ($response !== false) {
            $result = json_decode($response);

            if ($result && isset($result->results[0]->formatted_address)) {
                $address = $result->results[0]->formatted_address;
                // echo "<br>Address: $address";
                return $address;
            } else {
                echo "Unable to retrieve address.";
                // return null;
            }
        } else {
            echo "Failed to fetch data.";
            // return null;
        }
    }

    function calculateDistance($lat1, $lon1, $lat2, $lon2) {
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
            <li class="main-menu"><a href="#home">Home</a></li>
            <li class="main-menu"><a href="#services">Services</a></li>
            <li class="main-menu"><a href="#about">About Us</a></li>
            <li class="main-menu"><a href="#contact">Contact</a></li>
            <li class="user"><p class="user-details"><?php if(isset($_SESSION['email'])) echo $doner_email; else echo "Guest&nbsp;";  ?></p><a href="javascript:void(0)"><div class="user-icon"><img src="<?php if(isset($image_path)) echo $image_path; else echo "images/user.png" ?>" alt="user icon" id="user-icon"></div></a></li>
        </ul>
        <div id="other-menu">
            <li class="main-menu-other"><a href="#home">Home</a></li>
            <li class="main-menu-other"><a href="#services">Services</a></li>
            <li class="main-menu-other"><a href="#about">About Us</a></li>
            <li class="main-menu-other"><a href="#contact">Contact</a></li>
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
        <?php if($error){ echo "<p class='error'>".$error."</p>";}?>
        <form class="location-form" method="POST" action="">
            <input class="input" type="text" id="addressInput" name="addressInput" placeholder="Enter your(pick-up) address">
            <button type="submit" id="getLocationBtn" name="getLocationBtn" class="location-button">
                <i class="fa fa-location-dot" style="font-size: 30px"></i>
            </button>            
            <button type="submit" name="search-foodBank" class="search-submit">Search</button>
        </form>
    </section>
    <section class="the-details">
        <!-- Search Results -->
        <section class="results" id="searchResults">
            <!-- Dynamic content will be added here -->
            <?php
                if($_SERVER["REQUEST_METHOD"] == "POST")  {
                    if (isset($_POST['search-foodBank'])){
                        $address = $_POST['addressInput'];
                        // echo $address;
                        // Validate address input
                        if (empty($address)) {
                            $error = "Please enter an address";
                            echo "<p class='error'>Please enter an address</p>";
                        } else {
                            // echo "<br>address is not empty!!";
                            $coordinates = getLatLongFromAddress($address);
                            // echo $coordinates;
                            if ($coordinates !== false) {
                                $latitude = $coordinates['latitude'];
                                $longitude = $coordinates['longitude'];
                                $city = $coordinates['city'];
                                // $output = "Latitude: $latitude, Longitude: $longitude, City: $city";
                                echo "<p class='fetching'>Fetching food receivers in <u>".$city."</u> near you.";
                                // Fetch food banks near the provided coordinates
                                $foodbanks = searchFoodbank($address);
                                // echo $address;
                                if (!empty($foodbanks)) {
                                    // echo "<p class='fetching'>Fetching food banks in <u>".$city."</u> near you.</p>";
                                    // Sort food banks by distance
                                    usort($foodbanks, function($a, $b) {
                                        return $a['distance'] - $b['distance'];
                                    });
                                    // Display the list of food banks
                                    echo "<ul>";
                                    foreach ($foodbanks as $bank) {
                                        // Calculate distance between user and food bank
                                        $distance = number_format($bank['distance'], 2); // Format distance to two decimal places
                                        // Display each food bank as a container
                                        echo "<div class='foodbank-container'>
                                            <img class='foodbank-icon' src='{$bank['image_path']}'>
                                            <div class='foodbank-details'>
                                                <h3 class='foodbank-name'>{$bank['name']}</h3>
                                                <p class='receiver-type'>{$bank['receiver_type']}</p>
                                                <p class='foodbank-address'>{$bank['address']}</p>
                                                <p class='foodbank-phone'>{$bank['c_number']}</p>
                                                <p class='foodbank-distance'>Distance: {$distance} km</p> 
                                                <form method='POST' action='donationform.php' style='align-self: flex-end;'>
                                                    <input type='hidden' name='receiver_name' value='{$bank['name']}'>
                                                    <input type='hidden' name='receiver_email' value='{$bank['email']}'>
                                                    <input type='hidden' name='receiver_type' value='{$bank['receiver_type']}'>
                                                    <input type='hidden' name='receiver_address' value='{$bank['address']}'>
                                                    <input type='hidden' name='receiver_phone' value='{$bank['c_number']}'>
                                                    <input type='hidden' name='receiver_distance' value='{$bank['distance']}'>
                                                    <button type='submit' class='donate-btn'>Donate</button>
                                                </form>
                                            </div>
                                        </div>";
                                    }                                    
                                    echo "</ul>";

                                } else {
                                    echo "<p>No food banks found near your location.</p>";
                                }

                            } else {
                                $error = "Failed to fetch coordinates. Please try again.";
                            }
                        }
                    }
                    else if(isset($_POST['getLocationBtn'])){
                        // Retrieve the user's location
                        $ip = get_IP_address();
                        $json = @file_get_contents("http://ip-api.com/json/$ip");
                        $data = json_decode($json);
                    
                        if(@$data->status=="fail"){
                            $error = "Could not retrieve your geolocation.";
                            die("Could not retrieve your geolocation.");
                        } else {
                            $city = $data->city;
                            $latitude = $data->lat;
                            $longitude = $data->lon;
                            // echo $city.", ".$latitude.", ".$longitude;
                            // Search for food banks near the obtained coordinates
                            $address = getAddressFromLatLong($latitude, $longitude);
                            // echo $address;
                            if($address){
                                $foodbanks = searchFoodbank($address);
                            
                                if (!empty($foodbanks)) {
                                    // Sort food banks by distance
                                    usort($foodbanks, function($a, $b) {
                                        return $a['distance'] - $b['distance'];
                                    });
                            
                                    echo "<p class='fetching'>Fetching food banks in <u>".$city."</u> near you.</p>";
                                    
                                    // Display the list of food banks
                                    echo "<ul>";
                                    foreach ($foodbanks as $bank) {
                                        // Calculate distance between user and food bank
                                        $distance = number_format($bank['distance'], 2); // Format distance to two decimal places
                                        // Display each food bank as a container
                                        echo "<div class='foodbank-container'>
                                        <img class='foodbank-icon' src='{$bank['image_path']}'>
                                        <div class='foodbank-details'>
                                            <div style='display: flex; justify-content: space-between;'>
                                                <h3 class='foodbank-name'>{$bank['name']}</h3>";
                                                echo ($bank['req_bool'] == 'yes') ? "<span class='req'>{$bank['daily_count']}/{$bank['req_people']}</span>" : ""; // Conditionally display req_bool
                                                echo "
                                            </div>
                                            <p class='receiver-type'>{$bank['receiver_type']}</p>
                                            <p class='foodbank-address'>{$bank['address']}</p>
                                            <p class='foodbank-phone'>{$bank['c_number']}</p>
                                            <p class='foodbank-distance'>{$distance} km away</p>
                                            <form method='POST' action='donationform.php' style='align-self: flex-end;'>
                                                <input type='hidden' name='receiver_name' value='{$bank['name']}'>
                                                <input type='hidden' name='receiver_email' value='{$bank['email']}'>
                                                <input type='hidden' name='receiver_type' value='{$bank['receiver_type']}'>
                                                <input type='hidden' name='receiver_address' value='{$bank['address']}'>
                                                <input type='hidden' name='receiver_phone' value='{$bank['c_number']}'>
                                                <input type='hidden' name='receiver_distance' value='{$bank['distance']}'>
                                                <button type='submit' class='donate-btn'>Donate</button>
                                            </form>
                                        </div>
                                    </div>";
                                    }                                    
                                    echo "</ul>";

                                } else {
                                    echo "<p>No food banks found near your location.</p>";
                                }
                            } else {
                                $error = "Failed to fetch coordinates. Please try again.";
                            }
                            // Display the fetched city for confirmation
                            // echo "<p class='fetching'>Fetching food receivers in <u>".$city."</u> near you.";
                        }
                    }
                    
                }
            ?>
        </section>
        <section class="user-details1">
            <!-- <center><h2 class="user-details-title">User Details</h2></center> -->
            
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
    async function getCurrentPosition() {
        return new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(resolve, reject);
        });
    }
    async function fetchUserLocation() {
        try {
            // Get the user's current location using Geolocation API
            const position = await getCurrentPosition();
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;

            // Fetch city data from BigDataCloud API
            const response = await fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lon}`);
            const data = await response.json();
            const city = data.city;

            console.log(city);
        } catch (error) {
            console.error('Error fetching user location:', error);
        }
    }
    

</script>

</body>
</html>
