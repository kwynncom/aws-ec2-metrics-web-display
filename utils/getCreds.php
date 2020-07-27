<?php

require_once('/opt/kwynn/creds.php');

function getAWSCreds() {
    $co = new kwynn_creds();
    $c  = $co->getType('aws_cpu_creds_2020_1_series');
    return $c;
    
}