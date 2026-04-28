<?php
session_start();
date_default_timezone_set('Asia/Beirut');
define('UCM_URL',  'https://192.168.22.100:8089/api');
define('CDR_USER', 'cdrapi');
define('CDR_PASS', 'cdrapi123');
define('PAUSE_FILE', __DIR__.'/ucm_pause_state.json');

if (isset($_GET['page']))  $_SESSION['oop'] = urldecode($_GET['page']);
if (isset($_GET['page1'])) $_SESSION['ooq'] = urldecode($_GET['page1']);

// ── Pause state (supervisor-tracked) ──────────────────────────────────────
function getPauseState(): array {
    if (!file_exists(PAUSE_FILE)) return [];
    return json_decode(file_get_contents(PAUSE_FILE), true) ?? [];
}
function savePauseState(string $ext, bool $paused, string $reason='', string $since=''): void {
    $st = getPauseState();
    if ($paused) $st[$ext] = ['paused'=>true,'reason'=>$reason,'since'=>$since?:date('Y-m-d H:i:s')];
    else         unset($st[$ext]);
    file_put_contents(PAUSE_FILE, json_encode($st));
}

// ── UCM API helpers ───────────────────────────────────────────────────────
function ucm_post(array $payload): array {
    $ch = curl_init(UCM_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>json_encode(['request'=>$payload]),
        CURLOPT_HTTPHEADER=>['Content-Type: application/json; charset=UTF-8'],
        CURLOPT_RETURNTRANSFER=>true, CURLOPT_SSL_VERIFYPEER=>false,
        CURLOPT_SSL_VERIFYHOST=>false, CURLOPT_TIMEOUT=>10, CURLOPT_CONNECTTIMEOUT=>5,
    ]);
    $raw=curl_exec($ch); $err=curl_error($ch); curl_close($ch);
    if ($err||!$raw) return ['_error'=>$err?:'empty'];
    return json_decode($raw,true)??['_error'=>'json_fail'];
}
function ucm_login(): string {
    $r1=ucm_post(['action'=>'challenge','user'=>CDR_USER]);
    if (!isset($r1['response']['challenge'])) return '';
    $r2=ucm_post(['action'=>'login','user'=>CDR_USER,
        'token'=>md5($r1['response']['challenge'].CDR_PASS)]);
    return $r2['response']['cookie']??'';
}
function ucm_logout(string $c): void { if($c) ucm_post(['action'=>'logout','cookie'=>$c]); }

function parse_queueapi(array $data): array {
    $rows=$data['queue_statistics']??[];
    $total=$ans=$aban=$ttalk=$tc=$twait=$wn=0; $agAns=[];$agTalk=[];$agCnt=0;
    foreach ($rows as $item) {
        if (isset($item['agent'])) {
            $a=$item['agent']; $ext=(string)($a['agent']??''); $agCnt++;
            $ac=(int)($a['answered_calls']??0);
            if ($ext&&$ac>0) $agAns[$ext]=($agAns[$ext]??0)+$ac;
            $at=(float)($a['avg_talk']??0);
            if ($at>0&&$ac>0){$ttalk+=$at*$ac;$tc+=$ac;$agTalk[$ext]=($agTalk[$ext]??0)+(int)($at*$ac);}
        }
        if (isset($item['queue_details_total'])) $total++;
        if (isset($item['queue_details_answer'])) {
            $ans++;
            $wt=(int)($item['queue_details_answer']['wait_time']??0);
            if ($wt>0){$twait+=$wt;$wn++;}
        }
        if (isset($item['queue_details_abandon'])) $aban++;
    }
    if ($total===0) {
        foreach ($rows as $item) if (isset($item['agent'])) {
            $total+=(int)($item['agent']['total_calls']??0);
            $ans+=(int)($item['agent']['answered_calls']??0);
        }
        $aban=max(0,$total-$ans);
    }
    return ['total_calls'=>$total,'answered_calls'=>$ans,'abandoned_calls'=>$aban,
        'abandoned_rate'=>$total>0?round($aban/$total*100,2):0.0,
        'avg_wait_time'=>$wn>0?gmdate('H:i:s',(int)($twait/$wn)):'00:00:00',
        'avg_talk_time'=>$tc>0?gmdate('H:i:s',(int)($ttalk/$tc)):'00:00:00',
        'agent_answered'=>$agAns,'agent_talk'=>$agTalk,'stat_count'=>$agCnt];
}

