<?php
session_start();
?>
<?php

 $nam=urldecode($_GET['page']);
 $idf=urldecode($_GET['page1']);
 $num9=urldecode($_GET['page2']);
   $_SESSION["oop"]=$nam;
   $_SESSION["ooq"]=$idf;
    $_SESSION["num9"]=$num9;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  
   <?php include('head.php'); ?>

</head>	
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
	//font-size:2vw;
}
#fo{
	color:#303030;
	//font-size:2vw;
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
//width:190px; 
  color:white;
  //font-size:2vw;
 //background:green;
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
	
	loadDoc()+address()+show()+win()+email()+cust()+bus()+url()+tu();
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
function printContents(id)
{
    var contents = $("#"+id).html();

    if ($("#printDiv").length == 0)
    {
    var printDiv = null;
    printDiv = document.createElement('div');
    printDiv.setAttribute('id','printDiv');
    printDiv.setAttribute('class','printable');
    $(printDiv).appendTo('body');
    }

    $("#printDiv").html(contents);

    window.print();

    $("#printDiv").remove();


}	
</script>
	
<script>
function openWin() {
   
 
 var popup = window.open ("http://192.168.1.101//test19.php");
 
    $(popup.document).ready(function() {
       
		popup.window.top.location("http://192.168.1.101//test19.php");
	});     
}		
    

</script>

<script>
function leads(){
	
 window.open ("http://192.168.1.101//test82.php","","menubar=0,resizable=1,width=950,height=750");
	
}
</script> 

















<script>
function Import(){
	
 window.open ("http://192.168.1.101//test73.php","","menubar=0,resizable=1,width=480,height=300");
	
}
</script> 

</script> 
<script>
function Export(){
	
 window.open ("http://192.168.1.101//test72.php","","menubar=0,resizable=1,width=480,height=300");
	
}
</script> 


<script>

	
 


    function uro() {
   window.moveTo(0,250);	
    }


	

</script> 




<script>
function search(){
	
 window.open ("http://192.168.1.101//search.php","","menubar=0,resizable=1,width=480,height=620");
	
}
</script> 
<script>
function number(){
	
 window.open ("http://192.168.1.101//numbersearch.php","","menubar=0,resizable=1,width=480,height=435");
	
}
</script> 

<script>
function ImportSql(){
	
 window.open ("http://192.168.1.101//test38.php","","menubar=0,resizable=1,width=480,height=300");
	
}
</script> 

<script>
function bb(){
	
 window.open ("http://192.168.1.101//test42.php","","menubar=0,resizable=1,width=480,height=300");
	
}
</script> 


<script>
function add(){
	var myw;
	
   
	myw=window.open ("http://192.168.1.101//test56.php?page=<?php echo $nam ?>&page1=<?php echo $idf ?>&page2=<?php echo $num9 ?>","","menubar=0,resizable=1,width=450,height=800");
}
</script> 

<script>
function add2(){
	var myw;
	
  
	myw=window.open ("http://192.168.1.101//test276.php?page=<?php echo $nam ?>&page1=<?php echo $idf ?>&page2=<?php echo $num9 ?>","","menubar=0,resizable=1,width=450,height=800");
}
</script> 


<script>
function backup(){
	var myw;
	
   myw=location.replace('test36.php');
	
}
</script> 
<script>
function quit(){
	window.close();
}

</script>


<script>
function del(){
	
 window.open ("http://192.168.1.101//test68.php","","menubar=0,resizable=1,width=480,height=350");
	
}
</script> 
<script>
function delAll(){
	
 window.open ("http://192.168.1.101//test77.php","","menubar=0,resizable=1,width=480,height=350");
	
}
</script> 







<script>
function myFunction() {
    window.print();
}
</script>  






<script>
function update(){
	var myw;
	
   
	myw=window.open ("http://192.168.1.101//before.php?pag=<?php echo $nam ?>&pag1=<?php echo $idf ?>&pag2=<?php echo $num9 ?>","","menubar=0,resizable=1,width=500,height=800");
}
</script> 

        
        
    </head>

<body onload="loadDoc()+address()+show()+win()+email()+on()+bus()+url()+uro()+tu()+id()">

<div class="jumbotron">
<?php
-$fr=fopen("C:\wamp64)\www\CaCallStatus.dat","r");
while($ch=fgets($fr)){
	
    $tab=explode("RC",$ch);
  
}

	 $co=count($tab);
	
	  $co-=2;
	 $ca=$co-1;
	 $cc=$co-2;
	 $cd=$co-3;
	 

