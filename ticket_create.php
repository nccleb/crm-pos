<?php
session_start();

if (!isset($_SESSION["ses"])) {
     header("Location: login200.php");
    exit();
 }

$agent = $_SESSION["ses"];
$contact_id = $_GET['contact'] ?? '';

$idr = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
    die("Failed to connect to MySQL: " . mysqli_connect_error());
}

// Get contact info if provided
$contact_info = null;
if (!empty($contact_id)) {
    $stmt = $idr->prepare("SELECT * FROM client WHERE id = ?");
    $stmt->bind_param("i", $contact_id);
    $stmt->execute();
    $contact_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Get agents
$agents_query = mysqli_query($idr, "SELECT idf, name FROM form_element ORDER BY name");

// Get complaints
$complaints_query = mysqli_query($idr, "SELECT comment_text FROM comments ORDER BY id_co ASC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $ticket_name = mysqli_real_escape_string($idr, $_POST['ticket_name']);
    $last_activity = mysqli_real_escape_string($idr, $_POST['last_activity']);
    $complaint = mysqli_real_escape_string($idr, $_POST['complaint']);
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $assigned_agent = $_POST['assigned_agent'];
    $lcd = date('Y-m-d H:i:s');
    
    $insert_query = "INSERT INTO crm (id, task, la, incident, priority, status, idfc, lcd) 
                     VALUES ('$client_id', '$ticket_name', '$last_activity', '$complaint', 
                             '$priority', '$status', '$assigned_agent', '$lcd')";
    
    if (mysqli_query($idr, $insert_query)) {
        $ticket_id = mysqli_insert_id($idr);
        echo "<script>
            alert('Ticket created successfully!');
            if (window.opener) {
                window.opener.location.reload();
                window.close();
            } else {
                window.location = 'tickets.php';
            }
        </script>";
    } else {
        $error = "Error creating ticket: " . mysqli_error($idr);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Ticket</title>
    <?php include('head.php'); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4f46e5;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --dark-color: #1f2937;
            --light-bg: #f9fafb;
            --border-color: #e5e7eb;
        }

        body {
            background: var(--light-bg);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            padding: 20px;
        }

        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, var(--primary-color), #6366f1);
            color: white;
            padding: 32px;
            text-align: center;
        }

        .form-header h1 {
            margin: 0 0 8px 0;
            font-size: 28px;
            font-weight: 700;
        }

        .form-header p {
            margin: 0;
            opacity: 0.9;
        }

        .form-body {
            padding: 32px;
        }

        .form-section {
            margin-bottom: 32px;
        }

        .form-section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--border-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-group label .required {
            color: var(--danger-color);
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .client-search-box {
            position: relative;
        }

        .client-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid var(--border-color);
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 10;
            display: none;
        }

        .client-suggestion {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid var(--border-color);
            transition: background 0.2s;
        }

        .client-suggestion:hover {
            background: var(--light-bg);
        }

        .client-suggestion:last-child {
            border-bottom: none;
        }

        .selected-client {
            background: #eff6ff;
            border: 2px solid #3b82f6;
            border-radius: 8px;
            padding: 16px;
            margin-top: 12px;
        }

        .selected-client .client-name {
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 8px;
        }

        .selected-client .client-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            font-size: 13px;
            color: #6b7280;
        }

        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .radio-option:hover {
            background: var(--light-bg);
        }

        .radio-option input[type="radio"] {
            margin-right: 12px;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .radio-option.selected {
            border-color: var(--primary-color);
            background: #eff6ff;
        }

        .priority-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .priority-option {
            padding: 16px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .priority-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .priority-option input {
            display: none;
        }

        .priority-option.high {
            border-color: var(--danger-color);
        }

        .priority-option.high.selected {
            background: #fee2e2;
            border-color: var(--danger-color);
        }

        .priority-option.medium {
            border-color: var(--warning-color);
        }

        .priority-option.medium.selected {
            background: #fef3c7;
            border-color: var(--warning-color);
        }

        .priority-option.low {
            border-color: #3b82f6;
        }

        .priority-option.low.selected {
            background: #dbeafe;
            border-color: #3b82f6;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding-top: 24px;
            border-top: 2px solid var(--border-color);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.3);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc2626;
        }

        @media (max-width: 768px) {
            .priority-selector {
                grid-template-columns: 1fr;
            }

            .selected-client .client-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h1><i class="fas fa-ticket-alt"></i> Create New Ticket</h1>
            <p>Fill in the details to create a support ticket</p>
        </div>

        <div class="form-body">
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="ticketForm">
                <!-- Client Selection -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-user"></i> Client Information
                    </div>
                    
                    <div class="form-group">
                        <label>Search Client <span class="required">*</span></label>
                        <div class="client-search-box">
                            <input type="text" 
                                   class="form-control" 
                                   id="clientSearch" 
                                   placeholder="Type client name or number..." 
                                   autocomplete="off"
                                   <?php echo $contact_info ? 'disabled' : ''; ?>>
                            <div class="client-suggestions" id="clientSuggestions"></div>
                        </div>
                        <input type="hidden" name="client_id" id="clientId" required>
                    </div>

                    <?php if ($contact_info): ?>
                    <div class="selected-client" id="selectedClientInfo">
                        <div class="client-name">
                            <i class="fas fa-user-circle"></i> 
                            <?php echo htmlspecialchars($contact_info['nom'] . ' ' . $contact_info['prenom']); ?>
                        </div>
                        <div class="client-details">
                            <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($contact_info['number']); ?></div>
                            <div><i class="fas fa-building"></i> <?php echo htmlspecialchars($contact_info['company'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                    <script>
                        document.getElementById('clientId').value = '<?php echo $contact_info['id']; ?>';
                    </script>
                    <?php else: ?>
                    <div class="selected-client" id="selectedClientInfo" style="display: none;"></div>
                    <?php endif; ?>
                </div>

                <!-- Ticket Details -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-file-alt"></i> Ticket Details
                    </div>

                    <div class="form-group">
                        <label>Ticket Name <span class="required">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               name="ticket_name" 
                               placeholder="Brief description of the issue" 
                               required>
                    </div>

                    <div class="form-group">
                        <label>Last Activity / Description <span class="required">*</span></label>
                        <textarea class="form-control" 
                                  name="last_activity" 
                                  placeholder="Detailed description of the issue and any actions taken..."
                                  required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Complaint Type <span class="required">*</span></label>
                        <select class="form-control" name="complaint" id="complaintSelect" required>
                            <option value="">Select complaint type...</option>
                            <?php while ($complaint = mysqli_fetch_assoc($complaints_query)): ?>
                                <option value="<?php echo htmlspecialchars($complaint['comment_text']); ?>">
                                    <?php echo htmlspecialchars($complaint['comment_text']); ?>
                                </option>
                            <?php endwhile; ?>
                            <option value="other">Other (specify below)</option>
                        </select>
                    </div>

                    <div class="form-group" id="otherComplaintGroup" style="display: none;">
                        <label>Specify Other Complaint</label>
                        <input type="text" 
                               class="form-control" 
                               id="otherComplaint" 
                               placeholder="Enter custom complaint type">
                    </div>
                </div>

                <!-- Priority & Status -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-tasks"></i> Priority & Status
                    </div>

                    <div class="form-group">
                        <label>Priority <span class="required">*</span></label>
                        <div class="priority-selector">
                            <label class="priority-option low">
                                <input type="radio" name="priority" value="Low" required>
                                <div><i class="fas fa-info-circle"></i></div>
                                <div><strong>Low</strong></div>
                                <small>Not urgent</small>
                            </label>
                            <label class="priority-option medium">
                                <input type="radio" name="priority" value="Medium" checked required>
                                <div><i class="fas fa-minus-circle"></i></div>
                                <div><strong>Medium</strong></div>
                                <small>Moderate urgency</small>
                            </label>
                            <label class="priority-option high">
                                <input type="radio" name="priority" value="High" required>
                                <div><i class="fas fa-exclamation-circle"></i></div>
                                <div><strong>High</strong></div>
                                <small>Very urgent</small>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Status <span class="required">*</span></label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="status" value="Not Resolved" checked>
                                <span><strong>Not Resolved</strong> - Issue needs attention</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="status" value="In Progress">
                                <span><strong>In Progress</strong> - Currently being worked on</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="status" value="Resolved">
                                <span><strong>Resolved</strong> - Issue has been fixed</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Assign To <span class="required">*</span></label>
                        <select class="form-control" name="assigned_agent" required>
                            <option value="">Select agent...</option>
                            <?php 
                            mysqli_data_seek($agents_query, 0);
                            while ($agent_row = mysqli_fetch_assoc($agents_query)): 
                            ?>
                                <option value="<?php echo $agent_row['idf']; ?>" 
                                        <?php echo $agent == $agent_row['idf'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($agent_row['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="window.close()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Create Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Client search functionality
        let searchTimeout;
        const clientSearch = document.getElementById('clientSearch');
        const clientSuggestions = document.getElementById('clientSuggestions');
        const clientId = document.getElementById('clientId');
        const selectedClientInfo = document.getElementById('selectedClientInfo');

        if (clientSearch) {
            clientSearch.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const term = this.value.trim();
                
                if (term.length < 2) {
                    clientSuggestions.style.display = 'none';
                    return;
                }
                
                searchTimeout = setTimeout(() => {
                    searchClients(term);
                }, 300);
            });
        }

        function searchClients(term) {
            fetch('ajax/search_clients.php?q=' + encodeURIComponent(term))
                .then(response => response.json())
                .then(clients => {
                    if (clients.length === 0) {
                        clientSuggestions.innerHTML = '<div class="client-suggestion">No clients found</div>';
                    } else {
                        clientSuggestions.innerHTML = clients.map(client => `
                            <div class="client-suggestion" onclick="selectClient(${client.id}, '${client.name}', '${client.number}', '${client.company}')">
                                <strong>${client.name}</strong><br>
                                <small>${client.number} ${client.company ? '• ' + client.company : ''}</small>
                            </div>
                        `).join('');
                    }
                    clientSuggestions.style.display = 'block';
                })
                .catch(error => console.error('Error:', error));
        }

        function selectClient(id, name, number, company) {
            clientId.value = id;
            clientSearch.value = name;
            clientSuggestions.style.display = 'none';
            
            selectedClientInfo.innerHTML = `
                <div class="client-name">
                    <i class="fas fa-user-circle"></i> ${name}
                </div>
                <div class="client-details">
                    <div><i class="fas fa-phone"></i> ${number}</div>
                    <div><i class="fas fa-building"></i> ${company || 'N/A'}</div>
                </div>
            `;
            selectedClientInfo.style.display = 'block';
        }

        // Complaint selection
        document.getElementById('complaintSelect').addEventListener('change', function() {
            const otherGroup = document.getElementById('otherComplaintGroup');
            if (this.value === 'other') {
                otherGroup.style.display = 'block';
                document.getElementById('otherComplaint').required = true;
            } else {
                otherGroup.style.display = 'none';
                document.getElementById('otherComplaint').required = false;
            }
        });

        // Priority selector styling
        document.querySelectorAll('.priority-option input').forEach(input => {
            input.addEventListener('change', function() {
                document.querySelectorAll('.priority-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.closest('.priority-option').classList.add('selected');
            });
        });

        // Status radio styling
        document.querySelectorAll('.radio-option input').forEach(input => {
            input.addEventListener('change', function() {
                document.querySelectorAll('.radio-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.closest('.radio-option').classList.add('selected');
            });
        });

        // Initialize selected options
        document.querySelector('.priority-option input:checked')?.closest('.priority-option').classList.add('selected');
        document.querySelector('.radio-option input:checked')?.closest('.radio-option').classList.add('selected');

        // Form submission with other complaint handling
        document.getElementById('ticketForm').addEventListener('submit', function(e) {
            const complaintSelect = document.getElementById('complaintSelect');
            const otherComplaint = document.getElementById('otherComplaint');
            
            if (complaintSelect.value === 'other' && otherComplaint.value.trim()) {
                complaintSelect.removeAttribute('name');
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'complaint';
                hiddenInput.value = otherComplaint.value;
                this.appendChild(hiddenInput);
            }
        });
    </script>
</body>
</html>