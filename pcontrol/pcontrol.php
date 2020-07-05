<?php

require_once(__DIR__ . '/../utils/dao.php');
require_once(__DIR__ . '/../utils/testMode.php');
require_once(__DIR__ . '/../get/get.php');

class aws_cpu_pcontrol {

public function __construct($dao = false, $tm = false) { 
    if (!$dao) $dao = new aws_metrics_dao();
    $this->testMode = isTest('exe');
    
    $this->dao = $dao; 
}

public function getSyncOrAsyncInfo($dao) {
    
    $glres = false;
    if (self::isJSReq()) $glres = $this->getLatest();
    if ($glres) return $glres;

    $sres = false;
    
    if ($sres) return $sres;
    return false;    
}

private static function isJSReq() {
    if (isset($_REQUEST['getLatestOutput'])) return true;
    return false;
}

private function getLatest() {
    $ts = time();
    aws_cpu::awsMRegGet();    
    return ['ts' => $ts];
}

private function launchGet() {
    $coo = $this->dao->countPC($this->timeout);
    if ($coo !== 0) return false;
    
    try {
	$seq = $this->dao->insertPC();
    } catch (Exception $ex) { return false; }
    
    self::callAsync($seq);
    
    return ['seq' => $seq];
}
}
