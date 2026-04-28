<?php
$clearUrl = "test204.php?page=" . urlencode($_SESSION['oop'] ?? '') . "&page1=" . urlencode($_SESSION['ooq'] ?? '');
$currentPage = basename($_SERVER['PHP_SELF']);
$isDispatcherPage = ($currentPage === 'dispatcher_assignments.php');
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo $clearUrl; ?>">
            <i class="fas fa-headset"></i> NCCIS 
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <!-- Left menu -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <!-- Quick Search Bar -->
                <li class="nav-item">
                    <div class="nav-link quick-search-container">
                        <div class="input-group quick-search-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" 
                                   id="navbarQuickSearch" 
                                   class="form-control quick-search-input" 
                                   placeholder="Search clients..." 
                                   autocomplete="off">
                            <div class="search-loader" id="searchLoader" style="display: none;">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>

                <!-- Tickets Dropdown >
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-ticket-alt"></i> Tickets
                    </a>
                    <ul class="dropdown-menu">
                        <li><h6 class="dropdown-header">Ticket Actions</h6></li>
                        <li>
                            <a class="dropdown-item" href="test56.php" target="_blank">
                                <i class="fas fa-plus text-success me-2"></i>New Ticket
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="tickets.php">
                                <i class="fas fa-folder-open text-warning me-2"></i>All Tickets
                            </a>
                        </li>
                    </ul>
                </li-->

                <!-- Queue Statistics Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-chart-line"></i> Queue Statistics
                    </a>
                    <ul class="dropdown-menu">
                        <li><h6 class="dropdown-header">Overview</h6></li>
                        <li>
                            <a class="dropdown-item" href="test462.php?page=<?php echo urlencode($_SESSION['oop'] ?? ''); ?>&page1=<?php echo urlencode($_SESSION['ooq'] ?? ''); ?>">
                                <i class="fas fa-chart-bar me-2"></i>Total
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="test464.php?page=<?php echo urlencode($_SESSION['oop'] ?? ''); ?>&page1=<?php echo urlencode($_SESSION['ooq'] ?? ''); ?>">
                                <i class="fas fa-list-alt me-2"></i>Queue
                            </a>
                        </li>
                        <li>
                             <a class="dropdown-item" href="test466.php?page=<?php echo urlencode($_SESSION['oop'] ?? ''); ?>&page1=<?php echo urlencode($_SESSION['ooq'] ?? ''); ?>">
                                <i class="fas fa-user me-2"></i>Agent
                            </a>
                        </li>
                        
                        <li><hr class="dropdown-divider"></li>
                        
                        <li>
                            <a class="dropdown-item" href="test476.php?page=<?php echo urlencode($_SESSION['oop'] ?? ''); ?>&page1=<?php echo urlencode($_SESSION['ooq'] ?? ''); ?>">
                                <i class="fas fa-user-circle me-2"></i>Agent Details
                            </a>
                        </li>
                        <li>
                            <a   class="dropdown-item"   href="agent_performance_standalone.php?page=<?php echo urlencode($_SESSION['oop'] ?? ''); ?>&page1=<?php echo urlencode($_SESSION['ooq'] ?? ''); ?>">
                               
                                <i class="fas fa-chart-line"></i> Agent Performance
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="queue_switchboard.php?page=<?php echo urlencode($_SESSION['oop'] ?? ''); ?>&page1=<?php echo urlencode($_SESSION['ooq'] ?? ''); ?>">
                                <i class="fas fa-user-circle me-2"></i>Queue Switchboard
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="test478.php?page=<?php echo urlencode($_SESSION['oop'] ?? ''); ?>&page1=<?php echo urlencode($_SESSION['ooq'] ?? ''); ?>">
                                <i class="fas fa-sign-in-alt me-2"></i>Login Record
                            </a>
                        </li>
                        
                        <li>
                            <a class="dropdown-item" href="test480.php?page=<?php echo urlencode($_SESSION['oop'] ?? ''); ?>&page1=<?php echo urlencode($_SESSION['ooq'] ?? ''); ?>">
                                <i class="fas fa-pause-circle me-2"></i>Pause Log
                            </a>
                        </li>
                    </ul>
                </li>


                <!-- Assignment -->
                <?php if (!$isDispatcherPage): ?>
                <li class="nav-item">
                    <a class="nav-link" href="dispatcher_assignments.php?page=<?php echo urlencode($_SESSION['oop'] ?? ''); ?>&page1=<?php echo urlencode($_SESSION['ooq'] ?? ''); ?>">
                        <i class="fas fa-route"></i> Assignment
                    </a>
                </li>
                <?php endif; ?>

                <!-- Reports Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                    <ul class="dropdown-menu">
                        <li><h6 class="dropdown-header">Reports & Analytics</h6></li>
                        <li>
                            <a class="dropdown-item" href="live_tracking.php">
                                <i class="fas fa-map-marker-alt"></i> Live Tracking
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="test321.php?page=<?php echo urlencode($_SESSION['oop'] ?? ''); ?>&page1=<?php echo urlencode($_SESSION['ooq'] ?? ''); ?>">
                                <i class="fas fa-list"></i> Client List
                            </a>
                        </li>
                        
                        <li>
                             <a class="dropdown-item" href="call_history.php?page=<?php echo urlencode($_SESSION['oop'] ?? ''); ?>&page1=<?php echo urlencode($_SESSION['ooq'] ?? ''); ?>">
                                <i class="fas fa-phone"></i> Call History
                            </a>
                        </li>
                         <li>
                            <a class="dropdown-item" href="tickets.php?page=<?php echo urlencode($_SESSION['oop'] ?? ''); ?>&page1=<?php echo urlencode($_SESSION['ooq'] ?? ''); ?>">
                                <i class="fas fa-folder-open text-warning me-2"></i>All Tickets
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- POS -->
                <li class="nav-item">
                    <a class="nav-link" href="pos.php">
                        <i class="fas fa-cash-register"></i> POS
                    </a>
                </li>

                <!-- System Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-cog"></i> System
                    </a>
                    <ul class="dropdown-menu">
                         <?php if(isset($nam) && $nam=="super"): ?>
                        <li><h6 class="dropdown-header"><i class="fas fa-truck"></i> Drivers</h6></li>
                        <li>
                            <a class="dropdown-item" href="drivers_manager.php?page=<?php echo urlencode($_SESSION['oop'] ?? ''); ?>&page1=<?php echo urlencode($_SESSION['ooq'] ?? ''); ?>">
                                <i class="fas fa-users"></i>  Drivers
                            </a>
                        </li>
                        <!--li>
                            <a class="dropdown-item" href="test182.php?page=<?php echo urlencode($_SESSION['oop'] ?? ''); ?>&page1=<?php echo urlencode($_SESSION['ooq'] ?? ''); ?>">
                                <i class="fas fa-user-plus"></i> Add Driver
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="#" onclick="deldriverAdvanced(); return false;">
                                <i class="fas fa-user-minus"></i> Delete Driver
                            </a>
                        </li-->

                        <li><hr class="dropdown-divider"></li>

                        <li><h6 class="dropdown-header"><i class="fas fa-user-tie"></i> Agents</h6></li>
                        
                        <li>
                            <a class="dropdown-item" href="agents_manager.php?page=<?php echo urlencode($_SESSION['oop'] ?? ''); ?>&page1=<?php echo urlencode($_SESSION['ooq'] ?? ''); ?>">
                                <i class="fas fa-users"></i> Agents
                            </a>
                        </li>
                        
                        <!--li>
                            <a class="dropdown-item text-danger" href="test402.php?page=<?php echo urlencode($_SESSION['oop'] ?? ''); ?>&page1=<?php echo urlencode($_SESSION['ooq'] ?? ''); ?>">
                                <i class="fas fa-user-minus"></i> Delete Agent
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="test306.php">
                                <i class="fas fa-users-slash"></i> Delete All Agents
                            </a>
                        </li-->
                        <?php endif; ?>

                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header"><i class="fas fa-exclamation-triangle"></i> Complaints</h6></li>
                        <li>
                            <a class="dropdown-item" href="complaints_manager.php?page=<?php echo urlencode($_SESSION['oop'] ?? ''); ?>&page1=<?php echo urlencode($_SESSION['ooq'] ?? ''); ?>">
                                <i class="fas fa-list"></i> Complaints
                            </a>
                        </li>
                        <!--li>
                            <a class="dropdown-item" href="test380.php?page=<?php echo urlencode($_SESSION['oop'] ?? ''); ?>&page1=<?php echo urlencode($_SESSION['ooq'] ?? ''); ?>">
                                <i class="fas fa-plus"></i> Add Complaint
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="test382.php?page=<?php echo urlencode($_SESSION['oop'] ?? ''); ?>&page1=<?php echo urlencode($_SESSION['ooq'] ?? ''); ?>">
                                <i class="fas fa-trash-alt"></i> Delete Complaint
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="test384.php">
                                <i class="fas fa-trash"></i> Delete All Complaints
                            </a>
                        </li-->

                        <?php if(isset($nam) && $nam=="super"): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header"><i class="fas fa-phone-alt"></i> UCM Phone System</h6></li>
                        <li>
                            <a class="dropdown-item" href="ucm_settings_ui.php" target="_blank">
                                <i class="fas fa-satellite-dish me-2"></i> UCM Bridge Settings
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>

                <!-- Data Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-database"></i> Data
                    </a>
                    <ul class="dropdown-menu">
                        <!--li><h6 class="dropdown-header">Export</h6></li>
                        <li>
                            <a class="dropdown-item" href="test269.php">
                                <i class="fas fa-file-export"></i> Export Complaints
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li-->
                        <!--li><h6 class="dropdown-header">Operations</h6></li>
                        <li>
                            <a class="dropdown-item" href="test42.php">
                                <i class="fas fa-save"></i> Backup
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="test38.php">
                                <i class="fas fa-undo"></i> Recovery
                            </a>
                        </li-->

                        <?php if(isset($nam) && $nam=="super"): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header text-danger">Danger Zone</h6></li>
                        <li>
                            <a class="dropdown-item text-danger" href="#" onclick="openDeleteAllClientsModal(); return false;">
                                <i class="fas fa-users-slash"></i> Delete All Clients
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="#" onclick="openDeleteAllComplaintsModal(); return false;">
                                <i class="fas fa-exclamation-triangle"></i> Delete All Tickets
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="#" onclick="del7(); return false;">
                                <i class="fas fa-exclamation-triangle"></i> Delete Locations
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>

            </ul>

            <!-- Right menu -->
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <span class="navbar-text text-white me-3">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION["oop"]); ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="login200.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Simple Full-Screen Solution -->
<div class="modal fade" id="quickSearchModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-users"></i> Client Search: 
                    <span id="searchTermDisplay"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="clientSearchFrame" style="width: 100%; height: 100vh; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Delete All Clients Modal -->
