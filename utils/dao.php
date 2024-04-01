<?php

require_once('/opt/kwynn/kwutils.php');
require_once('utils.php');

class aws_metrics_dao extends dao_generic {
    const dbName = 'aws_cpu';
    const aggF   = 0.0026352; // 30.5 * 86400 / 1000000000; bytes per second to GB per month
    const defaultDays = 31;

    private $mcoll;
    private $ocoll;
    private $icoll;

    
    public function __construct() {
	parent::__construct(self::dbName);
	$this->mcoll = $this->client->selectCollection(self::dbName, 'metrics');
	$this->ocoll = $this->client->selectCollection(self::dbName, 'config');
	$this->icoll = $this->client->selectCollection(self::dbName, 'instances');
    }
    
    public function getI($iid, $f)        { 
	$res = $this->icoll->findOne(['iid' => $iid, 'datv' => 2]);
	if ($res && isset($res[$f])) return $res[$f];
	return false;
	
    }
    public function putI($iid, $f, $v) {        
	$dat['iid' ] = $iid ;
	$dat[$f    ] = $v;
	$dat['datv'] = 2;
	$this->icoll->upsert(['iid' => $iid], $dat);
	
    }
    
    public function getSeq($name) { return parent::getSeq($name);  }
    
    public function put($dat) { $this->mcoll->upsert(['cmd_seq' => $dat['cmd_seq']], $dat); }
    
    public function getLatest() {
	$res = $this->mcoll->findOne(['status' => 'OK'], ['sort' => ['begin_ts' => -1], 'limit' => 1]);
	return $res;
    }
    
    public function getSince($sin = false) { 
	
	if (!$sin) $since = time() - 86400 * self::defaultDays;
	else       $since = $sin;
	
	$sq['end_exec_ts'] = -1;
	$sq['begin_ts'   ] = -1;
	// $sq['cpu'	 ] =  1;
	$fsq['sort'] = $sq;
	$pj = ['end_exec_ts' => 1, 'begin_ts' => 1, 'net' => 1, 'cpu' => 1, 'gpm' => 1, '_id' => 0, 'end_iso' => 1, 'begin_iso' => 1, 'iid' => 1, 'lav' => 1];
	$a2['sort'] = $sq;
	$a2['projection'] = $pj;
		// [$fsq, $p];
	$rows = $this->mcoll->find(['end_exec_ts' => ['$gte' => $since], 'status' => 'OK'], $a2)->toArray();  
	return $rows;
	
    }
    
    public function getConfig($name) { 	return $this->ocoll->findOne(['name' => $name]);  }
    
    public function getAgg() {
	
	$mbs = getMonthBounds();
	extract($mbs); // c, p, n
	
	$group =   [	'$group' => [
			'_id' => 'aggdat',
			'bytes' => ['$sum' => '$net'],
			's'     => ['$sum' => ['$subtract' => ['$end_exec_ts', '$begin_ts']]]
			]  ];
	
	$bs[] = ['b' => $c, 'e' => $p];
	$bs[] = ['b' => $n, 'e' => $c];
	
	$reta = [];
	
	foreach($bs as $b) {
	    $match = ['$match' => ['end_exec_ts' => ['$gte' => $b['e']], 'begin_ts' => ['$lte' => $b['b']]]];
	    $q = [$match, $group];

	    $gpm = 0;
	    $aa = $this->mcoll->aggregate($q)->toArray();
	    if (isset($aa[0])) {
		$aa = $aa[0];
		if (isset($aa['s']) && $aa['s'] && isset($aa['bytes'])) $gpm = ($aa['bytes'] / $aa['s']) * self::aggF;
	    }
	    
	    $reta[] = $gpm;
	}
	
	return $reta;
    }
}

if (PHP_SAPI === 'cli' && time() < strtotime('2019-11-28')) {
    $dao = new aws_metrics_dao();
    $dao->getAgg();
}