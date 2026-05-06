<?php
session_start();
//$str=$_COOKIE['url'];
 //$str2 = substr($str, 7);
?>

<?php

$fichier="CaCallStatus.dat";
$xml=simplexml_load_file($fichier);
foreach($xml as $CallRecord){
    $ext=$show->ext;
    $inc=$CallRecord->CallerID;;
    
}  

 
	$idr = mysqli_connect("172.18.208.1", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  exit();
}
	
	          
		$stmt = $idr->prepare("SELECT  * FROM client c,address a
      where c.id=a.id 
       	and (number=? or inumber=? or telmobile=? or telother=? )   ");
	  
 
$stmt->bind_param("iiii",$inc,$inc,$inc,$inc );

$stmt->execute();

$result = $stmt ->get_result();

$stmt->close();






while($row=$result->fetch_assoc()){

	  
	          
			  
	         
              $num=$row['number'];
			  
              if(strlen($num)==7){
              $num="0".$num;
			  }			  
			  
			  
			  $inum=$row['inumber'];
			  
              if(strlen($inum)==7){
              $inum="0".$inum;
			  }			  
             $name=$row['nom']; 
			  $id=$row['id'];
		      $_SESSION["id"]=$id;
              $company=$row['company'];
			  $email=$row['email'];
			  $business=$row['business'];
			  $address=$row['address'];
			  $url=$row['url'];
			  $url = substr($url, 7);
			  $idf=$row['idf'];
			  $city=$row['city'];
			  $street=$row['street'];
			  $floor=$row['floor'];
			  $building=$row['building'];
			  $zone=$row['zone'];
			  $near=$row['near'];
			  $remark=$row['remark'];
			  $idf=$row['idf'];
			  $telmobile=$row['telmobile'];
			  if(strlen($telmobile)==7){
              $telmobile="0".$telmobile;
			  }			  
			  
			  $telother=$row['telother'];
			  if(strlen($telother)==7){
              $telother="0".$telother;
			  }			  
			  
			 $apartment=$row['apartment'];
			  
			  
		}
	 
	  
	  
	  
	 
	
	  
	 
	?> 	  
 

<?php
 $_SESSION["ses"]=$_POST['bp'];
  $s=$_SESSION["ses"];
$cookie_name = "user";
$cookie_value = $_POST['bp'];
setcookie($cookie_name, $cookie_value, time() + (86400 * 360), "/"); 
   $nam=$_GET['pag'];
  $idf=$_GET['pag1'];

$_SESSION["oop"]=$nam;
$_SESSION["ooq"]=$idf;
 
 
?>



<!DOCTYPE html>
<html lang="en">
<head>
  
  <?php include('head.php'); ?>
 
  
		
   
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
   <script type="text/javascript">
        function PrintDiv(id) {
            var data=document.getElementById(id).innerHTML;
            var myWindow = window.open('', 'my div', 'height=400,width=600');
            myWindow.document.write('<html><head><title>my div</title>');
            /*optional stylesheet*/ //myWindow.document.write('<link rel="stylesheet" href="main.css" type="text/css" />');
            myWindow.document.write('</head><body >');
            myWindow.document.write(data);
            myWindow.document.write('</body></html>');
            myWindow.document.close(); // necessary for IE >= 10

            myWindow.onload=function(){ // necessary if the div contain images

                myWindow.focus(); // necessary for IE >= 10
                myWindow.print();
                myWindow.close();
            };
        }
    </script>

   <script>
function setCookie(cname, cvalue, exdays) {
  var d = new Date();
  d.setTime(d.getTime() + (exdays * 1 * 1* 300 * 1000));
  var expires = "expires="+d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

</script>




   
   
   
   
  <style>
    /* Remove the navbar's default margin-bottom and rounded borders */ 
    .navbar {
      margin-bottom: 0;
      border-radius: 0;
    }
    
    /* Add a gray background color and some padding to the footer */
    footer {
      background-color: #f2f2f2;
      padding: 25px;
    }
    
  .carousel-inner img {
      width: 100%; /* Set width to 100% */
      margin: auto;
      min-height:200px;
  }

  /* Hide the carousel text when the screen is less than 600 pixels wide */
  @media (max-width: 600px) {
    .carousel-caption {
      display: none; 
    }
  }
  </style>
  
  <script>
function refresh(){
	window.location.reload();
}

</script>	


<script>
function qui(){
	setCookie("test_status", 1, 0);
}

</script>	
	
	

	
	
<script>
function openPopupWin()
	{
		
        
   
		
		var inc=<?php echo $inc?>;
		
			if(inc!==""){
				
			 var user = getCookie("test_status");	
				 if (user !== "1"){
			
				
				
		var url = 'http://192.168.140.225/test204.php?pag=<?php echo $nam ?>&pag1=<?php echo $idf ?>';
		var pw = 400;		// pixels - change for your desired popup width
		var ph = 750;		// pixels - change for your popup height
		var left   = (screen.width  - pw)/2;
		var top    = (screen.height - ph)/2;
		var parms = 'modal=yes, dialog=1, height='+ ph +', width=' + pw + ',left=' + left + ', top=' + top + ', status=0, location=no, menubar=0, title=no, scrollbars=0';
		var uid = window.open(url, '', parms);
		
		 setCookie("test_status", 1, 1);
		 
		 
		
	}
	
	
	
	
	}
	 
	
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
	
<style>



#id{
background:lightgrey;
color:#303030;
border-color:lightblue;
}
#id1{
color:green;
	//font-size:3vw;
}


#form{
	color:#303030;
	//font-size:3vw;
}
#fo{
	color:#303030;
	//font-size:3vw;
}
#zp{
	color:red;
	//font-size:3vw;
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
#uv1 {
// width:30px; 
 //height:30px   
