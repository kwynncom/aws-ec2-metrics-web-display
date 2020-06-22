<?php

require_once(__DIR__ . '/../utils/dao.php');
require_once(__DIR__ . '/../utils/testMode.php');
require_once(__DIR__ . '/../get/get.php');

class aws_cpu_pcontrol {

const timeout = 30;
const cleanup = 300;
const usleep  = 100 * 1000;

public function __construct($dao = false, $tm = false) { 
    
    kwl('constructor');
    
    if (!$dao) $dao = new aws_metrics_dao();
    $this->testMode = isTest('exe');
    
    if (isTest('exe')) $this->timeout = 5;
    else	       $this->timeout = self::timeout;
    $this->dao = $dao; 
    
    kwl('end constructor');
}

public function getSeq($dao, $seqFifo = false) {
    
    $glres = false;
    if (self::isJSReq()) $glres = $this->getLatest();
    if ($glres) return $glres;

    $sres = false;
    if ((PHP_SAPI !== 'cli' && !self::isJSReq()) || $this->testMode) $sres = $this->launchGet();
    if ($sres) return $sres;
    return false;    
}

private static function isJSReq() {
    if (   isset(     $_REQUEST['getLatestOutput']) 
	&& isset(     $_REQUEST['seq'])
	&& is_numeric($_REQUEST['seq'])
    ) return true;

    return false;
}

private function getLatest() {

    $seq = intval(     $_REQUEST['seq']);
    
    $i = 0;

    do {
	$pido = $this->dao->getPC($seq);
	if ($pido) break;
	usleep(self::usleep);
    } while($i++ < 20);
    
    $pid = $pido['pid_status'];
    
    if ($pid === true) return ['ts' => $pido['pc_start_ts']];
    
    if (!$pid || !is_numeric($pid)) {
	
	// This should not happen, but maybe  it does.  We never want to return false from here, so give it up.
	exit(0);  // also no point in exiting as an error until I figure this out better.
    }
    
    exec("tail --pid=$pid -f /dev/null");
    
    return ['ts' => $pido['pc_start_ts']];
}

private function launchGet() {
    $coo = $this->dao->countPC($this->timeout);
    if ($coo !== 0) return false;
    
    $seq = $this->dao->insertPC();
    
    self::callAsync($seq);
    
    return ['seq' => $seq];
}

public static function callAsync($seq) {
    exec('php ' . __DIR__ . '/' . 'async.php ' . $seq .  ' > /dev/null & ', $output, $retvar);   
    return;
}

public static function getSeqArg() {
    global $argv;
  
    if (PHP_SAPI !== 'cli')  return false;
    if (   !isset     ($argv[1]) 
	|| !is_numeric($argv[1])) return false;
    $seq = intval     ($argv[1]);
    
    return $seq;
    
}

public function doGet($seqFifo = false) {
    
    kwl('doGet 1');
    
    if ($seqFifo && is_numeric($seqFifo)) $seq = intval($seqFifo);
    else    $seq = self::getSeqArg();
    
    kwas($seq, 'no seq to doGet()');

    kwl('doGet 2');
    
    $dao = $this->dao;
    $dao->pidPC($seq, getmypid());
    $dao->clearPC(self::cleanup);
    aws_cpu::awsMRegGet($dao);
    $dao->donePC($seq);
}

private function r($sin) {
    file_put_contents('/tmp/r', date('g:i:s') . ' ' . $sin . "\n", FILE_APPEND);
}

}
