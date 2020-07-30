<?php

require_once('/opt/kwynn/kwutils.php');

function getUbuupCli() {
    
    $p = __DIR__;
    $p .= '/../../';
    if (isAWS()) $p .= '../../20/05/';
    $p .= 'ubuup/get.php';
    
    require_once($p);
    
    return getUbuup::get($p);

}

if (didCliCallMe(__FILE__)) var_dump(getUbuupCli());