font-family: Verdana, Geneva, sans-serif;
color: blue;
border-style: none;
vertical-align: center;
text-align: center;

}

#av{
//width:190px; 
  color:white;
  //font-size:2vw;
//background:#0000FF;	
background:#A9A9A9;
	
	
}





</style>	
        
      <script>
function myFunctions() {
	//var myElement = document.getElementById("notes");
	window.document.getElementById("notes").print(); 
//"The text from the intro paragraph is " + myElement.innerHTML;
   window.print();
}

	
	
	

 </script>
 
 
 <script>
function showdate() {
	

	
	
	
 
		
		
		  
  if (window.XMLHttpRequest) {
    // code for modern browsers
    xhttp = new XMLHttpRequest();
    } else {
    // code for IE6, IE5
    xhttp = new ActiveXObject("Microsoft.XMLHTTP");
  }
  xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      document.getElementById("form2").innerHTML = this.responseText;
    }
  };
   xhttp.open("GET","test333.php");
 
  xhttp.send();
  
   
}
</script>
 
 
 
<script>
function myFunction2() {
    window.open ("http://192.168.140.225//test154.php","","menubar=0,resizable=1,width=900,height=500");
}
</script>  

 <script>
function myFunction3() {
   window.open ("http://192.168.140.225/test168.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf)?>&page2=<?php echo urlencode($inc)?>","","menubar=0,resizable=1,width=2000,height=2000");
}
</script> 


<script>
function myCalendar() {
   window.open ("http://192.168.140.225/test300.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf)?>&page2=<?php echo urlencode($inc)?>","","menubar=0,resizable=1,width=2000,height=2000");
}
</script> 


<script>
function myCalendar() {
   window.open ("http://192.168.140.225/test300.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf)?>&page2=<?php echo urlencode($inc)?>","","menubar=0,resizable=1,width=2000,height=2000");
}
</scrip>


<script>
function myFunction4() {
   window.open ("http://192.168.140.225//test237.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf)?>","","menubar=0,resizable=1,width=1450,height=800");
}
</script>
 

 <script>
function prev(){
	
sendus()+show()+address()+address2()+loadDoc()+win()+email()+cust()+bus()+url()+wi()+test()+post12();
}
function on(){
	y=setInterval("prev()",1000);
}


</script> 

<script>

function clac(){
	
	class List{
		
		constructor(items,nb){
			this.items=items;
			this.nb=nb;
		}
		
		sayList(){
			alert(this.items + this.nb);
		}
	}
	
	const say = new List(["a","b","c"],3);
	var myJason = JSON.stringify(say);
//alert(myJason);
  //say.sayList();
	
	
   class ListTwo extends List{
	   
	   constructor(items,nb,amount){
		   
		   super(items,nb);
		   this.amount=amount;
		   
	   }
	   
	   sayListTwo(){
		   alert(this.items +","+ this.nb+","+this.amount);
	   }
	   
   }
	   
	   const sayTwo = new ListTwo(["a","b","c"],3,5);
	   
	   var myJason = JSON.stringify(sayTwo);
       alert(myJason);
	   
	   
   }










</script>


		




<script>
function openWin() {
   
 
  window.open ("http://192.168.140.225//test273.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf) ?>&page2=<?php echo urlencode($inc) ?>","","menubar=0,resizable=1,width=650,height=750");
 
        
}		
    

