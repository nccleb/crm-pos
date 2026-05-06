<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Get agent ID from GET parameter or session
// The agent ID comes from the 'page' parameter
if(isset($_GET['page']) && !empty($_GET['page'])){
  $current_agent_id = $_GET['page'];
    
}
//else{
//$current_agent_id = isset($_SESSION["ses"]) ? intval($_SESSION["ses"]) : 1;
//}
// Failsafe
// if($current_agent_id == 0){
//     $current_agent_id = 1;
// }

// DEBUG
//error_log("tickets.php - Current Agent ID: " . $current_agent_id);

// Database connection
$host="172.18.208.1";
$user="root";
$pass="1Sys9Admeen72";
$db="nccleb_test";
$conn=mysqli_connect($host,$user,$pass,$db);
if(!$conn){ die("DB connection failed"); }

// Fetch agents
$agents = [];
$res = mysqli_query($conn, "SELECT idf,name FROM form_element");
while($row=mysqli_fetch_assoc($res)){ $agents[$row['idf']]=$row['name']; }

// Fetch clients
$clients = [];
$res = mysqli_query($conn, "SELECT * FROM client");
while($row=mysqli_fetch_assoc($res)){
    $clients[$row['id']] = $row;
}

// Handle CSV Export
if(isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filter_status = $_GET['status'] ?? '';
    $filter_priority = $_GET['priority'] ?? '';
    $filter_date_from = $_GET['from'] ?? '';
    $filter_date_to = $_GET['to'] ?? '';

    $sql = "SELECT crm.*, COALESCE(form_element.name, 'Unassigned') as agent_name FROM crm 
            LEFT JOIN form_element ON crm.idfc = form_element.idf WHERE 1";

    if($filter_status) $sql .= " AND crm.status='".mysqli_real_escape_string($conn,$filter_status)."'";
    if($filter_priority) $sql .= " AND crm.priority='".mysqli_real_escape_string($conn,$filter_priority)."'";
    if($filter_date_from) {
        $from_formatted = str_replace('T', ' ', $filter_date_from) . ':00';
        $sql .= " AND crm.lcd >= '".mysqli_real_escape_string($conn,$from_formatted)."'";
    }
    if($filter_date_to) {
        $to_formatted = str_replace('T', ' ', $filter_date_to) . ':00';
        $sql .= " AND crm.lcd <= '".mysqli_real_escape_string($conn,$to_formatted)."'";
    }

    $sql .= " ORDER BY crm.lcd DESC";
    $result = mysqli_query($conn,$sql);
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=tickets_export_'.date('Y-m-d_His').'.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8 Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add CSV headers
    fputcsv($output, [
        'Ticket ID',
        'Task',
        'Client Name',
        'Contact Number',
        'Email',
        'Company',
        'Complaint/Incident',
        'Priority',
        'Status',
        'Agent',
        'Last Activity',
        'Created Date',
        'City',
        'Address'
    ]);
    
    // Add data rows
    while($row = mysqli_fetch_assoc($result)) {
        $client = $clients[$row['id']] ?? null;
        $client_name = isset($client['nom'],$client['prenom']) ? $client['nom'].' '.$client['prenom'] : 'Unknown';
        $contact = $client['number'] ?? 'Unknown';
        $email = $client['email'] ?? '';
        $company = $client['company'] ?? '';
        $city = $client['city'] ?? '';
        $address = $client['address'] ?? '';
        
        fputcsv($output, [
            $row['idc'],
            $row['task'],
            $client_name,
            $contact,
            $email,
            $company,
            $row['incident'],
            $row['priority'],
            $row['status'],
            $row['agent_name'],
            $row['la'],
            $row['lcd'],
            $city,
            $address
        ]);
    }
    
    fclose($output);
    exit;
}

// Fetch tickets
$filter_status = $_GET['status'] ?? '';
$filter_priority = $_GET['priority'] ?? '';
$filter_date_from = $_GET['from'] ?? '';
$filter_date_to = $_GET['to'] ?? '';

$sql = "SELECT crm.*, COALESCE(form_element.name, 'Unassigned') as agent_name FROM crm 
        LEFT JOIN form_element ON crm.idfc = form_element.idf WHERE 1";

