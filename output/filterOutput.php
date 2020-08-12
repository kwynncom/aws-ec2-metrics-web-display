<?php

function filterNetLogic($a, $i) {

    static $netd = 3.98;
    
    if (!isset($a[$i  ]['gpm'])) $a[$i  ]['gpm'] = 0;
    if (!isset($a[$i+1]['gpm'])) $a[$i+1]['gpm'] = 0;    
    $and = abs($a[$i  ]['gpm'] - $a[$i+1]['gpm']);
    return $and < $netd;
}

function isCompNull($a, $i) {
    if (	!isset($a[$i  ]['cpu']) 
	    &&  !isset($a[$i+1]['net'])) return true;
    if (	!isset($a[$i  ]['net']) 
	    &&  !isset($a[$i+1]['cpu'])) return true;
    
    return false;
}

function filterOutput($a) {

    if (count($a) < 2) return $a; // nothing to filter if only 1 row
    
    foreach($a as $i => $row) if ($a[$i]['status'] !== 'OK') unset($a[$i]); // remember that I store pre-fetch.  If the data isn't complete, skip it
    $a = array_values($a);  // reset indexes after possibly removing elements just above
    $a = array_reverse($a); // reset to oldest first for filtering
    
    $r = []; // rows I will build
    
    $i = 0;
    $bi = $ei = false; // begin and end indexes of rows that do not vary much
    
    $timel = time() - (86400 * 10);
    
    while (isset($a[$i + 1])) {
	
	$cl = isCPUSame($a, $i);
	$nl = filterNetLogic($a, $i);
	
	$tl = $a[$i]['end_exec_ts'] < $timel;
		
	if (($cl && $nl) || $tl) { unset($cl, $nl);
	    if ($bi === false) $bi = $i;
	    $ei = $i + 1;
	    $same = true;
	}
	else $same = false;
		
	if ((!$same || (($i + 2) === count($a))) && $bi !== false) { // create a combined row
	    
	    $t['end_exec_ts'] = $a[$ei]['end_exec_ts'];
	    $t['begin_ts'] = $a[$bi]['begin_ts'];
	    $t['status']      = 'OK'; // need to repeat this because this will become the new returned array
    	    $nsum = 0; // network sum
	    $ssum = 0; // time sum for average
	    $mincpu = ' ';
	    $t['cpu'] = $mincpu;
	    
	    for($j=$bi; $j <= $ei; $j++) {
		if (isset($a[$j]['net']))
		$nsum +=  $a[$j]['net'];
		$ssum +=  $a[$j]['end_exec_ts'] - $a[$j]['begin_ts'];
		
	//	if (isset($a[$j]['cpu']))
	//	     if ($mincpu >             $a[$j]['cpu'] ) 
	
		procAWSCPUmin($a[$j], $mincpu, $t);
			// {$mincpu = $t['cpu'] = $a[$j]['cpu'];  }
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
    
    return array_reverse($r); // back to newest to oldest
}

function procAWSCPUmin($a, &$b, &$t) {
    if (!is_numeric($b) && !isset($a['cpu'])) return setAWSCPUmin($a, $b, $t);
    if ( is_numeric($b) && !isset($a['cpu'])) return false;
    if (!is_numeric($b) &&  isset($a['cpu'])) return setAWSCPUmin($a, $b, $t);
    
    if ($a['cpu'] < $b) return setAWSCPUmin($a, $b, $t);
}

function isCPUSame($a, $i) {

	if (	    !isset($a[$i  ]['cpu']) 
	    ||	    !isset($a[$i+1]['cpu'])) return true;	
	return	   abs(    $a[$i  ]['cpu']
		       -   $a[$i+1]['cpu']) < 0.002;
}

function setAWSCPUmin($a, &$b, &$t) {
    $setto = ' ';
    if (isset($a['cpu'])) 
    $setto = $a['cpu'];
    $b = $t['cpu'] = $setto;
    return true;
}

