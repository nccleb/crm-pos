<?php
session_start();
?>





<?php

	  $idr = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  exit();
}
	    $result=mysqli_query($idr,"SELECT DISTINCT nom FROM client "); 
	  

	  while($row=mysqli_fetch_assoc($result)){ 
	  
	 
	          $a[] =$row['nom']; 
			 
	  }  

if (preg_match("/^[a-zA-Z ]*$/",$_GET['q'])){	
$q = $_GET["q"];

$hint = "";

// lookup all hints from array if $q is different from "" 
if ($q !== "") {
    $q = strtolower($q);
    $len=strlen($q);
    foreach($a as $lname) {
        if (stristr($q, substr($lname, 0, $len))) {
            if ($hint === "") {
                $hint = $lname;
            } else {
                $hint .= ", $lname";
            }
        }
    }
}}

// Output "no suggestion" if no hint was found or output correct values 
echo $hint === "" ? "no suggestion" : $hint;
?>



	<?php
	$cookie_name = "uq";
   $cookie_value = $q;
   setcookie($cookie_name, $cookie_value,  0, "/");

?>




