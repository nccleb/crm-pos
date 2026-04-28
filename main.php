<?php
session_start();
?>
<?php
 $_SESSION["ses"]=$_POST['bp'];
 
$cookie_name = "user";
$cookie_value = $_POST['bp'];
setcookie($cookie_name, $cookie_value, time() + (86400 * 360), "/"); 

?>

<!DOCTYPE html>
<html>
    <head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

	<title>
	
	</title>
<script>
function quit(){
	window.close();
}

</script>		
<script>
function refresh(){
	window.location.reload();
}

</script>		
	
	
	
<style>
#id{
background:lightgrey;
color:#303030;
border-color:lightblue;
}
#form{
	color:#303030;
	font-size:20px;
}
#fo{
	color:#303030;
	font-size:20px;
}

#uv {
// width:30px; 
 //height:30px   
font-family: Verdana, Geneva, sans-serif;
color: white;
border-style: none;
vertical-align: center;
text-align: center;

}
#av{
width:190px; 
  color:white;
  font-size:40px;
  //background:#0000FF	
	background:#A9A9A9;
	
	
}





</style>	
        <title>main</title>
      <script>
function myFunction() {
    window.print();
}
</script>  
 <script>
function prev(){
	
	loadDoc()+address()+show()+win()+email()+cust()+bus()+url();
}
function on(){
	y=setInterval("prev()",1000);
}


</script>       
 <script>
function Clear(){
	
	
   document.getElementById('yourid').value = "";
	
}
</script>


<script>
function openWin() {
   
 
 var popup = window.open ("http://192.168.40.17//test19.php");
 
    $(popup.document).ready(function() {
       
		popup.window.top.location("http://192.168.0.106/test19.php");
	});     
}		
    

</script>


















<script>
function Import(){
	
 window.open ("http://192.168.0.106/test31.php","","menubar=0,resizable=1,width=480,height=300");
	
}
</script> 

</script> 
<script>
function Export(){
	
 window.open ("http://192.168.0.106/test43.php","","menubar=0,resizable=1,width=480,height=300");
	
}
</script> 


<script>

	
 


    function uro() {
	//window.moveTo(700,350);	
    window.open ("http://192.168.0.106/test51.php","","menubar=0,resizable=1,width=1224,height=725");
	
	
	
    }


	

</script> 




<script>
function search(){
	
 window.open ("http://192.168.0.106/search.php","","menubar=0,resizable=1,width=480,height=620");
	
}
</script> 
<script>
function number(){
	
 window.open ("http://192.168.0.106/numbersearch.php","","menubar=0,resizable=1,width=480,height=435");
	
}
</script> 

<script>
function ImportSql(){
	
 window.open ("http://192.168.0.106/test38.php","","menubar=0,resizable=1,width=480,height=300");
	
}
</script> 

<script>
function bb(){
	
 window.open ("http://192.168.0.106/test42.php","","menubar=0,resizable=1,width=480,height=300");
	
}
</script> 


<script>
function add(){
	var myw;
	
   myw=window.open ("http://192.168.0.106/test19.php","","menubar=0,resizable=1,width=480,height=750");
	
}
</script> 
<script>
function backup(){
	var myw;
	
   myw=location.replace('test36.php');
	
}
</script> 



<script>
function del(){
	
 window.open ("http://192.168.0.106/test29.php","","menubar=0,resizable=1,width=480,height=350");
	
}
</script> 







<script>
function update(){
	var myw;
	
   myw=window.open ("http://192.168.0.106/test27.php","","menubar=0,resizable=1,width=480,height=750");
	
}
</script> 

        
        
    </head>

<body onload="loadDoc()+address()+show()+win()+email()+on()+bus()+url()">
<?php
$fr=fopen("C:\Program Files (x86)\WinTariff32\Calls\Calls.dbf","r");
while($ch=fgets($fr)){
	//echo $ch;
    $tab=explode("RC",$ch);
  // print_r($tab);
}

	 $co=count($tab);
	
	  $co-=2;
	 $ca=$co-1;
	 $cc=$co-2;
	 $cd=$co-3;
	 

