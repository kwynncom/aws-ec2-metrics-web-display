<?php

function filterNetLogic($a, $i) {
    static $netd = 4;
    return $a[$i]['gpm'] < $netd;
}

function filterOutput($a) {

    $i = 0;
    
    if (count($a) < 2) return $a; // nothing to filter if only a few rows
    
    // $a = array_reverse($a); // reset to oldest first for filtering
    
    $r = []; // rows I will build
    
    $bi = $ei = false; // begin and end indexes of rows that do not vary much
    
    $timel = time() - 12000;
    
    while (isset($a[$i + 1])) {
	
	$cl = $a[$i]['cpu'] > aws_cpu::getMaxCPUCreditFromInstanceID($a[$i]['iid']) - 0.02;
	$nl = filterNetLogic($a, $i);
	
	$tl = $a[$i]['end_exec_ts'] < $timel;
	// $tl = 0;
		
	if ((($cl && $nl) || $tl) && $i > 0) { unset($cl, $nl);
	    if ($bi === false) $bi = $i;
	    $ei = $i + 1;
	    $same = true;
	}
	else $same = false;
		
	if ((!$same || (($i + 2) === count($a))) && $bi !== false) { // create a combined row
	    
	    $t['end_exec_ts'] = $a[$ei]['end_exec_ts'];
	    $t['begin_ts']    = $a[$bi]['begin_ts'];
	    
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
	    
	    $t['netavg'] = getCAWSAvg($nsum, $ssum); unset($nsum, $ssum); // gets the calculation and format we want - Combined AWS Average

	    $r[] = $t; unset($t); // add temp array to return array
	} else if (!$same) {
	    $r[] = $a[$i]; // don't combine the row, just add it to the return array
	    if ($i + 2 === count($a)) $r[] = $a[$i+1]; // if we're at the end and don't combine, add the latest row
	}
	
	$i++;
    }
    
    return $r;
    // return array_reverse($r); // back to newest to oldest
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

