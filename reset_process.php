<!DOCTYPE html>
<html lang="en">
<head>

<?php include('head.php'); ?>
  <link rel="stylesheet" href="css/stylei.css">
  <link rel="stylesheet" href="css/stylei2.css">
   
  <link rel="stylesheet" href="css/whatsappButton.css" />
  <script src="js/test371.js"></script>






</head>

<body>

<div class="jumbotron"> 


<?php
if(isset($_POST['submit_password']) && $_POST['password'])
{
 echo "<p id=\"form\">"."The New Password For"."\n". $email=$_POST['email']."\n"."is:"."\n"."</p>";
  echo "<p id=\"form\">".$pass=$_POST['password']."</p>";

  $idr = mysqli_connect("172.18.208.1", "root", "1Sys9Admeen72", "nccleb_test");
  if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
  }
        
  $email=$_POST['email'];   
  $pass=$_POST['password'];
  $select=mysqli_query($idr,"update form_element set password='$pass' where eemail='$email'");
  echo "<button id=\"form\" class=\" whatsappbutton\"  onclick=\"subm2()\" type=\"button\">Login</button>";
}
else{

  echo "<p id=\"form\">MISSING ENTRY ! "."</br>";

  echo "<button id=\"form\" class=\" whatsappbutton\"  onclick=\"subm2()\" type=\"button\"  >Login</button>";



}

?>






</div>
</body>
</html>