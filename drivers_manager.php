<?php
session_start();

// Store session variables for navbar
$_SESSION['oop'] = $_SESSION['oop'] ?? '';
$_SESSION['ooq'] = $_SESSION['ooq'] ?? '';

// Database configuration
define('DB_HOST', '192.168.1.101');
define('DB_USER', 'root');
define('DB_PASS', '1Sys9Admeen72');
define('DB_NAME', 'nccleb_test');

// Get database connection
function getDBConnection() {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (mysqli_connect_errno()) {
        die("Failed to connect to MySQL: " . mysqli_connect_error());
    }
    return $conn;
}

// Input validation function
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Handle actions
$action = isset($_GET['action']) ? $_GET['action'] : 'view';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$message = '';
$messageType = '';

// Check for success parameter
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'updated') {
        $message = "✓ Driver updated successfully!";
        $messageType = "success";
    } elseif ($_GET['success'] === 'added') {
        $message = "✓ Driver added successfully!";
        $messageType = "success";
    }
}

// Get edit data if editing
$editData = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $editId = (int)$_GET['id'];
    $idr = getDBConnection();
    $stmt = $idr->prepare("SELECT * FROM drivers WHERE idx = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    $editData = $result->fetch_assoc();
    $stmt->close();
    mysqli_close($idr);
    
    if (!$editData) {
        header("Location: ?action=view");
        exit();
    }
}

