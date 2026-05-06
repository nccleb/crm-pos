<?php
session_start();


$inc2 = $_GET["number"] ?? '';
$inc = "0";

$opic = "c" . ":" . "\\" . "Mdr" . "\\" . "CallerID" . date("Y") . "-" . date("m") . "." . "txt";

// Initialize variables for the form
$inc = "";
$lineNum = "";

// Read from CaCallStatus.dat
$fichier = "CaCallStatus.dat";
if (file_exists($fichier)) {
    $xml = simplexml_load_file($fichier);
    if ($xml) {
        foreach ($xml as $CallRecord) {
            if (isset($CallRecord->ext)) {
                $ext = $CallRecord->ext;
            }
            if (isset($CallRecord->CallerID)) {
                $inc = (string)$CallRecord->CallerID;
            }
        }
    }
}

// Read the last line from the caller ID file
if (file_exists($opic)) {
    $f = fopen($opic, 'r');
    if ($f) {
        $line = '';
        $cursor = -1;
        fseek($f, $cursor, SEEK_END);
        $char = fgetc($f);
        
        // Trim trailing newline characters
        while ($char === "\n" || $char === "\r") {
            fseek($f, $cursor--, SEEK_END);
            $char = fgetc($f);
        }
        
        // Read until the next line begins
        while ($char !== false && $char !== "\n" && $char !== "\r") {
            $line = $char . $line;
            fseek($f, $cursor--, SEEK_END);
            $char = fgetc($f);
        }
        
        $inc = substr($line, 49, 8);
        $linenum = substr($line, 25, 8);
        $inc = trim($inc);
        $lineNum = trim($linenum);
        
        fclose($f);
    }
}


//include('test449.php');
//If no caller ID from XML, try to get from session

    $inc = $_SESSION["userinc"];


// Database connection
$idr = mysqli_connect("172.18.208.1", "root", "1Sys9Admeen72", "nccleb_test");
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}

// ============================================
// FETCH AND DISPLAY CLIENT INFORMATION
// ============================================
$result = mysqli_query($idr, "SELECT * FROM client");