if($filter_status) $sql .= " AND crm.status='".mysqli_real_escape_string($conn,$filter_status)."'";
if($filter_priority) $sql .= " AND crm.priority='".mysqli_real_escape_string($conn,$filter_priority)."'";
if($filter_date_from) {
    $from_formatted = str_replace('T', ' ', $filter_date_from) . ':00';
    $sql .= " AND crm.lcd >= '".mysqli_real_escape_string($conn,$from_formatted)."'";
}
if($filter_date_to) {
    $to_formatted = str_replace('T', ' ', $filter_date_to) . ':00';
    $sql .= " AND crm.lcd <= '".mysqli_real_escape_string($conn,$to_formatted)."'";
}

$sql .= " ORDER BY crm.lcd DESC";
$result = mysqli_query($conn,$sql);
$tickets = [];
while($row=mysqli_fetch_assoc($result)){
    $client = $clients[$row['id']] ?? null;
    $row['contact'] = $client['number'] ?? 'Unknown';
    $row['client_name'] = isset($client['nom'],$client['prenom']) ? $client['nom'].' '.$client['prenom'] : 'Unknown';
    $tickets[] = $row;
}

// Debug: Let's see what we got
// error_log("Tickets fetched: " . print_r($tickets, true));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ticket Management System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body{background:#f8f9fa;font-family:sans-serif;}
.stat-card{padding:20px;border-radius:10px;color:white;margin-bottom:15px;text-align:center;}
.stat-total{background:linear-gradient(135deg,#667eea,#764ba2);}
.stat-notresolved{background:linear-gradient(135deg,#e74c3c,#c0392b);}
.stat-inprogress{background:linear-gradient(135deg,#f39c12,#e67e22);}
.stat-resolved{background:linear-gradient(135deg,#27ae60,#229954);}
.ticket-item{padding:15px;margin-bottom:10px;border-radius:8px;background:white;transition:all 0.3s ease;}
.ticket-item.low{border-left:4px solid #28a745;background:#f0fff4;}
.ticket-item.medium{border-left:4px solid #ffc107;background:#fff9f0;}
.ticket-item.high{border-left:4px solid #dc3545;background:#fff5f5;}
.ticket-item:hover{transform:translateX(5px);box-shadow:0 4px 12px rgba(0,0,0,0.1);}
.pagination{justify-content:center;}
.btn-export{
    background:linear-gradient(135deg,#28a745,#20c997);
    color:white;
    border:none;
    transition:all 0.3s ease;
}
.btn-export:hover{
    background:linear-gradient(135deg,#20c997,#28a745);
    transform:translateY(-2px);
    box-shadow:0 4px 12px rgba(40,167,69,0.3);
}
.btn-success{
    transition:all 0.3s ease;
}
.btn-success:hover{
    transform:translateY(-2px);
    box-shadow:0 4px 12px rgba(40,167,69,0.3);
}
@media print {
    body * {
        visibility: hidden;
    }
    .print-ticket, .print-ticket * {
        visibility: visible;
    }
    .print-ticket {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>
</head>
<body>
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Ticket Management</h2>
        <div>
            <button class="btn btn-success me-2" onclick="window.open('test56.php?page=<?php echo $current_agent_id; ?>', '_blank', 'width=900,height=800')">
                <i class="fas fa-plus"></i> Create New Ticket
            </button>
            <button class="btn btn-primary me-2" onclick="window.location.reload()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <button class="btn btn-export me-2" onclick="exportToCSV()">
                <i class="fas fa-download"></i> Export to CSV
            </button>
            <button class="btn btn-danger" onclick="window.history.back();">Quit</button>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3 stat-card stat-total">
            <h3><?php echo count($tickets);?></h3><p>Total Tickets</p>
        </div>
        <div class="col-md-3 stat-card stat-notresolved">
            <h3><?php echo count(array_filter($tickets,function($t){return $t['status']=='Not Resolved';}));?></h3><p>Not Resolved</p>
        </div>
        <div class="col-md-3 stat-card stat-inprogress">
            <h3><?php echo count(array_filter($tickets,function($t){return $t['status']=='In Progress';}));?></h3><p>In Progress</p>
        </div>
        <div class="col-md-3 stat-card stat-resolved">
            <h3><?php echo count(array_filter($tickets,function($t){return $t['status']=='Resolved';}));?></h3><p>Resolved</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-md-2">
            <select class="form-select" id="filterStatus">
                <option value="">All Status</option>
                <option value="Not Resolved">Not Resolved</option>
                <option value="In Progress">In Progress</option>
                <option value="Resolved">Resolved</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" id="filterPriority">
                <option value="">All Priorities</option>
                <option value="Low">Low</option>
                <option value="Medium">Medium</option>
                <option value="High">High</option>
            </select>
        </div>
        <div class="col-md-3"><input type="datetime-local" id="filterFrom" class="form-control" placeholder="From"></div>
        <div class="col-md-3"><input type="datetime-local" id="filterTo" class="form-control" placeholder="To"></div>
        <div class="col-md-2"><input type="text" id="searchInput" class="form-control" placeholder="Search..."></div>
    </div>

    <!-- Tickets List -->
    <div id="ticketsList"></div>

    <!-- Pagination -->
    <nav>
      <ul class="pagination mt-3" id="pagination"></ul>
    </nav>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editForm">
        <div class="modal-header">
          <h5 class="modal-title">Edit Ticket</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="idc" id="editId">
          <div class="mb-2">
            <label>Ticket</label>
            <input type="text" name="task" id="editTask" class="form-control" required>
          </div>
          <div class="mb-2">
            <label>Complaint</label>
            <textarea name="incident" id="editComplaint" class="form-control" rows="3" required></textarea>
          </div>
          <div class="mb-2">
            <label>Priority</label>
            <select name="priority" id="editPriority" class="form-select">
              <option value="Low">Low</option>
              <option value="Medium">Medium</option>
              <option value="High">High</option>
            </select>
          </div>
          <div class="mb-2">
            <label>Status</label>
            <select name="status" id="editStatus" class="form-select">
              <option value="Not Resolved">Not Resolved</option>
              <option value="In Progress">In Progress</option>
              <option value="Resolved">Resolved</option>
            </select>
          </div>
          <div class="mb-2">
            <label>Agent</label>
            <select name="agent" id="editAgent" class="form-select">
              <?php foreach($agents as $id=>$name){ echo "<option value='$id'>$name</option>"; } ?>
            </select>
          </div>
          <div class="mb-2">
            <label>Last Activity</label>
            <textarea name="la" id="editLA" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const tickets = <?php echo json_encode($tickets); ?>;
console.log('Tickets loaded:', tickets); // Debug: check what data we got
let filteredTickets = [...tickets];
let ticketsPerPage = 10;
let currentPage = 1;

// Export to CSV function
function exportToCSV() {
    const status = document.getElementById('filterStatus').value;
    const priority = document.getElementById('filterPriority').value;
    const from = document.getElementById('filterFrom').value;
    const to = document.getElementById('filterTo').value;
    
    let url = 'tickets.php?export=csv';
    if(status) url += '&status=' + encodeURIComponent(status);
    if(priority) url += '&priority=' + encodeURIComponent(priority);
    if(from) url += '&from=' + encodeURIComponent(from);
    if(to) url += '&to=' + encodeURIComponent(to);
    
    window.location.href = url;
}

// Render tickets
function renderTickets(){
    const list = document.getElementById('ticketsList');
    list.innerHTML = '';
    const start = (currentPage-1)*ticketsPerPage;
    const end = start + ticketsPerPage;
    filteredTickets.slice(start,end).forEach(ticket=>{
        const div = document.createElement('div');
        div.className = `ticket-item ${ticket.priority ? ticket.priority.toLowerCase() : 'medium'}`;
        div.dataset.id = ticket.idc;
        div.dataset.task = ticket.task || '';
        div.dataset.complaint = ticket.incident || '';
        div.dataset.priority = ticket.priority || 'Medium';
        div.dataset.status = ticket.status || 'Not Resolved';
        div.dataset.agent = ticket.idfc || '';
        div.dataset.la = ticket.la || '';
        
        // Use the agent_name from database, don't override with fallback
        const agentName = ticket.agent_name && ticket.agent_name !== 'null' ? ticket.agent_name : 'Unassigned';
        const priorityClass = ticket.priority=='High'?'danger':(ticket.priority=='Medium'?'warning':'success');
        const statusClass = ticket.status=='Resolved'?'success':(ticket.status=='In Progress'?'warning':'danger');
        
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5>${ticket.task || 'Untitled Ticket'}</h5>
                    <p class="mb-1"><strong>Client:</strong> ${ticket.client_name || 'Unknown'}</p>
                    <p class="mb-1"><strong>Contact:</strong> ${ticket.contact || 'Unknown'}</p>
                    <p class="mb-1"><strong>Complaint:</strong> ${ticket.incident || 'N/A'}</p>
                    <p class="mb-1"><strong>Last Activity:</strong> ${ticket.la || 'N/A'}</p>
                    <p class="mb-0"><strong>Created:</strong> ${ticket.lcd || 'N/A'}</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-${priorityClass}">${ticket.priority || 'Medium'}</span><br>
                    <span class="badge bg-${statusClass}">${ticket.status || 'Not Resolved'}</span><br>
                    <span class="badge bg-secondary">${agentName}</span><br>
                    <button class="btn btn-sm btn-info mt-1 printBtn" data-id="${ticket.idc}"><i class="fas fa-print"></i> Print</button>
                    <button class="btn btn-sm btn-primary mt-1 editBtn" data-id="${ticket.idc}">Edit</button>
                    <button class="btn btn-sm btn-danger mt-1 deleteBtn" data-id="${ticket.idc}">Delete</button>
                </div>
            </div>
        `;
        list.appendChild(div);
    });
    renderPagination();
}

// Pagination
function renderPagination(){
    const totalPages = Math.ceil(filteredTickets.length / ticketsPerPage);
    const pag = document.getElementById('pagination');
    pag.innerHTML = '';

    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage===1?'disabled':''}`;
    prevLi.innerHTML = `<a class="page-link" href="#">Previous</a>`;
    prevLi.addEventListener('click',e=>{e.preventDefault(); if(currentPage>1){currentPage--;renderTickets();}});
    pag.appendChild(prevLi);

    for(let i=1;i<=totalPages;i++){
        const li = document.createElement('li');
        li.className = `page-item ${i===currentPage?'active':''}`;
        li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
        li.addEventListener('click',e=>{e.preventDefault(); currentPage=i; renderTickets();});
        pag.appendChild(li);
    }

    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPage===totalPages?'disabled':''}`;
    nextLi.innerHTML = `<a class="page-link" href="#">Next</a>`;
    nextLi.addEventListener('click',e=>{e.preventDefault(); if(currentPage<totalPages){currentPage++;renderTickets();}});
    pag.appendChild(nextLi);
}

// Filters & Search
function applyFilters(){
    const status=document.getElementById('filterStatus').value;
    const priority=document.getElementById('filterPriority').value;
    const from=document.getElementById('filterFrom').value;
    const to=document.getElementById('filterTo').value;
    const search=document.getElementById('searchInput').value.toLowerCase();

    filteredTickets = tickets.filter(t=>{
        let ok = true;
        if(status && t.status !== status) ok = false;
        if(priority && t.priority !== priority) ok = false;
        
        // Format dates for comparison (convert datetime-local to comparable format)
        if(from) {
            const fromDate = new Date(from);
            const ticketDate = new Date(t.lcd);
            if(ticketDate < fromDate) ok = false;
        }
        if(to) {
            const toDate = new Date(to);
            const ticketDate = new Date(t.lcd);
            if(ticketDate > toDate) ok = false;
        }
        
        if(search && !Object.values(t).join(' ').toLowerCase().includes(search)) ok = false;
        return ok;
    });
    currentPage=1;
    renderTickets();
}

document.getElementById('searchInput').addEventListener('input',applyFilters);
document.getElementById('filterStatus').addEventListener('change',applyFilters);
document.getElementById('filterPriority').addEventListener('change',applyFilters);
document.getElementById('filterFrom').addEventListener('change',applyFilters);
document.getElementById('filterTo').addEventListener('change',applyFilters);

// Edit & Delete
const editModal = new bootstrap.Modal(document.getElementById('editModal'));
document.addEventListener('click',e=>{
    if(e.target.classList.contains('printBtn') || e.target.closest('.printBtn')){
        const btn = e.target.classList.contains('printBtn') ? e.target : e.target.closest('.printBtn');
        const ticketId = btn.dataset.id;
        printTicket(ticketId);
    }
    if(e.target.classList.contains('editBtn')){
        const t = e.target.closest('.ticket-item');
        document.getElementById('editId').value=t.dataset.id;
        document.getElementById('editTask').value=t.dataset.task;
        document.getElementById('editComplaint').value=t.dataset.complaint;
        document.getElementById('editPriority').value=t.dataset.priority;
        document.getElementById('editStatus').value=t.dataset.status;
        document.getElementById('editAgent').value=t.dataset.agent;
        document.getElementById('editLA').value=t.dataset.la;
        editModal.show();
    }
    if(e.target.classList.contains('deleteBtn')){
        if(confirm('Are you sure?')){
            const id=e.target.dataset.id;
            fetch('delete_ticket.php',{method:'POST',body:new URLSearchParams({idc:id})})
            .then(r=>r.json()).then(d=>{
                if(d.success){filteredTickets=filteredTickets.filter(t=>t.idc!=id);renderTickets();}
                else alert(d.error||'Delete failed');
            });
        }
    }
});

// Edit submit via AJAX
document.getElementById('editForm').addEventListener('submit',function(e){
    e.preventDefault();
    const fd=new FormData(this);
    fetch('edit_ticket.php',{method:'POST',body:fd})
    .then(r=>r.json()).then(data=>{
        if(data.success){
            const t=filteredTickets.find(ticket=>ticket.idc==fd.get('idc'));
            if(t){
                t.task=fd.get('task');
                t.incident=fd.get('incident');
                t.priority=fd.get('priority');
                t.status=fd.get('status');
                t.idfc=fd.get('agent');
                t.agent_name=data.agent_name; // Update agent name from response
                t.la=fd.get('la');
            }
            renderTickets();
            editModal.hide();
        } else { alert(data.error||'Update failed'); }
    });
});

// Print ticket function
function printTicket(ticketId) {
    const ticket = tickets.find(t => t.idc == ticketId);
    if (!ticket) {
        alert('Ticket not found');
        return;
    }
    
    const agentName = ticket.agent_name && ticket.agent_name !== 'null' ? ticket.agent_name : 'Unassigned';
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Ticket #${ticket.idc} - ${ticket.task}</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 40px; }
                .header { text-align: center; border-bottom: 3px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
                .header h1 { margin: 0; color: #333; }
                .header p { margin: 5px 0; color: #666; }
                .ticket-info { margin: 20px 0; }
                .info-row { display: flex; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                .info-label { font-weight: bold; width: 200px; color: #555; }
                .info-value { flex: 1; color: #333; }
                .badge { display: inline-block; padding: 5px 15px; border-radius: 5px; font-weight: bold; }
                .badge-high { background: #dc3545; color: white; }
                .badge-medium { background: #ffc107; color: #333; }
                .badge-low { background: #28a745; color: white; }
                .badge-resolved { background: #28a745; color: white; }
                .badge-progress { background: #ffc107; color: #333; }
                .badge-notresolved { background: #dc3545; color: white; }
                .footer { margin-top: 50px; text-align: center; color: #999; font-size: 12px; }
                @media print {
                    body { padding: 20px; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Support Ticket</h1>
                <p>Ticket ID: #${ticket.idc}</p>
                <p>Created: ${ticket.lcd}</p>
            </div>
            
            <div class="ticket-info">
                <div class="info-row">
                    <div class="info-label">Ticket Name:</div>
                    <div class="info-value"><strong>${ticket.task}</strong></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Client:</div>
                    <div class="info-value">${ticket.client_name}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Contact:</div>
                    <div class="info-value">${ticket.contact}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Complaint/Incident:</div>
                    <div class="info-value">${ticket.incident}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Priority:</div>
                    <div class="info-value">
                        <span class="badge badge-${ticket.priority.toLowerCase()}">${ticket.priority}</span>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Status:</div>
                    <div class="info-value">
                        <span class="badge badge-${ticket.status=='Resolved'?'resolved':(ticket.status=='In Progress'?'progress':'notresolved')}">${ticket.status}</span>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Assigned Agent:</div>
                    <div class="info-value">${agentName}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Last Activity:</div>
                    <div class="info-value">${ticket.la || 'N/A'}</div>
                </div>
            </div>
            
            <div class="footer">
                <p>This is an automated ticket printout - Generated on ${new Date().toLocaleString()}</p>
            </div>
            
            <div class="no-print" style="text-align: center; margin-top: 30px;">
                <button onclick="window.print()" style="padding: 10px 30px; font-size: 16px; cursor: pointer;">Print</button>
                <button onclick="window.close()" style="padding: 10px 30px; font-size: 16px; cursor: pointer; margin-left: 10px;">Close</button>
            </div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
}

renderTickets();
</script>
</body>
</html>