<?php
/* Buffer output so headers & JSON stay clean */

header('Content-Type: application/json');
include_once './config.php';        // ensure this file is silent (no echo)

/* Read JSON sent from the browser */
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

$id_token = $data['id_token'] ?? null;
if (!$id_token) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing ID token']);
    ob_end_flush(); exit;
}

/* Verify ID token with Google */
$verify_url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($id_token);
$response   = @file_get_contents($verify_url);
if (!$response) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Google token verification failed']);
    ob_end_flush(); exit;
}

$token = json_decode($response, true);

/* Audience check */
$CLIENT_ID = '310344686192-560imlrkhos1eu4e5c11ttj6aaoo6rb8.apps.googleusercontent.com';
if (($token['aud'] ?? '') !== $CLIENT_ID) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid client ID']);
    ob_end_flush(); exit;
}

/* Expiry check */
if (isset($token['exp']) && $token['exp'] < time()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Token has expired']);
    ob_end_flush(); exit;
}

/* All good: create session */
$_SESSION['user'] = [
    'id'     => $token['sub'],
    'email'  => $token['email']        ?? '',
    'name'   => $token['name']         ?? '',
    'avatar' => $token['picture']      ?? '',
];

/* Respond */
echo json_encode([
    'success' => true,
    'email'   => $token['email'],
    'name'    => $token['name']        ?? ''
]);

?>
