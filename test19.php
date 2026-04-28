<?php 
// Fixed file path construction
$opic = "c:\\Mdr\\CallerID" . date("Y") . "-" . date("m") . ".txt";
?>

<?php
session_start();

// Initialize variables to avoid undefined index warnings
$s = isset($_SESSION["ses"]) ? $_SESSION["ses"] : "";
$C = isset($_COOKIE["user"]) ? $_COOKIE["user"] : "";
$o = isset($_GET['page']) ? urldecode($_GET['page']) : "";
$p = isset($_GET['page1']) ? urldecode($_GET['page1']) : "";
$n = isset($_GET['page2']) ? urldecode($_GET['page2']) : "";

$_SESSION["q"] = $n;
$_SESSION["o"] = $o;
$_SESSION["p"] = $p;
$_SESSION["sos"] = $s;

// Initialize variables for the form
$inc = ""; // Initialize caller ID variable

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

// If no caller ID from XML, try to get from session
//if (empty($inc) && isset($_SESSION["userinc"])) {
    //$inc = $_SESSION["userinc"];
//}

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add New Customer</title>
  
  <?php include('head.php'); ?>
  <link rel="stylesheet" href="css/stylei.css">
  <link rel="stylesheet" href="css/stylei2.css">
  <link rel="stylesheet" href="css/whatsappButton.css" />
  
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    body {
      font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      padding: 20px;
    }
    
    .container {
      max-width: 1400px;
      margin: 0 auto;
      background: white;
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      overflow: hidden;
    }
    
    .header {
      background: linear-gradient(135deg, #4a4a9c 0%, #667eea 100%);
      color: white;
      padding: 30px 40px;
      text-align: center;
    }
    
    .header h1 {
      font-size: 32px;
      font-weight: 700;
      margin-bottom: 10px;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
    }
    
    .header p {
      font-size: 16px;
      opacity: 0.9;
    }
    
    .form-container {
      padding: 40px;
    }
    
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 40px;
    }
    
    @media (max-width: 968px) {
      .form-grid {
        grid-template-columns: 1fr;
      }
    }
    
    .form-section {
      background: #f8f9fa;
      padding: 25px;
      border-radius: 15px;
      border-left: 4px solid #4a4a9c;
    }
    
    .form-section h3 {
      color: #4a4a9c;
      font-size: 20px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .form-section h3::before {
      content: '';
      width: 8px;
      height: 8px;
      background: #4a4a9c;
      border-radius: 50%;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group {
      display: flex;
      flex-direction: column;
    }
    
    .form-group label {
      font-weight: 600;
      color: #2c3e50;
      margin-bottom: 8px;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 5px;
    }
    
    .form-group label:after {
      content: ':';
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
      padding: 12px 15px;
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      font-size: 14px;
      transition: all 0.3s;
      background: white;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #007bff;
      box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
      transform: translateY(-2px);
    }
    
    .form-group textarea {
      resize: vertical;
      min-height: 100px;
      font-family: inherit;
    }
    
    .form-group.full-width {
      grid-column: 1 / -1;
    }
    
    .form-section {
      grid-column: 1 / -1;
      margin: 20px 0 10px 0;
      padding-bottom: 10px;
      border-bottom: 2px solid #007bff;
      color: #007bff;
      font-weight: 600;
      font-size: 18px;
    }
    
    .form-control {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid #e5e7eb;
      border-radius: 10px;
      font-size: 15px;
      transition: all 0.3s ease;
      background: white;
    }
    
    .form-control:focus {
      outline: none;
      border-color: #4a4a9c;
      box-shadow: 0 0 0 3px rgba(74, 74, 156, 0.1);
    }
    
    .form-control:hover {
      border-color: #9ca3af;
    }
    
    select.form-control {
      cursor: pointer;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%234a4a9c' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      padding-right: 36px;
    }
    
    textarea.form-control {
      resize: vertical;
      min-height: 100px;
      font-family: inherit;
    }
    
    .intelligence-section {
      background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
      padding: 25px;
      border-radius: 15px;
      border-left: 4px solid #0ea5e9;
      margin-top: 20px;
      grid-column: 1 / -1;
    }
    
    .intelligence-section h3 {
      color: #0369a1;
      font-size: 20px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .intelligence-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
    }
    
    .button-group {
      margin-top: 40px;
      padding-top: 30px;
      border-top: 2px solid #e5e7eb;
      display: flex;
      gap: 15px;
      justify-content: center;
    }
    
    .btn {
      padding: 14px 32px;
      font-size: 16px;
      font-weight: 600;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #4a4a9c 0%, #667eea 100%);
      color: white;
      box-shadow: 0 4px 15px rgba(74, 74, 156, 0.4);
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(74, 74, 156, 0.5);
    }
    
    .btn-secondary {
      background: #6b7280;
      color: white;
      box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
    }
    
    .btn-secondary:hover {
      background: #4b5563;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(107, 114, 128, 0.4);
    }
    
    .icon {
      display: inline-block;
      width: 20px;
      height: 20px;
      margin-right: 5px;
    }
    
    .info-badge {
      display: inline-block;
      background: #dbeafe;
      color: #1e40af;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      margin-left: 8px;
    }
  </style>

  <script type="text/javascript" src="js/test371.js"></script>
  <script>
   function fisa(str) {
     alert("Function fisa called with: " + str);
   }

   function test(){
     fieldval = document.getElementById("nd").value;
     document.getElementById("bp").value = fieldval;
   }
   
   function quit() {
     window.close();
   }
   
   function submitForm() {
     document.forms[0].submit();
   }
   
   // Save dropdown selection immediately when changed
   function saveSelection(selectElement) {
     const name = selectElement.name;
     const value = selectElement.value;
     if (value) {
       localStorage.setItem(name, value);
     }
   }
   
   // Restore dropdown selections when page loads
   function restoreSelections() {
     const selects = document.querySelectorAll('select.form-control');
     selects.forEach(select => {
       const savedValue = localStorage.getItem(select.name);
       if (savedValue) {
         select.value = savedValue;
       }
     });
   }
   
   // Clear saved selections after successful submission
   function clearSavedSelections() {
     const urlParams = new URLSearchParams(window.location.search);
     if (urlParams.get('success') === '1') {
       localStorage.clear();
     }
   }
   
   window.addEventListener('DOMContentLoaded', function() {
     restoreSelections();
     clearSavedSelections();
     
     // Add change event listeners to all dropdowns to save selections immediately
     const selects = document.querySelectorAll('select.form-control');
     selects.forEach(select => {
       select.addEventListener('change', function() {
         saveSelection(this);
       });
     });
   });
  </script>
  
</head>

<body>

<div class="container">
  <div class="header">
    <h1>📋 Add New Customer</h1>
    <p>Complete customer information and intelligence form</p>
  </div>
  
  <div class="form-container">
    <form method="post" action="<?php echo htmlspecialchars("test20.php");?>" enctype="multipart/form-data">
      
      <div class="form-grid">
        <!-- Contact Information Section -->
        <div class="form-section">
          <h3>📞 Contact Information</h3>
          
          <div class="form-group">
            <label>Tel (Main) <span class="required">*</span></label>
            <input class="form-control" type="text" value="<?php echo htmlspecialchars($n); ?>" name="nu" id="bp" onclick="" required>
          </div>
          
          <div class="form-group">
            <label>Tel (Office)</label>
            <input class="form-control" type="text" name="inu" id="ibp" placeholder="">
          </div>
          
          <div class="form-group">
            <label>Tel (Mobile)</label>
            <input class="form-control" type="text" name="tel" id="tel" placeholder="">
          </div>
          
          <div class="form-group">
            <label>Tel (Other)</label>
            <input class="form-control" type="text" name="oth" id="oth" placeholder="">
          </div>
          
          <div class="form-group">
            <label>Email</label>
            <input class="form-control" type="email" name="em" id="em" placeholder="">
          </div>
        </div>
        
        <!-- Personal Information Section -->
        <div class="form-section">
          <h3>👤 Personal Information</h3>
          
          <div class="form-group">
            <label>First Name <span class="required">*</span></label>
            <input class="form-control" type="text" name="na" id="ap" required>
          </div>
          
          <div class="form-group">
            <label>Last Name <span class="required">*</span></label>
            <input class="form-control" type="text" name="lna" id="lap" required>
          </div>
          
          <div class="form-group">
            <label>Company</label>
            <input class="form-control" type="text" name="co" id="co" placeholder="">
          </div>
          
          <div class="form-group">
            <label>Job Title</label>
            <input class="form-control" type="text" name="job" id="job" placeholder="">
          </div>
          
          <div class="form-group">
            <label>Business Type</label>
            <input class="form-control" type="text" name="bu" id="bu" placeholder="">
          </div>
        </div>
      </div>
      
      <!-- Customer Intelligence Section -->
      <div class="intelligence-section">
        <h3>🎯 Customer Intelligence <span class="info-badge">HELPS AGENTS SERVE BETTER</span></h3>
        
        <div class="intelligence-grid">
          <div class="form-group">
            <label>Decision Authority</label>
            <select  name="decision_authority" onchange="saveSelection(this)">
              <option value="">Select Authority Level</option>
              <option value="End User">End User</option>
              <option value="Recommender">Recommender</option>
              <option value="Influencer">Influencer</option>
              <option value="Decision Maker">Decision Maker</option>
              <option value="Budget Owner">Budget Owner</option>
              <option value="Executive">Executive</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Communication Style</label>
            <select  name="communication_style" onchange="saveSelection(this)">
              <option value="">Select Style</option>
              <option value="Detailed/Technical">Detailed/Technical</option>
              <option value="Quick/Concise">Quick/Concise</option>
              <option value="Relationship-focused">Relationship-focused</option>
              <option value="Data-driven">Data-driven</option>
              <option value="Formal">Formal</option>
              <option value="Casual">Casual</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Customer Priority</label>
            <select  name="customer_priority" onchange="saveSelection(this)">
              <option value="">Select Priority</option>
              <option value="Standard">Standard</option>
              <option value="VIP">High Priority</option>
              <option value="Strategic">Urgent</option>
              <option value="Key Account">Strategic</option>
            </select>
          </div>
        </div>
        
        <div class="form-group" style="margin-top: 20px;">
          <label>Key Preferences & Notes</label>
          <textarea class="form-control" name="key_preferences" id="key_preferences" placeholder="Examples: Prefers detailed proposals, hates cold calls, technical background, needs ROI calculations, budget conscious, etc."></textarea>
        </div>
      </div>
      
      <div class="form-grid" style="margin-top: 30px;">
        <!-- Address Information Section -->
        <div class="form-section">
          <h3>🏠 Address Information</h3>
          
          <div class="form-group">
            <label>Google Maps URL</label>
            <input class="form-control" type="text" name="ur" id="ur" placeholder="">
          </div>
          
          <div class="form-group">
            <label>City</label>
            <input class="form-control" type="text" name="cit" id="cit" placeholder="">
          </div>
          
          <div class="form-group">
            <label>Zone</label>
            <input class="form-control" type="text" name="zon" id="zon" placeholder="">
          </div>
          
          <div class="form-group">
            <label>Street</label>
            <input class="form-control" type="text" name="str" id="str" placeholder="">
          </div>
          
          <div class="form-group">
            <label>Building</label>
            <input class="form-control" type="text" name="bui" id="bui" placeholder="">
          </div>
          
          <div class="form-group">
            <label>Apartment</label>
            <input class="form-control" type="text" name="apa" id="apa" placeholder="">
          </div>
          
          <div class="form-group">
            <label>Floor</label>
            <select  name="flo" onchange="saveSelection(this)">
              <option value="">Select Floor</option>
              <?php for ($i = 0; $i <= 20; $i++): ?>
                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
              <?php endfor; ?>
            </select>
          </div>
          
          <div class="form-group">
            <label>Near</label>
            <input class="form-control" type="text" name="nea" id="nea" placeholder="">
          </div>
          
          <div class="form-group">
            <label>Address Line 1</label>
            <textarea class="form-control" name="ad" id="cp" placeholder=""></textarea>
          </div>
          
          <div class="form-group">
            <label>Address Line 2</label>
            <textarea class="form-control" name="ad2" id="cp2" placeholder=""></textarea>
          </div>
          
          <div class="form-group">
            <label>Best Delivery Time</label>
            <select  name="delti" onchange="saveSelection(this)">
              <option value="">Select Time</option>
              <option value="Morning (8AM-12PM)">Morning (8AM-12PM)</option>
              <option value="Afternoon (12PM-6PM)">Afternoon (12PM-6PM)</option>
              <option value="Evening (6PM-10PM)">Evening (6PM-10PM)</option>
              <option value="Anytime">Anytime</option>
            </select>
          </div>
        </div>
        
        <!-- Business & Classification Section -->
        <div class="form-section">
          <h3>💼 Business & Classification</h3>
          
          <div class="form-group">
            <label>Category</label>
            <select  name="cat" onchange="saveSelection(this)">
              <option value="">Select Category</option>
              <option value="Existing Client">Existing Client</option>
              <option value="Ignore Call">Ignore Call</option>
              <option value="Lead">Lead</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Source</label>
            <select  name="blog" onchange="saveSelection(this)">
              <option value="">Select Source</option>
              <option value="Blog posts">Blog posts</option>
              <option value="Landing pages">Landing pages</option>
              <option value="Organic search traffic">Organic search traffic</option>
              <option value="Direct traffic">Direct traffic</option>
              <option value="PPC ads">PPC ads</option>
              <option value="Affiliate marketers">Affiliate marketers</option>
              <option value="Social media channels">Social media channels</option>
              <option value="Paid social ads">Paid social ads</option>
              <option value="Voice assistants">Voice assistants</option>
              <option value="Direct marketing">Direct marketing</option>
              <option value="Traditional marketing channels">Traditional marketing channels</option>
              <option value="Tradeshows">Tradeshows</option>
              <option value="Referrals">Referrals</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Grade</label>
            <select  name="gra" onchange="saveSelection(this)">
              <option value="">Select Grade</option>
              <option value="regular">Regular</option>
              <option value="gold">Gold</option>
              <option value="platinum">Platinum</option>
              <option value="premium">Premium</option>
              <!--option value="vip">VIP</option-->
            </select>
          </div>
          
          <div class="form-group">
            <label>Type of Payment</label>
            <select  name="pay" onchange="saveSelection(this)">
              <option value="">Select Payment</option>
              <option value="Cash">Cash</option>
              <option value="Credit">Credit</option>
              <option value="Visa">Visa</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Loyalty Card</label>
            <select  name="loy" onchange="saveSelection(this)">
              <option value="">Select Option</option>
              <option value="Yes">Yes</option>
              <option value="No">No</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Community Member</label>
            <select  name="com" onchange="saveSelection(this)">
              <option value="">Select Option</option>
              <option value="Yes">Yes</option>
              <option value="No">No</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Notes & Remarks</label>
            <textarea class="form-control" name="rem" id="rem" placeholder="Add any additional notes or special instructions..."></textarea>
          </div>
        </div>
      </div>
      
      <input type="hidden" id="nd" value="<?php echo htmlspecialchars($s); ?>">
      
      <div class="button-group">
        <button class="btn btn-primary" name="upload" type="submit" id="form">
          ✓ Add Customer
        </button>
        <button class="btn btn-secondary" type="button" onclick="quit()">
          ✕ Cancel
        </button>
      </div>
      
    </form>
  </div>
</div>

</body>
</html>