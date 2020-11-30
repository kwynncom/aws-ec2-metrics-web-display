<?php

class awsmoc {
    public static function getLavTD($r) {
	$ht  = '';
	$ht .= '<td class="lav">';
	$ht .= self::lav10($r);
	$ht .= '</td>';
	return $ht;
    }
    
    private static function lav10($ain) {
	if (!isset($ain['lav'])) return '';
	$ret = [];
	$t  = 0;
	$te = 0;
	$p = -1;
	foreach($ain['lav'] as $r) {
	    
	    if (0) $r = '0.00'; // for testing
	    
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
}