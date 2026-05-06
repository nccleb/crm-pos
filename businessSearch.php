<!DOCTYPE html>
<html lang="en">
<head>
<?php include('head.php'); ?>
   <link rel="stylesheet" href="css/whatsappButton.css" />
   <script src="js/test371.js"></script>











<script>
function size(){
	window.resizeTo(1000,950);
}

</script>



</script>
</head>
<body onload="size()" >

<div class="jumbotron">

<?php 
	  if(isset($_POST['name'])&&!empty($_POST['name'])){ 
	 
	  if (preg_match("/^[a-zA-Z ]*$/",$_POST['name'])) {
	  $name=$_POST['name']; 
	  //connect  to the database 
	  $idr = mysqli_connect("172.18.208.1", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  exit();
}
	  
	  
	   $result=mysqli_query($idr,"SELECT  nom,prenom,number FROM client WHERE  business LIKE '{$name}'"); 
	  
	  $t=0;
	  while($row=mysqli_fetch_assoc($result)){ 
	        $Number  =$row['number']; 
			  $name  =$row['nom'];  
			  $lname  =$row['prenom'];
	          $ID=$row['id']; 
	  
		 
         echo  "<ul>";
		 echo  "<li><a  href=\"test201.php?number=$Number\">".$name."&nbsp".$lname."&nbsp"."&nbsp".$Number . " </a></li>\n"; 
         echo   "</ul>";

	 
	  $t=1;
	  
	  
	  
	  }
	  if($t==0){
		echo"<p id=\"for\"> Name not found!</p>";  
		  
	  }
	  echo "<a href=\"search.php\"><button   class=\"whatsappbutton\"        id=\"form\">Try again</button></a>";
  	  echo "<button id=\"form\" type=\"button\"  class=\"whatsappbutton\"           onclick=\"quit()\">Quit</button>";
	  }else{ 
	 
       echo"<p id=\"for\"> Please enter only letters!"."<br/><br/>"."<br/>";
		 echo "<a href=\"search.php\"><button  class=\"whatsappbutton\"         id=\"form\">Try again</button></a>";
	     echo "<button id=\"form\" type=\"button\"  class=\"whatsappbutton\"           onclick=\"quit()\">Quit</button>";

		 
	  }}
	  else{ 
	 
       echo"<p id=\"for\"> Please enter a search query!"."<br/><br/>"."<br/>";
		 echo "<a href=\"search.php\"><button  class=\"whatsappbutton\"         id=\"form\">Try again</button></a>";
	     echo "<button id=\"form\" type=\"button\"  class=\"whatsappbutton\"         onclick=\"quit()\">Quit</button>";

		 


	  
	  }
	  
	 
	  
	?> 
	
	</body>
	
	</div>
	
	</html>