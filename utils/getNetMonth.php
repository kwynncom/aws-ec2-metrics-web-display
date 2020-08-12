<?php

require_once('/opt/kwynn/kwcod.php');
require_once(__DIR__ . '/../get/get.php');

getAWSNetMonth();

function getAWSNetMonth() {
    $bmts = strtotime('first day of this month 00:00 GMT');
    $now = time();
    $ds = $now - $bmts;
    $dd = $ds / 86400;
    $na = aws_cpu::awsMRegGet($dd, ['net'], 1, 0);
    return;
    
}