// ── Get live pause state from queueapi pausedhistory ─────────────────────
// Returns array of ext => ['paused'=>true/false,'reason'=>'','since'=>'']
function get_live_pause_state(string $cookie, string $today): array {
    $r = ucm_post([
        'action'         => 'queueapi',
        'cookie'         => $cookie,
        'startTime'      => $today,
        'endTime'        => $today,
        'format'         => 'json',
        'statisticsType' => 'pausedhistory',
    ]);
    $rows = $r['queue_statistics'] ?? [];
    // Build: for each agent track their latest pause record
    // If pause_data set but unpause_data empty => currently paused
    $latest = []; // ext => latest row by pause_data timestamp
    foreach ($rows as $item) {
        if (!isset($item['agent'])) continue;
        $a   = $item['agent'];
        $ext = (string)($a['agent'] ?? '');
        if (!$ext || $ext === 'NONE') continue;
        $pd  = $a['pause_data']   ?? '';
        $upd = $a['unpause_data'] ?? '';
        if (!$pd) continue;
        // Keep the latest pause record per agent
        if (!isset($latest[$ext]) || $pd > ($latest[$ext]['pause_data'] ?? '')) {
            $latest[$ext] = ['pause_data'=>$pd,'unpause_data'=>$upd];
        }
    }
    $state = [];
    foreach ($latest as $ext => $rec) {
        $paused = empty($rec['unpause_data']);
        $state[$ext] = [
            'paused' => $paused,
            'reason' => '',           // UCM doesn't return reason in pausedhistory
            'since'  => $paused ? $rec['pause_data'] : '',
        ];
    }
    return $state;
}