?>
<span id="ep"></span>

<span style="color:#303030">Customers</span><button type="button" id="cu"></button>
 
      
   
   
   
   
   
    
<h1><p style="color:red"></p></h1>

<fieldset style="background:green">
   <!legend style="font-size:30px;color:red;background:lightblue"></legend>
 <table style="width:100%   ";  >

<tr><td  valign="top"   style="align:"left"  >  
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<p style="color:white;font-size:24px">Phone  &nbsp  &nbsp &nbsp <input style="color:#0000FF	;font-size:24px" type="text"  name="bp" id="bp" size="32" onload="closeWin()" onclick="submit()+openWin()"><p><br/>   
<p style="color:white;font-size:24px">Name&nbsp &nbsp &nbsp  &nbsp <input style="color:#0000FF;font-size:24px" type="text" name="ap" id="ap" size="32"><p><br/>
<p style="color:white;font-size:24px">E-mail &nbsp &nbsp &nbsp  <input style="color:#0000FF;font-size:24px" type="text" name="em" id="em" size="32" ><p><br/>
<p style="color:white;font-size:24px">Url &nbsp &nbsp  &nbsp   &nbsp   &nbsp   &nbsp  <input style="color:#0000FF;font-size:24px" type="text" name="ur" id="ur" size="32" ><p><br/>
<p style="color:white;font-size:24px">Business &nbsp    <input style="color:#0000FF;font-size:24px" type="text" name="bu" id="bu" size="32" ><p><br/>
<p  style="color:white;font-size:24px">Address &nbsp &nbsp <textarea  style="color:#0000FF;font-size:24px" name="cp" id="cp"  rows="5" cols="32" ></textarea>

<input type="submit" style="display:none" id="form1_submit_hidden">
</form><br/><br/>


</td>
   <td valign="top"  style="text-align:right"><p style="color:white;font-size:20px">Notes<textarea style="color:blue;font-size:18px" name="cp" id="yourid" rows="10" cols="55" ></textarea>
   <p ><select>
   <option>ID</option>
   <option> <?php echo $co?></option>
   <option><?php echo $ca?> </option>
   <option><?php echo $cc?> </option>
   <option><?php echo $cd?> </option>
   </select></p></td>
   



</tr>

<tr><td></td><td valign="top" style="text-align:right"><div style=" position: relative; top: -327px;height: 100px;">

<button  type="button" id="av" onclick="add()"><p id="uv">Add</p></button>
<button  type="button" id="av" onclick="del()"><p id="uv">Delete</p></button>
<button  type="button" id="av" onclick="number()"><p id="uv">SNumber</p></button><br/>
<button type="button" id="av" onclick="search()"><p id="uv">SName</p></button>
<button type="button" id="av" onclick="myFunction()"><p id="uv">Print</p></button>
<button  type="button" id="av" onclick="Clear()"><p id="uv">Clear</p></button><br/>
<button type="button" id="av" onclick=""> <p  "id="uv" >SNumber</p></button>
<button type="button" id="av" onclick="uro()"><p id="uv" >Check</p></button>
<button  type="button" id="av" onclick=""><p  id="uv">Pay</p></button>
 




</div></td></tr>


</table>
<button  type="button" style="color:#303030;font-size:20px" onclick="Import()">Import </button>
<button  type="button" style="color:#303030;font-size:20px" onclick="Export()">Export </button>
<button  type="button" style="color:#303030;font-size:20px" onclick="ImportSql()">Recovery </button>
<button  type="button" style="color:#303030;font-size:20px" onclick="bb()">BackUp </button>
<!button  type="button" style="color:#303030;font-size:20px" onclick="add()"> </button>
<!button  type="button" style="color:#303030;font-size:20px" onclick="del()"></button>
<!button  type="button" style="color:#303030;font-size:20px" onclick="update()"></button>
<!button style="color:white;font-size:20px;background:#0000FF" onclick="search()"></button>
<button  type="button" style="color:#303030; font-size:20px" onclick="refresh()">Reload</button>
<!button type="button" style="color:#303030;font-size:20px" onclick="myFunction()"></button>
<!button  type="button" style="color:#303030;font-size:20px" onclick="Clear()"></button>
<button type="button" id="form" onclick="quit()">Quit</button>
<p style="font-size:14px;color:white">&copy 2018 GEORGE NAHED.All rights reserved.</p>