</script>

<script>
function leads(){
	
 window.open ("http://192.168.140.225//test82.php","","menubar=0,resizable=1,width=950,height=750");
	
}
</script> 




<script>
function deals(){
	
 window.open ("http://192.168.140.225//test84.php","","menubar=0,resizable=1,width=850,height=550");
	
}
</script>

<script>
function ndeals(){
	
 window.open ("http://192.168.140.225//test137.php","","menubar=0,resizable=1,width=850,height=550");
	
}
</script>





<script>
function campaigns(){
	
 window.open ("http://192.168.140.225//test86.php","","menubar=0,resizable=1,width=820,height=650");
	
}
</script> 











<script>
function Import(){
	
 window.open ("http://192.168.140.225//test31.php","","menubar=0,resizable=1,width=480,height=300");
	
}
</script> 


<script>
function Importc1(){
	
 window.open ("http://192.168.140.225//test267.php","","menubar=0,resizable=1,width=480,height=300");
	
}
</script> 





<script>
function ImportVente(){
	
 window.open ("http://192.168.140.225//test286.php","","menubar=0,resizable=1,width=480,height=300");
	
}
</script> 



<script>
function ImportCalendar(){
	
 window.open ("http://192.168.140.225//test309.php","","menubar=0,resizable=1,width=480,height=300");
	
}
</script> 






<script>
function Import2(){
	
 window.open ("http://192.168.140.225//test220.php","","menubar=0,resizable=1,width=480,height=300");
	
}
</script> 




<script>
function Export(){
	
 window.open ("http://192.168.140.225//test43.php","","menubar=0,resizable=1,width=480,height=300");
	
}
</script> 







<script>
function Exportc1(){
	
 window.open ("http://192.168.140.225//test269.php","","menubar=0,resizable=1,width=480,height=300");
	
}
</script>




<script>
function ExportVente(){
	
 window.open ("http://192.168.140.225//test284.php","","menubar=0,resizable=1,width=480,height=300");
	
}
</script> 



<script>
function ExportCalendar(){
	
 window.open ("http://192.168.140.225//test311.php","","menubar=0,resizable=1,width=480,height=300");
	
}
</script> 






<script>
function Export2(){
	
 window.open ("http://192.168.140.225//test219.php","","menubar=0,resizable=1,width=480,height=300");
	
}
</script> 


<script>

	
 


    function uro() {
	window.moveTo(1100,0);	
    //window.open ("http://192.168.140.225//test51.php","","menubar=0,resizable=1,width=1100,height=800");
	
	
	
    }
</script> 


<script> 
function uro1() {
	myw=window.open ("http://192.168.140.225//test51.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf)?>&page2=<?php echo urlencode($inc)  ?>","","menubar=0,resizable=1,width=450,height=950");
		
     // myw=window.open ("http://192.168.140.225//test51.php?page=<?php echo $nam ?>&page1=<?php echo $idf ?>","","menubar=0,resizable=1,width=300,height=800");
	
	//window.open ("http://192.168.140.225//test51.php","","menubar=0,resizable=1,width=350,height=800");
	
    }
	

</script> 




<script>
function search5(){
myw=window.open ("http://192.168.140.225//search.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf)?>&page2=<?php echo urlencode($inc)?>","","menubar=0,resizable=1,width=1000,height=950");	

	
}
</script>



<script>
function search15(){
myw=window.open ("http://192.168.140.225//test336.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf)?>&page2=<?php echo urlencode($inc)?>","","menubar=0,resizable=1,width=1000,height=950");	

	
}
</script>



<script>
function search10(){
	
 window.open ("http://192.168.140.225//test242.php","","menubar=0,resizable=1,width=650,height=680");
	
}
</script>


<script>
function search11(){
	
 window.open ("http://192.168.140.225//test255.php","","menubar=0,resizable=1,width=950,height=880");
	
}
</script>


<script>
function search12(){
	
 window.open ("http://192.168.140.225//test251.php","","menubar=0,resizable=1,width=450,height=400");
	
}
</script>

<script>
function search13(){
	
 window.open ("http://192.168.140.225//test263.php","","menubar=0,resizable=1,width=450,height=500");
	
}
</script>


<script>
function search23(){
	
 window.open ("http://192.168.88.222/test263.php","","menubar=0,resizable=1,width=450,height=500");
	
}
</script>





<script>
function search6(){
	
 window.open ("http://192.168.140.225//test224.php","","menubar=0,resizable=1,width=650,height=680");
	
}
</script>


