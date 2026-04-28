<?php
session_start();
 
  $nam=$_GET['pag'];
 $idf=$_GET['page1'];
 
$_SESSION["oop"]=$nam;
$_SESSION["ooq"]=$idf;

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <?php include('head.php'); ?>
   <link rel="stylesheet" href="css/whatsappButton.css" />
   <script src="js/test371.js"></script>




			






<script>
function showHint(str) {
    if (str.length == 0) { 
        document.getElementById("txtHint").innerHTML = "";
        return;
    } else {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById("txtHint").innerHTML = this.responseText;
            }
        };
        xmlhttp.open("GET", "fetch2.php?q=" + str,false);
        xmlhttp.send();
    }
}
</script>




</head>

<body >


<div <div class="jumbotron">  





<fieldset style="background:#f2f2f2;">
   
   <h3 id="form">Search  Contacts Details</h3> 
   
 
<h4 id="for" >Enter The Business:</h4>
 
<form method="post" action="<?php echo htmlspecialchars("businessSearch.php");?>" id="searchform">
<input id="forme" type="text" name="name" >


<input class="whatsappbutton" id="form" type="submit" value="Search" name="submit">

<button  class="whatsappbutton"   type="button" id="form" onclick="quit()">Quit</button>
</form><br/><br/><br/><br/><br/><br/>
<p id="form"  ><b>Start typing a name in the input field below:</b></p>
<form> 
<p id="form" >First name:</p> <input type="text"    onkeyup="showHint(this.value)">
</form>
<p id="form"  >Suggestions: <span  id="txtHint"></span></p>






</fieldset>

<div>

</body>
</html>










 

   
        
   
       
   
   
   
   
   
    

 















   
    
    
    
    










