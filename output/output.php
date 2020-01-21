<?php

require_once('template.php');
require_once('filterOutput.php');



function awsMOutput($dao, $pci) {
    
    if (isset(     $pci['ts'])) 
	  $since = $pci['ts'];
    else  $since = false;
    
    $rows = $dao->getSince($since); // if no argument, get default number (N) days of data
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
	
	if ($r['status'] !== 'OK') continue;

	$rres = outCalcs($r, $i); extract($rres);
	
	if (!$amf && !$print) continue;
	
	$ht .= '<tr>';
	$ht .= "<td>$cpu</td><td>$net</td><td>$edates</td><td class='bdates'>$bdates</td>";
	$ht .= '</tr>' . "\n";
	
	if ($i === 0) {
	    $latestCPU = $cpu;
	    $latestNet = $net;
	    $latestTS  = $r['end_exec_ts'];
	    $x = 2;
	}
	
	$i++;
    }
    
    if (!$isAjax) $ht .= '</tbody>';
    
    if ($dao) {
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

function topOutput($cpu, $net, $dao, $asof) {
    $dsu = getDiskUsedPercentage('%');
    
    $ht = '';
    $ht .= "<tr><td id='kwdu'>$dsu</td><td>du <span id='kwduasof'></span></td></tr>\n";
    
    $dt1 = date('g:ia', $asof);
    $dt2 = date('s\s m/d' , $asof);
    $asofs = "<tr><td>$dt1</td><td>$dt2 as of </td></tr>\n";
    
    $ht .= "<tr><td>$cpu</td><td>curr. CPU bal.</td></tr>\n";
    
    
    $nd  = sprintf('%0.1f',  $net);
    $ht .= "<tr><td>$nd</td><td>latest network</td></tr>\n";
    
    $aa = $dao->getAgg();
    $pn = sprintf('%0.1f', round($aa[0], 1));
    $cn = sprintf('%0.1f', round($aa[1], 1));
    
    $ht .= "<tr><td>$cn</td><td>curr. month network usage (GB)</td></tr>\n";
    
    if (date('j') <= 3)
    $ht .= "<tr><td>$pn</td><td>prev. month network usage (GB)</td></tr>\n";
    
    $ht .= $asofs;
 
    return $ht;
}

function outCalcs($r, $i) { // $r row $i is row number
    
    static $now = false;
    if (!$now) $now = time();
    
    $df = 'm/d h:ia';
    $bts = $r['begin_ts'];
    $ets = $r['end_exec_ts'];
    $dts = $ets - $bts;
    
    if (!isset( $r['netavg'])) $net = getCAWSAvg($r['net'], $dts);
    else $net = $r['netavg'];

    $edates = date($df, $ets);
    $bdates = date($df, $bts); unset($df);
    if (isset   ($r['cpu']))
    $cpu = round($r['cpu'], 2);
    else
    $cpu = '';

    $print = false;

    if (!isTest('f2')) $showIGT = 86400 * 2;
    else               $showIGT = 120;
    
    if ($cpu < 71.98) $print = true;
    if ($dts > 86000) $print = true;
    if ($i < 5)       $print = true;
    if ($now - $ets < $showIGT ) $print = true;

    $rvs = ['cpu', 'net', 'edates', 'bdates', 'print'];
    return compact($rvs);
}