<script>
function search16(){
myw=window.open ("http://192.168.140.225//search3.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf)?>&page2=<?php echo urlencode($inc)?>","","menubar=0,resizable=1,width=1000,height=950");	

	
}
</script>



<script>
function search2(){
	
 
window.open ("http://192.168.140.225//search2.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf) ?>","","menubar=0,resizable=1,width=1000,height=950");	
}
</script> 



<script>
function incidents(){
	window.open ("http://192.168.140.225//test264.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf) ?>","","menubar=0,resizable=1,width=1000,height=950");	
 //window.open ("http://192.168.140.225//test264.php","","menubar=0,resizable=1,width=1000,height=680");
	
}
</script> 


<script>
function task(){
	window.open ("http://192.168.140.225//test350.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf) ?>","","menubar=0,resizable=1,width=1000,height=950");	
 //window.open ("http://192.168.140.225//test264.php","","menubar=0,resizable=1,width=1000,height=680");
	
}
</script> 




<script>
function list(){
	
 window.open ("http://192.168.140.225//test265.php","","menubar=0,resizable=1,width=1400,height=680");
	
}
</script> 

<script>
function list1(){
	
 window.open ("http://192.168.140.225//test321.php","","menubar=0,resizable=1,width=1400,height=680");
	
}
</script> 


<script>
function list79(){
	
 window.open ("http://192.168.140.225//test266.php","","menubar=0,resizable=1,width=1400,height=680");
	
}
</script> 


<script>
function list80(){
	
 window.open ("http://192.168.140.225//test308.php","","menubar=0,resizable=1,width=1400,height=680");
	
}
</script> 


<script>
function la(){
	
 window.open ("http://192.168.140.225//test156.php","","menubar=0,resizable=1,width=1000,height=680");
	
}
</script>

<script>
function ao(){
	
 window.open ("http://192.168.140.225//test293.php","","menubar=0,resizable=1,width=950,height=680");
	
}
</script>


<script>
function ago(){
	
 window.open ("http://192.168.140.225//test295.php","","menubar=0,resizable=1,width=950,height=680");
	
}
</script>

<script>
function aos(){
	
 window.open ("http://192.168.140.225//test196.php","","menubar=0,resizable=1,width=650,height=680");
	
}
</script>

<script>
function fo(){
	
 window.open ("http://192.168.140.225//test193.php","","menubar=0,resizable=1,width=650,height=680");
	
}
</script>


<script>
function lo(){
	
 window.open ("http://192.168.140.225//test180.php","","menubar=0,resizable=1,width=650,height=680");
	
}
</script>


<script>
function o(){
	
 window.open ("http://192.168.140.225//test187.php","","menubar=0,resizable=1,width=650,height=680");
	
}
</script>






<script>
function aga(){
	
 window.open ("http://192.168.140.225//test222.php","","menubar=0,resizable=1,width=1000,height=680");
	
}
</script>



<script>
function bv(){
	
 window.open ("http://192.168.140.225//test192.php","","menubar=0,resizable=1,width=650,height=680");
	
}
</script>


<script>
function crr(){
myw=window.open ("http://192.168.140.225//test240.php?pag=<?php echo $nam ?>&pag1=<?php echo $idf ?>&page2=<?php echo $inc ?>","","menubar=0,resizable=1,width=1050,height=1000");	

	
}
</script> 



<script>
function number(){
myw=window.open ("http://192.168.140.225//numbersearch.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf)?>&page2=<?php echo urlencode($inc)?>","","menubar=0,resizable=1,width=1050,height=1000");	

	
}
</script> 

<script>
function number22(){
myw=window.open ("http://192.168.140.225//test275.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf)?>&page2=<?php echo urlencode($inc) ?>","","menubar=0,resizable=1,width=1000,height=950");	

	
}
</script> 


<script>
function google(){
myw=window.open ("http://192.168.140.225//test342.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf)?>&page2=<?php echo urlencode($inc) ?>","","menubar=0,resizable=1,width=1000,height=950");	

	
}
</script> 




<script>
function number2(){
myw=window.open ("http://192.168.140.225//test171.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf) ?>","","menubar=0,resizable=1,width=750,height=450");	

}
</script> 

<script>
function ImportSql(){
	
 window.open ("http://192.168.140.225//test38.php","","menubar=0,resizable=1,width=480,height=300");
	
}
</script> 

<script>
function bb(){
	
 window.open ("http://192.168.140.225//test42.php","","menubar=0,resizable=1,width=480,height=300");
	
}
</script> 