<div id="deleteAllClientsModal" class="delete-modal-overlay" style="display: none;">
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
        
        <div class="delete-success-message" id="deleteAllClientsSuccessMsg">
            <i class="fas fa-check-circle"></i> All clients have been successfully deleted!
        </div>
        
        <div class="delete-error-message" id="deleteAllClientsErrorMsg">
            <i class="fas fa-times-circle"></i> Error: Failed to delete clients. Please try again.
        </div>
        
        <div class="delete-modal-footer" id="deleteAllClientsModalFooter">
            <button class="modal-btn modal-btn-cancel" onclick="closeDeleteAllClientsModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button class="modal-btn modal-btn-delete" onclick="confirmDeleteAllClients()">
                <i class="fas fa-trash"></i> Delete All
            </button>
        </div>
    </div>
</div>

<!-- Delete All Complaints Modal -->
<div id="deleteAllComplaintsModal" class="delete-modal-overlay" style="display: none;">
    <div class="delete-modal-content">
        <div class="delete-modal-header">
            <div class="warning-icon">⚠️</div>
            <h2>Delete All Tickets</h2>
        </div>
        
        <div class="delete-modal-body">
            <p>Are you sure you want to delete ALL complaint data?</p>
            <div class="delete-warning-text">
                <i class="fas fa-exclamation-triangle"></i> This action cannot be undone!<br>
                All complaint records will be permanently deleted.
            </div>
        </div>
        
        <div class="delete-success-message" id="deleteAllComplaintsSuccessMsg">
            <i class="fas fa-check-circle"></i> All complaints have been successfully deleted!
        </div>
        
        <div class="delete-error-message" id="deleteAllComplaintsErrorMsg">
            <i class="fas fa-times-circle"></i> Error: Failed to delete complaints. Please try again.
        </div>
        
        <div class="delete-modal-footer" id="deleteAllComplaintsModalFooter">
            <button class="modal-btn modal-btn-cancel" onclick="closeDeleteAllComplaintsModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button class="modal-btn modal-btn-delete" onclick="confirmDeleteAllComplaints()">
                <i class="fas fa-trash"></i> Delete All
            </button>
        </div>
    </div>
