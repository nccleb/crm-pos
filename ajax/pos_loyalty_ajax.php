<?php
/**
 * ajax/pos_loyalty_ajax.php
 * Loyalty system AJAX backend — handles points and cashback wallet
 * Actions: lookup_card, check_universal_key, generate_card,
 *          get_client_loyalty, adjust_balance, get_transactions, save_settings
 */
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['oop']) || empty($_SESSION['ooq'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$agent_name = $_SESSION['oop'];
$agent_id   = (int)$_SESSION['ooq'];
$is_super   = ($agent_name === 'super');

$conn = mysqli_connect("192.168.1.19", "root", "1Sys9Admeen72", "nccleb_test");
if (!$conn) { echo json_encode(['success' => false, 'error' => 'DB error']); exit(); }
mysqli_set_charset($conn, 'utf8mb4');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Helpers ────────────────────────────────────────────────────────────────────
function getLoyaltySettings($conn) {
    $row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT loyalty_mode, loyalty_rate, loyalty_point_value,
                loyalty_min_redeem, universal_key_card
         FROM company_settings LIMIT 1"));
    return $row ?: [
        'loyalty_mode'        => 'disabled',
        'loyalty_rate'        => 2.00,
        'loyalty_point_value' => 1000,
        'loyalty_min_redeem'  => 5000,
        'universal_key_card'  => null,
    ];
}

function getClientLoyalty($conn, $client_id) {
    return mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id, nom, prenom, company, number,
                loyalty_card, wallet_balance, loyalty_points, total_spent, loyalty_enrolled
         FROM client WHERE id = $client_id LIMIT 1"));
}

switch ($action) {

    // ── Look up client by loyalty card barcode ─────────────────────────────────
    case 'lookup_card':
        $barcode = mysqli_real_escape_string($conn, trim($_GET['barcode'] ?? ''));
        if (!$barcode) { echo json_encode(['success' => false, 'error' => 'No barcode']); break; }

        $settings = getLoyaltySettings($conn);

        // Check if this is the universal key card
        if ($settings['universal_key_card'] && $barcode === $settings['universal_key_card']) {
            echo json_encode(['success' => true, 'is_universal_key' => true]);
            break;
        }

        // Look up client by loyalty_card
        $client = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id, nom, prenom, company, number,
                    loyalty_card, wallet_balance, loyalty_points, total_spent
             FROM client WHERE loyalty_card = '$barcode' LIMIT 1"));

        if (!$client) {
            echo json_encode(['success' => false, 'error' => 'Card not found']);
            break;
        }

        $name = trim(($client['prenom'] ?? '') . ' ' . ($client['nom'] ?? ''));
        if (empty(trim($name))) $name = $client['company'] ?? 'Unknown';

        echo json_encode([
            'success'         => true,
            'is_universal_key'=> false,
            'client'          => [
                'id'              => $client['id'],
                'name'            => $name,
                'number'          => $client['number'],
                'loyalty_card'    => $client['loyalty_card'],
                'wallet_balance'  => (int)$client['wallet_balance'],
                'loyalty_points'  => (int)$client['loyalty_points'],
                'total_spent'     => (int)$client['total_spent'],
            ],
            'settings' => $settings,
        ]);
        break;

    // ── Look up client by phone for loyalty (phone-only auth) ──────────────────
    case 'lookup_phone':
        $phone = mysqli_real_escape_string($conn, trim($_GET['phone'] ?? ''));
        if (!$phone) { echo json_encode(['success' => false, 'error' => 'No phone']); break; }

        $settings = getLoyaltySettings($conn);

        $client = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id, nom, prenom, company, number,
                    loyalty_card, wallet_balance, loyalty_points, total_spent
             FROM client WHERE number LIKE '%$phone%' LIMIT 1"));

        if (!$client) {
            echo json_encode(['success' => false, 'error' => 'Customer not found']);
            break;
        }

        $name = trim(($client['prenom'] ?? '') . ' ' . ($client['nom'] ?? ''));
        if (empty(trim($name))) $name = $client['company'] ?? 'Unknown';

        echo json_encode([
            'success' => true,
            'client'  => [
                'id'             => $client['id'],
                'name'           => $name,
                'number'         => $client['number'],
                'loyalty_card'   => $client['loyalty_card'],
                'wallet_balance' => (int)$client['wallet_balance'],
                'loyalty_points' => (int)$client['loyalty_points'],
                'total_spent'    => (int)$client['total_spent'],
            ],
            'settings' => $settings,
        ]);
        break;

    // ── Generate a new loyalty card barcode for a client ──────────────────────
    case 'generate_card':
        if (!$is_super) { echo json_encode(['success' => false, 'error' => 'Super only']); break; }

        $client_id = (int)($_POST['client_id'] ?? 0);
        if (!$client_id) { echo json_encode(['success' => false, 'error' => 'No client']); break; }

        // Check client exists and does not already have a card
        $client = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id, nom, prenom, company, loyalty_card FROM client WHERE id = $client_id LIMIT 1"));
        if (!$client) { echo json_encode(['success' => false, 'error' => 'Client not found']); break; }
        if ($client['loyalty_card']) {
            echo json_encode(['success' => false, 'error' => 'Client already has a card: ' . $client['loyalty_card']]);
            break;
        }

        // Generate unique barcode NCC-L-XXXXXX
        do {
            $barcode = 'NCC-L-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $exists  = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT id FROM client WHERE loyalty_card = '$barcode' LIMIT 1"));
        } while ($exists);

        mysqli_query($conn,
            "UPDATE client SET loyalty_card = '$barcode', loyalty_enrolled = NOW()
             WHERE id = $client_id");

        $name = trim(($client['prenom'] ?? '') . ' ' . ($client['nom'] ?? ''));

        require_once dirname(__FILE__) . '/pos_log.php';
        posLog($conn, $agent_id, $agent_name, 'loyalty_card_generated',
            "Card $barcode generated for $name (client #$client_id)", $client_id, 'client');

        echo json_encode(['success' => true, 'barcode' => $barcode, 'client_name' => $name]);
        break;

    // ── Revoke / reset a loyalty card ─────────────────────────────────────────
    case 'revoke_card':
        if (!$is_super) { echo json_encode(['success' => false, 'error' => 'Super only']); break; }

        $client_id = (int)($_POST['client_id'] ?? 0);
        if (!$client_id) { echo json_encode(['success' => false, 'error' => 'No client']); break; }

        $client = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT nom, prenom, loyalty_card FROM client WHERE id = $client_id LIMIT 1"));
        $old_card = $client['loyalty_card'] ?? '';

        mysqli_query($conn, "UPDATE client SET loyalty_card = NULL WHERE id = $client_id");

        require_once dirname(__FILE__) . '/pos_log.php';
        posLog($conn, $agent_id, $agent_name, 'loyalty_card_revoked',
            "Card $old_card revoked for client #$client_id", $client_id, 'client');

        echo json_encode(['success' => true]);
        break;

    // ── Get full loyalty profile for a client ─────────────────────────────────
    case 'get_client_loyalty':
        $client_id = (int)($_GET['client_id'] ?? 0);
        if (!$client_id) { echo json_encode(['success' => false, 'error' => 'No client']); break; }

        $client   = getClientLoyalty($conn, $client_id);
        $settings = getLoyaltySettings($conn);

        // Recent transactions
        $txn_res = mysqli_query($conn,
            "SELECT * FROM pos_loyalty_transactions
             WHERE client_id = $client_id
             ORDER BY created_at DESC LIMIT 20");
        $txns = [];
        while ($t = mysqli_fetch_assoc($txn_res)) $txns[] = $t;

        echo json_encode([
            'success'      => true,
            'client'       => $client,
            'settings'     => $settings,
            'transactions' => $txns,
        ]);
        break;

    // ── Manual balance adjustment (super only) ────────────────────────────────
    case 'adjust_balance':
        if (!$is_super) { echo json_encode(['success' => false, 'error' => 'Super only']); break; }

        $client_id = (int)($_POST['client_id'] ?? 0);
        $amount    = (int)($_POST['amount']    ?? 0);  // can be negative
        $note_txt  = mysqli_real_escape_string($conn, $_POST['note'] ?? '');
        $settings  = getLoyaltySettings($conn);
        $mode      = $settings['loyalty_mode'];

        if (!$client_id || $mode === 'disabled') {
            echo json_encode(['success' => false, 'error' => 'Invalid request']); break;
        }

        $client = getClientLoyalty($conn, $client_id);
        if (!$client) { echo json_encode(['success' => false, 'error' => 'Client not found']); break; }

        if ($mode === 'cashback') {
            $before = (int)$client['wallet_balance'];
            $after  = max(0, $before + $amount);
            mysqli_query($conn, "UPDATE client SET wallet_balance = $after WHERE id = $client_id");
        } else {
            $before = (int)$client['loyalty_points'];
            $after  = max(0, $before + $amount);
            mysqli_query($conn, "UPDATE client SET loyalty_points = $after WHERE id = $client_id");
        }

        mysqli_query($conn,
            "INSERT INTO pos_loyalty_transactions
             (client_id, client_name, type, mode, amount, balance_before, balance_after,
              auth_method, agent_id, agent_name, note)
             VALUES ($client_id, '" . mysqli_real_escape_string($conn,
                trim(($client['prenom']??'').' '.($client['nom']??''))) . "',
             'adjusted', '$mode', " . abs($amount) . ", $before, $after,
             'card', $agent_id, '$agent_name', '$note_txt')");

        require_once dirname(__FILE__) . '/pos_log.php';
        posLog($conn, $agent_id, $agent_name, 'loyalty_adjusted',
            "Client #{$client_id} {$mode} adjusted by {$amount} (was {$before} now {$after}). {$note_txt}",
            $client_id, 'client');

        echo json_encode(['success' => true, 'balance_after' => $after]);
        break;

    // ── Get transaction log (filtered) ────────────────────────────────────────
    case 'get_transactions':
        $client_id = (int)($_GET['client_id'] ?? 0);
        $date_from = mysqli_real_escape_string($conn, $_GET['from'] ?? date('Y-m-d', strtotime('-30 days')));
        $date_to   = mysqli_real_escape_string($conn, $_GET['to']   ?? date('Y-m-d'));
        $type_f    = mysqli_real_escape_string($conn, $_GET['type'] ?? '');
        $limit     = min((int)($_GET['limit'] ?? 50), 200);

        $where = "WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'";
        if ($client_id) $where .= " AND client_id = $client_id";
        if ($type_f)    $where .= " AND type = '$type_f'";

        $res = mysqli_query($conn,
            "SELECT * FROM pos_loyalty_transactions $where ORDER BY created_at DESC LIMIT $limit");
        $txns = [];
        while ($t = mysqli_fetch_assoc($res)) $txns[] = $t;

        // Stats
        $stats = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT SUM(CASE WHEN type='earned'   THEN amount ELSE 0 END) as total_earned,
                    SUM(CASE WHEN type='redeemed' THEN amount ELSE 0 END) as total_redeemed,
                    COUNT(DISTINCT client_id) as unique_clients
             FROM pos_loyalty_transactions $where"));

        echo json_encode(['success' => true, 'transactions' => $txns, 'stats' => $stats]);
        break;

    // ── Save loyalty settings (super only) ────────────────────────────────────
    case 'save_settings':
        if (!$is_super) { echo json_encode(['success' => false, 'error' => 'Super only']); break; }

        $mode        = in_array($_POST['loyalty_mode'] ?? '', ['disabled','points','cashback'])
                       ? $_POST['loyalty_mode'] : 'disabled';
        $rate        = (float)($_POST['loyalty_rate']        ?? 2.00);
        $point_value = (int)($_POST['loyalty_point_value']   ?? 1000);
        $min_redeem  = (int)($_POST['loyalty_min_redeem']    ?? 5000);
        $ukey        = mysqli_real_escape_string($conn, trim($_POST['universal_key_card'] ?? ''));

        mysqli_query($conn,
            "UPDATE company_settings SET
             loyalty_mode        = '$mode',
             loyalty_rate        = $rate,
             loyalty_point_value = $point_value,
             loyalty_min_redeem  = $min_redeem,
             universal_key_card  = " . ($ukey ? "'$ukey'" : "NULL") . "
             LIMIT 1");

        require_once dirname(__FILE__) . '/pos_log.php';
        posLog($conn, $agent_id, $agent_name, 'loyalty_settings_saved',
            "Loyalty mode set to $mode, rate $rate, universal key " . ($ukey ?: 'cleared'),
            null, null);

        echo json_encode(['success' => true]);
        break;

    // ── Get settings ──────────────────────────────────────────────────────────
    case 'get_settings':
        echo json_encode(['success' => true, 'settings' => getLoyaltySettings($conn)]);
        break;

    // ── Enrolled clients list ─────────────────────────────────────────────────
    case 'get_enrolled':
        $q      = mysqli_real_escape_string($conn, trim($_GET['q'] ?? ''));
        $where  = "WHERE loyalty_card IS NOT NULL";
        if ($q) $where .= " AND (nom LIKE '%$q%' OR prenom LIKE '%$q%'
                                OR number LIKE '%$q%' OR loyalty_card LIKE '%$q%'
                                OR company LIKE '%$q%')";

        $res = mysqli_query($conn,
            "SELECT id, nom, prenom, company, number, loyalty_card,
                    wallet_balance, loyalty_points, total_spent, loyalty_enrolled
             FROM client $where ORDER BY loyalty_enrolled DESC LIMIT 100");
        $clients = [];
        while ($r = mysqli_fetch_assoc($res)) $clients[] = $r;

        // Totals
        $totals = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) as enrolled,
                    SUM(wallet_balance) as total_wallet,
                    SUM(loyalty_points) as total_points
             FROM client WHERE loyalty_card IS NOT NULL"));

        echo json_encode(['success' => true, 'clients' => $clients, 'totals' => $totals]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}

mysqli_close($conn);
?>
