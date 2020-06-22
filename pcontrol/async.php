<?php

require_once('/opt/kwynn/kwcod.php');
require_once(__DIR__ . '/../utils/utils.php');
require_once(__DIR__ . '/../utils/dao.php');
require_once('pcontrol.php');

function doAsync($testSeq = false) {

    $seq = getAsyncSeq($testSeq);
    $pco = new aws_cpu_pcontrol();
    $pco->doGet($seq);
}

function getAsyncSeq($testSeq) {
    global $argc;
    global $argv;
    
    $seq = false;
    if ($argc >= 2 && is_numeric($argv[1])) $seq = $argv[1];
    else if (is_numeric($testSeq)) $seq = $testSeq;
    
    kwas($seq, 'no valid sequence getAsyncSeq');
    
    return intval($seq);
}

if (!isset($testAsyncSeq))
           $testAsyncSeq = false;

doAsync($testAsyncSeq);

unset($testAsyncSeq);