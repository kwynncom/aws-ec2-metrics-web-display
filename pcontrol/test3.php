<?php

function kwarg() {
    
    global $argc;
    global $argv;
    
    if (     $argc      < 2 && 
       !isset($_REQUEST['arg'])) die('not enough args');
    
    if ($argc >=2) return $argv;
    else {
	$ret = [];
	$ret[1] = $_REQUEST['arg'];
	return $ret;
    }
}

$args = kwarg();

if      ($args[1] === 'w') {
    file_put_contents('/opt/www/kwynn_awscpu_202006_1', 'blah');

}
else if ($args[1] === 'r') file_get_contents('/opt/www/kwynn_awscpu_202006_1');
else die('bad arg');

sleep(10);