// ── AJAX ──────────────────────────────────────────────────────────────────
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $action=$_GET['ajax']; $today=date('Y-m-d');

    // PAUSE STATE (no UCM call needed)
    if ($action==='set_pause') {
        $ext    = trim($_GET['ext']??'');
        $paused = ($_GET['pause']??'yes')==='yes';
        $reason = trim($_GET['reason']??'');
        if (!$ext) { echo json_encode(['ok'=>false,'error'=>'no ext']); exit; }
        savePauseState($ext, $paused, $reason);
        echo json_encode(['ok'=>true,'paused'=>$paused,'ext'=>$ext,'reason'=>$reason]); exit;
    }

    // GET PAUSE STATE
    if ($action==='get_pause') {
        echo json_encode(['ok'=>true,'state'=>getPauseState()]); exit;
    }

    $cookie=ucm_login();
    if (!$cookie) { echo json_encode(['error'=>'Login failed']); exit; }

    // DEBUG
    if ($action==='debug') {
        $qE=trim($_GET['queue']??'');
        $out=['_login_ok'=>true];
        $out['listAccount']     = ucm_post(['action'=>'listAccount','cookie'=>$cookie,'options'=>'extension,status,fullname']);
        $out['listQueue']       = ucm_post(['action'=>'listQueue','cookie'=>$cookie]);
        $out['listBridged']     = ucm_post(['action'=>'listBridgedChannels','cookie'=>$cookie]);
        $out['listUnbridged']   = ucm_post(['action'=>'listUnBridgedChannels','cookie'=>$cookie]);
        $out['getQueueCalling'] = ucm_post(['action'=>'getQueueCalling','cookie'=>$cookie,'extension'=>$qE?:'6500']);
        $out['_pause_state']    = getPauseState();
        $out['_pausedhistory']  = ucm_post(['action'=>'queueapi','cookie'=>$cookie,'startTime'=>date('Y-m-d'),'endTime'=>date('Y-m-d'),'format'=>'json','statisticsType'=>'pausedhistory']);
        ucm_logout($cookie);
        echo json_encode($out,JSON_PRETTY_PRINT); exit;
    }

    // LIST QUEUES
    if ($action==='queues') {
        $r=ucm_post(['action'=>'listQueue','cookie'=>$cookie]);
        ucm_logout($cookie);
        $queues=[];
        foreach (($r['response']['queue']??[]) as $q)
            $queues[]=['extension'=>$q['extension']??'','queue_name'=>$q['queue_name']??''];
        echo json_encode(['queues'=>$queues]); exit;
    }

    // SWITCHBOARD
    if ($action==='switchboard') {
        $qExt=trim($_GET['queue']??'');
        $rQapi    =ucm_post(['action'=>'queueapi','cookie'=>$cookie,'startTime'=>$today,'endTime'=>$today,'format'=>'json']);
        $rPaused  =ucm_post(['action'=>'queueapi','cookie'=>$cookie,'startTime'=>$today,'endTime'=>$today,'format'=>'json','statisticsType'=>'pausedhistory']);
        $rList    =ucm_post(['action'=>'listQueue','cookie'=>$cookie]);
        $rBridged =ucm_post(['action'=>'listBridgedChannels','cookie'=>$cookie]);
        $rUnbridge=ucm_post(['action'=>'listUnBridgedChannels','cookie'=>$cookie]);
        $rAccounts=ucm_post(['action'=>'listAccount','cookie'=>$cookie,'options'=>'extension,status,fullname']);
        ucm_logout($cookie);

        // Merge: UCM live pause state takes priority; local file is fallback
        $livePause = [];
        $pauseRows = $rPaused['queue_statistics'] ?? [];
        $latestPause = [];
        foreach ($pauseRows as $item) {
            if (!isset($item['agent'])) continue;
            $a = $item['agent'];
            $ext = (string)($a['agent'] ?? '');
            if (!$ext || $ext === 'NONE') continue;
            $pd  = $a['pause_data']   ?? '';
            $upd = $a['unpause_data'] ?? '';
            if (!$pd) continue;
            if (!isset($latestPause[$ext]) || $pd > ($latestPause[$ext]['pause_data'] ?? ''))
                $latestPause[$ext] = [
                    'pause_data'   => $pd,
                    'unpause_data' => $upd,
                    'pause_reason' => $a['pause_reason'] ?? '',
                ];
        }
        foreach ($latestPause as $ext => $rec) {
            // UCM returns "-" when agent is still paused (not yet unpaused)
            $stillPaused = empty($rec['unpause_data']) || $rec['unpause_data'] === '-';
            $livePause[$ext] = [
                'paused' => $stillPaused,
                'reason' => $rec['pause_reason'],
                'since'  => $stillPaused ? $rec['pause_data'] : '',
            ];
        }
        $pauseState = !empty($livePause) ? $livePause : getPauseState();

        // Queue
        $qObj=[];
        foreach (($rList['response']['queue']??[]) as $q)
            if (($q['extension']??'')===$qExt){$qObj=$q;break;}
        if (empty($qObj)){echo json_encode(['error'=>"Queue {$qExt} not found"]);exit;}
        $queueName=$qObj['queue_name']??$qExt;

        // Members (static + dynamic)
        $memberExts=[];
        $rawM=$qObj['members']??'';
        if (is_string($rawM)&&$rawM!=='')
            $memberExts=array_values(array_filter(array_map('trim',explode(',',$rawM))));
        $dynRaw=$qObj['dynamic_members']??'';
        $dynExts=(is_string($dynRaw)&&$dynRaw!=='')?array_values(array_filter(array_map('trim',explode(',',$dynRaw)))):[];
        $allExts=array_unique(array_merge($memberExts,$dynExts));

        // Account map
        $acctMap=[];
        foreach (($rAccounts['response']['account']??[]) as $a)
            if ($e=($a['extension']??'')) $acctMap[$e]=$a;

        // Channels
        $now=time(); $busyAgents=[];$ringingAgents=[];$waiting=[];$proceeding=[];
        foreach ((array)($rBridged['response']['channel']??[]) as $ch) {
            if (($ch['feature_calleenum']??'')===$qExt) {
                $caller=$ch['callerid1']??'--'; $agent=$ch['callerid2']??'--';
                $elapsed=$now-(int)($ch['bridge_timestamp']??$now);
                $ttime=gmdate('H:i:s',max(0,$elapsed)); $chan=$ch['channel1']??'';
                if (in_array($agent,$allExts)) $busyAgents[$agent]=true;
                $proceeding[]=['status'=>'Proceeding','caller'=>$caller,'callee'=>$agent,'talk_time'=>$ttime,'channel'=>$chan];
            }
        }
        $seenLinked=[];
        foreach ((array)($rUnbridge['response']['channel']??[]) as $ch) {
            $src=$ch['callernum']??''; $dst=$ch['connectednum']??'';
            $qf=$ch['feature_calleenum']??'';
            $svc =strtolower($ch['service']     ??'');
            $dsvc=strtolower($ch['dial_service'] ??'');
            $lnk=$ch['linkedid']??$ch['uniqueid']??'';
            $elapsed=$now-(int)($ch['alloc_timestamp']??$now);
            $etime=gmdate('H:i:s',max(0,$elapsed)); $chan=$ch['channel']??'';
            // Ringing agent: service=normal + dial_service=Queue + callernum is an agent
            if ($qf===$qExt&&in_array($src,$allExts)&&$svc==='normal'&&$dsvc==='queue'){
                $ringingAgents[$src]=true; continue;
            }
            // Waiting caller: service=Queue (the inbound trunk leg)
            if ($dst===$qExt&&$svc==='queue'&&!isset($seenLinked[$lnk])){
                $seenLinked[$lnk]=true;
                $waiting[]=['status'=>'Waiting','caller'=>$src?:'--','callee'=>$dst,
                    'position'=>$ch['position']??'1','wait_time'=>$etime,'channel'=>$chan];
            }
        }

        // Stats
        $qapiData=isset($rQapi['queue_statistics'])?$rQapi:(isset($rQapi['response']['queue_statistics'])?$rQapi['response']:[]);
        $stats=parse_queueapi($qapiData);

        // Agents
        $agents=[];
        foreach ($allExts as $ext) {
            $acct=$acctMap[$ext]??[];
            $type=in_array($ext,$dynExts)?'Dynamic':'Static';
            $ps=$pauseState[$ext]??null;
            // Pause state (supervisor-tracked) overrides everything except Busy
            if (isset($busyAgents[$ext]))         $status='Busy';
            elseif ($ps&&($ps['paused']??false))  $status='Paused';
            elseif (isset($ringingAgents[$ext]))  $status='Ringing';
            else {
                $raw=strtolower($acct['status']??'idle');
                $status=match($raw){
                    'inuse','in use'=>'Busy','ringing'=>'Ringing',
                    'unavailable','unregistered','offline'=>'Unavailable',
                    default=>$acct['status']?:'Idle'};
            }
            $cumSecs=$stats['agent_talk'][$ext]??0; $liveSecs=0;
            foreach ($proceeding as $pc) {
                if (($pc['callee']??'')===$ext){
                    $p=explode(':',$pc['talk_time']);
                    $liveSecs=(int)($p[0]??0)*3600+(int)($p[1]??0)*60+(int)($p[2]??0); break;
                }
            }
            $fn=trim($acct['fullname']??'');
            $agents[]=['ext_status'=>$status,'agent'=>$ext,'name'=>$fn?:$ext,
                'answered'=>$stats['agent_answered'][$ext]??0,
                'talk_time'=>gmdate('H:i:s',$cumSecs+$liveSecs),
                'agent_status'=>$type,
                'pause_reason'=>$ps['reason']??'',
                'pause_since'=>$ps['since']??''];
        }
        $online=count(array_filter($agents,fn($a)=>in_array(strtolower($a['ext_status']),['busy','ringing'])));

        echo json_encode(['ts'=>date('H:i:s'),'queue_name'=>$queueName,'queue_ext'=>$qExt,
            'stats'=>['members_online'=>$online,'members_total'=>count($agents),
                'total_calls'=>$stats['total_calls'],'answered_calls'=>$stats['answered_calls'],
                'waiting_calls'=>count($waiting),'abandoned_calls'=>$stats['abandoned_calls'],
                'avg_wait_time'=>$stats['avg_wait_time'],
                'avg_talk_time'=>$stats['avg_talk_time'],'abandoned_rate'=>$stats['abandoned_rate']],
            'waiting'=>$waiting,'proceeding'=>$proceeding,'agents'=>$agents,
            '_stat_count'=>$stats['stat_count']]); exit;
    }

    // HANGUP
    if ($action==='agent_hangup') {
        $r=ucm_post(['action'=>'hangup','cookie'=>$cookie,'channel'=>$_GET['channel']??'']);
        ucm_logout($cookie);
        echo json_encode(['ok'=>($r['status']??-1)===0]); exit;
    }


    ucm_logout($cookie);
    echo json_encode(['error'=>'Unknown action']); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Queue Switchboard</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{--bg:#eef0f6;--surface:#fff;--border:#e2e5ef;--text:#1a1d27;--muted:#6b7280;
  --accent:#4f6ef7;--accent2:#7c3aed;--green:#10b981;--red:#ef4444;--amber:#f59e0b;
  --radius:12px;--font:'DM Sans',sans-serif;--mono:'DM Mono',monospace}
