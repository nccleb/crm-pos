<?php
session_start();
$mapo=$_SESSION["mapi"];
?>

<?php $opic=   "c".":"."\\"."Mdr"."\\"."CallerID".date("Y")."-". date("m")."."."txt" ?>

<?php

// Initialize variables for the form
$inc = ""; // Initialize caller ID variable

$fichier = "CaCallStatus.dat";
if (file_exists($fichier)) {
    $xml = simplexml_load_file($fichier);
    if ($xml) {
        foreach ($xml as $CallRecord) {
            if (isset($CallRecord->ext)) {
                $ext = $CallRecord->ext;
            }
            if (isset($CallRecord->CallerID)) {
                $inc = (string)$CallRecord->CallerID;
            }
        }
    }
}

 //If no caller ID from XML, try to get from session
//if (empty($inc) && isset($_SESSION["userinc"])) {
    
//}
//$inc = "81721326";


// Read the last line from the caller ID file
if (file_exists($opic)) {
    $f = fopen($opic, 'r');
    if ($f) {
        $line = '';
        $cursor = -1;
        fseek($f, $cursor, SEEK_END);
        $char = fgetc($f);
        
        // Trim trailing newline characters
        while ($char === "\n" || $char === "\r") {
            fseek($f, $cursor--, SEEK_END);
            $char = fgetc($f);
        }
        
        // Read until the next line begins
        while ($char !== false && $char !== "\n" && $char !== "\r") {
            $line = $char . $line;
            fseek($f, $cursor--, SEEK_END);
            $char = fgetc($f);
        }
        
        $inc = substr($line, 49, 8);
        $linenum = substr($line, 25, 8);
        $inc = trim($inc);
        $lineNum = trim($linenum);
        
        fclose($f);
    }
}

$inc = $_SESSION["userinc"];

$idr = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  exit();
}

