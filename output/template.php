<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>AWS EC2 CPU credit balance</title>
<script src='pcontrol/pcontrol.js'></script>
<style>
    body        { font-family: monospace }
    table.raw   { margin-top: 4ex }
    caption     { font-weight: bold; font-size: 115% }
    th.cpu      { text-align: left }
    div.dsu     { margin-bottom: 1ex}
    #topt       { font-size: 110%; margin-bottom: 1ex }
    .tar        { text-align: right}
    td		{ padding-right: 0.7ex}
 </style>
</head>
<body>
    <div id="msg"></div>
    <table id='topt'><?php echo $tht; ?></table>
    <table>	     <?php echo $fht; ?></table>
    <table class='raw'><caption>more calls</caption>
		     <?php echo $rht; ?></table>
</body>
</html>
