<?php

require_once('/opt/kwynn/kwutils.php');

if (didCLICallMe(__FILE__)) journalSizePer();

function journalSizePer() {
    
    try {
	$dir = '/var/log/journal';
	$cmd = 'du --summarize --block-size=1 ' . $dir;
	$shraw = trim(shell_exec($cmd));
	preg_match('/(\d+)/', $shraw, $ms);
	kwas(isset($ms[1]));
	$jrn = intval($ms[1]); 	unset($ms);
	$tot = disk_total_space($dir);
	$rat = $jrn / $tot;
	$perfl = $rat * 100;
	// $pers  = sprintf('%0.2f', $perfl);
	// $vars = get_defined_vars();
	return $perfl;
	
    } catch (Exception $ex) { }
    
    return '';
    
    
}