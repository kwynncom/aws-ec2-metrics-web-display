<?php

class aws_cpu_pcontrol {

const timeout = 30;
const cleanup = 300;
const usleep  = 100 * 1000;

public function __construct($dao) { 
    if (isTest('exe')) $this->timeout = 2;
    else	       $this->timeout = self::timeout;
    $this->dao = $dao; 
}

public function getSeq() {
    $glres = $this->getLatest();
    if ($glres) return $glres;
    $sres = $this->launchGet();
    if ($sres) return $sres;
    $this->doGet();
    return false;    
}

private function getLatest() {

    if (   !isset(     $_REQUEST['getLatestOutput']) 
	|| !isset(     $_REQUEST['seq'])
	|| !is_numeric($_REQUEST['seq'])
    ) return false;
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
    if ($coo === 0) {
	$seq = $this->dao->insertPC();
	exec('php -q ' . __DIR__ . '/../index.php ' . $seq .  ' > /dev/null & ');
	return ['seq' => $seq];
    }

    return false;
}

private function doGet() {
    
    global $argv;
  
    if (PHP_SAPI !== 'cli')  return false;
    if (   !isset     ($argv[1]) 
	|| !is_numeric($argv[1])) return false;
    $seq = intval     ($argv[1]);

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