while ($lig = mysqli_fetch_assoc($result)) {
    if ($inc != "" && $inc != 0) {
        // Check if incoming number matches any client number
        if ($inc == $lig['number'] || 
            $inc == $lig['inumber'] || 
            $inc == $lig['telmobile'] || 
            $inc == $lig['telother']) {
            
            // Store client ID for CRM lookup
            $client_id = $lig['id'];
            
            // Display full client information
            $output = [];

            // ============================================
            // ADDRESS INFORMATION
            // ============================================
            if (!empty($lig['address'])) {
                $output[] = "🏠 First address: " . $lig['address'];
            }
            
            if (!empty($lig['address_two'])) {
                $output[] = "🏠 Second address: " . $lig['address_two'];
            }
            
            if (!empty($lig['city'])) {
                $output[] = "🌆 City: " . $lig['city'];
            }
            
            if (!empty($lig['zone'])) {
                $output[] = "🗺️ Zone: " . $lig['zone'];
            }
            
            if (!empty($lig['street'])) {
                $output[] = "🛣️ Street: " . $lig['street'];
            }
            
            if (!empty($lig['building'])) {
                $output[] = "🏗️ Building: " . $lig['building'];
            }
            
            if (!empty($lig['floor']) && $lig['floor'] != "0") {
                $output[] = "🔼 Floor: " . $lig['floor'];
            }
            
            if (!empty($lig['apartment'])) {
                $output[] = "🚪 Apartment: " . $lig['apartment'];
            }
            
            if (!empty($lig['google_maps_url'])) {
                $output[] = "📍 Google Maps URL: " . $lig['google_maps_url'];
            }
            
            if (!empty($lig['best_delivery_time'])) {
                $output[] = "⏰ Best delivery time: " . $lig['best_delivery_time'];
            }
            
            
            // ============================================
            // STANDARD CONTACT & BUSINESS INFO
            // ============================================
            if (!empty($lig['category'])) {
                $output[] = "📂 Category: " . $lig['category'];
            }
            
            if (!empty($lig['source'])) {
                $output[] = "📌 Source: " . $lig['source'];
            }
            
            if (!empty($lig['company'])) {
                $output[] = "🏢 Company: " . $lig['company'];
            }
            
            if (!empty($lig['job'])) {
                $output[] = "💼 Job Title: " . $lig['job'];
            }
            
            if (!empty($lig['grade']) && $lig['grade'] != "regular") {
                $output[] = "👑 Grade: " . strtoupper($lig['grade']);
            }
            
            // ============================================
            // CONTACT DETAILS
            // ============================================
            if (!empty($lig['inumber'])) {
                $output[] = "☎️ Tel(Office): " . $lig['inumber'];
            }
            
            if (!empty($lig['telmobile'])) {
                $output[] = "📱 Tel(Mobile): " . $lig['telmobile'];
            }
            
            if (!empty($lig['telother'])) {
                $output[] = "📞 Tel(Other): " . $lig['telother'];
            }
            
            if (!empty($lig['email'])) {
                $output[] = "📧 Email: " . $lig['email'];
            }
            
            
            // ============================================
            // BUSINESS & PREFERENCES
            // ============================================
            if (!empty($lig['business'])) {
                $output[] = "💼 Business: " . $lig['business'];
            }
            
            
            if (!empty($lig['delivery_instructions'])) {
                $output[] = "📦 Delivery Instructions: " . $lig['delivery_instructions'];
            }
            
            if (!empty($lig['payment'])) {
                $output[] = "💳 Type of payment: " . $lig['payment'];
            }
            
            if (!empty($lig['card'])) {
                $output[] = "🎫 Loyalty card: " . $lig['card'];
            }
            
            if (!empty($lig['community'])) {
                $output[] = "👥 Joined community: " . $lig['community'];
            }
            
            // ============================================
            // NOTES (Last - Most Important)
            // ============================================
            if (!empty($lig['remark'])) {
                $output[] = "---";
                $output[] = "📌 NOTES: " . $lig['remark'];
            }
            
            // ============================================
            // CUSTOMER INTELLIGENCE SECTION
            // ============================================
            $intelligence_data = [];
            
            if (!empty($lig['decision_authority'])) {
                $intelligence_data[] = "🎯 Decision Authority: " . $lig['decision_authority'];
            }
            
            if (!empty($lig['communication_style'])) {
                $intelligence_data[] = "💬 Communication Style: " . $lig['communication_style'];
            }
            
            if (!empty($lig['customer_priority'])) {
                $intelligence_data[] = "⭐ Customer Priority: " . $lig['customer_priority'];
            }
            
            if (!empty($lig['key_preferences'])) {
                $intelligence_data[] = "📝 Key Preferences: " . $lig['key_preferences'];
            }
            
            // Add intelligence data if exists
            if (count($intelligence_data) > 0) {
                $output[] = "---";
                $output = array_merge($output, $intelligence_data);
            }
            
            // ============================================
            // CRM TICKET INFORMATION
            // ============================================
            // Fetch CRM tickets for this client
            $crm_query = "SELECT task, incident, status, priority, la, lcd 
                         FROM crm 
                         WHERE id = ? 
                         ORDER BY lcd DESC 
                         LIMIT 5";
            
            $crm_stmt = mysqli_prepare($idr, $crm_query);
            mysqli_stmt_bind_param($crm_stmt, "i", $client_id);
            mysqli_stmt_execute($crm_stmt);
            $crm_result = mysqli_stmt_get_result($crm_stmt);
            
            $ticket_count = mysqli_num_rows($crm_result);
            
            if ($ticket_count > 0) {
                $output[] = "---";
                $output[] = "🎫 CRM TICKETS (" . $ticket_count . " recent ticket(s)):";
                $output[] = "";
                
                while ($crm = mysqli_fetch_assoc($crm_result)) {
                    // Use task column as ticket name
                    if (!empty($crm['task'])) {
                        $output[] = $crm['task'] . ":";
                    }
                    
                    // Complaint in RED (using red circle emoji)
                    if (!empty($crm['incident'])) {
                        $output[] = "🔴 Complaint: " . $crm['incident'];
                    }
                    
                    if (!empty($crm['status'])) {
                        $output[] = "📊 Status: " . $crm['status'];
                    }
                    
                    if (!empty($crm['priority'])) {
                        $output[] = "⚡ Priority: " . $crm['priority'];
                    }
                    
                    // Use 'la' column for last activity
                    if (!empty($crm['la'])) {
                        $output[] = "📋 Last Action: " . $crm['la'];
                    }
                    
                    // Use 'lcd' column for last contacted
                    if (!empty($crm['lcd'])) {
                        $output[] = "📞 Last Contacted: " . $crm['lcd'];
                    }
                    
                    $output[] = "";
                }
            }
            
            mysqli_stmt_close($crm_stmt);
            
            // Output all information
            echo implode(" \r\n ", $output);
            exit();
        }
    }
}

mysqli_close($idr);
?>