<?php

require_once('/opt/kwynn/kwutils.php');
require_once(__DIR__ . '/../utils/utils.php');
require_once(__DIR__ . '/../utils/getawsacctid.php');
require_once(__DIR__ . '/../utils/dao.php');
require_once('parse.php');
require_once(__DIR__ . '/../utils/machineInfo.php');
require_once(__DIR__ . '/../utils/testMode.php');
require_once(__DIR__ . '/../pcontrol/pcontrol.php');
require_once(__DIR__ . '/../utils/getInstanceType.php');

class aws_cpu {
    
    // to better understand the constants - see commentary on the aws cli commands themselves
    const sind     = 86400; // seconds in a day
    const minInterval = 1200;  // always run at least these seconds of data
    const minDays  = self::minInterval / self::sind; // same as above in days
    const maxIDays = 12; // If running for more than n days of data, break it into 12 day periods
    const maxPer   = self::maxIDays * self::sind; // same in seconds
    const minPer   = 300; // minimum period as in if you run 1200 minutes end - start interval you'll run 4 periods === 1200 / 300
    const defaultDays = 30; // if never run before, run n days
    const rerunMaxMinus = 0.02;
    
    const minpts =  1530413163; // a check on time calculations - min possible timestamp - June 30, 2018 10:46:03 PM GMT-04:00

// Kwynn 2020/07/05 experimenting with making this method public - may or may not
private static function doCmds1($daysin = 0, $dao = false, $recursiveCall = false, $cmds = '') { // called from below; $days of data to get
    
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
    
    self::popII($rarr['iid'], $dao, $rarr['reg']);
        
    $rarr['status'] = 'OK'; // we haven't thrown an exception or we wouldn't be here
    $gpm            = self::gpm($rarr);
    if ($gpm !== false) $rarr['gpm'] = $gpm; unset($gpm); 
    
    if (isset($rarr['cpu'])) $rarr['lav'] = sys_getloadavg();
    
    $dao->put($rarr);

     if (!isset($rarr['cpu'])) return $rarr;
    
    $doRerunInit = self::reRunPerCPU($rarr['iid'], $rarr['cpu'], $dao, $rarr['reg']);
    
    if ($recursiveCall)  return $rarr;
    if (!$daysin)	 return $rarr;
    if (!isset($didLonger) || !$didLonger) return $rarr;

    if ($cmds !== false && !isset($cmds['cpu'])) return $rarr;
    
    
    if (!$doRerunInit)  return $rarr;
    
    /* If this ran a long period of time and the CPU was a bit low, I want to know the current value, so I re-run this function for the shortest 
     * amount of time.  This risks infinite recursion, but as of 2020/06/27, I think have I solved that.  */
    
    self::doCmds1(0, $dao, true, $cmds);
    
    return $rarr;
  
}

public static function getMaxCPUCreditFromInstanceType($tin) {
    switch($tin) { case 't3a.nano' : return KWYNN_AWS_EC2_t3a_nano_MAX_CPU;    }
    kwas(0, "cannot find instance type: $tin");
}

public static function getMaxCPUCreditFromInstanceID($iin) {
    
    static $cache = [];
    
    if (isset($cache[$iin])) return $cache[$iin];
    
    $dao = new aws_metrics_dao();    
    $dbr = $dao->getI($iin, 'max_possible_cpu');
    kwas($dbr && is_numeric($dbr) && $dbr > 0.9, 'invalid max cpu');
    $cache[$iin] = $dbr;
    return $dbr;
}

private static function popII($iid, $dao, $reg) {
    $dbr = $dao->getI($iid, 'type');
    
    if ($dbr)	return;
    
    $itype  = awsInstanceType::get($iid, $reg);
    $dao->putI($iid, 'type', $itype);
    
    $maxcpu = self::getMaxCPUCreditFromInstanceType($itype);
    $dao->putI($iid, 'max_possible_cpu', $maxcpu);
		
}


private static function reRunPerCPU($iid, $cpu, $dao, $reg) {
    
    $dbr = $dao->getI($iid, 'type'); kwas($dbr, 'no db result reRunPerCPU()');
    $itype = $dbr; 
    unset($iid, $dbr);
    
    $maxcpu = self::getMaxCPUCreditFromInstanceType($itype); unset($itype);
    $d =  $maxcpu - self::rerunMaxMinus;
    if ($cpu <= $d) return true;
    return false;
    
}

private static function gpm($d) {
    if (!isset($d['net'])) return false;
    $t = $d['end_exec_ts'] - $d['begin_ts'];
    $n = $d['net'];
    $bps = $n / $t;
    $gpm = $bps * aws_metrics_dao::aggF;
    return $gpm;
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

public static function awsMRegGet($daysin = false, $cmdsin = false, $overrideQ = false, $declutter = false) {
    
    $dao = new aws_metrics_dao();
   
    if ($daysin === false) {
	$prev = $dao->getLatest();
	if (!$prev) $days = self::defaultDays;
	else $days = (time() - $prev['end_exec_ts']) / self::sind;
    } else $days = $daysin;
    
    $doCheck = ($days > self::minPer / self::sind) || isTest('alwaysCheck') 
	    || (iscli() && $overrideQ);
    
    if (!$doCheck) return false;
    
    $res = self::doCmds1($days, $dao, false, $cmdsin);
    if ($declutter) return self::declutter($res);
    else            return                 $res;
}

public static function declutter($din) {
    static $fs = false;
    if (!isset($din['status']) || $din['status'] !== 'OK') return false;
    
    $dout = $din; unset($din);
    
    if (isset($dout['cpu'])) $dout['max_possible_cpu'] = self::getMaxCPUCreditFromInstanceID($dout['iid']);
    
    if (!$fs) $fs = [ 'begin_iso',  'begin_ts', 'end_exec_ts', 'end_iso', 'gpm', 'cpu', 'max_possible_cpu'];
    
    foreach($dout as $k => $ignore) if (!in_array($k, $fs)) unset($dout[$k]);
    
    return $dout;
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
