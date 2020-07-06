<?php

require_once('/opt/kwynn/kwcod.php');
require_once('utils/dao.php');

class dao_aws_metric_summary extends aws_metrics_dao {
    public function __construct() { parent::__construct(self::dbName); }
    
}

dosum();

function dosum() {
    
}