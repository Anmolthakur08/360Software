<?php
include_once './config.php';


$user = $_SESSION['user'];
$userId = $user['id'];

$stmt = $conn->prepare("SELECT refresh_token FROM google_users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($userRefreshToken);
$stmt->fetch();
$stmt->close();


$refreshToken = $userRefreshToken;
$clientId = '310344686192-560imlrkhos1eu4e5c11ttj6aaoo6rb8.apps.googleusercontent.com';  // Replace with your OAuth client ID
$clientSecret = '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';  // Replace with your OAuth client secret
$apiKey = 'AIzaSyAB6oe7ZQVRZZFi6iNbTIsuqZSUQXaeiqU';  // Replace with your API key


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
curl_setopt($ch, CURLOPT_POSTFIELDS, $tokenData);

$response = curl_exec($ch);
curl_close($ch);

$responseData = json_decode($response, true);
$accessToken = $responseData['access_token'] ?? die('Error getting access token');



$url = 'https://streetviewpublish.googleapis.com/v1/photos?view=BASIC';
// You can use 'view=INCLUDE_DOWNLOAD_URL' to get direct download links

$headers = [
    "Authorization: Bearer $accessToken"
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $photos = json_decode($response, true);
    echo "<pre>";
    print_r($photos); // Display list of uploaded photos
} else {
    echo "Error fetching photos: " . $response;
}


?>

<!-- <html>
    <head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
    </head>
    <body>
        <div class="container">
            <div class="row">
            
            <?php
            // if(isset($photos['photos'])){
            //     foreach($photos['photos'] as $photo){
            ?>
                        <div class="col-6">
                            
                        <iframe src="<?php //echo $photo['shareLink'] ?>" title="360 images shareable link"></iframe>
                        </div>
                            
                    <?php
                    //             }
                    // }
                    ?>
            </div>
        </div>
    </body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</html> -->