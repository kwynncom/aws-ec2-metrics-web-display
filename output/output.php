<?php

require_once('templateCaller.php');
require_once('filterOutput.php');
require_once(__DIR__ . '/../utils/ubuup.php');
require_once(__DIR__ . '/../get/journal.php');
require_once('filter2.php');

function awsMOutput($dao, $pci) {
    
    if (isset(     $pci['ts'])) 
	  $since = $pci['ts'];
    else  $since = false;
    
    $rows = aws_metrics_filtered_out::get($since); // if no argument, get default number (N) days of data
    if ($since && !$rows) getDUJS(1);
    $rht = getHTFromRes ($rows, 0, 0, $since); // raw HTML
    $fres = filterOutput($rows); // filtered result
    $fa  =  getHTFromRes($fres, 1, $dao, $since); // filtered HTML
    $fht = $fa['fht'];
    $tht = $fa['top'];
    
    if ($since) {
	$t['id'] = 'rawtb';
	$t['v']  = $rht;
	$r[] = $t;
	$t['id'] = 'filttb';
	$t['v']  = $fht;
	$r[] = $t;
	$t['id'] = 'topt';
	$t['v']  = $tht;
	$r[] = $t;
	
	kwjae($r);
	
    }
    
    doTemplate($fht, $rht, $tht, $pci);
}

function getHTFromRes($rows, $amf = false, $dao = false, $isAjax) { // $amf - "am I (is $rows) already filtered?"
    $ht = '';
    
    if (!$isAjax) $ht .= "<thead><tr><th class='cpu'>cpu</th><th>net</th><th>end</th><th>beg</th></tr></thead>\n";
    if ($amf) $id = 'filttb';
    else      $id = 'rawtb';
    
    if (!$isAjax) $ht .= "<tbody id='$id'>\n";
    
    $i = 0;
    $latestCPU = -1;
    $latestNet = -1;
    
    foreach($rows as $r) {

	if (!isset($r['begin_ts'])) {
	    $ignore = 2;
	    
	}
	
	$rres = outCalcs($r, $i); extract($rres);
	
	if (!$amf && !$print) continue;
	
	$ht .= '<tr>';
	$ht .= "<td>$cpu</td><td>$net</td><td>$edates</td><td class='bdates'>$bdates</td>";
	$ht .= '</tr>' . "\n";
	
        if (is_numeric($cpu) && $latestCPU === -1)  $latestCPU = $cpu;
        if (is_numeric($net) && $latestNet === -1 ) $latestNet = $net;
        if ($i === 0) $latestTS  = $r['end_exec_ts'];
	
	$i++;
    }
    
    if (!$isAjax) $ht .= '</tbody>';
    
    if ($dao) {
	
	if (!isset($latestTS)) $latestTS = time() - 300; // Kwynn 2020/06/20
	
	$topht = topOutput($latestCPU, $latestNet, $dao, $latestTS);
	return ['top' => $topht, 'fht' => $ht];
    }
    
    return $ht;
}

function getDUJS($only = false) {
    $t['id'] = 'kwdu';
    $t['v']  = getDiskUsedPercentage('%');
    $out[] = $t;
    $t['id'] = 'kwduasof';
    
    $dt1 = ' as of ' . date('g:i:sa m/d');
    
    $t['v']  =  $dt1;
    $out[] = $t;
    
    if (!$only) return $out;
    
    kwjae($out);
}

function ubuupOut() {
    $rfv = '-';
   
    $o = getUbuupCli();
    if (!$o || !is_object($o) || !isset($o->vital)) return $rfv;
    $r =			        $o->vital ? '*Y*' : 'OK';
    
    return $r;
}

function getRedoBtn() {

    $t = '<button onclick="window.history.go(0);">redo</button>';
    return $t;
    
}

function topOutput($cpu, $net, $dao, $asof) {
    $ht  = '';
    $dsu10 = sprintf('%4.1f', getDiskUsedPercentage()) . '%';
    $jsu10 = sprintf('%4.1f', journalSizePer()       ) . '%';
    $dsu   =  str_replace(' ', '&nbsp;', $dsu10);
    $jsu   =  str_replace(' ', '&nbsp;', $jsu10);
    
    
    $ht .= "<tr><td id='kwdu' class=''>$dsu</td><td>du <span id='kwduasof'></span></td></tr>\n";

    $ht .= "<tr><td class=''>$jsu</td><td>/var/log/journal</td></tr>\n";
    $ht .= '<tr><td class="">' . ubuupOut() . '</td><td>ubuup ' . getRedoBtn() . '</td></tr>' . "\n";
   
    
    $dt1 = date('g:ia', $asof);
    $dt2 = date('s\s m/d' , $asof);
    $asofs = "<tr><td>$dt1</td><td>$dt2 as of </td></tr>\n";
    
    $scurl = 'https://github.com/kwynncom/aws-ec2-metrics-web-display';
    
    $ht .= "<tr><td class=''>$cpu</td><td>curr. CPU bal. (<a href='$scurl'>source code</a>)</td></tr>\n";
    
    $nd = '-';
    if (is_numeric($net)) $nd  = sprintf('%0.1f',  $net);
    $ht .= "<tr><td class=''>$nd</td><td>latest network</td></tr>\n";
    
    $aa = $dao->getAgg();
    $pn = sprintf('%0.1f', kwnullround($aa[0], 1));
    $cn = sprintf('%0.1f', kwnullround($aa[1], 1));
    
    $ht .= "<tr><td class=''>$cn</td><td>curr. month network usage (GB)</td></tr>\n";
    
    if (date('j') <= 3)
    $ht .= "<tr><td class=''>$pn</td><td>prev. month network usage (GB)</td></tr>\n";
    
    $ht .= $asofs;
 
    return $ht;
}

function kwnullround($n, $places) {
    if (is_numeric($n)) return round($n, $places);
    else return ' ';
    
}

function outCalcs($r, $i) { // $r row $i is row number
    
    static $now = false;
    static $iid = false;
    
    if (!$iid) $iid = $r['iid'];
    
    if (!$now) $now = time();
    
    $df = 'm/d h:ia';
    if (!isset($r['begin_ts'])) {
	$ingore = 2;
	
    }
    $bts = $r['begin_ts'];
    $ets = $r['end_exec_ts'];
    $dts = $ets - $bts;
    
    $net = false;
    
    if    ( !isset( $r['netavg']) && isset($r['net'])) $net = getCAWSAvg($r['net'], $dts);
    else if (isset( $r['netavg'])) $net = $r['netavg'];

    $edates = date($df, $ets);
    $bdates = date($df, $bts); unset($df);
    if (isset   ($r['cpu']))
    $cpu = kwnullround($r['cpu'], 2);
    else
    $cpu = '';

    $print = false;

    if (!isTest('f2')) $showIGT = 12000;
    else               $showIGT = 120;
      
    if ($cpu < aws_cpu::getMaxCPUCreditFromInstanceID($iid) - 0.02) $print = true;
    if ($dts > 86000) $print = true;
    if ($i < 5)       $print = true;
    if ($now - $ets < $showIGT ) $print = true;
    if ($net > 3.99) $print = true;

    $rvs = ['cpu', 'net', 'edates', 'bdates', 'print'];
    return compact($rvs);
}
