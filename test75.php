<?php
session_start();

// Initialize session variables with proper checks
$id = isset($_SESSION["id"]) ? $_SESSION["id"] : "";
$os = isset($_SESSION["os"]) ? $_SESSION["os"] : "";
$op = isset($_SESSION["op"]) ? $_SESSION["op"] : "";

// Initialize other variables that might be used in JavaScript
$naa = isset($_SESSION["naa"]) ? $_SESSION["naa"] : "";
$idf = isset($_SESSION["idf"]) ? $_SESSION["idf"] : "";
$inc = isset($_SESSION["inc"]) ? $_SESSION["inc"] : "";
$incc = isset($_SESSION["incc"]) ? $_SESSION["incc"] : "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <?php include('head.php'); ?>
<script>
function quit(){
	window.close();
}

function size(){
	window.resizeTo(600,900);
}

function add(){
	var myw;
	myw=window.open ("http://172.18.208.1//before.php?page=<?php echo urlencode($naa) ?>&page1=<?php echo urlencode($idf)?>&page2=<?php echo urlencode($inc) ?>","","menubar=0,resizable=1,width=680,height=950");
}
</script>

<style>
* {
    box-sizing: border-box;
}

@media only screen and (max-width: 1400px) {
    body {
        background-color: lightblue;
    }
}

#form{
    color: blue;
}

#for{
    color: red;
}
</style>
</head>

<body onload="size()">

