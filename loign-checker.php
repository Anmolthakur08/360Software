 <?php
 session_start();
   if(!isset($_SESSION['user'])){
    print_r($_SESSION['user']);
 // header("Location: ./index.php");
//   exit();
 }
 ?>