<?php

function parseAWSMetric($a, $metric, $stat, $type) { // $a array; $type is cpu or net

    // check aws cli result for expected values
    kwas(isset($a) && $a && isset($a['Label']) && // Kwynn's assert kwas() - either true or exception
				  $a['Label'] === $metric, 'parse fail 1 - CPUBal');

    kwas(isset($a['Datapoints']) && is_array($a), 'parse fail 2 - CPUBal');
    
    $a = $a['Datapoints'];

    $count = count($a); kwas($count, 'parse fail count CPUBal');
    
    $sl = $stat; // statistic label, such as Minimum or Sum
    
    $a2 = []; // this will be my filtered version
    
    foreach($a as $r) {
	kwas(	    isset($r[$sl]) // another sanity check to make sure the data is what we expect
		&&  isset($r['Timestamp'])
		&&  isset($r['Unit']), 'parse fail dp 1 CPUBal');	unset($r['Unit']);
		
	$iso = $r['Timestamp']; unset($r['Timestamp']); // ISO 8601 date, which is close enough to what AWS outputs
	$r['begin_iso'] = $iso; // row array - I'm creating the row.
	
	$maxpts = time() + 60; // not too far in the future
	$ts = strtotime($iso); kwas($ts && is_numeric($ts) && $ts > aws_cpu::minpts && $ts < $maxpts, 'cpu bal time error'); unset($iso, $maxpts);
	$r['bts' ] = $ts; unset($ts);
	$s  = $r[$sl]; unset($r[$sl]); // string
	$f  = floatval($s); unset($s); // float
	$i  = intval(round($f));  // integer
	$d  = abs($i - $f);
	if ($d < 0.0000002) $v = $i; // close enough to integer
	else		    $v = $f; unset($f, $i, $d);
	$r[$type] = $v; unset($v);
	$a2[] = $r; unset($r);
    } unset($sl);
    
    kwas($count === count($a2), 'array mismatch count CPUBal');
    
    $a = $a2; unset($a2);
    
    if ($type === 'cpu') $theVal = PHP_INT_MAX; // set before loop
    else		 $theVal = 0;
    
    foreach($a as $r) {
	$v = $r[$type];
	if      ($type === 'cpu' && $v < $theVal) $theVal = $v; // we want min CPU
	else if ($type === 'net') $theVal += $v;
    } unset($a, $r);
    
    $ret = [];
    $ret[$type . '_periods'] = $count; // how many JSON rows we just pulled from
    $ret[$type] = $theVal;
    
    return $ret;
}