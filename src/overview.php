<?php
/*
           _     _                _           _     
          | |   (_)              | |         | |    
 _ __ ___ | |__  _ _ __ ______ __| | __ _ ___| |__  
| '__/ _ \| '_ \| | '_ \______/ _` |/ _` / __| '_ \ 
| | | (_) | |_) | | | | |    | (_| | (_| \__ \ | | |
|_|  \___/|_.__/|_|_| |_|     \__,_|\__,_|___/_| |_|

robin-dash: Centralized Controller for Robin-Mesh networking devices
Copyright (C) 2010-2011 Cody Cooper.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if(file_exists("settings.php")) {require("settings.php");}
else {header("Location: oobe.php");exit;}


if(isset($_GET['id']) && file_exists($dir . "data/" . $_GET['id'] . ".xml")) {$networkname = $_GET['id'];$loggedin = "false";}
else {
	if($_SESSION['user'] && $_SESSION['pass'] && file_exists($dir . "data/" . $_SESSION['user'] . ".xml")) {
		$xmlp = simplexml_load_file($dir . "data/" . $_SESSION['user'] . ".xml");

		if($_SESSION['pass'] == $xmlp->robindash->password) {$networkname = $_SESSION['user'];$loggedin = "true";}
		else {header("Location: " . $wdir);exit;}
	}
	else {header("Location: " . $wdir);exit;}
}

// Sets the timezones for us
$xmlp = simplexml_load_file($dir . "data/" . $networkname . ".xml");
setTimezoneByOffset($xmlp->management->enable_gmt_offset);

if(isset($_GET['action']) && $_GET['action'] == "download" && $_GET['format'] == "csv") {
	if(file_exists($dir . "data/" . $networkname . ".csv")) {
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Last-Modified: " . gmdate ("D, d M Y H:i:s", filemtime ($dir . "data/" . $networkname . ".csv")) . " GMT");
		header("Cache-Control: private", false);
		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename=" . $networkname . ".csv");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: " . filesize($dir . "data/" . $networkname . ".csv"));
		header("Connection: close");
		readfile($dir . "data/" . $networkname . ".csv");
	}
	else {echo $_LANG['error_no_checkin_data'];}

	exit;
}



else if(isset($_GET['action']) && $_GET['action'] == "block" && isset($_GET['mac'])) {
	if(is_dir($dir . "data/stats/" . $networkname . "/banned/")) {echo	"";}
	else {mkdir($dir . "data/stats/" . $networkname . "/banned/");}
	
	$fh = fopen($dir . "data/stats/" . $networkname . "/banned/" . base64_encode($_GET['mac']) . ".txt", 'w') or die("Can't write to the data file.");
	fwrite($fh, "1");
	fclose($fh);
	
	header("Location: " . $wdir . "overview.php?id=" . $_GET['id'] . "&action=clients");
	exit;
}



else if(isset($_GET['action']) && $_GET['action'] == "unblock" && isset($_GET['mac'])) {
	if(is_dir($dir . "data/stats/" . $networkname . "/banned/")) {echo	"";}
	else {mkdir($dir . "data/stats/" . $networkname . "/banned/");}
	
	if(file_exists($dir . "data/stats/" . $networkname . "/banned/" . base64_encode($_GET['mac']) . ".txt")) {unlink($dir . "data/stats/" . $networkname . "/banned/" . base64_encode($_GET['mac']) . ".txt");}
	
	header("Location: " . $wdir . "overview.php?id=" . $_GET['id'] . "&action=clients");
	exit;
}




else if(isset($_GET['action']) && $_GET['action'] == "clients" && isset($_GET['mac'])) {
	$done = array();
	$kbdown = array();
	$kbup = array();

	if($handle = opendir($dir . "data/stats/" . $networkname . "/" . date('Ymd') . "/")) {
		while(false !== ($file = readdir($handle))) {
			if($file != "." && $file != ".." && strpos($file, '.usage.') !==FALSE) {
				$fc = file_get_contents($dir . "data/stats/" . $networkname . "/" . date('Ymd') . "/" . $file);
				$fctwo = explode("&", $fc);
				$user = explode("+", urldecode($fctwo[1]));
				
				if(strpos(substr($file, 2, 2), '-') !==FALSE) {$hour = substr($file, 2, 1);}
				else {$hour = substr($file, 2, 2);}

				foreach($user as $thatuser) {
					$data = explode(",", $thatuser);
					
					if($data[3] == $_GET['mac']) {
						$thekbdown = round($data[1] / 1024, 0);
						$thekbup = round($data[2] / 1024, 0);
						$themac = $data[3];
						
						if($done[$hour][$themac] == 1) {echo "";}
						else {
							$kbdown[$hour] = $kbdown[$hour] + $thekbdown;
							$kbup[$hour] = $kbup[$hour] + $thekbup;
							
							$done[$hour][$themac] = 1;
						}
						
						$hascontent = true;
					}
				}
			}
		}

		closedir($handle);
	}

	if($kbdown[0] == "") {$kbdown[0] = 0;$kbup[0] = 0;}
	if($kbdown[1] == "") {$kbdown[1] = 0;$kbup[1] = 0;}
	if($kbdown[2] == "") {$kbdown[2] = 0;$kbup[2] = 0;}
	if($kbdown[3] == "") {$kbdown[3] = 0;$kbup[3] = 0;}
	if($kbdown[4] == "") {$kbdown[4] = 0;$kbup[4] = 0;}
	if($kbdown[5] == "") {$kbdown[5] = 0;$kbup[5] = 0;}
	if($kbdown[6] == "") {$kbdown[6] = 0;$kbup[6] = 0;}
	if($kbdown[7] == "") {$kbdown[7] = 0;$kbup[7] = 0;}
	if($kbdown[8] == "") {$kbdown[8] = 0;$kbup[8] = 0;}
	if($kbdown[9] == "") {$kbdown[9] = 0;$kbup[9] = 0;}
	if($kbdown[10] == "") {$kbdown[10] = 0;$kbup[10] = 0;}
	if($kbdown[11] == "") {$kbdown[11] = 0;$kbup[11] = 0;}
	if($kbdown[12] == "") {$kbdown[12] = 0;$kbup[12] = 0;}
	if($kbdown[13] == "") {$kbdown[13] = 0;$kbup[13] = 0;}
	if($kbdown[14] == "") {$kbdown[14] = 0;$kbup[14] = 0;}
	if($kbdown[15] == "") {$kbdown[15] = 0;$kbup[15] = 0;}
	if($kbdown[16] == "") {$kbdown[16] = 0;$kbup[16] = 0;}
	if($kbdown[17] == "") {$kbdown[17] = 0;$kbup[17] = 0;}
	if($kbdown[18] == "") {$kbdown[18] = 0;$kbup[18] = 0;}
	if($kbdown[19] == "") {$kbdown[19] = 0;$kbup[19] = 0;}
	if($kbdown[20] == "") {$kbdown[20] = 0;$kbup[20] = 0;}
	if($kbdown[21] == "") {$kbdown[21] = 0;$kbup[21] = 0;}
	if($kbdown[22] == "") {$kbdown[22] = 0;$kbup[22] = 0;}
	if($kbdown[23] == "") {$kbdown[23] = 0;$kbup[23] = 0;}
	if($kbdown[24] == "") {$kbdown[24] = 0;$kbup[24] = 0;}

	foreach($kbdown as $item_kbdown) {
		$biggest_kbdown = $biggest_kbdown + $item_kbdown;
	}

	foreach($kbup as $item_kbup) {
		$biggest_kbup = $biggest_kbup + $item_kbup;
	}

	$biggest_total = $biggest_kbdown + $biggest_kbup * 1.2;

	$data = $kbdown[0] . "," . $kbdown[1] . "," . $kbdown[2] . "," . $kbdown[3] . "," . $kbdown[4] . "," . $kbdown[5] . "," . $kbdown[6] . "," . $kbdown[7] . "," . $kbdown[8] . "," . $kbdown[9] . "," . $kbdown[10] . "," . $kbdown[11] . "," . $kbdown[12] . "," . $kbdown[13] . "," . $kbdown[14] . "," . $kbdown[15] . "," . $kbdown[16] . "," . $kbdown[17] . "," . $kbdown[18] . "," . $kbdown[19] . "," . $kbdown[20] . "," . $kbdown[21] . "," . $kbdown[22] . "," . $kbdown[23] . "," . $kbdown[24] . "|" . $kbup[0] . "," . $kbup[1] . "," . $kbup[2] . "," . $kbup[3] . "," . $kbup[4] . "," . $kbup[5] . "," . $kbup[6] . "," . $kbup[7] . "," . $kbup[8] . "," . $kbup[9] . "," . $kbup[10] . "," . $kbup[11] . "," . $kbup[12] . "," . $kbup[13] . "," . $kbup[14] . "," . $kbup[15] . "," . $kbup[16] . "," . $kbup[17] . "," . $kbup[18] . "," . $kbup[19] . "," . $kbup[20] . "," . $kbup[21] . "," . $kbup[22] . "," . $kbup[23] . "," . $kbup[24];
	
	// Send the user to the chart
	header("Location: http://chart.apis.google.com/chart?chxr=0,0,24|1,0," . round($biggest_total, 0) . "&chxt=x,y&chs=760x90&cht=lc&chco=FF0000,00FF00&chd=t:" . $data . "&chdl=Download+(MB)|Upload+(MB)&chg=14.3,-1,0,0&chls=1|1&chm=B,C5D4B5BB,0,0,0&chtt=Usage+over+the+past+24+hours+(for+user+with+MAC+address:+" . $_GET['mac'] . ")");
}





else if(isset($_GET['action']) && $_GET['action'] == "clients") {
include($dir . "resources/ouilookup.php");
?>
<html>
<head>
<title><?php echo $_LANG['clients_list'] . ": " . $brand; ?></title>
<link rel="stylesheet" type="text/css" href="<?php echo $wdir; ?>resources/style.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $wdir; ?>resources/lightbox/lightbox.css" />
<link rel="shortcut icon" href="<?php echo $wdir; ?>resources/favicon.ico"/>

<!-- 
<script type="text/javascript" src="<?php echo $wdir; ?>resources/sorttable.js"></script>
<script type="text/javascript" src="<?php echo $wdir; ?>resources/lightbox/prototype.js"></script>
<script type="text/javascript" src="<?php echo $wdir; ?>resources/lightbox/scriptaculous.js?load=effects,builder"></script>
<script type="text/javascript" src="<?php echo $wdir; ?>resources/lightbox/lightbox.js"></script>
-->
<script src="<?php echo $wdir; ?>resources/js/jquery-1.6.1.min.js" type="text/javascript"></script>
<script src="<?php echo $wdir; ?>resources/js/highcharts.js" type="text/javascript"></script>
<script type="text/javascript">

    <?php
	$xml = simplexml_load_file($dir . "data/" . $networkname . "_nodes.xml");
	foreach($xml->node as $node) {
	  echo "var chartrssi_" . strtr($node->name, "-", "_") . "; ";
	  echo "var chartdbm_" . strtr($node->name, "-", "_") . "; ";
	  echo "var chartstp_" . strtr($node->name, "-", "_") . "; ";	  
	}
     ?>

Highcharts.setOptions({
	global: {
		useUTC: false
	}
});

 function plot_single_var(pullURL, inchart, LrenderTo,  Ltitletext, Lsubtitletext, LyAxistext) {
   inchart = new Highcharts.Chart({
        chart: {
            renderTo: LrenderTo,
            defaultSeriesType: 'line',
	    zoomType: 'x',
            events: {
	       load: function (event) {
	     $.ajax({
	       url: pullURL,
		   success: function (items) {
		   for (var i = 0; i < items.length; i++) {
		     inchart.addSeries(items[i]);
		     inchart.xAxis.tickInterval = 3600 * 1000;
		   }
		 },
		   cache: false,
		   error: function (XMLHttpRequest, textStatus, errorThrown) { alert(errorThrown); }
	       });
	   }
	 }
        },
      title: {
         text: Ltitletext
      },
      subtitle: {
         text: Lsubtitletext
      },
      xAxis: {
         type: 'datetime',
      },
      yAxis: {
         title: {
            text: LyAxistext
         },
      },
      series: []
	 });
   return inchart;
 }
 
  $(document).ready(function() {
    <?php

			if (isset($_GET['date'])) {
	      $usedate = $_GET['date'];
	   } else {
	     $usedate = date('YmdHisT');
	   }
	$xml = simplexml_load_file($dir . "data/" . $networkname . "_nodes.xml");
	
	foreach($xml->node as $node) {
	  $chartvarname = "chartrssi_" . strtr($node->name, "-", "_");
	  $charturl = "overview-plot.php?network=" . $networkname . "&type=sta_rssi&station=" . $node->mac . "&date=" . $usedate;
	  echo " plot_single_var('" . $charturl ."', " . $chartvarname . ", '" . $chartvarname . "', 'RSSI', 'From Yesterday 0:00 AM to now','RSSI');";

	  $chartvarname = "chartdbm_" . strtr($node->name, "-", "_");
	  $charturl = "overview-plot.php?network=" . $networkname . "&type=sta_dbm&station=" . $node->mac . "&date=" . $usedate;
	  echo " plot_single_var('" . $charturl ."', " . $chartvarname . ", '" . $chartvarname . "', 'dbm', 'From Yesterday 0:00 AM to now','dbm');";

	  $chartvarname = "chartstp_" . strtr($node->name, "-", "_");
	  $charturl = "overview-plot.php?network=" . $networkname . "&type=sta_stp&station=" . $node->mac . "&date=" . $usedate;
	  echo " plot_single_var('" . $charturl ."', " . $chartvarname . ", '" . $chartvarname . "', 'stp', 'From Yesterday 0:00 AM to now','stp');";
	  
	  
	}
     ?>
	
	  });


</script>
</head>
<body>
<div id="wrapper">
	<div id="header">
		<div id="logo"><?php echo $_LANG['clients']; ?></div>
	</div>
	<div id="content">
		<div id="page-content">

			<?php
								  
          	   $prevdate = date_create_from_format('YmdHisT', $usedate);
          	   $nextdate = date_create_from_format('YmdHisT', $usedate);
                        $prevdate->sub(date_interval_create_from_date_string('1 day'));
                        $nextdate->add(date_interval_create_from_date_string('1 day'));

			echo "<a href='overview.php?id=" . $networkname . "&action=clients&date=" . $prevdate->format('YmdHisT') . "'>&lt;--Prev day</a><br />";
			echo "NOW == " . date_create_from_format('YmdHisT', $usedate)->format('Y-m-d') . "<br />";
			echo "<a href='overview.php?id=" . $networkname . "&action=clients&date=" . $nextdate->format('YmdHisT') . "'>&nbsp;&nbsp;&nbsp; Next day --&gt;</a><br />";

			$xml = simplexml_load_file($dir . "data/" . $networkname . "_nodes.xml");

			foreach($xml->node as $node) {
			  ?>
			  <table style="width: 100%;">
			    <thead>
				<tr>
			           <th><?php echo $node->name . ' ' . $node->ip . ' ' . $node->mac; ?></th>
			       </tr>
			    </thead>
			    <tbody>
			    <tr><td>
			  <?php
                              echo "<div id='chartrssi_" . strtr($node->name, "-", "_") . "' style='height: 240px; width: 100%'></div><br />";
                              echo "<div id='chartdbm_" . strtr($node->name, "-", "_") . "' style='height: 240px; width: 100%'></div><br />";
                              echo "<div id='chartstp_" . strtr($node->name, "-", "_") . "' style='height: 240px; width: 100%'></div><br />";
			  ?>
  		           </td></tr>
									       
			</tbody>
			</table>
			       <?php } ?>
			
		
			<br />
			<input type="button" style="font-weight:bold;width:100%;" onclick="window.close();" name="sent" value="<?php echo $_LANG['close_window']; ?>" />
		</div>
		<div id="sidebar"></div>	
	</div>
</div>
</body>
</html>
<?php
exit;
 }



else if(isset($_GET['action']) && $_GET['action'] == "node-info" && isset($_GET['mac']) && file_exists($dir . "data/stats/" . $networkname . "/" . base64_encode($_GET['mac']) . ".ip.txt")) {


 if (isset($_GET['date'])) {
   $usedate = $_GET['date'];
 } else {
   $usedate = date('YmdHisT');
 }

 $usedatetimestampe = date_create_from_format('YmdHisT', $usedate)->format('U');

 if(isset($_GET['day'])) {$day = $_GET['day'];} else {$day = date('d', $usedatetimestampe);}
 if(isset($_GET['mon'])) {$mon = $_GET['mon'];} else {$mon = date('m', $usedatetimestampe);}
 if(isset($_GET['year'])) {$year = $_GET['year'];} else {$year = date('Y', $usedatetimestampe);}

$xmlp = simplexml_load_file($dir . "data/" . $networkname . "_nodes.xml");
$sdir = $dir . "data/stats/" . $networkname . "/" . $year . $mon . $day . "/";

foreach($xmlp->node as $node) {
	if($node->mac == $_GET['mac']) {$name = $node->name;}
}



$file = $sdir . base64_encode($_GET['mac']) . ".txt";
$i = "0";

$mac = $_GET['mac'];
$ip = str_replace(":", "", $_GET['mac']);
$ip = "5." . hexdec(substr($ip, -6, 2)) . "." . hexdec(substr($ip, -4, 2)) . "." . hexdec(substr($ip, -2));

if(file_exists($dir . "data/stats/" . $networkname . "/" . $year . $mon . $day . "/" . base64_encode($_GET['mac']) . ".txt")) {$fc = file_get_contents($file);}
else {die("<h1>This node has not checked in yet!</h1>");}

$data = explode("&", $fc);
$nodes = explode(";", $data[1]);
$rssi = explode(";", $data[2]);
$ping = $data[3];

if($ping > 10) {$color = "red";}
else if($ping > 6) {$color = "orange";}
else {$color = "green";}

$online = array(0=>0,1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0,8=>0,9=>0,10=>0,11=>0,12=>0,13=>0,14=>0,15=>0,16=>0,17=>0,18=>0,19=>0,20=>0,21=>0,22=>0,23=>0,24=>0);

if($handle = opendir($dir . "data/stats/" . $networkname . "/" . $year . $mon . $day . "/")) {
	while(false !== ($file = readdir($handle))) {
		if($file != "." && $file != ".." && strpos($file, '.usage.') !==FALSE && strpos($file, base64_encode($_GET['mac'])) !==FALSE) {
		  $hour = intval(substr($file, 0, 2));
		  $online[$hour] = 100;
		}
	}

	closedir($handle);
}

$ip = file_get_contents($dir . "data/stats/" . $networkname . "/" . base64_encode($_GET['mac']) . ".ip.txt");

$isp = gethostbyaddr($ip);
$isp = explode(".", $isp);
$i = count($isp);

if($isp[$i - 2] == "co" || $isp[$i - 2] == "com") {$isp = ucfirst($isp[$i - 3]);}
else {$isp = ucfirst($isp[$i - 2]);}

$data = implode(",", $online);


$chartrssi = "?network=" . $networkname . "&type=rssi&station=" . $_GET['mac'] . "&date=" . $usedate;
$charttxrate = "?network=" . $networkname . "&type=txrate&station=" . $_GET['mac'] . "&date=" . $usedate;
$chartrtt = "?network=" . $networkname . "&type=rtt&station=" . $_GET['mac'] . "&date=" . $usedate;
$chartrank = "?network=" . $networkname . "&type=rank&station=" . $_GET['mac'] . "&date=" . $usedate;
$charthops = "?network=" . $networkname . "&type=hops&station=" . $_GET['mac'] . "&date=" . $usedate;
$chartgwqual = "?network=" . $networkname . "&type=gw-qual&station=" . $_GET['mac'] . "&date=" . $usedate;
$chartkbupdown = "?network=" . $networkname . "&type=kbupdown&station=" . $_GET['mac'] . "&date=" . $usedate;

?>

<html>
<head>
<title><? echo $_LANG['node_info'] . ": " . $brand; ?></title>
<link rel="stylesheet" type="text/css" href="<?php echo $wdir; ?>resources/style.css" />
<link rel="shortcut icon" href="<?php echo $wdir; ?>resources/favicon.ico"/>
<!-- <script src="<?php echo $wdir; ?>resources/sorttable.js"></script> -->
<script src="<?php echo $wdir; ?>resources/js/jquery-1.6.1.min.js" type="text/javascript"></script>
<script src="<?php echo $wdir; ?>resources/js/highcharts.js" type="text/javascript"></script>
<script type="text/javascript">

var chartrssi; // global
var charttxrate; // global
var chartrtt; // global
var chartrank; // global
var charthops; // global
var chartgwqual; // global
var chartkbupdown; //global

Highcharts.setOptions({
	global: {
		useUTC: false
	}
});

 function plot_single_var(pullURL, inchart, LrenderTo,  Ltitletext, Lsubtitletext, LyAxistext) {
   inchart = new Highcharts.Chart({
        chart: {
            renderTo: LrenderTo,
            defaultSeriesType: 'line',
	    zoomType: 'x',
            events: {
	       load: function (event) {
	     $.ajax({
	       url: pullURL,
		   success: function (items) {
		   for (var i = 0; i < items.length; i++) {
		     inchart.addSeries(items[i]);
		     inchart.xAxis.tickInterval = 3600 * 1000;
		   }
		 },
		   cache: false,
		   error: function (XMLHttpRequest, textStatus, errorThrown) { alert(errorThrown); }
	       });
	   }
	 }
        },
      title: {
         text: Ltitletext
      },
      subtitle: {
         text: Lsubtitletext
      },
      xAxis: {
         type: 'datetime',
      },
      yAxis: {
         title: {
            text: LyAxistext
         },
      },
      series: []
	 });
   return inchart;
 }

 /*
      xAxis: {
         type: 'datetime',
         tickInterval:  3600 * 1000,
	    labels: {
            enabled: true,
            formatter: function() {
                return Highcharts.dateFormat('%b %d %H:%M', this.value);
            },
            rotation: -90,
            align: 'right'
        }
 */