$stmt = $idr->prepare("SELECT  * FROM client 
      
       	where (number=? or inumber=? or telmobile=? or telother=? )   ");
	  
$stmt->bind_param("iiii",$inc,$inc,$inc,$inc );
$stmt->execute();
$result = $stmt ->get_result();
$stmt->close();

// Initialize variables with default values
$contact = "No contact found";
$name = "";
$lname = "";
$num = "";
$inum = "";
$id = "";
$company = "";
$email = "";
$business = "";
$grade = "";
$address = "";
$url = "";
$idf = "";
$city = "";
$street = "";
$floor = "";
$building = "";
$zone = "";
$near = "";
$remark = "";
$telmobile = "";
$telother = "";
$apartment = "";
$address2 = "";

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
    $lname=$row['prenom']; 
     $contact= $name." ".$lname." ".$num;
    $id=$row['id'];
    $_SESSION["id"]=$id;
    $company=$row['company'];
    $email=$row['email'];
    $business=$row['business'];
    $grade=$row['grade'];
    $address=$row['address'];
    $url=$row['url'];
    if($url) {
        $url = substr($url, 7);
    }
    $idf=$row['idf'];
    $city=$row['city'];
    $street=$row['street'];
    $floor=$row['floor'];
    $building=$row['building'];
    $zone=$row['zone'];
    $near=$row['near'];
    $remark=$row['remark'];
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

// Set session contact after processing
$_SESSION["contact"] = $contact;

// Build full address from existing variables
$simple_address = "";
if(isset($city) && $city) $simple_address .= $city. ", ";
if(isset($zone) && $zone) $simple_address .="Zone " . $zone. ", ";       
if(isset($street) && $street) $simple_address .="Street " . $street . ", ";
if(isset($building) && $building) $simple_address .= "Building " . $building . ", ";
if(isset($apartment) && $apartment) $simple_address .="Apartment " . $apartment . ", ";
if(isset($floor) && $floor) $simple_address .= "Floor " . $floor . ", ";
if(isset($near) && $near) $simple_address .="Near " . $near. ", ";
if(isset($address) && $address) $simple_address .= "address1 " . $address . ", ";
if(isset($address2) && $address2) $simple_address .= "address2 " . $address2;

?>

<?php

 $nam=$_GET['page'];
if($nam == ""){
    exit( "sorry! You have to login first in mypwca!"    );
}
 $idf=$_GET['page1'];

 $_SESSION["oop"]=$nam;
 $_SESSION["ooq"]=$idf;
 $_SESSION["ses"]=$nam;
$s=$_SESSION["ses"];
$cookie_name = "user";
$cookie_value = $_POST['bp'];
setcookie($cookie_name, $cookie_value, time() + (86400 * 360), "/"); 


$cookie_name = "oop";
$cookie_value =$nam;
setcookie($cookie_name, $cookie_value, time() + (86400 * 360), "/"); 

$cookie_name = "ooq";
$cookie_value = $idf;
setcookie($cookie_name, $cookie_value, time() + (86400 * 360), "/"); 

?>



<!DOCTYPE html>
<html lang="en">
<head>

<style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-title {
            color: #1976D2;
            font-weight: 600;
            margin-bottom: 25px;
            text-align: center;
            font-size: 24px;
            border-bottom: 2px solid #e3f2fd;
            padding-bottom: 15px;
        }
        
        .form-group-enhanced {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-label-enhanced {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1565C0;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control-enhanced {
            display: block;
            width: 100%;
            padding: 14px 16px;
            font-size: 16px;
            font-weight: 500;
            line-height: 1.5;
            color: #1976D2;
            background-color: #fff;
            background-clip: padding-box;
            border: 2px solid #BBDEFB;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.03);
        }
        
        .form-control-enhanced:focus {
            color: #0D47A1;
            background-color: #F5FBFF;
            border-color: #64B5F6;
            outline: 0;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.15), inset 0 1px 2px rgba(0, 0, 0, 0.03);
        }
        
        .form-control-enhanced::placeholder {
            color: #90CAF9;
            font-weight: 400;
        }
        
        .input-icon {
            position: absolute;
            right: 15px;
            top: 40px;
            color: #64B5F6;
            font-size: 18px;
        }
        
        .helper-text {
            display: block;
            margin-top: 6px;
            font-size: 13px;
            color: #546E7A;
            font-style: italic;
        }
        
        .input-highlight {
            display: inline-block;
            background-color: #E3F2FD;
            color: #1976D2;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 5px;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(33, 150, 243, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(33, 150, 243, 0); }
            100% { box-shadow: 0 0 0 0 rgba(33, 150, 243, 0); }
        }
        
        .form-control-enhanced:focus {
            animation: pulse 1.5s infinite;
        }
        
        @media (max-width: 576px) {
            .form-container {
                padding: 20px 15px;
            }
            
            .form-control-enhanced {
                padding: 12px 14px;
            }
        }
</style>

<style>
        .save-status {
            padding: 8px 12px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 14px;
            display: none;
            text-align: center;
        }
        
        .save-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .save-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .save-button {
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 10px;
            width: 100%;
        }
        
        .save-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .save-button:active {
            transform: translateY(0);
        }
        
        .autosave-indicator {
            font-size: 12px;
            color: #6b7280;
            text-align: right;
            margin-top: 5px;
            display: none;
        }
</style>

<!-- Delete All Clients Modal Styles -->
<style>
.delete-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(5px);
    z-index: 9999;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.delete-modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 16px;
    padding: 0;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        transform: translate(-50%, -60%);
        opacity: 0;
    }
    to {
        transform: translate(-50%, -50%);
        opacity: 1;
    }
}

.delete-modal-header {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    padding: 25px 30px;
    border-radius: 16px 16px 0 0;
    text-align: center;
}

.delete-modal-header h2 {
    margin: 0;
    font-size: 28px;
    font-weight: 700;
}

.delete-modal-header .warning-icon {
    font-size: 48px;
    margin-bottom: 10px;
    animation: shake 0.5s ease;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}

.delete-modal-body {
    padding: 30px;
    text-align: center;
}

.delete-modal-body p {
    font-size: 18px;
    color: #495057;
    margin: 0 0 20px 0;
    font-weight: 500;
}

.delete-warning-text {
    background: #fff3cd;
    border: 2px solid #ffc107;
    border-radius: 8px;
    padding: 15px;
    margin: 20px 0;
    color: #856404;
    font-weight: 600;
}

.delete-modal-footer {
    padding: 20px 30px;
    background: #f8f9fa;
    border-radius: 0 0 16px 16px;
    display: flex;
    gap: 15px;
    justify-content: center;
}

.modal-btn {
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 120px;
}

.modal-btn-cancel {
    background: #6c757d;
    color: white;
}

.modal-btn-cancel:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4);
}

.modal-btn-delete {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
}

.modal-btn-delete:hover {
    background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
}

.modal-btn-delete:active {
    transform: translateY(0);
}

