<!DOCTYPE html>
<html lang="en">
<head>
<?php include('head.php'); ?>
  <link rel="stylesheet" href="css/stylei.css">
  <link rel="stylesheet" href="css/stylei2.css">
  <link rel="stylesheet" href="css/whatsappButton.css" />
  <script src="js/test371.js"></script>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background-color: #f8fafc;
    margin: 0;
    padding: 20px;
}

.jumbotron {
    max-width: 800px;
    margin: 50px auto;
    background: white;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
}

table {
    width: 100%;
    border: none;
}

#form {
    font-size: 18px;
    color: #1f2937;
    font-weight: 600;
    text-align: center;
    margin-bottom: 25px;
    padding: 20px;
    background-color: #fef3c7;
    border-radius: 8px;
    border-left: 4px solid #f59e0b;
}

.method-container {
    background-color: #f9fafb;
    padding: 25px;
    border-radius: 8px;
    margin: 20px 0;
    border: 2px solid #e5e7eb;
    transition: all 0.3s ease;
}

.method-container.active {
    border-color: #3b82f6;
    background-color: #eff6ff;
}

.method-title {
    font-size: 18px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
}

.method-radio {
    margin-right: 10px;
    transform: scale(1.2);
}

.preset-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
    margin: 15px 0;
}

.preset-option {
    padding: 12px 16px;
    background-color: white;
    border: 2px solid #d1d5db;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
    font-weight: 500;
}

.preset-option:hover {
    background-color: #f3f4f6;
    border-color: #9ca3af;
}

.preset-option.selected {
    background-color: #3b82f6;
    color: white;
    border-color: #2563eb;
}

.preset-option input[type="radio"] {
    display: none;
}

.date-range-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 15px 0;
    align-items: end;
}

.date-input-group {
    display: flex;
    flex-direction: column;
}

.date-input-group label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 5px;
}

.date-input-group input[type="date"] {
    padding: 10px;
    border: 2px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s ease;
}

.date-input-group input[type="date"]:focus {
    outline: none;
    border-color: #3b82f6;
}

.warning-text {
    font-size: 14px;
    color: #dc2626;
    margin: 15px 0;
    font-weight: 500;
    padding: 10px;
    background-color: #fef2f2;
    border-radius: 6px;
    border-left: 4px solid #dc2626;
}

.info-text {
    font-size: 14px;
    color: #6b7280;
    margin: 10px 0;
    font-style: italic;
}

.whatsappbutton {
    background-color: #dc2626;
    color: white;
    border: none;
    padding: 12px 24px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
    margin: 0 8px;
    min-width: 120px;
    transition: all 0.2s ease;
}

.whatsappbutton:hover {
    background-color: #b91c1c;
    transform: translateY(-1px);
}

.whatsappbutton:disabled {
    background-color: #9ca3af;
    cursor: not-allowed;
    transform: none;
}

button.whatsappbutton {
    background-color: #6b7280;
}

button.whatsappbutton:hover {
    background-color: #4b5563;
}