<script>
function add(){
	var myw;
	
   myw=window.open ("http://192.168.140.225//test19.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf)?>&page2=<?php echo urlencode($inc) ?>","","menubar=0,resizable=1,width=680,height=950");
	
}
</script>

<script>
function add4(){
	var myw;
	
   myw=window.open ("http://192.168.140.225//test231.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf) ?>","","menubar=0,resizable=1,width=480,height=650");
	
}
</script> 


<script>
function add2(){
	var myw;
	
   myw=window.open ("http://192.168.140.225//test182.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf) ?>","","menubar=0,resizable=1,width=480,height=650");
	
}
</script> 


<script>
function add22(){
	var myw;
	
   myw=window.open ("http://192.168.140.225//test279.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf) ?>","","menubar=0,resizable=1,width=480,height=650");
	
}
</script> 


<script>
function add3(){
	var myw;
	
   myw=window.open ("http://192.168.140.225//test212.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf) ?>","","menubar=0,resizable=1,width=400,height=650");
	
}
</script> 

<script>
function cleard() {
   window.open ("http://192.168.140.225//test188.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf) ?>","","menubar=0,resizable=1,width=950,height=650");
}
</script> 


<script>
function search3(){
	
 window.open ("http://192.168.140.225//test184.php","","menubar=0,resizable=1,width=480,height=620");
	
}
</script> 

<script>
function search7(){
	
 window.open ("http://192.168.140.225//test225.php","","menubar=0,resizable=1,width=480,height=620");
	
}
</script> 



<script>
function search4(){
	
 window.open ("http://192.168.140.225//test189.php","","menubar=0,resizable=1,width=480,height=620");
	
}
</script> 


 
<script>
function backup(){
	var myw;
	
   myw=location.replace('test36.php');
	
}
</script> 
<script>
function history(){
	
 window.open ("http://192.168.140.225//test29.php","","menubar=0,resizable=1,width=480,height=350");
	
}
</script> 


<script>
function del(){
myw=window.open ("http://192.168.140.225//test29.php?page=<?php echo urlencode($nam) ?>&page1=<?php echo urlencode($idf)?>&page2=<?php echo urlencode($inc)?>","","menubar=0,resizable=1,width=600,height=650");	

	
}
</script>


<script>
function deldriver(){
	
 window.open ("http://192.168.140.225//test206.php","","menubar=0,resizable=1,width=480,height=350");
	
}
</script> 


<script>
function delproduct(){
	
 window.open ("http://192.168.140.225//test214.php","","menubar=0,resizable=1,width=480,height=350");
	
}
</script> 


<script>
function delAllVente(){
	
 window.open ("http://192.168.140.225//test227.php","","menubar=0,resizable=1,width=480,height=350");
	
}
</script> 

<script>
function delid(){
	
 window.open ("http://192.168.140.225//test172.php","","menubar=0,resizable=1,width=480,height=350");
	
}
</script>



<script>
function delAll(){
	
 window.open ("http://192.168.140.225//test79.php","","menubar=0,resizable=1,width=480,height=350");
	
}
</script> 


<script>
function deldrAll(){
	
 window.open ("http://192.168.140.225//test208.php","","menubar=0,resizable=1,width=480,height=350");
	
}
</script> 


<script>
function delprAll(){
	
 window.open ("http://192.168.140.225//test216.php","","menubar=0,resizable=1,width=480,height=350");
	
}
</script> 



<script>
function delfe(){
	
 window.open ("http://192.168.140.225//test164.php","","menubar=0,resizable=1,width=480,height=350");
	
}
</script>


<script>
function del_al(){
	
 window.open ("http://192.168.140.225//test306.php","","menubar=0,resizable=1,width=480,height=350");
	
}
</script>





<script>
function del_el(){
	
 window.open ("http://192.168.140.225//test166.php","","menubar=0,resizable=1,width=450,height=350");
	
}
</script>


<script>
function del_ag(){
	
 window.open ("http://192.168.140.225//test296.php","","menubar=0,resizable=1,width=450,height=350");
	
}
</script>





<script>
function delidAll(){
	
 window.open ("http://192.168.140.225//test210.php","","menubar=0,resizable=1,width=480,height=350");
	
}
</script> 

	


	
<script>
function search111(){
	
 window.open ("http://192.168.140.225//test278.php","","menubar=0,resizable=1,width=650,height=680");
	
}
</script>


<script>
function quit(){
	

	window.close();
}

</script>	