$(document).ready(function() {
		    
    plot_single_var('overview-plot.php<?php echo $chartrssi; ?>', chartrssi, 'chart-rssi', 'RSSI', 'From Yesterday 0:00 AM to now','RSSI');  

    plot_single_var('overview-plot.php<?php echo $chartrank; ?>', chartrank, 'chart-rank', 'Rank', 'From Yesterday 0:00 AM to now', 'Rank');

    plot_single_var('overview-plot.php<?php echo $charttxrate; ?>', charttxrate, 'chart-txrate', 'Estimated TX Rate to Gateway', 'From Yesterday 0:00 AM to now', 'KB/s');

    plot_single_var('overview-plot.php<?php echo $chartrtt; ?>', chartrtt, 'chart-rtt', 'Estimated RTT', 'From Yesterday 0:00 AM to now', 'RTT');
		    
    plot_single_var('overview-plot.php<?php echo $charthops; ?>', charthops, 'chart-hops', 'Hops to Gateway', 'From Yesterday 0:00 AM to now', 'Hops');
    
    plot_single_var('overview-plot.php<?php echo $chartgwqual; ?>', chartgwqual, 'chart-gw-qual', 'Gateway Quality', 'From Yesterday 0:00 AM to now', 'GW Qual');

    plot_single_var('overview-plot.php<?php echo $chartkbupdown; ?>', chartkbupdown, 'chart-kbupdown', 'Total Data Transfered', 'From Yesterday 0:00 AM to now', 'kb');
		    
});