</div>

<style>
/* Quick Search Styles */
.quick-search-container {
    padding-top: 0 !important;
    padding-bottom: 0 !important;
}

.quick-search-group {
    width: 280px;
    position: relative;
}

.quick-search-input {
    border-radius: 0 20px 20px 0 !important;
    border: none;
    padding: 8px 12px;
    font-size: 14px;
}

.quick-search-input:focus {
    box-shadow: none;
    border-color: #86b7fe;
}

.quick-search-group .input-group-text {
    border-radius: 20px 0 0 20px !important;
    border: none;
    background: white !important;
}

.search-loader {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 5;
}

/* Dropdown improvements */
.dropdown-menu {
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.dropdown-item {
    padding: 8px 16px;
    transition: background-color 0.2s;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

.dropdown-header {
    font-weight: 700;
    color: #495057;
    padding: 8px 16px;
}

.navbar-brand {
    font-weight: 700;
    font-size: 1.3rem;
}

/* Search suggestions dropdown */
.search-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    z-index: 1000;
    max-height: 200px;
    overflow-y: auto;
    display: none;
}

.search-suggestion-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f8f9fa;
}

.search-suggestion-item:hover {
    background-color: #f8f9fa;
}

.search-suggestion-item:last-child {
    border-bottom: none;
}

