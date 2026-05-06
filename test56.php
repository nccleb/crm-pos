<?php
// UTF-8 Fix for Arabic
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

session_start();
// if (!isset($_SESSION["oop"])) {
//     header("Location: login200.php");
//     exit();
// }

// Get agent ID from GET parameter or session
if(isset($_GET['page']) && !empty($_GET['page'])){
    $agent_id = $_GET['page'];
    $_SESSION["ses"] = $agent_id;
}

error_log("test56.php - Agent ID: " . $agent_id . " | From GET page: " . ($_GET['page'] ?? 'none') . " | From Session: " . ($_SESSION["ses"] ?? 'none'));

date_default_timezone_set('Asia/Beirut');

// Database connection
$idr = new mysqli("172.18.208.1", "root", "1Sys9Admeen72", "nccleb_test");
if ($idr->connect_error) {
    die("Database connection failed: " . $idr->connect_error);
}
$idr->set_charset("utf8mb4");

// Get current agent name
$agent_result = $idr->query("SELECT idf FROM form_element WHERE name='$agent_id'");
if($agent_row = $agent_result->fetch_assoc()){
    $agent_id = $agent_row['idf'];
}

// Fetch complaints
$complaints = $idr->query("SELECT comment_text FROM comments ORDER BY id_co ASC");

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?? '';
    $ticket_name = $_POST['ticket_name'] ?? '';
    $last_activity = $_POST['last_activity'] ?? '';
    $complaint = $_POST['complaint'] ?? '';
    $priority = $_POST['priority'] ?? '';
    $status = $_POST['status'] ?? '';
    $lcd = date('Y-m-d H:i:s');
    
    // If "other" is selected, use the custom complaint text
    if ($complaint === 'other' && isset($_POST['other_complaint']) && !empty(trim($_POST['other_complaint']))) {
        $complaint = trim($_POST['other_complaint']);
    }
    
    // Auto-assign to current logged-in agent
    $assigned_agent = $agent_id;
    
    error_log("Creating ticket - Agent ID: " . $assigned_agent . " | Original agent_id: " . $agent_id);

    // Validation
    if (!$client_id || !$ticket_name || !$last_activity || !$complaint || !$priority || !$status) {
        $error = "Please fill all required fields.";
    } elseif ($complaint === 'other') {
        $error = "Please specify the complaint when selecting 'Other'.";
    } else {
        $stmt = $idr->prepare("INSERT INTO crm (id, task, la, incident, priority, status, idfc, lcd) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssis", $client_id, $ticket_name, $last_activity, $complaint, $priority, $status, $assigned_agent, $lcd);

        if ($stmt->execute()) {
            $success = "Ticket created successfully and assigned to you!";
            echo "<script>
                alert('$success');
                if(window.opener){window.opener.location.reload(); window.close();} 
                else {window.location = 'tickets.php';}
            </script>";
            exit();
        } else {
            $error = "Error creating ticket: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Ticket</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{
--primary:#4f46e5; --danger:#ef4444; --warning:#f59e0b; --success:#10b981;
--light:#f9fafb; --dark:#1f2937; --border:#e5e7eb;
}
body{background:var(--light);font-family:Inter,sans-serif;margin:0;padding:20px;}
.container{max-width:800px;margin:auto;background:white;border-radius:16px;box-shadow:0 4px 6px rgba(0,0,0,0.1);overflow:hidden;}
.header{background:linear-gradient(135deg,var(--primary),#6366f1);color:white;text-align:center;padding:32px;}
.header h1{margin:0 0 8px;font-size:28px;}
.header p{margin:0;opacity:0.9;}
.body{padding:32px;}
.section{margin-bottom:32px;}
.section-title{font-size:18px;font-weight:700;color:var(--dark);margin-bottom:16px;padding-bottom:8px;border-bottom:2px solid var(--border);}
.form-group{margin-bottom:20px;}
label{display:block;font-weight:600;margin-bottom:8px;}
label .required{color:var(--danger);}
input.form-control,textarea.form-control,select.form-control{width:100%;padding:12px 16px;border:2px solid var(--border);border-radius:8px;font-size:14px;transition:all 0.2s;font-family:inherit;}
input.form-control:focus,textarea.form-control:focus,select.form-control:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 4px rgba(79,70,229,0.1);}
textarea.form-control{min-height:100px;resize:vertical;}
.priority-selector{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;}
.priority-option{padding:16px;border:2px solid var(--border);border-radius:8px;text-align:center;cursor:pointer;transition:all 0.2s;}
.priority-option.selected{box-shadow:0 4px 6px rgba(0,0,0,0.1);}
.priority-option.high{border-color:var(--danger);}
.priority-option.high.selected{background:#fee2e2;border-color:var(--danger);}
.priority-option.medium{border-color:var(--warning);}
.priority-option.medium.selected{background:#fef3c7;border-color:var(--warning);}
.priority-option.low{border-color:#3b82f6;}
.priority-option.low.selected{background:#dbeafe;border-color:#3b82f6;}
.radio-group{display:flex;flex-direction:column;gap:12px;}
.radio-option{display:flex;align-items:center;padding:12px;border:2px solid var(--border);border-radius:8px;cursor:pointer;transition:all 0.2s;}
.radio-option.selected{border-color:var(--primary);background:#eff6ff;}
.radio-option input[type="radio"]{margin-right:12px;width:20px;height:20px;}
.form-actions{display:flex;gap:12px;justify-content:flex-end;padding-top:24px;border-top:2px solid var(--border);}
.btn{padding:12px 24px;border:none;border-radius:8px;font-weight:600;cursor:pointer;transition:all 0.2s;display:inline-flex;align-items:center;gap:8px;}
.btn-primary{background:var(--primary);color:white;}
.btn-primary:hover{background:#4338ca;transform:translateY(-1px);box-shadow:0 4px 6px rgba(79,70,229,0.3);}
.btn-secondary{background:#6b7280;color:white;}
.btn-secondary:hover{background:#4b5563;}
.error-message{background:#fee2e2;color:#dc2626;padding:12px 16px;border-radius:8px;margin-bottom:20px;border-left:4px solid #dc2626;}
.info-box{background:linear-gradient(135deg,#dbeafe,#e0f2fe);border:2px solid #3b82f6;border-radius:12px;padding:16px;margin-bottom:24px;display:flex;align-items:center;gap:12px;}
.info-box i{font-size:24px;color:#2563eb;}
.info-box-content{flex:1;}
.info-box-content strong{display:block;color:#1e40af;font-size:16px;margin-bottom:4px;}
.info-box-content small{color:#475569;}
</style>
</head>
<body>
<div class="container">
<div class="header">
<h1><i class="fas fa-ticket-alt"></i> Create Ticket</h1>
<p>Fill the details to create a support ticket</p>
</div>
<div class="body">
<?php if($error): ?><div class="error-message"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<form method="POST" id="ticketForm">
<div class="section">
<div class="section-title"><i class="fas fa-user"></i> Client</div>
<div class="form-group">
<label>Search Client <span class="required">*</span></label>
<input type="text" id="clientSearch" class="form-control" placeholder="Type client name or number" autocomplete="off">
<input type="hidden" name="client_id" id="clientId" required>
<div id="clientSuggestions" style="position:absolute;background:white;border:1px solid #ddd;max-height:200px;overflow-y:auto;display:none;z-index:1000;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.1);"></div>
</div>
</div>

<div class="section">
<div class="section-title"><i class="fas fa-file-alt"></i> Ticket Details</div>
<div class="form-group">
<label>Ticket Name <span class="required">*</span></label>
<input type="text" name="ticket_name" class="form-control" placeholder="e.g., Internet Connection Issue" required>
</div>
<div class="form-group">
<label>Last Action <span class="required">*</span></label>
<textarea name="last_activity" class="form-control" placeholder="Describe the last action taken or current status..." required></textarea>
</div>
<div class="form-group">
<label>Complaint <span class="required">*</span></label>
<select name="complaint" id="complaintSelect" class="form-control" required>
<option value="">Select complaint</option>
<?php 
$complaints->data_seek(0); // Reset pointer
while($c=$complaints->fetch_assoc()): 
?>
<option value="<?= htmlspecialchars($c['comment_text']) ?>"><?= htmlspecialchars($c['comment_text']) ?></option>
<?php endwhile; ?>
<option value="other">Other</option>
</select>
</div>
<div class="form-group" id="otherComplaintGroup" style="display:none;">
<label>Specify Other Complaint <span class="required">*</span></label>
<input type="text" id="otherComplaint" name="other_complaint" class="form-control" placeholder="Enter custom complaint description">
</div>
</div>

<div class="section">
<div class="section-title"><i class="fas fa-tasks"></i> Priority & Status</div>
<div class="form-group">
<label>Priority <span class="required">*</span></label>
<div class="priority-selector">
<label class="priority-option low"><input type="radio" name="priority" value="Low" required><strong>Low</strong></label>
<label class="priority-option medium"><input type="radio" name="priority" value="Medium" checked required><strong>Medium</strong></label>
<label class="priority-option high"><input type="radio" name="priority" value="High" required><strong>High</strong></label>
</div>
</div>

<div class="form-group">
<label>Status <span class="required">*</span></label>
<div class="radio-group">
<label class="radio-option"><input type="radio" name="status" value="Not Resolved" checked>Not Resolved</label>
<label class="radio-option"><input type="radio" name="status" value="In Progress">In Progress</label>
<label class="radio-option"><input type="radio" name="status" value="Resolved">Resolved</label>
</div>
</div>
</div>

<div class="form-actions">
<button type="button" class="btn btn-secondary" onclick="window.close()"><i class="fas fa-times"></i> Cancel</button>
<button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Create Ticket</button>
</div>
</form>
</div>
</div>

<script>
// Client search
let searchTimeout;
const searchInput = document.getElementById('clientSearch');
const suggestions = document.getElementById('clientSuggestions');
const clientIdInput = document.getElementById('clientId');

searchInput.addEventListener('input', function(){
    clearTimeout(searchTimeout);
    const term = this.value.trim();
    if(term.length<2){ suggestions.style.display='none'; return;}
    searchTimeout = setTimeout(()=>{
        fetch('ajax/search_clients.php?q='+encodeURIComponent(term))
        .then(res=>res.json())
        .then(data=>{
            if(data.length===0){ 
                suggestions.innerHTML='<div style="padding:12px;color:#6b7280;">No clients found</div>'; 
            }
            else{
                suggestions.innerHTML = data.map(c=>
                    `<div style="padding:12px;cursor:pointer;border-bottom:1px solid #e5e7eb;transition:background 0.2s;" 
                     onmouseover="this.style.background='#f3f4f6'" 
                     onmouseout="this.style.background='white'"
                     onclick="selectClient(${c.id},'${c.name.replace(/'/g, "\\'")}')">
                        <strong>${c.name}</strong><br>
                        <small style="color:#6b7280;">${c.number}</small>
                     </div>`
                ).join('');
            }
            suggestions.style.display='block';
        }).catch(console.error);
    },300);
});

// Close suggestions when clicking outside
document.addEventListener('click', function(e){
    if(!searchInput.contains(e.target) && !suggestions.contains(e.target)){
        suggestions.style.display='none';
    }
});

function selectClient(id,name){
    clientIdInput.value=id;
    searchInput.value=name;
    suggestions.style.display='none';
}

// Complaint select - show/hide "Other" field
const complaintSelect = document.getElementById('complaintSelect');
const otherGroup = document.getElementById('otherComplaintGroup');
const otherInput = document.getElementById('otherComplaint');

complaintSelect.addEventListener('change',function(){
    if(this.value==='other'){
        otherGroup.style.display='block'; 
        otherInput.required=true;
    }
    else{
        otherGroup.style.display='none'; 
        otherInput.required=false;
        otherInput.value = ''; // Clear the field when hiding
    }
});

// Form validation before submit
document.getElementById('ticketForm').addEventListener('submit', function(e){
    // If "other" is selected, make sure they entered something
    if(complaintSelect.value === 'other'){
        if(!otherInput.value.trim()){
            e.preventDefault();
            alert('Please enter a complaint description when "Other" is selected.');
            otherInput.focus();
            return false;
        }
    }
    
    // If complaint is not selected at all
    if(!complaintSelect.value){
        e.preventDefault();
        alert('Please select a complaint type.');
        complaintSelect.focus();
        return false;
    }
});

// Priority & Status styling
document.querySelectorAll('.priority-option input').forEach(input=>{
    input.addEventListener('change',()=>{
        document.querySelectorAll('.priority-option').forEach(o=>o.classList.remove('selected')); 
        input.closest('.priority-option').classList.add('selected');
    });
});

document.querySelectorAll('.radio-option input').forEach(input=>{
    input.addEventListener('change',()=>{
        document.querySelectorAll('.radio-option').forEach(o=>o.classList.remove('selected')); 
        input.closest('.radio-option').classList.add('selected');
    });
});

// Set initial selected states
document.querySelector('.priority-option input:checked')?.closest('.priority-option').classList.add('selected');
document.querySelector('.radio-option input:checked')?.closest('.radio-option').classList.add('selected');
</script>
</body>
</html>