</script>
</head>
<body>
<div id="wrapper">
	<div id="header">
		<div id="logo"><?php echo substr($name, 0, 12); ?></div>
	</div>
	<div id="content">
		<div id="page-content">
			<table>
				<tr>
					<td style="text-align:left;font-weight:bold;">Uplink Info</td>
					<td style="text-align:left;padding-left:1em;"><b>IP:</b> <?php echo $ip; ?><br /><b>ISP:</b> <?php echo $isp; ?></td>
				</tr>
				<tr>
					<td style="text-align:left;font-weight:bold;">Ping</td>
					<td style="text-align:left;padding-left:1em;"><font color="<?php echo $color; ?>"><?php echo $ping; ?></font></td>
				</tr>
				<tr>
					<td style="text-align:left;font-weight:bold;">Uptime</td>
					<td><img src="http://chart.apis.google.com/chart?chf=bg,s,FF0000&chbh=8,0,0&chs=240x20&cht=bvs&chco=008000&chd=t:<?php echo $data; ?>" /></td>
				</tr>
				<tr>
					<td style="text-align:left;font-weight:bold;">Last check-in</td>
					<td style="text-align:left;padding-left:1em;">
						<?php
						if(file_exists($dir . "data/stats/" . $networkname . "/" . base64_encode($_GET['mac']) . ".date.txt")) {$fc = file_get_contents($dir . "data/stats/" . $networkname . "/" . base64_encode($_GET['mac']) . ".date.txt");}
						else {echo "Node has not checked in yet.";}
						$fulldatetime = date_create_from_format('YmdHisT', $fc);
						
						echo $fulldatetime->format('Y/m/d H:i e');
						// echo $fulldatetime->format('g:i a e d/m/Y'); 	
						?>
					</td>
				</tr>
			</table>
		
			<h2>Neighbors</h2>
			
			<table style="width:100%;" class="sortable">
			<thead>
				<tr>
					<th style="text-align:left;">neighbor</th>
					<th style="text-align:left;">IP</th>
					<th style="text-align:left;">MAC</th>
					<th style="text-align:left;">rssi</th>
				</tr>
			</thead>
			<tbody>
			<?php
			$idxcnt = 0;
			foreach($nodes as $node_data) {
				$i = $idxcnt;
				if (strlen($node_data) == 0) { 
					$idxcnt += 1;
					continue; 
				}
				else {
					foreach($xmlp->node as $node) {
						$norm_ip = $node->ip;
						
						//$ubnt_ip_array = explode(".", $node->ip);
						//$ubnt_ip_array[1] = $ubnt_ip_array[1] + 1;
						//$ubnt_ip = implode(".", $ubnt_ip_array);
						$ubnt_ip = $node->ip;

						if($norm_ip == $node_data) {
							$name = $node->name;
							$mac = $node->mac;
						}
						else if($ubnt_ip == $node_data) {
							// For the ubnt_ip to be valid, and not just another node with a different ip
							// we must check that the mac is a Ubiquiti device, otherwise, we have not
							// found the correct device.
							
							if(substr(str_replace(":", "", $node->mac), 0, 6) == "00156D" || substr(str_replace(":", "", $node->mac), 0, 6) == "002722") {
								// we have a valid Ubiquiti device, so we set the details
							
								$name = $node->name;
								$mac = $node->mac;
							}
							else {
								// we dont have a valid Ubiquiti device, so we dont set the details
							}
						}
					}
					
					if($rssi[$i] > 19) {$color = "green";}
					else if($rssi[$i] > 9) {$color = "orange";}
					else {$color = "red";}
					
					echo "<tr style=\"border:1px gray solid;\">";
					echo "<td style=\"text-align:left;font-weight:bold;\"><a href=\"?id=" . $networkname . "&action=node-info&mac=" . $mac . "\">" . $name . "</a></td>";
					echo "<td style=\"text-align:left;\">" . $nodes[$i] . "</td>";
					echo "<td style=\"text-align:left;\">" . $mac . "</td>";
					echo "<td style=\"text-align:left;\"><font color=\"" . $color ."\">" . $rssi[$i] . "</font></td>";
					echo "</tr>\n";
					
					$i = $i + 1;
					
					$name = "";
					$mac = "";
					$color = "";
				}
				$idxcnt += 1;
			}
			?>
			</tbody>
			</table>