body{font-family:var(--font);background:var(--bg);color:var(--text);min-height:100vh}
.topbar{background:linear-gradient(135deg,#1a1d2e,#252a4a);color:#fff;height:58px;padding:0 24px;
  display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 16px rgba(0,0,0,.3);position:sticky;top:0;z-index:200}
.topbar-left{display:flex;align-items:center;gap:12px}
.logo{width:34px;height:34px;background:linear-gradient(135deg,#4f6ef7,#7c3aed);border-radius:9px;display:grid;place-items:center;font-size:17px}
.tb-title{font-size:15px;font-weight:700}
.live-pill{display:flex;align-items:center;gap:5px;background:rgba(16,185,129,.18);color:#34d399;font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:20px}
.live-dot{width:7px;height:7px;background:#10b981;border-radius:50%;animation:blink 1.6s infinite}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.35}}
.ts{font-size:12px;color:rgba(255,255,255,.4);font-family:var(--mono)}
.btn-quit{background:rgba(239,68,68,.18);color:#fca5a5;border:1px solid rgba(239,68,68,.3);border-radius:8px;padding:6px 16px;font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)}
.btn-quit:hover{background:#ef4444;color:#fff}
.btn-debug{background:rgba(245,158,11,.15);color:#fcd34d;border:1px solid rgba(245,158,11,.3);border-radius:8px;padding:6px 14px;font-size:12px;font-weight:600;cursor:pointer;font-family:var(--font)}
.btn-debug:hover,.btn-debug.on{background:#f59e0b;color:#fff}
.page{padding:22px 24px;max-width:1440px;margin:0 auto}
.queue-bar{display:flex;align-items:center;gap:12px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px 20px;margin-bottom:20px}
.queue-bar label{font-size:13px;font-weight:600;color:var(--muted)}
.queue-select{flex:1;min-width:200px;max-width:360px;padding:9px 14px;border:1.5px solid var(--border);border-radius:8px;font-size:14px;font-family:var(--font);background:var(--bg)}
.btn-load{background:linear-gradient(135deg,#4f6ef7,#7c3aed);color:#fff;border:none;padding:9px 20px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:var(--font)}
.banner{display:none;padding:12px 18px;border-radius:8px;font-size:13px;margin-bottom:16px}
.banner.err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
.banner.info{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
.debug-panel{display:none;background:#1e2235;color:#a5f3a5;font-family:var(--mono);font-size:12px;
  border-radius:var(--radius);padding:18px;margin-bottom:18px;overflow:auto;white-space:pre;max-height:500px;border:1px solid #2d3561}
.grid-top{display:grid;grid-template-columns:260px 1fr;gap:18px;margin-bottom:18px}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden}
.card-hdr{display:flex;align-items:center;justify-content:space-between;padding:13px 18px 11px;border-bottom:1px solid var(--border)}
.card-hdr-title{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted)}
.cnt-badge{background:var(--accent);color:#fff;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;font-family:var(--mono)}
.stats-note{font-size:11px;padding:7px 18px 8px;border-bottom:1px solid var(--border)}
.stats-note.ok{background:#f0fdf4;color:#166534}.stats-note.warn{background:#fff7ed;color:#92400e}
.gauge-card{padding:24px 18px 18px;text-align:center}
.queue-label{font-family:var(--mono);font-size:15px;font-weight:700;margin-bottom:18px}
.gauge-wrap{position:relative;width:136px;height:136px;margin:0 auto 18px}
.gauge-svg{transform:rotate(-90deg)}
.g-bg{fill:none;stroke:#e8eaf0;stroke-width:10}
.g-fill{fill:none;stroke:url(#gg);stroke-width:10;stroke-linecap:round;stroke-dasharray:345;stroke-dashoffset:345;transition:stroke-dashoffset 1.1s ease}
.gauge-inner{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center}
.gauge-pct{font-size:20px;font-weight:700;color:var(--accent);font-family:var(--mono)}
.gauge-lbl{font-size:10.5px;color:var(--muted);margin-top:2px}
.mbr-row{display:flex;justify-content:center;gap:28px;padding:14px 0 2px;border-top:1px solid var(--border);margin-top:6px}
.mbr-cell{text-align:center}
.mbr-num{font-size:22px;font-weight:700;font-family:var(--mono)}
.mbr-lbl{font-size:10.5px;color:var(--muted);margin-top:2px}
.stat-row{display:flex;justify-content:space-between;align-items:center;padding:10px 18px;border-bottom:1px solid var(--border);font-size:13.5px}
.stat-row:last-child{border-bottom:none}.stat-row:hover{background:#f7f8fc}
.sr-lbl{color:var(--muted)}.sr-val{font-weight:600;font-family:var(--mono)}
.sr-val.time{color:var(--accent2);font-size:12.5px}
.tables-row{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px}
.tbl-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:12.5px}
thead tr{background:#f5f6fb}
th{padding:9px 14px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);white-space:nowrap;border-bottom:2px solid var(--border)}
td{padding:10px 14px;border-bottom:1px solid var(--border);vertical-align:middle;white-space:nowrap}
tbody tr:last-child td{border-bottom:none}tbody tr:hover{background:#f8f9ff}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700}
.badge::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor;opacity:.65}
.b-idle{background:#d1fae5;color:#065f46}.b-busy{background:#fee2e2;color:#991b1b}
.b-paused{background:#fef3c7;color:#92400e}.b-ringing{background:#dbeafe;color:#1e40af}
.b-waiting{background:#ede9fe;color:#5b21b6}.b-proceed{background:#dcfce7;color:#166534}
.b-unavail{background:#f3f4f6;color:#374151}
.empty{padding:32px 18px;text-align:center;color:var(--muted);font-size:12.5px}
.empty-ico{font-size:28px;opacity:.35;margin-bottom:8px}
.btn-act{background:#f1f3f9;border:1px solid var(--border);color:var(--muted);padding:3px 9px;border-radius:6px;font-size:11.5px;cursor:pointer;margin-right:3px;font-family:var(--font)}
.btn-act:hover{background:var(--accent);color:#fff}.btn-act.red:hover{background:var(--red);color:#fff}
.btn-act.amber:hover{background:var(--amber);color:#fff}.btn-act.green:hover{background:var(--green);color:#fff}
.mono{font-family:var(--mono);font-size:12px}
.pause-reason{font-size:11px;background:#fef3c7;color:#92400e;padding:2px 7px;border-radius:10px;margin-left:4px}
.pause-since{font-size:10px;color:var(--muted);display:block;margin-top:2px}
.pause-timer{font-size:12px;font-weight:700;display:inline-block;margin-left:5px;padding:2px 8px;border-radius:10px;background:#fef3c7;color:#92400e}
.pause-timer.warn{background:#fed7aa;color:#9a3412}
.pause-timer.over{background:#fee2e2;color:#991b1b;animation:blink 1s infinite}
/* Modal */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:500;align-items:center;justify-content:center}
.modal-bg.show{display:flex}
.modal{background:#fff;border-radius:14px;padding:28px;width:340px;box-shadow:0 8px 40px rgba(0,0,0,.18)}
.modal h3{font-size:16px;font-weight:700;margin-bottom:16px}
.modal label{font-size:12px;font-weight:600;color:var(--muted);display:block;margin-bottom:5px}
.modal input,.modal select{width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:14px;font-family:var(--font);margin-bottom:14px}
.modal-btns{display:flex;gap:10px;justify-content:flex-end}
.modal-btns button{padding:8px 18px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none;font-family:var(--font)}
.btn-cancel{background:#f1f3f9;color:var(--muted)}.btn-confirm{background:var(--amber);color:#fff}
@media(max-width:900px){.grid-top,.tables-row{grid-template-columns:1fr}.page{padding:14px}}
</style>
</head>
<body>

<!-- Pause Modal -->
<div class="modal-bg" id="pauseModal">
  <div class="modal">
    <h3>&#9646;&#9646; Mark Agent as Paused</h3>
    <label>Agent</label>
    <input type="text" id="pauseAgentExt" readonly style="background:#f5f6fb">
    <label>Pause Reason</label>
    <select id="pauseReason">
      <option value="">-- Select reason --</option>
      <option>Lunch</option><option>Break</option><option>Meeting</option>
      <option>Training</option><option>Send Email</option><option>Other</option>
    </select>
    <div class="modal-btns">
      <button class="btn-cancel" onclick="closePauseModal()">Cancel</button>
      <button class="btn-confirm" onclick="confirmPause()">&#9646;&#9646; Mark Paused</button>
    </div>
  </div>
</div>

<div class="topbar">
  <div class="topbar-left">
    <div class="logo">&#128222;</div>
    <span class="tb-title">Queue Switchboard</span>
    <div class="live-pill"><div class="live-dot"></div>LIVE</div>
  </div>
  <div style="display:flex;align-items:center;gap:10px">
    <span class="ts" id="last-ts">--:--:--</span>
    <!--button class="btn-debug" id="btn-debug" onclick="toggleDebug()">&#128269; Debug</button-->
    <button class="btn-quit" onclick="quit()">&#10005; Quit</button>
  </div>
</div>

<div class="page">
  <div class="banner err" id="err-banner"></div>
  <div class="banner info" id="info-banner"></div>
  <div class="debug-panel" id="debug-panel"></div>

  <div class="queue-bar">
    <label>Select Queue:</label>
    <select class="queue-select" id="queueSelect"><option value="">Loading...</option></select>
    <button class="btn-load" onclick="selectQueue()">&#9654; Load</button>
  </div>

  <div class="grid-top">
    <div class="card gauge-card">
      <div class="queue-label" id="q-label">select a queue</div>
      <div class="gauge-wrap">
        <svg class="gauge-svg" width="136" height="136" viewBox="0 0 136 136">
          <defs><linearGradient id="gg" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" stop-color="#4f6ef7"/><stop offset="100%" stop-color="#7c3aed"/></linearGradient></defs>
          <circle class="g-bg" cx="68" cy="68" r="55"/>
          <circle class="g-fill" cx="68" cy="68" r="55" id="gauge-arc"/>
        </svg>
        <div class="gauge-inner">
          <span class="gauge-pct" id="gauge-pct">0.00%</span>
          <span class="gauge-lbl">Abandoned Rate</span>
        </div>
      </div>
      <div class="mbr-row">
        <div class="mbr-cell"><div class="mbr-num" style="color:var(--green)" id="s-online">-</div><div class="mbr-lbl">Online</div></div>
        <div class="mbr-cell"><div class="mbr-num" style="color:var(--muted)" id="s-total-m">-</div><div class="mbr-lbl">Total</div></div>
      </div>
    </div>
    <div class="card">
      <div class="card-hdr"><span class="card-hdr-title">Queue Statistics</span></div>
      <div class="stats-note ok" id="stats-note">&#128197; Today's totals via queueapi</div>
      <div class="stat-row"><span class="sr-lbl">Members</span><span class="sr-val" id="s-members">-</span></div>
      <div class="stat-row"><span class="sr-lbl">Total Calls</span><span class="sr-val" id="s-total">-</span></div>
      <div class="stat-row"><span class="sr-lbl">Answered Calls</span><span class="sr-val" id="s-answered">-</span></div>
      <div class="stat-row"><span class="sr-lbl">Waiting Calls</span><span class="sr-val" id="s-waiting">-</span></div>
      <div class="stat-row"><span class="sr-lbl">Abandoned Calls</span><span class="sr-val" id="s-abandoned">-</span></div>
      <div class="stat-row"><span class="sr-lbl">Avg. Wait Time</span><span class="sr-val time mono" id="s-avgwait">-</span></div>
      <div class="stat-row"><span class="sr-lbl">Avg. Talk Time</span><span class="sr-val time mono" id="s-avgtalk">-</span></div>
    </div>
  </div>

  <div class="tables-row">
    <div class="card">
      <div class="card-hdr"><span class="card-hdr-title">Waiting Calls</span><span class="cnt-badge" id="cnt-waiting">0</span></div>
      <div class="tbl-wrap">
        <table><thead><tr><th>Status</th><th>Caller</th><th>Callee</th><th>Pos</th><th>Wait</th><th>Options</th></tr></thead>
        <tbody id="tbl-waiting"></tbody></table>
        <div class="empty" id="empty-waiting"><div class="empty-ico">&#128203;</div>No waiting calls</div>
      </div>
    </div>
    <div class="card">
      <div class="card-hdr"><span class="card-hdr-title">Proceeding Calls</span><span class="cnt-badge" id="cnt-proceed">0</span></div>
      <div class="tbl-wrap">
        <table><thead><tr><th>Status</th><th>Caller</th><th>Callee</th><th>Talk</th><th>Options</th></tr></thead>
        <tbody id="tbl-proceed"></tbody></table>
        <div class="empty" id="empty-proceed"><div class="empty-ico">&#128203;</div>No proceeding calls</div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:24px">
    <div class="card-hdr"><span class="card-hdr-title">Agents</span><span class="cnt-badge" id="cnt-agents">0</span></div>
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>Ext. Status</th><th>Agent</th><th>Name</th><th>Answered Today</th><th>Talk Time</th><th>Type</th><th>Pause Reason</th></tr></thead>
        <tbody id="tbl-agents"></tbody>
      </table>
      <div class="empty" id="empty-agents"><div class="empty-ico">&#128100;</div>No agents found</div>
    </div>
  </div>
</div>

<script>
const SESS_P="<?php echo addslashes($_SESSION['oop']??'');?>",
      SESS_Q="<?php echo addslashes($_SESSION['ooq']??'');?>";
let currentQueue='',refreshTimer=null,debugOpen=false,pauseTargetExt='';

function quit(){window.location.replace('test204.php?page='+SESS_P+'&page1='+SESS_Q);}

function badge(s){
  const m={'idle':'b-idle','available':'b-idle','busy':'b-busy','in use':'b-busy','inuse':'b-busy',
    'paused':'b-paused','ringing':'b-ringing','waiting':'b-waiting','proceeding':'b-proceed',
    'unavailable':'b-unavail','unregistered':'b-unavail','offline':'b-unavail'};
  return`<span class="badge ${m[(s||'').toLowerCase()]||'b-idle'}">${s||'Idle'}</span>`;
}
function setGauge(p){
  document.getElementById('gauge-arc').style.strokeDashoffset=2*Math.PI*55*(1-Math.min(p,100)/100);
  document.getElementById('gauge-pct').textContent=parseFloat(p).toFixed(2)+'%';
}
function showErr(m){const b=document.getElementById('err-banner');b.innerHTML='&#9888; '+m;b.style.display='block';setTimeout(()=>b.style.display='none',8000);}
function showInfo(m){const b=document.getElementById('info-banner');b.textContent=m;b.style.display='block';setTimeout(()=>b.style.display='none',4000);}
function esc(s){return(s||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'")}

// ── Pause modal ─────────────────────────────────────────────────────────
function openPauseModal(ext){
  pauseTargetExt=ext;
  document.getElementById('pauseAgentExt').value=ext;
  document.getElementById('pauseReason').value='';
  document.getElementById('pauseModal').classList.add('show');
}
function closePauseModal(){ document.getElementById('pauseModal').classList.remove('show'); }
async function confirmPause(){
  const reason=document.getElementById('pauseReason').value;
  await fetch(`?ajax=set_pause&ext=${encodeURIComponent(pauseTargetExt)}&pause=yes&reason=${encodeURIComponent(reason)}`);
  closePauseModal(); showInfo('Agent '+pauseTargetExt+' marked as paused.'); loadSwitchboard();
}
async function doResume(ext){
  if(!confirm('Mark agent '+ext+' as resumed?'))return;
  await fetch(`?ajax=set_pause&ext=${encodeURIComponent(ext)}&pause=no`);
  showInfo('Agent '+ext+' resumed.'); loadSwitchboard();
}

// ── Debug ────────────────────────────────────────────────────────────────
async function toggleDebug(){
  const dp=document.getElementById('debug-panel'),btn=document.getElementById('btn-debug');
  if(debugOpen){dp.style.display='none';btn.classList.remove('on');debugOpen=false;return;}
  debugOpen=true;btn.classList.add('on');dp.textContent='Loading...';dp.style.display='block';
  try{
    const url='?ajax=debug'+(currentQueue?'&queue='+encodeURIComponent(currentQueue):'');
    const txt=await(await fetch(url)).text();
    try{dp.textContent=JSON.stringify(JSON.parse(txt),null,2);}catch{dp.textContent=txt;}
  }catch(e){dp.textContent='Error: '+e.message;}
}

// ── Queue loading ────────────────────────────────────────────────────────
async function loadQueues(){
  try{
    const data=await(await fetch('?ajax=queues')).json();
    if(data.error){showErr(data.error);return;}
    const sel=document.getElementById('queueSelect');
    sel.innerHTML='<option value="">-- Select a queue --</option>';
    (data.queues||[]).forEach(q=>{const o=document.createElement('option');o.value=q.extension;o.textContent=q.extension+'  ('+q.queue_name+')';sel.appendChild(o);});
    if(sel.options.length>1){sel.selectedIndex=1;selectQueue();}
  }catch(e){showErr('Cannot reach UCM: '+e.message);}
}
function selectQueue(){currentQueue=document.getElementById('queueSelect').value;if(!currentQueue)return;loadSwitchboard();startRefresh();}

async function loadSwitchboard(){
  if(!currentQueue)return;
  try{
    const data=await(await fetch('?ajax=switchboard&queue='+encodeURIComponent(currentQueue))).json();
    if(data.error){showErr(data.error);return;}
    document.getElementById('last-ts').textContent=data.ts;
    document.getElementById('q-label').textContent=data.queue_ext+'  ('+data.queue_name+')';
    const note=document.getElementById('stats-note'),qc=data._stat_count||0;
    if(qc>0){note.className='stats-note ok';note.textContent='Today\'s stats via queueapi ('+qc+' agent record'+(qc>1?'s':'')+')';}
    else{note.className='stats-note warn';note.textContent='No stats yet today';}
    const s=data.stats;
    setGauge(parseFloat(s.abandoned_rate)||0);
    document.getElementById('s-online').textContent=s.members_online;
    document.getElementById('s-total-m').textContent=s.members_total;
    document.getElementById('s-members').textContent=s.members_online+' / '+s.members_total;
    document.getElementById('s-total').textContent=s.total_calls;
    document.getElementById('s-answered').textContent=s.answered_calls;
    document.getElementById('s-waiting').textContent=s.waiting_calls;
    document.getElementById('s-abandoned').textContent=s.abandoned_calls;
    document.getElementById('s-avgwait').textContent=s.avg_wait_time;
    document.getElementById('s-avgtalk').textContent=s.avg_talk_time;
    renderWaiting(data.waiting||[]);
    renderProceeding(data.proceeding||[]);
    renderAgents(data.agents||[]);
  }catch(e){showErr('Refresh error: '+e.message);}
}

function renderWaiting(rows){
  const tb=document.getElementById('tbl-waiting'),em=document.getElementById('empty-waiting');
  document.getElementById('cnt-waiting').textContent=rows.length;
  if(!rows.length){tb.innerHTML='';em.style.display='block';return;}
  em.style.display='none';
  tb.innerHTML=rows.map(r=>`<tr><td>${badge(r.status)}</td><td class="mono">${r.caller}</td><td class="mono">${r.callee}</td><td style="text-align:center">${r.position}</td><td class="mono">${r.wait_time}</td><td><button class="btn-act red" onclick="doHangup('${esc(r.channel)}')">&#10005; Hangup</button></td></tr>`).join('');
}
function renderProceeding(rows){
  const tb=document.getElementById('tbl-proceed'),em=document.getElementById('empty-proceed');
  document.getElementById('cnt-proceed').textContent=rows.length;
  if(!rows.length){tb.innerHTML='';em.style.display='block';return;}
  em.style.display='none';
  tb.innerHTML=rows.map(r=>`<tr>
    <td>${badge(r.status)}</td>
    <td class="mono">${r.caller}</td>
    <td class="mono">${r.callee}</td>
    <td class="mono">${r.talk_time}</td>
    <td>
      <button class="btn-act blue"  onclick="showBarge('${esc(r.callee)}')">&#128266; Barge</button>
      <button class="btn-act amber" onclick="showTransfer('${esc(r.callee)}')">&#8644; Transfer</button>
      <button class="btn-act red"   onclick="doHangup('${esc(r.channel)}')">&#10005; Hangup</button>
    </td>
  </tr>`).join('');
}
function renderAgents(rows){
  const tb=document.getElementById('tbl-agents'),em=document.getElementById('empty-agents');
  document.getElementById('cnt-agents').textContent=rows.length;
  if(!rows.length){tb.innerHTML='';em.style.display='block';return;}
  em.style.display='none';
  tb.innerHTML=rows.map(r=>{
    const paused=(r.ext_status||'').toLowerCase()==='paused';
    const reasonCell=paused
      ?(r.pause_reason?`<span class="pause-reason">${r.pause_reason}</span>`:'')
        +(r.pause_since?`<span class="pause-timer mono" data-since="${r.pause_since}"> &#9201; 00:00:00</span>`:'')
      :'-';
    return`<tr><td>${badge(r.ext_status)}</td><td class="mono">${r.agent}</td><td>${r.name}</td><td style="text-align:center;font-weight:600">${r.answered}</td><td class="mono">${r.talk_time}</td><td>${r.agent_status}</td><td>${reasonCell}</td></tr>`;
  }).join('');
}

async function doHangup(ch){
  if(!ch||!confirm('Hang up this call?'))return;
  const d=await(await fetch('?ajax=agent_hangup&channel='+encodeURIComponent(ch))).json();
  if(d.ok){showInfo('Call ended.');loadSwitchboard();}else showErr('Hangup failed.');
}
function showBarge(agentExt){
  const msg=
    '📞 Dial from your phone:\n\n'+
    '  *54'+agentExt+'  →  Listen only (silent)\n'+
    '  *55'+agentExt+'  →  Whisper to agent\n'+
    '  *56'+agentExt+'  →  Full barge (3-way)';
  alert(msg);
}

function showTransfer(agentExt){
  const msg=
    '📞 Ask agent '+agentExt+' to transfer from their phone:\n\n'+
    '  #1 + <ext>        →  Blind Transfer (immediate)\n'+
    '  *2 + <ext> + *2   →  Attended Transfer (talk first)\n\n'+
    'Note: Enable these in UCM → Feature Codes → Feature Maps';
  alert(msg);
}

function startRefresh(){clearInterval(refreshTimer);if(currentQueue)refreshTimer=setInterval(loadSwitchboard,5000);}

// ── Pause duration timer ─────────────────────────────────────────────────
function tickPauseTimers(){
  document.querySelectorAll('.pause-timer[data-since]').forEach(el=>{
    const since=new Date(el.dataset.since.replace(' ','T'));
    if(isNaN(since))return;
    const secs=Math.floor((Date.now()-since)/1000);
    const h=String(Math.floor(secs/3600)).padStart(2,'0');
    const m=String(Math.floor((secs%3600)/60)).padStart(2,'0');
    const s=String(secs%60).padStart(2,'0');
    el.textContent=` ⏱ ${h}:${m}:${s}`;
    el.classList.remove('warn','over');
    if(secs>=1800)el.classList.add('over');       // >30 min → red flash
    else if(secs>=900)el.classList.add('warn');   // >15 min → orange
  });
}
setInterval(tickPauseTimers,1000);

loadQueues();
</script>
</body>
</html>