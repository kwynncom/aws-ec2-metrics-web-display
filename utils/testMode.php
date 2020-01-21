<?php

require_once('utils/machineInfo.php');

function isTest($type) {
    
    if (isAWS()) return false;
    
    $tsac = $tsexe = '2020-01-20 21:30';
            // $tsexe = '2020-01-20 21:30';
    
    if ($type === 'alwaysCheck') 
	return time() < strtotime($tsac) && !isAWS() && 1;

    if ($type === 'exe') 
	return time() < strtotime($tsexe) && !isAWS() && 1;
    
    if ($type === 'f2')
	return time() < strtotime('2019-11-06 23:59')    && getHostInfo() === 'Kwynn-local';
    
    return false;
	
}