</fieldset>
  


<script>
function submit() {
    var form1 = document.forms['form1']
    
    
         form1.submit()
    
}
</script> 
<script>
function win() {
  var xhttp;
  if (window.XMLHttpRequest) {
    // code for modern browsers
    xhttp = new XMLHttpRequest();
    } else {
    // code for IE6, IE5
    xhttp = new ActiveXObject("Microsoft.XMLHTTP");
  }
  xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      document.getElementById("ep").innerHTML = this.responseText;
    }
  };
  xhttp.open("GET","test25.php", true);
  xhttp.send();
}
</script>

 <script>
function cust() {
  var xhttp;
  if (window.XMLHttpRequest) {
    // code for modern browsers
    xhttp = new XMLHttpRequest();
    } else {
    // code for IE6, IE5
    xhttp = new ActiveXObject("Microsoft.XMLHTTP");
  }
  xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      document.getElementById("cu").innerHTML = this.responseText;
    }
  };
  xhttp.open("GET","test37.php", true);
  xhttp.send();
}
</script>
<script>
function bus() {
  var xhttp;
  if (window.XMLHttpRequest) {
    // code for modern browsers
    xhttp = new XMLHttpRequest();
    } else {
    // code for IE6, IE5
    xhttp = new ActiveXObject("Microsoft.XMLHTTP");
  }
  xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      document.getElementById("bu").value = this.responseText;
    }
  };
  xhttp.open("GET","test40.php", true);
  xhttp.send();
}
</script>





<script>
function show() {
  var xhttp;
  if (window.XMLHttpRequest) {
    // code for modern browsers
    xhttp = new XMLHttpRequest();
    } else {
    // code for IE6, IE5
    xhttp = new ActiveXObject("Microsoft.XMLHTTP");
  }
  xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      document.getElementById("ap").value = this.responseText;
    }
  };
  xhttp.open("GET","test18.php", true);
  xhttp.send();
}
</script>

 
 
 <script>
function loadDoc() {
  var xhttp;
  if (window.XMLHttpRequest) {
    // code for modern browsers
    xhttp = new XMLHttpRequest();
    } else {
    // code for IE6, IE5
    xhttp = new ActiveXObject("Microsoft.XMLHTTP");
  }
  xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      document.getElementById("bp").value = this.responseText;
 
    }
  };
  xhttp.open("GET","test23.php", true);
  xhttp.send();
}
</script>

<script>
function address() {
  var xhttp;
  if (window.XMLHttpRequest) {
    // code for modern browsers
    xhttp = new XMLHttpRequest();
    } else {
    // code for IE6, IE5
    xhttp = new ActiveXObject("Microsoft.XMLHTTP");
  }
  xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      document.getElementById("cp").value = this.responseText;
      
    }
  };
  xhttp.open("GET","test17.php", true);
  xhttp.send();
}
</script>

<script>
function email() {
  var xhttp;
  if (window.XMLHttpRequest) {
    // code for modern browsers
    xhttp = new XMLHttpRequest();
    } else {
    // code for IE6, IE5
    xhttp = new ActiveXObject("Microsoft.XMLHTTP");
  }
  xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      document.getElementById("em").value = this.responseText;
      
    }
  };
  xhttp.open("GET","test34.php", true);
  xhttp.send();
}
</script>

<script>
function url() {
  var xhttp;
  if (window.XMLHttpRequest) {
    // code for modern browsers
    xhttp = new XMLHttpRequest();
    } else {
    // code for IE6, IE5
    xhttp = new ActiveXObject("Microsoft.XMLHTTP");
  }
  xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      document.getElementById("ur").value = this.responseText;
      
    }
  };
  xhttp.open("GET","test49.php", true);
  xhttp.send();
}
</script>
 
 
 


</div>
</body>
</html>











   
    
    
    
    






