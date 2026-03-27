<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once './config.php';

if (isset($_GET['id'])) {
    $storeId = intval($_GET['id']);
    if (!isset($_GET['id'])) {
        $_SESSION['publishallimageserror'] = 'Store ID not provided.';
        header("Location: g_storeimages.php?id=$storeId");
        exit();
    }
}

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

// === FUNCTIONS === //
function getAccessToken($clientId, $clientSecret, $refreshToken,$storeId)
{
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $tokenData = [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'refresh_token' => $refreshToken,
        'grant_type' => 'refresh_token'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));

    $response = curl_exec($ch);
    if ($response === false) {
         $_SESSION['updimgplaceerror'] = 'Curl error:';
         header("Location: g_storeimages.php?id=$storeId");  
         exit();   
    }
    curl_close($ch);

    $responseData = json_decode($response, true);
    if (!isset($responseData['access_token'])) {
         $_SESSION['updimgplaceerror'] = 'Error obtaining access token: ';
         header("Location: g_storeimages.php?id=$storeId");  
         exit();  
    }
    
    return $responseData['access_token'];
}

function updateImagesAttribute($updateRequests, $accessToken,$storeId)
{
    $url = "https://streetviewpublish.googleapis.com/v1/photos:batchUpdate";
    $headers = [
        "Authorization: Bearer $accessToken",
        "Content-Type: application/json"
    ];

    $payload = json_encode($updateRequests);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);
    if ($response === false) {
         $_SESSION['updimgplaceerror'] = 'Curl error:';
         header("Location: g_storeimages.php?id=$storeId");  
         exit();
    }
    curl_close($ch);
    //print_r($response);
    //die();
    return json_decode($response, true);

}

$storeId = $_GET['id'] ?? null;
if (!$storeId) {
       //die("");
       $_SESSION['updimgplaceerror'] = 'Missing  parameter in URL. Use ?id=123';
         header("Location: g_storeimages.php?id=$storeId");  
         exit();  
}

// Fetch photo IDs and place ID
$stmt = $conn->prepare("SELECT 360_i.360photoId, 360_s.placeId FROM 360g_storeimages as 360_i INNER JOIN 360g_stores as 360_s ON 360_s.id=360_i.storeID  WHERE storeID = ?");
$stmt->bind_param("i", $storeId);
$stmt->execute();
$result = $stmt->get_result();


sleep(30);
$photoIds = [];
$placeId = null;

while ($row = $result->fetch_assoc()) {
    // Only add photoIds if not empty/null
    if (!empty($row['360photoId'])) {
        $photoIds[] = $row['360photoId'];
    }
    // Assign placeId (assuming all rows have same placeId)
    if (!$placeId && !empty($row['placeId'])) {
        $placeId = $row['placeId'];
    }
}
$stmt->close();
if (empty($photoIds)) {
    $_SESSION['updimgplaceerror'] = 'No photo IDs found for store ID ';
         header("Location: g_storeimages.php?id=$storeId");  
         exit();  
}
if (!$placeId) {
    $_SESSION['updimgplaceerror'] = 'No place ID found for store ID';
         header("Location: g_storeimages.php?id=$storeId");  
         exit();  
}

// Get access token
$accessToken = getAccessToken($clientId, $clientSecret, $refreshToken,$storeId);

// Prepare batch update requests
$updateRequests = [
    'updatePhotoRequests' => []
];

foreach ($photoIds as $photoId) {
    $updateRequests['updatePhotoRequests'][] = [
        'photo' => [
            'photoId' => ['id' => $photoId],
            'places' => [
                ['placeId' => $placeId]
            ],
        ],
        'updateMask' => 'places',
    ];
}

// print_r($updateRequests);
// die();
// Call batchUpdate API once for all photos
$response = updateImagesAttribute($updateRequests, $accessToken,$storeId);

// Output response nicely
//echo "<pre>";
//print_r($response);
//echo "</pre>";

// Optional: Redirect after process completes (e.g., to success page)
if (!isset($response['error'])) {
      include_once './config.php';

    $updateStmt = $conn->prepare("UPDATE 360g_storeimages SET 360status = 'published', 360placeId= ? WHERE storeID = ?");
    if (!$updateStmt) {
        $_SESSION['updimgplaceerror'] = 'Prepare failed:';
         header("Location: g_storeimages.php?id=$storeId");  
         exit();  
    }

    foreach ($photoIds as $photoId) {
        $updateStmt->bind_param("si", $placeId ,$storeId);
        $updateStmt->execute();
    }

    $updateStmt->close();
    $conn->close();
    $_SESSION['updimgplacesuccess'] = 'All Images are Published and Connected to Place';
    header("Location: g_storeimages.php?id=$storeId"); // Change this URL to your destination
    exit;
} else {
     $_SESSION['updimgplaceerror'] = 'An error occurred during batch update.';
         header("Location: g_storeimages.php?id=$storeId");  
         exit();  
    
}