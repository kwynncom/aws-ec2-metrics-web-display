<?php

require_once('/opt/kwynn/kwutils.php');

class imdsv2Cl extends dao_generic_4 {

    const testUntil = '2024-04-01 02:20';
    const dropUntil = '2024-04-01 02:20';

    const urlb = 'http://169.254.169.254/latest';
    const urlgetT = '/api/token';

    const urlget = '';
    const timeoutS = 21600;
    const timeoutTestS = 40;
    const timeoutMarginS = 20;
    const deleteAtS = 0;
    const minTLen = 20; // actual is roughly 56, including padding-type non-space chars
    const maxTLen = 150;
    const dbname = 'imdsv2';
    const collnm = 'tokens';
    
    public readonly string $ores;
    public readonly string $url;
    public readonly int $now;
    public readonly int $expires_at;

    private readonly string $vtoken;
    private readonly object $tcoll;
        



    private function dbi10() {
	parent::__construct(self::dbname);
	$c = $this->kwsel(self::collnm);
	if ($this->now < strtotime(self::dropUntil)) $c->drop();
	$c->deleteMany(['expires_at' => ['$lte' => $this->now - self::deleteAtS]]);
	$this->tcoll = $c;
    }

    private function do30() {
	$d = [];
	$d['token'] = $this->vtoken;
	$d['expires_at'] = $this->expires_at;
	$d['expires_at_r'] = date('r', $d['expires_at']);
	$this->tcoll->insertOne($d);
    }

    private function __construct(string $url) {

	$this->url = $url;
	$this->now = time();
	$this->dbi10();
	$this->do10();
	$this->get10();
    }

    private function getTO() : int {
	$test = false;
	if ($this->now < strtotime(self::testUntil)) $test = true;

	return $test ? self::timeoutTestS : self::timeoutS;
    }

    public static function oec(string $s) {
	if (!iscli()) return;
	echo($s . "\n");
    }

    private function setVT($tin) {
	$tin = trim($tin);
	if (!$tin) return;
	if (!is_string($tin)) return;
	if (!preg_match('/^\S{' . self::minTLen . ',' . self::maxTLen. '}/', $tin)) return;
	$this->vtoken = $tin;
	
    }

    private function haveToken() : bool {
	$q = ['expires_at' => ['$gt' => $this->now + self::timeoutMarginS]];
	$s = ['sort' => ['expires_at' => -1]];

	$res = $this->tcoll->findOne($q, $s);
	$t = kwifs($res, 'token');
	if ($t) { 
	     $this->vtoken = $t;
	     return true;
	}

	return false;
    }

    private function get10() {
	$url = self::urlb . $this->url;
	$this->oec($this->url);
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-aws-ec2-metadata-token: ' . $this->vtoken]);
	$this->ores = curl_exec($ch);
	$this->oec($this->ores);
	
    }



    private function do10() {
	if ($this->haveToken()) {
	    $this->oec('from db: ' . $this->vtoken);
	    return;
	}
	$url = self::urlb . self::urlgetT;
	$this->oec($url);
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
	$timeout = $this->getTO();
	$this->oec('timeout = ' . $timeout);
	$this->expires_at = $timeout + $this->now;
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-aws-ec2-metadata-token-ttl-seconds: ' . $timeout]);
	$this->oec('calling cURL for token');
	$token = trim(curl_exec($ch));
	$this->oec($token);
	$this->setVT($token);
	$this->oec($this->vtoken);
	$this->do30();

	unset($timeout);


    }

    public static function get(string $url) : string {
	$o = new self($url);
	return kwifs($o, 'ores', ['kwiff' => '']);
    }

    public static function test() {
	self::oec(self::get('/meta-data/instance-id'));
	self::oec(self::get('/meta-data/instance-type'));
    }

}

if (didCLICallMe(__FILE__)) imdsv2Cl::test();

