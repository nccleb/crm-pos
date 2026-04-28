<?php
session_start();
$s=$_SESSION["ses"];
$C=$_COOKIE["user"];
 $u=urldecode($_GET['page']);
  $_SESSION["u"]=$u;
 $p=urldecode($_GET['page1']);
  $_SESSION["p"]=$p;
$ui=urldecode($_GET['page2']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <?php include('head.php'); ?>
<script>
function quit(){
	window.close();
}

</script>		
<style>
* {
    box-sizing: border-box;
}
@media only screen and (max-width: 1400px) {
    body {
        background-color: lightblue;
		//font-size:3VW;
	}
	
	
}


#form{
	color:blue;
	//font-size:3VW;
}

#for{
	color:blue;
	//font-size:2VW;
}




</style>

<style>
.whatsappbutton { background-color: #7ACC72; /* Green */ border: none; color: white; text-align: center; text-decoration: none; font-size: 16px; border-radius: 20px; padding: 10px 20px; margin-top: 10px !important; width: fit-content; margin: auto; cursor: pointer }



</style>

</head>

<body>

<div class="container text-center"> 





<fieldset style="background:#f2f2f2;">
   
   <h3 id="form">Search  Contacts Details</h3> 
   
 

 
<form method="post" action="<?php echo htmlspecialchars("test200.php");?>" id="searchform">
<h4 id="for" >Enter The Number:</h4>
<p style="color:blue"><input style="color:blue" type="text" name="name" ><p>


<input class="whatsappbutton" id="form" type="submit" value="Search" name="submit">

<button   class="whatsappbutton" type="button" id="form" onclick="quit()">Quit</button>
</form><br/><br/>






</fieldset>

<div>

</body>
</html>







 

   
        
   
       
   
   
   
   
   
    

 















   
    
    
    
    