<?php
			if (isset($_GET['date'])) {
	      $usedate = $_GET['date'];
	   } else {
	     $usedate = date('YmdHisT');
	   }
          	   $prevdate = date_create_from_format('YmdHisT', $usedate);
          	   $nextdate = date_create_from_format('YmdHisT', $usedate);
                        $prevdate->sub(date_interval_create_from_date_string('1 day'));
                        $nextdate->add(date_interval_create_from_date_string('1 day'));
			echo "<a href='overview.php?id=" . $networkname . "&action=node-info&mac=" . $_GET['mac'] . "&date=" . $prevdate->format('YmdHisT') . "'>&lt;--Prev day</a><br />";
			echo "NOW == " . date_create_from_format('YmdHisT', $usedate)->format('Y-m-d') . "<br />";
			echo "<a href='overview.php?id=" . $networkname . "&action=node-info&mac=" . $_GET['mac'] . "&date=" . $nextdate->format('YmdHisT') . "'>&nbsp;&nbsp;&nbsp; Next day --&gt;</a><br />";

?>
			<br />
			<div id="chart-txrate" style="height: 240px; width: 100%"></div>
			<br />
			<div id="chart-kbupdown" style="height: 240px; width: 100%"></div>
			<br />
			<div id="chart-rtt" style="height: 240px; width: 100%"></div>
			<br />
			<div id="chart-rssi" style="height: 480px; width: 100%"></div>
			<br />
			<div id="chart-rank" style="height: 480px; width: 100%"></div>
			<br />
			<div id="chart-hops" style="height: 240px; width: 100%"></div>
			<br />
			<div id="chart-gw-qual" style="height: 240px; width: 100%"></div>
			<br />
			<input type="button" style="font-weight:bold;width:100%;" onclick="window.close();" name="sent" value="Close Window" />
		</div>
		<div id="sidebar"></div>	
	</div>
