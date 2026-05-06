<!DOCTYPE html>
<html>
<head>
 <?php include('head.php'); ?>


<style>
#form{
	color:blue;
	font-size:28px;
}

#fo{
	color:red;
	font-size:28px;
}


</style>



<script>
function quit(){
	window.close()
}

</script>

<title></title>
</head>
<body>
<?php
$idr = mysqli_connect("172.18.208.1", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  exit();
}
if(isset($_FILES['csv_file'])&&($_FILES['csv_file']['error']==0)){
	if($_FILES['csv_file']['size']<=8000000){

		$info=pathinfo($_FILES['csv_file']['name']);
		$target=$info['basename'];
		
		$extensionU=$info['extension'];
        $extensionA=array("csv");
		if(in_array($extensionU,$extensionA)){
			move_uploaded_file($_FILES['csv_file']['tmp_name'],"upload/$target");
			
		}
		else{
	echo "<p style=\"color:red;font-size:28px\">Invalid file type!</p>";
	 echo "<button id=\"form\" type=\"button\" onclick=\"quit()\">Quit</button>";
  exit();
}
		
		
	}
	else{
	echo "<p style=\"color:red;font-size:28px\">Invalid file size!</p>";
	 echo "<button id=\"form\" type=\"button\" onclick=\"quit()\">Quit</button>";
  exit();
}
}
else{
	echo "<p style=\"color:red;font-size:28px\">Error! Please choose a correct file!</p>";
	 echo "<button id=\"form\" type=\"button\" onclick=\"quit()\">Quit</button>";
  exit();
}




$handle = fopen("upload/$target", "r");
$t=0;
   while($file=fgetcsv($handle,",")){
      $t=1;
	    $idc=$file[0];
	    $ca=$file[1];
	    $ba=$file[2];
	    $lc=$file[3];
	    $la=$file[4];
	    $in=$file[5];
	    $st=$file[6];
		$num=$file[7];
	    $id=$file[8];
		$idfc=$file[9];
	
  if (!preg_match("/^[0-9]*$/",$idc)) {
  echo "<p style=\"color:red;font-size:28px\">Invalid ID format!</p>"."<br/>";
  echo "<button id=\"form\"  type=\"button\" onclick=\"quit()\">Quit</button>";
  exit();  
	} 
   
if (!preg_match("/^[a-zA-Z ]*$/",$ca)) {
  echo "<p style=\"color:red;font-size:28px\">Invalid Category format!</p>"."<br/>";
  echo "<button id=\"form\" type=\"button\" onclick=\"quit()\">Quit</button>";
  exit();  
}
if (!preg_match("/^[0-9-: ]*$/",$ba)) {
  echo "<p style=\"color:red;font-size:28px\">Invalid balance format!</p>"."<br/>";
  echo "<button id=\"form\" type=\"button\" onclick=\"quit()\">Quit</button>";
  exit();  
}
if (!preg_match("/^[0-9a-zA-Z'?!=;~+%`\[\]()$*\"|:.,#&_\s-\p{Arabic} ]*$/u",$lc)) {
  echo "<p style=\"color:red;font-size:28px\">Invalid Last Contacted format!</p>"."<br/>";
  echo "<button id=\"form\"  type=\"button\" onclick=\"quit()\">Quit</button>";
  exit();  
}




if (!preg_match("/^[0-9a-zA-Z.,\s ]*$/",$la)) {
  echo "<p style=\"color:red;font-size:28px\">Invalid Last Activity format!</p>"."<br/>";
  echo "<button id=\"form\" type=\"button\" onclick=\"quit()\">Quit</button>";
  exit();  
}
if (!preg_match("/^[0-9a-zA-Z.,\s ]*$/",$in)) {
  echo "<p style=\"color:red;font-size:28px\">Invalid Incident format!</p>"."<br/>";
  echo "<button id=\"form\" type=\"button\" onclick=\"quit()\">Quit</button>";
  exit();  
}
if (!preg_match("/^[0-9a-zA-Z.,\s ]*$/",$st)) {
  echo "<p style=\"color:red;font-size:28px\">Invalid Status format!</p>"."<br/>";
  echo "<button id=\"form\" type=\"button\" onclick=\"quit()\">Quit</button>";
  exit();  
}

if (!preg_match("/^[0-9a-zA-Z.,\s ]*$/",$num)) {
  echo "<p style=\"color:red;font-size:28px\">Invalid Number format!</p>"."<br/>";
  echo "<button id=\"form\" type=\"button\" onclick=\"quit()\">Quit</button>";
  exit();  
}

if (!preg_match("/^[0-9a-zA-Z.,\s ]*$/",$id)) {
  echo "<p style=\"color:red;font-size:28px\">Invalid ID format!</p>"."<br/>";
  echo "<button id=\"form\" type=\"button\" onclick=\"quit()\">Quit</button>";
  exit();  
}

if (!preg_match("/^[0-9a-zA-Z.,\s ]*$/",$idfc)) {
  echo "<p style=\"color:red;font-size:28px\">Invalid ID format!</p>"."<br/>";
  echo "<button id=\"form\" type=\"button\" onclick=\"quit()\">Quit</button>";
  exit();  
}
	   
  
   
   
    $req = $idr->prepare("INSERT INTO crm (idc,category,balance,lcd,la,incident,status,num,id,idfc) VALUES (?,?,?,?, ?, ?,?,?,?,?)");

   $req->bind_param("ssssssssss", $idc, $ca,$ba,$lc,$la,$in,$st,$num,$id,$idfc);


   $req->execute();


   $req->close();
   
   
   
   





   
	
	
   }
   
    $req1=mysqli_query($idr,"select * from crm");
	
	
	
	   if(mysqli_num_rows($req1)>0 ){
		   echo "<p style=\"color:red;font-size:28px\">data is well inserted</p>";
		   echo "<button id=\"id\"   type=\"button\" onclick=\"quit()\">Quit</button>";
		   fclose($handle);
		   mysqli_close($idr);
	   }
	   else{
		   
		   echo "<p style=\"color:red;font-size:28px\">data was not inserted</p>";
		   echo "<button id=\"id\" type=\"button\" onclick=\"quit()\">Quit</button>";
   }
   
      
	


	

?>	
</body>
</html>			
			  
	


	

	
			
	