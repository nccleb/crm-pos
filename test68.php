

<!DOCTYPE html>
<html>
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
		font-size:1vw;
	}
	
	
}



#form{
	color:blue;
	font-size:28px;
}
</style>





</head>

<body>





<h1><p style="color:red"></p></h1>

<fieldset style="background:#f2f2f2;">
   <!legend style="font-size:30px;color:red;background:lightblue"></legend>
 <table style="width:100%   ";  >

<tr><td  valign="top"   style="align:"left"  >  
<form method="post" action="<?php echo htmlspecialchars("test69.php");?>" id="form1">
<p style="color:blue;font-size:20px">CUID<input style="color:blue;font-size:16px" type="number" name="id" id="bp" size="11"><p><br/>   


<input type="submit" value="Delete" id="form">&nbsp

<button type="button" id="form" onclick="quit()">Quit</button>
</form><br/><br/>
</td>
   <td>
</td></tr>

</table>
</fieldset>
</body>
</html>







 

   
        
   
       
   
   
   
   
   
    

 















   
    
    
    
    











       
        
        
    