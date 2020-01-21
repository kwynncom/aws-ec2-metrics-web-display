<?php // template.php

function doTemplate($fht = '', $rht = '', $tht = '', $seqin) { // filtered HTML and raw HTML

    if (!$seqin || !isset($seqin['seq']) || !is_numeric($seqin['seq'])) $seq = 0;
    else					 $seq = $seqin['seq'];
    
// HTML for full HTML template, as opposed to CLI form
$htt = <<<HT1
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>AWS EC2 CPU credit balance</title>
<script src='pcontrol/pcontrol.js'></script>
<script>
    new kwAWSCPU_latest($seq);
</script>
	
 <style>
	    body        { font-family: monospace }
	    table.raw   { margin-top: 4ex }
	    caption     { font-weight: bold; font-size: 115% }
	    th.cpu      { text-align: left }
	    div.dsu     { margin-bottom: 1ex}
	    #topt       { font-size: 110%; margin-bottom: 1ex }
	</style>

</head>
<body>
	<table id='topt'>$tht</table>
	<table>$fht</table>
	<table class='raw'><caption>more calls</caption>$rht</table>
</body>
</html>
HT1;

$htt = trim($htt);

if (PHP_SAPI !== 'cli' || 0) echo $htt; // The 1 is for testing the HTML in CLI mode.
// else echo $fht . "\n********\n" . $rht;
}
// end template.php ************