<?php

require_once('/opt/kwynn/kwutils.php');
require_once('./../utils/dao.php');

class aws_metrics_filtered_out extends aws_metrics_dao {
    public static function get() {
	$o = new self();
    }
    
    private function __construct() {
	parent::__construct(self::dbName);
	$this->get10();
	$this->f10();
    }
    
    private function f10() {
	$a = $this->thea;
	
	
    }
    
    private function get10() {
	$this->thea = $this->getSince();
    }
}

if (iscli()) aws_metrics_filtered_out::get();

