<?php

class awsmoc {
    public static function getLavTD($r) {
	$ht  = '';
	$ht .= '<td class="lav">';
	$ht .= self::getLavNonRedundant($r);
	$ht .= '</td>';
	return $ht;
    }
    
    public static function getLavNonRedundant($ain) {
	
	// The JavaScript re-call or something I lost track of injects some weird data here, so make sure it's what I'm expecting:
	if (isset($ain['lav']))  $lav = $ain['lav'];
	else if (isset($ain[2]) && is_array($ain)) $lav = $ain; // it's not obvious why I need $ain[2], but I do.
	else return '';
	
	$ret = [];
	$t  = 0;
	$te = 0;
	$p = -1;
	foreach($lav as $r) {
	    
	    if (0) $r = '0.00'; // for testing
	    
	    if (!is_numeric($r)) return ''; // similar to above -- not quite sure why, but whatever.  Not sure as in I lost track.
	    
	    if (abs($r - $p) < 0.003) $te++;
	    $t++;
	    $p = $r;
	    $rf = sprintf('%0.2f', $r);
	    $ret[] = $rf;
	}
	
	if ($t === $te + 1) return $rf;
	
	return implode(' ', $ret);
    }
    
    public static function loadbtn() {
	return "<span class='cbp10'><input type='checkbox' class='cb10' onclick='cbClick(this.checked);' /></span>"
	. "<label class='lavl'>load avg.</label>";
    }
    
    public static function cpuos($c, $maxin = false) {
	
	static $key = 'MAX_AWS_CPU';
	
	if ($maxin && !defined($key)) define($key, $maxin);
	
	if (abs($c - MAX_AWS_CPU) < 0.005) return intval($c);
	else return sprintf('%3.2f', $c);
    }
}