<script>
function update(){
	var myw;
	
   myw=window.open ("http://192.168.140.225//test27.php","","menubar=0,resizable=1,width=480,height=750");
	
}
</script> 


 <script>
function addCrm(){
	var myw;
	
   
	myw=window.open ("http://192.168.140.225//test56.php?page=<?php echo $nam ?>&page1=<?php echo $idf ?>&page2=<?php echo $inc ?>","","menubar=0,resizable=1,width=450,height=800");
}
</script> 

	
<script>
function updateCrm(){
	var myw;
	
   
	myw=window.open ("http://192.168.140.225//before.php?pag=<?php echo $nam ?>&pag1=<?php echo $idf ?>&pag2=<?php echo $inc ?>","","menubar=0,resizable=1,width=500,height=800");
}
</script> 


<script>
window.onbeforeunload = 
function sq(str) {
	
	 var name="<?php echo $nam ?>" ;
  var idf="<?php echo $idf ?>" ;
  
  //alert(name+idf);
	
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
      document.getElementById("sq").innerHTML = this.responseText;
    }
  };
  xhttp.open("GET","test313.php?name="+name+"&idf="+idf, true);
 
  xhttp.send();
}
</script>

	
<!--div id="demo">here</div-->

        
        
 <script>
function ura(){
	var mx;
	
   mx=1;
	
}
</script>    



<script>
function size(){
	window.resizeTo(700,750);
	
}

</script>
 
 





 
 
 
<script>
function add44(){
	var myw;
	
   
	myw=window.open ("http://192.168.140.225//test348.php?pag=<?php echo $nam ?>&pag1=<?php echo $idf ?>&pag2=<?php echo $inc ?>","","menubar=0,resizable=1,width=500,height=800");


 
 
}
 
</script>    




 
  
  
  
</head>
<body  onload="on()">


