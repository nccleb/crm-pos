<?php
// ============================================================
//  ucm_settings_ui.php  —  UCM API Bridge Configuration
// ============================================================
$settingsFile = __DIR__ . '/ucm_settings.json';

$defaults = [
    'api_url'               => 'https://192.168.22.100:8089/api',
    'api_user'              => 'cdrapi',
    'api_pass'              => 'cdrapi123',
    'api_timeout'           => 2,
    'api_connect_timeout'   => 1,
    'agent_ext_min'         => 6001,
    'agent_ext_max'         => 6099,
    'queue_numbers'         => '6500',
    'ivr_numbers'           => '7000',
    'callerid_file_path'    => 'c:\\Mdr\\CallerID',
    'callerid_col_number'   => 49,
    'callerid_col_extension'=> 25,
    'callerid_col_length'   => 8,
];

$message = ''; $msgType = '';

// ── Save ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $new = [
        'api_url'               => trim($_POST['api_url']            ?? $defaults['api_url']),
        'api_user'              => trim($_POST['api_user']           ?? $defaults['api_user']),
        'api_pass'              => trim($_POST['api_pass']           ?? $defaults['api_pass']),
        'api_timeout'           => max(1, intval($_POST['api_timeout']          ?? 2)),
        'api_connect_timeout'   => max(1, intval($_POST['api_connect_timeout']  ?? 1)),
        'agent_ext_min'         => intval($_POST['agent_ext_min']    ?? 6001),
        'agent_ext_max'         => intval($_POST['agent_ext_max']    ?? 6099),
        'queue_numbers'         => trim($_POST['queue_numbers']      ?? '6500'),
        'ivr_numbers'           => trim($_POST['ivr_numbers']        ?? '7000'),
        'callerid_file_path'    => trim($_POST['callerid_file_path'] ?? $defaults['callerid_file_path']),
        'callerid_col_number'   => intval($_POST['callerid_col_number']    ?? 49),
        'callerid_col_extension'=> intval($_POST['callerid_col_extension'] ?? 25),
        'callerid_col_length'   => intval($_POST['callerid_col_length']    ?? 8),
    ];
    if (file_put_contents($settingsFile, json_encode($new, JSON_PRETTY_PRINT))) {
        $message = '✅ Settings saved successfully.';
        $msgType = 'ok';
        $cfg = $new;
    } else {
        $message = '❌ Could not write settings file. Check PHP write permissions on this folder.';
        $msgType = 'err';
        $cfg = $new;
    }
} else {
    $cfg = file_exists($settingsFile)
        ? array_merge($defaults, json_decode(file_get_contents($settingsFile), true) ?: [])
        : $defaults;
}

