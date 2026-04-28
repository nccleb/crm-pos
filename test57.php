<?php
session_start();

// Fixed variable assignments - removed echo statements
$os = isset($_SESSION["o"]) ? $_SESSION["o"] : "";
$ps = isset($_SESSION["p"]) ? $_SESSION["p"] : "";

// Fixed session variable access
$sun = isset($_SESSION["sun"]) ? $_SESSION["sun"] : "";
?>

<!DOCTYPE html>
<html>
<head>
<?php include('head.php'); ?>
  <link rel="stylesheet" href="css/stylei.css">
  <link rel="stylesheet" href="css/stylei2.css">
   
  <link rel="stylesheet" href="css/whatsappButton.css" />
  <script src="js/test371.js"></script>
</head>

<body>
<?php
// Improved input validation function
function test_input($data) {
    if (!isset($data)) return '';
    $data = trim($data);
    $data = trim($data, "/");
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if form was submitted with proper validation
if (isset($_POST['ta']) && !empty($_POST['id']) && isset($_POST['id']) && 
    isset($_POST['la']) && isset($_POST['in']) && !empty($_POST['in']) && 
    isset($_POST['pr'])) {
    
    // Process form data
    // Your existing code
    $id = test_input($_POST['id']);

    // Add this line to keep numbers only
    $id = preg_replace('/[^0-9]/', '', $id);


    $ca = isset($_POST['ca']) ? test_input($_POST['ca']) : "";
    $ba = isset($_POST['ba']) ? test_input($_POST['ba']) : "";
    $lc = isset($_POST['lc']) ? test_input($_POST['lc']) : "";
    $la = test_input($_POST['la']);
    $in = test_input($_POST['in']);
    $st = isset($_POST['st']) ? test_input($_POST['st']) : "";
    $ca1 = isset($_POST['ca1']) ? test_input($_POST['ca1']) : "";
    $ta = test_input($_POST['ta']);
    $pr = test_input($_POST['pr']);

    // Initialize validation errors array
    $validation_errors = [];

    // FIXED VALIDATION PATTERNS - Using Unicode ranges instead of \p{Arabic}

    // Validate Priority
    if (!mb_check_encoding($pr, 'UTF-8')) {
        $validation_errors[] = "Invalid Priority encoding!";
    } elseif (!preg_match("/^[0-9a-zA-Z'?!=;~+%`\[\]()$*\"|:.,#&_\s\-\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]*$/u", $pr)) {
        $validation_errors[] = "Invalid Priority format!";
    }

    // Validate Task
    if (!mb_check_encoding($ta, 'UTF-8')) {
        $validation_errors[] = "Invalid Task encoding!";
    } elseif (!preg_match("/^[0-9a-zA-Z'?!=;~+%`\[\]()$*\"|:.,#&_\s\-\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]*$/u", $ta)) {
        $validation_errors[] = "Invalid Task format!";
    }

    // Validate Opportunity (ca1)
    if (!empty($ca1) && !preg_match("/^[a-zA-Z.,\s]*$/", $ca1)) {
        $validation_errors[] = "Invalid Opportunity format!";
    }

    // Validate Category
    if (!empty($ca) && !preg_match("/^[a-zA-Z.,\s]*$/", $ca)) {
        $validation_errors[] = "Invalid Category format!";
    }

    // Validate Last Activity
    if (!mb_check_encoding($la, 'UTF-8')) {
        $validation_errors[] = "Invalid Last Activity encoding!";
    } elseif (!preg_match("/^[0-9a-zA-Z'?!=;~+%`\[\]()$*\"|:.,#&_\s\-\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]*$/u", $la)) {
        $validation_errors[] = "Invalid Last Activity format!";
    }

    // Validate Incident
    if (!mb_check_encoding($in, 'UTF-8')) {
        $validation_errors[] = "Invalid Incident encoding!";
    } elseif (!preg_match("/^[0-9a-zA-Z'?!=;~+%`\[\]()$*\"|:.,#&_\s\-\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]*$/u", $in)) {
        $validation_errors[] = "Invalid Incident format!";
    }

    // Validate Status
    if (!empty($st) && !preg_match("/^[0-9a-zA-Z.,\s]*$/", $st)) {
        $validation_errors[] = "Invalid Status format!";
    }

    // Validate ID - improved number validation
    if (!preg_match("/^[\d.,\s\-]*$/", $id)) {
        $validation_errors[] = "Invalid ID format!";
    }

    // Display validation errors if any
    if (!empty($validation_errors)) {
        echo "<div style='color:red;font-size:28px;'>";
        echo "<p>Validation Errors:</p>";
        echo "<ul>";
        foreach ($validation_errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
        echo "<p style='font-size:14px;'>Debug Info:</p>";
        echo "<p style='font-size:12px;'>PHP Version: " . PHP_VERSION . "</p>";
        echo "<p style='font-size:12px;'>PCRE Version: " . PCRE_VERSION . "</p>";
        echo "<button id='id' type='button' onclick='quit()'>Quit</button>";
        echo "</div>";
        exit();
    }

    // Database connection
    $idr = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        exit();
    }

    // Set charset to handle Unicode properly
    mysqli_set_charset($idr, "utf8mb4");

    // First, find the client ID - improved query with proper parameter types
    $stmt = $idr->prepare("SELECT id FROM client WHERE number=? OR inumber=? OR telmobile=? OR telother=?");
    if (!$stmt) {
        echo "<script>alert('Database prepare error: " . mysqli_error($idr) . "');</script>";
        echo "<script>quit();</script>";
        exit();
    }

    // Bind as strings since the ID might contain non-numeric characters
    $stmt->bind_param("ssss", $id, $id, $id, $id);
    $stmt->execute();
    $req2 = $stmt->get_result();
    $stmt->close();

    $id1 = null;
    if ($req2) {
        while($lig = mysqli_fetch_assoc($req2)) {
            $id1 = $lig['id'];
            break; // Just get the first match
        }
    }

    // If client not found, show error
    if (!$id1) {
        echo "<script>alert('Client not found with ID: " . htmlspecialchars($id) . "');</script>";
        echo "<script>quit();</script>";
        mysqli_close($idr);
        exit();
    }

    // Insert into CRM - improved with error handling
    $stmt = $idr->prepare("INSERT INTO crm (task, la, incident, status, num, priority, id, idfc) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo "<script>alert('Database prepare error: " . mysqli_error($idr) . "');</script>";
        echo "<script>quit();</script>";
        mysqli_close($idr);
        exit();
    }

    $stmt->bind_param("ssssssss", $ta, $la, $in, $st, $id, $pr, $id1, $os);
    $insertSuccess = $stmt->execute();
    
    if (!$insertSuccess) {
        $error_msg = $stmt->error;
        $stmt->close();
        echo "<script>alert('Failed to insert data: " . htmlspecialchars($error_msg) . "');</script>";
        echo "<script>quit();</script>";
        mysqli_close($idr);
        exit();
    }

    $stmt->close();

    if ($insertSuccess) {
        // Send email only if insert was successful
        $to = "nccleb@gmail.com";
        $subject = "CRM Ticket - " . htmlspecialchars($ta);
        $txt = "Your ticket Name is: " . htmlspecialchars($ta) . "\r\n" .
               "Your Complaint is: " . htmlspecialchars($in) . "\r\n" .
               "Priority: " . htmlspecialchars($pr) . "\r\n" .
               "Status: " . htmlspecialchars($st) . "\r\n" .
               "Client ID: " . htmlspecialchars($id);
        $headers = "From: nccleb@gmail.com" . "\r\n" .
                   "CC: info@nccleb.com" . "\r\n" .
                   "Content-Type: text/plain; charset=UTF-8";
        
        // Try to send email, but don't fail if it doesn't work
        $mail_sent = mail($to, $subject, $txt, $headers);
        
        echo "<p id='p' style='color:green;font-size:20px;'>Data is well inserted!</p>";
        if (!$mail_sent) {
           // echo "<p style='color:orange;font-size:14px;'>Note: Email notification could not be sent.</p>";
        }
        echo "<a href='test56.php?page=" . urlencode($os) . "&page1=" . urlencode($ps) . "'>INSERT AGAIN</a><br/>";
        echo "<button id='id' type='button' onclick='quit()'>Quit</button>";
    }

    mysqli_close($idr);

} else {
    echo "<script>alert('Missing Entry!');</script>";
    echo "<script>quit();</script>";
}
?>
</body>
</html>