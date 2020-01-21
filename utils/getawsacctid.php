<?php  

require_once('kwutils.php');
require_once('machineInfo.php');

if (PHP_SAPI === 'cli' && $argc > 1 && $argv[1] === 'test') {
    echo getAWSAcctId();
}

function getAWSAcctId() {
    if (getHostInfo() === 'AWS-EC2') return getAcctId169();
    else return getAcctIdFromSTS();
}

function getAcctIdFromSTS() {
    $json = shell_exec('aws sts  get-caller-identity');
    return getAcctIdFromJSON($json);
}

function getAcctIdFromJSON($json) {
    $n = '"arn:aws:iam::';
    $p = strpos($json, $n); 
    $s = substr($json, $p + strlen($n)); unset($n, $p);

    preg_match('/\d+/', $s, $matches); kwas(isset($matches[0]), 'error3'); unset($s);
    $aid = $matches[0]; unset($matches);
    kwas(is_string($aid) && strlen($aid) >= 6, 'error4');

    return $aid;   
}

function getAcctId169() {
try {
    
$json = shell_exec('/usr/bin/wget -q -O - http://169.254.169.254/latest/meta-data/iam/info');
$arr = json_decode($json, 1);

kwas(isset($arr['Code']) &&
	   $arr['Code'] === 'Success', 'getAcctId169() error2'); unset($arr);
	   

return getAcctIdFromJSON($json);


} catch (Exception $ex) {    kwas(0, 'error:' . $ex->getMessage()); }
}