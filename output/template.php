<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>AWS EC2 CPU credit balance</title>
<script src='pcontrol/pcontrol.js'></script>
<script src='output/output.js'></script>
<style>
    body        { font-family: monospace }
    table.raw   { margin-top: 2ex }
    caption     { font-weight: bold; font-size: 115% }
    th.cpu      { text-align: left }
    div.dsu     { margin-bottom: 1ex}
    #topt       { font-size: 110%; margin-bottom: 1ex }
    .tar        { text-align: right}
    td		{ padding-right: 0.7ex}
     .lav	{ display: none; } 
    .cbp	{ margin-top: 1.5ex; font-size: 120%; padding-left: 1.0ex }
    .cb         { transform: scale(1.7);  }
    .lavl	{ padding-left: 0.5ex; }
 </style>
</head>
<body>
    <div id="msg"></div>
    <table id='topt'><?php echo $tht; ?></table>
    <table>	     <?php echo $fht; ?></table>
    
    <div class='cbp'><input type='checkbox' class='cb' onclick='cbClick(this.checked);' /><label class='lavl'>show load averages</label></div>    
    
    <table class='raw'><caption>more calls</caption>
		     <?php echo $rht; ?></table>
</body>
</html>