.delete-success-message {
    display: none;
    background: #d4edda;
    color: #155724;
    border: 2px solid #c3e6cb;
    border-radius: 8px;
    padding: 15px;
    margin: 20px 30px;
    text-align: center;
    font-weight: 600;
}

.delete-error-message {
    display: none;
    background: #f8d7da;
    color: #721c24;
    border: 2px solid #f5c6cb;
    border-radius: 8px;
    padding: 15px;
    margin: 20px 30px;
    text-align: center;
    font-weight: 600;
}
</style>

<link rel="stylesheet" href="css/stylei.css">
<link rel="stylesheet" href="css/stylei2.css">

<input type="hidden" id="demo" value="<?php echo $nam ?>"></input>
<input type="hidden" id="demo1" value="<?php echo $idf ?>"></input>
<input type="hidden" id="demo2" value="<?php echo $inc ?>"></input>
<input type="hidden" id="demo3" value="<?php echo $contact ?>"></input>

<script>
const global = document.getElementById("demo").value;
const global1 = document.getElementById("demo1").value;
const global2 = document.getElementById("demo2").value;
const global3 = document.getElementById("demo3").value;
</script>

<style>
/* Footer spacing fix */
html, body {
    margin: 0;
    padding: 0;
    height: 100%;
}

footer.container-fluid {
    margin: 0 !important;
    padding: 20px 0 0 0 !important;
    background: #343a40;
    color: white;
}

footer.container-fluid p {
    margin: 5px 0;
    color: white !important;
}
</style>

<script type="text/javascript" src="js/test371.js"></script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

</head>
<body onload="on() ">
  
<?php
include 'navbar.php';
?>

<div style="margin-bottom: 0; padding-bottom: 0;">
    <table style="background:#f8f8f8; margin-bottom: 0;" class="table" id="comment_form">
        <tr>
            <th style="width:20%;background:lightgrey">
                <div class="form-container">
                    <div class="form-group-enhanced">
                        <label for="bp" class="form-label-enhanced">
                            <i class="fas fa-phone"></i> INCOMING CALL
                        </label>
                        <input type="text" class="form-control-enhanced" id="bp" placeholder="" name="bp">
                        <span class="input-icon"><i class="fas fa-phone-volume"></i></span>
                    </div>
                    
                     <div class="form-group-enhanced">
                        <label for="ap" class="form-label-enhanced">
                            <i class="fas fa-user"></i> CUSTOMER NAME
                        </label>
                        <input type="text" style="background: #f1f5f9;font-weight: bold; border: 2px solid #e3f2fd;color: #1976D2; border-radius: 8px; padding: 15px; font-size: 14px; line-height: 1.5;"  class="form-control-enhanced" id="ap" placeholder="" name="ap">
                        <span class="input-icon"><i class="fas fa-user-circle"></i></span>
                    </div>
                </div>
            </th>

             <td style="vertical-align: top; width: 45%;">
                <div style="margin: 10px;">
                    <label for="cp"  class="form-label-enhanced">
                        <i class="fas fa-comments"></i> Customer Details
                    </label>
                    <textarea style="background: #f1f5f9;font-weight: bold; border: 2px solid #e3f2fd;color: #1976D2; border-radius: 8px; padding: 15px; font-size: 14px; line-height: 1.5;" 
                              class="form-control" id="cp" rows="35" name="cp" 
                              placeholder=" Call details, customer concerns, resolution steps, follow-up actions..."></textarea>
                </div>
            </td>

            <td style="width:30%; vertical-align: top;">
                <div style="margin: 10px;">
                    <!-- Action Buttons -->
                    <div class="action-buttons" style="background: #f8f9fa; border-radius: 12px; padding: 15px; margin-bottom: 20px;">
                        <div class="d-grid gap-2">
                            <button class="btn btn-success btn-modern" style="width:100%; margin-bottom: 8px;" onclick="javascript:replace()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>

                            <button class="btn btn-primary btn-modern" style="width:100%; margin-bottom: 8px;" onclick="javascript:add110()">
                                <i class="fas fa-user-plus"></i> New Client
                            </button>

                            <button class="btn btn-warning btn-modern" style="width:100%; margin-bottom: 8px;" onclick="javascript:number22()">
                                <i class="fas fa-search"></i> Search Client
                            </button>
                            
                            <button class="btn btn-primary btn-modern" style="width:100%; margin-bottom: 8px;" onclick="javascript:add()">
                                <i class="fas fa-ticket-alt"></i> New Ticket
                            </button>
                            
                            <!-- Delete All Clients Button >
                            <button class="btn btn-danger btn-modern" style="width:100%; margin-bottom: 8px;" onclick="openDeleteAllModal()">
                                <i class="fas fa-trash-alt"></i> Delete All Clients
                            </button-->
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- Delete All Clients Modal -->
<div id="deleteAllModal" class="delete-modal-overlay">
    <div class="delete-modal-content">
        <div class="delete-modal-header">
            <div class="warning-icon">⚠️</div>
            <h2>Delete All Clients</h2>
        </div>
        
        <div class="delete-modal-body">
            <p>Are you sure you want to delete ALL client data?</p>
            <div class="delete-warning-text">
                <i class="fas fa-exclamation-triangle"></i> This action cannot be undone!<br>
                All client records will be permanently deleted.
            </div>
        </div>
        
        <div class="delete-success-message" id="deleteSuccessMsg">
            <i class="fas fa-check-circle"></i> All clients have been successfully deleted!
        </div>
        
        <div class="delete-error-message" id="deleteErrorMsg">
            <i class="fas fa-times-circle"></i> Error: Failed to delete clients. Please try again.
        </div>
        
        <div class="delete-modal-footer" id="deleteModalFooter">
            <button class="modal-btn modal-btn-cancel" onclick="closeDeleteAllModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button class="modal-btn modal-btn-delete" onclick="confirmDeleteAll()">
                <i class="fas fa-trash"></i> Delete All
            </button>
        </div>
    </div>
