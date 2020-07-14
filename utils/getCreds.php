<?php

require_once('/opt/kwynn/creds.php');

function getAWSCreds() {
    $co = new kwynn_creds();
    $c  = $co->getType('aws_cpu_creds_1_2019_10');
    return $c;
    
}