.search-suggestion-name {
    font-weight: 600;
    color: #333;
}

.search-suggestion-details {
    font-size: 12px;
    color: #666;
}

/* Delete Modal Styles (shared by both modals) */
.delete-modal-overlay {
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

@keyframes fadeOut {
    from { opacity: 1; }
    to { opacity: 0; }
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

/* RESPONSIVE FIXES FOR 14" SCREENS (1366px and below) */
@media screen and (max-width: 1400px) {
    /* Make navbar more compact */
    .navbar-brand {
        font-size: 1.1rem !important;
        padding: 0.4rem 0.8rem !important;
    }
    
    .nav-link {
        font-size: 0.85rem !important;
        padding: 0.4rem 0.6rem !important;
    }
    
    /* Smaller search bar */
    .quick-search-group {
        width: 200px !important;
    }
    
    .quick-search-input {
        font-size: 12px !important;
        padding: 6px 10px !important;
    }
    
    /* Compact dropdown menus */
    .dropdown-item {
        font-size: 0.85rem !important;
        padding: 6px 12px !important;
    }
    
    .dropdown-header {
        font-size: 0.8rem !important;
        padding: 6px 12px !important;
    }
    
    /* Reduce navbar text spacing */
    .navbar-text {
        font-size: 0.85rem !important;
        margin-right: 0.5rem !important;
    }
    
    /* Compact navbar overall */
    .navbar {
        padding: 0.3rem 0.5rem !important;
    }
    
    .navbar-nav .nav-item {
        margin: 0 2px !important;
    }
}

/* Extra compact for 1366px screens (standard 14") */
@media screen and (max-width: 1366px) {
    .navbar-brand {
        font-size: 1rem !important;
        padding: 0.3rem 0.6rem !important;
    }
    
    .nav-link {
        font-size: 0.8rem !important;
        padding: 0.35rem 0.5rem !important;
    }
    
    .nav-link i {
        font-size: 0.85rem !important;
        margin-right: 3px !important;
    }
    
    .quick-search-group {
        width: 180px !important;
    }
    
    .quick-search-input {
        font-size: 11px !important;
        padding: 5px 8px !important;
    }
    
    .dropdown-item {
        font-size: 0.8rem !important;
        padding: 5px 10px !important;
    }
    
    .dropdown-item i {
        font-size: 0.8rem !important;
    }
    
    .dropdown-header {
        font-size: 0.75rem !important;
        padding: 5px 10px !important;
    }
    
    .navbar-text {
        font-size: 0.8rem !important;
    }
    
    .navbar {
        padding: 0.25rem 0.4rem !important;
    }
}

/* For very tight screens */
@media screen and (max-width: 1280px) {
    .quick-search-group {
        width: 160px !important;
    }
    
    .navbar-brand {
        font-size: 0.95rem !important;
    }
    
    .nav-link {
        font-size: 0.75rem !important;
        padding: 0.3rem 0.4rem !important;
    }
}
</style>

<script>
// Quick Search Functionality
let searchTimeout;
let currentSearchTerm = '';

function initializeQuickSearch() {
    const searchInput = document.getElementById('navbarQuickSearch');
    const searchLoader = document.getElementById('searchLoader');
    
    if (!searchInput) return;
    
    // Create suggestions dropdown
    const suggestionsDropdown = document.createElement('div');
    suggestionsDropdown.className = 'search-suggestions';
    suggestionsDropdown.id = 'searchSuggestions';
    searchInput.parentNode.appendChild(suggestionsDropdown);
    
    // Input event with debouncing
    searchInput.addEventListener('input', function(e) {
        const term = e.target.value.trim();
        currentSearchTerm = term;
        
        clearTimeout(searchTimeout);
        suggestionsDropdown.style.display = 'none';
        
        if (term.length === 0) {
            searchLoader.style.display = 'none';
            return;
        }
        
        searchLoader.style.display = 'block';
        
        searchTimeout = setTimeout(() => {
            performQuickSearch(term, suggestionsDropdown);
        }, 800);
    });
    
    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length > 0 && suggestionsDropdown.children.length > 0) {
            suggestionsDropdown.style.display = 'block';
        }
    });
    
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsDropdown.contains(e.target)) {
            suggestionsDropdown.style.display = 'none';
        }
    });
    
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (this.value.trim().length > 0) {
                openClientSearchModal(this.value.trim());
            }
        } else if (e.key === 'Escape') {
            suggestionsDropdown.style.display = 'none';
            this.blur();
        }
    });
}

