<?php
header('Content-Type: application/json');
session_start();
include_once './config.php';  // defines $conn (mysqli)

$body = json_decode(file_get_contents('php://input'), true);
$code = $body['code'] ?? null;


if (!$code) {
  http_response_code(400);
  exit(json_encode(['success' => false, 'error' => 'Missing code or verifier']));
}

/* ---------------------------------------------------------------
   1. Exchange authorization code for tokens
   ------------------------------------------------------------- */
$tokenResp = httpPost('https://oauth2.googleapis.com/token', [
  'code' => $code,
  'client_id' => '310344686192-560imlrkhos1eu4e5c11ttj6aaoo6rb8.apps.googleusercontent.com',
  'client_secret' => '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',
  'redirect_uri' => 'postmessage',
  'grant_type' => 'authorization_code',

]);


if (isset($tokenResp['error'])) {
  http_response_code(400);
  exit(json_encode(['success' => false, 'error' => $tokenResp['error_description'] ?? $tokenResp['error']]));
}

$access_token = $tokenResp['access_token'];
$refresh_token = $tokenResp['refresh_token'] ?? null;
$expires_at = time() + $tokenResp['expires_in'];

/* ---------------------------------------------------------------
   2. Decode ID token to extract profile info
   ------------------------------------------------------------- */
$jwtParts = explode('.', $tokenResp['id_token']);
$payload = json_decode(base64_decode(strtr($jwtParts[1], '-_', '+/')), true);
$userGoogleId = $payload['sub'] ?? '';
$email = $payload['email'] ?? '';
$name = $payload['name'] ?? '';
$avatar = $payload['picture'] ?? '';

/* ---------------------------------------------------------------
   3. Persist user to DB (mysqli version)
   ------------------------------------------------------------- */
function upsertUser(array $data): int
{
  global $conn;

  $stmt = $conn->prepare("SELECT id FROM google_users WHERE google_sub = ?");
  $stmt->bind_param('s', $data['google_sub']);
  $stmt->execute();
  $result = $stmt->get_result();
  $existing = $result->fetch_assoc()['id'] ?? null;
  $stmt->close();

  if ($existing) {
    if ($data['refresh_token']) {
      $stmt = $conn->prepare("UPDATE google_users
        SET email = ?, name = ?, avatar = ?, access_token = ?, expires_at = ?, refresh_token = ?
        WHERE id = ?");
      $stmt->bind_param(
        'ssssisi',
        $data['email'],
        $data['name'],
        $data['avatar'],
        $data['access_token'],
        $data['expires_at'],
        $data['refresh_token'],
        $existing
      );
    } else {
      $stmt = $conn->prepare("UPDATE google_users
        SET email = ?, name = ?, avatar = ?, access_token = ?, expires_at = ?
        WHERE id = ?");
      $stmt->bind_param(
        'ssssii',
        $data['email'],
        $data['name'],
        $data['avatar'],
        $data['access_token'],
        $data['expires_at'],
        $existing
      );
    }
    $stmt->execute();
    $stmt->close();
    return (int) $existing;
  }

  $stmt = $conn->prepare("INSERT INTO google_users
    (google_sub, email, name, avatar, refresh_token, access_token, expires_at)
    VALUES (?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param(
    'ssssssi',
    $data['google_sub'],
    $data['email'],
    $data['name'],
    $data['avatar'],
    $data['refresh_token'],
    $data['access_token'],
    $data['expires_at']
  );
  $stmt->execute();
  $insertId = $stmt->insert_id;
  $stmt->close();
  $uploadDir = __DIR__ . '/upload/' . $insertId;
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }
  return (int) $insertId;
}

function updateTokensInDB(int $userId, array $tokenSet): void
{
  global $conn;
  $expires_at = time() + $tokenSet['expires_in'];
  $stmt = $conn->prepare("UPDATE google_users SET access_token = ?, expires_at = ? WHERE id = ?");
  $stmt->bind_param('sii', $tokenSet['access_token'], $expires_at, $userId);
  $stmt->execute();
  $stmt->close();
}

/* ---------------------------------------------------------------
   4. Save session and respond
   ------------------------------------------------------------- */
$userId = upsertUser([
  'google_sub' => $userGoogleId,
  'email' => $email,
  'name' => $name,
  'avatar' => $avatar,
  'refresh_token' => $refresh_token,
  'access_token' => $access_token,
  'expires_at' => $expires_at
]);

$_SESSION['user'] = ['id' => $userId, 'email' => $email, 'name' => $name];
echo json_encode(['success' => true]);

/* ---------------------------------------------------------------
   5. Helper: send POST to token endpoint
   ------------------------------------------------------------- */
function httpPost($url, $data)
{
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => http_build_query($data),
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
  ]);
  $resp = curl_exec($ch);
  if ($resp === false)
    return ['error' => curl_error($ch)];
  curl_close($ch);
  return json_decode($resp, true);
}
