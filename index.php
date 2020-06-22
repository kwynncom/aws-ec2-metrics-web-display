<?php

require_once('utils/dao.php');
require_once('get/get.php');
require_once('output/output.php');
require_once('pcontrol/pcontrol.php');

function index_f($seqFifo = false) {
    $dao  = new aws_metrics_dao();
    $seqo = new aws_cpu_pcontrol($dao);
    $pci  = $seqo->getSeq($dao, $seqFifo);
    awsMOutput($dao, $pci);
}

index_f();