function performQuickSearch(term, suggestionsDropdown) {
    const searchLoader = document.getElementById('searchLoader');
    showQuickSuggestions(term, suggestionsDropdown);
    
    if (term.length >= 3) {
        setTimeout(() => {
            openClientSearchModal(term);
        }, 1200);
    }
    
    searchLoader.style.display = 'none';
}

function showQuickSuggestions(term, dropdown) {
    dropdown.innerHTML = '';
    
    const suggestionItem = document.createElement('div');
    suggestionItem.className = 'search-suggestion-item';
    suggestionItem.innerHTML = `
        <div class="search-suggestion-name">Search for "${term}"</div>
        <div class="search-suggestion-details">Click or press Enter to open full search</div>
    `;
    suggestionItem.onclick = function() {
        openClientSearchModal(term);
    };
    
    dropdown.appendChild(suggestionItem);
    dropdown.style.display = 'block';
}

function openClientSearchModal(searchTerm = null) {
    if (!searchTerm) {
        const input = document.getElementById('navbarQuickSearch');
        searchTerm = input ? input.value.trim() : '';
    }
    
    if (!searchTerm) return;
    
    const suggestionsDropdown = document.getElementById('searchSuggestions');
    if (suggestionsDropdown) {
        suggestionsDropdown.style.display = 'none';
    }
    
    document.getElementById('navbarQuickSearch').value = '';
    document.getElementById('searchTermDisplay').textContent = searchTerm;
    
    const iframe = document.getElementById('clientSearchFrame');
    iframe.src = `test321.php?search=${encodeURIComponent(searchTerm)}&autofocus=true`;
    
    const modal = new bootstrap.Modal(document.getElementById('quickSearchModal'));
    modal.show();
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeQuickSearch();
});

