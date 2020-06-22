<?php

require_once('/opt/kwynn/kwutils.php');

if (PHP_SAPI === 'cli' && time() < strtotime('2019-11-28')) {
    getMonthBounds();
}

function getMonthBounds() {

    $mytz = date_default_timezone_get();
            date_default_timezone_set('UTC');
    
    $bcms  = date('Y-m-01');
    $c = strtotime($bcms);
    $n = strtotime('+1 month', $c);
    $p = strtotime('-1 month', $c);
    date_default_timezone_set($mytz);
    
    return ['c' => $c, 'n' => $n, 'p' => $p];
}

function getAWSBEISO(&$begIO, &$endIO) {
    
    static $now   = false;
    static $maxts = false;
    
    if (!$now)   $now   = time();
    if (!$maxts) $maxts = $now + 60;
    
    $ta = ['beg' => $begIO, 'end' => $endIO];
    
    foreach ($ta as $k => $v) {
	if (!is_numeric($v))  $vv = strtotime($v);
	else if (!is_int($v)) $vv = intval($v);
	else $vv = $v;
	
	if ($vv < aws_cpu::minpts || $vv > $maxts) 
	    if ($k === 'beg') $vv = $now - aws_cpu::minInterval;
	    else              $vv = $now;
	
	kwas($vv > aws_cpu::minpts && $vv < $maxts, 'getAWSBEISO range fail');
	$ta[$k] = $vv;
    }
    
    if (time() - $ta['beg'] < aws_cpu::minInterval) $ta['beg'] = aws_cpu::minInterval;
        
    if ($ta['end'] - $ta['beg'] < aws_cpu::minPer) $ta['beg'] = $ta['end'] - aws_cpu::minPer;

    $oldtz = date_default_timezone_get();
    date_default_timezone_set('UTC');
    
    foreach($ta as $k => $v) {
       	$iso = date('c', $v);
	$iso = str_replace('+00:00', 'Z', $iso);
	$ta[$k] = $iso;
    }
   
    date_default_timezone_set($oldtz);

    $begIO = $ta['beg'];
    $endIO = $ta['end'];
}


function getDaysDBVal($din) { // either integer or 0.001 precision for database, because I don't want to see 15.3830583589490
   if (is_int($din)) return $din;
   return round($din, 3);
}

function rmSubRegion($in) { // us-east-1a is an availability zone, we want the "parent" region us-east-1
    kwas($in && is_string($in) && strlen($in) > 3, 'region in string error');
    return substr($in, 0, strlen($in) - 1);
}

function getPeriodDiv60($pin, $min = aws_cpu::minPer) { // per AWS rules, get a period that is a multiple of 60
    if ($pin < $min) return $min; // but not below the minimum period
    $pf = intval(floor($pin / 60)) * 60; // when in doubt use a smaller period for more data, thus floor()
    if ($pf < $min) return $min;
    return $pf;
}

function getCAWSAvg($n, $s) { // GB per month with 0.001 precision
    $days   = $s    / 86400;
    $dayspm = $days / 30.5 ; // average of 30.5 days in a month, or close enough
    $net = number_format(($n / 1000000000) / $dayspm, 3);
    return $net;
}

function getTheCmd($reg, $iid, $begin, $end, $per, $metric, $stat) {
    $cmd  = "/usr/bin/aws cloudwatch get-metric-statistics --metric-name $metric --namespace AWS/EC2 ";
    $cmd .= "--statistics $stat ";
    $cmd .= "--dimensions Name=InstanceId,Value=$iid --region $reg ";
    $cmd .= " --start-time $begin --end-time $end ";
    $cmd .= "--period $per ";
    
    return $cmd;
}

function getDiskUsedPercentage($sfx = '') {
    $f = disk_free_space (__DIR__);
    $t = disk_total_space(__DIR__);
    $pf = $f / $t;
    $uf  = 1 - $pf;
    $upr  = round($uf * 100, 1);
    return number_format($upr, 1) . $sfx;
        
}

function doCLIDirect() {
   
    global $argv;
    
    $l = $argv;
    unset($l[0]);
    $l = array_values($l);
    
   $res = call_user_func_array(['aws_cpu', 'cliGet'], $l);
   echo $res . "\n";    
}