<nav class="navbar navbar-inverse">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle"  style="background:#FF33E0 "     data-toggle="collapse" data-target="#myNavbar">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>                        
      </button>
     
	    
	
		  <!--button class="navbar-brand" href="#" "><p style="color:lightblue ">  <a href="<?php echo $url ?>">OPEN</a></p></button-->
		  <!--button class="navbar-brand" href="#" onclick="javascript:add()">ADD </button-->
	      <!--button class="navbar-brand" href="#" onclick="javascript:number22()">  NUM </button-->
		  <!--button class="navbar-brand" href="#" onclick="javascript:uro1()"> CRM </button-->
		   <!--button class="navbar-brand" href="#" onclick="javascript:myFunction3()"><p style="color:lightblue "> POS </p></button-->
		    <!--button class="navbar-brand" href="#" onclick="javascript:addCrm()">  ADC</button-->
		    <!--button class="navbar-brand" href="#" onclick="javascript:updateCrm()">  UPC</button-->
			<!--button class="navbar-brand" href="#" onclick="javascript:incidents()"><p style="color:lightblue ">  RCRM</p></button-->
		
		  <!--button class="navbar-brand" href="#" onclick="javascript:post12()"><p style="color:lightblue "> API </p></button-->
		  <!--button class="navbar-brand" href="#" onclick="javascript:refresh()"> R </button-->
		   <!--button class="navbar-brand" href="#" onclick="javascript:crr()">  MOD </button-->
		    <!--button class="navbar-brand" href="#" onclick="javascript:myCalendar()">TASK </button-->
			 <!--button class="navbar-brand" href="#" onclick="javascript:cashbutton()"><p style="color:lightblue "> CASH </p></button-->
		 <!--button class="navbar-brand" href="#" onclick="javascript:add44()">REM </button-->
		  <!--button class="navbar-brand" href="#" onclick="javascript:uro1()">   CIM </button-->
		   <!--button class="navbar-brand" href="#" onclick="javascript:list()">   LIST </button-->
		    <!--button class="navbar-brand" href="#" onclick="javascript:quit()"> <p style="color:lightblue ">  QUIT </p></button-->
			<!--button class="navbar-brand" href="#" onclick="javascript:sendurl()"><p style="color:lightblue "> google </p></button-->
    </div>
    <div class="collapse navbar-collapse" id="myNavbar">
      <ul class="nav navbar-nav">
        
	  <li class="dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">MORE
        <span class="caret"></span></a>
        <ul class="dropdown-menu">
		  <ul>
         
		  <!--li><a href="#" onclick="javascript:number()">Num</a></li-->
		  <li><a href="#" onclick="javascript:search5()">First name</a></li>
		   <li><a href="#" onclick="javascript:search15()">Last name</a></li>
		  <li><a href="#" onclick="javascript:search16()">Company</a></li>
		  <li><a href="#" onclick="javascript:search2()">Business</a></li>
		   <li><a href="#" onclick="javascript:del()">Del</a></li>
		  </ul>
          
        </ul>
      </li>
	  
	  
        
         
		  
	
	  
		  <li class="dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">REPORTS
        <span class="caret"></span></a>
        <ul class="dropdown-menu">
		<ul>
		  <li><a href="#" onclick="javascript:list1()">Simple List(Address)</a></li>
		   <li><a href="#" onclick="javascript:list79()">Simple List(Crm)</a></li>
		   <!--li><a href="#" onclick="javascript:list80()">Simple List(Calendar)</a></li-->
          <li><a href="#" onclick="javascript:incidents()">Comments</a></li>
		   <!--li><a href="#" onclick="javascript:task()">Tasks</a></li-->
		  <!--li><a href="#" onclick="javascript:aga()">Activities</a></li-->
		  <!--li><a href="#" onclick="javascript:ao()">All Orders</a></li>
		  <li><a href="#" onclick="javascript:ago()">Agent Orders</a></li>
		  <!--li><a href="#" onclick="javascript:sendus()">test</a></li-->
		 </ul>
		  
          
        </ul>
      </li>
	  
	  
	  
	  
	  
	  
	  <li class="dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">SYSTEM
        <span class="caret"></span></a>
        <ul class="dropdown-menu">
          <!--li><a href="#" >POS</a></li>
		  <ul>
		   <li><a href="#" onclick="javascript:myFunction3()">POS</a></li>
		    <li><a href="#" onclick="javascript:post12()">post12</a></li>
			<li><a href="#" onclick="sendurl()">post13</a></li>
			
		  </ul>
		  
		  
		  
		  <li><a href="#">Products</a></li>
		      <ul >
         <li><a href="#" onclick="javascript:search111()">Products</a></li>
		   <li><a href="#" onclick="javascript:add4()">Name_Pr</a></li>
          <li><a href="#" onclick="javascript:add3()">Add_Pr</a></li>
		 
		
		  
          <li><a href="#" onclick="javascript:delprAll()">Delpr-All</a></li>
		  <li><a href="#" onclick="javascript:delAllVente()">DelAllvente</a></li>
		 
		      </ul-->
			  
			  
			  
			  
			  
			  
			<!--li><a href="#">Users</a></li>
		      <ul >
			 <li><a href="#" onclick="javascript:add22()">Users</a></li>  
          <li><a href="#" onclick="javascript:add2()">Add_user</a></li>
		  <li><a href="#" onclick="javascript:search3()">Name_user</a></li>
		  <li><a href="#" onclick="javascript:search4()">Id_user</a></li>
		  <li><a href="#" onclick="javascript:del_ag()">Del-user</a></li>
		  <li><a href="#" onclick="javascript:del_al()">Del-ALL</a></li>
		 
		 
		      </ul-->    
			  
			
		  <li><a href="#">Agents</a></li>
		      <ul >
         <li><a href="#" onclick="javascript:search10()">AGENTS</a></li>
		  
		  <!--li><a href="#" onclick="javascript:del_el()">Del-ag</a></li>
		  <li><a href="#" onclick="javascript:delfe()">Delag-All</a></li-->
		 
		      </ul>
			  
			  
			  
			
		  
          
        </ul>
      </li>
		  
       
	  
	  
	  
           
	
	  
	  
	  <li class="dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">DATA
        <span class="caret"></span></a>
        <ul class="dropdown-menu">
          <li><a href="#" >Import</a></li>
		  <ul>
		   <li><a href="#" onclick="javascript:Import()">Client</a></li>
		   <!--li><a href="#" onclick="javascript:Importc1()">Crm</a></li-->
		   <!--li><a href="#" onclick="javascript:ImportVente()">Vente</a></li>
		    <li><a href="#" onclick="javascript:ImportCalendar()">Calendar</a></li-->
		  </ul>
		  
		  
		  
		  <li><a href="#">Export</a></li>
		      <ul >
          <li><a href="#" onclick="javascript:Export()">Client</a></li>
		  <!--li><a href="#" onclick="javascript:Exportc1()">Crm</a></li-->
		   <!--li><a href="#" onclick="javascript:ExportVente()">Vente</a></li>
		    <li><a href="#" onclick="javascript:ExportCalendar()">Calendar</a></li-->
		      </ul>
			 <li><a href="#" onclick="javascript:bb()">Back-Up</a></li> 
		  <li><a href="#" onclick="javascript:ImportSql()">Recovery</a></li>
		  
		   <li><a href="#" onclick="javascript:delAll()">Delete All</a></li>
		  
		  
		  
          
        </ul>
      </li>
       
    </div>
  </div>
