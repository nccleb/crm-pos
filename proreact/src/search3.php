

<!DOCTYPE html>
<html lang="en">

<span onclick=show()  hidden  id="thi"><?php echo  $GLOBALS['a']=$_COOKIE["us"]; ?></span>
<head>
  <?php include('head.php'); ?>
<script>
function quit(){
	window.close();
}

</script>	


<script>
function refresh(){
	location.reload();
}

</script>	

<script>
function show(){
	document.getElementById("forme").value=
 document.getElementById("thi").innerHTML ;
 
 
  
}

</script>		

<style>
.whatsappbutton { background-color: #7ACC72; /* Green */ border: none; color: white; text-align: center; text-decoration: none; font-size: 16px; border-radius: 20px; padding: 10px 20px; margin-top: 10px !important; width: fit-content; margin: auto; cursor: pointer }



</style>






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
        xmlhttp.open("GET", "fetch3.php?q=" + str,false);
        xmlhttp.send();
    }
}
</script>




</head>

<body onload="show()" >


 <div class="jumbotron"> 





<fieldset style="background:#f2f2f2;">
   
   <h3 id="form">Search  Contacts Details</h3> 
   
 
<h4 id="for" >Enter The Company:</h4>
 
<form method="post" action="<?php echo htmlspecialchars("phonesearch3.php");?>" id="searchform">
<input id="forme" type="text" name="name" >


<input class="whatsappbutton" id="form" type="submit" value="Search" name="submit">
<button  class="whatsappbutton"   type="button" id="form" onclick="refresh()">Refresh</button>
<button  class="whatsappbutton" type="button" id="form" onclick="quit()">Quit</button>
</form><br/><br/><br/><br/><br/><br/>
<p id="form"  ><b>Start typing a name in the input field below:</b></p>
<form> 
<p id="form" >First name:</p> <input type="text" onkeyup="showHint(this.value)">
</form>
<p id="form"  >Suggestions: <span id="txtHint"></span></p>






</fieldset>

<div>

</body>
</html>







 

   
        
   
       
   
   
   
   
   
    

 















   
    
    
    
    










