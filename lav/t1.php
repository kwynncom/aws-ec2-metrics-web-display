<?php

require_once(__DIR__ . '/../get/lav.php');

class dao_lav_display extends dao_lav {
    
    const sinceDays = 10;
    const sinceS    = self::sinceDays * 86400;
    
    public function __construct() {
	$tsa = ['m' => 'metrics'];
	parent::__construct($tsa);
	$this->p10();
    }
    
    private function p10() {
	static $now = false;
	if (!$now) $now = time();
	$gte = ['$gte' => $now - self::sinceS];
	$q = ['cpu' => ['$exists' => 1], 'end_exec_ts' => $gte];
	$s['sort'] = ['cmd_seq' => -1];
	$m = $this->mcoll->find($q, $s); unset($q, $s);
	$l = $this->lcoll->find(['seqmeta.ts' => $gte], ['sort' => ['seqmeta.ts' => -1]]);
	$this->p20($m, $l);
	return;
    }
    
    private function p20($m, $l) {
	
    }
}


if (didCLICallMe(__FILE__)) new dao_lav_display();
