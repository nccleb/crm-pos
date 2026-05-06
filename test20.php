<?php
session_start();

// Database connection
$host = "172.18.208.1";
$user = "root";
$pass = "1Sys9Admeen72";
$db = "nccleb_test";

$idr = mysqli_connect($host, $user, $pass, $db);
if (mysqli_connect_errno()) {
    echo "<script>alert('Database connection failed!');history.back();</script>";
    exit();
}

// Get form data
$nu  = trim($_POST['nu'] ?? '');
$inu = trim($_POST['inu'] ?? '');
$tel = trim($_POST['tel'] ?? '');
$oth = trim($_POST['oth'] ?? '');
$em  = trim($_POST['em'] ?? '');
$na  = trim($_POST['na'] ?? '');
$lna = trim($_POST['lna'] ?? '');
$co  = trim($_POST['co'] ?? '');
$job = trim($_POST['job'] ?? '');
$bu  = trim($_POST['bu'] ?? '');

$decision_authority = trim($_POST['decision_authority'] ?? '');
$communication_style = trim($_POST['communication_style'] ?? '');
$customer_priority = trim($_POST['customer_priority'] ?? '');
$key_preferences = trim($_POST['key_preferences'] ?? '');

$ur = trim($_POST['ur'] ?? '');
$cit = trim($_POST['cit'] ?? '');
$zon = trim($_POST['zon'] ?? '');
$str = trim($_POST['str'] ?? '');
$bui = trim($_POST['bui'] ?? '');
$apa = trim($_POST['apa'] ?? '');
$flo = trim($_POST['flo'] ?? '');
$nea = trim($_POST['nea'] ?? '');
$ad = trim($_POST['ad'] ?? '');
$ad2 = trim($_POST['ad2'] ?? '');
$delti = trim($_POST['delti'] ?? '');

$cat = trim($_POST['cat'] ?? '');
$blog = trim($_POST['blog'] ?? '');
$gra = trim($_POST['gra'] ?? '');
$pay = trim($_POST['pay'] ?? '');
$loy = trim($_POST['loy'] ?? '');
$com = trim($_POST['com'] ?? '');
$rem = trim($_POST['rem'] ?? '');

// Validate required fields
if (empty($nu) || empty($na) || empty($lna)) {
    echo "<script>alert('Error: Required fields (Number, First Name, Last Name) cannot be empty!');history.back();</script>";
    exit();
}

// 🔍 Check duplicates
$numbers_to_check = [
    'Main Number' => $nu,
    'Internal Number' => $inu,
    'Mobile' => $tel,
    'Other' => $oth
];