// ── Test connection ─────────────────────────────────────────
$testResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test'])) {
    $tUrl  = trim($_POST['api_url']  ?? $cfg['api_url']);
    $tUser = trim($_POST['api_user'] ?? $cfg['api_user']);
    $tPass = trim($_POST['api_pass'] ?? $cfg['api_pass']);
    $tTo   = max(1, intval($_POST['api_timeout'] ?? 2));
    $tCto  = max(1, intval($_POST['api_connect_timeout'] ?? 1));

    $ch = curl_init($tUrl);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ["Connection: close","Content-Type: application/json"],
        CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $tTo, CURLOPT_CONNECTTIMEOUT => $tCto,
        CURLOPT_POSTFIELDS     => json_encode(['request' => ['action' => 'challenge', 'user' => $tUser]]),
    ]);
    $r = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($r === false) {
        $testResult = ['ok' => false, 'msg' => 'Connection failed: ' . $err];
    } else {
        $arr = json_decode($r, true);
        $challenge = $arr['response']['challenge'] ?? '';
        if ($challenge) {
            // Try login
            $token = md5($challenge . $tPass);
            $ch2 = curl_init($tUrl);
            curl_setopt_array($ch2, [
                CURLOPT_HTTPHEADER     => ["Connection: close","Content-Type: application/json"],
                CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $tTo,
                CURLOPT_POSTFIELDS     => json_encode(['request' => ['action'=>'login','token'=>$token,'user'=>$tUser]]),
            ]);
            $r2   = curl_exec($ch2); curl_close($ch2);
            $arr2 = json_decode($r2, true);
            $cookie = $arr2['response']['cookie'] ?? '';
            if ($cookie) {
                $testResult = ['ok' => true, 'msg' => '✅ Connected & authenticated successfully! Cookie received.'];
            } else {
                $status = $arr2['response']['status'] ?? 'unknown error';
                $testResult = ['ok' => false, 'msg' => '⚠️ Reached UCM but login failed: ' . $status . '. Check username/password.'];
            }
        } else {
            $testResult = ['ok' => false, 'msg' => '⚠️ UCM responded but returned no challenge. Check API URL.'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UCM Bridge Settings</title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --bg:       #0f1117;
    --surface:  #1a1d27;
    --card:     #21253a;
    --border:   #2e3350;
    --accent:   #4f8ef7;
    --accent2:  #7c5ef7;
    --green:    #22d3a0;
    --red:      #f7506a;
    --amber:    #f7b950;
    --text:     #e8eaf6;
    --muted:    #7a7f9a;
    --mono:     'IBM Plex Mono', monospace;
    --sans:     'IBM Plex Sans', sans-serif;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: var(--sans);
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    padding: 0;
}

/* Topbar */
.topbar {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 0 40px;
    height: 64px;
    display: flex;
    align-items: center;
    gap: 16px;
    position: sticky;
    top: 0;
    z-index: 100;
}
.topbar-logo {
    width: 32px; height: 32px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
}
.topbar-title { font-size: 15px; font-weight: 600; letter-spacing: .3px; }
.topbar-sub   { font-size: 12px; color: var(--muted); font-family: var(--mono); }
.topbar-spacer { flex: 1; }
.topbar-badge {
    font-family: var(--mono);
    font-size: 11px;
    background: var(--card);
    border: 1px solid var(--border);
    color: var(--muted);
    padding: 4px 10px;
    border-radius: 20px;
}
.btn-quit {
    font-family: var(--sans);
    font-size: 13px;
    font-weight: 600;
    padding: 8px 18px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    background: var(--red);
    color: #fff;
    display: flex;
    align-items: center;
    gap: 7px;
    transition: all .2s;
    text-decoration: none;
}
.btn-quit:hover { background: #e03050; transform: translateY(-1px); }

/* Layout */
.page { max-width: 900px; margin: 0 auto; padding: 40px 24px 80px; }

/* Section headers */
.section {
    margin-bottom: 32px;
}
.section-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--accent);
    font-family: var(--mono);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.section-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border);
}

/* Card */
.card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
}

/* Form rows */
.field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.field-grid.triple { grid-template-columns: 1fr 1fr 1fr; }
.field-grid.single { grid-template-columns: 1fr; }

.field { display: flex; flex-direction: column; gap: 7px; }
.field label {
    font-size: 12px;
    font-weight: 600;
    color: var(--muted);
    letter-spacing: .5px;
    text-transform: uppercase;
    font-family: var(--mono);
}
.field input[type=text],
.field input[type=password],
.field input[type=number],
.field input[type=url] {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 7px;
    color: var(--text);
    font-family: var(--mono);
    font-size: 13px;
    padding: 10px 13px;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
}
.field input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(79,142,247,.15);
}
.field small {
    font-size: 11px;
    color: var(--muted);
    line-height: 1.5;
}

/* Inline number pair */
.range-pair { display: flex; align-items: center; gap: 10px; }
.range-pair input { flex: 1; }
.range-sep { color: var(--muted); font-size: 13px; font-family: var(--mono); white-space: nowrap; }

/* Buttons */
.btn-row { display: flex; gap: 12px; margin-top: 28px; align-items: center; flex-wrap: wrap; }
.btn {
    font-family: var(--sans);
    font-size: 14px;
    font-weight: 600;
    padding: 11px 22px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all .2s;
    display: flex; align-items: center; gap: 8px;
    letter-spacing: .2px;
}
.btn-primary {
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #fff;
    box-shadow: 0 4px 14px rgba(79,142,247,.35);
}
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(79,142,247,.45); }
.btn-test {
    background: var(--surface);
    color: var(--green);
    border: 1px solid var(--green);
}
.btn-test:hover { background: rgba(34,211,160,.1); }

