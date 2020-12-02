<?php

require_once(__DIR__ . '/../utils/dao.php');
require_once('/opt/kwynn/mongodb2.php');

class dao_lav extends dao_generic_2 {
    
    const dbName = aws_metrics_dao::dbName;
    
    public function __construct($moreColls = false, $doit = false) {
	parent::__construct(self::dbName, __FILE__);
	$tsa = ['l' => 'loadavg'];
	if ($moreColls) $tsa = array_merge($tsa, $moreColls);
	$this->creTabs(self::dbName, $tsa);
	if ($doit) $this->doit();
    }
    
    private function doit() {
	$dat = $this->lcoll->getSeq2(true);
	$dat['lav'] =  sys_getloadavg();
	$this->lcoll->insertOne($dat);
    }
}

if (didCLICallMe(__FILE__)) new dao_lav(false, true);
