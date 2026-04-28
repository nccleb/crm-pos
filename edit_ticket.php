<?php
header('Content-Type: application/json');
$host="192.168.1.101"; $user="root"; $pass="1Sys9Admeen72"; $db="nccleb_test";
$conn=mysqli_connect($host,$user,$pass,$db);
if(!$conn){ echo json_encode(['success'=>false,'error'=>'DB connection failed']); exit; }

$idc = intval($_POST['idc']);
$task = mysqli_real_escape_string($conn, $_POST['task']);
$incident = mysqli_real_escape_string($conn, $_POST['incident']);
$priority = $_POST['priority'];
$status = $_POST['status'];
$agent = intval($_POST['agent']);
$la = $_POST['la'];

$sql = "UPDATE crm SET task='$task', incident='$incident', priority='$priority', status='$status', idfc='$agent', la='$la' WHERE idc=$idc";
if(mysqli_query($conn, $sql)){
    echo json_encode(['success'=>true]);
}else{
    echo json_encode(['success'=>false,'error'=>mysqli_error($conn)]);
}
?>