// Process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idr = getDBConnection();
    
    // Add driver
    if (isset($_POST['add_driver'])) {
        $name = test_input($_POST['name']);
        $number = test_input($_POST['number']);
        $email = test_input($_POST['email']);
        
        if (empty($name) || empty($number)) {
            $message = "Name and Number are required!";
            $messageType = "error";
        } elseif (!preg_match("/^[a-zA-Z0-9\p{Arabic}\s]*$/u", $name)) {
            $message = "Invalid name format!";
            $messageType = "error";
        } elseif (!preg_match("/^[0-9]*$/", $number)) {
            $message = "Invalid number format! Only digits allowed.";
            $messageType = "error";
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Invalid email format!";
            $messageType = "error";
        } else {
            // Check for duplicate name
            $checkStmt = $idr->prepare("SELECT COUNT(*) as count FROM drivers WHERE name_d = ?");
            $checkStmt->bind_param("s", $name);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $row = $result->fetch_assoc();
            $checkStmt->close();
            
            if ($row['count'] > 0) {
                $message = "Duplicate driver name!";
                $messageType = "error";
            } else {
                // Insert driver
                $insertStmt = $idr->prepare("INSERT INTO drivers (name_d, num_d, email) VALUES (?, ?, ?)");
                $insertStmt->bind_param("sss", $name, $number, $email);
                
                if ($insertStmt->execute()) {
                    $insertStmt->close();
                    mysqli_close($idr);
                    header("Location: ?action=view&success=added");
                    exit();
                } else {
                    $message = "Failed to add driver!";
                    $messageType = "error";
                }
                $insertStmt->close();
            }
        }
    }
    
    // Update driver
    if (isset($_POST['update_driver'])) {
        $id = test_input($_POST['driver_id']);
        $name = test_input($_POST['name']);
        $number = test_input($_POST['number']);
        $email = test_input($_POST['email']);
        
        if (empty($name) || empty($number)) {
            $message = "Name and Number are required!";
            $messageType = "error";
        } elseif (!preg_match("/^[a-zA-Z0-9\p{Arabic}\s]*$/u", $name)) {
            $message = "Invalid name format!";
            $messageType = "error";
        } elseif (!preg_match("/^[0-9]*$/", $number)) {
            $message = "Invalid number format! Only digits allowed.";
            $messageType = "error";
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Invalid email format!";
            $messageType = "error";
        } else {
            // Check for duplicate name (excluding current record)
            $checkStmt = $idr->prepare("SELECT COUNT(*) as count FROM drivers WHERE name_d = ? AND idx != ?");
            $checkStmt->bind_param("si", $name, $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $row = $result->fetch_assoc();
            $checkStmt->close();
            
            if ($row['count'] > 0) {
                $message = "Duplicate driver name!";
                $messageType = "error";
            } else {
                // Update driver
                $updateStmt = $idr->prepare("UPDATE drivers SET name_d = ?, num_d = ?, email = ? WHERE idx = ?");
                $updateStmt->bind_param("sssi", $name, $number, $email, $id);
                
                if ($updateStmt->execute()) {
                    $updateStmt->close();
                    mysqli_close($idr);
                    header("Location: ?action=view&success=updated");
                    exit();
                } else {
                    $message = "Failed to update driver!";
                    $messageType = "error";
                }
                $updateStmt->close();
            }
        }
    }
    
    // Import CSV
    if (isset($_POST['import_csv']) && isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file'];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $tmpName = $file['tmp_name'];
            $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if ($fileType !== 'csv') {
                $message = "Invalid file type! Please upload a CSV file.";
                $messageType = "error";
            } else {
                $handle = fopen($tmpName, 'r');
                $imported = 0;
                $skipped = 0;
                $errors = 0;
                
                // Skip header row
                fgetcsv($handle);
                
                while (($data = fgetcsv($handle)) !== false) {
                    if (count($data) < 3) {
                        $skipped++;
                        continue;
                    }
                    
                    $name = test_input($data[1]);
                    $number = test_input($data[2]);
                    $email = isset($data[3]) ? test_input($data[3]) : '';
                    
                    // Validate
                    if (empty($name) || empty($number)) {
                        $skipped++;
                        continue;
                    }
                    
                    if (!preg_match("/^[a-zA-Z0-9\p{Arabic}\s]*$/u", $name)) {
                        $skipped++;
                        continue;
                    }
                    
                    if (!preg_match("/^[0-9]*$/", $number)) {
                        $skipped++;
                        continue;
                    }
                    
                    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $skipped++;
                        continue;
                    }
                    
                    // Check for duplicates
                    $checkStmt = $idr->prepare("SELECT COUNT(*) as count FROM drivers WHERE name_d = ?");
                    $checkStmt->bind_param("s", $name);
                    $checkStmt->execute();
                    $result = $checkStmt->get_result();
                    $row = $result->fetch_assoc();
                    $checkStmt->close();
                    
                    if ($row['count'] > 0) {
                        $skipped++;
                        continue;
                    }
                    
                    // Insert
                    $insertStmt = $idr->prepare("INSERT INTO drivers (name_d, num_d, email) VALUES (?, ?, ?)");
                    $insertStmt->bind_param("sss", $name, $number, $email);
                    
                    if ($insertStmt->execute()) {
                        $imported++;
                    } else {
                        $errors++;
                    }
                    $insertStmt->close();
                }
                
                fclose($handle);
                
                $message = "✓ Import completed! Imported: $imported, Skipped/Duplicates: $skipped, Errors: $errors";
                $messageType = "success";
            }
        } else {
            $message = "File upload error!";
            $messageType = "error";
        }
    }
    
    // Delete single driver
    if (isset($_POST['delete_single'])) {
        $id = test_input($_POST['delete_id']);
        
        if (empty($id) || !is_numeric($id)) {
            $message = "Invalid ID!";
            $messageType = "error";
        } else {
            $deleteStmt = $idr->prepare("DELETE FROM drivers WHERE idx = ?");
            $deleteStmt->bind_param("i", $id);
            
            if ($deleteStmt->execute() && $deleteStmt->affected_rows > 0) {
                $message = "✓ Driver deleted successfully!";
                $messageType = "success";
            } else {
                $message = "Driver not found or already deleted!";
                $messageType = "error";
            }
            $deleteStmt->close();
        }
    }
    
    // Delete all drivers
    if (isset($_POST['delete_all_confirm'])) {
        mysqli_query($idr, "SET foreign_key_checks=0");
        $result = mysqli_query($idr, "TRUNCATE TABLE drivers");
        mysqli_query($idr, "SET foreign_key_checks=1");
        mysqli_query($idr, "ALTER TABLE drivers AUTO_INCREMENT=1");
        
        if ($result) {
            $message = "✓ All drivers deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to delete all drivers!";
            $messageType = "error";
        }
    }
    
    mysqli_close($idr);
}

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $idr = getDBConnection();
    $result = mysqli_query($idr, "SELECT * FROM drivers ORDER BY idx ASC");
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="drivers_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, array('ID', 'Name', 'Number', 'Email'));
    
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, array($row['idx'], $row['name_d'], $row['num_d'], $row['email']));
    }
    
    fclose($output);
    mysqli_close($idr);
    exit();
}

