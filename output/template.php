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
     .lav	{ display: none; text-align: center; } 
   /* .cbp	{ margin-top: 1.5ex; font-size: 120%; padding-left: 1.0ex }
    .cb         { transform: scale(1.0); border: 3px solid black; padding: 2px; margin: 1px; display: inline-block; } */
    .lavl	{ padding-left: 0.5ex; font-size: 110%; }
    
    .cbp10 { 
	 background-color: #aaaaaa; 
         display: inline-block; 
         height: 1.75em; 
         width:  1.75em; 
         position: relative; 
         border-radius: 5px;
    }
    .cb10 {
        transform: scale(1.3);
        display: block;
        position: absolute;
        top: 50%;
        height: 1.1em; width: 1.1em;
        margin: -0.557em 0 0 0.37em;
        /* top bottom right left*/

    }
 </style>
</head>
<body>
    <div id="msg"></div>
    <table id='topt'><?php echo $tht; ?></table>
    <table>	     <?php echo $fht; ?></table>
    
    <!-- <div class='cbp'></div>     -->
    
    <table class='raw'><caption>more calls</caption>
		     <?php echo $rht; ?></table>
</body>
</html>
