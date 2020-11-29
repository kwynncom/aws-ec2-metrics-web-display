<?php

require_once('/opt/kwynn/kwutils.php');
require_once(__DIR__ . '/../utils/dao.php');

class aws_metrics_filtered_out extends aws_metrics_dao {
    public static function get($since) {
	$o = new self($since);
	return $o->geta10();
    }
    
    public function geta10() { return $this->a10; }
    
    private function __construct($since) {
	parent::__construct(self::dbName);
	$this->a10 = false;
	$this->get10($since);
	$this->f10();
    }
    
    private function f10() {
	$ain = $this->thein;
	foreach($ain as $r) $this->build10($r);
	$a10 = $this->a10;
	return;
    }
    
    private function build10($r) {
	static $t = false;
	static $laCPU = false;
	static $laCPUa = false;

	if ($laCPU === false && isset($r['cpu'])) $laCPU = $r['cpu'];	
	if (isset($r['net']) && isset($r['cpu'])) { $this->a10[] = $r; $t = false; return; }
	if (!$t) { $t = $r; return; }
	$t['begin_ts'    ] = min($t['begin_ts'], $r['begin_ts']); 
	$t['end_exec_ts']  = max($t['end_exec_ts'], $r['end_exec_ts']); unset($t['begin_iso'], $t['end_iso']);
	if ( isset($t['cpu']) && isset($r['cpu'])) {
	    if ($laCPUa === false) { $t['cpu'] = $laCPU; $laCPUa = true; }
	    else $t['cpu'] = min($r['cpu'], $t['cpu']);
	}
	if (!isset($t['cpu']) && isset($r['cpu'])) $t['cpu'] = $r['cpu'];
	if (!isset($t['net']) && isset($r['net'])) $t['net'] = $r['net'];
	if ( isset($t['net']) && isset($r['net'])) {
	    $t['net'] += $r['net']; unset($t['gpm']);
	}
	
	if (!  (isset($t['cpu']) && isset($t['net'])) ) return;

	$t['gpm'] = getCAWSAvg($t['net'], $t['end_exec_ts'] - $t['begin_ts']);
	
	$t['iid'] = $r['iid'];
	
	if (isset($r['lav'])) $t['lav'] = $r['lav'];
	
	$this->a10[] = $t;
	$t = false;
	
    }
    
    private function get10($since) {
	$this->thein = $this->getSince($since);
    }
}

if (didCLICallMe(__FILE__)) aws_metrics_filtered_out::get();

