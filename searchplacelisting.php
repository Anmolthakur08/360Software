<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once './config.php';

// Google OAuth credentials
$refreshToken = '1//04w0iu9mQlRaRCgYIARAAGAQSNwF-L9IrSrDcuPRspUy7JQDP2jBSeaYXu0XfKbGYJ6qI_1yrfwvF0Uc_vd2pQAa5WyX0oQHFszg';
$clientId = '310344686192-560imlrkhos1eu4e5c11ttj6aaoo6rb8.apps.googleusercontent.com';
$clientSecret = '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';
$apiKey = 'AIzaSyAB6oe7ZQVRZZFi6iNbTIsuqZSUQXaeiqU'; // For Places API

// 🔁 Refresh access token
function refreshAccessToken($refreshToken, $clientId, $clientSecret) {
    $url = 'https://oauth2.googleapis.com/token';

    $postFields = [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'refresh_token' => $refreshToken,
        'grant_type' => 'refresh_token'
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => http_build_query($postFields),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded'
        ]
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        //echo "cURL error while refreshing token: " . curl_error($ch);
        return null;
    }

    curl_close($ch);
    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

// Get a fresh access token before making requests
$accessToken = refreshAccessToken($refreshToken, $clientId, $clientSecret);
if (!$accessToken) {
    echo "<p><strong>Error:</strong> Could not refresh access token.</p>";
    exit;
}
// echo '<pre>';
// print_r($accessToken);
// echo '<pre>';
// exit();

// 1. Places API call
function getPlacesList($textQuery, $apiKey) {
    $url = 'https://places.googleapis.com/v1/places:searchText';
    $fieldMask = 'places.id,places.displayName,places.formattedAddress,places.location,places.photos';

    $data = ['textQuery' => $textQuery];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Goog-Api-Key: ' . $apiKey,
            'X-Goog-FieldMask: ' . $fieldMask,
        ],
    ]);

    $response = curl_exec($ch);
    // echo '<pre>';
    // print_r($response);
    // echo '<pre>';
    // exit();
    if (curl_errno($ch)) {
        return ['error' => curl_error($ch)];
    }

    curl_close($ch);
    $textApi= json_decode($response, true);
    $_SESSION['textsearchAPIdata'] =$textApi; 
    return $textApi; 
}

// 3. Handle user search
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['searchImage'])) {
    $query = trim($_POST['searchImage']);
    $result = getPlacesList($query, $apiKey);

    if (isset($result['places']) && count($result['places']) > 0) {
        echo "<ul class='list-group'>";
        foreach ($result['places'] as $place) {
            $id = htmlspecialchars($place['id']);
            $name = htmlspecialchars($place['displayName']['text'] ?? 'No Name');
            $address = htmlspecialchars($place['formattedAddress']);
            $lat = $place['location']['latitude'] ?? null;
            $lng = $place['location']['longitude'] ?? null;

            echo "<li class='list-group-item' data-place-id='$id' data-place-name='$name'>
                    <input type='hidden' name='$id' value='$name' />
                    <input type='hidden' name='{$id}_lat' value='$lat' />
                    <input type='hidden' name='{$id}_long' value='$lng' />
                    <label>
                        <input type='radio' name='placeId' value='$id' />
                        <strong>$name</strong> - $address<br/>
                    </label>
                  </li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No places found matching your search.</p>";
    }
} else {
    echo "<p>Please enter a place name to search.</p>";
}
?>
