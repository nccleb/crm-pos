<?php
// trial_manager.php - Trial Management Functions

class TrialManager {
    private $db;
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
    }
    
    /**
     * Start a 2-week free trial for a new client
     */
    public function startTrial($client_id) {
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+14 days'));
        
        $stmt = $this->db->prepare("UPDATE client SET 
            trial_start_date = ?, 
            trial_end_date = ?, 
            account_status = 'trial',
            trial_used = TRUE 
            WHERE id = ?");
        
        $stmt->bind_param("ssi", $start_date, $end_date, $client_id);
        return $stmt->execute();
    }
    
    /**
     * Check if client's trial is still active
     */
    public function isTrialActive($client_id) {
        $stmt = $this->db->prepare("SELECT trial_end_date, account_status 
            FROM client WHERE id = ?");
        $stmt->bind_param("i", $client_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if ($row['account_status'] === 'trial') {
                $today = date('Y-m-d');
                return $today <= $row['trial_end_date'];
            }
        }
        return false;
    }
    
    /**
     * Get trial status information
     */
    public function getTrialInfo($client_id) {
        $stmt = $this->db->prepare("SELECT 
            trial_start_date, 
            trial_end_date, 
            account_status,
            DATEDIFF(trial_end_date, CURDATE()) as days_remaining
            FROM client WHERE id = ?");
        
        $stmt->bind_param("i", $client_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Expire trials automatically (run this daily via cron job)
     */
    public function expireTrials() {
        $stmt = $this->db->prepare("UPDATE client SET 
            account_status = 'expired' 
            WHERE account_status = 'trial' 
            AND trial_end_date < CURDATE()");
        
        $stmt->execute();
        return $this->db->affected_rows;
    }
    
    /**
     * Check access and show trial banner
     */
    public function checkAccess($client_id) {
        $trial_info = $this->getTrialInfo($client_id);
        
        if (!$trial_info) {
            return ['access' => false, 'message' => 'Account not found'];
        }
        
        switch ($trial_info['account_status']) {
            case 'trial':
                if ($trial_info['days_remaining'] > 0) {
                    return [
                        'access' => true, 
                        'trial' => true,
                        'days_remaining' => $trial_info['days_remaining'],
                        'message' => 'Trial active'
                    ];
                } else {
                    // Auto-expire
                    $this->expireTrials();
                    return [
                        'access' => false, 
                        'message' => 'Trial expired'
                    ];
                }
                break;
                
            case 'active':
                return ['access' => true, 'trial' => false, 'message' => 'Account active'];
                break;
                
            case 'expired':
                return ['access' => false, 'message' => 'Trial expired - please upgrade'];
                break;
                
            case 'suspended':
                return ['access' => false, 'message' => 'Account suspended'];
                break;
                
            default:
                return ['access' => false, 'message' => 'Invalid account status'];
        }
    }
}

// Usage example in your main files
session_start();
$idr = mysqli_connect("172.18.208.1", "root", "1Sys9Admeen72", "nccleb_test");
$trial_manager = new TrialManager($idr);

// Get client ID from session or URL parameter
$client_id = $_SESSION['id'] ?? null;

if ($client_id) {
    $access_check = $trial_manager->checkAccess($client_id);
    
    if (!$access_check['access']) {
        // Redirect to upgrade page or show access denied
        header("Location: trial_expired.php?message=" . urlencode($access_check['message']));
        exit();
    }
    
    // Show trial banner if on trial
    if (isset($access_check['trial']) && $access_check['trial']) {
        echo "<div class='trial-banner'>";
        echo "⏰ Trial: {$access_check['days_remaining']} days remaining. ";
        echo "<a href='upgrade.php'>Upgrade Now</a>";
        echo "</div>";
    }
}
?>

<style>
.trial-banner {
    background: linear-gradient(45deg, #ff6b6b, #ffa726);
    color: white;
    padding: 12px 20px;
    text-align: center;
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.trial-banner a {
    color: white;
    text-decoration: underline;
    margin-left: 10px;
}

.access-denied {
    max-width: 500px;
    margin: 50px auto;
    padding: 40px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    text-align: center;
}

.access-denied h2 {
    color: #dc2626;
    margin-bottom: 20px;
}

.upgrade-btn {
    background: linear-gradient(45deg, #3b82f6, #1d4ed8);
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    margin: 20px 10px;
    transition: transform 0.2s ease;
}

.upgrade-btn:hover {
    transform: translateY(-2px);
    color: white;
    text-decoration: none;
}
</style>