// Handle modal close messages
window.addEventListener('message', function(event) {
    if (event.data.type === 'closeModal') {
        const modal = document.getElementById('quickSearchModal');
        if (modal) {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        }
    }
});

// Delete All Clients Modal Functions
function openDeleteAllClientsModal() {
    document.getElementById('deleteAllClientsModal').style.display = 'block';
}

function closeDeleteAllClientsModal() {
    const modal = document.getElementById('deleteAllClientsModal');
    modal.style.animation = 'fadeOut 0.3s ease';
    setTimeout(() => {
        modal.style.display = 'none';
        modal.style.animation = '';
        // Reset messages
        document.getElementById('deleteAllClientsSuccessMsg').style.display = 'none';
        document.getElementById('deleteAllClientsErrorMsg').style.display = 'none';
        document.getElementById('deleteAllClientsModalFooter').style.display = 'flex';
    }, 300);
}

function confirmDeleteAllClients() {
    const deleteBtn = document.querySelector('#deleteAllClientsModal .modal-btn-delete');
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
            document.getElementById('deleteAllClientsSuccessMsg').style.display = 'block';
            document.getElementById('deleteAllClientsModalFooter').style.display = 'none';
            
            // Close modal and refresh page after 2 seconds
            setTimeout(() => {
                closeDeleteAllClientsModal();
                location.reload();
            }, 2000);
        } else {
            // Show error message
            document.getElementById('deleteAllClientsErrorMsg').style.display = 'block';
            document.getElementById('deleteAllClientsErrorMsg').innerHTML = 
                '<i class="fas fa-times-circle"></i> Error: ' + (data.message || 'Failed to delete clients');
            deleteBtn.innerHTML = originalText;
            deleteBtn.disabled = false;
        }
    })
    .catch(error => {
        document.getElementById('deleteAllClientsErrorMsg').style.display = 'block';
        document.getElementById('deleteAllClientsErrorMsg').innerHTML = 
            '<i class="fas fa-times-circle"></i> Network error: ' + error.message;
        deleteBtn.innerHTML = originalText;
        deleteBtn.disabled = false;
    });
}

// Delete All Complaints Modal Functions
function openDeleteAllComplaintsModal() {
    document.getElementById('deleteAllComplaintsModal').style.display = 'block';
}

function closeDeleteAllComplaintsModal() {
    const modal = document.getElementById('deleteAllComplaintsModal');
    modal.style.animation = 'fadeOut 0.3s ease';
    setTimeout(() => {
        modal.style.display = 'none';
        modal.style.animation = '';
        // Reset messages
        document.getElementById('deleteAllComplaintsSuccessMsg').style.display = 'none';
        document.getElementById('deleteAllComplaintsErrorMsg').style.display = 'none';
        document.getElementById('deleteAllComplaintsModalFooter').style.display = 'flex';
    }, 300);
}