</nav>


   
 

 
  <div class="jumbotron">  
   
  <table class="table ">
 <tr>
 
<td>

<div class="mb-3">
  <label for="exampleFormControlInput1" class="form-label">INCOMING CALL</label>
  <input type="text" class="form-control" id="bp" placeholder="" name="bp" id="bp" onclick="openWin()"  >
</div>


<div class="mb-3">
  <label for="exampleFormControlInput1" class="form-label">NAME</label>
  <input type="text" class="form-control" id="ap" placeholder="" name="ap"   >
</div>

<div class="mb-3">
  <label for="exampleFormControlTextarea1" class="form-label">FIRST ADDRESS</label>
  <textarea class="form-control" id="cp" rows="3"  name="cp"       ></textarea>
</div>

<div class="mb-3">
  <label for="exampleFormControlTextarea1" class="form-label">SECOND ADDRESS </label>
  <textarea class="form-control" id="cp2" rows="3"  name="cp2"       ></textarea>
</div>

</td>

<td>
<div >
        
           
            <p >MORE
              <ul class="list-group">
                <li class="list-group-item "><a href="#" onclick="javascript:add()">ADD</a></li>
                <li class="list-group-item "><a href="#" onclick="javascript:number22()">NUM</a></li>
                <li class="list-group-item "><a href="#" onclick="javascript:uro1()">CIM</a></li>
                <li class="list-group-item "><a href="#" onclick="javascript:refresh()">R</a></li>
              </ul>
            </p>
          </div>
       
</td>
 </tr>



   
 
   
 </table>















 <script>
function submit() {
    var form1 = document.forms['form1']
    
    
         form1.submit()
    
}
</script>  

<script>
function submit() {
    var form1 = document.forms['form1']
    
    
         form1.submit()
    
}
</script>  

<script>
function cashbutton() {
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
      document.getElementById("cas").innerHTML = this.responseText;
    }
  };
  xhttp.open("GET","test339.php", true);
  xhttp.send();
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
function post12() {
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
      document.getElementById("pio").innerHTML = this.responseText;
    }
  };
  xhttp.open("GET","test341.php", true);
  xhttp.send();
}
</script>













<script>
function wi() {
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
      document.getElementById("zp").innerHTML = this.responseText;
    }
  };
  xhttp.open("GET","test162.php", true);
  xhttp.send();
}
</script>

<script>
function sendurl() {
	
	 var x=document.getElementById("se").value;
	 //alert('03205818');
	 //var x = '03205818';
	//alert(x);
	
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
      document.getElementById("pio").innerHTML = this.responseText;
    }
  };
  xhttp.open("GET","test341.php?q=" + x, true);
  xhttp.send();
}
</script>



<script>
function sendus() {
	
	 //var x=document.getElementById("se").value;
	 //alert('03205818');
	 var x = "<?php echo $nam ?>";
	// var x=0;
	//alert(x);
	
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
      document.getElementById("pio").innerHTML = this.responseText;
    }
  };
  xhttp.open("GET","test347.php?q=" + x, true);
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
function loadDoc(str) {
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
  xhttp.open("GET","test23.php?q="+str, true);
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
function address2() {
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
      document.getElementById("cp2").value = this.responseText;
      
    }
  };
  xhttp.open("GET","test353.php", true);
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
      document.getElementById("com").value = this.responseText;
      
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
 
 
 
 <script>
function sq(str) {
	
	 var name="<?php echo $nam ?>" ;
  var idf="<?php echo $idf ?>" ;
  
  //alert(name+idf);
	
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
      document.getElementById("sq").innerHTML = this.responseText;
    }
  };
  xhttp.open("GET","test313.php?name="+name+"&idf="+idf, true);
 
  xhttp.send();
}
</script>

 
 
 
 




















 </div>
</body>



<footer class="container-fluid text-center">

<p align="center"><font color="black"  >&copy 2018 Nahed Computers & Communications</font></p>

<p align="center"><font color="black"  > <i class="fa fa-phone"></i> +00 961 5 454262   |    <i class="fa fa-mobile"></i> +00 961 3 205818 </font></p>

<p align="center"><font color="black"  >MYPWCA 1.5</font></p>


</footer>













</html>
