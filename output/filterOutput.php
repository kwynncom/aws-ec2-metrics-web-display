<?php

function filterNetLogic($a, $i) {
    static $netd = 20;
    return $a[$i]['gpm'] < $netd;
}

function combineRows($a, &$bi, &$ei, $i) {
    
    if ($bi === $ei) {
	$bi = $ei = false;
	return $a[$i];
	
    }
    
    $t['end_exec_ts'] = $a[$bi]['end_exec_ts'];
    $t['begin_ts']    = $a[$ei]['begin_ts'];

    $nsum = 0; // network sum
    $ssum = 0; // time sum for average
    $mincpu = ' ';
    $t['cpu'] = $mincpu;

    for($j=$bi; $j <= $ei; $j++) {
	if (isset($a[$j]['net']))
	$nsum +=  $a[$j]['net'];
	$ssum +=  $a[$j]['end_exec_ts'] - $a[$j]['begin_ts'];
	procAWSCPUmin($a[$j], $mincpu, $t);
    }

    $bi = $ei = false; // end this combined row set

    $t['gpm'] = getCAWSAvg($nsum, $ssum); unset($nsum, $ssum); // gets the calculation and format we want - Combined AWS Average
    return $t;

}

function filterOutput($a) {
    $a = filterOutput20($a);
    $a = fo30($a);
    return $a;
}

// clt($lowest, $r1['cpu'])		||
	//	clt($r1['cpu'], $r0['cpu'])

function co30l($cpu) {
    
    static $lowest = MAX_AWS_CPU;
    static $state  = 'start';
    
    $l1 = clt($cpu, $lowest);
    if ($l1 && $cpu < $lowest) {
	$state = 1;
	$lowest = $cpu;
    }
    
    return  $l1 || ($state === 1  && !clt($cpu, MAX_AWS_CPU))    ;
}

function fo30($a) {
    
    $bi = $ei = false;
    
    $r = [];
    if (!isset($a[0])) return $r;
    
    if (!defined('MAX_AWS_CPU')) define('MAX_AWS_CPU', aws_cpu::getMaxCPUCreditFromInstanceID($a[0]['iid']));
    
    $lowest = MAX_AWS_CPU;
    
    $r[] = $a[0];
    for ($i=1; isset($a[$i + 1]); $i++) {   
	$r0 = $a[$i];
	
	if (co30l($r0['cpu'])) {
	    if ($bi === false) $bi = $i;
	    $ei = $i;
	} 
	else {
	    $ei = $i;
	    if ($bi === false) $bi = $i;
	    $crr = combineRows($a, $bi, $ei, $i);
	    $r[] = $crr;	    
	}
    }
 
    if ($bi !== false) $r[] = combineRows($a, $bi, $ei, $i);
    
    return $r;    
}

function clt($a, $b) {
    
    if (abs(MAX_AWS_CPU - $a) < 0.01) return false;
    
    if ($a < $b) return true;
    if (abs($a - $b) < 0.003) return true;
    return false;
}

function filterOutput20($a) {
    
    if (!$a) $a = [];
    if (count($a) < 2) return $a; // nothing to filter if only a few rows
    
    $bi = $ei = false; // begin and end indexes of rows that do not vary much
    
    $timel = time() - 12000;
    
    $mp = date('m'); // month pointer
    
    for ($i=0; isset($a[$i]); $i++) {
	$row = $a[$i];
	
	$cl = $row['cpu'] > aws_cpu::getMaxCPUCreditFromInstanceID($a[$i]['iid']) - 0.02;
	$nl = filterNetLogic($a, $i);
	
	// $tl = $a[$i]['end_exec_ts'] < $timel;
	$tl = 0;
		
	$rm = date('m', $a[$i]['end_exec_ts']); // row month
	
	if ((($cl && $nl) || $tl) && $i > 0  && $rm === $mp) { unset($cl, $nl);
	    if ($bi === false) $bi = $i;
	    $ei = $i;
	}
	else {
	    $ei = $i;
	    if ($bi === false) $bi = $i;
	    $crr = combineRows($a, $bi, $ei, $i);
	    $r[] = $crr;
	}
	
	$mp = $rm;
    }
    
    return $r;
}

function procAWSCPUmin($a, &$b, &$t) {
    if (!is_numeric($b) && !isset($a['cpu'])) return setAWSCPUmin($a, $b, $t);
    if ( is_numeric($b) && !isset($a['cpu'])) return false;
    if (!is_numeric($b) &&  isset($a['cpu'])) return setAWSCPUmin($a, $b, $t);
    
    if ($a['cpu'] < $b) return setAWSCPUmin($a, $b, $t);
}

function setAWSCPUmin($a, &$b, &$t) {
    $setto = ' ';
    if (isset($a['cpu'])) 
    $setto = $a['cpu'];
    $b = $t['cpu'] = $setto;
    return true;
}

