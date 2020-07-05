<?php

require_once('/opt/kwynn/kwutils.php');
require_once(__DIR__ . '/../utils/utils.php');
require_once(__DIR__ . '/../utils/getawsacctid.php');
require_once(__DIR__ . '/../utils/dao.php');
require_once('parse.php');
require_once(__DIR__ . '/../utils/machineInfo.php');
require_once(__DIR__ . '/../utils/testMode.php');
require_once(__DIR__ . '/../pcontrol/pcontrol.php');

class aws_cpu {
    
    // to better understand the constants - see commentary on the aws cli commands themselves
    const sind     = 86400; // seconds in a day
    const minInterval = 1200;  // always run at least these seconds of data
    const minDays  = self::minInterval / self::sind; // same as above in days
    const maxIDays = 12; // If running for more than n days of data, break it into 12 day periods
    const maxPer   = self::maxIDays * self::sind; // same in seconds
    const minPer   = 300; // minimum period as in if you run 1200 minutes end - start interval you'll run 4 periods === 1200 / 300
    const defaultDays = 30; // if never run before, run n days
    const rerunAtCPU = 143.98; // Depenent upon certain types of instance.  Fix this eventually.  Magic number 72 71 143 144
    
    const minpts =  1530413163; // a check on time calculations - min possible timestamp - June 30, 2018 10:46:03 PM GMT-04:00

// Kwynn 2020/07/05 experimenting with making this method public - may or may not
private static function doCmds1($daysin = 0, $dao = false, $recursiveCall = false, $cmds) { // called from below; $days of data to get
    
    static $ts = false;
    
    if (!$dao) $dao = new aws_metrics_dao();
   
    if (!$ts) $ts = time();
    
    if ($daysin <= self::minDays) $days = self::minDays;
    else			 $days = $daysin;
    
    $secs = $days * self::sind;
    
    // near the bottom of the function I explain min versus "longer" a bit better
    $dmdd = abs(self::minDays - $days); // my potential infinite recursion problem was relying on floating point comparison, so $dmdd gives me a 
					// reliable floating point comparison
    if (    $recursiveCall || $daysin <= 0 || ($days <= self::minDays) || ($daysin <= self::minDays) || ($dmdd < 0.00001)
	    ) { // run for the minimum interval
	$sub   = self::minInterval;
	$per   = self::minPer;
    } else {
	$didLonger = true; // did a longer interval
	$sub = $secs;
	if ($days > self::maxIDays) $per = intval(round(self::maxIDays * self::sind * (2/3)));
	else			    $per = intval(round($secs * (2/3)));
    } unset($secs, $dmdd);
    
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
    // $rarr['pctrl_seq'] = aws_cpu_pcontrol::getSeqArg();
    $rarr['pid'] = getmypid();
    $rarr['status'] = 'pre-Fetch';
    $dao->put($rarr);

    self::doCmds2($rarr, $dao, $cmds); // just below
    
    $rarr['status'] = 'OK'; // we haven't thrown an exception or we wouldn't be here
    $dao->put($rarr);
    
    if ($recursiveCall) return;
    if (!$daysin) return;
    if (!isset($didLonger) || !$didLonger) return;
    if ($rarr['cpu'] >= self::rerunAtCPU)  return;
    
    /* If this ran a long period of time and the CPU was a bit low, I want to know the current value, so I re-run this function for the shortest 
     * amount of time.  This risks infinite recursion, but as of 2020/06/27, I think have I solved that.  */
    
    self::doCmds1(0, $dao, true);
  
}

private static function doCmds2(&$rarr, $dao, $cmdsin) { // & means changes are carried back to the calling func
    
    $cmds = self::getValidCmds($cmdsin);
    
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
    
    if ($rarr !== false) {
	$arr  = json_decode($json, 1);  unset($json);
	$parr  = parseAWSMetric($arr, $metric, $stat, $ctype); unset($arr);
	$rarr  = array_merge($rarr, $parr); unset($parr);
	$rarr[$ctype . '_exec_s'] = $cend - $cbeg; unset($cend, $cbeg);
	return $rarr;
    } else return $json;
    
}

public static function awsMRegGet($dao = false, $daysin = false, $cmdsin = false) {
    
    if (!$dao) $dao = new aws_metrics_dao();
   
    if (!$daysin) {
	$prev = $dao->getLatest();
	if (!$prev) $days = self::defaultDays;
	else $days = (time() - $prev['end_exec_ts']) / self::sind;
    } else $days = $daysin;
    if ($days > self::minPer / self::sind || isTest('alwaysCheck')) self::doCmds1($days, $dao, false, $cmdsin);
}

private static function getValidCmds($cin) {
    $d[] = 'cpu';
    $d[] = 'net';

    if (!$cin) return $d;
    if ($cin === 'both' || $cin === 'all') return $d;
    if ($cin === 'cpu') return ['cpu'];
    if ($cin === 'net') return ['net'];
    if (!is_array($cin)) return $d;
    if (count($cin) > 2) return $d;
    
    foreach($cin as $v) if (!in_array($v, $d)) return $d; 
    
    return $cin;
}
}

// allows you to run a simplified get from the command line, if you call this file directly
// usage:    php get.php 2019-12-01 2019-12-31 2700000 net
if (PHP_SAPI === 'cli' && isset($argv[0]) && pathinfo(__FILE__, PATHINFO_FILENAME) === pathinfo($argv[0], PATHINFO_FILENAME)) doCLIDirect();
