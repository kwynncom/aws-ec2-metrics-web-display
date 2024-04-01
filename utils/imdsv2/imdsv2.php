<?php

require_once('/opt/kwynn/kwutils.php');

class imdsv2Cl {
    const urlb = 'http://169.254.169.254/latest/meta-data/'; // meta-data is all I care about for now.
    const timeoutS = 21600;
    const timeoutTestS = 120;

    public readonly string $url;
    public readonly bool   $istest;
    
    private function __construct(string $url, bool $istest) {

	$this->url = $url;
	$this->istest = $istest;
	$this->do10();
    }

    private function oec(string $s) {
	if (iscli()) echo($s . "\n");
    }

    private function do10() {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
	$timeout = $this->istest ? self::timeoutTestS : self::testoutS;
	$this->oec('timeout = ' . $timeout);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-aws-ec2-metadata-token-ttl-seconds' => $timeout]); 
	$token = trim(curl_exec($ch));
	$this->oec($token);

	unset($timeout);


    }

    public static function test() {
	
    }

}

if (didCLICallMe(__FILE__)) imdsv2Cl::test();

