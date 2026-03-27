<style>
  .navbar {
    background-color: #3368b5;
    overflow: hidden;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .navbar a,
  .navbar button {
    color: white;
    text-decoration: none;
    margin: 0 10px;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 16px;
  }
</style>

<div class="navbar">
  <div class="nav-left">
    <a href="/360software/store.php">Home</a>
  </div>
  <div class="nav-right" style="display: flex; align-items: center; gap: 10px;">
    <?php
    include_once './config.php';
    if (isset($_SESSION['user'])) {
      $user = $_SESSION['user'];
      $userName = $user['name'];
      $userEmail = $user['email'];
      echo "<h6 style='color: white; margin: 0;'>Welcome ($userName)<p>Login as $userEmail</p></h6>";
      echo '<a href="./logout.php" style="margin-left: 10px;">Logout</a>';
    } else {
      echo '<a href="./index.php">Login</a>';
    }
    ?>
  </div>
</div>
<div id="pageLoader" class="loader-wrapper">
  <div class="spinner-border text-primary" role="status">
    <span class="visually-hidden"></span>
  </div>
</div>