/* Alert / toast */
.alert {
    padding: 14px 18px;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 28px;
    border-left: 3px solid;
    font-family: var(--mono);
}
.alert.ok  { background: rgba(34,211,160,.1); border-color: var(--green); color: var(--green); }
.alert.err { background: rgba(247,80,106,.1);  border-color: var(--red);   color: var(--red);   }
.alert.test-ok  { background: rgba(34,211,160,.08); border-color: var(--green); color: var(--text); }
.alert.test-err { background: rgba(247,80,106,.08); border-color: var(--red);   color: var(--text); }

/* Info box */
.info-row {
    display: flex; gap: 12px; margin-top: 28px;
    padding: 14px 16px;
    background: rgba(79,142,247,.07);
    border: 1px solid rgba(79,142,247,.2);
    border-radius: 8px;
    font-size: 12px;
    color: var(--muted);
    line-height: 1.6;
}
.info-row b { color: var(--accent); }

/* File badge */
.file-badge {
    display: inline-flex; align-items: center; gap: 7px;
    font-family: var(--mono); font-size: 12px;
    color: var(--muted);
    background: var(--surface);
    border: 1px solid var(--border);
    padding: 5px 12px; border-radius: 20px;
    margin-top: 6px;
}
.file-dot { width: 7px; height: 7px; border-radius: 50%; }
.file-dot.exists { background: var(--green); }
.file-dot.missing { background: var(--red); }

@media (max-width: 620px) {
    .field-grid, .field-grid.triple { grid-template-columns: 1fr; }
    .topbar { padding: 0 16px; }
    .page { padding: 24px 16px 60px; }
}
</style>
</head>
<body>

<div class="topbar">
    <div class="topbar-logo">📡</div>
    <div>
        <div class="topbar-title">UCM Bridge Settings</div>
        <div class="topbar-sub">test449.php configuration</div>
    </div>
    <div class="topbar-spacer"></div>
    <div class="topbar-badge">ucm_settings.json</div>
    <button class="btn-quit" onclick="window.close(); setTimeout(()=>window.history.back(),100);">
        ✕ Close
    </button>
</div>

<div class="page">

<?php if ($message): ?>
<div class="alert <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($testResult !== null): ?>
<div class="alert <?= $testResult['ok'] ? 'test-ok' : 'test-err' ?>">
    <?= htmlspecialchars($testResult['msg']) ?>
</div>
<?php endif; ?>