foreach ($numbers_to_check as $label => $number) {
    if (empty($number)) continue;

    $check_query = "
        SELECT field_name, nom, prenom FROM (
            SELECT 'number' AS field_name, nom, prenom, number AS val FROM client
            UNION ALL
            SELECT 'inumber', nom, prenom, inumber FROM client
            UNION ALL
            SELECT 'telmobile', nom, prenom, telmobile FROM client
            UNION ALL
            SELECT 'telother', nom, prenom, telother FROM client
        ) t
        WHERE TRIM(val) <> '' AND val IS NOT NULL AND val = ?
        LIMIT 1
    ";

    $stmt_check = mysqli_prepare($idr, $check_query);
    mysqli_stmt_bind_param($stmt_check, "s", $number);
    mysqli_stmt_execute($stmt_check);
    $result = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($result) > 0) {
        $dup = mysqli_fetch_assoc($result);
        $dup_field = htmlspecialchars($dup['field_name']);
        $dup_name = htmlspecialchars($dup['nom'] . ' ' . $dup['prenom']);
        $dup_msg = htmlspecialchars("Duplicate Found!\n\nInput: $label ($number)\nAlready in: $dup_field ($dup_name)");

        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Duplicate Found</title>
            <style>
                body {
                    font-family: 'Segoe UI', sans-serif;
                    background: linear-gradient(135deg, #ff758c 0%, #ff7eb3 100%);
                    height: 100vh;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    margin: 0;
                }
                .popup {
                    background: white;
                    border-radius: 20px;
                    padding: 40px;
                    text-align: center;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    max-width: 500px;
                    animation: fadeIn 0.4s ease-in-out;
                }
                .icon {
                    font-size: 70px;
                    color: #ef4444;
                    margin-bottom: 15px;
                }
                h1 {
                    color: #111827;
                    margin-bottom: 10px;
                }
                p {
                    color: #4b5563;
                    white-space: pre-line;
                    font-size: 15px;
                }
                button {
                    margin-top: 20px;
                    background: #ef4444;
                    color: white;
                    border: none;
                    padding: 10px 25px;
                    border-radius: 10px;
                    cursor: pointer;
                    font-size: 15px;
                }
                button:hover {
                    background: #dc2626;
                }
                @keyframes fadeIn {
                    from {opacity: 0; transform: translateY(-20px);}
                    to {opacity: 1; transform: translateY(0);}
                }
            </style>
        </head>
        <body>
            <div class='popup'>
                <div class='icon'>⚠️</div>
                <h1>Duplicate Found</h1>
                <p>$dup_msg</p>
                <button onclick='history.back()'>Go Back</button>
            </div>
        </body>
        </html>";
        exit();
    }

    mysqli_stmt_close($stmt_check);
}

// ✅ Insert new record
$sql = "INSERT INTO client (
    number, inumber, telmobile, telother, email,
    nom, prenom, company, job, business,
    decision_authority, communication_style, customer_priority, key_preferences,
    google_maps_url, city, zone, street, building, apartment, floor, near,
    address, address_two, best_delivery_time,
    category, source, grade, payment, card, community, remark,
    idf, idx
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($idr, $sql);
$idf = 1;
$idx = 1;

mysqli_stmt_bind_param($stmt, "ssssssssssssssssssssssssssssssssii",
    $nu, $inu, $tel, $oth, $em,
    $na, $lna, $co, $job, $bu,
    $decision_authority, $communication_style, $customer_priority, $key_preferences,
    $ur, $cit, $zon, $str, $bui, $apa, $flo, $nea,
    $ad, $ad2, $delti,
    $cat, $blog, $gra, $pay, $loy, $com, $rem,
    $idf, $idx
);

if (mysqli_stmt_execute($stmt)) {
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Success</title>
        <style>
            body {
                font-family: 'Segoe UI', sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .popup {
                background: white;
                padding: 40px;
                border-radius: 20px;
                text-align: center;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 500px;
                animation: fadeIn 0.5s ease-in-out;
            }
            .icon {
                font-size: 70px;
                color: #10b981;
                margin-bottom: 20px;
            }
            h1 {color: #111827; margin-bottom: 10px;}
            p {color: #6b7280; margin-bottom: 20px; font-size: 16px;}
            .count {color: #374151; font-weight: bold;}
            @keyframes fadeIn {
                from {opacity: 0; transform: translateY(-20px);}
                to {opacity: 1; transform: translateY(0);}
            }
        </style>
    </head>
    <body>
        <div class='popup'>
            <div class='icon'>✅</div>
            <h1>Customer Added Successfully!</h1>
            <p>Redirecting in <span class='count' id='timer'>5</span> seconds...</p>
        </div>
        <script>
            let t = 5;
            const timer = document.getElementById('timer');
            const interval = setInterval(() => {
                t--;
                timer.textContent = t;
                if (t <= 0) {
                    clearInterval(interval);".
                    //window.location.href = 'test321.php';
                    header("Location: test321.php").
                    
              "  }
            }, 1000);
        </script>
    </body>
    </html>";
} else {
    echo "<script>alert('Error adding customer: " . addslashes(mysqli_stmt_error($stmt)) . "');history.back();</script>";
}

mysqli_stmt_close($stmt);
mysqli_close($idr);
?>
