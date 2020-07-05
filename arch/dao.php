<?php
die('archive only');
require_once('/opt/kwynn/kwutils.php');
require_once('utils.php');

class aws_metrics_dao extends dao_generic {
    const dbName = 'aws_cpu';
    const aggF   = 0.0026352; // 30.5 * 86400 / 1000000000; bytes per second to GB per month
    const defaultDays = 31;
    
    public function __construct() {
	parent::__construct(self::dbName);
	$this->mcoll = $this->client->selectCollection(self::dbName, 'metrics');
	$this->ocoll = $this->client->selectCollection(self::dbName, 'config');
	$this->pcoll = $this->client->selectCollection(self::dbName, 'pcontrol');
	$this->pmucoll = $this->client->selectCollection(self::dbName, 'pcmutex');
	$this->updatedb();
    }
    
    private function updatedb() {
	if (time() > strtotime('2020-07-06')) return;
	$this->pcoll->drop();
	$this->pmucoll->drop();
    }
    
    public function getSeq($name) { return parent::getSeq($name);  }
    
    public function put($dat) { $this->mcoll->upsert(['cmd_seq' => $dat['cmd_seq']], $dat); }
    
    public function getLatest() {
	$res = $this->mcoll->findOne(['status' => 'OK'], ['sort' => ['begin_ts' => -1], 'limit' => 1]);
	return $res;
    }
    
    public function getSince($sin) { 
	
	if (!$sin) $since = time() - 86400 * self::defaultDays;
	else       $since = $sin;
	
	// Kwynn 2020/06/20 added status
	return  $this->mcoll->find(['end_exec_ts' => ['$gte' => $since], 'status' => 'OK'], ['sort' => ['begin_ts' => -1]])->toArray();  
	
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
    
    private function pcMutexInit() {
	
	$muc =  $this->pmucoll;
	
	$res = $muc->findOne(['type' => 'placeholder']);
	if (!$res) {
	    $muc->createIndex(['type' => -1], ['unique' => true ]);
	    $muc->insertOne(['type' => 'placeholder']);
	}
    }
    
    private function pcunlock() {
	$this->pmucoll->deleteOne(['type' => 'running']);		
    }
    
    private function pclock() {
	$this->pcMutexInit();
	$this->pmucoll->insertOne(['type' => 'running']);
    }
    
    public function insertPC() {
	
	$this->pclock();
	$seq = $this->getSeq('aws-cpu-pcontrol');
	$now = time();
	$dat['pc_start_ts']   = $now;
	$dat['r'] = date('r', $now);
	$dat['seq'] = $seq;
	$dat['status'] = 'init';
	$dat['pid_status'] = false;
	$this->pcoll->insertOne($dat);
	return $seq;
    }
    
    public function countPC($within) { return $this->pcoll->count(     ['pc_start_ts' => ['$gte' => time() - $within]]);    }
    public function clearPC($within) {        $this->pcoll->deleteMany(['pc_start_ts' => ['$lte' => time() - $within]]);    }
    
    public function pidPC($seq, $pid) {
	$dat['pid'] = $dat['pid_status'] = $pid;
	$dat['status'] = 'pid-set';
	$this->pcoll->upsert(['seq' => $seq], $dat);
    }
    
    public function donePC($seq) { 
	$dat['status'] = 'OK';
	$dat['pid_status'] = true;
	$this->pcoll->upsert(['seq' => $seq], $dat);
	$this->pcunlock();
		
    }
        
    public function getPC(/*$seq*/) {
	$res = $this->pcoll->findOne(['sent' => ['$exists' => false], 'pid' => ['$exists' => true], 'pc_start_ts' => ['$exists' => true]], ['sort' => ['pc_start_ts' => -1]]);
	if (isset( $res['pid_status']))
	    return $res;
	return false;
    }
    
    public function putPCSent($pid) {
	$this->pcoll->upsert(['pid' => $pid], ['sent' => true]);
    }
    
}

if (PHP_SAPI === 'cli' && time() < strtotime('2019-11-28')) {
    $dao = new aws_metrics_dao();
    $dao->getAgg();
}