<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment Management</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .assignment-card { border-left: 4px solid #007bff; }
        .assignment-card.completed { border-left-color: #28a745; opacity: 0.8; }
        .assignment-card.cancelled { border-left-color: #dc3545; opacity: 0.8; }
        .driver-status { font-size: 12px; font-weight: bold; padding: 2px 6px; border-radius: 10px; color: white; }
        .status-busy { background: #ffc107; color: black; }
        .status-available { background: #28a745; }
        .status-offline { background: #dc3545; }
        .btn-sm { font-size: 12px; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2><i class="fas fa-clipboard-list"></i> Assignment Management</h2>
                <p class="text-muted">Manage driver assignments and track completion status</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-plus"></i> Create New Assignment</h5>
                    </div>
                    <div class="card-body">
                        <form id="assignmentForm">
                            <div class="form-group">
                                <label>Driver:</label>
                                <select class="form-control" id="driverId" required>
                                    <option value="">Select Driver...</option>
                                    <option value="1">Nehme (ID: 1)</option>
                                    <option value="2">George (ID: 2)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Client ID:</label>
                                <input type="number" class="form-control" id="clientId" placeholder="Optional">
                            </div>
                            <div class="form-group">
                                <label>Delivery Address:</label>
                                <textarea class="form-control" id="deliveryAddress" rows="2" required placeholder="Enter delivery address..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-truck"></i> Create Assignment
                            </button>
                        </form>
                        <div id="createResult" class="mt-3"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5><i class="fas fa-list"></i> Active Assignments</h5>
                        <button class="btn btn-sm btn-light float-right" onclick="loadActiveAssignments()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                    <div class="card-body" id="activeAssignments">
                        Loading assignments...
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fas fa-history"></i> Recent Completions</h5>
                    </div>
                    <div class="card-body" id="recentCompletions">
                        Loading recent completions...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Create assignment
        $('#assignmentForm').on('submit', function(e) {
            e.preventDefault();
            
            const data = {
                dispatcher_id: parseInt($('#driverId').val()),
                client_id: $('#clientId').val() ? parseInt($('#clientId').val()) : null,
                delivery_address: $('#deliveryAddress').val()
            };
            
            $.ajax({
                url: 'create_quick_assignment.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                success: function(response) {
                    if (response.success) {
                        $('#createResult').html(`
                            <div class="alert alert-success">
                                <strong>Success!</strong> Assignment created for ${response.assignment.driver_name}<br>
                                <small>Assignment ID: ${response.assignment_id}</small>
                            </div>
                        `);
                        $('#assignmentForm')[0].reset();
                        loadActiveAssignments();
                    } else {
                        $('#createResult').html(`
                            <div class="alert alert-danger">
                                <strong>Error:</strong> ${response.error}
                            </div>
                        `);
                    }
                },
                error: function(xhr) {
                    const error = xhr.responseJSON ? xhr.responseJSON.error : 'Unknown error';
                    $('#createResult').html(`
                        <div class="alert alert-danger">
                            <strong>Error:</strong> ${error}
                        </div>
                    `);
                }
            });
        });
        
        // Complete assignment
        function completeAssignment(assignmentId, status = 'completed') {
            const notes = prompt('Add completion notes (optional):');
            
            const data = {
                assignment_id: assignmentId,
                status: status,
                notes: notes
            };
            
            $.ajax({
                url: 'complete_assignment.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                success: function(response) {
                    if (response.success) {
                        alert('Assignment completed successfully!');
                        loadActiveAssignments();
                        loadRecentCompletions();
                    } else {
                        alert('Error: ' + response.error);
                    }
                },
                error: function(xhr) {
                    const error = xhr.responseJSON ? xhr.responseJSON.error : 'Unknown error';
                    alert('Error: ' + error);
                }
            });
        }
        
        // Load active assignments
        function loadActiveAssignments() {
            // This would connect to your existing assignment listing API
            // For now, showing placeholder
            $('#activeAssignments').html(`
                <div class="text-muted">
                    <p><i class="fas fa-info-circle"></i> Connect this to your existing assignment listing API</p>
                    <p>Typically you'd query: <code>SELECT * FROM dispatch_assignments WHERE status IN ('assigned', 'in_progress')</code></p>
                </div>
            `);
        }
        
        // Load recent completions
        function loadRecentCompletions() {
            $('#recentCompletions').html(`
                <div class="text-muted">
                    <p><i class="fas fa-info-circle"></i> Recent completed assignments would appear here</p>
                    <p>Query: <code>SELECT * FROM dispatch_assignments WHERE status = 'completed' ORDER BY updated_at DESC LIMIT 10</code></p>
                </div>
            `);
        }
        
        // Initialize
        $(document).ready(function() {
            loadActiveAssignments();
            loadRecentCompletions();
        });
    </script>
</body>
</html>