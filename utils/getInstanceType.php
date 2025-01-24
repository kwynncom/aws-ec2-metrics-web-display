<?php

require_once('/opt/kwynn/kwutils.php');
require_once('awsConfig.php');
require_once(__DIR__ . '/imdsv2/imdsv2.php');

// awsInstanceType::get(0,0,1);

class awsInstanceType {
      
    public static function get($iidin = false, $regid = false, $direct = false) {
	$rout = self::getITypeOutsideAWS($iidin, $regid);
	if ($rout) return $rout;
	return self::getITypeInsideAWS($iidin, $direct);
    }
    
    public static function isTypeFormat($tin) { return preg_match('/^[a-z0-9]{1,20}\.[a-z0-9]{1,20}$/', $tin); }
    
private static function getITypeInsideAWS($iin, $direct) {

    if (!isAWS()) return;
    
    $it = imdsv2Cl::get('/meta-data/instance-type'); 
    if (!$direct) return $it;
    if (!$iin && !isset($_REQUEST['iid'])) self::simpleInstTyAndExit($it);
    
    if ($iin) $inid = $iin;
    else      $inid = $_REQUEST['iid']; unset($iin);
    
    kwas(preg_match('/^i-[0-9a-f]{8,17}$/', $inid), 'bad incoming iid format');
    
    $aiid = imdsv2Cl::get('/meta-data/instance-id');
    if ($aiid !== $inid) self::simpleInstTyAndExit($it); unset($inid);
    
    $iid = $aiid; unset($aiid);
    $itype = $it; unset($it);
    
    unset($http_response_header);
    $vars = get_defined_vars();
    
    header('application/json');
    echo json_encode($vars);
}

private static function simpleInstTyAndExit($it) {
    header('Content-Type: text/plain');
    echo $it;
    exit(0);
}

private static function getInstanceType($urlin = KWYNN_GET_AWS_INSTANCE_TYPE_URL, $iidin = false) {
    
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
     
    kwas(self::isTypeFormat($itype),'bad instance type format');
    return $itype;
} catch (Exception $ex) { return ''; }
    
}

private static function getITypeOutsideAWS($iin = false, $regid = false) {
    global $argc;
    global $argv;
    
    if (isAWS()) return;
    
    if (!$iin && !didCLICallMe(__FILE__)) return;
    if (!$iin && $argc < 2) die('error: the iid must be the first argument - other process or func' . "\n");
    
    if ($iin) $iid = $iin;
    else      $iid = $argv[1];

    $cmd = "/usr/local/bin/aws ec2 describe-instances --region $regid --instance-ids $iid";
    
    $res = shell_exec("/usr/local/bin/aws ec2 describe-instances --region $regid --instance-ids $iid");
    $a   = json_decode($res, 1);
    
    $itype = $a['Reservations'][0]['Instances'][0]['InstanceType'];
    
    if (!$iin) { 
	echo $itype . "\n";
	exit(0);
    }
    
    return $itype;
    
    // In my specific case, this works: $itype = self::getInstanceType(KWYNN_GET_AWS_INSTANCE_TYPE_URL, $argv[1]);
}
}
