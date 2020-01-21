<?php

require_once(__DIR__ . '/../utils/kwutils.php');
require_once(__DIR__ . '/../utils/utils.php');
require_once(__DIR__ . '/../utils/getawsacctid.php');
require_once(__DIR__ . '/../utils/dao.php');
require_once('parse.php');
require_once(__DIR__ . '/../utils/machineInfo.php');
require_once(__DIR__ . '/../utils/testMode.php');

class aws_cpu {
    
    // to better understand the constants - see commentary on the aws cli commands themselves
    const sind     = 86400; // seconds in a day
    const minInterval = 1200;  // always run at least these seconds of data
    const minDays  = self::minInterval / self::sind; // same as above in days
    const maxIDays = 12; // If running for more than n days of data, break it into 12 day periods
    const maxPer   = self::maxIDays * self::sind; // same in seconds
    const minPer   = 300; // minimum period as in if you run 1200 minutes end - start interval you'll run 4 periods === 1200 / 300
    const defaultDays = 30; // if never run before, run n days
    const rerunAtCPU = 71.98;
    
    const minpts =  1530413163; // a check on time calculations - min possible timestamp - June 30, 2018 10:46:03 PM GMT-04:00

private static function doCmds1($days, $dao) { // called from below; $days of data to get
    
    static $ts = false;
    
    if (!$ts) $ts = time();
    
    if ($days < self::minDays) $days = self::minDays;
    
    $secs = $days * self::sind;
    
    if ($days <= self::minDays) { // run for the minimum interval
	$sub   = self::minInterval;
	$per   = self::minPer;
    } else {
	$didLonger = true; // did a longer interval
	$sub = $secs;
	if ($days > self::maxIDays) $per = intval(round(self::maxIDays * self::sind * (2/3)));
	else			    $per = intval(round($secs * (2/3)));
    } unset($secs);
    
    $per = getPeriodDiv60($per); // periods must be divisible by 60.  The func is in utils.php

    $beginTS =  intval($ts  - $sub);

    $beg = $beginTS;
    $end = $ts;
    
    getAWSBEISO($beg, $end);
    
    $rarr['begin_ts'] = $beginTS; unset($beginTS);
    $rarr['end_iso'] = $end; unset($end);
    $rarr['end_exec_ts'] = $ts; unset($ts);
    $rarr['per_interval_s']    = $per;
    $rarr['per_interval_days'] = $per / self::sind; unset($per);
    $rarr['begin_iso'] = $beg; unset($beg);
    $rarr['interval_days'] = getDaysDBVal($days); unset($days);
    $rarr['exec_n'] = php_uname('n');
    $rarr['cmd_seq']  = $dao->getSeq('awscmdset');
    $rarr['status'] = 'pre-Fetch';
    $dao->put($rarr);

    self::doCmds2($rarr, $dao); // just below
    
    $rarr['status'] = 'OK'; // we haven't thrown an exception or we wouldn't be here
    $dao->put($rarr);
    
    if (isset($didLonger) && $rarr['cpu'] < self::rerunAtCPU) self::doCmds1(0, $dao);
  
}

private static function doCmds2(&$rarr, $dao) { // & means changes are carried back to the calling func
  
    $cmds = ['cpu', 'net'];
        
    foreach($cmds as $ctype) self::cliGet($rarr['begin_iso'], $rarr['end_iso'], $rarr['per_interval_s'], $ctype, $rarr, $dao);
}

public static function cliGet($beg = false, $end = false, $per = aws_cpu::minPer, $ctype = 'cpu', &$rarr = false, $dao = false) {
    
    $per = getPeriodDiv60($per);
    
    if (!$dao) $dao = new aws_metrics_dao();
 
    $cres = getInstanceInfo($dao); extract($cres); kwas(isset($reg), 'no region doc2 CPUBal');

    if ($rarr) $rarr = array_merge($rarr, $cres);
    
    if ($ctype === 'cpu') {
	$metric = 'CPUCreditBalance';
	$stat   = 'Minimum';
    } else {
	$metric = 'NetworkOut';
	$stat   = 'Sum';
    }

    if (!$rarr) getAWSBEISO($beg, $end);
    
    $cmd = getTheCmd($reg, $iid, $beg, $end, $per, $metric, $stat);

    $cbeg = microtime(1);
    $rawres = shell_exec($cmd);
    $cend = microtime(1); unset($cmd);
    $json = trim($rawres); unset($rawres);
    
    if ($rarr) {
	$arr  = json_decode($json, 1);  unset($json);
	$parr  = parseAWSMetric($arr, $metric, $stat, $ctype); unset($arr);
	$rarr  = array_merge($rarr, $parr); unset($parr);
	$rarr[$ctype . '_exec_s'] = $cend - $cbeg; unset($cend, $cbeg);
    } else return $json;
    
}

public static function awsMRegGet($dao = false, $daysin = false) {
    
    if (!$dao) $dao = new aws_metrics_dao();

    if (!$daysin) {
	$prev = $dao->getLatest();
	if (!$prev) $days = self::defaultDays;
	else $days = (time() - $prev['end_exec_ts']) / self::sind;
    } else $days = $daysin;
    if ($days > self::minPer / self::sind || isTest('alwaysCheck')) self::doCmds1($days, $dao);
}
}

// allows you to run a simplified get from the command line, if you call this file directly
// usage:    php get.php 2019-12-01 2019-12-31 2700000 net
if (PHP_SAPI === 'cli' && pathinfo(__FILE__, PATHINFO_FILENAME) === pathinfo($argv[0], PATHINFO_FILENAME)) doCLIDirect();