?>
<span id=""></span>


      
   
   
   
   
   
    
<h1><p style="color:red"></p></h1>


   
 


<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">

<div class="mb-3">
  <label for="exampleFormControlInput1" class="form-label">Incoming Call</label>
  <input type="text" class="form-control" id="bp" placeholder="" name="bp" id="bp"  value="<?php echo $num9 ?>"  >
</div><br>



<div class="mb-3">
  <label for="exampleFormControlInput1" class="form-label">Last Contacted Date</label>
  <input type="text" class="form-control" id="bp" placeholder="" name="lc" id="lc"    >
</div><br>
 
 
<div class="mb-3">
  <label for="exampleFormControlTextarea1" class="form-label">Last Activity </label>
  <textarea class="form-control" id="co" rows="3"  name="co"       ></textarea>
</div>

<div class="mb-3">
  <label for="exampleFormControlTextarea1" class="form-label">Comment </label>
  <textarea class="form-control" id="in" rows="3"  name="in"       ></textarea>
</div>


<div class="mb-3">
  <label for="exampleFormControlInput1" class="form-label">Status</label>
  <input type="text" class="form-control" id="bp" placeholder="" name="in" id="in"    >
</div><br>




<input type="submit" style="display:none" id="form1_submit_hidden">
</form><br/><br/>


<div id="printDiv">

<input   type="hidden" id="av" onclick="add()">
<button  class="btn btn-success" type="button"  onclick="add()">Add</button>

<button class="btn btn-success" type="button"  onclick="update()">Update</button>
<!--button class="btn btn-success" type="button"  onclick="del()">Delete</button-->
<!--button  class="btn btn-success"      type="button"  onclick="printContents(id)">Print</button-->
<button  class="btn btn-success"     type="button"  onclick="quit()">Quit</button></br>










<button  class="btn btn-success type="button"  onclick="Import()">Import </button>
<button class="btn btn-success type="button"  onclick="Export()">Export </button>
<!--button class="btn btn-success type="button"  onclick="ImportSql()">Recovery </button>
<button class="btn btn-success type="button"  onclick="bb()">BackUp </button-->
<!button  type="button" style="color:#303030;font-size:20px" onclick="add()"> </button>
<button class="btn btn-success type="button"  onclick="delAll()">Delete All</button>
<!button  type="button" style="color:#303030;font-size:20px" onclick="update()"></button>
<!button style="color:white;font-size:20px;background:#0000FF" onclick="search()"></button>
<!--button  class="btn btn-success type="button"  onclick="refresh()">Reload</button-->
<!button type="button" style="color:#303030;font-size:20px" onclick="myFunction()"></button>
<!button  type="button" style="color:#303030;font-size:20px" onclick="Clear()"></button>








<!--p style="color:white">&copy 2018 GEORGE NAHED  <i class="fa fa-mobile"> </i> +00 961 3 205818.All rights reserved.</p-->


</div>
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
      document.getElementById("ca").innerHTML = this.responseText;
    }
  };
  xhttp.open("GET","test64.php", true);
  xhttp.send();
}
</script>

<script>
function tu() {
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
      document.getElementById("etu").innerHTML = this.responseText;
    }
  };
  xhttp.open("GET","test81.php", true);
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
      document.getElementById("cx").value = this.responseText;
    }
  };
  xhttp.open("GET","test61.php", true);
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
      document.getElementById("ba").value = this.responseText;
    }
  };
  xhttp.open("GET","test62.php", true);
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
      document.getElementById("lc").value = this.responseText;
 
    }
  };
  xhttp.open("GET","test63.php", true);
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
      document.getElementById("co").value = this.responseText;
      
    }
  };
  xhttp.open("GET","test65.php", true);
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
      document.getElementById("in").value = this.responseText;
      
    }
  };
  xhttp.open("GET","test66.php", true);
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
      document.getElementById("st").value = this.responseText;
      
    }
  };
  xhttp.open("GET","test67.php", true);
  xhttp.send();
}
</script>
 
 <script>
function id() {
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
      document.getElementById("ci").value = this.responseText;
      
    }
  };
  xhttp.open("GET","test246.php", true);
  xhttp.send();
}
</script>
 


</div>


</div>
</body>
</html>











   
    
    
    
    






