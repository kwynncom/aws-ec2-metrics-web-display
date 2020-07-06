<?php

require_once('/opt/kwynn/kwutils.php');
require_once('awsConfig.php');

if (isAWS()) getIIInsideAWS();
else getInstanceType();

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
    $instance_type = $it; unset($it);
    
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

function getInstanceType($url = KWYNN_GET_AWS_INSTANCE_TYPE_URL) {
    
try {
    kwas(iscli() || time() < strtotime('2020-07-06 04:00'), 'cli only');
    $key = 'default_socket_timeout';
    $dst = ini_get($key);
    ini_set($key, 4);
    $res = file_get_contents($url);
    ini_set($key, $dst);
    $len = strlen($res);
    kwas(preg_match('/^[a-z0-9]{2,20}\.[a-z0-9]{1,20}$/', $res),'bad instance type format');
    return $res;
} catch (Exception $ex) { return ''; }
    
}