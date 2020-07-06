<?php

require_once('/opt/kwynn/kwutils.php');
require_once('awsConfig.php');

if (isAWS()) getIIInsideAWS();

function getIIInsideAWS() {

    if (isAWS()) {
 	$it = file_get_contents('http://169.254.169.254/latest/meta-data/instance-type'); 
	if (!isset($_REQUEST['iid'])) simpleInstTyAndExitAndExit($it);
    }
    
    $inid = $_REQUEST['iid'];
    kwas(preg_match('/^i-[0-9a-f]{8,17}$/', $inid), 'bad incoming iid format');
    
    if (!isAWS()) simpleInstTyAndExitAndExit($it);
    
    $aiid = file_get_contents('http://169.254.169.254/latest/meta-data/instance-id');
    if ($aiid !== $inid) simpleInstTyAndExitAndExit($it); unset($inid);
    
    $iid = $aiid; unset($aiid);
    $itype = $it; unset($it);
    
    unset($http_response_header);
    $vars = get_defined_vars();
    
    header('application/json');
    echo json_encode($vars);
}

function simpleInstTyAndExitAndExit($it) {
    header('Content-Type: text/plain');
    echo $it;
    exit(0);
}

function getInstanceType($urlin = KWYNN_GET_AWS_INSTANCE_TYPE_URL, $iidin = false) {
    
try {
    kwas(iscli() || time() < strtotime('2020-07-06 04:00'), 'cli only');
    
    $url = $urlin;
    if ($iidin) $url .= '?iid=' . $iidin;
    
    $key = 'default_socket_timeout';
    $dst = ini_get($key);
    ini_set($key, 4);
    $res = file_get_contents($url);
    ini_set($key, $dst);
    $len = strlen($res);
    
    if ($iidin) {
	$jarr = json_decode($res, 1);
	kwas(isset($jarr['iid'])	  , 'iid cannot be confirmed - 1');
	kwas(      $jarr['iid'] === $iidin, 'iid cannot be confirmed - 2');
	kwas(isset($jarr['itype']),         'bad instance type - 0202');
	$itype =   $jarr['itype'];
    } else $itype = $res;
     
    kwas(isAWSInstanceTypeFormat($itype),'bad instance type format');
    return $itype;
} catch (Exception $ex) { return ''; }
    
}

function getintyclitest() {
    global $argc;
    global $argv;
    
    if (!isCLITest(__FILE__)) return;
    if ($argc < 2) return;
    
    $res = getInstanceType(KWYNN_GET_AWS_INSTANCE_TYPE_URL, $argv[1]);
    echo $res . "\n";
    
}

getintyclitest();