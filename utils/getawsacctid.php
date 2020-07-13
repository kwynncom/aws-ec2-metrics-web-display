<?php  

require_once('/opt/kwynn/kwutils.php');
require_once('machineInfo.php');


awsAcctId::test();

class awsAcctId {
    
    public static function test() {
	if (!didCLICallMe(__FILE__)) return;
	echo  self::get();
    }
    
    
    public static function get($iid = false, $dao = false) {
	$dbr = self::db($iid, $dao, 'from');
	if ($dbr) return $dbr;
	if (getHostInfo() === 'AWS-EC2') $res = self::get169();
	else $res = self::getSTS();
	self::db($iid, $dao, 'to', $res);
	return $res;
	
	
    }
    
    private static function db($iid, $dao, $dir, $v = false) {
	if (!$iid || !$dao) return;
	
	if ($dir === 'from') return $dao->getI($iid, 'acctid');
	$dao->putI($iid, 'acctid', $v);
	
	
    }
    

    private static function getSTS() {
	$json = shell_exec('aws sts  get-caller-identity');
	return self::getJSON($json);
    }

    private static function getJSON($json) {
	$n = '"arn:aws:iam::';
	$p = strpos($json, $n); 
	$s = substr($json, $p + strlen($n)); unset($n, $p);

	preg_match('/\d+/', $s, $matches); kwas(isset($matches[0]), 'error3'); unset($s);
	$aid = $matches[0]; unset($matches);
	kwas(is_string($aid) && strlen($aid) >= 6, 'error4');

	return $aid;   
    }

    private static function get169() {
    try {

    $json = shell_exec('/usr/bin/wget -q -O - http://169.254.169.254/latest/meta-data/iam/info');
    $arr = json_decode($json, 1);

    kwas(isset($arr['Code']) &&
	       $arr['Code'] === 'Success', 'getAcctId169() error2'); unset($arr);


    return self::getJSON($json);


    } catch (Exception $ex) {    kwas(0, 'error:' . $ex->getMessage()); }
    }
} // class