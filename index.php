<?php
include_once './config.php';
if (isset($_SESSION['user'])) {
    header('Location: store.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>360 Software</title>

    <!---- basic styling only ---->
    <style>
        body  { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .form-container {
            max-width: 400px; margin: 40px auto; padding: 20px;
            border: 1px solid #ccc;
        }
        .form-container input[type="text"],
        .form-container input[type="password"] {
            width: 100%; padding: 12px; margin: 8px 0; box-sizing: border-box;
        }
        .form-container input[type="submit"] {
            background: #0d6efd; color: #fff; padding: 12px;
            border: none; width: 100%; cursor: pointer;
        }
    </style>

    <!-- libs -->
    <link rel="stylesheet" href="./360Software/360Software.css">    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <link  href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-center",
            "timeOut": "3000"
        };
    </script>

</head>

<?php include_once './navbar.php'; ?>

<body>
<?php
if (isset($_SESSION['loginmessage'])) {
    $msg = $_SESSION['loginmessage'];
   
    echo "<script>
            document.addEventListener('DOMContentLoaded', () => {
                toastr.error(" . json_encode($msg) . ");
            });
          </script>";
    unset($_SESSION['loginmessage']);
}


if (isset($_SESSION['logoutsuccess'])) {
    $msg = $_SESSION['logoutsuccess'];
   
    echo "<script>
            document.addEventListener('DOMContentLoaded', () => {
                toastr.success(" . json_encode($msg) . ");
            });
          </script>";
    unset($_SESSION['logoutsuccess']);
}
?>




<div class="form-container">
    <!-- traditional form -->
    <form action="login.php" method="POST">
        <label for="email">User Name (Email):</label>
        <input type="text" id="email" name="email" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <input type="submit" value="Login">
    </form>

    <hr><h2 class="h2head">OR</h2>

    <!-- Google sign-in button will render here -->
    <button onclick="googleLogin()" class="gbutton">Sign in with Google</button>
</div>

<script src="https://accounts.google.com/gsi/client" async defer></script>

   <script>
        // document.addEventListener('DOMContentLoaded',()=>{
        const loader = document.getElementById('pageLoader');
        // Show loader on page load
        window.addEventListener('load', () => {
            loader.classList.add('hidden');
        });

        // Show loader before unload (navigation)
        window.addEventListener('beforeunload', () => {
            loader.classList.remove('hidden');
        });
        // })

    </script>
<script>
/* util ----------------------------------------------------------*/
function b64url(bytes){
  return btoa(String.fromCharCode(...bytes))
         .replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
}
async function makePKCE() {
  const rnd   = crypto.getRandomValues(new Uint8Array(64));
  const ver   = b64url(rnd);
  const hash  = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(ver));
  return { verifier: ver, challenge: b64url(new Uint8Array(hash)) };
}

/* login flow ----------------------------------------------------*/
async function googleLogin() {
  const {verifier, challenge} = await makePKCE();
  sessionStorage.setItem('pkce_verifier', verifier);     // temp-store

  const codeClient = google.accounts.oauth2.initCodeClient({
    client_id: '310344686192-560imlrkhos1eu4e5c11ttj6aaoo6rb8.apps.googleusercontent.com',
    // scope: 'openid email profile https://www.googleapis.com/auth/drive.readonly',
    scope: 'openid email profile https://www.googleapis.com/auth/streetviewpublish',
    access_type: 'offline',   // => refresh_token
    prompt: 'consent',
    include_granted_scopes: true,
    ux_mode: 'popup',
    code_challenge: challenge,
    code_challenge_method: 'S256',
    callback: ({code}) => {
       fetch('oauth2callback.php', {
         method:'POST',
         headers:{'Content-Type':'application/json'},
         body:JSON.stringify({code, verifier})
       })
       .then(r=>r.json()).then(res=>{
          if(res.success) location.href='https://testumgebung.dunnet.de/360software/store.php';
          else toastr.error(res.error);
       });
    }
  });
  codeClient.requestCode();                        
}
</script>

</body>
</html>
