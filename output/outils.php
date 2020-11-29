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
	foreach($ain['lav'] as $r)  $ret[] = sprintf('%0.2f', $r);
	return implode(' ', $ret);
    }
    
    public static function loadbtn() {
	return "<span class='cbp10'><input type='checkbox' class='cb10' onclick='cbClick(this.checked);' /></span><label class='lavl'>load</label>";
    }
}