<?php

// Set the JSON header
header("Content-type: text/json");

if(file_exists("settings.php")) {require("settings.php");}
else {header("Location: oobe.php");exit;}


function parse_query_string($qstr) {
  $res = array();
  $ands = explode('&', $qstr);
  foreach($ands as $elm) {
    $kvp = explode('=', $elm);
    if (sizeof($kvp >= 2)) {
      $res[$kvp[0]] = $kvp[1];
    }
  }
  return $res;
}


function get_single_param($data, $param)
{
  $res = array(0=>array());
  $res[0]['name'] = $param;
  $res[0]['data'] = array();
  foreach($data as $checkin) {
    if ($checkin[$param] == "") {
      continue;
    }
    $val = intval(trim($checkin[$param]));
    $thedate = date_create_from_format('YmdHisT', trim($checkin['datetime']));
    $datestr = $thedate->format('D, d M Y H:i:s'); 
    $res[0]['data'][] = array($datestr, $val);
  }
  return $res;
}


function get_txrate($data) {
  
  $res = array(0=>array());
  $res[0]['name'] = "TX Rate";
  $res[0]['data'] = array();
  foreach($data as $checkin) {
    if ($checkin['NTR'] == "") {
      continue;
    }
    $ratearr = explode('-', trim($checkin['NTR']));
    $txrate = intval($ratearr[0]);
    if ($ratearr[1] == "MB/s") {
      $txrate = $txrate * 1024;
    }
    $thedate = date_create_from_format('YmdHisT', trim($checkin['datetime']));
    $datestr = $thedate->format('D, d M Y H:i:s'); 
    $res[0]['data'][] = array($datestr, $txrate);
  }
  return $res;

}

function get_name_val_pair($data, $name, $val) {

  $res = array();
  
  foreach($data as $checkin) {
    $nodes = explode(';', $checkin[$name]);
    $vals = explode(';', $checkin[$val]);
    for ($i = 0; $i < sizeof($nodes); $i++) {
      if ($nodes[$i] == "") {
	continue;
      }
      if (!isset($checkin['datetime'])) {
	continue;
      }
      $res[$nodes[$i]]['name'] = $nodes[$i];
      $thedate = date_create_from_format('YmdHisT', trim($checkin['datetime']));
      $datestr = $thedate->format('D, d M Y H:i:s'); 
      $res[$nodes[$i]]['data'][] = array($datestr, intval(trim($vals[$i])));
    }
  }

  $retobj = array();
  $count = 0;
  foreach ($res as $item) {
    $retobj[] = $item;
    $count += 1;
  }
  return $retobj;




}




// setup input
$ret = array();
$network = "";
$station = "";
$date = "";


if (isset($_GET['network'])) {
  $network = $_GET['network'];
} else {
  $network = $_SESSION['user'];
}
if (!isset($_GET['station']) || ! isset($_GET['date'])) {
  print "FAIL!";
  echo json_encode($ret);
  return;
}

// get all the past two days of data
$nowdate = date_create_from_format('YmdHisT', $_GET['date']);
$yesterday = date_create_from_format('YmdHisT', $_GET['date']);
$yesterday = $yesterday->sub(date_interval_create_from_date_string('1 day'));

$allcheckindata = array();

if (file_exists($dir . "data/stats/" . $network . "/" . $yesterday->format('Ymd') . "/" . base64_encode($_GET['station']) . ".allcheckins.txt")) {
  $lines = file($dir . "data/stats/" . $network . "/" . $yesterday->format('Ymd') . "/" . base64_encode($_GET['station']) . ".allcheckins.txt");
  foreach($lines as $qstr) {
    $qres = parse_query_string($qstr);
    $allcheckindata[$qres['datetime']] = $qres;
  }

}

if (file_exists($dir . "data/stats/" . $network . "/" . $nowdate->format('Ymd') . "/" . base64_encode($_GET['station']) . ".allcheckins.txt")) {
  $lines = file($dir . "data/stats/" . $network . "/" . $nowdate->format('Ymd') . "/" . base64_encode($_GET['station']) . ".allcheckins.txt");
  foreach($lines as $qstr) {
    $qres = parse_query_string($qstr);
    $allcheckindata[$qres['datetime']] = $qres;
  }
}

// sort by date!
ksort($allcheckindata);

//print_r($allcheckindata);

// switch on type of plot
switch ($_GET["type"]) {

 case "rssi":

   $ret = get_name_val_pair($allcheckindata, "nodes", "rssi");
   break;

 case "txrate":

   $ret = get_txrate($allcheckindata);
   break;

 case "rtt":
   
   $ret = get_single_param($allcheckindata, "RTT");
   break;

 case "hops":
   
   $ret = get_single_param($allcheckindata, "hops");
   break;

 case "rank":
   
   $ret = get_name_val_pair($allcheckindata, "nbs", "rank");

 default:

   break;

}

//print_r($ret);

echo json_encode($ret);
/*
$outstr =  "";
$outstr .= "[" . "\n";
foreach ($ret as $ds) {
  $outstr .= "{" . "\n";
  $outstr .= "  \"name\": \"" . $ds['name'] ."\",\n";
  $outstr .= "  \"data\": [\n";
  foreach ($ds['data'] as $item) {
    $thedate = $item[0];
    $theres = $item[1];
    //$outstr .= "    [Date(" . $thedate->format('Y, m, d, H, i, s') . "), " . $theres . "],\n";
    $outstr .= "    [" . $thedate->format('"D, d M Y H:i:s"') . ", " . $theres . "],\n";
  }
  $outstr .= "  ]},\n";
}
$outstr .= "]";
*/
print $outstr;

//print "<br>\n";
//print json_encode($oustr);
?>
