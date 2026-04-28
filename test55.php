<?php
session_start();

// Initialize session variables
$id = isset($_SESSION["id"]) ? $_SESSION["id"] : "";
$idf = isset($_SESSION["idf"]) ? $_SESSION["idf"] : "";
$naa = isset($_SESSION["naa"]) ? $_SESSION["naa"] : "";
$inc = isset($_SESSION["q"]) ? $_SESSION["q"] : "";
$incc = isset($_SESSION["a3"]) ? $_SESSION["a3"] : "";

// Process form data
function test_input($data) {
    if (!isset($data)) return '';
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check essential fields
$essential_fields = ['nu', 'na', 'lna'];
$all_essential_present = true;
$missing_fields = [];

foreach ($essential_fields as $field) {
    if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
        $all_essential_present = false;
        $missing_fields[] = $field;
    }
}

if (!$all_essential_present) {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Missing Information - Client Management</title>
        <style>
            body { 
                font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0; padding: 20px; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            }
            .card {
                background: white; border-radius: 16px; padding: 40px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                max-width: 500px; width: 100%; text-align: center;
            }
            .icon { font-size: 64px; margin-bottom: 20px; }
            .btn { 
                background: #667eea; color: white; border: none; padding: 12px 30px; 
                border-radius: 8px; cursor: pointer; font-size: 16px; margin: 10px;
                transition: all 0.3s ease;
            }
            .btn:hover { background: #5a6fd8; transform: translateY(-2px); }
            .btn-secondary { background: #6c757d; }
            .btn-secondary:hover { background: #5a6268; }
        </style>
    </head>
    <body>
        <div class='card'>
            <div class='icon'>❌</div>
            <h2 style='color: #e74c3c; margin-bottom: 10px;'>Missing Required Information</h2>
            <p style='color: #666; margin-bottom: 25px;'>The following essential fields are required:</p>
            <ul style='text-align: left; background: #f8f9fa; padding: 15px; border-radius: 8px;'>
    ";
    foreach ($missing_fields as $field) {
        echo "<li style='padding: 5px 0;'><strong>" . htmlspecialchars($field) . "</strong></li>";
    }
    echo "
            </ul>
            <div style='margin-top: 30px;'>
                <button class='btn' onclick='history.back()'>← Go Back & Fill</button>
                <button class='btn btn-secondary' onclick='window.close()'>Close</button>
            </div>
        </div>
    </body>
    </html>";
    exit();
}

// Set default values for all fields
$all_possible_fields = [
    'nu', 'na', 'lna', 'company', 'inu', 'tel', 'oth', 'ur', 'bu', 'ad', 'ad2', 'em',
    'cit', 'str', 'flo', 'delti', 'bui', 'zon', 'nea', 'rem', 'apa', 'grad', 'driver',
    'pay', 'loy', 'disa', 'job', 'cat', 'src', 'community', 'pho', 'decision_authority',
    'communication_style', 'customer_priority', 'key_preferences'
];

foreach ($all_possible_fields as $field) {
    if (!isset($_POST[$field])) {
        $_POST[$field] = '';
    }
}

// Process all form data
$nu = test_input($_POST['nu']);
$na = test_input($_POST['na']);
$lna = test_input($_POST['lna']);
$cat = test_input($_POST['cat']);
$src = test_input($_POST['src']);
$company = test_input($_POST['company']);
$gra = test_input($_POST['grad']);
$pay = test_input($_POST['pay']);
$loy = test_input($_POST['loy']);
$community = test_input($_POST['community']);
$ci = test_input($_POST['cit']);
$zo = test_input($_POST['zon']);
$st = test_input($_POST['str']);
$fl = test_input($_POST['flo']);
$ad = test_input($_POST['ad']);
$ad2 = test_input($_POST['ad2']);
$re = test_input($_POST['rem']);
$customer_priority = test_input($_POST['customer_priority']);
$key_preferences = test_input($_POST['key_preferences']);
$decision_authority = test_input($_POST['decision_authority']);
$communication_style = test_input($_POST['communication_style']);

// Database connection
$idr = mysqli_connect("192.168.1.101", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($idr, "utf8mb4");

// FIND CLIENT
$client_id = null;
$current_data = [];
if (!empty($nu)) {
    $stmt = $idr->prepare("SELECT id, nom, prenom, category, source, company, grade FROM client WHERE number = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $nu);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $current_data = $result->fetch_assoc();
            $client_id = $current_data['id'];
        }
        $stmt->close();
    }
}

if (!$client_id) {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Client Not Found</title>
        <style>
            body { 
                font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
                background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
                margin: 0; padding: 20px; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            }
            .card {
                background: white; border-radius: 16px; padding: 40px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                max-width: 500px; width: 100%; text-align: center;
            }
            .icon { font-size: 64px; margin-bottom: 20px; }
            .btn { 
                background: #667eea; color: white; border: none; padding: 12px 30px; 
                border-radius: 8px; cursor: pointer; font-size: 16px; margin: 10px;
                transition: all 0.3s ease;
            }
            .btn:hover { background: #5a6fd8; transform: translateY(-2px); }
        </style>
    </head>
    <body>
        <div class='card'>
            <div class='icon'>🔍</div>
            <h2 style='color: #e74c3c; margin-bottom: 10px;'>Client Not Found</h2>
            <p style='color: #666; margin-bottom: 25px;'>No client found with number: <strong>$nu</strong></p>
            <p style='color: #666;'>Please check the number or use the Add Client function.</p>
            <div style='margin-top: 30px;'>
                <button class='btn' onclick='window.close()'>Close</button>
            </div>
        </div>
    </body>
    </html>";
    mysqli_close($idr);
    exit();
}

// PERFORM UPDATE
$update_success = false;
$affected_rows = 0;

$sql = "UPDATE client SET 
    nom = ?, prenom = ?, category = ?, source = ?, company = ?, grade = ?, 
    payment = ?, card = ?, community = ?, city = ?, zone = ?, street = ?, 
    floor = ?, address = ?, address_two = ?, remark = ?, customer_priority = ?, 
    key_preferences = ?, decision_authority = ?, communication_style = ? 
    WHERE id = ?";

$stmt = $idr->prepare($sql);

if ($stmt) {
    $floor_value = (empty($fl) || $fl === '') ? NULL : (int)$fl;
    
    $stmt->bind_param("ssssssssssssisssssssi", 
        $na, $lna, $cat, $src, $company, $gra, $pay, $loy, $community, 
        $ci, $zo, $st, $floor_value, $ad, $ad2, $re, $customer_priority, 
        $key_preferences, $decision_authority, $communication_style,
        $client_id);
    
    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        $update_success = true;
    }
    $stmt->close();
}

mysqli_close($idr);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Successful - Client Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            max-width: 600px;
            width: 100%;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            text-align: center;
        }

        .status-icon {
            font-size: 100px;
            margin-bottom: 30px;
            animation: bounce 1s ease-in-out;
        }

        .status-title {
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .status-subtitle {
            color: #666;
            font-size: 1.2em;
            margin-bottom: 40px;
            line-height: 1.5;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .btn {
            padding: 15px 35px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-20px);
            }
            60% {
                transform: translateY(-10px);
            }
        }

        @media (max-width: 768px) {
            .card {
                padding: 30px 25px;
                margin: 10px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                justify-content: center;
                padding: 12px 25px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="status-icon">🎉</div>
            <h1 class="status-title">Update Successful!</h1>
            <p class="status-subtitle">Client information has been successfully updated in the system.</p>
            
            <div class="action-buttons">
                <button class="btn btn-success" onclick="window.open('http://192.168.1.101/test275.php?page=<?php echo urlencode($naa); ?>&page1=<?php echo urlencode($idf); ?>&page2=<?php echo urlencode($inc); ?>','','menubar=0,resizable=1,width=680,height=950')">
                    <span>✏️</span> Edit Another Client
                </button>
                <button class="btn btn-secondary" onclick="window.close()">
                    <span>❌</span> Close Window
                </button>
            </div>
        </div>
    </div>

    <script>
        function size() {
            window.resizeTo(700, 600);
        }
        window.onload = size;
    </script>
</body>
</html>