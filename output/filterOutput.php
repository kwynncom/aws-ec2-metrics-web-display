<?php

function filterNetLogic($d) {
    if (!isset($d['net'])) return false;
    $t = $d['end_exec_ts'] - $d['begin_ts'];
    $n = $d['net'];
    $bps = $n / $t;
    $gpm = $bps * aws_metrics_dao::aggF;
    if ($gpm > 0.999) return true;
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
    
    while (isset($a[$i + 1])) {
	
	$cl = true;
	
	if (    isset($a[$i  ]['cpu']) 
	    &&  isset($a[$i+1]['cpu']))
	    $cl = abs(    $a[$i  ]['cpu']
		      -   $a[$i+1]['cpu']) < 0.002;
	
	$nl = filterNetLogic($a[$i]);
	
	if ($cl || $nl) { unset($cl, $nl);
	    if ($bi === false) $bi = $i;
	    $ei = $i + 1;
	    $same = true;
	}
	else $same = false;
		
	if ((!$same || (($i + 2) === count($a))) && $bi !== false) { // create a combined row
	    if (isset($a[$bi]['cpu']))
	    $t['cpu']	      = $a[$bi]['cpu']; // I'm interested in the later cpu value; create a temp array
	    $t['end_exec_ts'] = $a[$ei]['end_exec_ts'];
	    $t['begin_ts'] = $a[$bi]['begin_ts'];
	    $t['status']      = 'OK'; // need to repeat this because this will become the new returned array
    	    $nsum = 0; // network sum
	    $ssum = 0; // time sum for average
	    for($j=$bi; $j <= $ei; $j++) {
		if (isset($a[$j]['net']))
		$nsum +=  $a[$j]['net'];
		$ssum +=  $a[$j]['end_exec_ts'] - $a[$j]['begin_ts'];
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