function confirmDeleteAllComplaints() {
    const deleteBtn = document.querySelector('#deleteAllComplaintsModal .modal-btn-delete');
    const originalText = deleteBtn.innerHTML;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
    deleteBtn.disabled = true;
    
    // Send AJAX request to delete all complaints
    fetch('delete_all_complaints.php', {
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
            document.getElementById('deleteAllComplaintsSuccessMsg').style.display = 'block';
            document.getElementById('deleteAllComplaintsModalFooter').style.display = 'none';
            
            // Close modal and refresh page after 2 seconds
            setTimeout(() => {
                closeDeleteAllComplaintsModal();
                location.reload();
            }, 2000);
        } else {
            // Show error message
            document.getElementById('deleteAllComplaintsErrorMsg').style.display = 'block';
            document.getElementById('deleteAllComplaintsErrorMsg').innerHTML = 
                '<i class="fas fa-times-circle"></i> Error: ' + (data.message || 'Failed to delete complaints');
            deleteBtn.innerHTML = originalText;
            deleteBtn.disabled = false;
        }
    })
    .catch(error => {
        document.getElementById('deleteAllComplaintsErrorMsg').style.display = 'block';
        document.getElementById('deleteAllComplaintsErrorMsg').innerHTML = 
            '<i class="fas fa-times-circle"></i> Network error: ' + error.message;
        deleteBtn.innerHTML = originalText;
        deleteBtn.disabled = false;
    });
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    const clientsModal = document.getElementById('deleteAllClientsModal');
    const complaintsModal = document.getElementById('deleteAllComplaintsModal');
    
    if (e.target === clientsModal) {
        closeDeleteAllClientsModal();
    }
    if (e.target === complaintsModal) {
        closeDeleteAllComplaintsModal();
    }
});

// Close modals on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const clientsModal = document.getElementById('deleteAllClientsModal');
        const complaintsModal = document.getElementById('deleteAllComplaintsModal');
        
        if (clientsModal && clientsModal.style.display === 'block') {
            closeDeleteAllClientsModal();
        }
        if (complaintsModal && complaintsModal.style.display === 'block') {
            closeDeleteAllComplaintsModal();
        }
    }
});

// Driver delete function (keep for backward compatibility)
function deldriverAdvanced() {
    fetch('get_drivers.php')
    .then(response => response.json())
    .then(drivers => {
        if (drivers.length === 0) {
            alert('No drivers found to delete.');
            return;
        }

        let selectHTML = '<select id="driverSelect" style="width: 100%; padding: 10px; margin: 10px 0;">';
        selectHTML += '<option value="">Select a driver to delete...</option>';
        drivers.forEach(driver => {
            selectHTML += `<option value="${driver.idx}">${driver.name_d} (ID: ${driver.idx})</option>`;
        });
        selectHTML += '</select>';

        const modal = document.createElement('div');
        modal.id = 'driverDeleteModal';
        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center;';

        modal.innerHTML = `
            <div style="background: white; padding: 20px; border-radius: 10px; max-width: 400px; width: 90%;">
                <h3 style="margin-top: 0; color: #dc3545;">Delete Driver</h3>
                <p>Select a driver to delete:</p>
                ${selectHTML}
                <div style="text-align: right; margin-top: 20px;">
                    <button onclick="document.getElementById('driverDeleteModal').remove()" 
                        style="margin-right: 10px; padding: 8px 16px; border: 1px solid #ccc; background: #f8f9fa; border-radius: 4px; cursor: pointer;">
                        Cancel
                    </button>
                    <button onclick="confirmDriverDeletion()" 
                        style="padding: 8px 16px; border: none; background: #dc3545; color: white; border-radius: 4px; cursor: pointer;">
                        Delete
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
    })
    .catch(error => {
        console.error('Error fetching drivers:', error);
        alert('Error loading drivers list.');
    });
}

function confirmDriverDeletion() {
    const select = document.getElementById('driverSelect');
    const driverId = select.value;

    if (!driverId) {
        alert('Please select a driver to delete.');
        return;
    }

    if (!confirm('Are you sure you want to delete this driver?')) return;

    fetch('delete_driver.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ driver_id: driverId })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message || 'Driver deleted successfully');
            const modal = document.getElementById('driverDeleteModal');
            if (modal) modal.remove();
        } else {
            alert(result.message || 'Error deleting driver.');
        }
    })
    .catch(error => {
        console.error('Error deleting driver:', error);
        alert('Error deleting driver.');
    });
}

// Delete location function
function del7() {
    if (confirm('Are you sure you want to delete all location data?')) {
        window.location.href = 'del7.php';
    }
}
</script>