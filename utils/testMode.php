<?php

require_once('machineInfo.php');

function isTest($type = false) {
  
    if (isAWS()) return false;
    
    if (time() < strtotime('2020-12-01 21:31' ))  return true;
    
    return 0;
    
    
    if (!isAWS() && time() < strtotime('2020-03-25 22:59' ))  return true;
    
    
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