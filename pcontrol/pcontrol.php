<?php

require_once('fifo.php');
require_once(__DIR__ . '/../utils/dao.php');
require_once(__DIR__ . '/../utils/testMode.php');

class aws_cpu_pcontrol {

const timeout = 30;
const cleanup = 300;
const usleep  = 100 * 1000;

public function __construct($dao = false, $tm = false) { 
    
    if (!$dao) $dao = new aws_metrics_dao();
    $this->testMode = false;
    if ($tm) $this->testMode = true;
    
    if (isTest('exe')) $this->timeout = 5;
    else	       $this->timeout = self::timeout;
    $this->dao = $dao; 
}

public function getSeq($dao, $seqFifo = false) {
    
    aws_cpu::awsMRegGet($dao);
    
    // if ($seqFifo) { $this->doGet($seqFifo); return; }
    
    $glres = false;
    if (self::isJSReq()) $glres = $this->getLatest();
    
    return;
    
    if ($glres) return $glres;
    $sres = false;
    if ((PHP_SAPI !== 'cli' && !self::isJSReq()) || $this->testMode) $sres = $this->launchGet(); // Kwynn 2020/06/20
    if ($sres) return $sres;
    $this->doGet();
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
    
    // if (1) aws_pc_fifo::write('exedoitInternalPCntrl', $seq);
    // else   exec('php -q ' . __DIR__ . '/../index.php ' . $seq .  ' > /dev/null & ');
    return ['seq' => $seq];
}

public static function getSeqArg() {
    global $argv;
  
    if (PHP_SAPI !== 'cli')  return false;
    if (   !isset     ($argv[1]) 
	|| !is_numeric($argv[1])) return false;
    $seq = intval     ($argv[1]);
    
    return $seq;
    
}

private function doGet($seqFifo = false) {
    
    if ($seqFifo && is_numeric($seqFifo)) $seq = intval($seqFifo);
    else    $seq = self::getSeqArg();
    
    kwas($seq, 'no seq to doGet()');
    
    $dao = $this->dao;
    $dao->pidPC($seq, getmypid());
    $dao->clearPC(self::cleanup);
    aws_cpu::awsMRegGet($dao);
    $dao->donePC($seq);
    exit(0);
}

private function r($sin) {
    file_put_contents('/tmp/r', date('g:i:s') . ' ' . $sin . "\n", FILE_APPEND);
}

}
