
<?php



if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['placeId'])) {
    print_r($_GET);

    $placeId = $_GET['placeId']; // Replace with the actual Place ID
    $apiKey = 'AIzaSyAB6oe7ZQVRZZFi6iNbTIsuqZSUQXaeiqU';; // Replace with your API key

    $url = "https://places.googleapis.com/v1/places/{$placeId}";

    $headers = [
        'Content-Type: application/json',
        "X-Goog-Api-Key: {$apiKey}",
        "X-Goog-FieldMask: id,displayName,photos" // Request photo information
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode === 200) {
        $placeDetails = json_decode($response, true);

        // Process the $placeDetails array to find the 'photos' array
        if (isset($placeDetails['photos']) && is_array($placeDetails['photos'])) {
            $photoReferences = [];
            foreach ($placeDetails['photos'] as $photo) {
                if (isset($photo['name'])) {
                    $photoReferences[] = $photo['name']; // This is the photo resource name
                }
            }
            // Now you have an array of photo resource names ($photoReferences)
            // Proceed to Step 2 for each photo reference
            echo"<pre>";
            print_r($placeDetails);
        } else {
            echo "No photos found for this place.\n";
        }

    } else {
        echo "Error fetching place details: HTTP status code {$httpCode}\n";
        echo "Response: {$response}\n";
    }

    curl_close($ch);

}

?>