</div>
</body>
</html>

<?php
exit;
}




else if(isset($_GET['action']) && $_GET['action'] == "maps") {
	$sxml = simplexml_load_file($dir . "data/" . $networkname . "_nodes.xml");

	if(strlen($xmlp->robindash->location) == "0" || strlen($xmlp->robindash->location) == "3") {
		if(strlen(substr($sxml->node[0]->lat, 0, 9)) > 0 || strlen(substr($sxml->node[0]->lng, 0, 9)) > 0) {
			$lat = substr($sxml->node[0]->lat, 0, 9);
			$lng = substr($sxml->node[0]->lng, 0, 9);
		}
		else {
			$lat = "-180";
			$lng = "0";
		}
	}
	else {
		$location = explode(",", $xmlp->robindash->location);
		
		$lat = $location[0];
		$lng = $location[1];
	}
?>
<html>
<head>
<title><?php echo "Map Overview: " . $brand; ?></title>
<link rel="shortcut icon" href="<?php echo $wdir; ?>resources/favicon.ico"/>
</head>
<body>
<div id="map" style="height:100%;width:100%;"></div>

<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script type="text/javascript">
var osmMapType = new google.maps.ImageMapType({
	getTileUrl: function(coord, zoom) {return "http://tile.openstreetmap.org/" + zoom + "/" + coord.x + "/" + coord.y + ".png";},
	tileSize: new google.maps.Size(256, 256),
	isPng: true,
	alt: "OSM",
	name: "OpenStreet Map",
	maxZoom: 18
});

function initialize() {
	function drawlines(lat, lng, lat2, lng2, rssi) {
		if(rssi > 17) {var colour = "#1FBB29";}
		else if(rssi > 10) {var colour = "#F39C04";}
		else {var colour = "#E01D49";}
		
		var coords = [
			new google.maps.LatLng(lat, lng),
			new google.maps.LatLng(lat2, lng2)
		];
		var line = new google.maps.Polyline({
			path: coords,
			strokeColor: colour,
			strokeOpacity: 1.0,
			strokeWeight: 2
		});
		line.setMap(map);
	}

	var options = {
		zoom: 15,
		center: new google.maps.LatLng(<?php echo $lat . ", " . $lng; ?>),
		mapTypeControlOptions: {mapTypeIds: ['OSM', google.maps.MapTypeId.ROADMAP, google.maps.MapTypeId.SATELLITE, google.maps.MapTypeId.TERRAIN]},
		mapTypeId: google.maps.MapTypeId.ROADMAP
	}

	var map = new google.maps.Map(document.getElementById("map"), options);
	setMarkers(map, nodes);
	map.mapTypes.set('OSM', osmMapType);
	
	<?php
	// Start processing the node lines
	// nodes=5.164.59.50;5.164.78.104&rssi=46;33

	$node_data = array();
	$sxml = simplexml_load_file($dir . "data/" . $networkname . "_nodes.xml");

	foreach($sxml->node as $node) {
		$ip = base64_encode($node->ip);
		$mac = base64_encode($node->mac);

		$node_data[$ip]['lat'] = $node->lat;
		$node_data[$ip]['lng'] = $node->lng;

		$node_data[$mac]['lat'] = $node->lat;
		$node_data[$mac]['lng'] = $node->lng;
	}


	if ($dh = opendir($dir . "data/stats/" . $networkname . "/")) {
		while (($file = readdir($dh)) !== false) {
			if (strpos($file, ".txt") !== FALSE && !(strpos($file, '.ip.') !==FALSE || strpos($file, '.date.') !==FALSE)) {
				// We have our data..
				
				$data = explode("&", file_get_contents($dir . "data/stats/" . $networkname . "/" . $file));
				$nodes = "";
				$rssi = "";
				
				//$this_mac = base64_decode(str_replace(".txt", "", $file));
				//$this_mac = explode(":", $this_mac);
				//$this_mac[5] = dechex(hexdec($this_mac[5])-1);
				//$this_mac = implode(":", $this_mac);
				//$this_mac = base64_encode(strtoupper($this_mac));
				$this_mac = str_replace(".txt", "", $file);				

				foreach($data as $dataitem) {
					if(strpos($dataitem, 'nodes=') !==FALSE) {$nodes = str_replace("nodes=", "", $dataitem);}
					else if(strpos($dataitem, 'rssi=') !==FALSE) {$rssi = str_replace("rssi=", "", $dataitem);}
					else {echo "";}
				}
				
				$nodes = explode(";", $nodes);
				$rssi = explode(";", $rssi);
				$i = 0;
				
				foreach($nodes as $node) {
					$id = base64_encode($node);
					
					$lat = $node_data[$this_mac]['lat'];
					$lng = $node_data[$this_mac]['lng'];
					
					$lat2 = $node_data[$id]['lat'];
					$lng2 = $node_data[$id]['lng'];
										
					if($lat == "" || $lng == "" || $lat2 == "" || $lng2 == "") {echo "";}
					else {echo "drawlines(" . $lat . ", " . $lng . ", " . $lat2 . ", " . $lng2 . ", " . $rssi[$i] . ");\n";}

					$i = $i + 1;
				}
			}
		}
		closedir($dh);
	}

echo "}\n\n";

// Start processing the node icons

$minx=90;
$maxx=-90;
$miny=360;
$maxy=-360;

echo "var nodes = [";
foreach($sxml->node as $item) {
	if(file_exists($dir . "data/stats/" . $networkname . "/" . base64_encode($item->mac) . ".date.txt")) {
		$ts = file_get_contents($dir . "data/stats/" . $networkname . "/" . base64_encode($item->mac) . ".date.txt");

		$fulldatetime = date_create_from_format('YmdHisT', $ts);
		$nowdatetime = date_create();
		$datediff = $nowdatetime->diff($fulldatetime);

		if ($datediff->y == 0 && $datediff->m == 0 && $datediff->d == 0 && $datediff->h == 0 && $datediff->i <= 59) {
			$status = "online";
		} else {
			$status = "offline";
		}
		
		if(file_exists($dir . "data/role/" . base64_encode($item->mac) . ".txt")) {
			if(file_get_contents($dir . "data/role/" . base64_encode($item->mac) . ".txt") == "G") {$role = "g";}
			else {$role = "r";}
		}
		else {$role = "r";}
		
		if($item->name == "") {echo "";}
		else if($item->lat == "") {echo "";}
		else if($item->lng == "") {echo "";}
		else if(!$status) {echo "";}
		else {
			echo "['" . $item->name . "', " . $item->lat . ", " . $item->lng . ", " . rand(1, 100000) . ", \"" . $status . $role . "\"],";
			
			$lat = $item->lat;
			$lng = $item->lng;
			
			if ($lat < $minx) {$minx = $lat;}
			if ($lat > $maxx) {$maxx = $lat;}
			if ($lng < $miny) {$miny = $lng;}
			if ($lng > $maxy) {$maxy = $lng;}
		}
	}
}

echo "];\n\n";
?>

function setMarkers(map, locations) {
	var onliner = new google.maps.MarkerImage('<?php echo $wdir; ?>resources/onliner.png',
		new google.maps.Size(24, 24),
		new google.maps.Point(0,0),
		new google.maps.Point(0, 24)
	);

	var onlineg = new google.maps.MarkerImage('<?php echo $wdir; ?>resources/onlineg.png',
		new google.maps.Size(24, 24),
		new google.maps.Point(0,0),
		new google.maps.Point(0, 24)
	);
	
	var offline = new google.maps.MarkerImage('<?php echo $wdir; ?>resources/offline.png',
		new google.maps.Size(24, 24),
		new google.maps.Point(0,0),
		new google.maps.Point(0, 24)
	);

  for (var i = 0; i < locations.length; i++) {
	var node = locations[i];
	var latlng = new google.maps.LatLng(node[1], node[2]);

	if(node[4] == "offline") {
		var marker = new google.maps.Marker({
			position: latlng,
			map: map,
			icon: offline,
			title: node[0],
			zIndex: node[3]
		});
	}
	else if(node[4] == "onliner") {
		var marker = new google.maps.Marker({
			position: latlng,
			map: map,
			icon: onliner,
			title: node[0],
			zIndex: node[3]
		});
	}
	else if(node[4] == "onlineg") {
		var marker = new google.maps.Marker({
			position: latlng,
			map: map,
			icon: onlineg,
			title: node[0],
			zIndex: node[3]
		});
	}
  }
}

window.onload = function(){
	initialize();
	
	//var rectBounds = new GLatLngBounds(new GLatLng(<?php echo $miny; ?>, <?php echo $minx; ?>), new GLatLng(<?php echo $maxy; ?>, <?php echo $maxx; ?>));
	//map.setCenter(rectBounds.getCenter(), map.getBoundsZoomLevel(rectBounds));
};
</script>
</body>
</html>
<?php
exit;
}
?>
<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Network Overview: <?php echo $brand; ?></title>
<link rel="stylesheet" type="text/css" href="<?php echo $wdir; ?>resources/style.css" />
<link rel="shortcut icon" href="<?php echo $wdir; ?>resources/favicon.ico"/>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
</head>
<body>
<div id="wrapper">
	<div id="header">
		<div id="logo"><?php echo ucwords($networkname); ?></div>
	</div>
<div id="content">
	<div id="page-content">
		<div id="main">
			<?php
			if(isset($_GET['id'])) {echo "<input type='button' onclick=\"window.open('" . $wdir . "overview.php?id=" . $networkname . "&amp;action=clients', '" . rand(1, 9999999) . "', 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,width=1024,height=768');\" value='Clients'>&nbsp;<input type='button' onclick=\"window.open('" . $wdir . "overview.php?id=" . $networkname . "&amp;action=maps', '" . rand(1, 9999999) . "', 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,width=1024,height=768');\" value='Map'>&nbsp;<input type='button' onclick='window.location = \"" . $wdir . "?id=" . $networkname . "&action=download&format=csv\";' value='Download CSV'>&nbsp;<input type='button' onclick='window.location = \"" . $wdir . "?id=" . $networkname . "\";' value='Login'>";}
			else { ?><input type='button' onclick='window.location = "<?php echo $wdir; ?>edit.php";' value='Edit Network'>&nbsp;<input type='button' onclick="window.open('<?php echo $wdir; ?>overview.php?id=<?php echo $networkname; ?>&action=clients', '<?php echo rand(1, 9999999); ?>', 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,width=1024,height=768');" value='Clients'>&nbsp;<input type='button' onclick="window.open('<?php echo $wdir; ?>overview.php?id=<?php echo $networkname; ?>&amp;action=maps', '<?php echo rand(1, 9999999); ?>', 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,width=1024,height=768');" value='Map'>&nbsp;<input type='button' onclick='window.location = "<?php echo $wdir; ?>logout.php";' value='Logout'><?php }
			
			// Graph making begins here...
			$users = array();
			$kbdown = array();
			$kbup = array();
			$done = array();
			
			$datedir = date('Ymd');
			
			if($handle = opendir($dir . "data/stats/" . $networkname . "/" . $datedir . "/")) {
				while(false !== ($file = readdir($handle))) {
					if($file != "." && $file != ".." && strpos($file, '.usage.') !==FALSE) {
						$fc = file_get_contents($dir . "data/stats/" . $networkname . "/" . $datedir . "/" . $file);
						$fctwo = explode("&", $fc);
						$user = explode("+", urldecode($fctwo[1]));
						
						if(strpos(substr($file, 2, 2), '-') !==FALSE) {$hour = substr($file, 2, 1);}
						else {$hour = substr($file, 2, 2);}

						foreach($user as $thatuser) {
							$data = explode(",", $thatuser);

							$thekbdown = round($data[1] / 1024, 0);
							$thekbup = round($data[2] / 1024, 0);
							$themac = $data[3];
							
							if($done[$hour][$themac] == 1) {echo "";}
							else {
								$kbdown[$hour] = $kbdown[$hour] + $thekbdown;
								$kbup[$hour] = $kbup[$hour] + $thekbup;
								
								$done[$hour][$themac] = 1;
							}
						}

						$users[$hour] = $fctwo[0];
					}
				}

				closedir($handle);
			}

			if($users[0] == "") {$users[0] = 0;$kbdown[0] = 0;$kbup[0] = 0;}
			if($users[1] == "") {$users[1] = 0;$kbdown[1] = 0;$kbup[1] = 0;}
			if($users[2] == "") {$users[2] = 0;$kbdown[2] = 0;$kbup[2] = 0;}
			if($users[3] == "") {$users[3] = 0;$kbdown[3] = 0;$kbup[3] = 0;}
			if($users[4] == "") {$users[4] = 0;$kbdown[4] = 0;$kbup[4] = 0;}
			if($users[5] == "") {$users[5] = 0;$kbdown[5] = 0;$kbup[5] = 0;}
			if($users[6] == "") {$users[6] = 0;$kbdown[6] = 0;$kbup[6] = 0;}
			if($users[7] == "") {$users[7] = 0;$kbdown[7] = 0;$kbup[7] = 0;}
			if($users[8] == "") {$users[8] = 0;$kbdown[8] = 0;$kbup[8] = 0;}
			if($users[9] == "") {$users[9] = 0;$kbdown[9] = 0;$kbup[9] = 0;}
			if($users[10] == "") {$users[10] = 0;$kbdown[10] = 0;$kbup[10] = 0;}
			if($users[11] == "") {$users[11] = 0;$kbdown[11] = 0;$kbup[11] = 0;}
			if($users[12] == "") {$users[12] = 0;$kbdown[12] = 0;$kbup[12] = 0;}
			if($users[13] == "") {$users[13] = 0;$kbdown[13] = 0;$kbup[13] = 0;}
			if($users[14] == "") {$users[14] = 0;$kbdown[14] = 0;$kbup[14] = 0;}
			if($users[15] == "") {$users[15] = 0;$kbdown[15] = 0;$kbup[15] = 0;}
			if($users[16] == "") {$users[16] = 0;$kbdown[16] = 0;$kbup[16] = 0;}
			if($users[17] == "") {$users[17] = 0;$kbdown[17] = 0;$kbup[17] = 0;}
			if($users[18] == "") {$users[18] = 0;$kbdown[18] = 0;$kbup[18] = 0;}
			if($users[19] == "") {$users[19] = 0;$kbdown[19] = 0;$kbup[19] = 0;}
			if($users[20] == "") {$users[20] = 0;$kbdown[20] = 0;$kbup[20] = 0;}
			if($users[21] == "") {$users[21] = 0;$kbdown[21] = 0;$kbup[21] = 0;}
			if($users[22] == "") {$users[22] = 0;$kbdown[22] = 0;$kbup[22] = 0;}
			if($users[23] == "") {$users[23] = 0;$kbdown[23] = 0;$kbup[23] = 0;}
			if($users[24] == "") {$users[24] = 0;$kbdown[24] = 0;$kbup[24] = 0;}
			
			$total_users = 0;
			$total_usage = 0;
			
			$biggest_users = 0;
			$biggest_kbdown = 0;
			$biggest_kbup = 0;
			
			foreach($users as $item_users) {
				$biggest_users = $biggest_users + $item_users;
				$total_users = $total_users + $item_users;
			}
			
			foreach($kbdown as $item_kbdown) {
				$biggest_kbdown = $biggest_kbdown + $item_kbdown;
				$total_usage = $total_usage + $item_kbdown;
			}
			
			foreach($kbup as $item_kbup) {
				$biggest_kbup = $biggest_kbup + $item_kbup;
				$total_usage = $total_usage + $item_kbup;
			}
			
			$biggest_total = $biggest_kbdown + $biggest_kbup * 1.2;
			
			$data = $users[0] . "," . $users[1] . "," . $users[2] . "," . $users[3] . "," . $users[4] . "," . $users[5] . "," . $users[6] . "," . $users[7] . "," . $users[8] . "," . $users[9] . "," . $users[10] . "," . $users[11] . "," . $users[12] . "," . $users[13] . "," . $users[14] . "," . $users[15] . "," . $users[16] . "," . $users[17] . "," . $users[18] . "," . $users[19] . "," . $users[20] . "," . $users[21] . "," . $users[22] . "," . $users[23] . "," . $users[24];
			$data = $data . "|";
			$data = $data . $kbdown[0] . "," . $kbdown[1] . "," . $kbdown[2] . "," . $kbdown[3] . "," . $kbdown[4] . "," . $kbdown[5] . "," . $kbdown[6] . "," . $kbdown[7] . "," . $kbdown[8] . "," . $kbdown[9] . "," . $kbdown[10] . "," . $kbdown[11] . "," . $kbdown[12] . "," . $kbdown[13] . "," . $kbdown[14] . "," . $kbdown[15] . "," . $kbdown[16] . "," . $kbdown[17] . "," . $kbdown[18] . "," . $kbdown[19] . "," . $kbdown[20] . "," . $kbdown[21] . "," . $kbdown[22] . "," . $kbdown[23] . "," . $kbdown[24];
			$data = $data . "|";
			$data = $data . $kbup[0] . "," . $kbup[1] . "," . $kbup[2] . "," . $kbup[3] . "," . $kbup[4] . "," . $kbup[5] . "," . $kbup[6] . "," . $kbup[7] . "," . $kbup[8] . "," . $kbup[9] . "," . $kbup[10] . "," . $kbup[11] . "," . $kbup[12] . "," . $kbup[13] . "," . $kbup[14] . "," . $kbup[15] . "," . $kbup[16] . "," . $kbup[17] . "," . $kbup[18] . "," . $kbup[19] . "," . $kbup[20] . "," . $kbup[21] . "," . $kbup[22] . "," . $kbup[23] . "," . $kbup[24];
			
			// We are now done constructing the graphs,
			// lets get them, and show them for all to see:
			?>
			<center><img src="http://chart.apis.google.com/chart?chxr=0,1,24|1,0,<?php echo round($biggest_total, 0); ?>|2,0,<?php echo round($biggest_users, 0); ?>&chxt=x,y,r&chs=800x90&cht=lc&chco=FF0000,00FF00,3072F3&chd=t:<?php echo $data; ?>&chdl=Users|Download+(MB)|Upload+++++(MB)&chg=14.3,-1,0,0&chls=2|2|2&chm=B,C5D4B5BB,0,0,0&chtt=Usage+over+the+past+24+hours+(<?php echo $total_users; ?>+clients+transferred+<?php echo $total_usage; ?>+MB)" height="90" alt="Usage over the past 24 hours (<?php echo $total_users; ?> clients transferred <?php echo $total_usage; ?> MB)" /></center>
			<table style="height:100%;width:100%;" class="sortable">
			<thead>
				<tr>
					<th>&nbsp;</th>
					<th><?php echo $_LANG['name']; ?></th>
					<th><?php echo $_LANG['ip']; ?><br /><?php echo $_LANG['mac']; ?></th>
					<th><?php echo $_LANG['users']; ?></th>
					<th><?php echo $_LANG['usage']; ?><br />&darr;/&uarr; <small>(<?php echo $_LANG['mb']; ?>)</small></th>
					<th><?php echo $_LANG['uptime']; ?><br /><?php echo $_LANG['lastcheckin']; ?></th>
					<th><?php echo $_LANG['txrate']; ?><br /><?php echo $_LANG['rtt']; ?></th>
					<th><?php echo $_LANG['version']; ?></th>
					<th><?php echo $_LANG['load']; ?><br /><?php echo $_LANG['memfree']; ?></th>
					<th><?php echo $_LANG['gatewayip']; ?><br /><?php echo $_LANG['ping']; ?></th>
					<th><?php echo $_LANG['route']; ?></th>
					<th><?php echo $_LANG['hops']; ?></th>
					<th><?php echo $_LANG['neigh']; ?><br /><?php echo $_LANG['rssi']; ?></th>
				</tr>
			</thead>
			<tbody>

			<?php
			$xml = simplexml_load_file($dir . "data/" . $networkname . "_nodes.xml");

			foreach($xml->node as $node) {
			if(file_exists($dir . "data/stats/" . $networkname . "/" . base64_encode($node->mac) . ".txt") && file_exists($dir . "data/stats/" . $networkname . "/" . base64_encode($node->mac) . ".date.txt")) {
			$array = explode("&", file_get_contents($dir . "data/stats/" . $networkname . "/" . base64_encode($node->mac) . ".txt"));
			sort($array);
			$ts = file_get_contents($dir . "data/stats/" . $networkname . "/" . base64_encode($node->mac) . ".date.txt");

			$fulldatetime = date_create_from_format('YmdHisT', $ts);
			$nowdatetime = date_create();
			$datediff = $nowdatetime->diff($fulldatetime);

			foreach($array as $item) {
				if(strpos($item, 'sta_') !==FALSE) {echo "";}
				else if(strpos($item, 'ip=') !==FALSE) {$ip = str_replace("ip=", "", $item);}
				else if(strpos($item, 'mac=') !==FALSE) {$mac = str_replace("mac=", "", $item);}
				else if(strpos($item, 'users=') !==FALSE) {
					// Some strange things happen here, so we try and isolate them
					
					$users = str_replace("users=", "", $item);
					
					if(is_array($users)) {$users = "0";}
					else {echo "";}
				}
				else if(strpos($item, 'kbdown=') !==FALSE) {$kbdown = round(str_replace("kbdown=", "", $item) / 1024, 2);}
				else if(strpos($item, 'kbup=') !==FALSE) {$kbup = round(str_replace("kbup=", "", $item) / 1024, 2);}
				else if(strpos($item, 'uptime=') !==FALSE) {$uptime = str_replace("uptime=", "", str_replace("%", "/", str_replace("D", "", str_replace("-", "", $item))));}
				else if(strpos($item, 'NTR=') !==FALSE) {$ntr = str_replace("-", "", str_replace("NTR=", "", $item));if($ntr == "0" || strlen($ntr) < 1) {$ntr = "n/a";}}
				else if(strpos($item, 'robin=') !==FALSE) {$robin = str_replace("robin=", "", $item);}
				else if(strpos($item, 'batman=') !==FALSE) {$batman = str_replace("batman=", "", $item);}
				else if(strpos($item, 'load=') !==FALSE) {$load = str_replace("load=", "", $item);}
				else if(strpos($item, 'memfree=') !==FALSE) {$memfree = str_replace("memfree=", "", $item);}
				else if(strpos($item, 'gateway=') !==FALSE) {$gateway = str_replace("gateway=", "", $item);}
				else if(strpos($item, 'hops=') !==FALSE) {$hops = str_replace("hops=", "", $item);}
				else if(strpos($item, 'nodes=') !==FALSE) {$nodes = str_replace("nodes=", "", $item);}
				else if(strpos($item, 'rssi=') !==FALSE) {$rssi = str_replace("rssi=", "", $item);}
				else if(strpos($item, 'RTT=') !==FALSE) {$rtt = str_replace("RTT=", "", $item);}
				else if(strpos($item, 'routes=') !==FALSE) {$route = str_replace("routes=", "", $item);}
				else {echo "";}
			}
			
			//if(isset($load)) {$expload = explode(",", $load);}	// expload is not a typo, rather a note to self
			//else {exit;}

			//$nums = $expload[0] + $expload[1] + $expload[2];
			//$load = round($nums / 3, 2);
			/* get the last checking date */
			if(file_exists($dir . "data/stats/" . $networkname . "/" . base64_encode($mac) . ".date.txt")) {$fc = file_get_contents($dir . "data/stats/" . $networkname . "/" . base64_encode($mac) . ".date.txt");}
			else {echo "Node has not checked in yet.";}
			$lastcheckindatetime = date_create_from_format('YmdHisT', $fc);

			$xmlp = simplexml_load_file($dir . "data/" . $networkname . "_nodes.xml");

			foreach($xmlp->node as $node) {
				if($node->mac == $mac) {$name = $node->name;}
			}

			if(file_exists($dir . "data/role/" . base64_encode($mac) . ".txt")) {$status = file_get_contents($dir . "data/role/" . base64_encode($mac) . ".txt");}
			else {$status = "R";}

			if($status == "G") {$statusid = "gateway";$hops = "0";$ntr = "100 MB/s";}
			else {$statusid = "repeater";}

			if ($datediff->y != 0 || $datediff->m != 0 && $datediff->d != 0 && $datediff->h >= 1) {
				$status = "O";
				$statusid = "offline";
			}

			if($statusid == "offline") {
				
				//$uptime = "<font color=\"red\"><b>Down!</b></font><br />" . $fulldatetime->format('d/m/Y') . "<br />" . $fulldatetime->format('g:i a e');
				$uptime = "<font color=\"red\"><b>Down!</b></font><br />" . $fulldatetime->format('Y/m/d') . "<br />" . $fulldatetime->format('H:i e');
			}
			
			$nodes = explode(";", $nodes);
			$rssi = explode(";", $rssi);
			
			echo "<tr style=\"border:1px gray solid;font-size:80%;\">";
			echo "<td class=\"" . $statusid ."\">" . $status . "</td>";

			if(file_exists($dir . "data/stats/" . $networkname . "/" . date('Ymd') . "/" . base64_encode($mac) . ".txt")) {
				if($statusid == "offline") {echo "<td style=\"text-align:left;padding:10px;\">" . $name . "</td>";}
				else {echo "<td style=\"text-align:left;padding:10px;\"><a href=\"#\" onclick=\"window.open('" . $wdir . "overview.php?id=" . $networkname . "&action=node-info&mac=" . $mac . "', '" . rand(1, 9999999) . "', 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,width=800,height=800');\">" . $name . "</a></td>";}
			}
			else {echo "<td style=\"text-align:left;padding:10px;\">" . $name . "</td>";}

			echo "<td style=\"text-align:left;\">" . $ip . "<br />" . $mac . "</td>";
			echo "<td>" . $users . "</td>";
			echo "<td>" . $kbdown . "<br />" . $kbup . "</td>";
			echo "<td>" . $uptime . "<br />". $lastcheckindatetime->format('Y/m/d H:i e') . "</td>";
			echo "<td>" . $ntr . "<br />" . $rtt . "</td>";
			echo "<td>" . $robin . "<br />" . $batman . "</td>";
			echo "<td>" . $load . "<br />" . $memfree . "</td>";
			echo "<td>" . $gateway . "</td>";
			echo "<td>" . $route . "</td>";
			echo "<td>" . $hops . "</td>";
			echo "<td>";
			for ($i = 0; $i < sizeof($node); $i++ ) {
				if (strlen($nodes[$i]) == 0) {
					continue;
				}
				echo $nodes[$i] . " : " . $rssi[$i] . "<br />";
			}
			echo "</td>";
			echo "</tr>\n";

			$array[19] == null;
			$hascontent = true;
			}
			else {echo "";}
			}
			?>

			</tbody>
			</table>
			<?php
			if(!isset($hascontent)) {echo "<br /><br /><center><b>" . $_LANG['error_nodes_checkedin'] . "</b></center>";}
			else {echo "";}
			$nowtime = new DateTime();
			echo "<br /><center>Now: " . $nowtime->format('Y/m/d H:i e') . "</center>";
			?>
		</div>
	</div>
</div>

<script type="text/javascript" src="<?php echo $wdir; ?>resources/sorttable.js"></script>
<?php echo $tracker; ?>
</body>
</html>
