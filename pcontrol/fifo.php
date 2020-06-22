<?php

require_once('/opt/kwynn/kwutils.php');
require_once('pcontrol.php');

class aws_pc_fifo {
    
    const path = '/opt/www/kwynn_awscpu_202006_1';
    
    private $hand = false;
    
    public static function write($dir = '', $dat = '') {
	
	global $argv;
	
	if ($dir === 'exedoitInternalPCntrl') { self::mkif($dir, $dat); return; }
	if (self::doitStatus() === 'iHaveBeenExecd') self::mkif($argv[1]);
	
    }
    
    private static function mkif($dir, $dat = '') {
	
	global $argv;
	global $argc;

	if (!file_exists(self::path)) {
	    $mkfr = posix_mkfifo(self::path, 0660);
	    kwas($mkfr, 'fifo create failed');
	}

	// $eres = self::doitStatus($dir, 'n/a', $dat);
	if ($dir === 'exedoitInternalPCntrl') { 
	    
	    if (!is_numeric($dat)) die('non numeric to exec mkif pcontrol');
	    // exec("echo $dat  > " . self::path); 	    
	    // $sres = exec('php ' . __DIR__  . '/test3.php'. ' w ' . $dat . ' > /dev/null & '); 
	    // $sres = exec('php ' . __DIR__  . '/test3.php'. ' r ' . $dat . ' > /dev/null & '); 

	    file_put_contents(self::path, intval(trim($dat))); return; 
	    // echo $sres;
	    // exec('php -q ' . __FILE__ . ' r ' . $dat . ' > /dev/null & '); 	    
	    // exit(0);
	    return; 
	    
	}

	

	if ($dir === 'w') { 
	    // kwas(is_numeric(trim($argv[2])), 'bad argv 2 w');
	    file_put_contents(self::path, 'blah'); 
	    exit(0); 
	    
	}

	if (PHP_SAPI !== 'cli') return;
	
	// fopen(self::path, 'r');
	$res = trim(file_get_contents(self::path));
	if (is_numeric($res)) {
	    
	    exit(0); // *** TESTING
	    
	    require_once(__DIR__ . '/../' . 'index.php');
	    index_f($res);
	}
	$x = 2;
	
	exit(0);
	
    }
    
    public static function doitStatus($dirin = '', $type = '', $dat = '') {
	global $argc;
	global $argv;
	
	if (PHP_SAPI !== 'cli') return false;
	
	if ($argc < 1) return false;
	$caller = $argv[0];
	
	$thisd = __DIR__;
	$cmptest = $thisd . '/test.php';
	
	/* if ($caller === $cmptest) {
	    if ($type === 'check1') return 'testok';
	    
	    if (!$dat || !is_integer($dat) || $dat < 1) return false;
	    if ($dirin !== 'exedoitInternalPCntrl') return false;

	    if ($caller === $cmptest) return 'execOK20060047';
	    
	}*/
	
	if ($argc < 2) return false;
	
	if ($argv[1] !== 'w'  &&  $argv[1] !== 'r') return false;
	
	// $seq = $argv[2];
	// if ($seq < 1) return false;
	
	// $cmp2 = $thisd . '/test2.php';
	// if ($caller === $cmp2 && $type === 'check1') return 'testok';
	
	if ($caller !== __FILE__) return false;
	
	// if ($caller !== __FILE__ && $caller !== $cmp2 && $caller !== $cmptest) return false;
		
	return 'iHaveBeenExecd';
	
    }
    
    public static function isTest() {
	
	global $argc;
	global $argv;
	
	$dis = self::doitStatus('', '');
	
	if ($dis === 'iHaveBeenExeced') return $dis;
	
	// if ($dis !== 'testok') return false;
	
	// if ($argc > 1) return false;
	if (PHP_SAPI !== 'cli') return false;
	if (isAWS()) return false;
	if ($argc >= 2 && $argv[1] === 'test') return true;
	if (time() > strtotime('2020-06-21 20:00')) return false;
	return true;
    }
    
    public static function test() {
	$itr = self::isTest();
	
	if (!$itr) return;
	
	if ($itr === 'iHaveBeenExeced') { self::write(); return; }
	
	$pco = new aws_cpu_pcontrol(false, true);
	
	$seq = $pco->getSeq();
	$x = 2;
    }
    
}

// if (aws_pc_fifo::doitStatus() === 'iHaveBeenExecd') aws_pc_fifo::write();