.button-container {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

@media (max-width: 768px) {
    .jumbotron {
        margin: 20px auto;
        padding: 25px;
    }
    
    .date-range-container {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .preset-options {
        grid-template-columns: 1fr;
    }
    
    .whatsappbutton {
        display: block;
        width: 100%;
        margin: 8px 0;
    }
}
</style>
</head>

<body>
<div class="jumbotron"> 
   <table>
    <tr>
     <td valign="top"> 
      <p id="form">DATA CLEANUP - SELECT DELETION METHOD</p> 
      
      <form method="post" action="<?php echo htmlspecialchars("del77.php");?>" id="cleanupForm">
        
        <!-- Method 1: Quick Presets -->
        <div class="method-container" id="presetContainer">
          <div class="method-title">
            <input type="radio" name="deletion_method" value="preset" id="presetMethod" class="method-radio" checked>
            <label for="presetMethod">Quick Cleanup Options</label>
          </div>
          <p class="info-text">Keep recent data and delete older records</p>
          
          <div class="preset-options">
            <label class="preset-option" for="today">
              <input type="radio" name="keep_period" value="today" id="today">
              Keep Today Only
            </label>
            
            <label class="preset-option" for="days3">
              <input type="radio" name="keep_period" value="3" id="days3">
              Keep Last 3 Days
            </label>
            
            <label class="preset-option" for="days7">
              <input type="radio" name="keep_period" value="7" id="days7">
              Keep Last 7 Days
            </label>
            
            <label class="preset-option" for="days30">
              <input type="radio" name="keep_period" value="30" id="days30">
              Keep Last 30 Days
            </label>
            
            <label class="preset-option" for="deleteAll">
              <input type="radio" name="keep_period" value="all" id="deleteAll">
              Delete All Data
            </label>
          </div>
        </div>
        
        <!-- Method 2: Custom Date Range -->
        <div class="method-container" id="dateRangeContainer">
          <div class="method-title">
            <input type="radio" name="deletion_method" value="date_range" id="dateRangeMethod" class="method-radio">
            <label for="dateRangeMethod">Custom Date Range</label>
          </div>
          <p class="info-text">Delete data between specific dates</p>
          
          <div class="date-range-container">
            <div class="date-input-group">
              <label for="delete_from">Delete From Date:</label>
              <input type="date" name="delete_from" id="delete_from">
            </div>
            
            <div class="date-input-group">
              <label for="delete_to">Delete To Date:</label>
              <input type="date" name="delete_to" id="delete_to">
            </div>
          </div>
        </div>
        
        <div class="warning-text" id="warningText" style="display: none;">
          ⚠️ This action cannot be undone!
        </div>
        
        <div class="button-container">
          <input type="submit" class="whatsappbutton" value="PROCEED WITH CLEANUP" id="submitBtn" disabled>
          <button type="button" class="whatsappbutton" onclick="quit()">CANCEL</button>
        </div>
      </form>
     </td>
    </tr>
   </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const methodRadios = document.querySelectorAll('input[name="deletion_method"]');
    const presetRadios = document.querySelectorAll('input[name="keep_period"]');
    const presetOptions = document.querySelectorAll('.preset-option');
    const dateInputs = document.querySelectorAll('#delete_from, #delete_to');
    const submitBtn = document.getElementById('submitBtn');
    const warningText = document.getElementById('warningText');
    
    const presetContainer = document.getElementById('presetContainer');
    const dateRangeContainer = document.getElementById('dateRangeContainer');
    
    // Handle method selection
    methodRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (this.value === 'preset') {
                presetContainer.classList.add('active');
                dateRangeContainer.classList.remove('active');
                checkPresetSelection();
            } else {
                dateRangeContainer.classList.add('active');
                presetContainer.classList.remove('active');
                checkDateRangeSelection();
            }
        });
    });
    
    // Handle preset selection
    presetRadios.forEach(function(radio, index) {
        radio.addEventListener('change', function() {
            if (document.querySelector('input[name="deletion_method"]:checked').value === 'preset') {
                updatePresetUI(index);
                checkPresetSelection();
            }
        });
    });
    
    // Handle preset option clicks
    presetOptions.forEach(function(option, index) {
        option.addEventListener('click', function() {
            if (document.querySelector('input[name="deletion_method"]:checked').value === 'preset') {
                presetRadios[index].checked = true;
                updatePresetUI(index);
                checkPresetSelection();
            }
        });
    });
    
    // Handle date range changes
    dateInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            if (document.querySelector('input[name="deletion_method"]:checked').value === 'date_range') {
                checkDateRangeSelection();
            }
        });
    });
    
    function updatePresetUI(selectedIndex) {
        presetOptions.forEach((option, index) => {
            option.classList.toggle('selected', index === selectedIndex);
        });
    }
    
    function checkPresetSelection() {
        const selectedPreset = document.querySelector('input[name="keep_period"]:checked');
        if (selectedPreset) {
            submitBtn.disabled = false;
            warningText.style.display = 'block';
            updateWarningText('preset', selectedPreset.value);
        } else {
            submitBtn.disabled = true;
            warningText.style.display = 'none';
        }
    }
    
    function checkDateRangeSelection() {
        const fromDate = document.getElementById('delete_from').value;
        const toDate = document.getElementById('delete_to').value;
        
        if (fromDate && toDate) {
            if (new Date(fromDate) <= new Date(toDate)) {
                submitBtn.disabled = false;
                warningText.style.display = 'block';
                updateWarningText('date_range', { from: fromDate, to: toDate });
            } else {
                submitBtn.disabled = true;
                warningText.innerHTML = '❌ "From" date must be earlier than or equal to "To" date';
                warningText.style.display = 'block';
            }
        } else {
            submitBtn.disabled = true;
            warningText.style.display = 'none';
        }
    }
    
    function updateWarningText(method, value) {
        if (method === 'preset') {
            switch (value) {
                case 'all':
                    warningText.innerHTML = '⚠️ This will delete ALL location data! ID counter will be reset to 1. This action cannot be undone!';
                    break;
                case 'today':
                    warningText.innerHTML = '⚠️ This will delete all data except today\'s records! This action cannot be undone!';
                    break;
                default:
                    warningText.innerHTML = `⚠️ This will delete all data older than ${value} day${value > 1 ? 's' : ''}! This action cannot be undone!`;
            }
        } else if (method === 'date_range') {
            warningText.innerHTML = `⚠️ This will delete all data from ${value.from} to ${value.to}! This action cannot be undone!`;
        }
    }
    
    // Initialize UI
    presetContainer.classList.add('active');
    
    // Form submission validation
    document.getElementById('cleanupForm').addEventListener('submit', function(e) {
        const method = document.querySelector('input[name="deletion_method"]:checked').value;
        let confirmMessage;
        
        if (method === 'preset') {
            const selectedPreset = document.querySelector('input[name="keep_period"]:checked').value;
            if (selectedPreset === 'all') {
                confirmMessage = 'Are you absolutely sure you want to DELETE ALL location data?\n\nThis will also reset the ID counter to 1.\nThis action cannot be undone!';
            } else if (selectedPreset === 'today') {
                confirmMessage = 'Are you sure you want to delete all location data except today\'s records?\n\nThis action cannot be undone!';
            } else {
                confirmMessage = `Are you sure you want to delete all location data older than ${selectedPreset} day${selectedPreset > 1 ? 's' : ''}?\n\nThis action cannot be undone!`;
            }
        } else {
            const fromDate = document.getElementById('delete_from').value;
            const toDate = document.getElementById('delete_to').value;
            confirmMessage = `Are you sure you want to delete all location data from ${fromDate} to ${toDate}?\n\nThis action cannot be undone!`;
        }
        
        if (!confirm(confirmMessage)) {
            e.preventDefault();
        }
    });
    
    // Set default max date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('delete_from').setAttribute('max', today);
    document.getElementById('delete_to').setAttribute('max', today);
});

function quit() {
    window.close();
}
</script>

</body>
</html>