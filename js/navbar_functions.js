// navbar_functions.js
// All JavaScript functions required for the NCC CRM system

// ===== CALL CENTER DASHBOARD FUNCTIONS =====
function refresh() {
    window.location.reload();
}

function add110() {
    window.open('test19.php', '_blank', 'width=900,height=950');
}

function number22() {
    window.open('test321.php', '_blank', 'width=1000,height=700');
}

function add() {
    window.open('test56.php', '_blank', 'width=900,height=800');
}

function quitPage() {
    if (confirm('Are you sure you want to quit?')) {
        window.close();
    }
}

// ===== TICKET FUNCTIONS =====
function uro2() {
    window.open('ticket_search_number.php', '_blank', 'width=800,height=600');
}

function uro8() {
    window.open('ticket_search.php', '_blank', 'width=1000,height=700');
}

function tick79() {
    window.open('tickets.php?status=open', '_blank', 'width=1000,height=700');
}

// ===== SEARCH FUNCTIONS =====
function search5() {
    window.open('test321.php?searchtype=firstname', '_blank');
}

function search15() {
    window.open('test321.php?searchtype=lastname', '_blank');
}

function search16() {
    window.open('test321.php?searchtype=company', '_blank');
}

function search2() {
    window.open('test321.php?searchtype=business', '_blank');
}

function search10() {
    window.open('test321.php?searchtype=agent', '_blank');
}

// ===== DELETE FUNCTIONS =====
function del() {
    if (confirm('Are you sure you want to delete a client?')) {
        window.open('delete_client.php', '_blank');
    }
}

function delag() {
    if (confirm('Are you sure you want to delete an agent?')) {
        window.open('delete_agent.php', '_blank');
    }
}

function delal() {
    if (confirm('WARNING: This will delete ALL agents. Are you sure?')) {
        window.open('delete_all_agents.php', '_blank');
    }
}

function del_ag1() {
    if (confirm('Are you sure you want to delete a complaint?')) {
        window.open('delete_complaint.php', '_blank');
    }
}

function del_al1() {
    if (confirm('WARNING: This will delete ALL complaints. Are you sure?')) {
        window.open('delete_all_complaints.php', '_blank');
    }
}

function delAll() {
    if (confirm('WARNING: This will delete ALL clients. This cannot be undone!')) {
        window.open('delete_all_clients.php', '_blank');
    }
}

function delAll2() {
    if (confirm('WARNING: This will delete ALL complaints. Are you sure?')) {
        window.open('delete_all_complaints2.php', '_blank');
    }
}

function del7() {
    if (confirm('Are you sure you want to delete locations?')) {
        window.open('delete_locations.php', '_blank');
    }
}

function deldriverAdvanced() {
    if (confirm('Are you sure you want to delete a driver?')) {
        window.open('delete_driver.php', '_blank');
    }
}

// ===== ADD/VIEW FUNCTIONS =====
function dispatch() {
    window.open('dispatcher_assignments.php', '_blank', 'width=1200,height=800');
}

function list1() {
    window.open('client_list.php', '_blank', 'width=1000,height=700');
}

function add22() {
    window.open('drivers.php', '_blank', 'width=1000,height=700');
}

function add3() {
    window.open('add_driver.php', '_blank', 'width=600,height=500');
}

function add322() {
    window.open('complaints.php', '_blank', 'width=1000,height=700');
}

function add33() {
    window.open('add_complaint.php', '_blank', 'width=600,height=500');
}

// ===== DATA FUNCTIONS =====
function Exportc1() {
    window.open('export_complaints.php', '_blank');
}

function bb() {
    if (confirm('Create a backup of the database?')) {
        window.open('backup.php', '_blank');
    }
}

function ImportSql() {
    if (confirm('Restore database from backup?')) {
        window.open('recovery.php', '_blank');
    }
}

// ===== AUTO-HIDE ALERTS =====
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', function() {
    console.log('NCC CRM functions loaded successfully');
});