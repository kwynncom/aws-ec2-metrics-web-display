<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>load average vs. AWS CPU balance</title>

<style>
    body { font-family: monospace }
    .backd10 { font-size: 85%; margin-bottom: 2ex;  }
    td.cpu { padding-right: 2ex; }
    td.date { padding-left: 2ex; }
</style>

</head>
<body>

    <div class='backd10'><a href='../'>back to all metrics</a></div>
    <table>
	<thead></thead>
	<tbody>
<?php
    require_once('lavDispClass.php');
    $o = new dao_lav_display();
    $ht = '';
    while ($r = $o->getOutFr()) {
	if ($r === true) continue;
	extract($r);
	if ($i > 200) break;
	$ht .= '<tr>';
	$ht .= '<td class="cpu">';
	$ht .= $cpu;
	$ht .= '</td>';
	$ht .= '<td>';
	$ht .= $lav;
	$ht .= '</td>';
	$ht .= '<td class="date">';
	$ht .= $end;
	$ht .= '</td>';
	$ht .= '</tr>' . "\n";
	continue;
    }
    echo $ht;
?>
	</tbody>
    </table>
</body>
</html>