</div>

<script>
function openDeleteAllModal() {
    document.getElementById('deleteAllModal').style.display = 'block';
}

function closeDeleteAllModal() {
    const modal = document.getElementById('deleteAllModal');
    modal.style.animation = 'fadeOut 0.3s ease';
    setTimeout(() => {
        modal.style.display = 'none';
        modal.style.animation = '';
        // Reset messages
        document.getElementById('deleteSuccessMsg').style.display = 'none';
        document.getElementById('deleteErrorMsg').style.display = 'none';
        document.getElementById('deleteModalFooter').style.display = 'flex';
    }, 300);
}

function confirmDeleteAll() {
    const deleteBtn = document.querySelector('.modal-btn-delete');
    const originalText = deleteBtn.innerHTML;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
    deleteBtn.disabled = true;
    
    // Send AJAX request to delete all clients
    fetch('delete_all_clients.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=delete_all'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            document.getElementById('deleteSuccessMsg').style.display = 'block';
            document.getElementById('deleteModalFooter').style.display = 'none';
            
            // Close modal and refresh page after 2 seconds
            setTimeout(() => {
                closeDeleteAllModal();
                location.reload();
            }, 2000);
        } else {
            // Show error message
            document.getElementById('deleteErrorMsg').style.display = 'block';
            document.getElementById('deleteErrorMsg').innerHTML = 
                '<i class="fas fa-times-circle"></i> Error: ' + (data.message || 'Failed to delete clients');
            deleteBtn.innerHTML = originalText;
            deleteBtn.disabled = false;
        }
    })
    .catch(error => {
        document.getElementById('deleteErrorMsg').style.display = 'block';
        document.getElementById('deleteErrorMsg').innerHTML = 
            '<i class="fas fa-times-circle"></i> Network error: ' + error.message;
        deleteBtn.innerHTML = originalText;
        deleteBtn.disabled = false;
    });
}

