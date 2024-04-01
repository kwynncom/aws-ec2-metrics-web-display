<?php

require_once('/opt/kwynn/kwutils.php');
require_once('dao.php');
require_once('getawsacctid.php');
require_once(__DIR__ . '/' . 'getCreds.php');
require_once(__DIR__ . '/imdsv2/imdsv2.php');

function putAWSCLICreds() {
	$c = file_get_contents('/var/kwynn/awscli_credentials.txt');
	$r = preg_match_all('/([^\s]+)\s+=\s+([^\s]+)/', $c, $m); kwas(isset($m[2][1]), 'creds preg fail CPUBal');
    for($i=0; $i <= 1; $i++) {
	$s = trim(strtoupper($m[1][$i]) . '=' . $m[2][$i]); kwas(strlen($s) > 20, 'bad uname / pass strlen CPUBal');
	putenv($s);
    }
	
}

function getConfig($dao = false) {
	    
    if (!$dao) $dao = new aws_metrics_dao();
	
	putAWSCLICreds();
	
	$cf = '/var/kwynn/awscpuInfo.json';
	if (is_readable($cf)) $c = json_decode(file_get_contents($cf), 1);
 
    $c['acctid'] = awsAcctId::get($c['iid'], $dao);
    
    $retf = ['reg', 'iid', 'acctid'];
    
    foreach($c as $key => $val) 
	if (!in_array($key, $retf)) unset($c[$key]);
	else 
	
    foreach($retf as $f) {
	    kwas(isset($c[$f]), 'required field ' . $f . ' fail CPUBal');
	    $val =     $c[$f];
	kwas(is_string($val) && strlen(trim($val)) > 1,'bad strings config CPUBal: ' . $f . ' ' . $val);
    }
    
    return $c;

    
}
function getHostInfo() {
    if (isAWS()) return 'AWS-EC2';
    if (getenv('KWYNN_ID_201701'   ) === 'aws-nano-1') return 'AWS-EC2';
    if (getenv('KWYNN_201704_LOCAL') === 'yes')        return 'Kwynn-local';
}

function getInstanceInfo($dao) {
    
    // curl http://1 6 9 . 2 5 4 . 1 6 9 . 2 5 4/latest/meta-data/instance-type   results in t3a.nano
    
    if (getHostInfo() === 'AWS-EC2') {
	$iid =		   trim(imdsv2Cl::get('/meta-data/instance-id'));
	$reg = rmSubRegion(trim(imdsv2Cl::get('/meta-data/placement/availability-zone')));
	$acctid = awsAcctId::get($iid, $dao);
	return ['iid' => $iid, 'reg' => $reg, 'acctid' => $acctid];
    } else {
	$c = getConfig($dao);
	return $c;
    }
}

if (PHP_SAPI === 'cli') {
    if (strpos($argv[0], pathinfo(__FILE__, PATHINFO_FILENAME)) !== false) {
	getConfig();
	echo 'OK - standalone test passes' . "\n";
    }
}