// Get total count and pagination info
$idr = getDBConnection();
$countQuery = "SELECT COUNT(*) as total FROM drivers";
if (!empty($searchTerm)) {
    $countQuery .= " WHERE name_d LIKE '%" . mysqli_real_escape_string($idr, $searchTerm) . "%' 
                     OR num_d LIKE '%" . mysqli_real_escape_string($idr, $searchTerm) . "%'
                     OR email LIKE '%" . mysqli_real_escape_string($idr, $searchTerm) . "%'";
}
$countResult = mysqli_query($idr, $countQuery);
$totalDrivers = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalDrivers / $perPage);
mysqli_close($idr);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drivers Management System</title>
    
    <?php include('head.php'); ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            padding-top: 80px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header h1 {
            color: #2d3748;
            font-size: 32px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header h1 i {
            color: #667eea;
        }
        
        .stats-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
        }
        
        .nav-tabs {
            background: white;
            padding: 15px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .nav-tabs a {
            flex: 1;
            min-width: 150px;
            padding: 15px 25px;
            background: #f7fafc;
            text-decoration: none;
            color: #4a5568;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 600;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .nav-tabs a:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }
        
        .nav-tabs a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .content-section {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            min-height: 400px;
        }
        
        .search-box {
            margin-bottom: 25px;
        }
        
        .search-box input {
            width: 100%;
            max-width: 500px;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .message {
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 12px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }
        
        .message i {
            font-size: 24px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 18px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 18px;
            border-bottom: 1px solid #e2e8f0;
            color: #4a5568;
        }
        
        tr:hover {
            background: #f7fafc;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .form-group {
            margin: 25px 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #2d3748;
            font-size: 16px;
        }
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="file"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }
        
        .form-group input[type="file"] {
            padding: 12px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            padding: 15px 30px;
            font-size: 16px;
            cursor: pointer;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #718096;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            flex-wrap: wrap;
        }
        
        .export-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .delete-confirm {
            background: linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
        }
        
        .delete-confirm h3 {
            font-size: 32px;
            margin-bottom: 20px;
        }
        
        .delete-confirm p {
            font-size: 18px;
            margin-bottom: 15px;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #a0aec0;
        }
        
        .empty-state i {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 5px solid #2196f3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
        }
        
        .info-box h4 {
            color: #1976d2;
            margin-bottom: 10px;
        }
        
        .info-box p {
            color: #555;
            margin: 5px 0;
        }
        
        .quit-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #dc3545;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
            box-shadow: 0 5px 20px rgba(220, 53, 69, 0.4);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .quit-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 30px rgba(220, 53, 69, 0.6);
        }
        
        .quit-btn i {
            font-size: 24px;
        }
        
        /* Pagination Styles */
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 30px;
            gap: 10px;
        }
        
        .pagination {
            display: flex;
            gap: 5px;
            list-style: none;
            padding: 0;
        }
        
        .pagination a {
            padding: 10px 16px;
            background: #f7fafc;
            color: #4a5568;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }
        
        .pagination a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .pagination a.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .page-info {
            color: #4a5568;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            body {
                padding-top: 120px;
            }
            
            .header {
                text-align: center;
                justify-content: center;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .nav-tabs a {
                min-width: 100%;
            }
            
            .content-section {
                padding: 20px;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 12px 8px;
            }
        }
    </style>
</head>

<body>



<div class="container">
    <!-- Header -->
    <div class="header">
        <h1>
            <i class="fas fa-truck"></i>
            Drivers Manager
        </h1>
        <div class="stats-badge">
            <i class="fas fa-users"></i> Total: <?php echo $totalDrivers; ?> Drivers
        </div>
    </div>
    
    <!-- Navigation Tabs -->
    <div class="nav-tabs">
        <a href="?action=view" class="<?php echo $action === 'view' ? 'active' : ''; ?>">
            <i class="fas fa-list"></i> View All
        </a>
        <a href="?action=add" class="<?php echo $action === 'add' ? 'active' : ''; ?>">
            <i class="fas fa-user-plus"></i> Add New
        </a>
        <a href="?action=import" class="<?php echo $action === 'import' ? 'active' : ''; ?>">
            <i class="fas fa-file-import"></i> Import CSV
        </a>
        <a href="?action=delete_all" class="<?php echo $action === 'delete_all' ? 'active' : ''; ?>">
            <i class="fas fa-trash-alt"></i> Delete All
        </a>
    </div>
    
    <!-- Messages -->
    <?php if (!empty($message)): ?>
        <div class="message <?php echo $messageType; ?>">
            <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
            <span><?php echo $message; ?></span>
        </div>
    <?php endif; ?>
    
    <!-- Content Sections -->
    <div class="content-section">
        
        <?php if ($action === 'view'): ?>
            <!-- VIEW ALL DRIVERS -->
            
            <!-- Search Box -->
            <div class="search-box">
                <form method="get" action="">
                    <input type="hidden" name="action" value="view">
                    <input type="text" name="search" placeholder="🔍 Search by name, number, or email..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                </form>
            </div>
            
            <div class="export-buttons">
                <a href="?action=view&export=csv<?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>" class="btn btn-success">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            </div>
            
            <?php
            $idr = getDBConnection();
            
            // Build query with search
            $query = "SELECT * FROM drivers";
            if (!empty($searchTerm)) {
                $searchTermEscaped = mysqli_real_escape_string($idr, $searchTerm);
                $query .= " WHERE name_d LIKE '%$searchTermEscaped%' OR num_d LIKE '%$searchTermEscaped%' OR email LIKE '%$searchTermEscaped%'";
            }
            $query .= " ORDER BY idx DESC LIMIT $perPage OFFSET $offset";
            
            $result = mysqli_query($idr, $query);
            
            if (mysqli_num_rows($result) > 0):
            ?>
            
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Name</th>
                        <th>Number</th>
                        <th>Email</th>
                        <th style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($row = mysqli_fetch_assoc($result)) {
                        $id = $row['idx'];
                        $name = htmlspecialchars($row['name_d']);
                        $number = htmlspecialchars($row['num_d']);
                        $email = htmlspecialchars($row['email']);
                        
                        echo "<tr>";
                        echo "<td style='text-align:center; font-weight:600;'>{$id}</td>";
                        echo "<td>{$name}</td>";
                        echo "<td>{$number}</td>";
                        echo "<td>{$email}</td>";
                        echo "<td style='text-align:center;'>
                                <a href='?action=edit&id={$id}' class='btn btn-primary btn-small' style='margin-right: 5px;'>
                                    <i class='fas fa-edit'></i>
                                </a>
                                <form method='post' style='display:inline;'>
                                    <input type='hidden' name='delete_id' value='{$id}'>
                                    <button type='submit' name='delete_single' class='btn btn-danger btn-small' onclick='return confirm(\"Delete this driver?\")'>
                                        <i class='fas fa-trash'></i>
                                    </button>
                                </form>
                              </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination-container">
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?action=view&page_num=<?php echo $page - 1; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php else: ?>
                        <a href="#" class="disabled">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    if ($startPage > 1): ?>
                        <a href="?action=view&page_num=1<?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>">1</a>
                        <?php if ($startPage > 2): ?>
                            <span style="padding: 10px;">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?action=view&page_num=<?php echo $i; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>" 
                           class="<?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span style="padding: 10px;">...</span>
                        <?php endif; ?>
                        <a href="?action=view&page_num=<?php echo $totalPages; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>"><?php echo $totalPages; ?></a>
                    <?php endif; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?action=view&page_num=<?php echo $page + 1; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <a href="#" class="disabled">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="page-info">
                    Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-slash"></i>
                    <h3>No Drivers Found</h3>
                    <?php if (!empty($searchTerm)): ?>
                        <p>No results for "<?php echo htmlspecialchars($searchTerm); ?>"</p>
                        <a href="?action=view" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-times"></i> Clear Search
                        </a>
                    <?php else: ?>
                        <p>Start by adding a new driver</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php mysqli_close($idr); ?>
            
        <?php elseif ($action === 'add'): ?>
            <!-- ADD NEW DRIVER -->
            <h2 style="margin-bottom: 30px; color: #2d3748;">
                <i class="fas fa-user-plus"></i> Add New Driver
            </h2>
            
            <form method="post">
                <div class="form-group">
                    <label for="name">
                        <i class="fas fa-user"></i> Name <span style="color: red;">*</span>
                    </label>
                    <input type="text" name="name" id="name" required placeholder="Enter driver name">
                    <small style="color: #718096;">Letters, numbers, and Arabic text allowed</small>
                </div>
                
                <div class="form-group">
                    <label for="number">
                        <i class="fas fa-phone"></i> Number <span style="color: red;">*</span>
                    </label>
                    <input type="text" name="number" id="number" required placeholder="Enter phone number">
                    <small style="color: #718096;">Only digits allowed</small>
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <input type="email" name="email" id="email" placeholder="Enter email address (optional)">
                    <small style="color: #718096;">Optional</small>
                </div>
                
                <div class="button-group">
                    <button type="submit" name="add_driver" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Driver
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('name').value=''; document.getElementById('number').value=''; document.getElementById('email').value=''; document.getElementById('name').focus();">
                        <i class="fas fa-eraser"></i> Clear
                    </button>
                </div>
            </form>
            
        <?php elseif ($action === 'edit'): ?>
            <!-- EDIT DRIVER -->
            <h2 style="margin-bottom: 30px; color: #2d3748;">
                <i class="fas fa-edit"></i> Edit Driver
            </h2>
            
            <?php if ($editData): ?>
            <form method="post">
                <input type="hidden" name="driver_id" value="<?php echo $editData['idx']; ?>">
                
                <div class="form-group">
                    <label for="name">
                        <i class="fas fa-user"></i> Name <span style="color: red;">*</span>
                    </label>
                    <input type="text" name="name" id="name" required 
                           value="<?php echo htmlspecialchars($editData['name_d']); ?>"
                           placeholder="Enter driver name">
                    <small style="color: #718096;">Letters, numbers, and Arabic text allowed</small>
                </div>
                
                <div class="form-group">
                    <label for="number">
                        <i class="fas fa-phone"></i> Number <span style="color: red;">*</span>
                    </label>
                    <input type="text" name="number" id="number" required 
                           value="<?php echo htmlspecialchars($editData['num_d']); ?>"
                           placeholder="Enter phone number">
                    <small style="color: #718096;">Only digits allowed</small>
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <input type="email" name="email" id="email" 
                           value="<?php echo htmlspecialchars($editData['email']); ?>"
                           placeholder="Enter email address (optional)">
                    <small style="color: #718096;">Optional</small>
                </div>
                
                <div class="button-group">
                    <button type="submit" name="update_driver" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Driver
                    </button>
                    <a href="?action=view" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
            <?php endif; ?>
            
        <?php elseif ($action === 'import'): ?>
            <!-- IMPORT CSV -->
            <h2 style="margin-bottom: 30px; color: #2d3748;">
                <i class="fas fa-file-import"></i> Import Drivers from CSV
            </h2>
            
            <div class="info-box">
                <h4><i class="fas fa-info-circle"></i> CSV File Format</h4>
                <p><strong>Required columns:</strong></p>
                <p>• Column 1: ID (will be auto-generated, can be any value)</p>
                <p>• Column 2: Name (required)</p>
                <p>• Column 3: Number (required, digits only)</p>
                <p>• Column 4: Email (optional)</p>
                <p style="margin-top: 10px;"><strong>Example:</strong></p>
                <p style="font-family: monospace; background: white; padding: 10px; border-radius: 5px;">
                    ID,Name,Number,Email<br>
                    1,John Doe,1234567890,john@example.com<br>
                    2,Jane Smith,0987654321,jane@example.com
                </p>
                <p style="margin-top: 10px; color: #856404; background: #fff3cd; padding: 8px; border-radius: 5px;">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Note:</strong> Duplicate driver names will be automatically skipped.
                </p>
            </div>
            
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="csv_file">
                        <i class="fas fa-file-csv"></i> Select CSV File
                    </label>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                    <small style="color: #718096;">Only CSV files are accepted</small>
                </div>
                
                <div class="button-group">
                    <button type="submit" name="import_csv" class="btn btn-info">
                        <i class="fas fa-upload"></i> Import CSV
                    </button>
                    <a href="?action=view&export=csv" class="btn btn-success">
                        <i class="fas fa-download"></i> Download Sample CSV
                    </a>
                </div>
            </form>
            
        <?php elseif ($action === 'delete_all'): ?>
            <!-- DELETE ALL DRIVERS -->
            <div class="delete-confirm">
                <i class="fas fa-exclamation-triangle" style="font-size: 80px; margin-bottom: 20px;"></i>
                <h3>⚠️ CRITICAL WARNING ⚠️</h3>
                <p style="font-size: 20px; font-weight: 600;">Are you absolutely sure you want to delete ALL <?php echo $totalDrivers; ?> drivers?</p>
                <p>This action is PERMANENT and CANNOT be undone!</p>
                
                <div class="action-buttons">
                    <form method="post" style="display: inline;">
                        <button type="submit" name="delete_all_confirm" class="btn btn-danger" style="background: white; color: #dc3545; font-size: 18px; padding: 18px 35px;">
                            <i class="fas fa-trash-alt"></i> YES, Delete Everything
                        </button>
                    </form>
                    <button type="button" class="btn" onclick="window.location.href='?action=view'" style="font-size: 18px; padding: 18px 35px; background: rgba(255,255,255,0.2); color: white;">
                        <i class="fas fa-times"></i> NO, Go Back
                    </button>
                </div>
            </div>
            
        <?php endif; ?>
        
    </div>
</div>

<!-- Floating Quit Button -->
<button class="quit-btn" onclick="quit()" title="Quit Application">
    <i class="fas fa-power-off"></i>
</button>

<script>
function quit() {
     window.location.replace("test204.php?page=<?php echo $_SESSION["oop"] ?>&page1=<?php echo $_SESSION["ooq"] ?>");   
}
</script>

</body>
</html>