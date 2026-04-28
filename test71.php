<!DOCTYPE html>
<html>
<head>

 <?php include('head.php'); ?>
<style>
#p{
	color:blue;
	font-size:36px;
}

#q{
	color:blue;
	font-size:24px;
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
backup_tables('192.168.1.101','username','password','blog');


function backup_tables($host,$user,$pass,$name,$tables = '*')
{
	
	$idr = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  exit();
}
	
		$result = mysqli_query($idr,'SELECT * FROM crm');
		
		$num_fields = mysqli_num_fields($result);
		for ($i = 0; $i < $num_fields; $i++) 
		{
			while($row = mysqli_fetch_row($result))
			{
				
				
				for($j=0; $j < $num_fields; $j++) 
				{
					
					
					
					if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
					if ($j < ($num_fields-1)) { $return.= ','; }
				}
				$return.= "\n";
			}
		}
		
		$return= rtrim($return);
		
	
	 $handle = fopen('crm-csv-backup-'.time().'-'.(md5(implode(',',$tables))).'.csv','w+');
	
	fwrite($handle,$return);
	fclose($handle);
}
     echo "<p id=\"p\">Export is completed!</p>";
	 
	 echo "<button  type=\"button\" onclick=\"quit()\"><p id=\"q\">Quit</p></button>";











?>
</body>
</html>