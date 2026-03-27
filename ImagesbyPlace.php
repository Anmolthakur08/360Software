<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once './config.php';
$storeId = isset($_GET['id']) ? $_GET['id'] : null;

$user = $_SESSION['user'];
        $userId = $user['id'];

        $stmt = $conn->prepare("SELECT refresh_token FROM google_users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);   
        $stmt->execute();
        $stmt->bind_result($userRefreshToken);
        $stmt->fetch();
        $stmt->close();



        // Google API credentials
$refreshToken = $userRefreshToken;
$clientId = '310344686192-560imlrkhos1eu4e5c11ttj6aaoo6rb8.apps.googleusercontent.com';  
$clientSecret = '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';  
$apiKey = 'AIzaSyAB6oe7ZQVRZZFi6iNbTIsuqZSUQXaeiqU';  

// function getAccessToken($clientId, $clientSecret, $refreshToken) {
//     $tokenUrl = 'https://oauth2.googleapis.com/token';
//     $tokenData = [
//         'client_id' => $clientId,
//         'client_secret' => $clientSecret,
//         'refresh_token' => $refreshToken,
//         'grant_type' => 'refresh_token'
//     ];

//     $ch = curl_init();
//     curl_setopt($ch, CURLOPT_URL, $tokenUrl);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_POST, true);
//     curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));

//     $response = curl_exec($ch);
//     if ($response === false) {
//         die('Curl error: ' . curl_error($ch));
//     }
//     curl_close($ch);

//     $responseData = json_decode($response, true);
//     if (!isset($responseData['access_token'])) {
//         die('Error obtaining access token: ' . json_encode($responseData));
//     }
//     return $responseData['access_token'];
// }

// function getPlace($textQuery, $apiKey, $accessToken) {
//     $fieldMask = 'places.id,places.name,places.googleMapsUri,places.displayName,places.formattedAddress';
//     $url = 'https://places.googleapis.com/v1/places:searchText';

//     $data = ['textQuery' => $textQuery];
//     $ch = curl_init($url);

//     curl_setopt_array($ch, [
//         CURLOPT_POST => true,
//         CURLOPT_POSTFIELDS => json_encode($data),
//         CURLOPT_RETURNTRANSFER => true,
//         CURLOPT_HTTPHEADER => [
//             'Content-Type: application/json',
//             'X-Goog-Api-Key: ' . $apiKey,
//             'X-Goog-FieldMask: ' . $fieldMask,
//         ],
//     ]);

//     $response = curl_exec($ch);

//     if (curl_errno($ch)) {
//         echo 'Error: ' . curl_error($ch);
//         curl_close($ch);
//         return null;
//     }

//     curl_close($ch);
//     return $response;
// }




if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['placeId']) && $storeId) {
    $placeNameWithCountry = trim($_POST['placeId']);
    if ($placeNameWithCountry === '') {
        die('Error: Please select a place.');
    }

    // $accessToken = getAccessToken($clientId, $clientSecret, $refreshToken);
    // $placeAPIRes = getPlace($placeNameWithCountry, $apiKey, $accessToken);

    // === Print the raw API response here for debugging ===
   // echo "<pre>";
   // echo htmlspecialchars($placeAPIRes);
   // echo "</pre>";

    // $placeAPIResData = json_decode($placeAPIRes, true);

    if (isset($_POST['placeId'])) {
        $placeId = $_POST['placeId'];
        $placeName = $_POST[$placeId];

        if ($conn->connect_error) {
            die("Database connection failed: " . $conn->connect_error);
        }
        

        // ✅ Update placeId

        $updateSql = "UPDATE 360g_storeimages SET `360placeId` = ?, `placeName` = ? WHERE storeID = ?";
        $stmt = $conn->prepare($updateSql);

        $stmt->bind_param("sss", $placeId, $placeName, $storeId);

        if ($stmt->execute()) {
            header("Location: publishallimages.php?id=$storeId");
            exit();
        } else {
            echo "❌ Error updating database: " . $stmt->error;
        }

        $stmt->close();
        $conn->close();
    } else {
        echo "❌ Please select a place";
    }
} else {
    echo "❗ Invalid request. Please submit a place name and ensure store ID is provided in the URL.";
}
?>