<?php
// Check if all required POST fields are present
if (isset($_POST['la']) && isset($_POST['ur']) && isset($_POST['bu']) && isset($_POST['pr'])) {
    
    // Improved input sanitization function
    function test_input($data) {
        if (!isset($data)) return '';
        $data = trim($data);
        $data = trim($data, "/");
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    
    // Process form data with proper checks
    $la = test_input($_POST['la']);
    $ur = test_input($_POST['ur']);
    $bu = test_input($_POST['bu']);
    $na = isset($_POST['na']) ? test_input($_POST['na']) : "";
    $co = isset($_POST['co']) ? test_input($_POST['co']) : "";
    $tas = isset($_POST['ta']) ? test_input($_POST['ta']) : "";
    $pr = test_input($_POST['pr']);

    // Initialize validation errors array
    $validation_errors = [];

    // FIXED VALIDATION PATTERNS - Using Unicode ranges instead of \p{Arabic}
    $arabic_pattern = "/^[0-9a-zA-Z'?!=;~+%`\[\]()$*\"|:.,#&_\s\-\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]*$/u";
    $simple_arabic_pattern = "/^[a-zA-Z'?!=;~+%`\[\]()$*\"|:.,#&_\s\-\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]*$/u";

    // Validate Priority
    if (!mb_check_encoding($pr, 'UTF-8')) {
        $validation_errors[] = "Invalid Priority encoding!";
    } elseif (!preg_match($simple_arabic_pattern, $pr)) {
        $validation_errors[] = "Invalid Priority format!";
    }

    // Validate Company/Contact
    if (!empty($co)) {
        if (!mb_check_encoding($co, 'UTF-8')) {
            $validation_errors[] = "Invalid Company encoding!";
        } elseif (!preg_match($arabic_pattern, $co)) {
            $validation_errors[] = "Invalid Company format!";
        }
    }

    // Validate Last Contacted
    if (!empty($na)) {
        if (!mb_check_encoding($na, 'UTF-8')) {
            $validation_errors[] = "Invalid Last Contacted encoding!";
        } elseif (!preg_match($arabic_pattern, $na)) {
            $validation_errors[] = "Invalid Last Contacted format!";
        }
    }

    // Validate Task
    if (!empty($tas)) {
        if (!mb_check_encoding($tas, 'UTF-8')) {
            $validation_errors[] = "Invalid Task encoding!";
        } elseif (!preg_match($arabic_pattern, $tas)) {
            $validation_errors[] = "Invalid Task format!";
        }
    }

    // Validate Last Activity
    if (!mb_check_encoding($la, 'UTF-8')) {
        $validation_errors[] = "Invalid Last Activity encoding!";
    } elseif (!preg_match($arabic_pattern, $la)) {
        $validation_errors[] = "Invalid Last Activity format!";
    }

    // Validate Comment/URL
    if (!mb_check_encoding($ur, 'UTF-8')) {
        $validation_errors[] = "Invalid Comment encoding!";
    } elseif (!preg_match($arabic_pattern, $ur)) {
        $validation_errors[] = "Invalid Comment format!";
    }

    // Validate Status/Business
    if (!mb_check_encoding($bu, 'UTF-8')) {
        $validation_errors[] = "Invalid Status encoding!";
    } elseif (!preg_match($arabic_pattern, $bu)) {
        $validation_errors[] = "Invalid Status format!";
    }

    // Display validation errors if any
    if (!empty($validation_errors)) {
        echo "<div style='color:red;font-size:20px;'>";
        echo "<p>Validation Errors:</p>";
        echo "<ul>";
        foreach ($validation_errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
        echo "<p style='font-size:14px;'>Debug Info:</p>";
        echo "<p style='font-size:12px;'>PHP Version: " . PHP_VERSION . "</p>";
        echo "<p style='font-size:12px;'>PCRE Version: " . PCRE_VERSION . "</p>";
        echo "<button id='form' type='button' onclick='quit()'>Quit</button>";
        echo "</div>";
        exit();
    }

    // Database connection
    $idr = mysqli_connect("172.18.208.1", "root", "1Sys9Admeen72", "nccleb_test");
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        exit();
    }

    // Set charset for Unicode support
    mysqli_set_charset($idr, "utf8mb4");

    // Find and update CRM record
    $stmt = $idr->prepare("SELECT * FROM client c, crm cr WHERE c.id = cr.id AND (number=? OR inumber=? OR telmobile=? OR telother=?) ORDER BY idc DESC LIMIT 1");
    
    if (!$stmt) {
        echo "Database prepare error: " . mysqli_error($idr);
        echo "<button id='form' type='button' onclick='quit()'>Quit</button>";
        exit();
    }

    // Bind as strings for flexibility
    $stmt->bind_param("ssss", $id, $id, $id, $id);
    $stmt->execute();
    $req2 = $stmt->get_result();
    $stmt->close();

    $test = 0;
    $record_found = false;

    if ($req2) {
        while($lig = mysqli_fetch_assoc($req2)) {
            $record_found = true;
            $idc = $lig['idc'];
            
            // Check if the ID matches any of the phone numbers
            if ($id == $lig['number'] || $id == $lig['inumber'] || 
                $id == $lig['telmobile'] || $id == $lig['telother']) {
                
                // Update CRM record
                $update_stmt = $idr->prepare("UPDATE crm SET la=?, incident=?, status=?, lcd=?, task=?, priority=? WHERE idc=?");
                
                if (!$update_stmt) {
                    echo "Database prepare error: " . mysqli_error($idr);
                    echo "<button id='form' type='button' onclick='quit()'>Quit</button>";
                    exit();
                }

                $update_stmt->bind_param("ssssssi", $la, $ur, $bu, $na, $tas, $pr, $idc);
                
                if ($update_stmt->execute()) {
                    $test = mysqli_affected_rows($idr);
                } else {
                    echo "Update error: " . $update_stmt->error;
                }
                
                $update_stmt->close();
                break;
            }
        }
    }

    // Handle results
    if (!$record_found) {
        echo "<script>
        var r = confirm('No records found for ID: " . htmlspecialchars($id) . "! Press OK to retry');
        if (r == true) {
            location.replace('http://192.168.20.201//before.php?page=" . urlencode($naa) . "&page1=" . urlencode($idf) . "&page2=" . urlencode($incc) . "');
        } else {
            window.close();
        }
        </script>";
    } elseif ($test == 0) {
        echo "<script>
        var r = confirm('No changes made! Press OK to retry');
        if (r == true) {
            location.replace('http://192.168.20.201//before.php?page=" . urlencode($naa) . "&page1=" . urlencode($idf) . "&page2=" . urlencode($incc) . "');
        } else {
            window.close();
        }
        </script>";
    } elseif ($test > 0) {
        echo "<p id='form' style='color:green;font-size:20px;'>Data is well updated!</p>";
    } else {
        echo "<p id='form' style='color:red;font-size:20px;'>Data is not updated!</p>";
    }

    echo "<button id='form' type='button' onclick='add()'>TRY AGAIN</button>";
    echo "<button id='form' type='button' onclick='quit()'>Quit</button>";

    mysqli_close($idr);

} else {
    echo "<script>alert('Missing Entry!');</script>";
    echo "<script>location.replace('before.php');</script>";
}
?>

</body>
</html>