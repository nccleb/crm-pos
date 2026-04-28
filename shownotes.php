<?php
// shownotes.php
session_start();


// Initialize variables for the form
$inc = ""; // Initialize caller ID variable

$fichier = "CaCallStatus.dat";
if (file_exists($fichier)) {
    $xml = simplexml_load_file($fichier);
    if ($xml) {
        foreach ($xml as $CallRecord) {
            if (isset($CallRecord->ext)) {
                $ext = $CallRecord->ext;
            }
            if (isset($CallRecord->CallerID)) {
                $inc = (string)$CallRecord->CallerID;
                
            }
        }
    }
}

// If no caller ID from XML, try to get from session
if (empty($inc) && isset($_SESSION["userinc"])) {
    $inc = $_SESSION["userinc"];
}


 $idr = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  exit();
}
	  $result=@mysqli_query($idr,"SELECT  * FROM client 
      
	  ") ;
	  
	
	  
	  while($lig=@mysqli_fetch_assoc($result)){
      
      
      if( $inc!="" AND $inc==$lig['number'] ){
        echo $lig['remark'];
        
		exit();
    }
	else if( $inc!="" AND  $inc==$lig['inumber']) {
       echo $lig['remark'];
		exit();
    }
	else if( $inc!="" AND  $inc==$lig['telother']) {
        echo $lig['remark'];
		exit();
    }
	
	else if( $inc!="" AND  $inc==$lig['telmobile']) {
        echo $lig['remark'];
		exit();
    }
	
}
 


?>