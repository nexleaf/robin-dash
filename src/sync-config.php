<?php



if (file_exists("settings.php")) {
	require("settings.php");
}
else {
	die("# We need to setup the server first");
}

function auth_user($xmlp) {
	if(isset($_SESSION['user']) && isset($_SESSION['pass']) && file_exists($dir . "data/" . $_SESSION['user'] . ".xml")) {
		// $xmlp = simplexml_load_file($dir . "data/" . $_SESSION['user'] . ".xml");
	        if($_SESSION['pass'] != $xmlp->robindash->password) {
			print "# Invalid session\n";
			die("# Invalid session\n");
	        }
	}
}

function verify_localhost() {
	$ip = $_SERVER['REMOTE_ADDR'];
	//print $ip;
	if ($ip != "localhost" || $ip != "127.0.0.1" || $ip != "127.0.1.1") {
	//	die("Sync only allowed from localhost!");
	}
}

function show_config() {
	$fc = file_get_contents($dir . "data/" . $_SESSION['user'] . ".xml");	
	print $fc;
}

function store_config() {

	//$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
	$uploadfile = $dir . "data/" . $_SESSION['user'] . ".xml";

	// TODO: Verify remote file has newer version that local file

	if (move_uploaded_file($_FILES['conffile']['tmp_name'], $uploadfile)) {
		echo "File is valid, and was successfully uploaded.\n";
	} else {
		echo "Possible file upload attack!\n";
	}

}


function do_login($xmlp, $network) {

	global $sn;
	global $wdir;
	
	// Figure out the login url
	$loginurl = "";
	if (stripos($xmlp->robindash->configsync, "https") === false) {
		$loginurl = "http://" . $sn . $wdir; 
	} else {
		$loginurl = "https://" . $sn . $wdir; 
	}

	if (!isset($_GET['usepass'])) {
		die("# Must set usepass GET parameter");
	}

	// Do the login
	$post_data = array();
	$post_data['user'] = $network;
	$post_data['pass'] = $_GET['usepass'];
	
	$ckfile = $dir . "data/" . $network . "_cookies.txt";
	$ch = curl_init($loginurl);
	
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);
	curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$postResult = curl_exec($ch);
	if (curl_errno($ch)) {
		die("# unable to login! " . curl_errno($ch));
	}
	curl_close($ch);
	
}



function pull_config($xmlp, $network) {

	do_login($xmlp, $network);

	// Figre out server url for checkin
	$serverurl = $xmlp->robindash->configsync;
	if (stripos($xmlp->robindash->configsync, "http") === false) {
		$serverurl = "http://" . $xmlp->robindash->configsync . '/sync-config.php?action=show';
	}

	// Get the config
	$ckfile = $dir . "data/" . $network . "_cookies.txt";

	$ch = curl_init($serverurl);
	
	curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);
	// curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$getResult = curl_exec($ch);
	if (curl_errno($ch)) {
		die("# unable to get data! " . curl_errno($ch));
	}
	curl_close($ch);

	return $getResult;

}

function push_config($xmlp) {

}


// Verify that we are configured for remote sync and load the xml for everyone
$network = "";
if (isset($_GET['network'])) {
	$network = $_GET['network'];
} else {
	$network = $_SESSION['user'];
}
$xmlp = simplexml_load_file($dir . "data/" . $network . ".xml");	

switch ($_GET["action"]) {

	case "show":
		auth_user($xmlp);
		show_config();
		break;
	case "store":
		auth_user($xmlp);
		store_config();
		break;
	case "dosync":
		verify_localhost($xmlp);
		$remoteconfig = pull_config($xmlp, $network);
		print $remoteconfig;
		/* if remote new, sync local, if local new push */
		break;
	default:
		print "# invalid action\n";
}