<form method="POST">

    <!-- ── UCM API ── -->
    <div class="section">
        <div class="section-label">UCM API Connection</div>
        <div class="card">
            <div class="field-grid single" style="margin-bottom:20px">
                <div class="field">
                    <label>API URL</label>
                    <input type="text" name="api_url" value="<?= htmlspecialchars($cfg['api_url']) ?>"
                           placeholder="https://192.168.x.x:8089/api">
                    <small>Full URL to the Grandstream UCM API endpoint including port 8089.</small>
                </div>
            </div>
            <div class="field-grid" style="margin-bottom:20px">
                <div class="field">
                    <label>API Username</label>
                    <input type="text" name="api_user" value="<?= htmlspecialchars($cfg['api_user']) ?>"
                           placeholder="cdrapi">
                </div>
                <div class="field">
                    <label>API Password</label>
                    <input type="password" name="api_pass" value="<?= htmlspecialchars($cfg['api_pass']) ?>"
                           placeholder="••••••••">
                </div>
            </div>
            <div class="field-grid">
                <div class="field">
                    <label>Response Timeout (seconds)</label>
                    <input type="number" name="api_timeout" value="<?= (int)$cfg['api_timeout'] ?>" min="1" max="30">
                    <small>How long to wait for API response. Default: 2s.</small>
                </div>
                <div class="field">
                    <label>Connect Timeout (seconds)</label>
                    <input type="number" name="api_connect_timeout" value="<?= (int)$cfg['api_connect_timeout'] ?>" min="1" max="10">
                    <small>How long to wait for initial TCP connection. Default: 1s.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Extension Ranges ── -->
    <div class="section">
        <div class="section-label">Extension & Number Routing</div>
        <div class="card">
            <div class="field" style="margin-bottom:20px">
                <label>Agent Extension Range</label>
                <div class="range-pair">
                    <input type="number" name="agent_ext_min" value="<?= (int)$cfg['agent_ext_min'] ?>"
                           placeholder="6001" style="max-width:130px">
                    <span class="range-sep">to</span>
                    <input type="number" name="agent_ext_max" value="<?= (int)$cfg['agent_ext_max'] ?>"
                           placeholder="6099" style="max-width:130px">
                </div>
                <small>Extensions in this range are treated as agent phones (e.g. 6001–6099).</small>
            </div>
            <div class="field-grid" style="margin-top:20px">
                <div class="field">
                    <label>Queue Numbers</label>
                    <input type="text" name="queue_numbers" value="<?= htmlspecialchars($cfg['queue_numbers']) ?>"
                           placeholder="6500">
                    <small>Comma-separated. E.g. <code>6500, 6501</code></small>
                </div>
                <div class="field">
                    <label>IVR Numbers</label>
                    <input type="text" name="ivr_numbers" value="<?= htmlspecialchars($cfg['ivr_numbers']) ?>"
                           placeholder="7000">
                    <small>Comma-separated. E.g. <code>7000, 7001</code></small>
                </div>
            </div>
        </div>
    </div>

    <!-- ── CallerID File ── -->
    <div class="section">
        <div class="section-label">CallerID File Fallback</div>
        <div class="card">
            <div class="field" style="margin-bottom:20px">
                <label>CallerID File Base Path</label>
                <input type="text" name="callerid_file_path"
                       value="<?= htmlspecialchars($cfg['callerid_file_path']) ?>"
                       placeholder="c:\Mdr\CallerID">
                <small>
                    Base path without date suffix. The file opened will be:<br>
                    <code><?= htmlspecialchars(rtrim($cfg['callerid_file_path'],'\\/')) . '\\CallerID' . date('Y') . '-' . date('m') . '.txt' ?></code>
                </small>
            </div>
            <div class="field-grid triple">
                <div class="field">
                    <label>Number Column Start</label>
                    <input type="number" name="callerid_col_number"
                           value="<?= (int)$cfg['callerid_col_number'] ?>" min="0">
                    <small>Character offset of caller number in each line.</small>
                </div>
                <div class="field">
                    <label>Extension Column Start</label>
                    <input type="number" name="callerid_col_extension"
                           value="<?= (int)$cfg['callerid_col_extension'] ?>" min="0">
                    <small>Character offset of extension in each line.</small>
                </div>
                <div class="field">
                    <label>Field Length</label>
                    <input type="number" name="callerid_col_length"
                           value="<?= (int)$cfg['callerid_col_length'] ?>" min="1" max="20">
                    <small>Number of characters to read for each field.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Actions ── -->
    <div class="btn-row">
        <button type="submit" name="save" class="btn btn-primary">
            💾 Save Settings
        </button>
        <button type="submit" name="test" class="btn btn-test">
            ⚡ Test Connection
        </button>
    </div>

    <div class="info-row">
        <div>
            <b>How it works:</b> Settings are stored in <code>ucm_settings.json</code> next to your PHP files.
            <code>test449.php</code> reads this file on every request — no code editing required.
            Use <b>Test Connection</b> to verify your UCM credentials before saving.
        </div>
    </div>

</form>
</div>

<script>
// Auto-dismiss save alert after 4s
setTimeout(function() {
    var a = document.querySelector('.alert.ok, .alert.err');
    if (a) { a.style.transition='opacity .5s'; a.style.opacity='0'; setTimeout(function(){a.remove()},500); }
}, 4000);
</script>
</body>
</html>