// Close modal when clicking outside
document.getElementById('deleteAllModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteAllModal();
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php
// ── POS Purchase History ──────────────────────────────────────────────────
if (!empty($id)) {
    try {
        $cid = (int)$id;
        $pos_conn = mysqli_connect("192.168.1.101","root","1Sys9Admeen72","nccleb_test");
        if (!$pos_conn) throw new Exception("POS DB connection failed");
        mysqli_set_charset($pos_conn,'utf8mb4');

        // Check pos_sales table exists before querying
        $tbl_check = mysqli_query($pos_conn, "SHOW TABLES LIKE 'pos_sales'");
        if (!$tbl_check || mysqli_num_rows($tbl_check) === 0) throw new Exception("POS tables not installed");

        // Summary stats
        $pos_stats = mysqli_fetch_assoc(mysqli_query($pos_conn,
        "SELECT COUNT(*) as total_sales,
                SUM(final_total) as total_spent,
                SUM(discount) as total_saved,
                MAX(created_at) as last_visit
         FROM pos_sales WHERE client_id = $cid AND status != 'refunded'"
    ));

    // Sales list
    $pos_sales = mysqli_query($pos_conn,
        "SELECT s.*,
            (SELECT GROUP_CONCAT(product_name,' x',qty SEPARATOR ', ')
             FROM pos_sale_items WHERE sale_id=s.id) as items_summary,
            (SELECT COUNT(*) FROM pos_sale_items WHERE sale_id=s.id) as item_count
         FROM pos_sales s
         WHERE s.client_id = $cid
         ORDER BY s.created_at DESC"
    );
    $pos_rows = [];
    while ($r = mysqli_fetch_assoc($pos_sales)) $pos_rows[] = $r;

    mysqli_close($pos_conn);

    $pay_labels = [
        'cash'=>'Cash','card'=>'Card','omt'=>'OMT','whish'=>'Whish',
        'bank_transfer'=>'Bank Transfer','cheque'=>'Cheque','credit'=>'Credit'
    ];
?>
<div style="margin:20px 15px 0;">

    <!-- Section header -->
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
        <div style="background:linear-gradient(135deg,#1976D2,#0D47A1);color:white;padding:8px 18px;border-radius:8px;font-weight:700;font-size:14px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-shopping-bag"></i> POS Purchase History
        </div>
        <?php if (!empty($pos_rows)): ?>
        <a href="pos_sales.php?s=<?= $cid ?>" target="_blank"
           style="font-size:12px;color:#1976D2;text-decoration:none;font-weight:600;">
            <i class="fas fa-external-link-alt"></i> View in Sales
        </a>
        <?php endif; ?>
    </div>

    <?php if (empty($pos_rows)): ?>
    <div style="background:#f8fafc;border:2px dashed #e5e7eb;border-radius:10px;padding:24px;text-align:center;color:#9ca3af;">
        <i class="fas fa-shopping-cart" style="font-size:28px;display:block;margin-bottom:8px;"></i>
        No POS purchases found for this client.
    </div>

    <?php else: ?>

    <!-- Mini stats -->
    <div style="display:flex;gap:12px;margin-bottom:14px;flex-wrap:wrap;">
        <div style="background:white;border-radius:10px;padding:12px 18px;border-left:4px solid #10b981;box-shadow:0 1px 4px rgba(0,0,0,.07);flex:1;min-width:120px;">
            <div style="font-size:18px;font-weight:800;color:#10b981;">
                $<?= number_format($pos_stats['total_spent'] ?? 0, 2) ?>
            </div>
            <div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;">Total Spent</div>
        </div>
        <div style="background:white;border-radius:10px;padding:12px 18px;border-left:4px solid #1976D2;box-shadow:0 1px 4px rgba(0,0,0,.07);flex:1;min-width:120px;">
            <div style="font-size:18px;font-weight:800;color:#1976D2;">
                <?= $pos_stats['total_sales'] ?? 0 ?>
            </div>
            <div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;">Purchases</div>
        </div>
        <div style="background:white;border-radius:10px;padding:12px 18px;border-left:4px solid #f59e0b;box-shadow:0 1px 4px rgba(0,0,0,.07);flex:1;min-width:120px;">
            <div style="font-size:18px;font-weight:800;color:#f59e0b;">
                $<?= number_format($pos_stats['total_saved'] ?? 0, 2) ?>
            </div>
            <div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;">Saved (Discounts)</div>
        </div>
        <div style="background:white;border-radius:10px;padding:12px 18px;border-left:4px solid #7c3aed;box-shadow:0 1px 4px rgba(0,0,0,.07);flex:1;min-width:140px;">
            <div style="font-size:14px;font-weight:800;color:#7c3aed;">
                <?= $pos_stats['last_visit'] ? date('d M Y', strtotime($pos_stats['last_visit'])) : '—' ?>
            </div>
            <div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;">Last Visit</div>
        </div>
    </div>

    <!-- Sales table -->
    <div style="background:white;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,.08);overflow:hidden;margin-bottom:20px;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:10px 14px;text-align:left;border-bottom:2px solid #e5e7eb;color:#374151;font-weight:700;">#</th>
                        <th style="padding:10px 14px;text-align:left;border-bottom:2px solid #e5e7eb;color:#374151;font-weight:700;">Date</th>
                        <th style="padding:10px 14px;text-align:left;border-bottom:2px solid #e5e7eb;color:#374151;font-weight:700;">Items</th>
                        <th style="padding:10px 14px;text-align:left;border-bottom:2px solid #e5e7eb;color:#374151;font-weight:700;">Payment</th>
                        <th style="padding:10px 14px;text-align:left;border-bottom:2px solid #e5e7eb;color:#374151;font-weight:700;">Cashier</th>
                        <th style="padding:10px 14px;text-align:left;border-bottom:2px solid #e5e7eb;color:#374151;font-weight:700;">Discount</th>
                        <th style="padding:10px 14px;text-align:left;border-bottom:2px solid #e5e7eb;color:#374151;font-weight:700;">Total</th>
                        <th style="padding:10px 14px;text-align:left;border-bottom:2px solid #e5e7eb;color:#374151;font-weight:700;">Status</th>
                        <th style="padding:10px 14px;border-bottom:2px solid #e5e7eb;"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pos_rows as $i => $pr):
                    $sym = $pr['currency']==='LBP'?'LL ':($pr['currency']==='EUR'?'€':'$');
                    $bg  = $i % 2 === 0 ? 'white' : '#f8fafc';
                    $is_refunded = $pr['status'] === 'refunded';
                ?>
                <tr style="background:<?= $bg ?>;<?= $is_refunded ? 'opacity:.6;' : '' ?>">
                    <td style="padding:10px 14px;border-bottom:1px solid #f3f4f6;font-weight:700;color:#1976D2;">
                        #<?= $pr['id'] ?>
                    </td>
                    <td style="padding:10px 14px;border-bottom:1px solid #f3f4f6;white-space:nowrap;color:#4b5563;">
                        <?= date('d M Y', strtotime($pr['created_at'])) ?><br>
                        <span style="font-size:11px;color:#9ca3af;"><?= date('H:i', strtotime($pr['created_at'])) ?></span>
                    </td>
                    <td style="padding:10px 14px;border-bottom:1px solid #f3f4f6;color:#4b5563;max-width:220px;">
                        <div style="font-size:12px;color:#374151;line-height:1.5;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($pr['items_summary'] ?? '') ?>">
                            <?= htmlspecialchars($pr['items_summary'] ?? '—') ?>
                        </div>
                        <span style="font-size:11px;color:#9ca3af;"><?= $pr['item_count'] ?> item<?= $pr['item_count']!=1?'s':'' ?></span>
                    </td>
                    <td style="padding:10px 14px;border-bottom:1px solid #f3f4f6;">
                        <span style="display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:#dbeafe;color:#1e40af;">
                            <?= $pay_labels[$pr['payment_method']] ?? ucfirst($pr['payment_method']) ?>
                        </span>
                        <div style="font-size:11px;color:#9ca3af;margin-top:2px;"><?= $pr['currency'] ?></div>
                    </td>
                    <td style="padding:10px 14px;border-bottom:1px solid #f3f4f6;font-size:12px;color:#6b7280;">
                        <?= htmlspecialchars($pr['agent_name']) ?>
                    </td>
                    <td style="padding:10px 14px;border-bottom:1px solid #f3f4f6;font-size:13px;color:#f59e0b;font-weight:700;">
                        <?= $pr['discount'] > 0 ? '-'.$sym.number_format($pr['discount'],2) : '—' ?>
                    </td>
                    <td style="padding:10px 14px;border-bottom:1px solid #f3f4f6;font-weight:800;font-size:14px;color:#1a1a2e;">
                        <?= $sym ?><?= number_format($pr['final_total'],2) ?>
                    </td>
                    <td style="padding:10px 14px;border-bottom:1px solid #f3f4f6;">
                        <?php if ($is_refunded): ?>
                        <span style="display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:#fee2e2;color:#991b1b;">Refunded</span>
                        <?php else: ?>
                        <span style="display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:#d1fae5;color:#065f46;">Completed</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:10px 14px;border-bottom:1px solid #f3f4f6;">
                        <a href="pos_print.php?id=<?= $pr['id'] ?>" target="_blank"
                           style="display:inline-flex;align-items:center;gap:5px;padding:5px 10px;background:#eff6ff;color:#1976D2;border-radius:6px;font-size:12px;font-weight:700;text-decoration:none;">
                            <i class="fas fa-print"></i> Receipt
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php endif; ?>
</div>
<?php
    } catch (Exception $e) {
        // POS section failed silently — CRM continues working normally
    }
} // end if $id ?>

<?php include 'footer.php';?>

</body>
</html>