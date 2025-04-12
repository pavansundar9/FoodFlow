<?php
// Function to get latitude, longitude and city name
function getGeoLocation($address) {
    // Replace spaces with plus signs for URL compatibility
    $formattedAddress = str_replace(' ', '+', $address);
    
    // Google Maps Geocoding API URL
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$formattedAddress}&key=AIzaSyB6yIQr2JGOVXaDifaI_cE96odWcoNXsPA";
    
    // Get the JSON response from the API
    $responseJson = file_get_contents($url);
    echo $responseJson.'<br><br>';
    $response = json_decode($responseJson, true);
    print_r($response);
    
    // Initialize variables
    $latitude = $longitude = $city = '';
    
    // Check if the response contains results
    if (!empty($response['results'])) {
        // Get latitude and longitude
        $geometry = $response['results'][0]['geometry'];
        $latitude = $geometry['location']['lat'];
        $longitude = $geometry['location']['lng'];
        
        // Loop through address components to find the city
        foreach ($response['results'][0]['address_components'] as $component) {
            if (in_array('locality', $component['types'])) {
                $city = $component['long_name'];
                break;
            }
        }
    }
    
    // Return the results as an array
    return array('latitude' => $latitude, 'longitude' => $longitude, 'city' => $city);
}

// Example usage:
$address = "1600 Amphitheatre Parkway, Mountain View, CA";
$locationInfo = getGeoLocation($address);
echo "Latitude: " . $locationInfo['latitude'] . "\n";
echo "Longitude: " . $locationInfo['longitude'] . "\n";
echo "City: " . $locationInfo['city'] . "\n";
?>
