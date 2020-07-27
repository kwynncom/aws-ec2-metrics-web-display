<?php

require_once('/opt/kwynn/kwutils.php');
require_once('dao.php');
require_once('getawsacctid.php');
require_once(__DIR__ . '/' . 'getCreds.php');

function getConfig($dao = false) {
    
    if (!$dao) $dao = new aws_metrics_dao();
    $c = getAWSCreds(); extract($c);
    kwas($c && is_array($c), 'cannot find AWS creds');
    
    $r = preg_match_all('/([^\s]+)\s+=\s+([^\s]+)/', $c['creds'], $m); kwas(isset($m[2][1]), 'creds preg fail CPUBal');
    for($i=0; $i <= 1; $i++) {
	$s = trim(strtoupper($m[1][$i]) . '=' . $m[2][$i]); kwas(strlen($s) > 20, 'bad uname / pass strlen CPUBal');
	putenv($s);
    }
  
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
    
    // curl http://169.254.169.254/latest/meta-data/instance-type   results in t3a.nano
    
    if (getHostInfo() === 'AWS-EC2') {
	$iid = trim(shell_exec(            '/usr/bin/wget -q -O - http://169.254.169.254/latest/meta-data/instance-id'));
	$reg = rmSubRegion(trim(shell_exec('/usr/bin/wget -q -O - http://169.254.169.254/latest/meta-data/placement/availability-zone')));
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

