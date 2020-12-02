<?php

require_once(__DIR__ . '/../get/lav.php');
require_once(__DIR__ . '/../output/outils.php');
require_once(__DIR__ . '/../get/get.php');

class dao_lav_display extends dao_lav {
    
    const sinceDays = 10;
    const sinceS    = self::sinceDays * 86400;
    const maxIntervalDays = 0.022222; /* 0.02222 = 32 minutes */
    
    public function __construct() {
	$tsa = ['m' => 'metrics'];
	parent::__construct($tsa);
	$this->p10();
    }
    
    private function p10() {
	static $now = false;
	if (!$now) $now = time();
	$gte = ['$gte' => $now - self::sinceS];
	$q = ['cpu' => ['$exists' => true], 'end_exec_ts' => $gte, 'interval_days' => ['$lte' => self::maxIntervalDays], 'lav' => ['$exists' => true]];
	$o['sort'] = ['end_exec_ts' => -1, 'interval_days' => 1];
	$o['projection'] = ['cpu' => 1, 'lav' => 1, 'end_exec_ts' => 1, '_id' => 0, 'begin_iso' => 1, 'end_iso' => 1, 'interval_days' => 1, 'iid' => 1];
	$m = $this->mcoll->find($q, $o); unset($q, $o);
	$l = $this->lcoll->find(['seqmeta.ts' => $gte], ['sort' => ['seqmeta.ts' => -1]]);
	$this->p20($m, $l);
	return;
    }
    
    private function p20($m, $l) {
	for($i=0; $i < count($l); $i++) $l[$i]['end_exec_ts'] = $l[$i]['seqmeta']['ts'];
	$all = array_merge($m, $l);
	usort($all, [$this, 'sort']);
	$this->all = $all;
	return;
    }
    
    public function getOutFr() {
	static $i=0;
	static $maxcpu = false;
	
	if (!isset($this->all[$i])) return false;
	$r = $this->all[$i];
	extract($r); unset($r);
	if (!isset($cpu)) $cpu = '';
	$lavd = awsmoc::getLavNonRedundant($lav); $lav = $lavd; unset($lavd);
	$df = 'h:ia m/d';
	$end = date($df, $end_exec_ts); unset($df);
	$i++;
			
	if (!$maxcpu && isset($iid)) { $maxcpu = aws_cpu::getMaxCPUCreditFromInstanceID($iid); unset($iid); }
	if ($maxcpu && $cpu) $cpu = awsmoc::cpuos($cpu, $maxcpu);
	if ($maxcpu && is_numeric($lav) && $cpu && is_numeric($cpu) && abs($cpu - $maxcpu) < 0.002) return true;
	if (!$cpu && is_numeric($lav) && abs($lav) < 0.002) return true;
		
	return get_defined_vars();
	
    }
    
    private function sort($a, $b) {
	return $b['end_exec_ts'] - $a['end_exec_ts'];
    }
}

if (didCLICallMe(__FILE__)) new dao_lav_display();
