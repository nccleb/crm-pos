<?php
session_start();

// Database configuration
define('DB_HOST', '172.18.208.1');
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
$message = '';
$messageType = '';

// Check for success parameter
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'updated') {
        $message = "✓ Complaint updated successfully!";
        $messageType = "success";
    }
}

// Get edit data if editing
$editData = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $editId = (int)$_GET['id'];
    $idr = getDBConnection();
    $stmt = $idr->prepare("SELECT * FROM comments WHERE id_co = ?");
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
    
    // Update complaint
    if (isset($_POST['update_complaint'])) {
        $id = test_input($_POST['complaint_id']);
        $complaint = test_input($_POST['complaint_text']);
        
        if (empty($complaint)) {
            $message = "Comment cannot be empty!";
            $messageType = "error";
        } elseif (strlen($complaint) < 3) {
            $message = "Comment must be at least 3 characters long!";
            $messageType = "error";
        } elseif (strlen($complaint) > 500) {
            $message = "Comment is too long! Maximum 500 characters allowed.";
            $messageType = "error";
        } elseif (!preg_match("/^[\p{L}\p{N}\p{P}\p{S}\s]+$/u", $complaint)) {
            $message = "Invalid comment format!";
            $messageType = "error";
        } else {
            // Check for duplicates (excluding current record)
            $checkStmt = $idr->prepare("SELECT COUNT(*) as count FROM comments WHERE comment_text = ? AND id_co != ?");
            $checkStmt->bind_param("si", $complaint, $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $row = $result->fetch_assoc();
            $checkStmt->close();
            
            if ($row['count'] > 0) {
                $message = "Duplicate complaint! This comment already exists.";
                $messageType = "error";
            } else {
                // Update complaint
                $updateStmt = $idr->prepare("UPDATE comments SET comment_text = ? WHERE id_co = ?");
                $updateStmt->bind_param("si", $complaint, $id);
                
                if ($updateStmt->execute()) {
                    $message = "✓ Complaint updated successfully!";
                    $messageType = "success";
                    header("Location: ?action=view&success=updated");
                    exit();
                } else {
                    $message = "Failed to update complaint!";
                    $messageType = "error";
                }
                $updateStmt->close();
            }
        }
    }
    
    // Add complaint
    if (isset($_POST['add_complaint'])) {
        $complaint = test_input($_POST['complaint_text']);
        
        if (empty($complaint)) {
            $message = "Comment cannot be empty!";
            $messageType = "error";
        } elseif (strlen($complaint) < 3) {
            $message = "Comment must be at least 3 characters long!";
            $messageType = "error";
        } elseif (strlen($complaint) > 500) {
            $message = "Comment is too long! Maximum 500 characters allowed.";
            $messageType = "error";
        } elseif (!preg_match("/^[\p{L}\p{N}\p{P}\p{S}\s]+$/u", $complaint)) {
            $message = "Invalid comment format!";
            $messageType = "error";
        } else {
            // Check for duplicates
            $checkStmt = $idr->prepare("SELECT COUNT(*) as count FROM comments WHERE comment_text = ?");
            $checkStmt->bind_param("s", $complaint);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $row = $result->fetch_assoc();
            $checkStmt->close();
            
            if ($row['count'] > 0) {
                $message = "Duplicate complaint! This comment already exists.";
                $messageType = "error";
            } else {
                // Insert new comment
                $insertStmt = $idr->prepare("INSERT INTO comments (comment_text, comment_status) VALUES (?, 0)");
                $insertStmt->bind_param("s", $complaint);
                
                if ($insertStmt->execute()) {
                    $message = "✓ Comment inserted successfully!";
                    $messageType = "success";
                } else {
                    $message = "Failed to insert comment: " . mysqli_error($idr);
                    $messageType = "error";
                }
                $insertStmt->close();
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
                    if (count($data) < 2) {
                        $skipped++;
                        continue;
                    }
                    
                    $complaint_text = test_input($data[1]); // Column 2 is the complaint text
                    
                    // Validate
                    if (empty($complaint_text) || strlen($complaint_text) < 3 || strlen($complaint_text) > 500) {
                        $skipped++;
                        continue;
                    }
                    
                    if (!preg_match("/^[\p{L}\p{N}\p{P}\p{S}\s]+$/u", $complaint_text)) {
                        $skipped++;
                        continue;
                    }
                    
                    // Check for duplicates
                    $checkStmt = $idr->prepare("SELECT COUNT(*) as count FROM comments WHERE comment_text = ?");
                    $checkStmt->bind_param("s", $complaint_text);
                    $checkStmt->execute();
                    $result = $checkStmt->get_result();
                    $row = $result->fetch_assoc();
                    $checkStmt->close();
                    
                    if ($row['count'] > 0) {
                        $skipped++;
                        continue;
                    }
                    
                    // Insert
                    $insertStmt = $idr->prepare("INSERT INTO comments (comment_text, comment_status) VALUES (?, 0)");
                    $insertStmt->bind_param("s", $complaint_text);
                    
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
    
    // Delete single complaint
    if (isset($_POST['delete_single'])) {
        $id = test_input($_POST['delete_id']);
        
        if (empty($id) || !is_numeric($id)) {
            $message = "Invalid ID!";
            $messageType = "error";
        } else {
            $deleteStmt = $idr->prepare("DELETE FROM comments WHERE id_co = ?");
            $deleteStmt->bind_param("i", $id);
            
            if ($deleteStmt->execute() && $deleteStmt->affected_rows > 0) {
                $message = "✓ Complaint deleted successfully!";
                $messageType = "success";
            } else {
                $message = "Complaint not found or already deleted!";
                $messageType = "error";
            }
            $deleteStmt->close();
        }
    }
    
    // Delete all complaints
    if (isset($_POST['delete_all_confirm'])) {
        mysqli_query($idr, "SET foreign_key_checks=0");
        $result = mysqli_query($idr, "TRUNCATE TABLE comments");
        mysqli_query($idr, "SET foreign_key_checks=1");
        mysqli_query($idr, "ALTER TABLE comments AUTO_INCREMENT=1");
        
        if ($result) {
            $message = "✓ All complaints deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to delete all complaints!";
            $messageType = "error";
        }
    }
    
    mysqli_close($idr);
}

// Handle export
if (isset($_GET['export'])) {
    $idr = getDBConnection();
    $result = mysqli_query($idr, "SELECT * FROM comments ORDER BY id_co ASC");
    
    $exportType = $_GET['export'];
    
    if ($exportType === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="complaints_' . date('Y-m-d_H-i-s') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'Complaint', 'Status'));
        
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, array($row['id_co'], $row['comment_text'], $row['comment_status']));
        }
        
        fclose($output);
        mysqli_close($idr);
        exit();
    }
}

// Get total count for dashboard
$idr = getDBConnection();
$countResult = mysqli_query($idr, "SELECT COUNT(*) as total FROM comments");
$totalComplaints = mysqli_fetch_assoc($countResult)['total'];
mysqli_close($idr);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaints Management System</title>
    
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
        .form-group input[type="file"],
        .form-group textarea {
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
        
        .form-group input[type="text"]:focus,
        .form-group input[type="file"]:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
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
        
        @media (max-width: 768px) {
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
            <i class="fas fa-clipboard-list"></i>
            Complaints Manager
        </h1>
        <div class="stats-badge">
            <i class="fas fa-database"></i> Total: <?php echo $totalComplaints; ?> Complaints
        </div>
    </div>
    
    <!-- Navigation Tabs -->
    <div class="nav-tabs">
        <a href="?action=view" class="<?php echo $action === 'view' ? 'active' : ''; ?>">
            <i class="fas fa-list"></i> View All
        </a>
        <a href="?action=add" class="<?php echo $action === 'add' ? 'active' : ''; ?>">
            <i class="fas fa-plus-circle"></i> Add New
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
            <!-- VIEW ALL COMPLAINTS -->
            <div class="export-buttons">
                <a href="?action=view&export=csv" class="btn btn-success">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            </div>
            
            <?php
            $idr = getDBConnection();
            $result = mysqli_query($idr, "SELECT * FROM comments ORDER BY id_co DESC");
            
            if (mysqli_num_rows($result) > 0):
            ?>
            
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Complaint</th>
                        <th style="width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($row = mysqli_fetch_assoc($result)) {
                        $id = $row['id_co'];
                        $text = htmlspecialchars($row['comment_text']);
                        
                        echo "<tr>";
                        echo "<td style='text-align:center; font-weight:600;'>{$id}</td>";
                        echo "<td>{$text}</td>";
                        echo "<td style='text-align:center;'>
                                <a href='?action=edit&id={$id}' class='btn btn-primary btn-small' style='margin-right: 5px;'>
                                    <i class='fas fa-edit'></i>
                                </a>
                                <form method='post' style='display:inline;'>
                                    <input type='hidden' name='delete_id' value='{$id}'>
                                    <button type='submit' name='delete_single' class='btn btn-danger btn-small' onclick='return confirm(\"Delete this complaint?\")'>
                                        <i class='fas fa-trash'></i>
                                    </button>
                                </form>
                              </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
            
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Complaints Found</h3>
                    <p>Start by adding a new complaint or importing from CSV</p>
                </div>
            <?php endif; ?>
            
            <?php mysqli_close($idr); ?>
            
        <?php elseif ($action === 'add'): ?>
            <!-- ADD NEW COMPLAINT -->
            <h2 style="margin-bottom: 30px; color: #2d3748;">
                <i class="fas fa-plus-circle"></i> Add New Complaint
            </h2>
            
            <form method="post">
                <div class="form-group">
                    <label for="complaint_text">
                        <i class="fas fa-comment-dots"></i> Complaint Text
                    </label>
                    <textarea name="complaint_text" id="complaint_text" required placeholder="Enter complaint text (3-500 characters)"></textarea>
                    <small style="color: #718096;">Minimum 3 characters, maximum 500 characters</small>
                </div>
                
                <div class="button-group">
                    <button type="submit" name="add_complaint" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Complaint
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('complaint_text').value=''; document.getElementById('complaint_text').focus();">
                        <i class="fas fa-eraser"></i> Clear
                    </button>
                </div>
            </form>
            
        <?php elseif ($action === 'edit'): ?>
            <!-- EDIT COMPLAINT -->
            <h2 style="margin-bottom: 30px; color: #2d3748;">
                <i class="fas fa-edit"></i> Edit Complaint
            </h2>
            
            <?php if ($editData): ?>
            <form method="post">
                <input type="hidden" name="complaint_id" value="<?php echo $editData['id_co']; ?>">
                
                <div class="form-group">
                    <label for="complaint_text">
                        <i class="fas fa-comment-dots"></i> Complaint Text
                    </label>
                    <textarea name="complaint_text" id="complaint_text" required placeholder="Enter complaint text (3-500 characters)"><?php echo htmlspecialchars($editData['comment_text']); ?></textarea>
                    <small style="color: #718096;">Minimum 3 characters, maximum 500 characters</small>
                </div>
                
                <div class="button-group">
                    <button type="submit" name="update_complaint" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Complaint
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
                <i class="fas fa-file-import"></i> Import Complaints from CSV
            </h2>
            
            <div class="info-box">
                <h4><i class="fas fa-info-circle"></i> CSV File Format</h4>
                <p><strong>Required columns:</strong></p>
                <p>• Column 1: ID (will be auto-generated, can be any value)</p>
                <p>• Column 2: Complaint text (3-500 characters)</p>
                <p>• Column 3: Status (optional, will default to 0)</p>
                <p style="margin-top: 10px;"><strong>Example:</strong></p>
                <p style="font-family: monospace; background: white; padding: 10px; border-radius: 5px;">
                    ID,Complaint,Status<br>
                    1,Customer reported billing issue,0<br>
                    2,Product defect complaint,0
                </p>
                <p style="margin-top: 10px; color: #856404; background: #fff3cd; padding: 8px; border-radius: 5px;">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Note:</strong> Duplicate complaints will be automatically skipped.
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
            <!-- DELETE ALL COMPLAINTS -->
            <div class="delete-confirm">
                <i class="fas fa-exclamation-triangle" style="font-size: 80px; margin-bottom: 20px;"></i>
                <h3>⚠️ CRITICAL WARNING ⚠️</h3>
                <p style="font-size: 20px; font-weight: 600;">Are you absolutely sure you want to delete ALL <?php echo $totalComplaints; ?> complaints?</p>
                <p>This action is PERMANENT and CANNOT be undone!</p>
                
                <div class="action-buttons">
                    <form method="post" style="display: inline;">
                        <button type="submit" name="delete_all_confirm" class="btn btn-danger" style="background: white; color: #dc3545; font-size: 18px; padding: 18px 35px;">
                            <i class="fas fa-trash-alt"></i> YES, Delete Everything
                        </button>
                    </form>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='?action=view'" style="font-size: 18px; padding: 18px 35px; background: rgba(255,255,255,0.2); color: white;">
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
                <p>